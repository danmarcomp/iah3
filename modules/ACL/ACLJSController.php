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


class ACLJSController{
	
	/* eggsurplus - SECURITYGROUPS - add $in_group */
	function ACLJSController($module,$form='', $is_owner=false, $in_group=false){
		
		$this->module = $module;
		$this->is_owner = $is_owner;
		$this->form = $form;
		$this->in_group = $in_group;
	}
	/* end eggsurplus - SECURITYGROUPS */
	
	function getJavascript(){
		global $action;
		if(!ACLController::moduleSupportsACL($this->module)){
			return '';
		}
		$script = "<SCRIPT>\n//BEGIN ACL JAVASCRIPT\n";

		if($action == 'DetailView'){
			/* eggsurplus - SECURITYGROUPS - add $in_group */
			if(!ACLController::checkAccess($this->module,'edit', $this->is_owner, $this->in_group)){
			$script .= <<<EOQ
						if(typeof(document.DetailView) != 'undefined'){
							if(typeof(document.DetailView.elements['Edit']) != 'undefined'){
								document.DetailView.elements['Edit'].disabled = 'disabled';
							}
							if(typeof(document.DetailView.elements['Duplicate']) != 'undefined'){
								document.DetailView.elements['Duplicate'].disabled = 'disabled';
							}
						} 		
EOQ;
}
			if(!ACLController::checkAccess($this->module,'delete', $this->is_owner, $this->in_group)){
			$script .= <<<EOQ
						if(typeof(document.DetailView) != 'undefined'){
							if(typeof(document.DetailView.elements['Delete']) != 'undefined'){
								document.DetailView.elements['Delete'].disabled = 'disabled';
							}
						} 		
EOQ;
}
		}
		/* end eggsurplus - SECURITYGROUPS */
		if(file_exists('modules/'. $this->module . '/metadata/acldefs.php')){
			include('modules/'. $this->module . '/metadata/acldefs.php');
			
			foreach($acldefs[$this->module]['forms'] as $form_name=>$form){
			
				foreach($form as $field_name=>$field){
					
					if($field['app_action'] == $action){
						switch($form_name){
							case 'by_id':
								$script .= $this->getFieldByIdScript($field_name, $field);
								break;
							case 'by_name':
								$script .= $this->getFieldByNameScript($field_name, $field);
								break;
							default:
								$script .= $this->getFieldByFormScript($form_name, $field_name, $field);
								break;
						}
					}
					
				}
			}
		}
		$script .=  '</SCRIPT>';
		
		return $script;
		
		
	}
	
	function getHTMLValues($def){
		$return_array = array();
		switch($def['display_option']){
			case 'clear_link':
				$return_array['href']= "#";
				$return_array['className']= "nolink";
				break;
			default;
				$return_array[$def['display_option']] = $def['display_option'];
				break;
			
		}
		return $return_array;
		
	}
	
	function getFieldByIdScript($name, $def){
		$script = '';
		if(!ACLController::checkAccess($def['module'], $def['action_option'], true)){
		foreach($this->getHTMLValues($def) as $key=>$value){
			$script .=  "\nif(document.getElementById('$name'))document.getElementById('$name')." . $key . '="' .$value. '";'. "\n";
		}
		}
		return $script;
	
	}
	
	function getFieldByNameScript($name, $def){
		$script = '';
		if(!ACLController::checkAccess($def['module'], $def['action_option'], true)){
			
		foreach($this->getHTMLValues($def) as $key=>$value){
			$script .=  <<<EOQ
			var aclfields = document.getElementsByName('$name');
			for(var i in aclfields){
				aclfields[i].$key = '$value';
			}
EOQ;
		}
		}
		return $script;
	
	}
	
	function getFieldByFormScript($form, $name, $def){
		$script = '';


		if(!ACLController::checkAccess($def['module'], $def['action_option'], true)){
			foreach($this->getHTMLValues($def) as $key=>$value){
				$script .= "\nif(typeof(document.$form.$name.$key) != 'undefined')\n document.$form.$name.".$key . '="' .$value. '";';
			}
		}
		return $script;
	
	}
	
	
	
	
	
	
	
	
}



?>
