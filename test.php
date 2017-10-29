<?php
/**
 * Copyright (c) Acinfo Tech .Inc
 *
 */

/**
 * Created by PhpStorm.
 * User: AaronW
 * Date: 2017/10/28
 * Time: 18:38
 */

require_once './functions.php';
require_once './ConfOperator.php';

/*$code = git_clone('https://github.com/vuejs/vue-cli.git', 'd:/test/test', $result);
echo $code;*/

$c = new ConfOperator('./test.conf');
$c->setItem('gitosc.name', 'abcdefg');