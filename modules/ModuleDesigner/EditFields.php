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

if (! in_array('designer', $_SESSION['LIC_PRODUCTS']))
	indexRedirect(array('path' => 'index.php'));


$json = getJSONObj();
$fields = $json->decode($_POST['fields']);


require_once 'include/Sugar_Smarty.php';
require_once 'modules/ModuleDesigner/ModuleDesignerFields.php';

$params = array();

$module_name = array_get_default($_REQUEST, 'mod_name');
$model_name = AppConfig::setting("modinfo.by_name.$module_name.detail.primary_bean");

$mbf = new ModuleDesignerFields($module_name);
$result = $mbf->save($fields);
if ($result)
	return $result;



$params['rel_modules'][''] = translate('LBL_RELATED_DYNAMIC');
$beans = AppConfig::setting('modinfo.primary_beans');
foreach ($beans as $mod => $bean) {
	if (AppConfig::setting("modinfo.by_name.$mod.detail.module_designer", true)) {
		$name = array_get_default($GLOBALS['app_list_strings']['moduleListSingular'], $mod, $bean);
		$params['rel_modules'][$bean] = $name;
	}
}

$date_default_opts = array('' => '');
$date_default_opts += AppConfig::setting('lang.lists.current.NewStudio.date_default_values', array());
$params['date_default_opts'] = $date_default_opts;


$fields = AppConfig::setting("model.fields.$model_name");

foreach ($fields as $k => $f) {
	if ($f['source']['type'] == 'custom_field')
		unset($fields[$k]);
	if (!in_array($f['type'], ModuleDesignerFields::$dataTypes))
		unset($fields[$k]);
	if (array_get_default($f, 'module_designer') == 'disabled')
		unset($fields[$k]);
}
$params['module'] = $module_name;
$params['fields'] = $fields;

$dom_opts = array_keys(AppConfig::setting('lang.lists.current.app', array()));
$exclude = array_merge(
	AppConfig::setting("lang.detail.current.app.lists_generated", array()),
	AppConfig::setting("lang.detail.current.app.lists_hidden", array())
);
$dom_opts = array_diff($dom_opts, $exclude);
sort($dom_opts);
$params['dom_opts'] = $dom_opts;

$pageInstance->add_js_language($module_name, null, LOAD_PRIORITY_BODY);

$typeSelect = '<select id="data_type" name="data_type" onchange="ModuleDesignerFields.updateField(this, \'type\');">';
foreach (ModuleDesignerFields::$dataTypes as $t) {
	if ($t == 'module_name')
		continue;
	$sel = ($t == @$params['data_type']) ? 'selected="selected"' : '';
	$typeSelect .= '<option value="' . $t . '" ' . $sel . ' >';
	$typeSelect .= translate('LBL_TYPE_' . strtoupper($t));
	$typeSelect .= '</option>';
}
$typeSelect .= '</select>';
		


$tpl = new Sugar_Smarty;
$tpl->assign('module',  $module_name);
$tpl->assign('LANG',  $mod_strings);
$tpl->assign('params', $json->encode($params));
$tpl->assign('typeSelect',  $typeSelect);

$tpl->assign('FUNCTIONS', $mbf->getFunctionListHtml());
$tpl->assign('FUNCTIONS_FIELDS', $mbf->getFieldListHtml());

$title = $mod_strings['LBL_EDIT_FIELDS'];
$icon = get_module_header_icon($module_name, '');
$title .= ' &laquo; ' . $icon . ' ' . translate('LBL_MODULE_TITLE', $module_name);
echo get_module_title($mod_strings['LBL_MODULE_NAME'], $title, true); 

$tpl->display('modules/ModuleDesigner/templates/fields_list.tpl');

