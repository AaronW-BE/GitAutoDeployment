<?php
/**
 * Copyright (c) Acinfo Tech .Inc 
 *
 */

/**
 * Created by PhpStorm.
 * User: AaronW
 * Date: 2017/10/30
 * Time: 14:53
 */

class Logger {
    public $filename;
    private $handle;
    public function __construct($filename) {
        $this->filename = $filename;
        $this->handle = fopen($filename, 'a+');
    }

    public function __destruct() {
        fclose($this->handle);
    }

    public function info($msg, $tag) {
        $content = "<info>  [{$tag}] ".date('Y-m-d H:i:s')." :{$msg}\n";
        fputs($this->handle, $content);
    }
}