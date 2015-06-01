<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once('XTemplate/xtpl.php');

global $app_strings;
global $app_list_strings;
global $mod_strings;

$focus = new User();

if(!is_admin($current_user))
	sugar_die("Unauthorized access to administration.");

if(!empty($_REQUEST['record']))
    $focus->retrieve($_REQUEST['record']);
if(empty($focus->id))
	sugar_die("User not found");

echo get_module_title($mod_strings['LBL_MODULE_NAME'], $mod_strings['LBL_MODULE_NAME'].": ".$focus->name." (".$focus->user_name.")", true);

global $theme;
$theme_path='themes/'.$theme.'/';
$image_path=$theme_path.'images/';
require_once($theme_path.'layout_utils.php');

$xtpl=new XTemplate ('modules/Users/EditView.html');
$xtpl->assign('MOD', $mod_strings);
$xtpl->assign('APP', $app_strings);
$xtpl->assign("ID", $focus->id);

if (isset($_REQUEST['error_string'])) $xtpl->assign('ERROR_STRING', '<span class="error">Error: '.$_REQUEST['error_string'].'</span>');
if (isset($_REQUEST['return_module'])) $xtpl->assign('RETURN_MODULE', $_REQUEST['return_module']);
if (isset($_REQUEST['return_action'])) $xtpl->assign('RETURN_ACTION', $_REQUEST['return_action']);
if (isset($_REQUEST['return_id'])) $xtpl->assign('RETURN_ID', $_REQUEST['return_id']);
else { $xtpl->assign('RETURN_ACTION', 'ListView'); }


if(is_admin($focus))
	$xtpl->assign('IS_ADMIN', 'checked="checked"');
if(!empty($focus->portal_only))
	$xtpl->assign('IS_PORTALONLY', 'checked="checked"');

$quote_prefs = array(
	'noncatalog_products',
	'nonstandard_prices',
	'manual_discounts',
	'standard_discounts',
	'product_costs',
	'financial_information',
);
foreach($quote_prefs as $p) {
	$pref = $focus->getPreference($p);
	$xtpl->assign(strtoupper($p), empty($pref) ? '' : 'checked="checked"');
}

$xtpl->parse('permission_settings.quotation_mode');
$xtpl->parse('permission_settings.project_mode');
$xtpl->parse('permission_settings');
$xtpl->out('permission_settings');

?>