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


require_once('modules/LicenseInfo/LicenseHistory.php');

global $app_strings, $app_list_strings, $mod_strings;
global $vendor_info;
require_once('vendor_info.php');


require_once('include/ListView/ListViewManager.php');
$subpanel = new ListViewManager('subpanel', array('show_title' => false, 'show_filter' => false, 'show_checks' => false));
$subpanel->loadRequest();

$layout = 'Standard';
if(! $subpanel->initModuleView($module, $layout))
	ACLController::displayNoAccess();

$subpanel->getFormatter()->setTitle(translate('LBL_LICENSE_HISTORY'));
$subpanel->getFormatter()->hide_remove_links = true;
if($subpanel->async_list) {
	$subpanel->render();
	return;
}


require_once('include/layout/forms/FormGenerator.php');
$form_det = AppConfig::setting("views.detail.{$module}.view.Index");
$form_layout = AppConfig::setting("views.layout.{$module}.view.Index");
$form_def = $form_det + $form_layout;

$layout = new FormLayout($form_def);

$form_gen = FormGenerator::new_form('html', null, $layout, 'DetailForm');

$record = new RowResult();
foreach($vendor_info as $k => $v) {
	$record->assign("vendor_$k", $v);
}
$record->addField('vendor_address', array(
	'type' => 'address',
	'source' => array(
		'alias_fields' => array(
			'address_street' => 'vendor_address_street',
			'address_city' => 'vendor_address_city',
			'address_state' => 'vendor_address_state',
			'address_country' => 'vendor_address_country',
			'address_postalcode' => 'vendor_address_postalcode',
		),
	),
));
$record->fields['vendor_url']['type'] = 'url';
$record->fields['vendor_phone']['type'] = 'phone';
$record->fields['vendor_email_sales']['type'] = 'email';
$record->fields['vendor_email_support']['type'] = 'email';


$seed = new LicenseHistory();
if($seed->retrieve_latest() === null)
	$record->assign('license_status', translate('LBL_LICENSE_MISSING'));
else {
	$who = $seed->licensee;
	$exts = $seed->get_extensions();
	$limit = $seed->ext_active_limit;
	if(licensee() != $who || $limit != max_users())
		LicenseHistory::invalidateSessionCache();
	$record->assign('license_status', sprintf(translate('LBL_LICENSE_INFO'), "<b>$who</b>", "<b>$limit</b>"));
}
$record->fields['license_status']['type'] = 'html';

$status = $seed->get_support_status();
$supp_image = '<div class="input-icon icon-led' . $status['colour'] . '"></div>';
$record->assign('support_info', $supp_image . '&nbsp;'. $status['text']);
$record->fields['support_info']['type'] = 'html';

if(! empty($seed->ext_product_list)) {
	$record->addField('product_list', array(
		'type' => 'multienum',
		'options' => 'license_product_dom',
	));
	$record->assign('product_list', $seed->ext_product_list);
	/*$prods = explode('^,^', $seed->ext_product_list);
	$prod_str = array();
	$dom = $app_list_strings['license_product_dom'];
	foreach($prods as $p) {
		if(isset($dom[$p])) $prod_str[] = $dom[$p];
	}
	if($prod_str) {
		$record->assign('product_list', implode(', ', $prod_str));
		//$xtpl->parse('summary.products');
	}*/
}

$form_gen->getLayout()->addFormHiddenFields(array(
	'module' => 'LicenseInfo',
	'action' => 'Update',
));


$record->assign('key_form', <<<EOH
		<p>{$mod_strings['LBL_QUICK_UPDATE_TEXT']}</p>
		<input type="text" size="20" class="input-text" name="license_key"><br>
		<button type="submit" class="input-button input-outer" style="margin-top: 0.2em"><div class="input-icon icon-accept left"></div><span class="input-label">{$app_strings['LBL_UPDATE']}</span></button>
EOH
);
$record->fields['key_form']['type'] = 'html';


$record->assign('upload_form', <<<EOH
		<p>{$mod_strings['LBL_UPLOAD_LICENSE_TEXT']}</p>
		<input type="file" size="20" class="input-file" name="license_file"><br>
		<button type="submit" class="input-button input-outer" style="margin-top: 0.2em"><div class="input-icon icon-accept left"></div><span class="input-label">{$app_strings['LBL_UPDATE']}</span></button>
EOH
);
$record->fields['upload_form']['type'] = 'html';


$form_gen->formatResult($record);
$form_gen->renderForm($record, $module);
echo $form_gen->getResult();
$form_gen->exportIncludes();

if ($seed->id) {
    require_once('modules/LicenseInfo/SystemUpdateManager.php');
    if(SystemUpdateManager::is_enabled()) {
		echo '<br>';
		$updates_manager = new SystemUpdateManager();
		echo $updates_manager->getUpdatesTable();
	}
}

echo '<br>';

$subpanel->render();

?>
