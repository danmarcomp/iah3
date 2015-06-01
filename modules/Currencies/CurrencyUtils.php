<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/*
	Copyright 2005 Sveta Smirnova
	$Id: CurrencyUtils.php 7531 2010-10-13 20:03:33Z andrew $
*/

require_once('modules/Currencies/Currency.php');

class CurrencyUtils
{
	function get_list($reload=false)
	{
		static $list;
		
		if (! isset($list) || $reload) {
			$focus = new Currency();
			$temp_list = $focus->get_full_list('name');
			$focus->retrieve('-99');
	  		if(is_array($temp_list)){
				$temp_list = array_merge(array($focus), $temp_list);
			}else{
				$temp_list = array($focus);	
			}
			foreach ($temp_list as $value) {
				$list[$value->id] = $value;
			}
		}
		
		return $list;
	}
	
	function get_currency_by_id($currency_id)
	{
		$list = CurrencyUtils::get_list();
		return (isset($list[$currency_id]) ? $list[$currency_id] : $list['-99']);
	}
	
	static function get_currency_name($currency_id, $short=false)
	{
		$currency = CurrencyUtils::get_currency_by_id($currency_id);
		if( $currency->deleted != 1)
			return ($short ? $currency->name : $currency->iso4217) . ' : ' . $currency->symbol;
		else 
			return ($short ? $currency->getDefaultCurrencyName() : $currency->getDefaultISO4217()) . ' : ' . $currency->getDefaultCurrencySymbol();
	}
	
	function get_pdf_currency_name($currency_id)
	{
		$currency = CurrencyUtils::get_currency_by_id($currency_id);
		return $currency->name . ' : ' . $currency->getPdfCurrencySymbol();
	}
	
	function get_pdf_currency_symbol($currency_id)
	{
		$currency = CurrencyUtils::get_currency_by_id($currency_id);
		return $currency->getPdfCurrencySymbol();
	}
	
	function get_currency_symbol($currency_id)
	{
		$currency = CurrencyUtils::get_currency_by_id($currency_id);
		if( $currency->deleted != 1)
			return $currency->symbol;
		else 
			return $currency->getDefaultCurrencySymbol();
	}

	function updateRates()
	{
		global $log;
		
		$requestUrl = 'http://www.ecb.int/stats/eurofxref/eurofxref-daily.xml';
		$curlInstance = curl_init($requestUrl);
		curl_setopt($curlInstance, CURLOPT_HEADER, 0);
		curl_setopt($curlInstance, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curlInstance, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($curlInstance, CURLOPT_TIMEOUT, 30);
		$response = curl_exec($curlInstance);
		if($response === false) {
			$log->fatal("Error downloading currency data ($requestUrl)");
			return;
		}
		
		libxml_use_internal_errors(true);		
		$xml = simplexml_load_string($response);
		
		if (!$xml) {
			$log->fatal("Error parsing currency data as XML ($requestUrl)");
			return;
		}
		
		$xml->registerXPathNamespace('ns1', 'http://www.ecb.int/vocabulary/2002-08-01/eurofxref');

		$rates = $xml->xpath("/gesmes:Envelope/ns1:Cube/ns1:Cube/ns1:Cube");
		$map = array();
		foreach($rates as &$rate) {
			$attrs = $rate->attributes();
			if(empty($attrs['currency']) || ! isset($attrs['rate']))
				continue;
			$symbol = (string)$attrs['currency'];
			$theRate = (float)$attrs['rate'];
			if (!empty($symbol))
				$map[$symbol] = $theRate;
		}
		$map['EUR'] = 1;
		$defSymbol = AppConfig::setting('locale.base_currency.iso4217');
		if (!isset($map[$defSymbol])) {
			$log->fatal("Rate not defined for default currency ($defSymbol)");
			return;
		}

		$all = CurrencyUtils::get_list();
		foreach ($all as $currency) {
			if (isset($map[$currency->iso4217])) {
				$currency->conversion_rate = 1.0000 / $map[$defSymbol] * $map[$currency->iso4217];
				$currency->save();
			}
		}
	}
}

?>
