<?php
/**
 * Copyright (c) Acinfo Tech .Inc
 *
 */

/**
 * Created by PhpStorm.
 * User: AaronW
 * Date: 2017/10/28
 * Time: 21:03
 */

/*
 * init
 */

define('TAG_SYS', 'SYS');
define('TAG_COMMON', 'COMMON');

require_once('./core/ConfOperator.php');
require_once('./core/Logger.php');


$git_conf_path = './conf/git-repo.conf';
$log_file_path = './log/log.log';

$logger = new Logger($log_file_path);

if (file_get_contents("php://input") == null) {
    header('HTTP/1.0 401 Unauthorized');

    $logger->info('request failed', TAG_SYS);

    exit(json_encode([
        'errcode' => -5000,
        'msg' => 'request failed'
    ]));
}

$remoteData = json_decode(file_get_contents("php://input"));


$logger->info("get the remote request: " . json_encode($remoteData), TAG_SYS);

/**
 * If there is not exist a git-repo.conf config file, we create a git-repo.conf file first for
 * clone, pull remote repository, etc.
 *
 * White there exist git-repo.conf, we need check the repo version, check it whether need updated,
 * if remote git repository rollback a previous version, local need to rollback also, and every operating
 * would update log file and conf file
 *
 */

$first_request = false;

$git_password = isset($remoteData->password) ? $remoteData->password : null;
if (!$git_password) {
    header('HTTP/1.0 401 Unauthorized');

    $logger->info('auth failed', TAG_SYS);

    exit(
    json_encode([
        'errcode' => 401,
        'msg' => 'need keey safety connection for deployment',
    ])
    );
}

# if the password exists, get the enccypt passwod and store in conf file

if (!file_exists($git_conf_path)) {
    $first_request = true;

    $conf = new ConfOperator($git_conf_path);

    $__salt = time();
    $crypted_password = sha1($git_password . $__salt);

    $conf->setItem('common.firstTime', $__salt);
    $conf->setItem('common.crypted-pass', $crypted_password);

    $conf->setItem('git.url', $remoteData->project->url);
    $conf->setItem('git.ssh_url', $remoteData->project->git_ssh_url);
    $conf->setItem('git.http_url', $remoteData->project->git_http_url);
    $conf->setItem('git.deploy_branch', $remoteData->project->default_branch);
}

if (!isset($conf)) {
    $conf = new ConfOperator($git_conf_path);
}


# check the password whether equals conf file stored crypted
# if not equal: return a 401 Http status code to remote

$salt = $conf->getItem('git.firstTime');
if (sha1($git_password . $salt) != $conf->getItem('git.crypted-pass') ) {
    header('HTTP/1.0 401 Unauthorized');
    exit(
    json_encode([
        'errcode' => 401,
        'msg' => 'password error',
    ])
    );
}


# Checked pass, then process common procedure

$cwd = __DIR__;

# When it's first time, check the config for clone the repo
if ($conf->getItem('git.inited') == null) {
    $resc = git_clone($remoteData->project->git_ssh_url, $cwd, $result);
    if ($resc != 0) {
        $logger->info('execute git command failed, code is '.$resc." result: ".$result, TAG_SYS);
        exit();
    }

    $conf->setItem('git.inited', true);

}

# If target branch is deploy bracn

# when webhook request type is push, remote data will contain commits key, and we can get the ref of branch
if (isset($remoteData->commits)) {
    $_arr = explode('/', $remoteData->ref);
    $push_branch = $_arr[2];

    if ($push_branch == $conf->getItem('git.deploy_branch')) {
        $resc = git_pull('origin', $conf->getItem('git.deploy_branch'),
            '', $cwd, $result);
        if ($resc != 0) {
            $logger->info('execute git command failed, code is '.$resc." result: ".$result, TAG_SYS);
            exit();
        }
    }
}

/*
function http_resposne($content, $status = 200, $content_type = 'application/json') {

}
*/
