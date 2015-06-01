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

$newRels = $json->decode(array_get_default($_POST, 'newrels'));
if (!empty($newRels)) {
	require_once 'include/config/format/ConfigParser.php';
	require_once 'include/config/format/ConfigWriter.php';

	$createTable = false;
	$linkTableName = 'mb_' . strtolower($module_name) . '_links';
	try {
		$model = new ModelDef($linkTableName);
	} catch (IAHModelError $e) {
		$createTable = true;
	}
	$filename = "modules/{$module_name}/models/link.{$linkTableName}.php";
	if ($createTable) {
		$content = array(
			'detail' => array(
				'type' => 'link',
				'table_name'=> $linkTableName,
				'primary_key' => array('left_id', 'right_id', 'relation_type'),
			),
			'fields' => array(
				'app.date_modified' => array(),
				'app.deleted' => array(),
				'left' => array(
					'type' => 'ref',
					'bean_name' => $modelName,
				),
				'right' => array(
					'required' => true,
					'type' => 'ref',
					'dynamic_module' => 'relation_type',
				),
				'relation_type' => array(
					'type' => 'varchar',
					'required' => true,
				),
			),
			'indices' => array(
				'idx_left' => array(
					'fields' => array('left_id'),
				),
				'idx_right' => array(
					'fields' => array('right_id'),
				),
				'idx_relation' => array(
					'fields' => array('relation_type'),
				),
			),
		);
	} else {
		$content = ConfigParser::load_file($filename);
	}
	foreach ($newRels as $relName => $relModule) {
		$relModel = AppConfig::setting("modinfo.primary_beans.$relModule");
		$content['relationships'][$relName] = array(
			'lhs_key' => 'id',
			'rhs_key' => 'id',
			'relationship_type' => 'many-to-many',
			'join_key_lhs' => 'left_id',
			'join_key_rhs' =>  'right_id',
			'relationship_role_column' =>  'relation_type',
			'relationship_role_column_value' => $relName,
			'lhs_bean' => $modelName,
			'rhs_bean' => $relModel,
		);
	}
	$cw = new ConfigWriter;
	$cw->writeFile($filename, $content);

	$filename = AppConfig::custom_dir() . "/modules/{$module_name}/models/bean.{$modelName}.php";
	try {
		$content = ConfigParser::load_file($filename);
	} catch (IAHConfigFileError $e) {
		$content = array();
	}
	foreach ($newRels as $relName => $relModule) {
		$content['links'][$relName] = array(
			'relationship' => $relName,
		);
	}
	$cw->writeFile($filename, $content);
	AppConfig::invalidate_cache('model');

	if ($createTable) {
		require_once 'include/database/DBChecker.php';
		$checker = new DBChecker();
		$checker->reloadModels();
		$checker->checkRepairModel($linkTableName, DB_CHECK_STANDARD | DB_CHECK_AUDIT, true, false);
	}	
}



$all = AppConfig::setting('model.index.relationships');
$relations = array();
global $theme;
foreach ($all as $relName => $beanName) {
	$rel = AppConfig::setting("model.relationships.$beanName.$relName");
	if (($rel['left']['model'] == $modelName || $rel['right']['model'] == $modelName) && isset($rel['join'])) {
		$rmodel = ($rel['left']['model'] == $modelName) ? $rel['right']['model'] : $rel['left']['model'];
		$rmodule = AppConfig::module_for_model($rmodel);
		$rel['module_name'] = translate('LBL_MODULE_TITLE', $rmodule);
		$rel['bean_name'] = $rmodel;
		$rel['icon'] = get_image("themes/$theme/images/$rmodule.gif", '');
		$relations[] = $rel;
	}
}

$allModules = array();
$moduleData = array();
$beans = AppConfig::setting('modinfo.primary_beans');
foreach ($beans as $module => $model) {
	if (AppConfig::setting("modinfo.by_name.$module.detail.module_designer", true)) {
		$allModules[$module] =  translate('LBL_MODULE_TITLE', $module);
		$moduleData[$module] = array(
			'label' => translate('LBL_MODULE_TITLE', $module),
			'icon' => get_image("themes/$theme/images/$module.gif", ''),
			'bean' => AppConfig::setting("modinfo.primary_beans.$module"),
		);
	}
}

asort($allModules);
uasort($relations, 'relations_sort_by_title');

require_once('include/layout/forms/EditableForm.php');
$frm = new EditableForm('', 'RelationsForm');
$module_spec = array(
	'name' => 'add_module_name',
	'type' => 'enum',
	'options' => $allModules,
	'onchange' => '$("rel_add_button").style.display = this.getValue() == "" ? "none" : "";',
);
$sel_module = $frm->renderSelect($module_spec, '');
$tpl->assign('MODULE_SELECTOR', $sel_module);


$tpl->assign('relationships',  $relations);
$tpl->assign('moduleData',  $json->encode($moduleData));

$tpl->assign('module',  $module_name);
$tpl->assign('LANG',  $mod_strings);

$title = $mod_strings['LBL_EDIT_RELATIONS'];
$icon = get_module_header_icon($module_name, '');
$title .= ' &laquo; ' . $icon . ' ' . translate('LBL_MODULE_TITLE', $module_name);
echo get_module_title($mod_strings['LBL_MODULE_NAME'], $title, true); 
$tpl->display('modules/ModuleDesigner/templates/relations.tpl');
echo $frm->exportIncludes();

function relations_sort_by_title($a, $b)
{
	return ($a['module_name'] < $b['module_name']) ? -1 : 1;
}



