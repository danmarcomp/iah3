<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/*********************************************************************************
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
 ********************************************************************************/

 class ListCurrency{
	var $focus = null;
	var $list = null;
	//var $javascript = '<script type="text/javascript">';
    var $javascript = '';

	function lookupCurrencies(){

		require_once('modules/Currencies/Currency.php');
		$this->focus = new Currency();
		$this->list = $this->focus->get_full_list('name');
		$this->focus->retrieve('-99');
	  	if(is_array($this->list)){
		$this->list = array_merge(Array($this->focus), $this->list);
	  	}else{
	  		$this->list = Array($this->focus);
	  	}

	}
	function handleAdd(){
			global $current_user;
			if(isset($_REQUEST['record']) && $_REQUEST['record'] == '-99')
				return $this->handleUpdateDefault();
			if($current_user->is_admin){
			if(isset($_POST['edit']) && $_POST['edit'] == 'true' && isset($_POST['name']) && !empty($_POST['name']) && isset($_POST['conversion_rate']) && !empty($_POST['conversion_rate']) && isset($_POST['symbol']) && !empty($_POST['symbol'])){
				require_once('modules/Currencies/Currency.php');
				$currency = new Currency();
				if(isset($_POST['record']) && !empty($_POST['record'])){

					$currency->retrieve($_POST['record']);
				}
				$currency->name = $_POST['name'];
				$currency->status = $_POST['status'];
				$currency->symbol = $_POST['symbol'];
				$currency->iso4217 = $_POST['iso4217'];
				$currency->conversion_rate = unformat_number($_POST['conversion_rate']);
				$currency->decimal_places = $_POST['decimal_places'];
				$currency->symbol_place_after = array_get_default($_POST, 'symbol_place_after', 0);
				$currency->save();
				$this->focus = $currency;
			}
			}

	}

	function handleUpdateDefault() {
		global $current_user;
		if(!$current_user->is_admin)
			return;
		require_once('modules/Configurator/Configurator.php');
		$cfg = new Configurator();
		$map = array(
			'name' => 'default_currency_name',
			'symbol' => 'default_currency_symbol',
			'iso4217' => 'default_currency_iso4217',
			'decimal_places' => 'default_currency_significant_digits',
			'symbol_place_after' => 'default_currency_symbol_place_after',
		);
		foreach($map as $input => $field) {
			$fv = array_get_default($_REQUEST, $input);
			if(! isset($fv) || strlen($fv) <= 0)
				return;
			$cfg->config[$field] = $fv;
		}
		$cfg->handleOverride();
	}

	function handleUpdate(){
		global $current_user;
			if($current_user->is_admin){
				if(isset($_POST['id']) && !empty($_POST['id'])&&isset($_POST['name']) && !empty($_POST['name']) && isset($_POST['rate']) && !empty($_POST['rate']) && isset($_POST['symbol']) && !empty($_POST['symbol'])){
			$ids = $_POST['id'];
			$names= $_POST['name'];
			$symbols= $_POST['symbol'];
			$rates  = $_POST['rate'];
			$isos  = $_POST['iso'];
			$size = sizeof($ids);
			if($size != sizeof($names)|| $size != sizeof($isos) || $size != sizeof($symbols) || $size != sizeof($rates)){
				return;
			}
			require_once('modules/Currencies/Currency.php');
				$temp = new Currency();
			for($i = 0; $i < $size; $i++){
				$temp->id = $ids[$i];
				$temp->name = $names[$i];
				$temp->symbol = $symbols[$i];
				$temp->iso4217 = $isos[$i];
				$temp->conversion_rate = $rates[$i];
				$temp->save();
			}
	}}
	}

	function getJavascript(){
		// wp: DO NOT add formatting and unformatting numbers in here, add them prior to calling these to avoid double calling
		// of unformat number

		// longreach - modified - postpone return
		$js = $this->javascript . <<<EOQ
					function get_rate(id){
						return ConversionRates[id];
					}
					function ConvertToDollar(amount, rate){
						return amount / rate;
					}
					function ConvertFromDollar(amount, rate){
						return amount * rate;
					}
					function ConvertRate(id,fields){
							for(var i = 0; i < fields.length; i++){
								fields[i].value = toDecimal(ConvertFromDollar(ConvertToDollar(fields[i].value, lastRate), ConversionRates[id]));
							}
							lastRate = ConversionRates[id];
						}
					function ConvertRateSingle(id,field){
						var temp = field.innerHTML.substring(1, field.innerHTML.length);
						unformattedNumber = unformatNumber(temp, num_grp_sep, dec_sep);
						var obj = CurrencyData[id];
						var symbol = obj ? obj.symbol : '';
						field.innerHTML = symbol + formatNumber(toDecimal(ConvertFromDollar(ConvertToDollar(unformattedNumber, lastRate), ConversionRates[id])), num_grp_sep, dec_sep, 2, 2);
						lastRate = ConversionRates[id];
					}
					function GetCurrencyData(id, field, fallback) {
						if(! id) id = '-99';
						var obj = CurrencyData[id];
						if(obj) {
							return field ? obj[field] : obj;
						}
						return fallback;
					}
					function CurrencyDecimalPlaces(id) {
						return GetCurrencyData(id, 'decimals', currency_significant_digits);
					}
EOQ;

		// longreach - start added
		global $theme, $app_strings;
		if(!empty($GLOBALS['image_path']))
			$image_path = $GLOBALS['image_path'];
		else
			$image_path = "themes/$theme/images/";
		$defaults = array(
			'image_path' => $image_path,
			'img_standard_src' => "exchange_rate.gif",
			'img_custom_src' => "exchange_rate_custom.gif",
			'img_clear_src' => "delete_inline.gif",
			'clear_alt_text' => $app_strings['LBL_CLEAR_EXCHANGE_RATE_ALT'],
			'editor_title' => $app_strings['LBL_EDIT_EXCHANGE_RATE_TITLE'],
			'source_alt_text' => $app_strings['LBL_EDIT_EXCHANGE_RATE_ALT'],
			'close_text' => "<img border=\"0\" src=\"{$image_path}close_inline.gif\">",
			'close_title' => $app_strings['LBL_ADDITIONAL_DETAILS_CLOSE_TITLE'],
			'lbl_standard_rate' => $app_strings['LBL_STANDARD_RATE'],
			'lbl_custom_rate' => $app_strings['LBL_CUSTOM_RATE'],
			'lbl_reset' => $app_strings['LBL_RESET_BUTTON_LABEL'],
			'lbl_commit' => $app_strings['LBL_COMMIT_BUTTON_LABEL'],
			'lbl_link_updates' => $app_strings['LBL_LINK_UPDATES'],
		);

		$json = getJSONobj();
		$defaults = $json->encode($defaults);

		global $pageInstance;
        $pageInstance->add_js_include('modules/Currencies/exchange_rate.js', null, LOAD_PRIORITY_FOOT);
        $pageInstance->add_js_literal("ExchangeRateEditor.config($defaults);", null, LOAD_PRIORITY_FOOT);
		$pageInstance->add_html_literal('<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>');

        return $js;
		// longreach - end added

	}
     
	function addJavascript() {
		$GLOBALS['pageInstance']->add_js_literal($this->getJavascript(), null, LOAD_PRIORITY_HEAD);
	}


	// longreach - start added
	function addExchangeRateEditor($bean, $amount_fields, $params=array(), $js_params=array()) {
		global $locale;
		if (!isset($js_params['decimals'])) {
			$decimals = $locale->getPrecedentPreference('default_currency_significant_digits');
			$js_params['decimals'] = $decimals;
		}
		$html = '';
		//--
		$form_name = 'EditView';
		if(! empty($params['form_name']))
			$form_name = $params['form_name'];
		//--
		$rate_name = 'exchange_rate';
		if(! empty($params['rate_field']))
			$rate_name = $params['rate_field'];
		$rate = $bean->$rate_name;
		//--
		$currency_name = 'currency_id';
		if(! empty($params['currency_field']))
			$currency_name = $params['currency_field'];
		$currency_id = $bean->$currency_name;
		//--
		if(empty($params['omit_rate_fields'])) {
			$override = empty($rate) ? '0' : '1';
			$html .= '<input type="hidden" name="override_exchange_rate['.htmlentities($rate_name).']" value="'.$override.'">';
			$html .= '<input type="hidden" name="'.htmlentities($rate_name).'" value="'.htmlentities($rate).'">';
		}
		$src = htmlentities($rate_name).'_src';
		$html .= '<span class="exchangeRateSource" id="'.$src.'"></span>';
		$json = getJSONobj();
		$set_js_params = array(
			'form_name' => $form_name,
			'rate_field_name' => $rate_name,
			'currency_field_name' => $currency_name,
			'amount_field_info' => $amount_fields,
			'source_id' => $src,
		);
		if($js_params)
			$set_js_params = array_merge($set_js_params, $js_params);
		$js_params = $json->encode($set_js_params);
		$currency_id = addcslashes($currency_id, "'");
		$rate = addcslashes($rate, "'");
		$GLOBALS['pageInstance']->add_js_literal("ExchangeRateEditor.setup($js_params);", null, LOAD_PRIORITY_BODY);
		return $html;
	}
	// used as an alternative to the exchange rate editor (call before getSelectOptions):
	function set_custom_rate($rate, $formatted=true) {
		if($formatted) $rate = unformat_number($rate);
		$this->custom_rate = $rate;
	}
	// longreach - end added
	function displayRateEditors() {
		$GLOBALS['pageInstance']->add_js_literal('ExchangeRateEditor.display_sources();', null, LOAD_PRIORITY_FOOT);
	}

	function getSelectOptions($id = ''){
		global $current_user;
		$json = getJSONobj();
		$this->javascript .="var ConversionRates = {};\n";
		$this->javascript .="var CurrencyData = {};\n";
		$options = '';
		$this->lookupCurrencies();
		$setLastRate = false;
		if(isset($this->list ) && !empty($this->list )){
		$miscData = array();
		foreach ($this->list as $data){
			if($data->status == 'Active'){
			if($id == $data->id){
			$options .= '<option value="'. $data->id . '" selected>';
			$setLastRate = true;
			$this->javascript .= 'var lastRate = "' . $data->conversion_rate . '";';

			}else{
				$options .= '<option value="'. $data->id . '">'	;
			}
			$options .= $data->name . ' : ' . $data->symbol;
			$this->javascript .=" ConversionRates['".$data->id."'] = '".$data->conversion_rate."';\n";
			$miscData[$data->id] = array(
				'name' => $data->name,
				'symbol' => $data->symbol,
				'iso' => $data->iso4217,
				'decimals' => (int)$data->decimal_places,
				'symbol_after' => $data->symbol_place_after,
			);
			}
		}
		$miscData = $json->encode($miscData);
		$this->javascript .=" CurrencyData = $miscData;\n";
		if(!$setLastRate){
			$this->javascript .= 'var lastRate = "1";';
		}

		// longreach - start added
		if(! empty($this->custom_rate))
			$this->javascript .= 'var lastRate = "' + $this->custom_rate + '";';
		// longreach - start added

	}
	return $options;
	}
	function getTable(){
		$this->lookupCurrencies();
		$usdollar = translate('LBL_US_DOLLAR');
		$currency = translate('LBL_CURRENCY');
		$currency_sym = AppConfig::setting('locale.base_currency.symbol');
		$conv_rate = translate('LBL_CONVERSION_RATE');
		$add = translate('LBL_ADD');
		$delete = translate('LBL_DELETE');
		$update = translate('LBL_UPDATE');

		$form = $html = "<br><table cellpadding='0' cellspacing='0' border='0'  class='tabForm'><tr><td><tableborder='0' cellspacing='0' cellpadding='0'>";
		$form .= <<<EOQ
					<form name='DeleteCurrency' action='index.php' method='post'><input type='hidden' name='action' value='{$_REQUEST['action']}'>
					<input type='hidden' name='module' value='{$_REQUEST['module']}'><input type='hidden' name='deleteCur' value=''></form>

					<tr><td><B>$currency</B></td><td><B>ISO 4217</B>&nbsp;</td><td><B>$currency_sym</B></td><td colspan='2'><B>$conv_rate</B></td></tr>
					<tr><td>$usdollar</td><td>USD</td><td>$</td><td colspan='2'>1</td></tr>
					<form name="UpdateCurrency" action="index.php" method="post"><input type='hidden' name='action' value='{$_REQUEST['action']}'>
					<input type='hidden' name='module' value='{$_REQUEST['module']}'>
EOQ;
		if(isset($this->list ) && !empty($this->list )){
		foreach ($this->list as $data){

			$form .= '<tr><td><input type="hidden" name="id[]" value="'.$data->id.'">'.$data->name. '<input type="hidden" name="name[]" value="'.$data->name.'"></td><td>'.$data->iso4217. '<input type="hidden" name="iso[]" value="'.$data->iso4217.'"></td><td>'.$data->symbol. '<input type="hidden" name="symbol[]" value="'.$data->symbol.'"></td><td>'.$data->conversion_rate.'&nbsp;</td><td><input type="text" name="rate[]" value="'.$data->conversion_rate.'"><td>&nbsp;<input type="button" name="delete" class="button" value="'.$delete.'" onclick="document.forms[\'DeleteCurrency\'].deleteCur.value=\''.$data->id.'\';document.forms[\'DeleteCurrency\'].submit();"> </td></tr>';
		}
		}
		$form .= <<<EOQ
					<tr><td></td><td></td><td></td><td></td><td></td><td>&nbsp;<input type='submit' name='Update' value='$update' class='button'></TD></form> </td></tr>
					<tr><td colspan='3'><br></td></tr>
					<form name="AddCurrency" action="index.php" method="post">
					<input type='hidden' name='action' value='{$_REQUEST['action']}'>
					<input type='hidden' name='module' value='{$_REQUEST['module']}'>
					<tr><td><input type = 'text' name='addname' value=''>&nbsp;</td><td><input type = 'text' name='addiso' size='3' maxlength='3' value=''>&nbsp;</td><td><input type = 'text' name='addsymbol' value=''></td><td colspan='2'>&nbsp;<input type ='text' name='addrate'></td><td>&nbsp;<input type='submit' name='Add' value='$add' class='button'></td></tr>
					</form></table></td></tr></table>
EOQ;
	return $form;

	}


	// longreach - start added
	function getCurrenciesNames()
	{
		$result = array();
		$this->lookupCurrencies();
		if(isset($this->list ) && !empty($this->list ))
			foreach ($this->list as $data)
				if($data->status == 'Active')
					$result[$data->id] = $data->name . ' : ' . $data->symbol;

		return $result;
	}
	// longreach - end added

}

//$lc = new ListCurrency();
//$lc->handleDelete();
//$lc->handleAdd();
//$lc->handleUpdate();
//echo '<select>'. $lc->getSelectOptions() . '</select>';
//echo $lc->getTable();

?>
