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
class TemplateBoolean extends TemplateField{
	var $default_value = '0';
	function get_html_edit(){
		$this->prepare();
		return "<input type='hidden' name='". $this->name. "' value='0'><input type='checkbox' name='". $this->name. "' id='".$this->name."'  title='{" . strtoupper($this->name) ."_HELP}' value='1' {". strtoupper($this->name). "_CHECKED}>";
	}

	function get_html_detail(){
		return "<input type='checkbox' class='checkbox' name='". $this->name. "' id='".$this->name."'  value='1' disabled {". strtoupper($this->name). "_CHECKED}>";	
	}
	
	function get_html_list(){
		if(isset($this->bean)){
			$name = $this->bean->object_name . '.'. $this->name;
		}else{
			$name = $this->name;	
		}
		
			return "<input type='checkbox' class='checkbox'  name='". $name. "' id='". $name. "'   value='1' disabled {". strtoupper($name). "_CHECKED}>";		
	}
	
	function get_html_search(){
		$this->prepare();
		return "<select name='". $this->name. "' title='{" . strtoupper($this->name) ."_HELP}'>{OPTIONS_". strtoupper($this->name). "}</select>";
	}
	
	
	function get_xtpl_edit(){
		$name = $this->name;
		$returnXTPL = array();
		if(!empty($this->help)){
		    $returnXTPL[$this->name . '_help'] = translate($this->help, $this->bean->module_dir);
		}
		if(isset($this->bean->$name)){

			
            if(strcmp($this->bean->$name ,'1') ==0  || strcmp($this->bean->$name,'on')==0 || strcmp($this->bean->$name,'yes')==0 || strcmp($this->bean->$name, 'true')==0){
				$returnXTPL[$this->name . '_checked'] = 'checked';
				$returnXTPL[$this->name] = 'checked';
			}
		}else{
				
				if(empty($this->bean->id)){
					
					if(!empty($this->default_value)){
						
                        if(!(strcmp($this->default_value,'false')==0 || strcmp($this->default_value,'no')==0 || strcmp($this->default_value,'off')==0 )){
							$returnXTPL[$this->name . '_checked'] = 'checked';
							$returnXTPL[$this->name] = 'checked';	
						}
							
					}
					$returnXTPL[strtoupper($this->name)] =  $this->default_value;	
				}		
		}
			

		
		return $returnXTPL;
	}
	

	
	
	function get_xtpl_search(){
		$tab = array_get_default($_REQUEST, 'searchFormTab');
		if($tab == 'basic_search' && isset($_REQUEST[$this->name.'_basic']))
			$searchFor = $_REQUEST[$this->name.'_basic'];
		else if(!empty($_REQUEST[$this->name]))
			$searchFor = $_REQUEST[$this->name];
		else
			$searchFor = '';
		global $app_list_strings;
		$returnXTPL = array();
		$returnXTPL[strtoupper($this->name)] = $searchFor;
		$opts = $app_list_strings['dom_int_bool'];
		$opts = array('' => '', '1' => $opts[1], '0' => $opts[0]);
		$returnXTPL[strtoupper('options_'.$this->name)] = get_select_options_with_id($opts, $searchFor);
		return $returnXTPL;	

		/*if(!empty($_REQUEST[$this->name])){
			$returnXTPL = array();
			
			if($_REQUEST[$this->name] == '1' || $_REQUEST[$this->name] == 'on' || $_REQUEST[$this->name] == 'yes'){
				$returnXTPL[$this->name . '_checked'] = 'checked';
				$returnXTPL[$this->name] = 'checked';
			}
			return $returnXTPL;

		}
		return '';*/
	}
	

	function get_db_type(){
		if ($GLOBALS['db']->dbType=='oci8') {
			return " NUMBER(1) ";
		}elseif($GLOBALS['db']->dbType=='mssql'){
			return " bit ";
		}else{
			return " BOOL ";
		}	
	}	

	function get_field_def(){
		return array_merge(array('required'=>$this->is_required(),"name"=>$this->name, "vname"=>$this->label,"len"=>$this->max_size,'rname'=>$this->name, 'table'=>$this->bean->table_name . '_cstm',  'custom_type'=>'bool', 'type'=>'relate','massupdate'=>$this->mass_update, 'source'=>'custom_fields'), $this->get_additional_defs());	
	}
	
	
	function get_xtpl_detail(){
        $name = $this->name;
        $returnXTPL = array();
        if(!empty($this->help)){
            $returnXTPL[$this->name . '_help'] = translate($this->help, $this->bean->module_dir);
        }
        $returnXTPL[$this->name . '_checked'] = '';
        $returnXTPL[$this->name] = '';

        if(isset($this->bean->$name)){
            if(strcmp($this->bean->$name ,'1') ==0  || strcmp($this->bean->$name,'on')==0 || strcmp($this->bean->$name,'yes')==0 || strcmp($this->bean->$name, 'true')==0){
                $returnXTPL[$this->name . '_checked'] = 'checked';
                $returnXTPL[$this->name] = 'checked';
            }
        }
        return $returnXTPL;
	}

	function get_xtpl_list(){
		return $this->get_xtpl_edit();	
	}
	
	
	
}


?>
