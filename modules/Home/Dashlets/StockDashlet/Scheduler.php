<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
if(!defined('inScheduler')) die('Unauthorized access');

require_once 'modules/Home/Dashlets/StockDashlet/StockDashlet.php';
global $timedate, $timezones;

$u = new User;
$users = $u->get_list("", "(portal_only IS NULL OR !portal_only)");

$tz_info = $timezones['America/New_York'];
$adjust = $timedate->adjustmentForUserTimeZone($tz_info);
$now_EST = time() - $adjust*60;
$wday = date('w', $now_EST);
$tm = date('H:i:s', $now_EST);
if($wday == 0 || $wday == 6 || $tm < '09:00:00' || $tm > '19:00:00')
	return; // outside trading hours

foreach ($users['list'] as $user ) {
	$defs = array();
	$dashletDefs = $user->getPreference('dashlets', 'home');

	if (isset($dashletDefs[0])) {
		$defs = array_merge($defs, $dashletDefs[0]);
	}
	if (isset($dashletDefs[1])) {
		$defs = array_merge($defs, $dashletDefs[1]);
	}
	$symbols = null;
	foreach ($defs as $def) {
		if ($def['className'] != 'StockDashlet') continue;
		if (empty($def['options']['symbols'])) {
			$symbols = StockDashlet::getDefaultSymbols();
		} else {
			$symbols = $def['options']['symbols'];
		}
	}
	if (!empty($symbols)) {
		$quotes = StockDashlet::getQuotes($symbols);
		$response = array('symbols' => $symbols, 'rows' => $quotes, 'time' => time());
		$user->setPreference('quotes', $response, 0, 'StockDashlet');
		$user->savePreferencesToDB();
	}
}

