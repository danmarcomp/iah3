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
require_once('modules/ACLRoles/ACLRole.php');
require_once('modules/ACLActions/ACLAction.php');
require_once('modules/ACL/ACLJSController.php');
class ACLController {

	static function checkAccess($category, $action, $is_owner=true,$in_group=false){	
		$user_id = AppConfig::current_user_id();
        if ($is_owner === true) $is_owner = $user_id;
        
		$action = strtolower($action);
		if($action == 'create') {
			if($category == 'Users') $action = 'admin';
			else $action = 'edit';
		}
		else if($action == 'detailview') $action = 'view';
		else if($action == 'editview') $action = 'edit';
		else if($action == 'listview') $action = 'list';

		if(AppConfig::is_admin()) {
			$lvl = ACLAction::getUserAccessLevel($user_id, $category, $action, 'module', 'aclaccess');
			if(isset($lvl)) $lvl = true;
			return $lvl;
		}
        
		return ACLAction::userHasAccess($user_id, $category, $action, 'module', $is_owner, $in_group);
	}
	
	static function requireOwner($category, $value, $uid=null, $allow_admin=true) {
		if($allow_admin && AppConfig::is_admin($uid)) return false;
		return ACLAction::userNeedsOwnership($uid, $category, $value, 'module');
	}
	
	static function requireSecurityGroup($category, $value, $uid=null, $allow_admin=true) {
		if($allow_admin && AppConfig::is_admin($uid)) return false;
		return ACLAction::userNeedsSecurityGroup($uid, $category, $value, 'module');
	}

	static function requireSecurityAdmin($category, $value, $uid=null) {
		return ACLAction::userNeedsSecurityAdmin($uid, $category, $value, 'module');
	}
	
	static function filterModuleList(&$moduleList, $by_value=true){
		
		global $aclModuleList, $current_user;
		// longreach - added
        if (is_admin($current_user)) return;
        if(isset($moduleList['Administration']))
        	unset($moduleList['Administration']);
		$actions = ACLAction::getUserActions($current_user->id, false);
		
		$compList = array();
		if($by_value){
			foreach($moduleList as $key=>$value){
				$compList[$value]= $key;
			}
		}else{
			$compList =& $moduleList;
		}
		foreach($actions as $action_name=>$action){
			if(!empty($action['module'])){
				$aclModuleList[$action_name] = $action_name;
				if(isset($compList[$action_name])){
					if($action['module']['access']['aclaccess'] < ACL_ALLOW_LIMITED || $action['module']['access']['aclaccess'] == ACL_ALLOW_ADMIN){
						if($by_value){
							unset($moduleList[$compList[$action_name]]);
						}else{
							unset($moduleList[$action_name]);
						}
					}
				}
			}
		}
	}
	
	/**
	 * Check to see if the module is available for this user.
	 *
	 * @param String $module_name
	 * @return true if they are allowed.  false otherwise.
	 */
	static function checkModuleAllowed($module_name, $actions)
	{
		global $current_user;
		
		if(empty($actions[$module_name]['actions']['access']['aclaccess']))
			return false;
		$lvl = $actions[$module_name]['actions']['access']['aclaccess'];
		if($lvl < 0 || ($lvl == ACL_ALLOW_ADMIN && ! is_admin($current_user)))
			return false;
		if($lvl == ACL_ALLOW_ENABLED || $lvl == ACL_ALLOW_LIMITED)
			return true;
		
		return false;
	}
	
	static function checkUserModuleAllowed($module_name, $user=null) {
		if(! $user) $user = $GLOBALS['current_user'];
		if(! $user || ! $user->id)
			return false;
		$actions = ACLAction::getUserActions($user->id, false);
		return ACLController::checkModuleAllowed($module_name, $actions);
	}
	
	static function addJavascript($category,$form_name='', $is_owner=false, $in_group=false){
		$jscontroller = new ACLJSController($category, $form_name, $is_owner, $in_group);
		echo $jscontroller->getJavascript();
	}
	
	static function moduleSupportsACL($module){
		return true;
	}
	
	static function displayNoAccess($redirect_home = false){
		throw IAHPermissionError::withParams('LBL_MODULE_NO_ACCESS', array('redirect_home' => $redirect_home));
	}
	
}


	
	
	




?>
