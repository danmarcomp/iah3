<?php

function get_export_charset_options() {
	global $locale;
	return $locale->getCharsetSelect();
}

function get_export_format_options() {
	global $locale;
	return $locale->getExportFormatSelect();
}

function get_fiscal_year_options() {
	global $current_language, $app_list_strings;
	$fy_options = array();
	foreach(range(1, 12) as $mo) {
		$fy_options[$mo] = $app_list_strings['months_long_dom'][$mo];
	}
	return $fy_options;
}

function get_mobile_columns_options() {
	return array(
		'0' => translate('LBL_AUTOMATIC', 'Configurator'),
		'1' => '1',
		'2' => '2',
	);
}

function get_quote_layout_options() {
	static $layouts;
	if(! isset($layouts)) {
		require_once('modules/Quotes/QuotePDF.php');
		$layouts = QuotePDF::getAvailableLayouts();
		if(! $layouts) $layouts = array();
	}
	return $layouts;
}

function get_case_queue_users($form=null, $selected=null) {
	return get_user_array(true, "Active", $selected);
}

function get_payment_gateway_options() {
	require_once 'include/CCGateway/Base.php';
	$gateways = CCGatewayBase::getSupportedGateways();
	return $gateways;
}

function get_week_day_options() {
	global $current_language, $app_list_strings;
	return $app_list_strings['weekdays_long_dom'];
}

function get_hour_options() {
	$hour_opts = array();
	for($h = 0; $h < 24; $h += 0.5)
		$hour_opts[(string)$h] = format_decimal_time($h);
	return $hour_opts;
}

function get_locale_date_options() {
	return AppConfig::setting('locale.date_formats');
}

function get_locale_time_options() {
	return AppConfig::setting('locale.time_formats');
}

function get_locale_language_options() {
	return AppConfig::get_languages();
}

function get_locale_number_options() {
	return $GLOBALS['locale']->getLocaleNumberFormatOptions();
}

function get_locale_timezone_options() {
	// FIXME - need optgroup support
	$opts = $GLOBALS['timedate']->getTimeZones(true, false);
	return $opts;
}

function get_locale_holidays_options() {
	$holidays = array();
	$files = glob('modules/Calendar/holidays/*.holidays.php');
	foreach ($files as $file) {
		$HOLIDAYS = ConfigParser::load_file($file);
		$m = array();
		preg_match('~[/\\\\]([^/\\\\]+)\.holidays\.php$~', $file, $m);
		if (isset($HOLIDAYS['lang']['LBL_NAME'])) {
			$holidays[$m[1]] =  $HOLIDAYS['lang']['LBL_NAME'];
		} else {
			$holidays[$m[1]] =  $HOLIDAYS['name_default'];
		}
	}
	return $holidays;
}

function get_theme_options() {
	return AppConfig::get_themes();
}

function get_locale_phone_country_options() {
	$telephony_country = array(
		"NorthAmerica" => translate('LBL_NORTH_AMERICA', 'Configurator'),
		"other" => translate('LBL_RULES_OTHER', 'Configurator'),
	);
	return $telephony_country;
}

function get_database_collation_options() {
	global $db;
	$q = "SHOW COLLATION LIKE 'utf8%'";
	$r = $db->query($q);
	$ret = array();
	while($a = $db->fetchByAssoc($r))
		$ret[$a['Collation']] = $a['Collation'];
	return $ret;
}

function get_email_inbound_max_messages_options() {
	$opts = AppConfig::setting('email.import.max_messages_options', array());
	$cur = AppConfig::setting('email.import.max_session_messages');
	if($cur && ! in_array($cur, $opts))
		$opts[] = $cur;
	$ret = array();
	foreach($opts as $o)
		$ret[$o] = $o;
	ksort($ret);
	return $ret;
}


function get_email_outbound_max_messages_options() {
	$opts = AppConfig::setting('massemailer.max_messages_options', array());
	$cur = AppConfig::setting('massemailer.campaign_emails_per_run');
	if($cur && ! in_array($cur, $opts))
		$opts[] = $cur;
	$ret = array();
	foreach($opts as $o)
		$ret[$o] = $o;
	ksort($ret);
	return $ret;
}
