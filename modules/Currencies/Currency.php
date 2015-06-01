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
/*********************************************************************************

 ********************************************************************************/



require_once('data/SugarBean.php');

// Contact is used to store customer information.
class Currency extends SugarBean
{
	// Stored fields
	var $id;
	var $iso4217;
	var $name;
	var $status;
	var $conversion_rate;
	var $deleted;
	var $date_entered;
	var $date_modified;
	var $symbol;
	var $symbol_place_after;
	var $decimal_places;
	var $hide = '';
	var $unhide = '';
	var $field_name_map;

	var $table_name = "currencies";
	var $object_name = "Currency";
    var $bean_name = "Currency";
	var $module_dir = "Currencies";
	var $new_schema = true;
	
	var $disable_num_format = true;
	
    function Currency()
	{
		parent::SugarBean();
	}


	function convertToDollar($amount, $precision = 6) {
        // jennyg - Bug 10298.
        // rounding numbers with a comma separator fails e.g. 9,500 rounds to 9, so we need to strip
        // out the number separator before rounding. 
        
        /* longreach - removed - suspect unnecessary in IAH
        $seps = get_number_seperators();
        $num_grp_sep = $seps[0];
        $amount = str_replace($num_grp_sep, "", $amount);
        */
        
		return round(($amount / $this->conversion_rate), $precision);
	}

    /**
     * convert amount from base("usdollar") to selected currency.
     */
	function convertFromDollar($amount, $precision = 6){
        // jennyg - Bug 10298.
        // rounding numbers with a comma separator fails e.g. 9,500 rounds to 9, so we need to strip
        // out the number separator before rounding. 
        
        /* longreach - removed - suspect unnecessary in IAH
        $seps = get_number_seperators();
        $num_grp_sep = $seps[0];
        $amount = str_replace($num_grp_sep, "", $amount);
        */
        
		return round(($amount * $this->conversion_rate), $precision);
	}

	function getDefaultCurrencyName(){
		return AppConfig::setting('locale.base_currency.name');
	}

	function getDefaultCurrencySymbol(){
		return AppConfig::setting('locale.base_currency.symbol');
	}

	function getDefaultISO4217(){
		return AppConfig::setting('locale.base_currency.iso4217');
	}
	
	function getDefaultDecimalPlaces(){
		return AppConfig::setting('locale.base_currency.significant_digits');
	}
	
	function getDefaultSymbolPlaceAfter(){
		return AppConfig::setting('locale.base_currency.symbol_place_after');
	}

	function retrieveIDBySymbol($symbol, $encode = true){
	 	$query = "select id from currencies where symbol='$symbol' and deleted=0;";
	 	$result = $this->db->query($query);
	 	if($result){
	 	$row = $this->db->fetchByAssoc($result);
	 	if($row){
	 		return $row['id'];
	 	}
	 	}
	 	return '';
	 }


	function retrieve_id_by_name($name) {
	 	$query = "select id from currencies where name='$name' and deleted=0;";
	 	$result = $this->db->query($query);
	 	if($result){
	 	$row = $this->db->fetchByAssoc($result);
	 	if($row){
	 		return $row['id'];
	 	}
	 	}
	 	return '';		
	}
	
	
	static function before_save(RowUpdate &$update) {
		if($update->getField('id') =='-99') {
			$fix = array(
				'conversion_rate' => 1.0,
				'status' => 'Active',
				'deleted' => 0,
			);
			$cfg_up = array();
			
			if(! ($val = $update->getField('name')) )
				$fix['name'] = self::getDefaultCurrencyName();
			else
				$cfg_up['locale.base_currency.name'] = $val;
			if(! ($val = $update->getField('symbol')) )
				$fix['symbol'] = self::getDefaultCurrencySymbol();
			else
				$cfg_up['locale.base_currency.symbol'] = $val;
			if(! ($val = $update->getField('iso4217')) )
				$fix['iso4217'] = self::getDefaultISO4217();
			else
				$cfg_up['locale.base_currency.iso4217'] = $val;
			if( ($val = $update->getField('decimal_places')) !== null && $val !== '')
				$cfg_up['locale.base_currency.significant_digits'] = $val;
			if( ($val = $update->getField('symbol_place_after')) !== null && $val !== '')
				$cfg_up['locale.base_currency.symbol_place_after'] = $val;
			
			$update->set($fix);
			if($cfg_up) {
				foreach($cfg_up as $k => $v)
					AppConfig::set_local($k, $v);
				AppConfig::save_local();
			}
		}
	}
	
	
	static function get_default_list_row($id='-99') {
		$row = array(
			'id' => '-99',
			'name' => self::getDefaultCurrencyName(),
			'symbol' => self::getDefaultCurrencySymbol(),
			'conversion_rate' => 1,
			'iso4217' => self::getDefaultISO4217(),
			'status' => 'Active',
			'symbol_place_after' => self::getDefaultSymbolPlaceAfter(),
			'decimal_places' => self::getDefaultDecimalPlaces(),
			'deleted' => 0,
		);
		return $row;
	}
	
	function loadDefaultCurrency() {
		foreach(self::get_default_list_row() as $k => $v)
			$this->$k = $v;
		$this->hide = '<!--';
		$this->unhide = '-->';	
		global $current_user;
		if(! is_admin($current_user)) {
			$this->admin = '<!--';
			$this->end_admin = '-->';			
		}
	}
	
     function retrieve($id, $encode = true){
     	/*if(empty($id) || $id == '-99'){
     		$this->loadDefaultCurrency();
     		return $this;
     	}else{*/
     		$ret = parent::retrieve($id, $encode);
     	//}
     	if(!isset($this->name) || $this->deleted == 1){
     		$this->loadDefaultCurrency();
     	}
		return $ret;
     }
     
    /**
     * Method for returning the currency symbol, must return chr(2) for the € symbol
     * to display correctly in pdfs
     * Parameters:
     * 	none
     * Returns:
     * 	$symbol otherwise chr(2) for euro symbol
     */
     function getPdfCurrencySymbol() {
     	if($this->symbol == '&#8364;') 
     		return chr(2);
     // longreach - added - pound
     	if($this->symbol == '&#8356;')
            return chr(3);
     	return $this->symbol;
     }
     
     
     // longreach - added
     function getPdfFontMapping() {
		return array(2=>'Euro', 3=> 'sterling');
     }


	function save($check_notify=false) {
		$ret = parent::save($check_notify);
		return $ret;
	}
} // end currency class

/**
 * currency_format_number
 * 
 * This method is a wrapper designed exclusively for formatting currency values
 * with the assumption that the method caller wants a currency formatted value
 * matching his/her user preferences (if set) or the system configuration defaults
 * (if user preferences are not defined).
 * 
 * @param $amount The amount to be formatted
 * @param $params Optional parameters (see @format_number)
 * @return String representation of amount with formatting applied
 */
function currency_format_number($amount, $params = array(), $raw_amount=null) {
    global $current_user, $locale;
    static $c_decimals, $c_space;
	
	if(! isset($params['currency_symbol'])) {
	   $params["currency_symbol"] = true;
	}
	if(!isset($params['use_currency_decimals']) && ! isset($params['decimals'])) {
	   $params["use_currency_decimals"] = true;
	}
	if(! isset($c_space))
		$c_space = $locale->getLocaleSpaceSeparateCurrency();
	if(! isset($params['symbol_space']))
		$params['symbol_space'] = $c_space;
	
	if(! isset($params['currency_id'])) {
		$currency_id = $current_user->getPreference('currency');
		if(! $currency_id)
			$currency_id = '-99'; // use default if none set
		$params['currency_id'] = $currency_id;
	}
	if(isset($params['entered_currency_id'])) {
		$entered = $params['entered_currency_id'];
		if(! $entered) $entered = '-99';
		if($entered == $params['currency_id'] && isset($raw_amount)) { // no conversion necessary
			$params['convert'] = false;
			$amount = $raw_amount;
		}
		else if(isset($params['round']))
			unset($params['round']); // use automatic rounding
	}
	
	if(isset($params['decimals']))
    	$real_decimals = $params['decimals'];
    else {
    	if(! isset($c_decimals))
    		$c_decimals = $locale->getPrecedentPreference('default_currency_significant_digits');
    	$real_decimals = $c_decimals;
    }
    if(isset($params['round']))
    	$real_round = $params['round'];
    else
		$real_round = $real_decimals;
	$real_round = $real_round == '' ? 0 : $real_round;
	$real_decimals = $real_decimals == '' ? 0 : $real_decimals;

	return format_number($amount, $real_round, $real_decimals, $params);
		
}

/**
 * function format_number($amount, $round = 2, $decimals = 2, $params = array()) 
 * 
 * number formatting
 *
 * @param FLOAT $amount - # to be converted
 * @param INT $round - # of places to round (can be -)
 * @param INT $decimals - floating point precision
 * 
 * The following are passed in as an array of params:
 * @param BOOL $params['currency_symbol'] - true to display currency symbol
 * @param BOOL $params['convert'] - true to convert from USD dollar
 * @param BOOL $params['percentage'] - true to display % sign
 * @param BOOL $params['symbol_space'] - true to have space between currency symbol and amount
 * @param STRING $params['symbol_override'] - string to over default currency symbol
 * @param STRING $params['type'] - pass in 'pdf' for pdf currency symbol conversion
 * @param GUID $params['currency_id'] - currency_id to retreive, defaults to current user
 * 
 * @return STRING $amount - formatted number 
 */
function format_number($amount, $round = null, $decimals = null, $params = array()) {
	global $app_strings, $current_user, $locale;
	static $current_users_currency = null;
	
	$seps = get_number_seperators();
	$num_grp_sep = $seps[0];
	$dec_sep = $seps[1];
	$reset_rate = null;
	$symbol_after = array_get_default($params, 'symbol_place_after', false);
	
	// only create a currency object if we need it
	if(!empty($params['currency_symbol']) || !empty($params['convert']) ||
	   !empty($params['currency_id']) || !empty($params['use_currency_decimals'])) {
	   		// if we have an override currency_id
	   		if(isset($params['currency_id'])) {
				$currency_id = $params['currency_id'];
			}
			else {
				if(!isset($current_users_currency)) {
					$current_users_currency = $current_user->getPreference('currency');
					if(! $current_users_currency)
						$current_users_currency = '-99'; // use default if none set
				}
				$currency_id = $current_users_currency;
			}
			$currency = AppConfig::db_object('Currency', $currency_id);
			if(! $currency) {
				$currency = new Currency();
				$currency->loadDefaultCurrency();
			}
			
			// longreach - start added
			if(isset($params['entered_currency_id']) && $params['entered_currency_id'] == '')
				$params['entered_currency_id'] = '-99';
			if(! empty($params['exchange_rate']) &&
				! empty($params['entered_currency_id']) &&
				$params['entered_currency_id'] == $currency->id &&
				$currency->id != '-99') {
					$reset_rate = $currency->conversion_rate;
					$currency->conversion_rate = $params['exchange_rate'];
			}
			
			if(! empty($params['use_currency_decimals'])) {
				if(isset($currency->decimal_places)) {
					$decimals = $currency->decimal_places;
					if(! isset($round) || $round >= 0)
						$round = $decimals;
				}
			}
			if(! isset($params['symbol_place_after']) && isset($currency->symbol_place_after)) {
				$symbol_after = $currency->symbol_place_after;
			}
			// longreach - end added
	}
	
	if(is_null($round)) {
		$round = $locale->getPrecision();
	}
	if(is_null($decimals)) {
		if($round >= 0) {
			$decimals = $round;
		} else {
			$round = $locale->getPrecision();
			$decimals = -1;
		}
	}
	if($round < 0 && $decimals >= 0) {
		// swap arguments when round=-1, decimals given
		$round = $decimals;
		$decimals = -1;
	}
	if($decimals < 0) {
		$decs = (isset($round) && $round >= 0) ? $round : 5;
		if($decs > 0) {
			$a2 = rtrim(number_format($amount, $decs, '.', ''), '0');
			$round = strlen($a2) - strpos($a2, '.') - 1;
			$decimals = $round;
		} else {
			$decimals = 0;
		}
	}
	if($round < 0)
		$round = $decimals;
	
	if(!empty($params['convert'])) {
		$amount = $currency->convertFromDollar($amount, 6);
	}

	if(!empty($params['currency_symbol']) && $params['currency_symbol']) {
		if(!empty($params['symbol_override'])) {
			$symbol = $params['symbol_override'];
		}
		elseif(!empty($params['type']) && $params['type'] == 'pdf') {
			$symbol = $currency->getPdfCurrencySymbol();
		} else {
			if(empty($currency->symbol))
				$symbol = $currency->getDefaultCurrencySymbol();
			else 
				$symbol = $currency->symbol;
		}
	} else {
		$symbol = '';
	}
	
	if(isset($params['charset_convert'])) {
		$symbol = $locale->translateCharset($symbol, 'UTF-8', $locale->getExportCharset());
	}
	
	//TODO: display human readable - easy
	$human = false;
	
	$space = array_get_default($params, 'symbol_space', false);
	$param_type = array_get_default($params, 'type');
	if($space === true || $space === 1) {
		if($param_type == 'pdf')
			$space = html_entity_decode('&nbsp;', ENT_QUOTES, 'UTF-8');
		else if($param_type == 'chart' || $param_type == 'export')
			$space = ' ';
	}
	if($human == false) {
		$amount = number_format(round($amount, $round), $decimals, $dec_sep, $num_grp_sep);
		$amount = format_place_symbol($amount, $symbol, $space, $symbol_after);
	} else {
		if($amount > 1000) {
			$amount = round(($amount / 1000), 0);
			$amount = $amount . 'k';
			$amount = format_place_symbol($amount, $symbol, array_get_default($params, 'symbol_space', false), $symbol_after);
		} else {
			$amount = format_place_symbol($amount, $symbol, array_get_default($params, 'symbol_space', false), $symbol_after);
		}
	}
	
	if(!empty($params['percentage']) && $params['percentage']) $amount .= $app_strings['LBL_PERCENTAGE_SYMBOL'];
	
	if(isset($reset_rate))
		$currency->conversion_rate = $reset_rate;

	if(isset($currency)) {
		$currency->cleanup();
		unset($currency);
	}

	return $amount;
} //end function format_number


function format_place_symbol($amount, $symbol, $symbol_space, $after=false) {
	if($symbol != '') {
		if($symbol_space === true || $symbol_space === 1)
			$symbol_space = '&nbsp;';
		if(! is_string($symbol_space))
			$symbol_space = '';
		if(! $after) {
			$amount = $symbol . $symbol_space . $amount;
		} else {
			$amount = $amount . $symbol_space . $symbol;
		}
	}
	return $amount;	
}	

function unformat_number($string) {
	static $currency;
	if(!isset($currency)) {
		global $current_user;
		$currency = new Currency();
		if($current_user->getPreference('currency')) $currency->retrieve($current_user->getPreference('currency'));
		else $currency->retrieve('-99'); // use default if none set
	}
	
	$string = from_html($string); // convert possible apostrophes
	
	$seps = get_number_seperators();
	// remove num_grp_sep and replace decimal seperater with decimal
	$string = trim(str_replace(array($seps[0], $seps[1]), array('', '.'), $string));
	$string = preg_replace('/^' . preg_quote($currency->symbol) . '/', '',  $string); // remove currency symbol in the beginning of there is one	
	$string = str_replace('&nbsp;', '', $string);

	return trim($string);
}

// deprecated use format_number() above
function format_money($amount, $for_display = TRUE )
{
	// This function formats an amount for display.
	// Later on, this should be converted to use proper thousand and decimal seperators
	// Currently, it stays closer to the existing format, and just rounds to two decimal points
	if ( isset($amount) )
	{
		if ( $for_display )
		{
			return sprintf("%0.02f",$amount);
		}
		else
		{
			// If it's an editable field, don't use a thousand seperator.
			// Or perhaps we will want to, but it doesn't matter right now.
			return sprintf("%0.02f",$amount);
		}
	}
	else
	{
		return;
	}
}

// returns the array(1000s seperator, decimal seperator)
function get_number_seperators() {
	global $locale;
	static $fmt = null;
	if(is_null($fmt)) {
		$std_fmt = $locale->getNumberFormat();
		$fmt = array($std_fmt['grp_sep'], $std_fmt['dec_sep']);
	}
	return $fmt;
}


// longreach - start added
/*
	Handle exchange rate field for a SugarBean.
	This method must be called after unformat_all_fields() and before save().
	Allows the user to override the rate, and automatically resets the rate
	when the currency changes.
	Set the exchange rate field to null to force a reset.
	$currency will be set to the current assigned currency if not already.
	Returns true if the rate has been set or updated.
	Do not save $currency (that might alter the standard rate).
*/
function adjust_exchange_rate(&$bean, &$currency, $params=null) {
	if($params == null) $params = array();
	$cid_f = 'currency_id';
	if(isset($params['currency_field']))
		$cid_f = $params['currency_field'];
	$rate_f = 'exchange_rate';
	if(isset($params['rate_field']))
		$rate_f = $params['rate_field'];

	$cid = $bean->$cid_f;
	if(empty($cid)) $cid = '-99';
	if($currency->id != $cid)
		$currency->retrieve($cid);
	if(! empty($params['override']))
		$override = true;
	else if($currency->id == '-99')
		$override = false;
	else if((empty($params['allow_override']) || $params['allow_override'])
		&& ! empty($_REQUEST['override_exchange_rate'])
		&& ! empty($_REQUEST['override_exchange_rate'][$rate_f]))
			$override = true;
	else
		$override = false;
	
	$old_rate = isset($bean->$rate_f) ? $bean->$rate_f : '';
		
	if(isset($bean->fetched_row) && is_array($bean->fetched_row) && array_key_exists($rate_f, $bean->fetched_row))
		$old_rate = $bean->fetched_row[$rate_f];
	if(! $override) {
		$bean->$rate_f = $old_rate;
		if($bean->field_value_has_changed($cid_f) || $currency->id == '-99')
			$bean->$rate_f = '';
	}
	if(empty($bean->$rate_f))
		$bean->$rate_f = $currency->conversion_rate;
	else
		$currency->conversion_rate = $bean->$rate_f;
	return ($bean->$rate_f != $old_rate);
}


if(! defined('PHP_ROUND_HALF_UP')) { // introduced in PHP 5.3
    define('ROUND_COMPAT', 1);
    define('PHP_ROUND_HALF_UP', 1);
    define('PHP_ROUND_HALF_DOWN', 2);
    define('PHP_ROUND_HALF_EVEN', 3);
    define('PHP_ROUND_HALF_ODD', 4);
} else {
    define('ROUND_COMPAT', 0);
}
$GLOBALS['std_currency_round_method'] = PHP_ROUND_HALF_UP;
function currency_round($value, $decimals, $string=true, $method=null) {
    if(! isset($method)) $method = $GLOBALS['std_currency_round_method'];
    if(ROUND_COMPAT) {
        if($method === PHP_ROUND_HALF_EVEN) {
            if(floor(abs($value * pow(10, $decimals))) % 2 == 0) {
                $p = pow(0.1, $decimals + 1);
                return number_format($value + ($value > 0 ? -$p : $p), $decimals);
            }
            else
                $v = round($value, $decimals);
        }
        else
            $v = round($value, $decimals);
    } else
        $v = round($value, $decimals, $method);
    if($string)
        $v = number_format($v, $decimals, '.', '');
    return $v;
}
// longreach - end added

?>
