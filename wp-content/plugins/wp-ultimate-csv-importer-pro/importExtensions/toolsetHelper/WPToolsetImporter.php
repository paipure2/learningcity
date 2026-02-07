<?php

namespace Smackcoders\WCSV;
if (!defined('ABSPATH')) exit; // Exit if accessed directly
/**
 * 
 */

require_once "ToolsetImporter.php";

class WPToolsetImporter extends ToolsetImporter
{
	private static $wpToolsetImporter = NULL,$mediaInstance;
	private $dataArray;
	private $metaData;
	private $fieldType;

	private $postType;
	private $wpTypesFields;


	static function getInstance() {
		if (self::$wpToolsetImporter == NULL) {
			self::$wpToolsetImporter = new WPToolsetImporter();
			self::$mediaInstance = new MediaHandling();
		}
		return self::$wpToolsetImporter;
	}

	function set($dataArray, $metaData, $field_Type,$wpTypesFields,$postType) {
		$this->dataArray 		= $dataArray;
		$this->metaData 		= $metaData;
		$this->fieldType 		= $field_Type;
		$this->wpTypesFields 	= $wpTypesFields;
		$this->postType 		= $postType;

	}

	function import($postId,$post_val,$hash_key,$line_number,$gmode,$templatekey) {
		$ToolsetImport = ToolsetImporter::getInstance();
		
		//get fields and groups of homegroup post   
		if ($this->postType == 'Users') {
			$postTypeValue = 'wp-types-user-group';
		}else{            	
			$postTypeValue = 'wp-types-group';
		}

		// $fieldsAndGroups = $this->format($this->dataArray['Parent_Group'],$postTypeValue);
		if(isset($this->dataArray['Parent_Group'])){
			
			$fieldsAndGroups = $ToolsetImport->format($this->dataArray['Parent_Group'],$postTypeValue);
		}else{
			$fieldsAndGroups= 0;
		}		
		if ($fieldsAndGroups == 0) {
			$this->insertCustomFields($postId,$this->postType,$post_val, $hash_key,$line_number,$gmode,$templatekey);

		}else{
			$this->loopAndInsertField($postId,$fieldsAndGroups,0,true,$post_val,$hash_key,$line_number,$gmode,$templatekey);
		}
		return;                                

	}

	function loopAndInsertField($postId,$fieldsAndGroups,$index,$isParent,$post_val,$hash_key,$line_number,$gmode,$templatekey) {		
		foreach ($fieldsAndGroups as $fieldkey => $fieldvalue) {
			foreach ($fieldvalue as $key => $value) {
				$data=$this->explodeFunction('_',$value);
				if (isset($data[1]) && isset($data[2]) && $data[1]=='repeatable' && $data[2]=='group') {
					//send repeatable group id.
					$groupName=$this->getRepeatableName($data[3]);
					$groupRelationId=$this->getRelationshipId($groupName);
					$groups=$this->dataArray[$groupName];               
					// csv directs to create 2 repetable group
					$csvGroupsArray =$this->explodeFunction('|',$groups);
					$elementString=$this->getRepeatableMetaValue($data[3]);
					//from user created toolset group , don't change the symbol
					$elementArray =$this->explodeFunction(',',$elementString);
					if ($index < count($csvGroupsArray)) {
						for ($i=0; $i < count($csvGroupsArray) ; $i++) { 
							//create post , post_type =  $groupName->ram-house $csvGroupsArray[i]
							$childPostId=$this->insertPost($groupName,$csvGroupsArray[$i]);
							$this->insertRelationship($groupRelationId,$postId,$childPostId);
							$this->loopAndInsertField($childPostId, array($elementArray), $i,false,$post_val,$hash_key,$line_number,$gmode,$templatekey);
							update_post_meta($childPostId, 'toolset-post-sortorder', $i+1);

						}
					}
				} else {  // simply make entry for homefroup fields. 


					$fieldValues=$this->dataArray[$value];              
					$fieldsArray =explode('|',$fieldValues);
					$metaKey=$this->metaData[$value];
					$fieldType=$this->fieldType[$value]; 

					if (!$isParent) {
						if ($index < count($fieldsArray)) {

							$fieldValue=$fieldsArray[$index];
							$this->checkFieldType($postId,$fieldValue,$fieldType,$metaKey,$post_val,$hash_key,$line_number,$gmode,$templatekey);
						}
					} else {
						$this->checkFieldType($postId,$fieldValues,$fieldType,$metaKey,$post_val,$hash_key,$line_number,$gmode,$templatekey);
					}
				}
			}
		}

	}

	function insertCustomFields($postId,$postType,$post_val, $hash_key,$line_number,$gmode,$templatekey){
		foreach ($this->dataArray as $key => $value) {
			if(isset($this->fieldType[$key]) && isset($this->metaData[$key])){
				
				$fieldType=$this->fieldType[$key];
				$metaKey=$this->metaData[$key];
				$listTaxonomy = get_taxonomies();
			    if(!empty($fieldType) && !empty($metaKey)) {
				    if ( in_array( $postType, $listTaxonomy ) ) {
					$values = explode( '|', $value );
					foreach ( $values as $keys => $value1 ) {
						$this->insertTermFields( $postId, $value1, $fieldType, $metaKey,$line_number );
					}
				} else {
					$this->checkFieldType( $postId, $value, $fieldType, $metaKey,$post_val,$hash_key,$line_number,$gmode,$templatekey );
			    }
			   }
			}
			
		} 
	}
	function insertTermFields($postId,$fieldValue,$field_Types,$metaKey,$line_number)
	{
		
		if($field_Types=='checkboxes'){
			
			$fieldTypeArray=array_flip($this->metaData);
			$fieldTypeValue=$fieldTypeArray[$metaKey];

			$term_fields = get_option('wpcf-termmeta');
			$wpTypes = $term_fields[$fieldTypeValue]['data']['options'];
			//$wpTypes=$this->wpTypesFields[$fieldTypeValue]['data']['options'];
			
			$checkbox_array = array();
			$fieldValueArrays =$this->explodeFunction(',',$fieldValue);
			
			foreach ($fieldValueArrays as $keys => $values) {
				$values = trim($values);
				foreach ($wpTypes as $key1 => $value1) {
					if ($values == $value1['title']) {
						$term_value = $value1['set_value'];
						$checkbox_array[$key1] = array($term_value);	
					}
				}
			}	
			add_term_meta($postId,$metaKey,$checkbox_array);	
		}
		else
		{      
			$this->InsertUpdateTerm($postId,$metaKey,$field_Types,$fieldValue,$line_number);
		}
	}


	function checkFieldType($postId,$fieldValue,$fields_Type,$metaKey,$post_val,$hash_key,$line_number,$gmode,$templatekey){		
		global $wpdb;
		if ($fields_Type == 'checkboxes') {
			$fieldValueArray =$this->explodeFunction(',',$fieldValue); 
			$fieldValueArray=array_flip($fieldValueArray);

			$fieldTypeArray=array_flip($this->metaData);
			$fieldTypeValue=$fieldTypeArray[$metaKey];

			$wpTypes=$this->wpTypesFields[$fieldTypeValue]['data']['options'];
			$checkbox_array = array();
			
			foreach ($fieldValueArray as $key => $value) {
				$key = trim($key);
				foreach ($wpTypes as $key1 => $value1) {
					if ($key == $value1['title']) {
						$checkbox_array[$key1] = array($value1['set_value']);
					}
				}
			}
			
			//update_post_meta($postId, $metaKey, $checkbox_array);
			if ($this->postType == 'Users') {
				update_user_meta($postId,$metaKey,$checkbox_array);
			}else{
				update_post_meta($postId,$metaKey,$checkbox_array);
			}
		}elseif ($fields_Type == 'post') {
			
			$fieldValueArray=array_flip($this->metaData);
			$relationshipSlug=$fieldValueArray[$metaKey];
			$groupRelationId=$this->getRelationshipId($relationshipSlug);
			if (is_numeric($fieldValue)) {
				$field_Value=$fieldValue;
			}elseif(is_string($fieldValue)){
				$query = "SELECT id FROM {$wpdb->prefix}posts WHERE post_title ='{$fieldValue}' AND post_status='publish'";
				$name = $wpdb->get_results($query);
				if (!empty($name)) {
					$field_Value=$name[0]->id;
				}else{
					return;
				}
			}
			$interpostquery ="select display_name_plural,intermediary_type from {$wpdb->prefix}toolset_relationships where slug ='{$relationshipSlug}'";
			$interpost = $wpdb->get_results($interpostquery);
			$array=array('post_title' => $interpost[0]->display_name_plural.': '.$field_Value.'-'.$postId,
			'post_type' => $interpost[0]->intermediary_type,'post_status'=> 'publish');
			$interid=wp_insert_post($array);
			if (class_exists('SitePress')) {
				$get_trid = $wpdb->get_results("select trid from {$wpdb->prefix}icl_translations where element_id ='$interid'");
				$trid = $get_trid[0]->trid;
				if(empty($trid)){
					$trid = 0;
				}
			}
			else{
				$trid = 0;
			}   

			$wpdb->insert($wpdb->prefix . 'toolset_connected_elements', array(
				'element_id' => $interid,
				'domain' => 'posts',
				'wpml_trid' => $trid
			));
			$pinter = "select id from {$wpdb->prefix}toolset_connected_elements where element_id ='{$interid}'";
			$pinterres = $wpdb->get_results($pinter);
			$inter_id = $pinterres[0]->id;
			

			//$wpdb->update($wpdb->toolset_connected_elements, ['group_id' => $inter_id], ['id' => $inter_id]);
			$wpdb->get_results("UPDATE {$wpdb->prefix}toolset_connected_elements set group_id = '$inter_id' where id = $inter_id");
			if(!empty($field_Value) && !empty($metaKey)) {
				$this->insertRelationship( $groupRelationId, $field_Value, $postId,$inter_id);
				// update_post_meta( $postId, $metaKey, $field_Value );
			}

		}else{
			//add_post_meta($postId,$metaKey,$fieldValue);add_user_meta
			$this->InsertUpdateData($postId,$metaKey,$fields_Type,$fieldValue,$post_val, $hash_key,$line_number,$gmode,$templatekey);
		}

	}
	function InsertUpdateTerm($postId,$metaKey,$fields_Types,$fieldValue,$line_number)
	{
		$helpers_instance = ImportHelpers::getInstance();
		$fieldTypeArray=array_flip($this->metaData);
		$fieldTypeValue=$fieldTypeArray[$metaKey];
		settype($isRepetitive, "integer");
		$isRepetitive=$this->wpTypesFields[$fieldTypeValue]['data']['repetitive'];

		if (!empty($isRepetitive) && $isRepetitive == 1) {
			$valuesArray =$this->explodeFunction('|',$fieldValue);
			foreach ($valuesArray as $values) {
				$values=trim($values);

				if ($fields_Types == 'date') {
					if(!empty($values)) {
						$values = trim($values);
						//$dateformat = "Y-m-d";
						$checkformat = $this->wpTypesFields[$fieldTypeValue]['data']['date_and_time'];		
						$dateformat =$checkformat == 'date'?"Y-m-d" : "Y-m-d H:i:s";
						$fieldnm = substr($metaKey,strpos($metaKey,'-')+1);
						$values = $helpers_instance->validate_datefield($values,$fieldnm,$dateformat,$line_number);
					$values = strtotime($values);					
					}
					else {
						$values = $values;
					}
				}elseif ($fields_Types == 'skype') {
					$values = array(
							'skypename' => $values,
							'action' => 'chat',
							'color' => 'blue',
							'size' => '32'
						       );
				}
				add_term_meta($postId,$metaKey,$values);
			}
		}else{
			if($isRepetitive == 0){
				if ($fields_Types == 'date') {
					if(!empty($fieldValue)) {
						$fieldValue = trim($fieldValue);
						//$dateformat = "Y-m-d";
						$checkformat = $this->wpTypesFields[$fieldTypeValue]['data']['date_and_time'];		
						$dateformat =$checkformat == 'date'?"Y-m-d" : "Y-m-d H:i:s";
						$fieldnm = substr($metaKey,strpos($metaKey,'-')+1);
						$fieldValue = $helpers_instance->validate_datefield($fieldValue,$fieldnm,$dateformat,$line_number);
						$fieldValue = strtotime($fieldValue);					
					}
					else {
						$fieldValue = $fieldValue;
					}
				}elseif ($fields_Types == 'skype') {

					$fieldValue = array(
							'skypename' => $fieldValue,
							'action' => 'chat',
							'color' => 'blue',
							'size' => '32'
							);
				}
				add_term_meta($postId,$metaKey,$fieldValue);
			}
		}
	}

	function InsertUpdateData($postId,$metaKey,$fields_Types,$fieldValue,$post_val,$hash_key,$line_number,$gmode,$templatekey)
	{ 
		global $wpdb;		
		$helpers_instance = ImportHelpers::getInstance();
		settype($isRepetitive, "integer");
		$fieldTypeArray=array_flip($this->metaData);
		$fieldTypeArray[$metaKey] = isset($fieldTypeArray[$metaKey]) ? $fieldTypeArray[$metaKey]  : '';
		$fieldTypeValue=$fieldTypeArray[$metaKey];
		if(isset($this->wpTypesFields[$fieldTypeValue]['data']['repetitive'])){
			$isRepetitive=$this->wpTypesFields[$fieldTypeValue]['data']['repetitive'];
		}
		if (!empty($isRepetitive) && $isRepetitive == 1 && $fields_Types!='image' && $fields_Types != 'file') {			
			$valuesArray =$this->explodeFunction('|',$fieldValue);
			foreach ($valuesArray as $values) {
				$values=trim($values);
				if ($fields_Types == 'date') {
					if(!empty($values)){
						$checkformat = $this->wpTypesFields[$fieldTypeValue]['data']['date_and_time'];		
						$dateformat =$checkformat == 'date'?"Y-m-d" : "Y-m-d H:i:s";
						$fieldnm = substr($metaKey,strpos($metaKey,'-')+1);
						$values = $helpers_instance->validate_datefield($values,$fieldnm,$dateformat,$line_number);
						if(!empty($values))
						$values = strtotime($values);						
					}
					else {
						$values = $values;
					}					
				}elseif ($fields_Types == 'skype') {
					$values = array(
							'skypename' => $values,
							'action' => 'chat',
							'color' => 'blue',
							'size' => '32'
						       );
				}
				elseif ($fields_Types == 'google_address'){
					$values =$values;	
				}
				
				if ($this->postType == 'Users') {
					add_user_meta($postId,$metaKey,$values);
				}else{
					if($fields_Types == 'google_address'){

						add_post_meta($postId,$metaKey,$values);
						$meta_id  = $wpdb->get_results( $wpdb->prepare( "select meta_id from {$wpdb->prefix}postmeta where meta_key = %s AND post_id = %d", $metaKey,$postId ) );
					    $array=json_decode(json_encode($meta_id),true);
					    foreach($array as $key=>$keyval ){
						   foreach($keyval as $keys => $keysval){
							  $metadata[$key] = $keysval;
						   }
					    }
					    $meta_key = '_'.$metaKey.'-sort-order';
						update_post_meta($postId,$meta_key,$metadata);
					}
					else{
						add_post_meta($postId,$metaKey,$values);
					}
					
				}
			}
		}
		if($fields_Types == 'image' || $fields_Types == 'file') {
			$plugin = 'types';
			$valuesArray = explode('|',$fieldValue);
			$shortcode_table = $wpdb->prefix . "ultimate_csv_importer_shortcode_manager";
			$indexs = 0;
			foreach($valuesArray as $value) {
				$ext = pathinfo($value, PATHINFO_EXTENSION );
				WPToolsetImporter::$mediaInstance->store_image_ids($i=1);
					if ($this->postType == 'Users') {
						$get_import_type = 'user';
					}
					else{
						$get_import_type = 'post';
					}
					$attachid = WPToolsetImporter::$mediaInstance->image_meta_table_entry($line_number,$post_val, $postId, $metaKey, $value, $hash_key, 'types', $get_import_type,$templatekey,$gmode,'','','','',$indexs);
					$attachids[] =$attachid;
					if(empty($attachid)){
						if ($this->postType == 'Users') {
							add_user_meta($postId,$metaKey,$value);
						}else{
							add_post_meta($postId,$metaKey,$value);
						}	
					}
					else{
						$guid=$wpdb->get_row("select guid from ".$wpdb->prefix."posts where ID='$attachid'");
						$values=$guid->guid;
						if ($this->postType == 'Users') {
							add_user_meta($postId,$metaKey,$value);
						}else{
							add_post_meta($postId,$metaKey,$values);
						}
					}

				//update_image_meta
				$get_existing_image_option = get_option('smack_schedule_image_exists_types_'.$postId);
				if($get_existing_image_option){
					self::$mediaInstance->acfimageMetaImports($attachids, $post_val, $plugin);
					delete_option('smack_schedule_image_exists_types_'.$postId);
				}
				$indexs++;
			} 
		}
		else{			
			if($isRepetitive == 0){
				if ($fields_Types == 'date') {									
					if(!empty($fieldValue)){
						$fieldValue = trim($fieldValue);
						//$dateformat = "Y-m-d";
						$checkformat = $this->wpTypesFields[$fieldTypeValue]['data']['date_and_time'];		
						$dateformat =$checkformat == 'date'?"Y-m-d" : "Y-m-d H:i:s";
						$fieldnm = substr($metaKey,strpos($metaKey,'-')+1);
						$fieldValue = $helpers_instance->validate_datefield($fieldValue,$fieldnm,$dateformat,$line_number);
						if(!empty($fieldValue))
						$fieldValue = strtotime($fieldValue);						
					}
					else {
						$fieldValue = $fieldValue;
					}
					
				}elseif ($fields_Types == 'skype') {

					$fieldValue = array(
							'skypename' => $fieldValue,
							'action' => 'chat',
							'color' => 'blue',
							'size' => '32'
							);
				}elseif($fields_Types == 'google_address'){
					$fieldValue = $fieldValue;
				}

				if ($this->postType == 'Users') {
					add_user_meta($postId,$metaKey,$fieldValue);
				}else{
					if($fields_Types == 'google_address'){
						add_post_meta($postId,$metaKey,$fieldValue);
					}
					else{
						add_post_meta($postId,$metaKey,$fieldValue);
					}
					
				}
			}
		}
	}
}
