<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/**
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
 */



class TabGroupHelper{
    var $modules = array();
    function getAvailableModules(){
       static $availableModules = array();
       if(!empty($availableModules))return $availableModules;
       foreach($GLOBALS['moduleList'] as $value){
           $availableModules[$value] = array('label'=>$GLOBALS['app_list_strings']['moduleList'][$value], 'value'=>$value);
       }
       foreach($GLOBALS['modSemiInvisList'] as $value){
           $availableModules[$value] = array('label'=>$GLOBALS['app_list_strings']['moduleList'][$value], 'value'=>$value);
       }
       return $availableModules;
    }
    
    /**
     * Takes in the request params from a save request and processes 
     * them for the save.
     *
     * @param REQUEST params  $params
     */
    function saveTabGroups($params){
    	$tabGroups = array();
		 $selected_lang = (!empty($params['dropdown_lang'])?$params['dropdown_lang']:$_SESSION['authenticated_user_language']);    	

		$update_app_strings = array();
        for($count = 0; isset($params['slot_' . $count]); $count++){
        	
        	if($params['delete_' . $count] == 1){
        		continue;	
        	}
        	
        	
        	$index = $params['slot_' . $count];
        	$labelID = (!empty($params['tablabelid_' . $index]))?$params['tablabelid_' . $index]: 'LBL_GROUPTAB' . $count . '_'. time();
        	$labelValue = from_html($params['tablabel_' . $index]);
        	if(empty($GLOBALS['app_strings'][$labelID]) || $GLOBALS['app_strings'][$labelID] != $labelValue){
        		$update_app_strings[$labelID] = $labelValue;
        	}
        	$tabGroups[$labelID] = array('label'=>$labelID);
        	$tabGroups[$labelID]['modules']= array();
        	for($subcount = 0; isset($params[$index.'_' . $subcount]); $subcount++){
        		$tabGroups[$labelID]['modules'][] = $params[$index.'_' . $subcount];
        	}
        	
    	}

		if($update_app_strings) {
			$contents = return_custom_app_list_strings_file_contents($selected_lang);
			foreach($update_app_strings as $labelID => $labelValue) {
				$contents = replace_or_add_app_string($labelID, $labelValue, $contents);
			}
			save_custom_app_list_strings_contents($contents, $selected_lang);
		}
    	
     	$newFile = create_custom_directory('include/tabConfig.php');
     	write_array_to_file("GLOBALS['tabStructure']", $tabGroups, $newFile);
   		$GLOBALS['tabStructure'] = $tabGroups; 
   		sugar_cache_reset();
   		$GLOBALS['app_strings'] = return_application_language(null);
   }
    
}


?>
