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

global $theme, $mod_strings, $app_list_strings, $app_strings;
require_once('themes/'.$theme.'/layout_utils.php');
require_once 'XTemplate/xtpl.php';

$xtpl = new XTemplate('modules/Assemblies/EditProductDiscount.html');

$xtpl->assign('MOD', $mod_strings);
$xtpl->assign('APP', $app_strings);

$discount_type = empty($_REQUEST['discount_id']) ? $_REQUEST['discount_type'] : 'std';
$xtpl->assign('TYPE_OPTIONS', get_select_options_with_id($app_list_strings['assembly_discount_type_dom'], $discount_type));
$xtpl->assign('AMOUNT', $_REQUEST['amount']);
$xtpl->assign('RATE', $_REQUEST['rate']);
$xtpl->assign('DISCOUNT_ID', $_REQUEST['discount_id']);
$xtpl->assign('DISCOUNT_NAME', $_REQUEST['discount_name']);
$xtpl->assign('ID', $_REQUEST['record']);
$xtpl->assign('TARGET_MODULE', $_REQUEST['target_module']);
$xtpl->assign('START', $_REQUEST['start']);


$popup_request_data = array(
	'call_back_function' => 'set_discount_return',
	'form_name' => 'EditView',
	'field_to_name_array' => array(
		'id' => 'discount_id',
		'name' => 'discount_name',
		'fixed_amount_usdollar' => 'amount',
		'raw_type' => 'type',
		'raw_rate' => 'rate',
		),
	);
$json = getJSONobj();
$encoded_popup_request_data = $json->encode($popup_request_data);
$xtpl->assign('popup_request_data', $encoded_popup_request_data);

insert_popup_header($theme);

$xtpl->parse('main');
$xtpl->out('main');


echo get_form_footer();
echo insert_popup_footer();





