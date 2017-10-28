<?php
/**
 * Copyright (c) Acinfo Tech .Inc
 *
 */

/**
 * Created by PhpStorm.
 * User: AaronW
 * Date: 2017/10/28
 * Time: 15:14
 */


/**
 * Class ConfOperator
 * The class Configration file operator for easy parse config file born
 *
 * Config file format:
 *
 * #statments
 * [the config group]
 * configItem = configData
 *
 * #commnets
 * [the another config group]
 * configItem = configData
 *
 *
 * example config:
 * [git]
 * repo = "git://github.git"
 *
 * [user]
 * name = 'yourname'
 * email = 'email@email.com'
 *
 *
 */
class ConfOperator {

    const ERRCODE = [
        'PARAMETER_FORMAT_ERROR' => -1001,
        'PARAMETER_NOT_FOUND' => -4004
    ];
    public $filename;

    private $fileHandle;

    /*
     * file content
     */
    private $fileContent;

    private $paramArr;

    /**
     * ConfOperator constructor.
     * @param $filename string filename
     */
    public function __construct($filename) {
        $this->filename = $filename;
        $this->fileHandle = fopen($filename, 'a+');

        $this->fileContent = $this->content();
    }

    /**
     * Get all params arr from the conf file
     * @return mixed
     */
    public function getParams() {
        return $this->paramArr;
    }

    /**
     * @param $groupName
     * @return null | array
     */
    public function getGroupItem($groupName) {
        return in_array($groupName, $this->paramArr) ? $this->paramArr[$groupName]: null;
    }


    /**
     * the method to get config item from format "itemgroupName.itemName"
     * @param $itemName
     * @return array|bool|null
     */
    public function getItem($itemName) {
        $params = explode('.', $itemName);
        if (!$params || count($params) != 2) {
            throw new Exception('Argument 1 need be a dot format for conf file',
                self::ERRCODE['PARAMETER_FORMAT_ERROR']);
        }

        $paramGroup = $this->getGroupItem($params[0]);
        return in_array($params[1], $paramGroup) ? $this->getGroupItem($params[0]) : null;
    }

    /**
     * The same method against getItem parameter,
     * It's use to save a exist config item or insert a not exist config item
     *
     * @param $itemName
     */
    private function setItem($itemName, $value) {
        $params = explode('.', $itemName);
        if (!$params || count($params) != 2) {
            throw new Exception('Argument 1 need be a dot format for conf file',
                self::ERRCODE['PARAMETER_FORMAT_ERROR']);
        }
    }


    public function content() {
        $content = "";
        $lastKey = "";
        $newKey = false;

        $lineType = "";

        $arrParased = [];

        while (!feof($this->fileHandle)) {
            $_currentLine = fgets($this->fileHandle);
            $content .= $_currentLine;

            $lineData = $this->parseLine($_currentLine, $lineType);

            switch ($lineType) {
                case 'key':
                    $lastKey = $lineData;
                    break;
                case 'comments':
                    break;
                case 'param':
                    $arrParased[$lastKey][] = $lineData;
                    break;
                default:
                    break;
            }

        }
        $this->fileContent = $content;
        $this->paramArr = $arrParased;
        return $content;
    }

    /**
     * It's use to parse the line string to a known format.
     *
     * You need to give 2 paramaters, first: need parsed string , seccond: return type data,
     * and the function return the data parsed from linestring paramater.
     *
     * @param $lineStr string
     * @param $type string
     * @return bool|null|string
     */
    public function parseLine($lineStr, &$type) {
        $type = null;
        $lineStr = trim($lineStr);
        if (!$lineStr) {
            return null;
        }

        $_prefix_char = substr($lineStr, 0, 1);
        $_tall_char = substr($lineStr, -1, 1);

        if ($_prefix_char === '[' && $_tall_char === ']') {
            $type = 'key';
            $data = substr($lineStr, 1, strlen($lineStr) -2);
        } elseif ( $_prefix_char === '#' || $_prefix_char === ';') {
            $type = 'comments';
            $data = substr($lineStr, 1, strlen($lineStr) -2);
        }else {
            if (strpos($lineStr, '=') != false) {
                $type = 'param';

                $lineArr = explode("=", $lineStr);

                $lineArr[0] = trim($lineArr[0]);
                $lineArr[1] = trim($lineArr[1]);

                if (in_array(substr($lineArr[0], 0, 1), ['\'', '\"'])){
                    $lineArr[0] = substr($lineArr[0], 1, strlen($lineArr[0]) - 2);
                }

                if (in_array(substr($lineArr[1], 0, 1), ['\'', '\"'])){
                    $lineArr[1] = substr($lineArr[1], 1, strlen($lineArr[1]) - 2);
                }
                $data[$lineArr[0]] = $lineArr[1];
            } else {
                $type = 'null';
                $data = null;
            }
        }
        return $data;
    }

}


class ConfigFile {

}