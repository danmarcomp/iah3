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
class TemplateHTML extends TemplateField{
    var $data_type = 'html';


	
	
	function set($values){
	   parent::set($values);
	   if(!empty($this->ext4)){
	       $this->default_value = $this->ext4;
	   }
		
	}
	
	function get_html_detail(){
	  
	    return '<div title="' . strtoupper($this->name . '_HELP'). '" >{'.strtoupper($this->name) . '}</div>';
	}
	
	function get_html_edit(){
	    return $this->get_html_detail();
	}
	
	function get_html_list(){
	    return $this->get_html_detail();
	}
	
	function get_html_search(){
	    return $this->get_html_detail();
	}
	
	function get_xtpl_detail(){
	    
		return from_html(nl2br($this->ext4));	
	}
	
	function get_xtpl_edit(){
	   return  $this->get_xtpl_detail();
	}
	
	function get_xtpl_list(){
	    return  $this->get_xtpl_detail();
	}
	function get_xtpl_search(){
	    return  $this->get_xtpl_detail();
	}
	
    function get_db_add_alter_table($table){
			return "";
	}
	
	function get_db_modify_alter_table($table){
	       return "";
	}
	
    function get_field_def() {
        return array_merge(parent::get_field_def(), $this->get_additional_defs());
    }
	
	
}


?>
