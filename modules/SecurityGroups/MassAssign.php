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

require_once('include/formbase.php');
global $current_user, $db;

$module = $_REQUEST['return_module'];
$sugarbean = null;

$securitygroup = $_REQUEST['massassign_group'];
if(!isset($module) || empty($securitygroup) || !isset($securitygroup)) return;

if(!empty($GLOBALS['beanList'][$module])){
	$class = $GLOBALS['beanList'][$module];
	if(!empty($GLOBALS['beanFiles'][$class])){
		require_once($GLOBALS['beanFiles'][$class]);
		$sugarbean = new $class();
	}
}

$groupFocus = new SecurityGroup();
$groupFocus->retrieve($securitygroup);





if(!empty($_REQUEST['uid'])) $_POST['mass'] = explode(',', $_REQUEST['uid']); // coming from listview
elseif(isset($_REQUEST['entire'])) {
	if(isset($_SESSION['export_where']) && !empty($_SESSION['export_where'])) { // bug 4679
		$where = $_SESSION['export_where'];
		$whereArr = explode (" ", trim($where));
		if ($whereArr[0] == trim('where')) {
			$whereClean = array_shift($whereArr);
		}
		$where = implode(" ", $whereArr);
	} else {
		$where = '';
	}
	if(empty($order_by))$order_by = '';
	$query = $sugarbean->create_export_query($order_by,$where);
	$result = $db->query($query,true);

	$new_arr = array();
	while($val = $db->fetchByAssoc($result,-1,false))
	{
		array_push($new_arr, $val['id']);
	}
	$_POST['mass'] = $new_arr;
}

if(isset($_POST['mass']) && is_array($_POST['mass'])){
	foreach($_POST['mass'] as $id){
		if(isset($_POST['Delete'])){
			$sugarbean->retrieve($id);

			//if($sugarbean->ACLAccess('Delete')){

				$GLOBALS['log']->fatal("deleting relationship: $groupFocus->name");
				$rel_name = "SecurityGroups";
				$sugarbean->load_relationship($rel_name);
				$sugarbean->$rel_name->delete($sugarbean->id,$groupFocus->id);
			//}


		}
		else {
			$sugarbean->retrieve($id);

			//if($sugarbean->ACLAccess('Save')){

				$rel_name = "SecurityGroups";
				$sugarbean->load_relationship($rel_name);
				$sugarbean->$rel_name->add($groupFocus->id);

			//}
		}
	}
}


header("Location: index.php?action={$_POST['return_action']}&module={$_POST['return_module']}");

?>
