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
require_once('modules/DynFields/templates/Fields/TemplateField.php');
class TemplateText extends TemplateField{
	function get_html_edit(){
		$this->prepare();
		return "<input type='text' name='". $this->name. "' id='".$this->name."' size='".$this->size."' maxlength='".$this->max_size."' value='{". strtoupper($this->name). "}' title='{" . strtoupper($this->name) ."_HELP}'>";
	}

	function get_html_detail(){
		return '{'. strtoupper($this->name).'}';	
	}
	
	function get_html_list(){
		if(isset($this->bean)){
			$name = $this->bean->object_name . '.'. $this->name;
		}else{
			$name = $this->name;	
		}
		return '{'. strtoupper($name) . '}';	
	}
	
	function get_xtpl_edit(){
		$name = $this->name;
		$returnXTPL = array();
	
		if(!empty($this->help)){
		    $returnXTPL[strtoupper($this->name . '_help')] = translate($this->help, $this->bean->module_dir);
		}
	
		if(isset($this->bean->$name)){
		    $returnXTPL[$this->name] = $this->bean->$name;
		}else{
			if(empty($this->bean->id)){
				 $returnXTPL[$this->name] =  $this->default_value;	
			}	
		}
		return $returnXTPL;
	}
	function get_xtpl_search(){
		if(!empty($_REQUEST[$this->name])){
			return $_REQUEST[$this->name];
		}	
	}
	
	
	
	function get_xtpl_detail(){
		$name = $this->name;
		if(isset($this->bean->$name)){
			return $this->bean->$name;	
		}
		return '';
		
	}
    
    function get_field_def() {
        return array_merge(parent::get_field_def(), $this->get_additional_defs());
    }
	
}


?>
