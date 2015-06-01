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
if (!defined('inScheduler')) die('Unauthorized access');


require_once('modules/Forecasts/ForecastCalculator.php');

global $current_user;

$period 			= AppConfig::setting('company.forecast_period');
$forecast_periods 	= AppConfig::setting('company.retain_forecast_periods');
$history_periods 	= AppConfig::setting('company.retain_history_periods');
$fiscal_year_start	= AppConfig::setting('company.fiscal_year_start');

if ('Disabled' == $period) return;

$fc = new ForecastCalculator($period);

global $db;
$db->query("update forecasts set deleted = 1 where start_date < '" . PearDatabase::quote($fc->getRemoveDate($fiscal_year_start, $history_periods)) . "'");

for ($i = -$history_periods; $i < $forecast_periods; $i ++) {
	$fc->fill_forecasts($i, $fiscal_year_start);
}

$fc->cleanup();

?>
