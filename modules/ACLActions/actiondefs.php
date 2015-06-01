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
if(!defined('ACL_ALLOW_NONE')){   
	define('ACL_ALLOW_ADMIN', 99);
	define('ACL_ALLOW_ALL', 90);                        
	define('ACL_ALLOW_ENABLED', 89);
	define('ACL_ALLOW_OWNER', 75);
	define('ACL_ALLOW_NORMAL', 1);
	define('ACL_ALLOW_LIMITED', 88);
	define('ACL_ALLOW_DISABLED', -98);
	define('ACL_ALLOW_NONE', -99);
	define('ACL_ALLOW_ROLE', 78);
	define('ACL_ALLOW_GROUP', 80); 
}
 
global $ACLActionAccessLevels, $ACLStyleClassNames, $ACLActionLevelString, $ACLActions;
 
 /**
  * $ACLActionAccessLevels
  * these are rendering descriptions for Access Levels giving information such as the label, color, and text color to use when rendering the access level
  */
$ACLActionAccessLevels = array(
 	ACL_ALLOW_ALL=>array('color'=>'#008000', 'label'=>'LBL_ACCESS_ALL', 'text_color'=>'white'),
 	ACL_ALLOW_OWNER=>array('color'=>'#6F6800', 'label'=>'LBL_ACCESS_OWNER', 'text_color'=>'white'),
 	ACL_ALLOW_NONE=>array('color'=>'#FF0000', 'label'=>'LBL_ACCESS_NONE', 'text_color'=>'white'),
 	ACL_ALLOW_ENABLED=>array('color'=>'#008000', 'label'=>'LBL_ACCESS_ENABLED', 'text_color'=>'white'),
 	ACL_ALLOW_LIMITED=>array('color'=>'#6F6800', 'label'=>'LBL_ACCESS_LIMITED', 'text_color'=>'white'),
 	ACL_ALLOW_DISABLED=>array('color'=>'#FF0000', 'label'=>'LBL_ACCESS_DISABLED', 'text_color'=>'white'),
 	ACL_ALLOW_ADMIN=>array('color'=>'#0000FF', 'label'=>'LBL_ACCESS_ADMIN', 'text_color'=>'white'),
 	ACL_ALLOW_NORMAL=>array('color'=>'#008000', 'label'=>'LBL_ACCESS_NORMAL', 'text_color'=>'white'),
 	ACL_ALLOW_ROLE=>array('color'=>'#0000FF', 'label'=>'LBL_ACCESS_ROLE', 'text_color'=>'white'),
 	ACL_ALLOW_GROUP=>array('color'=>'#0000A0', 'label'=>'LBL_ACCESS_GROUP', 'text_color'=>'white'),
);
 

$ACLStyleClassNames = array(
	'' => 'Blank',
	ACL_ALLOW_ALL => 'All',
	ACL_ALLOW_GROUP => 'Group',
	ACL_ALLOW_OWNER => 'Owner',
	ACL_ALLOW_NONE => 'None',
	ACL_ALLOW_ENABLED => 'Enabled',
	ACL_ALLOW_LIMITED => 'Owner',
	ACL_ALLOW_DISABLED => 'Disabled',
	ACL_ALLOW_ADMIN => 'Admin',
	ACL_ALLOW_NORMAL => 'Normal',
	ACL_ALLOW_ROLE => 'Role',
);


$ACLActionLevelString = array(
	'all' => ACL_ALLOW_ALL,
	'owner' => ACL_ALLOW_OWNER,
	'team' => ACL_ALLOW_GROUP,
	'admin' => ACL_ALLOW_ADMIN,
	'none' => ACL_ALLOW_NONE,
	'enabled' => ACL_ALLOW_ENABLED,
	'limited' => ACL_ALLOW_LIMITED,
	'disabled' => ACL_ALLOW_DISABLED,
);

 
/**
 * $ACLActions
 * These are the actions for a given type. It includes the ACCESS Levels for that action and the label for that action. Every an object of the category (e.g. module) is added all associated actions are added for that object
 */
 /** eggsurplus - SECURITYGROUPS - add ACL_ALLOW_GROUP */
$ACLActions = array(
	'module' => array(
		'actions' =>
			array(
				'access' => array(
					'aclaccess'=>array(ACL_ALLOW_ENABLED, ACL_ALLOW_LIMITED, ACL_ALLOW_DISABLED),
					'label'=>'LBL_ACTION_ACCESS',
					'default'=>ACL_ALLOW_ENABLED,
				),			
				'view' => array(
					// longreach - added ACL_ALLOW_ROLE
					'aclaccess'=>array(ACL_ALLOW_ALL,ACL_ALLOW_GROUP, ACL_ALLOW_ROLE, ACL_ALLOW_OWNER, ACL_ALLOW_ADMIN, ACL_ALLOW_NONE),
					'label'=>'LBL_ACTION_VIEW',
					'default'=>ACL_ALLOW_ALL,
				),	
				'list' => array(
					// longreach - added ACL_ALLOW_ROLE
					'aclaccess'=>array(ACL_ALLOW_ALL,ACL_ALLOW_GROUP,ACL_ALLOW_ROLE,ACL_ALLOW_OWNER, ACL_ALLOW_ADMIN, ACL_ALLOW_NONE),
					'label'=>'LBL_ACTION_LIST',
					'default'=>ACL_ALLOW_ALL,
				),
				'edit' => array(
					// longreach - added ACL_ALLOW_ROLE
					'aclaccess'=>array(ACL_ALLOW_ALL,ACL_ALLOW_GROUP,ACL_ALLOW_ROLE,ACL_ALLOW_OWNER, ACL_ALLOW_ADMIN, ACL_ALLOW_NONE),
					'label'=>'LBL_ACTION_EDIT',
					'default'=>ACL_ALLOW_ALL,				
				),
				'delete' => array(
					// longreach - added ACL_ALLOW_ROLE
					'aclaccess'=>array(ACL_ALLOW_ALL,ACL_ALLOW_GROUP,ACL_ALLOW_ROLE,ACL_ALLOW_OWNER, ACL_ALLOW_ADMIN, ACL_ALLOW_NONE),
					'label'=>'LBL_ACTION_DELETE',
					'default'=>ACL_ALLOW_ALL,				
				),
				'import' => array(
					'aclaccess'=>array(ACL_ALLOW_ALL, ACL_ALLOW_ADMIN, ACL_ALLOW_NONE),
					'label'=>'LBL_ACTION_IMPORT',
					'default'=>ACL_ALLOW_ALL,
				),
				'export' => array(
					'aclaccess'=>array(ACL_ALLOW_ALL,ACL_ALLOW_GROUP,ACL_ALLOW_ROLE,ACL_ALLOW_OWNER, ACL_ALLOW_ADMIN, ACL_ALLOW_NONE),
					'label'=>'LBL_ACTION_EXPORT',
					'default'=>ACL_ALLOW_ALL,
				),
				'report' => array(
					'aclaccess'=>array(ACL_ALLOW_ALL,ACL_ALLOW_GROUP,ACL_ALLOW_ROLE,ACL_ALLOW_OWNER, ACL_ALLOW_ADMIN, ACL_ALLOW_NONE),
					'label'=>'LBL_ACTION_REPORT',
					'default'=>ACL_ALLOW_ALL,
				),
				'approve' => array(
					'aclaccess'=>array(ACL_ALLOW_ENABLED, ACL_ALLOW_DISABLED),
					'label'=>'LBL_ACTION_APPROVE',
					'default'=>ACL_ALLOW_ENABLED,
				),
			)
		)
	);

?>
