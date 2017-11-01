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
        'PARAMETER_NOT_FOUND' => -4004,
        'PARAMETER_ERROR' => -4001,
    ];

    const L_GROUP = 0;
    const L_ITEM = 1;

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
     * close opened file handle
     */
    public function __destruct() {
        if ($this->fileHandle) {
            fclose($this->fileHandle);
        }
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
        return isset($this->paramArr[$groupName]) ? $this->paramArr[$groupName]: null;
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
        return isset($paramGroup[$params[1]]) ? $paramGroup[$params[1]] : null;
    }

    /**
     * The same method against getItem parameter,
     * It's use to save a existing config item or insert a not existing config item
     *
     * @param $itemName
     */
    public function setItem($itemName, $value) {
        $params = explode('.', trim($itemName));
        if (!$params || count($params) != 2) {
            throw new Exception('Argument 1 need be a dot format for conf file',
                self::ERRCODE['PARAMETER_FORMAT_ERROR']);
        }

        # checkif there is exists a group named given
        $groupName = $params[0];
        $item = $params[1];

        if ($this->getGroupItem($groupName) == null) {
//            echo "not exists a group name {$groupName} \n";
            # if group is not found, seek the file handle to file tail,
            fseek($this->fileHandle, 0, SEEK_END);
            fputs($this->fileHandle, "\n");
            fputs($this->fileHandle, $this->assembleConfigString($groupName, null, self::L_GROUP));

            # move handle to the next line, and write string
            fputs($this->fileHandle, "\n");
            fputs($this->fileHandle, $this->assembleConfigString($item, trim($value), self::L_ITEM));
            fputs($this->fileHandle, "\n");
        } else {


            $modify_mode = $this->getItem(implode('.', $params)) == null ? false : true;

//            echo $modify_mode ? 'modify mode' : 'add mode';

            # get the group name with file handle position
            #
            # first need to move file handle to file header
            fseek($this->fileHandle, 0, SEEK_SET);

            # Build the new temp file for exchange the content will be replaced, if build fail, throw a
            # exception directly
            $tempfile = tempnam('./tmp', 'temp');
            $tempfile_handle = fopen($tempfile, 'w');
            if (!$tempfile_handle) {
                throw new Exception('temp file create failed');
            }

            $flag_catch_group = false;
            while (!feof($this->fileHandle)) {

                $lineString = fgets($this->fileHandle);

                # if catch the group name we would confirm the group name with file handle
                if (trim($lineString) == $this->assembleConfigString($groupName, '',
                        self::L_GROUP)) {
//                    echo "\n catch the group {$groupName} \n";
                    $flag_catch_group = true;
                    fputs($tempfile_handle, $lineString);
                    continue;
                }

                if ($flag_catch_group) {
                    # If there exists a config item, we need to modify it
                    if ($modify_mode) {
                        if (
                            $this->parseItemLine($lineString)[0] == $item
                        ) {
//                            echo "\n ready to write string \n";
                            fputs($tempfile_handle, $this->assembleConfigString($item, $value, self::L_ITEM));
                            fputs($tempfile_handle, "\n");
                            $flag_catch_group = false;
                            continue;
                        }
                    }else {

                        # While get the group label need to move current file handle to next line
                        # for write a config item, and break this loop
                        #
                        # Notice: fseek cannot move the file handle to the not exists position, means that
                        # fseek function can move max langth is eof only
                        # So, there need put a line break to add new line, there just put a \n label, need optimize

                        # write config item
//                        echo "ready to write string {$item} - {$value}";
                        fputs($tempfile_handle, $lineString);
                        fputs($tempfile_handle, $this->assembleConfigString($item, $value, self::L_ITEM));
                        fputs($tempfile_handle, "\n");
                        $flag_catch_group = false;
                        continue;
                    }
                }
                fputs($tempfile_handle, $lineString);

            }

            # copy the temp file to the origin conf file, and delete temp file
            copy($tempfile, $this->filename);

            fclose($tempfile_handle);
            unlink($tempfile);
        }

        $this->content();
    }

    /**
     * assemble the config file line string
     * @param $item
     * @param $value
     * @param $type
     */
    private function assembleConfigString($item, $value, $type) {
        $_types = [
            self::L_GROUP, self::L_ITEM
        ];
        if (!in_array($type, $_types)) {
            throw new Exception('parameter of type is error',self::ERRCODE['PARAMETER_ERROR']);
        }

        $lineString = "";
        if ($type == $_types[0]) {
            $lineString = '[' . trim($item) . ']';
        }else if ($type == $_types[1]) {
            $lineString = implode('=', [$item, $value]);
        }
        return $lineString;
    }

    /**
     * Parse the string to readable format
     * @param $lineString
     * @return array|bool
     */
    private function parseItemLine($lineString) {
        $lineString = trim($lineString);
        $resArr = explode('=', $lineString);
        if (is_array($resArr) && count($resArr) == 2) {
            return [
                trim($resArr[0]),
                trim($resArr[1])
            ];
        }
        return false;
    }

    /**
     * get handle position
     */
    private function getHandlePosition() {
        return $this->fileHandle != null ? ftell($this->fileHandle) : null;
    }


    public function content() {
        $content = "";
        $lastKey = "";
        $newKey = false;

        $lineType = "";

        $arrParased = [];

        fseek($this->fileHandle, 0, SEEK_SET);

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
                    $arrParased[$lastKey][$lineData[0]] = $lineData[1];
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
                $data = $lineArr;
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