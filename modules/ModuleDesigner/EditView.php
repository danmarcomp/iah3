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

require_once 'modules/ModuleDesigner/ModuleDesigner.php';

if(!is_admin($current_user)){
	sugar_die('Admin Only');	
}


$isNew = false;
$module_name = array_get_default($_REQUEST, 'mod_name', array_get_default($_REQUEST, 'save_module', ''));
if (!$module_name) {
	$module_name = array_get_default($_REQUEST, 'mod_name!regexp', '');
	$isNew = true;
}

$redirect = array(
		'perform', 
		array(
			'module' => 'ModuleDesigner',
			'action' => 'index',
			'layout' => '', 
		),
	);

$errors = array();
if ($module_name !== '') {
	if (!preg_match('/^[a-z][a-z_0-9]+$/i', $module_name)) {
		$errors[] = 'ERR_INVALID_MODULE_NAME';
	}
}


if (!$isNew && empty($errors))  {
	if (!AppConfig::setting("modinfo.primary_beans.$module_name")) {
		$errors[] = 'ERR_NO_MODULE';
	}
	if (!AppConfig::setting("modinfo.by_name.$module_name.detail.module_designer", true)) {
		$errors[] = 'ERR_DESIGNER_DISABLED';
	}
}

if ($isNew && $module_name && is_dir("modules/$module_name")) {
	$errors[] = 'ERR_DUPLICATE_MODULE';
}

foreach ($errors as $k => $error) {
	$errors[$k] = array(translate($error, 'ModuleDesigner'), 'error');
}

if (!empty($errors)) 
	display_flash_messages_default($errors);


$mb = new ModuleDesigner($module_name, $_REQUEST);

if (empty($errors)) {
	if ($mb->save()) {
		return $redirect;
	}
}

$layout = array_get_default($_REQUEST, 'layout');
if(! preg_match('~^\w+$~', $layout) || ! file_exists("modules/ModuleDesigner/views/edit.$layout.php"))
	$layout = 'Standard';
$layout_file = "edit.$layout.php";


$cpath = AppConfig::local_config_path();
if(! is_writable($cpath)) {
	echo '<p class="error">' . str_replace('%s', $cpath, translate('MSG_CONFIG_NOT_WRITABLE')) . '</p>';
}

require_once 'modules/ModuleDesigner/ModuleDesignerModel.php';

$model = new ModuleDesignerModel;

$lspec = ConfigParser::load_file('modules/ModuleDesigner/views/'.$layout_file);
$dfields = $model->getDisplayFieldDefinitions();
$result = new RowResult();
$result->module_dirs = array('ModuleDesigner');
$result->fields = $dfields;


$hidden = array(
	'module' => 'ModuleDesigner',
	'action' => 'EditView',
	'layout' => $layout,
	'__save' => 1,
);

if (!$isNew)
	$hidden['save_module'] = $module_name;

foreach($lspec['detail'] as $k => $v)
	$lspec['layout'][$k] = $v;


require_once('include/layout/forms/FormGenerator.php');
$gen = FormGenerator::html_form($model, $lspec['layout'], 'ModuleDesigner', 'ModuleDesigner');
$gen->setDefaultEditable(true);
$gen->default_buttons_pos = 'topbottom';
$title = translate($lspec['detail']['title']);
$pageInstance->set_title($title);

if ($module_name && !$isNew) {
	global $theme;
	$icon = get_image($module_name, '');
	$title .= ' &laquo; ' . $icon . ' ' . translate('LBL_MODULE_TITLE', $module_name);
}
$gen->setTitle($title);

$reqd = $gen->getRequiredFields();
$icon = $gen->getLayout()->getIcon();
if($icon) $pageInstance->init_favicon($icon);

$result->row = ModuleDesignerModel::populateValues($module_name, $_REQUEST);
$result->new_record = $isNew;

$format_fields = array();
foreach($result->fields as $f => $spec) {
	if(isset($spec['editable']) && ! $spec['editable']) {
		$format_fields[$f] = $spec;
	}
}
if($format_fields) {
	require_once('include/layout/FieldFormatter.php');
	$fmt = new FieldFormatter('html', 'editview');
	$result->formatted = array_merge($result->row, $fmt->formatRow($format_fields, $result->row));
} else
	$result->formatted = $result->row;

$lq = new ListQuery();
$lq->populateSecondary($result, false); // fill in any ref fields
$gen->getFormObject()->addHiddenFields($hidden);
$gen->renderForm($result, 'ModuleDesigner');
echo $gen->getResult();
$gen->exportIncludes();


?>
