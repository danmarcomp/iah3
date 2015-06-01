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
require_once('modules/DynFields/templates/Fields/TemplateText.php');
class TemplateInt extends TemplateText{
	function get_html_edit(){
		$this->prepare();
		return "<input type='text' name='". $this->name. "' id='".$this->name."' title='{" . strtoupper($this->name) ."_HELP}' size='".$this->size."' maxlength='".$this->max_size."' value='{". strtoupper($this->name). "}'>";
	}
	
function get_field_def(){
		$vardef =  array('required'=>$this->is_required(),'source'=>'custom_fields', "name"=>$this->name, "vname"=>$this->label,"type"=>'int',"len"=>$this->max_size,'rname'=>$this->name,'massupdate'=>$this->mass_update, 'table'=>$this->bean->table_name . '_cstm',  'custom_type'=>'int', 'type'=>'relate');	 
		if(!empty($this->ext2)){
		
		    $min = (!empty($this->ext1))?$this->ext1:0;
		    $max = $this->ext2;
		    $vardef['validation'] = array('type' => 'range', 'min' => $min, 'max' => $max);
		    
		}
        $vardef=array_merge($this->get_additional_defs(),$vardef);
		return $vardef;
}

function get_db_type(){
	switch($GLOBALS['db']->dbType){
		case 'oci8': return ' NUMBER ';
		case 'mysql': return  (!empty($this->max_size) && $this->max_size <= 11 && $this->max_size > 0)? ' INT(' .$this->max_size . ')' : ' INT(11) ';	
		default: return ' INT ';
	}
}	
	
}


?>
