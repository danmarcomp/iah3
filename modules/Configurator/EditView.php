<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/**
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
 */

action_restrict_params('demo', array('save', 'restore'));

if(!is_admin($current_user)){
	sugar_die('Admin Only');	
}

$layout = array_get_default($_REQUEST, 'layout');
$layout_file = "edit.$layout.php";
$layout_path = null;
if(preg_match('~^\w+$~', $layout)) {
	$detail = AppConfig::setting("views.detail.Configurator.edit.$layout");
	$ext_detail = AppConfig::setting("ext.new_views.Configurator.edit.$layout");
	if($ext_detail)
		$layout_path = $ext_detail[0]['name'];
	else
		$layout_path = "modules/Configurator/views/edit.$layout.php";
}
if(empty($layout_path)) {
	$layout = 'Settings';
	$layout_path = "modules/Configurator/views/edit.$layout.php";
}


if($layout == 'Company') {
	require_once('include/ListView/ListViewManager.php');
	$listview = new ListViewManager('listview', array('is_primary' => true));
	$listview->show_tabs = false;
	$listview->show_title = 'formatter';
	$listview->outer_style = 'margin-top: 1.5em';
	$listview->loadRequest();
	$listview->addHiddenFields(array('layout' => $layout));

	if(! $listview->initModuleView('CompanyAddress'))
		$listview = null;
	else if($listview->async_list) {
		$listview->render();
		return;
	}
}


$cpath = AppConfig::local_config_path();
if(! is_writable($cpath)) {
	echo '<p class="error">' . str_replace('%s', $cpath, translate('MSG_CONFIG_NOT_WRITABLE')) . '</p>';
}


require_once('modules/Configurator/Configurator.php');
require_once('modules/Configurator/ConfiguratorModel.php');
require_once('modules/Configurator/utils.php');

$configurator = new Configurator();
$model = new ConfiguratorModel();

$config_files = Array(
	$layout_path,
	AppConfig::custom_dir() . 'modules/Configurator/views/'.$layout_file,
);
$ext_files = AppConfig::setting("ext.views.Configurator.edit.$layout");
if($ext_files)
	array_splice($config_files, 1, 0, array_column($ext_files, 'name'));
$lspec = ConfigParser::load_files($config_files);
$dfields = $model->getDisplayFieldDefinitions();
$result = new RowResult();
$result->module_dirs = array('Configurator');
$fs = $cfg_fields = array();
$req_ids = array();
foreach($dfields as $k => $f) {
	if(isset($f['config']))
		$cfg_fields[$k] = $f['config'];
	if(isset($f['id_name']))
		$req_ids[$k] = $f['id_name'];
}
$result->fields = $dfields;

$buttons = array(
	'save' => array(
		'vname' => 'LBL_SAVE_BUTTON_LABEL',
		'title' => 'LBL_SAVE_BUTTON_TITLE',
		'accesskey' => 'LBL_SAVE_BUTTON_KEY',
		'type' => 'submit',
		'icon' => 'icon-accept',
        'params' => array(
            'record_perform' => 'save',
        )
	),
	'restore' => array(
		'vname' => 'LBL_RESTORE_BUTTON_LABEL',
		'type' => 'button',
		'confirm' => 'CONFIRM_RESTORE',
        'params' => array(
            'record_perform' => 'restore',
        )
	),
	'recheck' => null,
	'runbackup' => null,
	'cancel' => array(
		'vname' => 'LBL_CANCEL_BUTTON_LABEL',
		'title' => 'LBL_CANCEL_BUTTON_TITLE',
		'accesskey' => 'LBL_CANCEL_BUTTON_KEY',
		'type' => 'button',
		'onclick' => "return SUGAR.util.loadUrl('index.php?module=Administration&action=index');",
		'icon' => 'icon-cancel',
	),
);

if($layout == 'Backup') {
	$buttons['recheck'] = array(
		'vname' => 'LBL_RECHECK_BUTTON_LABEL',
		'type' => 'button',
	);
	$buttons['runbackup'] = array(
		'vname' => 'LBL_RUN_BACKUP_BUTTON_LABEL',
		'type' => 'button',
		'confirm' => 'LBL_CONFIRM_RUN_BACKUP',
        'params' => array(
            'record_perform' => 'runbackup',
        ),
        'conn_params' => array(
        	'status_msg' => translate('LBL_BACKUP_RUNNING'),
        ),
	);
}
$lspec['layout']['form_buttons'] = $buttons;

$hidden = array(
	'module' => 'Configurator',
	'action' => 'EditView',
	'layout' => $layout,
);

foreach($lspec['detail'] as $k => $v)
	$lspec['layout'][$k] = $v;

require_once('include/layout/forms/FormGenerator.php');
$gen = FormGenerator::html_form($model, $lspec['layout'], 'ConfigureSettings', 'ConfigureSettings');
$gen->setDefaultEditable(true);
$gen->default_buttons_pos = 'topbottom';
$title = translate($lspec['detail']['title']);
$pageInstance->set_title($title);
$reqd = $gen->getRequiredFields();
$icon = $gen->getLayout()->getIcon();
if($icon) $pageInstance->init_favicon($icon);

foreach(array_intersect(array_keys($req_ids), $reqd) as $k)
	$reqd[] = $req_ids[$k];

$fetch = array();
foreach(array_intersect(array_keys($cfg_fields), $reqd) as $k)
	$fetch[$k] = $cfg_fields[$k];

$configurator->settings = $fetch;

$perform = array_get_default($_REQUEST, 'record_perform');
$upd_status = javascript_escape(translate('LBL_SETTINGS_UPDATED'));
if($perform == 'save'){
	$configurator->saveConfig();
	ob_clean();
	echo $upd_status;
	echo '<script nodisplay>SUGAR.util.loadUrl("index.php?module=Administration&action=index", "'.$upd_status.'");</script>';
	return;
}

if($perform == 'restore'){
	$configurator->restoreConfig();	
	ob_clean();
	echo $upd_status;
	echo '<script nodisplay>SUGAR.util.loadUrl("index.php?module=Administration&action=index", "'.$upd_status.'");</script>';
	return;
}

if($layout == 'Backup' && $perform == 'runbackup') {
	$configurator->saveConfig();
	runBackup();
	$upd_status = javascript_escape(translate('LBL_BACKUP_COMPLETE'));
	echo '<script nodisplay>SUGAR.util.loadUrl("index.php?module=Configurator&action=EditView&layout=Backup", "'.$upd_status.'");</script>';
	return;	
}

$cfg_vals = $configurator->loadConfig(false, true);
$coll_key = 'site_database_primary_collation';
if(array_key_exists($coll_key, $cfg_vals) && empty($cfg_vals[$coll_key]))
	$cfg_vals[$coll_key] = AppConfig::setting('site.db_defaults.collation');
$result->row = $cfg_vals;
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
$gen->renderForm($result, 'Configurator');
echo $gen->getResult();
$gen->exportIncludes();


if(! empty($listview))
	$listview->render();

?>
