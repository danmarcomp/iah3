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

class TemplateMultiEnum extends TemplateEnum
{
	function get_html_edit(){
		$this->prepare();
		$xtpl_var = strtoupper( $this->name);
		return "<input type='hidden' name='". $this->name . "'>" .
			"<select name='". $this->name . "[]' size='5' title='{" . $xtpl_var ."_HELP}' MULTIPLE=true>{OPTIONS_".$xtpl_var. "}</select>";
	}
	
	function get_db_type(){
		if ($GLOBALS['db']->dbType=='oci8') {
			return " CLOB ";
		} else {	
			return " TEXT ";
		}
	}
	
	function get_xtpl_edit(){
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
		    $returnXTPL[strtoupper($this->name . '_help')] = translate($this->help, $this->bean->module_dir);
		}
		
		global $app_list_strings;
		$returnXTPL = array();

		$returnXTPL[strtoupper($this->name)] = str_replace('^,^', ',', $value);
		$returnXTPL[strtoupper('options_'.$this->name)] = get_select_options_with_id($app_list_strings[$this->ext1], explode('^,^', $value));
		
		return $returnXTPL;	
		
		
	}
	function prepSave(){
	    
	}
	function get_xtpl_list(){
		return $this->get_xtpl_detail();
		
	}
	function get_xtpl_detail(){
	   
		$name = $this->name;
		$value = '';
		if(isset($this->bean->$name)){
			$value = $this->bean->$name;
		}else{
			if(empty($this->bean->id)){
				$value= $this->default_value;	
			}	
		}
		$returnXTPL = array();
		if(empty($value)) return $returnXTPL;
		global $app_list_strings;
		
        $values = explode('^,^', $value);
        $translatedValues = array();
        
        foreach($values as $val){
            $translated = translate($this->ext1, '', $val);
            if(is_string($translated))$translatedValues[] = $translated;
        }
        
		$returnXTPL[strtoupper($this->name)] = implode(', ', $translatedValues);
		return $returnXTPL;
		
	
	
	
}
function get_field_def(){
		return array_merge(array('isMultiSelect'=>true), parent::get_field_def());	
	}
function get_db_default(){
    return '';
}

}


?>
