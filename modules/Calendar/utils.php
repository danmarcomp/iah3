<?php

require_once 'include/config/format/ConfigParser.php';

function get_holidays_params()
{
	global $current_user, $current_language, $locale;
	global $HOLIDAYS;
	
	if (!empty($HOLIDAYS)) {
		return array($HOLIDAYS['country'], $HOLIDAYS['language']);
	}
	
	$country =  $current_user->getPreference('user_holidays');
	if (empty($country)) {
		$country = $locale->getPrecedentPreference('default_holidays');
	}

	$holidays_file = "modules/Calendar/holidays/$country.holidays.php";
	$default_h = AppConfig::setting('locale.defaults.holidays', 'usa');

	if (file_exists($holidays_file)) {
	} elseif (file_exists("modules/Calendar/holidays/{$default_h}.holidays.php")) {
		$country = $default_h;
	} else {
		return array('', '');
	}
	
	$holidays_file = "modules/Calendar/holidays/$country.holidays.php";
	$HOLIDAYS = ConfigParser::load_file($holidays_file);
	$HOLIDAYS['mtime'] = filemtime($holidays_file);
	
	$language_used = $current_language;
	$HOLIDAYS['country'] = $country;
	$HOLIDAYS['language'] = $language_used;

	$holidays_file = "modules/Calendar/holidays/$country.holidays_calc.php";
	if (file_exists($holidays_file))
		require_once $holidays_file;
	
	return array($country, $language_used);
}

function get_holidays_mtime() {
	return array_get_default($GLOBALS['HOLIDAYS'], 'mtime', false);
}

function load_holidays()
{
	global $HOLIDAYS;

	list ($country, $language) = get_holidays_params();
	
	$base_language = AppConfig::setting('locale.base_language', 'en_us');
	$holiday_base = array_get_default($HOLIDAYS, 'language_default', $base_language);
	
	$lang_paths = array(
		"modules/Calendar/holidays/$country.$holiday_base.php",
		"custom/include/language/$country.$holiday_base.php",
		"modules/Calendar/holidays/$country.$language.php",
		"custom/include/language/$country.$language.php",
	);

	foreach($lang_paths as $path) {
		if (file_exists($path)) {
			$mtime = filemtime($path);
			$HOLIDAYS['lang'] = ConfigParser::load_file($path);
			if($mtime > $HOLIDAYS['mtime'])
				$HOLIDAYS['mtime'] = $mtime;
		}
	}
}

function holiday($year, $month, $day)
{
	global $HOLIDAYS;
	if (!function_exists('calculate_holiday')) {
		return '';
	}
	$str = calculate_holiday($year, $month, $day);
	if (isset($HOLIDAYS['lang'][$str])) {
		return $HOLIDAYS['lang'][$str];
	}
	return '';
}


