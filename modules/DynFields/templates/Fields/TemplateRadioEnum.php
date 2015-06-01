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
require_once('modules/DynFields/templates/Fields/TemplateEnum.php');
require_once('include/utils/array_utils.php');
class TemplateRadioEnum extends TemplateEnum{
	function get_html_edit(){
		$this->prepare();
		$xtpl_var = strtoupper( $this->name);
		return "{RADIOOPTIONS_".$xtpl_var. "}";
	}
	
	
	function get_xtpl_edit($add_blank = false){
		$name = $this->name;
		$value = '';
		if(isset($this->bean->$name)){
			$value = $this->bean->$name;
		}else{
			if(empty($this->bean->id)){
				$value= $this->default_value;	
			}	
		}
		if(!empty($this->help)){
		    $returnXTPL[$this->name . '_help'] = translate($this->help, $this->bean->module_dir);
		}
		
		global $app_list_strings;
		$returnXTPL = array();
		$returnXTPL[strtoupper($this->name)] = $value;

		
		$returnXTPL[strtoupper('RADIOOPTIONS_'.$this->name)] = $this->generateRadioButtons($value, false);
		return $returnXTPL;	
		
		
	}
	

	function generateRadioButtons($value = '', $add_blank =false){
		global $app_list_strings;
		$radiooptions = '';
		$keyvalues = $app_list_strings[$this->ext1];
		if($add_blank){
			$keyvalues = add_blank_option($keyvalues);
		}
		$help = (!empty($this->help))?"title='". translate($this->help, $this->bean->module_dir) . "'": '';
		foreach($keyvalues as $key=>$displayText){
			$selected = ($value == $key)?'checked': '';
			$radiooptions .= "<input type='radio' id='{$this->name}{$key}' name='$this->name'  $help value='$key' $selected><span onclick='document.getElementById(\"{$this->name}{$key}\").checked = true' style='cursor:default' onmousedown='return false;'>$displayText</span><br>\n";
		}
		return $radiooptions;
		
	}
	
	function get_xtpl_search(){
		$searchFor = '';
		if(!empty($_REQUEST[$this->name])){
			$searchFor = $_REQUEST[$this->name];
		}
		global $app_list_strings;
		$returnXTPL = array();
		$returnXTPL[strtoupper($this->name)] = $searchFor;
		$returnXTPL[strtoupper('RADIOOPTIONS_'.$this->name)] = $this->generateRadioButtons($searchFor, true);
		return $returnXTPL;	

	}
	

	function get_field_def(){
		return array_merge(array('required'=>$this->is_required(),'source'=>'custom_fields',"name"=>$this->name,'options'=>$this->ext1, "vname"=>$this->label,"len"=>$this->max_size,'rname'=>$this->name, 'table'=>$this->bean->table_name . '_cstm', 'massupdate'=>$this->mass_update, 'custom_type'=>'enum', 'type'=>'relate'),$this->get_additional_defs());	
	}
	
	
	function get_xtpl_detail(){
		$name = $this->name;
		if(isset($this->bean->$name)){
			global $app_list_strings;
			if(isset($app_list_strings[$this->ext1])){
				if(isset($app_list_strings[$this->ext1][$this->bean->$name])){
					return $app_list_strings[$this->ext1][$this->bean->$name];
				}
			}
		}else{
		    if(empty($this->bean->id)){
		        return $this->default_value;
		    }
		}
		return '';
	}
	
	function get_db_default(){
    return '';
}
	
}


?>
