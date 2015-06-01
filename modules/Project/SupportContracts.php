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


require_once 'include/database/ListQuery.php';
require_once 'include/ListView/ListViewManager.php';

$json = getJSONObj();

$project_id = $_REQUEST['record'];

$lq = new ListQuery('Project', null, array('link_name' => 'assets'));
$lq->setParentKey($project_id);
$lq->addSimpleFilter('service_subcontract_id', null, 'NULL');
$result = $lq->runQuery();

if (!$result->getResultCount()) {
	echo translate('LBL_NO_PRODUCTS_TO_CONVERT');
	return;
}

$lv = new ListViewManager('listview', true);
$lv->show_create_button = false;
//$lv->show_mass_update = false;
$lv->show_tabs = false;
$lv->show_filter = false;
$lv->show_help = false;
//$lv->custom_icon = 'Accounts';
$lv->custom_title_html = translate('LBL_POPUP_TITLE1');
$lv->hide_navigation_controls = true;
$lv->no_sort = true;
unset($_REQUEST['list_limit']);
$lv->default_list_limit = -1;
$lv->loadRequest();

if(! $lv->initModuleView('Assets'))
	ACLController::displayNoAccess();
$lv->getFormatter()->setQuery($lq);
$mu = $lv->getFormatter()->getMassUpdate();
$mu->no_mass_panel = true;
$mu->no_mass_export = true;
$mu->no_mass_print = true;
$mu->no_mass_delete = true;

$lv->render();

echo  '<br /><h2>' . translate('LBL_POPUP_TITLE2') . '</h2><br />';

if (!empty($_POST['list_uids'])) {
	@ob_clean();
	$pageInstance->add_js_literal("SUGAR.ui.PopupManager.close();", null, LOAD_PRIORITY_END);
	$pageInstance->add_js_literal("window.location.href='index.php?module=Project&action=DetailView&record={$project_id}';", null, LOAD_PRIORITY_END);
	return;
}


$defaultContractId = '';
$defaultContractNo = '';
$defaultContractType = '';
$defaultContractTypeName = '';

$actOptions = array('keys' => array(), 'values' => array(), 'width' => '30em');
$contrOptions = array('keys' => array(), 'values' => array(), 'width' => '50em');
$contrTypeOptions = array('keys' => array(), 'values' => array(), 'width' => '50em');

$result = ListQuery::quick_fetch_all('ContractType');
foreach ($result->getRows() as $row) {
	$contrTypeOptions['keys'][] = $row['id'];
	$contrTypeOptions['values'][] = $row['name'];
	if (!$defaultContractType) {
		$defaultContractType = $row['id'];
		$defaultContractTypeName = $row['name'];
	}
}

$project = ListQuery::quick_fetch_row('Project', $project_id);
$lq = new ListQuery('Contract');
$lq->addSimpleFilter('account_id', $project['account_id']);
$result = $lq->runQuery();

$actOptions['keys'][] = 'create_new';
$actOptions['values'][] = translate('LBL_CREATE_CONTRACT');

if ($result->getResultCount()) {
	$actOptions['keys'][] = 'use_existing';
	$actOptions['values'][] = translate('LBL_SELECT_CONTRACT');
	foreach ($result->getRows() as $row) {
		$contrOptions['keys'][] = $row['id'];
		$contrOptions['values'][] = $row['contract_no'];
		if (!$defaultContractId) {
			$defaultContractId = $row['id'];
			$defaultContractNo = $row['contract_no'];
		}
	}
	
}
$ctls = array();

$actOptions = $json->encode($actOptions);
$contrOptions = $json->encode($contrOptions);
$contrTypeOptions = $json->encode($contrTypeOptions);
$ctls[] = "new SUGAR.ui.SelectInput('contract_action-input', {name: 'contract_action', options: $actOptions, onchange: function(k) { contract_action_change(this);}})";
$ctls[] = "new SUGAR.ui.SelectInput('contract-input', {name: 'contract', options: $contrOptions, onchange: function(k) { }})";
$ctls[] = "new SUGAR.ui.SelectInput('contract_type-input', {name: 'contract_type', options: $contrTypeOptions, onchange: function(k) { }})";
$ctls[] = "new SUGAR.ui.TextInput('contract_name-input', {name: 'contract_name', onchange: function(k) { }})";

$form = '<form name="contract_actions_form">';
$form .=
			'<table border="0"><tr><td>' .
			'<input type="hidden" id="contract_action" name="contract_action" value="create_new" />'.
			'<button type="button" class="input-select input-outer " id="contract_action-input"><div class="input-arrow select-label" style="width: 30em"><span id="contract_action-input-label" class="input-label">' . translate('LBL_CREATE_CONTRACT') . '</span></div></button>' .
			'</td>';
$form .=
			'<td id="contract-cell" style="display:none">' .
			'<input type="hidden" name="contract" id="contract" value="' . $defaultContractId . '" />'.
			'<button type="button" class="input-select input-outer " id="contract-input"><div class="input-arrow select-label" style="width: 30em"><span id="contract-input-label" class="input-label">' . $defaultContractNo . '</span></div></button>' .
			'</td>';
$form .=
	'<td id="contract_type_label-cell">' .
	translate('LBL_CONTRACT_TYPE') .
	'</td>';
$form .=
			'<td id="contract_type-cell">' .
			'<input type="hidden" name="contract_type" id="contract_type" value="' . $defaultContractType . '" />'.
			'<button type="button" class="input-select input-outer " id="contract_type-input"><div class="input-arrow select-label" style="width: 30em"><span id="contract_type-input-label" class="input-label">' . $defaultContractTypeName . '</span></div></button>' .
			'</td>';
$form .=
	'<td id="contract_name_label-cell">' .
	translate('LBL_CONTRACT_NAME') .
	'</td>';
$form .=
	'<td id="contract_name-cell">' .
	'<input type="text" size="35" maxlength="150" class="input-text input-outer" value="" name="contract_name" id="contract_name">' .
	'</td>';

$form .= '</tr><tr><td>' .
		'<input type="button" class="form-button" value="' . translate('LBL_PROCEED') . '" onclick="return create_contracts();" />' .
		'</td>';

$form .= '</tr></table></form>';


echo $form;
$pageInstance->add_js_literal('SUGAR.ui.initForm("contract_actions_form", ['.implode(', ', $ctls).']);', null, LOAD_PRIORITY_END);
$pageInstance->add_js_literal(<<<JS
contract_action_change = function(input) {
	if (input.getValue() == 'create_new') {
		$('contract-cell').style.display = 'none';
		$('contract_type-cell').style.display = '';
		$('contract_type_label-cell').style.display = '';
		$('contract_name_label-cell').style.display = '';
		$('contract_name-cell').style.display = '';
	} else {
		$('contract-cell').style.display = '';
		$('contract_type-cell').style.display = 'none';
		$('contract_type_label-cell').style.display = 'none';
		$('contract_name_label-cell').style.display = 'none';
		$('contract_name-cell').style.display = 'none';
	}
};

create_contracts = function() {
	var errors = [];
	var inputs = ['contract_type-input', 'contract_name', 'contract-input']
	var params = {
		contract_action: $('contract_action').value,
		contract_type: $('contract_type').value,
		contract_name: $('contract_name').value,
		contract_id: $('contract').value,
		account_id: '{$project['account_id']}',
		record: '{$project_id}'
	};
	for (var i = 0; i < inputs.length; i ++) {
		SUGAR.ui.addRemoveClass(inputs[i], 'error', false);
		SUGAR.ui.addRemoveClass(inputs[i], 'invalid', false);
	}
	if (params.contract_action == 'create_new') {
		if (!params.contract_type.length) {
			errors.push('contract_type-input');
		}
		if (!params.contract_name.length) {
			errors.push('contract_name');
		}
	}
	if (errors.length) {
		for (var i =0; i < errors.length; i++) {
			SUGAR.ui.addRemoveClass(errors[i], 'error', true);
			SUGAR.ui.addRemoveClass(errors[i], 'invalid', true);
		}
		return false;
	}
	sListView.sendMassUpdate('{$lv->list_id}', 'create_contracts', null, params);
};

JS
, null, LOAD_PRIORITY_END);

