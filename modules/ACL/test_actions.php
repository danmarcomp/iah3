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
require_once('modules/ACL/ACLController.php');

include('include/modules.php');
$default_actions = array();
$actions = array('view', 'edit', 'delete', 'list', 'import', 'export');
$default_deny = array('import'=>1,'export'=>1);
$default_owner = array('delete'=>1);
$db =& PearDatabase::getInstance();
$db->query("TRUNCATE acl_actions");
$db->query("TRUNCATE acl_roles");
$db->query("TRUNCATE acl_roles_actions");
$db->query("TRUNCATE acl_roles_users");
function acl_translate($access){
	switch($access){
		case ACL_ALLOW_ALL:
			return 'ALL';
		case ACL_ALLOW_OWNER:
			return 'OWNER';
		case ACL_ALLOW_NONE:
			return 'NONE';
		
	}
}
$testmodules = array('Accounts', 'Contacts', 'Users');
foreach($testmodules as $module){
	foreach($actions as $cur_action){
		$action = new ACLAction();
		$action->name = $cur_action;
		$action->category = $module;
		if(isset($default_owner[$cur_action])){
			$action->access = ACL_ALLOW_OWNER;
		}else if(isset($default_deny[$cur_action])){
			$action->access = ACL_ALLOW_NONE;
		}else{
				
			
			$action->access = ACL_ALLOW_ALL;
		}
		
		$action->save();
		
	}	

}

$action_results = ACLAction::getUserActions('will_id', true);
echo 'Actions Test no roles for will -access to all modules -owner delete and no import or export<br>';
foreach($action_results as $category_name=>$category){
	
	foreach($category as $action_name=>$action){
		_pp($category_name .':'. $action_name . ':' . acl_translate($action['access']));
		
	}
	
}

echo 'Create a role for Peon Users<br>';

$aclrole = new ACLRole();
$aclrole->name = 'Peon User';
$aclrole->description = 'The Peon Role For All Peons';
$aclrole->user_id = 'will_id';
$aclrole->save();

echo 'No Peon user should have access to accounts<br>';
foreach($action_results['Accounts'] as $action){
	$aclrole->setAction($aclrole->id, $action['id'], ACL_ALLOW_NONE);
}

echo 'Only owner Peon user should have editaccess to contacts<br>';

	$aclrole->setAction($aclrole->id, $action_results['Contacts']['edit']['id'], ACL_ALLOW_OWNER);
echo 'Some one made a mistake and added delete access on Contacts<br>';
$aclrole->setAction($aclrole->id, $action_results['Contacts']['delete']['id'], ACL_ALLOW_ALL);

$action_results = ACLAction::getUserActions('will_id', true);
echo 'Actions Peon role for will<br>';
foreach($action_results as $category_name=>$category){
	
	foreach($category as $action_name=>$action){
		_pp($category_name .':'. $action_name . ':' . acl_translate($action['access']));
		
	}
	
}
echo 'Will is a bad peon user<br>';
echo 'Create a role for Bad Peon Users<br>';
$aclrole = new ACLRole();
$aclrole->name = 'Bad Peon User';
$aclrole->description = 'The Bad Peon Role For All Bad Peons';
$aclrole->user_id = 'will_id';
$aclrole->save();

echo 'No Bad Peon user should have access to contacts <br>';
foreach($action_results['Contacts'] as $action){
	$aclrole->setAction($aclrole->id, $action['id'], ACL_ALLOW_NONE);
}
$action_results = ACLAction::getUserActions('will_id', true);
echo 'Actions Peon role for will<br>';
foreach($action_results as $category_name=>$category){
	
	foreach($category as $action_name=>$action){
		_pp($category_name .':'. $action_name . ':' . acl_translate($action['access']));
		
	}
	
}





echo 'PRINTING THE ACTIONS for a role <br>';
$role_actions = ACLRole::getRoleActions($aclrole->id);
_pp($role_actions);
echo 'PRINTING THE SESSION CACHE FOR ACL <br>';
_PP($_SESSION['ACL']);







?>
