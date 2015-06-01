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


class QuoteFormBase {
	var $log;
	var $db;
	
	var $pricing_method_map = array(
		'Inherit' => '', // 'inherit',
		'None' => '',
		'Margin' => 'margin',
		'Markup' => 'markup',
		'SameAsList' => 'list',
		'PercentDiscount' => 'discount',
		'StdPercentDiscount' => 'stddiscount',
		'StdFixedDiscount' => 'stddiscount',
	);
    
	function QuoteFormBase()
	{
		global $db;
		$this->db =& $db;
	}
	

	function BuildLineItemsList(&$focus)
	{

	}
	
	function BuildSoapItemsList(&$focus)
	{
		global $app_list_strings, $current_language;
		$mod_strings = return_module_language($current_language, 'Quotes');

		$ret = array(
			'groups' => array(),
			'extra' => array(),
		);

		$all_shipping_taxed = -1;
		//
		require_once 'include/Tally/TallyUpdate.php';
		require_once 'include/database/ListQuery.php';
		$result = ListQuery::quick_fetch($focus->object_name, $focus->id);
		$tally = new TallyUpdate($result);
		$tally->loadCurrency();
		$groups = $tally->getGroups();
		
		$currency = AppConfig::db_object('Currency', $focus->currency_id);
		$symbol = $currency->symbol;
		//
		$shipping_provider = AppConfig::db_object('ShippingProvider', $focus->shipping_provider_id);
		$shipper = $shipping_provider->name;
		unset($shipping_provider);
		//

		foreach ($groups as $key => $grp) {
			$tally->addBaseAmounts($grp, $tally->group_model, array('total', 'subtotal'));
			$group_stage = '&nbsp;';
			if($focus->object_name == 'Quote') {
				$group_stage = $mod_strings['LBL_GROUP_STAGE'].'&nbsp;<b>'.array_get_default($app_list_strings['quote_stage_dom'], $grp['status'], '').'</b>';
			}

			$group = array(
				'lines' => array(),
				'extra' => array(),
			);
			$lines =& $group['lines'];
			$extra =& $group['extra'];


			$taxes = array();
			$discounts = array();
			$row_shipping = '';
			$row_shipping_taxed = false;
			

			if(! empty($grp['adjusts'])) {
				foreach($grp['adjusts'] as $idx => $adj) {
					$tally->addBaseAmounts($adj, $tally->adj_model);
					$adj['desc'] = $adj['name'] . ' (' . format_number($adj['rate'], -1) . '%)';
					$adj['amount'] = currency_format_number($adj['amount'], $format_params);
					if($adj['type'] == 'StandardDiscount')
						$discounts[] = $adj;
					else if($adj['type'] == 'StandardTax' || $adj['type'] == 'CompoundedTax')
						$taxes[] = $adj;
					else if($adj['type'] == 'TaxedShipping' || $adj['type'] == 'UntaxedShipping') {
						$row_shipping = $adj['amount_usd'];
						$row_shipping_taxed = $adj['type'] == 'TaxedShipping';
					}
				}
			}

			if(! empty($grp['lines'])) {
				$top_index = 1;
				$next_line = array();
				$idx = '';
				foreach(array_keys($grp['lines']) as $nidx) {
					$next_line[$idx] = $nidx; $next_line[$nidx] = ''; $idx = $nidx;
				}
				foreach ($grp['lines'] as $idx => $line) {
					$tally->addBaseAmounts($line, $tally->line_model);
					$newline = array();
					if($line['depth']) {
						if(empty($line['is_comment']))
							$part_index ++;
						$show_top_index = '&nbsp;';
						$next = array_get_default($grp['lines'], $next_line[$idx]);
					} else {
						$show_top_index = $top_index;
						$part_index = '';
					}
					
					if(! empty($line['is_comment'])) {
						$newline['name'] = nl2br($line['body']);
					} else {
						if($focus->object_name != 'PurchaseOrder' && $focus->object_name != 'Bill') {
							$newline['cost_price'] = currency_format_number(array_get_default($line, 'cost_price_usd', $line['cost_price']), $format_params);
							$newline['list_price'] = currency_format_number(array_get_default($line, 'list_price_usd', $line['list_price']), $format_params);
						}
						$newline['unit_price'] = currency_format_number(array_get_default($line, 'unit_price_usd', $line['unit_price']), $format_params);
		
						$name = $line['name'];
						if($line['sum_of_components']) {
							$name = "<b>$name</b>";
						}
						$newline['name'] = $name;
						$newline['quantity'] = format_number($line['quantity'], -1);
						if(! $line['depth'])
							$top_index ++;
					}
					$lines[] = $newline;
				}
			}

			if(! $row_shipping_taxed)
				$all_shipping_taxed = 0;
			else if($all_shipping_taxed < 0)
				$all_shipping_taxed = 1;
					
			if(count($groups) > 1) {
				// only show group totals if more than one group exists
				foreach(array('subtotal', 'total') as $f) {
					$f2 = "row_$f";
					$f = $f . '_usd';
					$$f2 = currency_format_number($grp[$f], $format_params);
				}
				$extra[] = array(
					'name' => $mod_strings['LBL_SUBTOTAL'],
					'value' => $row_subtotal,
				);
				foreach($discounts as $d) {
					$extra[] = array(
						'name' => $d['desc'],
						'value' => '-' . currency_format_number($d['amount_usd'], $format_params),
					);
				}


				if($row_shipping != 0 && $row_shipping_taxed) {
					$extra[] = array(
						'name' => $mod_strings['LBL_SHIPPING'],
						'value' => $row_shipping,
					);
				}

				foreach($taxes as $t) {
					$extra[] = array(
						'name' => $t['desc'],
						'value' => $t['amount_usd'],
					);
				}
				
				if($row_shipping != 0 && !$row_shipping_taxed) {
					$extra[] = array(
						'name' => $mod_strings['LBL_SHIPPING'],
						'value' => $row_shipping,
					);
				}

				$extra[] = array(
					'name' => $mod_strings['LBL_TOTAL'],
					'value' => $row_total,
				);
			}

			$ret['groups'][] = $group;
		}

		$extra =& $ret['extra'];
		foreach(array('subtotal', 'total', 'tax', 'discount', 'shipping') as $f) {
			$f2 = "grand_$f";
			$$f2 = currency_format_number($currency->convertToDollar($tally->totals[$f]), $format_params);
			if(($f == 'tax' || $f == 'discount' | $f == 'shipping') && empty($tally->totals[$f]))
				 $$f2 = '';
		}
		
		$extra[] = array(
			'name' => $mod_strings['LBL_SUBTOTAL'],
			'value' => $grand_subtotal,
		);

		$grand_shipping_str = '';

		if(count($groups) > 1) {
			if($grand_discount) {
				$extra[] = array(
					'name' => $mod_strings['LBL_DISCOUNT'],
					'value' => -$grand_discount,
				);
			}
			if($all_shipping_taxed == 1) {
				if(($focus->shipping_provider_id && $focus->shipping_provider_id != '-99') || $tally->totals['shipping']) {
					if ($grand_shipping) {
						$shipping_label = $mod_strings['LBL_SHIPPING'];
						if (!empty($shipper)) $shipping_label .= ' (' . $shipper . ')';
						$extra[] = array(
							'name' => $shipping_label,
							'value' => $grand_shipping,
						);
					}
				}
			}
			if($grand_tax) {
				$extra[] = array(
					'name' => $mod_strings['LBL_TAX'],
					'value' => $grand_tax,
				);
			}
		} else {
			if(!empty($discounts)) {
				foreach($discounts as $d) {
					$extra[] = array(
						'name' => $d['desc'],
						'value' => '-' . currency_format_number($d['amount_usd'], $format_params),
					);
				}
			}
			if($all_shipping_taxed == 1) {
				if ($grand_shipping != 0) {
					$shipping_label = $mod_strings['LBL_SHIPPING'];
					if (!empty($shipper)) $shipping_label .= ' (' . $shipper . ')';
					$extra[] = array(
						'name' => $shipping_label,
						'value' => $grand_shipping,
					);
				}
			}
			if(!empty($taxes)) {
				foreach($taxes as $t) {
					$extra[] = array(
						'name' => $t['desc'],
						'value' => currency_format_number($t['amount_usd'], $format_params),
					);
				}
			}
		}
		
		if($all_shipping_taxed != 1) {
			if ($grand_shipping) {
				$shipping_label = $mod_strings['LBL_SHIPPING'];
				if (!empty($shipper)) $shipping_label .= ' (' . $shipper . ')';
				$extra[] = array(
					'name' => $shipping_label,
					'value' => $grand_shipping,
				);
			}
		}

		
		$extra[] = array(
			'name' => $mod_strings['LBL_TOTAL'],
			'value' => $grand_total,
		);

		foreach ($ret['groups'] as $gid => $grp) {
			foreach ($grp['lines'] as $lid => $ln) {
				$line = array();
				foreach($ln as $name => $value) {
					$line[] = array(
						'name' => $name,
						'value' => $value,
					);
				}
				$ret['groups'][$gid]['lines'][$lid] = $line;
			}
		}
		return $ret;
	}
	

	function getForm($prefix, $mod='Quotes'){
        if (!ACLController::checkAccess('Quotes', 'edit', true)) {
            return '';
        }
		if(!empty($mod)){
			global $current_language;
			$mod_strings = return_module_language($current_language, $mod);
		}else global $mod_strings;
		global $app_strings;
		$lbl_save_button_title = $app_strings['LBL_SAVE_BUTTON_TITLE'];
		$lbl_save_button_key = $app_strings['LBL_SAVE_BUTTON_KEY'];
		$lbl_save_button_label = $app_strings['LBL_SAVE_BUTTON_LABEL'];


		$the_form = get_left_form_header($mod_strings['LBL_NEW_FORM_TITLE']);
		$the_form .= <<<EOQ
				<form name="{$prefix}EditView" onSubmit="return check_form('{$prefix}EditView')" method="POST" action="index.php">
					<input type="hidden" name="{$prefix}module" value="Quotes">
					<input type="hidden" name="${prefix}action" value="Save">
EOQ;
		$the_form .= $this->getFormBody($prefix, $mod, "{$prefix}EditView");
		$the_form .= <<<EOQ
				<input title="$lbl_save_button_title" accessKey="$lbl_save_button_key" class="button" type="submit" name="button" value="  $lbl_save_button_label  " >
				</form>

EOQ;
		$the_form .= get_left_form_footer();
		$the_form .= get_validate_record_js();

		return $the_form;
	}
	
	
	function default_shipping_taxed_js() {
		if(AppConfig::setting('company.tax_shipping'))
			return "default_shipping_taxed = true;\n";
		return '';
	}
	
	

// create quote main // vlozeni jednoduche quote
function getFormBody($prefix, $mod='Quotes', $formname=''){
    if (!ACLController::checkAccess('Quotes', 'edit', true)) {
        return '';
    }
if(!empty($mod)){
	global $current_language;
	$mod_strings = return_module_language($current_language, $mod);
}else global $mod_strings;
global $app_strings;
global $app_list_strings;
global $theme;
global $current_user;

$lbl_required_symbol = $app_strings['LBL_REQUIRED_SYMBOL'];
$lbl_quote_subject = $mod_strings['LBL_QUOTE_SUBJECT'];
$lbl_quote_stage = $mod_strings['LBL_QUOTE_STAGE'];
$lbl_terms = $mod_strings['LBL_TERMS'];

$user_id = $current_user->id;

require_once('include/TimeDate.php');
$timedate = new TimeDate();
$ntc_date_format = $timedate->get_user_date_format();
$cal_dateformat = $timedate->get_cal_date_format();

	
// Set up account popup
$popup_request_data = array(
	'call_back_function' => 'set_return',
	'form_name' => $formname,
	'field_to_name_array' => array(
		'id' => 'account_id',
		'name' => 'account_name',
		),
	);
$json = getJSONobj();
$encoded_popup_request_data = $json->encode($popup_request_data);


// Unimplemented until jscalendar language files are fixed
// $cal_lang = (empty($cal_codes[$current_language])) ? $cal_codes[$default_language] : $cal_codes[$current_language];
$cal_lang = "en";


$the_form = <<<EOQ
<p>
		<input type="hidden" name="{$prefix}record" value="">
		<input type="hidden" name="{$prefix}assigned_user_id" value='${user_id}'>

		$lbl_quote_subject&nbsp;<span class="required">$lbl_required_symbol</span><br>
		<span><input name='{$prefix}name' type="text" value=""></span><br>
EOQ;
$disabled = '';
if (ACLController::moduleSupportsACL('Accounts')  && !ACLController::checkAccess('Accounts', 'list', true)) {
    $disabled = ' disabled = "disabled" ';
}

$the_form .= <<<EOQ
		${mod_strings['LBL_ACCOUNT_NAME']}&nbsp;<br>
        <span><input name='account_name' type='text' $disabled value="" size="16" id="account_name" class="sqsEnabled" autocomplete="off"></span><br>
<input name='account_id' type="hidden" value='' id="account_id">&nbsp;<input  title="{$app_strings['LBL_SELECT_BUTTON_TITLE']}" accessKey="{$app_strings['LBL_SELECT_BUTTON_KEY']}" type="button" class="button" value='{$app_strings['LBL_SELECT_BUTTON_LABEL']}'
			$disabled onclick='open_popup("Accounts", 600, 400, "", true, false, {$encoded_popup_request_data});'><br>
EOQ;
/*$the_form .= <<<EOQ
		${mod_strings['LBL_OPPORTUNITY_NAME']}<br>
		<input name='opportunity_name' type='text' readonly value="" size="16"><input name='opportunity_id' type="hidden" value=''>&nbsp;<input  title="{$app_strings['LBL_SELECT_BUTTON_TITLE']}" accessKey="{$app_strings['LBL_SELECT_BUTTON_KEY']}" type="button" class="button" value='{$app_strings['LBL_SELECT_BUTTON_LABEL']}' name=btn1 LANGUAGE=javascript
			onclick='return window.open("index.php?module=Opportunities&action=Popup&html=Popup_picker&form=$formname&form_submit=false","","width=600,height=400,resizable=1,scrollbars=1");'><br>
EOQ;*/
$the_form .= <<<EOQ
		$lbl_quote_stage&nbsp;<span class="required">$lbl_required_symbol</span><br>
		<select name='{$prefix}quote_stage'>
EOQ;
$the_form .= get_select_options_with_id($app_list_strings['quote_stage_dom'], "");
$the_form .= <<<EOQ
		</select><br>
EOQ;

$the_form .= <<<EOQ
		$lbl_terms&nbsp;<span class="required">$lbl_required_symbol</span><br>
		<select name='{$prefix}terms'>
EOQ;
$the_form .= get_select_options_with_id($app_list_strings['terms_dom'], "");
$the_form .= <<<EOQ
		</select><br>
EOQ;

$the_form .= <<<EOQ
		${mod_strings['LBL_VALID_UNTIL']}&nbsp;<span class="required">$lbl_required_symbol</span><br>
		<span class="dateFormat">$ntc_date_format</span><br>
		<input name='{$prefix}valid_until' size='12' maxlength='10' id='{$prefix}jscal_field' type="text" value=""> <img src="themes/$theme/images/jscalendar.gif" alt="{$app_strings['LBL_ENTER_DATE']}"  id="jscal_trigger" align="absmiddle"><br>
EOQ;
$the_form .= <<<EOQ
		</p>

		<script type="text/javascript">
			Calendar.setup ({
				inputField : "{$prefix}jscal_field", ifFormat : "$cal_dateformat", showsTime : false, button : "jscal_trigger", singleClick : true, step : 1
			});
		</script>
EOQ;

require_once('include/QuickSearchDefaults.php');
$qsd = new QuickSearchDefaults();

$qsAccount = array( 
    'method' => 'query',
    'modules' => array('Accounts'), 
	'group' => 'or', 
	'field_list' => array('name', 'id', ), 
    'populate_list' => array('account_name', 'account_id'), 
	'conditions' => array(array('name'=>'name','op'=>'like_custom','end'=>'%','value'=>'')), 
	'order' => 'name', 
	'limit' => '30',
	'no_match_text' => $app_strings['ERR_SQS_NO_MATCH']
);
$sqs_objects = array(
    'account_name' => $qsAccount,
);
$quicksearch_js = $qsd->getQSScripts();
$quicksearch_js .= '<script type="text/javascript" language="javascript">sqs_objects = ' . $json->encode($sqs_objects) . '</script>';
$the_form .= $quicksearch_js;

require_once('include/javascript/javascript.php');
require_once('modules/Quotes/Quote.php');
$javascript = new javascript();
$javascript->setFormName($formname);
$javascript->setSugarBean(new Quote());
$javascript->addRequiredFields($prefix);

$javascript->addToValidateBinaryDependency('account_name', 'alpha', $app_strings['ERR_SQS_NO_MATCH_FIELD'] . $mod_strings['LBL_ACCOUNT_NAME'], 'false', '', 'account_id');
$the_form .= $javascript->getScript();

/*
$the_form.= <<<SCRIPT
<script type="text/javascript">
addToValidate('{$prefix}EditView', '{$prefix}account_name', 'alpha', true, '{$mod_strings['LBL_ACCOUNT_NAME']}' );
</script>
SCRIPT;
*/

return $the_form;

}


function get_copy_address_js()
{
	$script = <<<EOQ
	<script type="text/javascript" language="JavaScript">
	function copyAddressRight(form) {
		form.shipping_address_street.value = form.billing_address_street.value;
		form.shipping_address_city.value = form.billing_address_city.value;
		form.shipping_address_state.value = form.billing_address_state.value;
		form.shipping_address_postalcode.value = form.billing_address_postalcode.value;
		form.shipping_address_country.value = form.billing_address_country.value;
		return true;
	}
	function copyAddressLeft(form) {
		form.billing_address_street.value =	form.shipping_address_street.value;
		form.billing_address_city.value = form.shipping_address_city.value;
		form.billing_address_state.value = form.shipping_address_state.value;
		form.billing_address_postalcode.value =	form.shipping_address_postalcode.value;
		form.billing_address_country.value = form.shipping_address_country.value;
		return true;
	}	
	</script>
EOQ;
	return $script;
}

function getConversionProductList(&$focus)
{
	global $theme, $odd_bg, $even_bg, $app_strings, $mod_strings;
	$image_path = 'themes/' . $theme . '/images/';
	require_once 'XTemplate/xtpl.php';
	require_once('modules/Currencies/Currency.php');
	require_once('modules/ContractTypes/ContractType.php');
	$list = array();
	$groups =& $focus->get_line_groups();
	if(!is_array($groups)) $groups = array();
	$lastnested = false;
	$i = $j = 0;
	$oddRow = false;
	foreach($groups as $group) {
		if (!empty($group->lines)) foreach ($group->lines as $row) {
			if(empty($row['related_type'])) {
				continue;
			}
			$item = array(
				'num' => $i,
				'depth' => $row['depth'],
				'id' => array_get_default($row, 'related_id', ''),
				'related_type' => $row['related_type'],
				'tax_code_id' => array_get_default($row, 'tax_class_id', '-99'),
				'name' => $row['name'],
				'cost_price' => $row['cost_price'],
				'list_price' => $row['list_price'],
				'purchase_price' => $row['unit_price'],
				'quantity' => $row['quantity'],
			);
			if($row['depth']) {
				if(! $lastnested)
					$j = 0;
				else
					$j ++;
				$item['num'] = $j;
				$lastnested = true;
			}
			else {
				$lastnested = false;
				$i ++;
			}
			$list[] = $item;
		}
	}

	$query = "SELECT service_maincontracts.id FROM service_maincontracts WHERE account_id = '{$focus->billing_account_id}' AND deleted = 0 LIMIT 1";
	$res = $focus->db->query($query);
	$has_contract = (bool)($focus->db->fetchByAssoc($res));

	$currency_fields = array('cost_price', 'list_price', 'purchase_price',);
	$xtpl = new XTemplate('modules/Quotes/ConversionProductList.html');
	$currency = AppConfig::db_object('Currency', $focus->currency_id);
	$symbol = $currency->symbol;
    $xtpl->assign('APP',  $app_strings);
    $xtpl->assign('MOD',  $mod_strings);
    $xtpl->assign('SYMBOL',  $symbol);
	$xtpl->assign('CURRENCY_ID',  $focus->currency_id);
	$xtpl->assign('EXCHANGE_RATE',  $focus->exchange_rate);
	$xtpl->assign($focus->module_dir == 'Quotes' ? 'QUOTE_ID' : 'INVOICE_ID',  $focus->id);
    $xtpl->assign('PRODUCT_ICON',  get_image($image_path . 'ProductCatalog',' border="0" valign="middle" '));
	foreach ($list as $item) {
		$item['icon'] = get_image($image_path . ($item['related_type']),' border="0" valign="middle" ');
		foreach ($currency_fields as $f) $item[$f . '_f'] =  currency_format_number($item[$f], array('currency_symbol' => false));
		if(! $item['depth'])
			$oddRow = !$oddRow;
		if($oddRow) {
			$ROW_COLOR = 'oddListRow';
			$BG_COLOR =  $odd_bg;
		} else {
			$ROW_COLOR = 'evenListRow';
			$BG_COLOR =  $even_bg;
		}
		$xtpl->assign("ROW_COLOR", $ROW_COLOR);
		$xtpl->assign("BG_COLOR", $BG_COLOR);
		
		if(! $item['depth']) {
			$xtpl->assign('item', $item);
			$xtpl->parse('main.row.line');
		}
		else {
			$xtpl->assign('part', $item);
			$xtpl->parse('main.row.part');
		}
		$xtpl->parse('main.row');
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
	$popup_request_data = array(
		'call_back_function' => 'set_return',
		'form_name' => 'ConvertForm',
		'field_to_name_array' => array(
			'id' => 'project_id',
			'name' => 'project_name',
			),
		);
	$encoded_popup_request_data = $json->encode($popup_request_data);
	$xtpl->assign('project_popup_request_data', $encoded_popup_request_data);
	$xtpl->assign('ACCOUNT_NAME', $focus->billing_account_name);
	$xtpl->assign('ACCOUNT_ID', $focus->billing_account_id);
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
	
	$xtpl->parse('main');
	return $xtpl->text('main');
}

}
?>
