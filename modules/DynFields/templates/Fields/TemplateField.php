<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/*********************************************************************************
 * The contents of this file are subject to the SugarCRM Public License Version
 * 1.1.3 ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied.  See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * All copies of the Covered Code must include on each user interface screen:
 *    (i) the "Powered by SugarCRM" logo and
 *    (ii) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.  See full license for
 * requirements.
 *
 * The Original Code is: SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) 2004-2006 SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 ********************************************************************************/
$GLOBALS['studioReadOnlyFields'] = array('date_entered'=>1, 'date_modified'=>1, 'created_by'=>1, 'id'=>1, 'modified_user_id'=>1);
class TemplateField{
	/*
		The view is the context this field will be used in
		-edit
		-list
		-detail
		-search
	*/
	var $view = 'edit';
	var $name = '';
	var $label = '';
	var $id = '';
	var $size = '20';
	var $max_size = '255';
	var $required_option = 'optional';
	var $default_value = '';
	var $data_type = 'varchar';
	var $bean;
	var $ext1 = '';
	var $ext2 = '';
	var $ext3 = '';
	var $audited= 0;
	var $mass_update = 0;
    var $duplicate_merge=0;
    
	var $new_field_definition;
	
	/*
		HTML FUNCTIONS
	*/
	function get_html(){
		$view = $this->view;
		if(!empty($GLOBALS['studioReadOnlyFields'][$this->name]))$view = 'detail';
		switch($view){
			case 'search':return $this->get_html_search();
			case 'edit': return $this->get_html_edit();
			case 'list': return $this->get_html_list();
			case 'detail': return $this->get_html_detail();
				
		}		
	}
	function set($values){
	
		foreach($values as $name=>$value){
			$this->$name = $value;	
		}
		
	}
	
	function get_html_edit(){
		return 'not implemented';	
	}
	
	function get_html_list(){
		return $this->get_html_detail();	
	}
	
	function get_html_detail(){
		return 'not implemented';	
	}
	
	function get_html_search(){
		return $this->get_html_edit();	
	}
	function get_html_label(){
		if (strpos($this->label, 'MOD.') !== 0 && strpos($this->label, 'APP.') !== 0) {
			$label =  "{MOD." .$this->label . "}";	
		} else {
			$label =  "{" .$this->label . "}";	
		}
		if($this->view == 'edit' && $this->is_required()){
			$label .= '<span class="required">*</span>';
		}
		if($this->view == 'list'){
			if(isset($this->bean)){
				if(!empty($this->id)){
					$name = $this->bean->table_name . '_cstm.'. $this->name;
					$arrow = $this->bean->table_name . '_cstm_'. $this->name;
				}else{
					$name = $this->bean->table_name . '.'. $this->name;
					$arrow = $this->bean->table_name . '_'. $this->name;	
				}
			}else{
				$name = $this->name;	
				$arrow = $name;
			}
			$label = "<a href='{ORDER_BY}$name' class='listViewThLinkS1'>{$label}{arrow_start}{".$arrow."_arrow}{arrow_end}</a>";
		}
		return $label;
			
	}
	
	/*
		XTPL FUNCTIONS
	*/
	
	function get_xtpl($bean = false){
		if($bean)
		$this->bean = $bean;
		$view = $this->view;
		if(!empty($GLOBALS['studioReadOnlyFields'][$this->name]))$view = 'detail';
		switch($view){
			case 'search':return $this->get_xtpl_search();
			case 'edit': return $this->get_xtpl_edit();
			case 'list': return $this->get_xtpl_list();
			case 'detail': return $this->get_xtpl_detail();
				
		}		
	}
	
	function get_xtpl_edit(){
		return '/*not implemented*/';	
	}
	
	function get_xtpl_list(){
		return get_xtpl_detail();	
	}
	
	function get_xtpl_detail(){
		return '/*not implemented*/';	
	}
	
	function get_xtpl_search(){
		return get_xtpl_edit();	
	}
	
	function is_required(){
		if($this->required_option == 'required'){
			return true;
		}
		return false;
			
	}


	function getFieldMeta()
	{
		return array(
			'type' => 'varchar',
			'len' => $this->max_size,
			'required' => $this->is_required(),
			'name' => $this->name,
			'vname' => $this->label,
			'type' => $this->data_type,
			'massupdate' => $this->mass_update,
			'default' => $this->default_value,
		);
	}	
	
	
	/*
		DB FUNCTIONS
	*/
	
	function get_db_type(){
		switch($GLOBALS['db']->dbType){
			case 'oci8': return " varchar2($this->max_size)";
			default: return " varchar($this->max_size)";	
		}
	}
	
	function get_db_default($modify=false){
		if (!$modify or empty($this->new_field_definition['default_value']) or $this->new_field_definition['default_value'] != $this->default_value ) {
			if(!empty($this->default_value)){
				return " DEFAULT '$this->default_value'";	
			}else{
				return '';	
			}
		}
	}
	
	/*
	 * if modfying required require clause only if value was changed.
	 */
	function get_db_required($modify=false){
		if (!$modify or empty($this->new_field_definition['required_option']) or $this->new_field_definition['required_option'] != $this->required_option ) {
			if(!empty($this->required_option) && $this->required_option == 'required'){
				return " NOT NULL";	
			}else{
				return '';	
			}
		}
	}
	
	/**
	 * Oracle Support: do not set required constraint if no default value is supplied.
	 * In this case the default value will be han
	 */
	 //(!empty($this->get_db_default())) && (!empty($this->get_db_required()))
	function get_db_add_alter_table($table){
		$db_default=$this->get_db_default();

		$query="ALTER TABLE $table ADD $this->name " . $this->get_db_type();
	
		if ($query) {
			$query .= $db_default . $this->get_db_required();
		} else { 
			if (!empty($db_default)) {
				$query .= $db_default;
			}
		}
		
		return $query;
	}
	/**
	 * Oracle Support: removed data-type clause from the alter statement, the application does not
	 * allow change of datatype.
	 * mysql requires the datatype caluse in the alter statment.it will be no-op anyway.
	 */	
	function get_db_modify_alter_table($table){
		$db_default=$this->get_db_default(true);
		$db_required=$this->get_db_required(true);

        switch ($GLOBALS['db']->dbType) {
        	
            case "mssql":
                $query="ALTER TABLE $table ALTER COLUMN $this->name "  .$this->get_db_type();
                break;

            case "mysql":
                $query="ALTER TABLE $table MODIFY $this->name " .$this->get_db_type();
                break;
            default:
                $query="ALTER TABLE $table MODIFY $this->name " ;
                break;

        }
		if (!empty($db_default) && !empty($db_required)) {
			$query .= $db_default . $db_required ;
		} else if (!empty($db_default)) {
			$query .= $db_default;
		}
		return $query;
	}
	

	/*
	 * BEAN FUNCTIONS
	 * 
	 */
	function get_field_def(){
		
		return array('required'=>$this->is_required(), 'source'=>'custom_fields', "name"=>$this->name, "vname"=>$this->label,"type"=>$this->data_type,'massupdate'=>$this->mass_update,"len"=>$this->max_size,'rname'=>$this->name, 'table'=>$this->bean->table_name . '_cstm',  'custom_type'=>$this->data_type, 'type'=>'relate');	
	}
	
    /* returns definition of additional attributes supported by custom field.
     * this function is called by all implementing classes.
     */
    function get_additional_defs() {
        $add_att=array();
        $add_att=$this->get_dup_merge_def();
		if(!empty($this->default_value)) {
			$add_att['default_value'] = $this->default_value;
		}
		return $add_att;        
    }
    
    /* if the field is duplicate merge enabled this function will return the vardef entry for the same.
     */    
    function get_dup_merge_def() {
        $ret_value=array();
        switch ($this->duplicate_merge) {
            case 0:
                $ret_value['duplicate_merge']='disabled';
                break;
            case 1:   
                $ret_value['duplicate_merge']='enabled';
                break; 
            case 2:
                $ret_value['merge_filter']='enabled';            
                break;
            case 3:
                $ret_value['merge_filter']='selected';            
                break;
            case 4:
                $ret_value['merge_filter']='enabled';            
                $ret_value['duplicate_merge']='disabled';            
                break;
        }
        return $ret_value;
    }	
    
	/*
		HELPER FUNCTIONS
	*/
	
	
	function prepare(){
		if(empty($this->id)){
			$this->id = $this->name;	
		}	
	}
	
}


?>
