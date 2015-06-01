<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point'); 
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

if (! in_array('designer', $_SESSION['LIC_PRODUCTS']))
	indexRedirect(array('path' => 'index.php'));

require_once 'include/Sugar_Smarty.php';

$tpl = new Sugar_Smarty;
$json = getJSONObj();


$module_name = array_get_default($_REQUEST, 'mod_name', '');

$redirect = array(
		'perform', 
		array(
			'module' => 'ModuleDesigner',
			'action' => 'index',
			'layout' => '', 
		),
	);

if (!preg_match('/^[a-z][a-z_0-9]+$/i', $module_name))
	return $redirect;

if (!AppConfig::setting("modinfo.primary_beans.$module_name"))
	return $redirect;
if (!AppConfig::setting("modinfo.by_name.$module_name.detail.module_designer", true))
	return $redirect;

$modelName = AppConfig::setting("modinfo.primary_beans.$module_name");

$all = AppConfig::setting('model.index.relationships');
global $theme;
$links = AppConfig::setting("model.links.$modelName");
$panels = array();
foreach ($links as $linkName => $link) {
	$relName = array_get_default($link, 'relationship');
	if ($relName) {
		$beanName = $all[$relName];
		$rel = AppConfig::setting("model.relationships.$beanName.$relName");
		$defTitle = 'MB_SUBPANEL_' . strtoupper($module_name) . '_' . strtoupper($relName);
		if (isset($rel['join'])) {
			$relModel =($rel['left']['model'] == $modelName) ? $rel['right']['model'] : $rel['left']['model'];
			$relModule = AppConfig::module_for_model($relModel);
			$panels[$linkName] = array(
				'name' => $linkName,
				'visible' => false,
				'vname' => array_get_default($link, 'vname', $defTitle),
				'relationship' => $relName,
				'icon' => get_image("themes/$theme/images/$relModule.gif", ''),
				'module_name' => translate('LBL_MODULE_TITLE', $relModule),
			);
			$panels[$linkName]['title'] = translate($panels[$linkName]['vname'], $module_name);
			if (strpos($panels[$linkName]['title'], 'MB_SUBPANEL_') === 0)
				$panels[$linkName]['title'] = $relModule;
		}
	}
}
$visible = AppConfig::setting("views.layout.$module_name.view.Standard.subpanels");

foreach ($visible as $k => $v) {
	if(is_string($v)) $v = array('name' => $v);
	if(! is_array($v) || empty($v['name']))
		continue;
	$k = $v['name'];
	if (isset($panels[$k])) {
		$panels[$k]['visible'] = true;
		$panels[$k]['idx'] = $idx;
		if (isset($v['title'])) {
			$panels[$k]['title'] = translate($v['title'], $module_name);
		}
	}
}

if (!empty($_POST['_save'])) {

	$detail = AppConfig::setting("views.detail.{$module_name}.view.Standard");
	if (empty($detail)) {
		$detail = array(
			'type' => 'view',
			'name' => 'Standard',
			'title' => 'LBL_MODULE_TITLE',
		);
	}
	$layout = AppConfig::setting("views.layout.{$module_name}.view.Standard", array());
	$form_def = array(
		'detail' => $detail,
		'layout' => $layout,
	);

	if (empty($form_def['layout']['subpanels']))
		$form_def['layout']['subpanels'] = array();

	foreach ($panels as $k => $v) {
		$defTitle = 'MB_SUBPANEL_' . strtoupper($module_name) . '_' . strtoupper($k);
		$found = false;
		foreach ($form_def['layout']['subpanels'] as $sk => $sv) {
			if (is_array($sv)) {
				$sv = $sv['name'];
			}
			if ($sv == $k) {
				$found = $sk;
				if (!array_get_default($_POST, $k . '_visible')) {
					unset($form_def['layout']['subpanels'][$sk]);
				}
				break;
			}
		}
		if (array_get_default($_POST, $k . '_visible')) {
			if ($found !== false) {
				$p =& $form_def['layout']['subpanels'][$found];
				if (is_string($p)) {
					$p = array('name' => $p, 'title' => $defTitle);
				}
			} else {
				$form_def['layout']['subpanels'][] = array(
					'name' => $k,
					'title' => $defTitle,
				);
			}
			AppConfig::set_local("lang.strings.base.{$module_name}.$defTitle", array_get_default($_POST, $k . '_title'));
		}
	}
	AppConfig::save_local('lang');
	require_once 'include/config/format/ConfigWriter.php';
	$cw = new ConfigWriter;
	$filename =  AppConfig::custom_dir() . "/modules/$module_name/new_views/view.Standard.php";
	$cw->writeFile($filename, $form_def);
	AppConfig::invalidate_cache('views');
	unset($_POST['_save']);
	return array(
		'perform', 
		array(
			'module' => 'ModuleDesigner',
			'action' => 'Subpanels',
			'mod_name' => $module_name,
			'layout' => '', 
		),
	);
}

uasort($panels, 'subpanels_sort_by_title');

$tpl->assign('MODULES_OPTIONS', get_select_options_with_id($allModules));
$tpl->assign('subpanels',  $panels);

$tpl->assign('module',  $module_name);
$tpl->assign('LANG',  $mod_strings);

$title = $mod_strings['LBL_EDIT_SUBPANELS'];
$icon = get_module_header_icon($module_name, '');
$title .= ' &laquo; ' . $icon . ' ' . translate('LBL_MODULE_TITLE', $module_name);
echo get_module_title($mod_strings['LBL_MODULE_NAME'], $title, true); 

$tpl->display('modules/ModuleDesigner/templates/subpanels.tpl');

function subpanels_sort_by_title($a, $b)
{
	return ($a['title'] < $b['title']) ? -1 : 1;
}

