<?php
/*
 *
 * The contents of this file are subject to the info@hand Software License Agreement Version 1.3
 *
 * ("License"); You may not use this file except in compliance with the License.
 * You may obtain a copy of the License at <http://1crm.com/pdf/swlicense.pdf>.
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the
 * specific language governing rights and limitations under the License,
 *
 * All copies of the Covered Code must include on each user interface screen:
 * (i) the 1CRM copyright notice,
 * (ii) the "Powered by the 1CRM Engine" logo, 
 *
 * (iii) the "Powered by SugarCRM" logo, and
 * (iv) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.
 * See full license for requirements.
 *
 * The Original Code is : 1CRM Engine proprietary commercial code.
 * The Initial Developer of this Original Code is 1CRM Corp.
 * and it is Copyright (C) 2004-2012 by 1CRM Corp.
 *
 * All Rights Reserved.
 * Portions created by SugarCRM are Copyright (C) 2004-2008 SugarCRM, Inc.;
 * All Rights Reserved.
 *
 */
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');


require_once('modules/SecurityGroups/SecurityGroup.php');

class AssignGroups {

function cleanup() {}

function popup_select(&$bean, $event, $arguments)
{
	$config = SecurityGroup::getConfig();

	//only process if action is Save (meaning a user has triggered this event and not the portal or automated process)
	if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'Save' 
		&& $config['popup_select'] == true
		&& empty($bean->fetched_row['id']) && $bean->module_dir != "Users") {
		
		$groupFocus = new SecurityGroup();
		$security_modules = $groupFocus->getSecurityModules();
		if(in_array($bean->module_dir,$security_modules)) {
	
			//check if user is in more than 1 group. If so then set the session var otherwise inherit it's only group
			global $current_user;

			$memberships = $groupFocus->getMembershipCount($current_user->id);
			if($memberships > 1) {
				$_REQUEST['return_module'] = $bean->module_dir;
				$_REQUEST['return_action'] = "DetailView";
				$_REQUEST['return_id'] = $bean->id;

				$_SESSION['securitygroups_popup_'.$bean->module_dir] = $bean->id;
			} else if($memberships == 1) {
				$groupFocus->inheritOne($current_user->id, $bean->id, $bean->module_dir);
			}

		}

	}
	
	if($config['user_popup'] == true
		&& empty($bean->fetched_row['id']) && $bean->module_dir == "Users") {

		$_REQUEST['return_module'] = $bean->module_dir;
		$_REQUEST['return_action'] = "DetailView";
		$_REQUEST['return_id'] = $bean->id;
		
		$_SESSION['securitygroups_popup_'.$bean->module_dir] = $bean->id;
	}
} 


function popup_onload($event, $arguments)
{
	$config = SecurityGroup::getConfig();

	$module = array_get_default($_REQUEST, 'module');
	$action = array_get_default($_REQUEST, 'action');
	
	if(isset($action) && $action == "Save") return; 


	if( (($config['popup_select'] == true)
			|| ($module == "Users" && $config['user_popup'] == true)
		)
	
		&& isset($_SESSION['securitygroups_popup_'.$module]) && !empty($_SESSION['securitygroups_popup_'.$module])
	) {
		$record_id = $_SESSION['securitygroups_popup_'.$module];
 		unset($_SESSION['securitygroups_popup_'.$module]);
		//$record_id = $_REQUEST['record'];
	$auto_popup = <<<EOQ
<script type="text/javascript" language="javascript">
	open_popup("SecurityGroups",600,400,"",true,true,{"call_back_function":"set_return_and_save_background","form_name":"DetailView","field_to_name_array":{"id":"subpanel_id"},"passthru_data":{"child_field":"SecurityGroups","return_url":"index.php%3Fmodule%3D$module%26action%3DSubPanelViewer%26subpanel%3DSecurityGroups%26record%3D$record_id%26sugar_body_only%3D1","link_field_name":"SecurityGroups","module_name":"SecurityGroups","refresh_page":"1"}},"MultiSelect",true);
</script>
EOQ;

		echo $auto_popup;
	}

}

function mass_assign()
{
	$module = array_get_default($_REQUEST, 'module');
	$action = array_get_default($_REQUEST, 'action');
    
    //check if security suite enabled
    if(isset($module) && ($action == "list" || $action == "index" || $action == "listview") && array_get_default($_REQUEST, 'search_form_only') !== 'true') {
   		global $current_user;
 
   		if(ACLAction::getUserAccessLevel($current_user->id,"SecurityGroups", 'access') == ACL_ALLOW_ENABLED) {

			require_once('modules/SecurityGroups/SecurityGroup.php');
			$groupFocus = new SecurityGroup();
			$security_modules = $groupFocus->getSecurityModules();
			if(in_array($module,$security_modules)) {

				global $app_strings;

				global $current_language;
				$current_module_strings = return_module_language($current_language, 'SecurityGroups');

				$form_header = get_form_header($current_module_strings['LBL_MASS_ASSIGN'], '', false);

				$groups = $groupFocus->get_list("name");
				$options = array(""=>"");
				foreach($groups['list'] as $group) {
					$options[$group->id] = $group->name;
				}
				$group_options =  get_select_options_with_id($options, "");
				
				$GLOBALS['pageInstance']->add_js_include('modules/SecurityGroups/assigngroups.js');

				$mass_assign = <<<EOQ

		<form action='index.php' method='post' name='MassAssign_SecurityGroups' id='MassAssign_SecurityGroups' autocomplete="off">
			<input type='hidden' name='action' value='MassAssign' />
			<input type='hidden' name='module' value='SecurityGroups' />
			<input type='hidden' name='return_action' value='${action}' />
			<input type='hidden' name='return_module' value='${module}' />
			<textarea style='display: none' name='uid'></textarea>


		<div id='massassign_form'>$form_header
		<div style='padding-left: 2px; padding-bottom: 2px;' class='listViewButtons opaque form-mid'>
		<input type='submit' name='Assign' value='${current_module_strings['LBL_ASSIGN']}' onclick="return send_massassign('selected', '{$app_strings['LBL_LISTVIEW_NO_SELECTED']}','${current_module_strings['LBL_ASSIGN_CONFIRM']}','${current_module_strings['LBL_CONFIRM_END']}',0);" class='button'>
		<input type='submit' name='Remove' value='${current_module_strings['LBL_REMOVE']}' onclick="return send_massassign('selected', '{$app_strings['LBL_LISTVIEW_NO_SELECTED']}','${current_module_strings['LBL_REMOVE_CONFIRM']}','${current_module_strings['LBL_CONFIRM_END']}',1);" class='button'>
		</div>
		<table cellpadding='0' cellspacing='0' border='0' width='100%' class='tabForm' id='mass_update_table'>
		<tr>
		<td class="dataLabel" width="15%">${current_module_strings['LBL_GROUP']}</td>
		<td class="dataField"><select name='massassign_group' id="massassign_group" tabindex='1' style="width: 16em">${group_options}</select></td>
		</tr>
		</table></div>			
		</form>		
EOQ;


				return $mass_assign;
			}
		}
    }

	return '';
}

}
?>
