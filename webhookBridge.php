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

require_once('./ConfOperator.php');

$git_conf_path = './conf/git-repo.conf';


$remoteData = json_decode($GLOBALS['HTTP_RAW_POST_DATA']);

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

    $conf->setItem('git.firstTime', $__salt);
    $conf->setItem('git.crypted-pass', $crypted_password);
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



/*
function http_resposne($content, $status = 200, $content_type = 'application/json') {

}
*/
