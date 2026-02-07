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

class XmlHandler {
	private static $xml_instance = null;
	private $result_xml = [];
	public $total_xml_count;
	public function __construct(){
		add_action('wp_ajax_get_parse_xml',array($this,'parse_xml'));
	}

	public static function getInstance() {

		if (XmlHandler::$xml_instance == null) {
			XmlHandler::$xml_instance = new XmlHandler;
			return XmlHandler::$xml_instance;
		}
		return XmlHandler::$xml_instance;
	}


	public function parse_xml(){
		check_ajax_referer('smack-ultimate-csv-importer-pro', 'securekey');
		$row_count = intval($_POST['row']);
		$hash_key = sanitize_key($_POST['HashKey']);

		$smack_csv_instance = SmackCSV::getInstance();
		$upload_dir = $smack_csv_instance->create_upload_dir();

		$upload_dir_path = $upload_dir. $hash_key;
		if (!is_dir($upload_dir_path)) {
			wp_mkdir_p( $upload_dir_path);
		}
		chmod($upload_dir_path, 0777);   
		$path = $upload_dir . $hash_key . '/' . $hash_key;    

		$response = [];
		$content = file_get_contents($path);
		$content = str_replace('xmlns="http://www.w3.org/2005/Atom" xmlns:g="http://base.google.com/ns/1.0"','',$content);
		/********************** */
		$doc = new \DOMDocument();
		libxml_use_internal_errors(true); // To suppress warnings about malformed XML
		$doc->loadXML($content);
		$content = preg_replace('/<copyright\s*\/>/s', '', $content);
		$channel = $doc->getElementsByTagName('channel')->item(0);	
		if ($channel) {
			// Move all child nodes of <channel> to <rss>
			$rss = $doc->getElementsByTagName('rss')->item(0);
			while ($channel->firstChild) {
				$rss->appendChild($channel->firstChild);
			}
			$channel->parentNode->removeChild($channel);
			$newContent = $doc->saveXML();
		}
		else{
			$newContent = $content;
		}
		// Save the updated content back to the file
		file_put_contents($path, $newContent);
		/********************** */
 		//file_put_contents($path,$content);
		$xml = simplexml_load_file($path);
		$xml_arr = json_decode( json_encode($xml) , 1);
		if (is_countable($xml_arr) && count($xml_arr) == count($xml_arr, COUNT_RECURSIVE)) 
		{
			$item = $xml->addchild('item');
			foreach($xml_arr as $key => $value){
				$xml->item->addchild($key,$value);
				unset($xml->$key);
			}
			$arraytype = "not parent";
			$xmls['item'] =$xml_arr;
		}
		else
		{
			$arraytype = "parent";
		}
		$i=0;
		$childs=array();
		foreach($xml->children() as $child => $val){   
			// $child_name = $child->getName();  
			$values =(array)$val;
			if(empty($values)){
				if (!in_array($child, $childs,true))
				{
					$childs[$i++] = $child;
				}
			}
			else{
				if(array_key_exists("@attributes",$values)){
					if (!in_array($child, $childs,true))
					{
						$childs[$i++] = $child;
					}   
				}
				else{
					foreach($values as $k => $v){
						is_array($values[$k])  ? $checks = implode(',', $values[$k]) : $checks = (string)$values[$k];
						if(is_numeric($k)){
							if(empty($checks)){
								if (!in_array($child, $childs,true))
								{
									$childs[$i++] = $child;
								}   	
							}
						}
						else{
							if(!empty($checks)){
								if (!in_array($child, $childs,true))
								{
									$childs[$i++] = $child;
								}   	
							}
						}
					}
				}
			}
		}
		// $child_name =current($childs);
		$f=0;
		$res =array();
		$r=0;
		$file = 'file'.$f;
		$file = array();
		$this->total_xml_count = 0;
		if($arraytype == "parent"){
			foreach($childs as $child_name){
				$this->result_xml =array();
				$total_xml_count = $this->get_xml_count($path , $child_name);
			
				foreach($xml->children() as $child){  
					$child_names =  $child->getName();  
				}
				$this->total_xml_count = $this->total_xml_count + $total_xml_count;
				// $total_xml_count = $this->get_xml_count($path , $child_name);
				if($total_xml_count == 0){
					$sub_child = $this->get_child($child,$path);
					$child_name = $sub_child['child_name'];
					$this->total_xml_count = $sub_child['total_count'];
				}

				$doc = new \DOMDocument();
				$doc->load($path);
				$row = $row_count - 1;  
					$node = $doc->getElementsByTagName($child_name)->item($row);
					$this->tableNodes($node);
				// $node = $doc->getElementsByTagName($child_name)->item($row);
				// $this->tableNodes($node);
				$res['xml_array'] = $this->result_xml;
				$res['count'] = $total_xml_count;
					
				array_push($file,$res);
				$response['file']= $file;
				$response['total_rows'] = $this->total_xml_count;
				$response['success'] = true;
				$f++;	
			}
		}
		else{
			$doc = new \DOMDocument();
			$doc->load($path);
			$row = $row_count - 1; 
			$total_xml_count = 1;
			foreach($xml_arr as $key => $value){
				$node = $doc->getElementsByTagName($key)->item($row);
				$this->tableNodes($node);
			}
			$res['xml_array'] = $this->result_xml;
			$res['count'] = $total_xml_count;
					
			array_push($file,$res);
			$response['file']= $file;
			$response['total_rows'] = $this->total_xml_count;
			$response['success'] = true;
		}
		echo  wp_json_encode($response);
		wp_die();
	}

	public function parse_xmls($hash_key,$line_number = null,$mode = null,$path = null){

		if($mode != 'CLI') {
		$smack_csv_instance = SmackCSV::getInstance();
		$upload_dir = $smack_csv_instance->create_upload_dir();

		$upload_dir_path = $upload_dir. $hash_key;
		if (!is_dir($upload_dir_path)) {
			wp_mkdir_p( $upload_dir_path);
		}
		chmod($upload_dir_path, 0777);   
		$path = $upload_dir . $hash_key . '/' . $hash_key;    
		}
		$response = [];
		$content = file_get_contents($path);
        $content = str_replace('xmlns="http://www.w3.org/2005/Atom" xmlns:g="http://base.google.com/ns/1.0"','',$content);
        $doc = new \DOMDocument();
		libxml_use_internal_errors(true); // To suppress warnings about malformed XML
		$doc->loadXML($content);
		$content = preg_replace('/<copyright\s*\/>/s', '', $content);
		$channel = $doc->getElementsByTagName('channel')->item(0);	
		if ($channel) {
			// Move all child nodes of <channel> to <rss>
			$rss = $doc->getElementsByTagName('rss')->item(0);
			while ($channel->firstChild) {
				$rss->appendChild($channel->firstChild);
			}
			$channel->parentNode->removeChild($channel);
			$newContent = $doc->saveXML();
		}
		else{
			$newContent = $content;
		}
		// Save the updated content back to the file
		file_put_contents($path, $newContent);
		$xml = simplexml_load_file($path);
		$xml_arr = json_decode( json_encode($xml) , 1);
		if (count($xml_arr) == count($xml_arr, COUNT_RECURSIVE)) 
		{
			$item = $xml->addchild('item');
			foreach($xml_arr as $key => $value){
				$xml->item->addchild($key,$value);
				unset($xml->$key);
			}
			$arraytype = "not parent";
			$xmls['item'] =$xml_arr;
		}
		else
		{
			$arraytype = "parent";
		
		}
		$i=0;
		$childs=array();
		foreach($xml->children() as $child => $val){   
			// $child_name =  $child->getName();  
			$values =(array)$val;
			if(empty($values)){
				if (!in_array($child, $childs,true))
				{
					$childs[$i++] = $child;
	
				}
			}
			else{
				if(array_key_exists("@attributes",$values)){
					if (!in_array($child, $childs,true))
					{
						$childs[$i++] = $child;
					}   
				}
				else{
					foreach($values as $k => $v){
						is_array($values[$k])  ? $checks = implode(',', $values[$k]) : $checks = (string)$values[$k];
						if(is_numeric($k)){
							if(empty($checks)){
								if (!in_array($child, $childs,true))
								{
									$childs[$i++] = $child;
								}   	
							}
						}
						else{
							if(!empty($checks)){
								if (!in_array($child, $childs,true))
								{
									$childs[$i++] = $child;
								}   	
							}
						}
					}
				}
			}
		}
		// $child_name =current($childs);
		$f=0;
		$res =array();	
		if($arraytype == "parent"){
			foreach($childs as $child_name){
				$this->result_xml =array();
				$total_xml_count = $this->get_xml_count($path , $child_name);
		
				foreach($xml->children() as $child){  
					$child_names =  $child->getName();  
				}
				if($total_xml_count == 0){
					$sub_child = $this->get_child($child,$path);
					$child_name = $sub_child['child_name'];
					$total_xml_count = $sub_child['total_count'];
				}
				// $total_xml_count = $this->get_xml_count($path , $child_name);
				$doc = new \DOMDocument();
				$doc->load($path);
				if ($line_number !== null) {
					$node = $doc->getElementsByTagName($child_name)->item($line_number);
				} else {
					$node = $doc->getElementsByTagName($child_name)->item(0);
				}
				$this->tableNodes($node);
				$response['xml_array'.$f] = $this->result_xml;
				$response['success'] = true;
				$response['total_rows'.$f] = $total_xml_count;
				$response['count'.$f] = $total_xml_count;
				$f++;
			}
		}
		else{
			$total_xml_count = 1;
			$doc = new \DOMDocument();
			$doc->load($path);
			foreach($xml_arr as $key => $value){
				$node = $doc->getElementsByTagName($key)->item($line_number);
				$this->tableNodes($node);
			}
			$response['xml_array'.$f] = $this->result_xml;
			$response['success'] = true;
			$response['total_rows'.$f] = $total_xml_count;
			$response['count'.$f] = $total_xml_count;
		}
		return $response;
	}

	public function get_child($child,$path){
		foreach($child->children() as $sub_child){
			$sub_child_name = $sub_child->getName();
		}
		$total_xml_count = $this->get_xml_count($path , $sub_child_name);
		if($total_xml_count == 0){
			$this->get_child($sub_child,$path);
		}
		else{
			$result['child_name'] = $sub_child_name;
			$result['total_count'] = $total_xml_count;
			return $result;
		}
	}

	public function tableNodes($node, $prefix = '')
{
	if ($node->nodeName !== '#text' && $node->nodeType === XML_ELEMENT_NODE) {

		$currentPath = $prefix ? $prefix . '/' . $node->nodeName : $node->nodeName;

		// Handle attributes
		if ($node->hasAttributes()) {
			for ($i = 0; $i < $node->attributes->length; ++$i) {
				$attr_nodes = $node->attributes->item($i);
				if ($attr_nodes && $attr_nodes->nodeName && $attr_nodes->nodeValue) {
					$xml_array = array();
					$xml_array['name'] = $attr_nodes->nodeName; // 👈 Only show attr name
					$xml_array['node_path'] = $attr_nodes->getNodePath();
					$xml_array['value'] = $attr_nodes->nodeValue;
					array_push($this->result_xml, $xml_array);
				}
			}
		}

		$hasElementChildren = false;
		foreach ($node->childNodes as $child) {
			if ($child->nodeType === XML_ELEMENT_NODE) {
				$hasElementChildren = true;
				break;
			}
		}

		if (!$hasElementChildren && trim($node->textContent) !== '') {
			$xml_array = array();
			$xml_array['name'] = $node->nodeName; // 👈 Show only the final tag name (not full path)
			$xml_array['node_path'] = $node->getNodePath(); // 👈 Full path used internally
			$xml_array['value'] = trim($node->textContent);
			array_push($this->result_xml, $xml_array);
		}

		// Recurse
		foreach ($node->childNodes as $child) {
			$this->tableNodes($child, $currentPath);
		}
	}
}





	/**
	 * Get xml rows count.
	 * @param  string $eventFile - path to file
	 * @return int
	 */
	public function get_xml_count($eventFile , $child_name){
		$doc = new \DOMDocument();
		$doc->load($eventFile);
		$nodes=$doc->getElementsByTagName($child_name);
		$total_row_count = $nodes->length;
		return $total_row_count;	
	}
}

