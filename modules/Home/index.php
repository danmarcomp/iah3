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

if (AppConfig::is_mobile()) return;

global $current_user;

require_once('include/Sugar_Smarty.php');
$sugar_smarty = new Sugar_Smarty();

$edit_layout = false;
$dashlets_locked = AppConfig::setting('layout.lock_homepage');
$editing = '';
if(isset($_REQUEST['edit'])) {
	if($_REQUEST['edit'] == 'page' && ! $dashlets_locked) {
		$edit_layout = true;
		$editing = 'page';
	}
	else if($_REQUEST['edit'] == 'delete' && ! $dashlets_locked) {
		$delete_layout = true;
	}
}

if(! $edit_layout)
	;//theme_hide_side_menu();
else
	$pageInstance->edit_layout = true;

if( ($dboard = $pageInstance->get_dashboard()) ) {
	if (!empty($_REQUEST['refresh_all_dashlets']) || !empty($GLOBALS['theme_changed'])) {
	}
	$columns = $dboard->process_dashlets($edit_layout);
	$can_edit = $dboard->user_can_edit();
} else {
	$columns = array();
	$can_edit = false;
}
if(! $columns)
	$columns = array(array(), array());

if(! empty($delete_layout) && $can_edit) {
	$dboard->mark_deleted($dboard->id);
	header('Location: index.php');
	exit;
}

$t = $pageInstance->get_dashboard_title();
$editMenu = $pageMenu = '';
if(! $edit_layout) {
	$actions = array();
	$t .= '&nbsp;&nbsp;&nbsp;<button type="button" class="form-button input-outer flatter menu-source" style="margin: -4px 0" id="dashboard-menu"><div class="input-arrow"><div class="input-icon icon-action"></div></div></button>';
	if(! $dashlets_locked && $can_edit) {
		$actions['edit_page'] = array(
			'label' => $mod_strings['LBL_EDIT_LAYOUT'],
			'icon' => 'icon-editlayout',
			'url' => '?module=Home&action=index&edit=page&layout='.$pageInstance->dashboard_id,
		);
		$actions['edit_detail'] = array(
			'label' => $mod_strings['LBL_EDIT_DETAILS'],
			'icon' => 'icon-edit',
			'url' => '?module=Dashboard&action=EditView&record='.$pageInstance->dashboard_id.'&return_module=Home&return_action=index&return_layout='.$pageInstance->dashboard_id,
		);
		$actions['delete'] = array(
			'label' => $mod_strings['LBL_DELETE_DASHBOARD'],
			'icon' => 'icon-delete',
			'url' => '?module=Home&action=index&edit=delete&layout='.$pageInstance->dashboard_id,
			'confirm' => $mod_strings['NTC_CONFIRM_DELETE_DASHBOARD'],
		);
	} else {
		// need support for disabled menu items
		//$editMenu .= '<div class="menuItem">'.$mod_strings['LBL_CANNOT_EDIT'].'</div>';
	}
	$actions['create_page'] = array(
		'label' => $mod_strings['LBL_CREATE_DASHBOARD'],
		'icon' => 'theme-icon create-Dashboard',
		'url' => '?module=Dashboard&action=EditView&return_module=Dashboard&return_action=DetailView',
	);
	$actions['dupe_page'] = array(
		'label' => $mod_strings['LBL_DUPE_DASHBOARD'],
		'icon' => 'icon-duplicate',
		'url' => '?module=Dashboard&action=Duplicate&record='.$pageInstance->dashboard_id.'&return_module=Dashboard&return_action=DetailView',
	);
	
	foreach($actions as &$a) {
		$a['perform'] = "SUGAR.util.loadUrl('{$a['url']}', null, true); return false;";
		unset($a['url']);
	}
	$json = getJSONobj();
	$params = array(
		'options' => array('keys' => array_keys($actions), 'values' => array_values($actions), 'width' => '200px'),
		'icon_key' => 'icon',
		'label_key' => 'label',
	);
	$params_js = $json->encode($params);
	$pageInstance->add_js_literal("SUGAR.ui.registerMenuSource('dashboard-menu', $params_js);", null, LOAD_PRIORITY_FOOT);
	
	/*if(!using_grouped_tabs()) {
		$pages = $pageInstance->dmgr->get_dashboard_page_info();
		$pageMenu = '<div class="menu" id="select_page_menu">';
		foreach($pages as $grp => $pgList) {
			$grpName = array_get_default($app_strings, $grp, $grp);
			foreach($pgList as $pid => $pg) {
				$pageLink = 'index.php?module=Home&action=index&layout='.$pid;
				$icon = get_image($image_path . $pg['icon'], 'alt="" style="vertical-align: middle"');
				$pageMenu .= '<a href="'.$pageLink.'" onclick="SUGAR.popups.hidePopup();" onmouseover="SUGAR.popups.hiliteItem(this, true);" onmouseout="SUGAR.popups.unhiliteItem(this);" class="menuItem">'.$icon.'&nbsp;'.htmlspecialchars($pg['title'], ENT_QUOTES, 'UTF-8').'</a>';
			}
		}
		$pageMenu .= '</div>';
		$t .= '&nbsp;&nbsp;<button type="button" class="button" onclick="SUGAR.popups.showPopup(this, \'select_page_menu\', {below:1});" style="margin: -4px 0">'.$mod_strings['LBL_SELECT_PAGE_BUTTON_LABEL'].'</button>';
	}*/
}
else if(! $dashlets_locked && $can_edit)
	$pageInstance->add_edit_javascript(! empty($_REQUEST['sugar_body_only']));

$icon = $pageInstance->get_dashboard_icon();
if($t) {
	echo get_module_title($icon, $t, ! $edit_layout);
	echo $editMenu;
	echo $pageMenu;
}

$sugar_smarty->assign('MOD', $mod_strings);
$sugar_smarty->assign('dashboard_id', $pageInstance->dashboard_id);
$sugar_smarty->assign('columns', $columns);
$sugar_smarty->assign('updateDisplay', !empty($pageInstance->cur_layout) && (is_admin($current_user) || $pageInstance->cur_layout{0} != '~') ? 'inline' : 'none');
$sugar_smarty->assign('global', is_admin($current_user));
$sugar_smarty->assign('isAdmin', is_admin($current_user) ? 'true' : 'false');
$sugar_smarty->assign('editLayout', $edit_layout);
$sugar_smarty->assign('editing', $editing);

$sugar_smarty->display('modules/Home/Home.tpl');


?>
