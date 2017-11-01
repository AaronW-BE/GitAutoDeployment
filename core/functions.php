<?php
/**
 * Copyright (c) Acinfo Tech .Inc
 *
 */

/**
 * Created by PhpStorm.
 * User: AaronW
 * Date: 2017/10/28
 * Time: 17:51
 */

function execShell($cmd, $cwd, &$result, $env = null) {
    error_reporting(E_ALL);

    $error_file_path = "./error-output.txt";
    $descriptorspec = array(
        0 => array("pipe", "r"),  // 标准输入，子进程从此管道中读取数据
        1 => array("pipe", "w"),  // 标准输出，子进程向此管道中写入数据
        2 => array("file", $error_file_path, "w") // 标准错误，写入到一个文件
    );

    $process = proc_open($cmd, $descriptorspec, $pipes, $cwd, $env);
    if (is_resource($process)) {

        fclose($pipes[0]);

        // get process results from pipe
        $shell_result = stream_get_contents($pipes[1]);

        $result = $shell_result;

        fclose($pipes[1]);

        return proc_close($process);
    }
    return false;
}


/**
 * @param string $cwd current working directory
 * @param string  $result the executing result
 * @param array $env other enviement params
 */
function git_init($cwd, &$result, $env = null) {
    $cmd = 'git init';
    return execShell($cmd, $cwd, $result, $env);
}

/**
 * @param string $remoteLabel
 * @param string $remoteBranch
 * @param string $params
 * @param $cwd
 * @param $result
 * @param null $env
 * @return bool|int
 */
function git_pull ($remoteLabel = 'origin', $remoteBranch = 'master', $params = '', $cwd, &$result, $env = null) {
    $cmd = "git pull {$params} {$remoteLabel} {$remoteBranch}";
    return execShell($cmd, $cwd, $result, $env);
}

/**
 * @param $git_url
 * @param $cwd
 * @param $result
 * @param null $env
 * @return bool|int
 */
function git_clone($git_url, $cwd, &$result, $env = null) {
    $cmd = "git clone {$git_url}";
    echo "will excute cmd : $cmd,  cwd is $cwd";
    return execShell($cmd, $cwd, $result, $env);
}

function composer_install($cwd, &$result, $env = null) {
    $cmd = "composer install";
    return execShell($cmd, $cwd, $result, $env);
}