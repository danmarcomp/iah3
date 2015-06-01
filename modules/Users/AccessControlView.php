<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once('include/DetailView/DetailView.php');
require_once('XTemplate/xtpl.php');

if(!is_admin($current_user) && $_REQUEST['record'] != $current_user->id) sugar_die("Unauthorized access to administration.");

$focus = new User();

$detailView = new DetailView(); 
$offset=0;
if (isset($_REQUEST['offset']) or !empty($_REQUEST['record'])) {
	$result = $detailView->processSugarBean("USER", $focus, $offset);
	if($result == null) {
	    sugar_die($app_strings['ERROR_NO_RECORD']);
	}
	$focus=$result;
} else {
	header("Location: index.php?module=Users&action=index");
}
if(isset($_REQUEST['isDuplicate']) && $_REQUEST['isDuplicate'] == 'true') {
	$focus->id = "";
}

echo get_module_title($mod_strings['LBL_MODULE_NAME'], $mod_strings['LBL_MODULE_NAME'].": ".$focus->full_name." (".$focus->user_name.")", true);

$xtpl=new XTemplate ('modules/Users/DetailView.html');
$xtpl->assign("MOD", $mod_strings);
$xtpl->assign("APP", $app_strings);

$xtpl->assign("THEME", $theme);
$xtpl->assign("GRIDLINE", $gridline);
$xtpl->assign("IMAGE_PATH", $image_path);
$xtpl->assign("PRINT_URL", "index.php?".$GLOBALS['request_string']);
$xtpl->assign("ID", $focus->id);

$buttons = "<input title='".$app_strings['LBL_EDIT_BUTTON_TITLE']."' accessKey='".$app_strings['LBL_EDIT_BUTTON_KEY']."' class='button' onclick=\"this.form.return_module.value='Users'; this.form.return_action.value='AccessControlView'; this.form.return_id.value='$focus->id'; this.form.action.value='AccessControlEdit'\" type='submit' name='Edit' value='  ".$app_strings['LBL_EDIT_BUTTON_LABEL']."  '>  ";
$buttons .= " <input title='".$app_strings['LBL_CANCEL_BUTTON_TITLE']."' accessKey='".$app_strings['LBL_CANCEL_BUTTON_KEY']."' class='button' onclick=\"this.form.return_module.value='Users'; this.form.return_action.value='AccessControlView'; this.form.return_id.value='$focus->id'; this.form.action.value='DetailView'; this.form.submit();\" type='button' name='Cancel' value='  ".$app_strings['LBL_CANCEL_BUTTON_LABEL']."  '>  ";
$xtpl->assign('BUTTONS', $buttons);

if($focus->is_admin == '1') {
	$xtpl->assign("IS_ADMIN", 'checked="checked"');
}
if(!empty($focus->portal_only)){
    $portal_only_value = "checked";
    $xtpl->assign("IS_PORTALONLY", 'checked="checked"');
}

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

$xtpl->parse('main');
$xtpl->out('main');

$xtpl->parse('permission_settings.quotation_mode');
$xtpl->parse('permission_settings.project_mode');
$xtpl->parse('permission_settings');
$xtpl->out('permission_settings');

//require_once('include/SubPanel/SubPanelTiles.php');
//$subpanel = new SubPanelTiles($focus, 'Users');
//echo $subpanel->display(true, true);

require_once('modules/ACLRoles/DetailUserRole.php');

?>
