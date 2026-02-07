<?php
/******************************************************************************************
 * Copyright (C) Smackcoders. - All Rights Reserved under Smackcoders Proprietary License
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * You can contact Smackcoders at email address info@smackcoders.com.
 *******************************************************************************************/

namespace Smackcoders\WCSV;

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly
namespace Smackcoders\WCSV;

class GzHandler
{
    private static $instance = null;
    private static $smack_csv_instance = null;

    public static function getInstance() {
		if (GzHandler::$instance == null) {
			GzHandler::$instance = new GzHandler;
            GzHandler::$smack_csv_instance = SmackCSV::getInstance();
			return GzHandler::$instance;
		}
		return GzHandler::$instance;
    }
    public function extractGzipFile($sourcePath, $destinationPath)
    {
        $gz = gzopen($sourcePath, 'rb');
        if (!$gz) {
            throw new \Exception("Cannot open Gzip file: $sourcePath");
        }
        if (!is_dir($destinationPath)) {
            mkdir($destinationPath, 0777, true); 
        }
        $get_upload_dir = wp_upload_dir();

       $test = $this->getOriginalFilenameFromGz($sourcePath);
        $destinationPathfile = $destinationPath.'/'.$test;
        chmod($destinationPathfile,0777);
        $outFile = fopen($destinationPathfile, 'wb');

        if (!$outFile) {
            gzclose($gz);
            throw new \Exception("Cannot create destination file: $destinationPathfile");
        }
        while (!gzeof($gz)) {
            fwrite($outFile, gzread($gz, 4096)); 
        }
        $content = [];
        $get_upload_dirpath =  $get_upload_dir['basedir'];
        $get_upload_dirurl =  $get_upload_dir['baseurl'];
        $getFileRealPath = explode($get_upload_dirpath,$destinationPathfile);
        $getFileRealPath = $get_upload_dirurl.$getFileRealPath[1];
        $file_names = array("name"=>'' , "path"=>'');
        $file_names['name'] = $test;
        $file_names['path'] = $getFileRealPath;  
        array_push($content , $file_names);       
        gzclose($gz);
        fclose($outFile);

        return $content;
    } 
    public function getOriginalFilenameFromGz($gzFilePath) {
    $fp = fopen($gzFilePath, 'rb');
    if (!$fp) {
        return false;
    }
    $header = fread($fp, 10);
    if (strlen($header) < 10 || substr($header, 0, 2) !== "\x1f\x8b") {
        fclose($fp);
        return false; 
    }
    $flags = ord($header[3]);
    if ($flags & 0x04) {
        $extraLenData = fread($fp, 2);
        $extraLen = unpack('v', $extraLenData)[1];
        fread($fp, $extraLen);
    }
    if (!($flags & 0x08)) {
        fclose($fp);
        return false;
    }
    $filename = '';
    while (!feof($fp)) {
        $char = fread($fp, 1);
        if ($char === "\x00") break;
        $filename .= $char;
    }

    fclose($fp);
    return $filename;
}
}
