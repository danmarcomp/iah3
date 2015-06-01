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



global $theme, $image_path, $mod_strings;
require_once 'XTemplate/xtpl.php';
require_once 'modules/Project/Project.php';
require_once 'modules/Assets/Asset.php';
require_once 'modules/SupportedAssemblies/SupportedAssembly.php';
require_once 'modules/ContractTypes/ContractType.php';
require_once('themes/'.$theme.'/layout_utils.php');

$image_path = "themes/$theme/images/";

$focus = new Project;

$focus->retrieve($_REQUEST['record']);
$focus->load_relationship('assets');
$focus->load_relationship('supportedassemblies');

$assets = $focus->assets->getBeans(new Asset);
$assemblies = $focus->supportedassemblies->getBeans(new SupportedAssembly);

$assets = array_merge($assets, $assemblies);

$xtpl = new XTemplate('modules/Project/PopupCreateProducts.html');

$xtpl->assign('APP',  $app_strings);
$xtpl->assign('MOD',  $mod_strings);
$xtpl->assign('CURRENCY_ID',  $focus->currency_id);
$xtpl->assign('EXCHANGE_RATE',  $focus->exchange_rate);
$xtpl->assign('ID',  $focus->id);
$xtpl->assign('PRODUCT_ICON',  get_image($image_path . 'ProductCatalog',' border="0" valign="middle" '));
$xtpl->assign('ID',  $focus->id);
$xtpl->assign('ACCOUNT_ID',  $focus->account_id);
$xtpl->assign('ACCOUNT_NAME',  $focus->account_name);

$i = 0;
$oddRow = false;
$should_convert = false;
foreach ($assets as $asset) {
	if ($asset->service_subcontract_id) continue;
	$item = array(
		'num' => $i,
		'id' => $asset->id,
		'is_assembly' => (int)($asset->module_dir == 'SupportedAssemblies'),
		'name' => $asset->name,
	);
	$item['icon'] = get_image($image_path . ($item['is_assembly'] ? 'Assemblies' : 'ProductCatalog'),' border="0" valign="middle" ');
	$xtpl->assign('item', $item);
	if($oddRow) {
		$ROW_COLOR = 'oddListRow';
		$BG_COLOR =  $odd_bg;
	} else {
		$ROW_COLOR = 'evenListRow';
		$BG_COLOR =  $even_bg;
	}
	$oddRow = !$oddRow;

	$xtpl->assign("ROW_COLOR", $ROW_COLOR);
	$xtpl->assign("BG_COLOR", $BG_COLOR);
	$xtpl->parse('main.row');

	if ($item['is_assembly']) {
		$asset->load_relationship('assets');
		$j = 0;
		foreach ($asset->assets->getBeans(new Asset) as $row) {
			$part = array(
				'num' => $j,
				'id' => $row->id,
				'name' => $row->name,
			);
			$j++;
			$xtpl->assign('part', $part);
			$xtpl->parse('main.part');
		}
	}
	$i++;
	$should_convert = true;
}

$types = get_contract_types_list();
$xtpl->assign('CONTRACT_TYPE_OPTIONS', get_select_options_with_id($types,''));


	$popup_request_data = array(
		'call_back_function' => 'set_return',
		'form_name' => 'ConvertForm',
		'field_to_name_array' => array(
			'id' => 'service_subcontract_id',
			'name' => 'subcontract_name',
			),
		);
	require_once 'include/JSON.php';
	$json = new JSON(JSON_LOOSE_TYPE);
	$encoded_popup_request_data = $json->encode($popup_request_data);
	$xtpl->assign('subcontract_popup_request_data', $encoded_popup_request_data);

	$query = "SELECT service_maincontracts.id FROM service_maincontracts WHERE account_id = '{$focus->account_id}' AND deleted = 0 LIMIT 1";
	$res = $focus->db->query($query);
	$has_contract = (bool)($focus->db->fetchByAssoc($res));

	if ($has_contract) {
		$xtpl->parse('main.select_contract');
	}
	if (!empty($types)) {
		$xtpl->parse('main.create_contract');
	}
	require_once 'include/javascript/javascript.php';
	$javascript = new javascript;
	$javascript->setFormName('ConvertForm');
	$javascript->setSugarBean($focus);

	$xtpl->assign('JAVASCRIPT', $javascript->getScript());
	$xtpl->assign('TITLE1',  get_form_header ($mod_strings['LBL_POPUP_TITLE1'], '', false));
	$xtpl->assign('TITLE2',  get_form_header ($mod_strings['LBL_POPUP_TITLE2'], '', false));

insert_popup_header($theme);

if ($should_convert) {
	$xtpl->parse('main');
	$xtpl->out('main');
} else {
	$xtpl->parse('no_products');
	$xtpl->out('no_products');
}
echo get_form_footer();
echo insert_popup_footer();


