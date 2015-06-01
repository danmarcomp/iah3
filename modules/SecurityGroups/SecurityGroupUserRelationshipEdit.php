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

require_once('XTemplate/xtpl.php');
require_once('modules/SecurityGroups/SecurityGroupUserRelationship.php');
require_once('modules/SecurityGroups/Forms.php');
require_once('include/utils.php');

global $app_strings;
global $app_list_strings;
global $mod_strings;

$focus = new SecurityGroupUserRelationship();

if(isset($_REQUEST['record'])) {
    $focus->retrieve($_REQUEST['record']);
}

if(isset($_REQUEST['isDuplicate']) && $_REQUEST['isDuplicate'] == 'true') {
	$focus->id = "";
}

// Prepopulate either side of the relationship if passed in.
safe_map('user_name', $focus);
safe_map('user_id', $focus);
safe_map('securitygroup_name', $focus);
safe_map('securitygroup_id', $focus);
safe_map('noninheritable', $focus);


$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";
require_once($theme_path.'layout_utils.php');

$GLOBALS['log']->info("SecurityGroup User relationship");

$json = getJSONobj();
require_once('include/QuickSearchDefaults.php');
$qsd = new QuickSearchDefaults();
$sqs_objects = array('user_name' => $qsd->getQSParent());
$sqs_objects['user_name']['populate_list'] = array('user_name', 'user_id');
$quicksearch_js = $qsd->getQSScripts();
$quicksearch_js .= '<script type="text/javascript" language="javascript">sqs_objects = ' . $json->encode($sqs_objects) . '</script>';
echo $quicksearch_js;

$xtpl=new XTemplate ('modules/SecurityGroups/SecurityGroupUserRelationshipEdit.html');
$xtpl->assign("MOD", $mod_strings);
$xtpl->assign("APP", $app_strings);

$xtpl->assign("RETURN_URL", "&return_module=$currentModule&return_action=DetailView&return_id=$focus->id");
$xtpl->assign("RETURN_MODULE", $_REQUEST['return_module']);
$xtpl->assign("RETURN_ACTION", $_REQUEST['return_action']);
$xtpl->assign("RETURN_ID", $_REQUEST['return_id']);
$xtpl->assign("THEME", $theme);
$xtpl->assign("IMAGE_PATH", $image_path);$xtpl->assign("PRINT_URL", "index.php?".$GLOBALS['request_string']);
$xtpl->assign("ID", $focus->id);
$xtpl->assign("SECURITYGROUP",$securityGroup = Array("NAME" => $focus->securitygroup_name, "ID" => $focus->securitygroup_id));
$xtpl->assign("USER",$user = Array("NAME" => $focus->user_name, "ID" => $focus->user_id));

echo "\n<p>\n";
echo get_module_title($mod_strings['LBL_MODULE_NAME'], $mod_strings['LBL_SECURITYGROUP_USER_FORM_TITLE'].": ".$securityGroup['NAME'] . " - ". $user['NAME'], true);
echo "\n</p>\n";

// noninheritable
$noninheritable = '';
if(isset($focus->noninheritable) && $focus->noninheritable == true) {
	$noninheritable = 'CHECKED';
} 
$xtpl->assign('noninheritable', $noninheritable);


$xtpl->parse("main");

$xtpl->out("main");

require_once('include/javascript/javascript.php');
$javascript = new javascript();
$javascript->setFormName('EditView');
$javascript->setSugarBean($focus);
$javascript->addToValidateBinaryDependency('user_name', 'alpha', $app_strings['ERR_SQS_NO_MATCH_FIELD'] . $mod_strings['LBL_USER_NAME'], 'false', '', 'user_id');
echo $javascript->getScript();


?>
