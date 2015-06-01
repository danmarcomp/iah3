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


require_once('data/SugarBean.php');

class Forecast extends SugarBean
{
	var $id;
    var $date_entered;
    var $created_by;
    var $date_modified;
    var $modified_user_id;
    var $deleted;
    var $name;
    var $user_id;
	var $user_name;
    var $start_date;
    var $end_date;
    var $opportunities;
    var $total;
    var $weighted;
    var $best_case;
    var $commit;
    var $actual;
    var $forecast;
    var $quota;
	var $commit_date;
	var $quota_date;
	var $type;
	
	var $last_forecast;
	var $last_forecast_date;
	
	var $table_name = 'forecasts';
    var $object_name = 'Forecast';
    var $module_dir = 'Forecasts';
	
	var $new_schema = true;
	
	var $additional_column_fields = Array(
		'last_forecast',
	   	'last_forecast_date',
	   	'local_forecast',
	   	'local_quota',
	   	'local_total',
	   	'local_last_forecast',
	);
	

	static function retrieve_by_period($user_id, $start_date, $type = 'personal')
	{
		$lq = new ListQuery('Forecast', true);
		$lq->addSimpleFilter('user_id', $user_id);
		$lq->addSimpleFilter('start_date', $start_date);
		$lq->addSimpleFilter('type', $type);
		$ret = $lq->runQuerySingle();
		if($ret && ! $ret->failed) return $ret;
	}

    static function calc_quote_percent($spec) {
        $quota = 0;
        if (isset($spec['raw_values']['quota']))
            $quota = $spec['raw_values']['quota'];

        $actual = 0;
        if (isset($spec['raw_values']['actual']))
            $actual = $spec['raw_values']['actual'];

        $quota_percent = '&mdash;';
        if ($quota > 0)
            $quota_percent = format_number($actual / $quota * 100, 0, 0) . '%';

        return $quota_percent;
    }

	function is_team_leader($user_id)
	{
		static $cache = array();
		if (isset($cache[$user_id])) return $cache[$user_id];
		$query = "
			SELECT 
				count(`{$this->table_name}`.`id`) as count_id
			FROM `{$this->table_name}`
			JOIN
				users
			ON
				users.id = `{$this->table_name}`.user_id
			WHERE
				`{$this->table_name}`.deleted = 0
				AND users.deleted = 0
				AND users.status = 'Active'
				AND users.reports_to_id='" . PearDatabase::quote($user_id) . "'
		";
		return $cache[$user_id] = $this->db->getOne($query);
	}
	
	function recreatePull()
	{
		$this->db->query("update `{$this->table_name}` set deleted = 1");
		define('inScheduler', true);
		require_once('modules/Forecasts/RunScheduled.php');
	}
	
	function getPeriodsList($forecast_type, $user_id, $with_empty_field = false)
	{
		$retval = array();
		if ($with_empty_field) {
            global $mod_strings;
			$retval[null] = $mod_strings['LBL_ALL_PERIODS'];
        }
		
		$result = $this->db->query("SELECT start_date FROM `{$this->table_name}` WHERE type='"
							. PearDatabase::quote($forecast_type) . "' AND deleted != 1"
							. " AND user_id='" . PearDatabase::quote($user_id) . "'"
							. " ORDER BY start_date"
							);
		while ($row = $this->db->fetchByAssoc($result)) {
			$retval[$row['start_date']] = $this->getPeriodName($row['start_date']);
		}
		
		return $retval;
	}

    static function get_period_options() {
        $type = 'personal';
        if (isset($_REQUEST['type']))
            $type = $_REQUEST['type'];

        $show_empty = true;

        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'WorksheetView')
            $show_empty = false;

        $forecast = new Forecast();
        $options = $forecast->getPeriodsList($type, AppConfig::current_user_id(), $show_empty);

        return $options;
    }
    
    static function get_type_options() {
    	global $app_list_strings;
    	$opts = array_merge(
    		//array('' => translate('LBL_ALL_TYPES', 'Forecasts')),
    		$app_list_strings['forecast_type_dom']);
    	return $opts;
    }

	// start date must be in database (YYYY-MM-DD) format, and defaults to the forecast's start date
	function getPeriodName($start_date=null, $long=false, $period_type=null) {
		$info = self::getPeriodInfo($start_date, $period_type);
		return $info[$long ? 'long_name' : 'name'];
	}
	
	// date must be in database (YYYY-MM-DD) format, and defaults to the forecast's start date
	static function getPeriodInfo($date, $period_type=null, $fy_start=null)
	{
		global $current_language, $app_list_strings, $timedate;

		if($period_type == null)
			$period_type = AppConfig::setting('company.forecast_period');
		if($fy_start == null)
			$fy_start = AppConfig::setting('company.fiscal_year_start');
		
		$year = $month = $day = 0;
		$name = '';
		$long_name = '';
		sscanf($date, '%4d-%2d-%2d', $year, $month, $day);

		switch($period_type) {
			case 'Monthly':
				$start_date = gmdate("Y-m-d", gmmktime(0, 0, 0, $month, 1, $year));
				$end_date = gmdate("Y-m-d", gmmktime(0, 0, 0, $month + 1, 0, $year));
				
				$name = $app_list_strings['months_dom'][$month+0] . ' ' . $year;
				$long_name = $app_list_strings['months_long_dom'][$month+0] . ' ' . $year;
				break;
			case 'Quarterly':
				$start_month = $fy_start + floor(($month - $fy_start) / 3) * 3;
				if (0 > $start_month)
					$start_month = 12 + $start_month;
				$start_date = gmdate("Y-m-d", gmmktime(0, 0, 0, $start_month, 1, $year));
				$end_date = gmdate("Y-m-d", gmmktime(0, 0, 0, $start_month + 3, 0, $year));
				if ($fy_start <= $start_month) {
					$quart_number = floor(($start_month - $fy_start) / 3) + 1;
					$fy_year = $year;
				} else {
					$quart_number = floor(($start_month + 12 - $fy_start) / 3) + 1;
					$fy_year = $year - 1;
				}
				$name = $long_name = 'FY' . $fy_year . '-Q' . $quart_number;
				break;
			default:
				return;
		}
		
		return array('id' => $start_date, 'start_date' => $start_date, 'end_date' => $end_date,
			'start_date_time' => $start_date . ' 00:00:00', 'end_date_time' => $end_date . ' 23:59:59',
			'name' => $name, 'long_name' => $long_name);
	}
	
	static function getTotals($where = '')
	{
		global $db;
		
		$need_users = preg_match('~\busers\.~', $where);
		if (strlen($where)) $where .= ' AND ';
		$where .= 'forecasts.deleted = 0';
		$query = 'SELECT COALESCE(SUM(opportunities), 0) AS opportunities,'
					. ' COALESCE(SUM(total), 0) AS total,'
					. ' COALESCE(SUM(weighted), 0) AS weighted,'
					. ' COALESCE(SUM(best_case), 0) AS best_case,'
					. ' COALESCE(SUM(commit), 0) AS commit,'
					. ' COALESCE(SUM(actual), 0) AS actual,'
					. ' COALESCE(SUM(forecast), 0) AS forecast,'
					. ' COALESCE(SUM(quota), 0) AS quota,'
					. ' COUNT(DISTINCT user_id) AS user_count'
					. ' FROM forecasts ';
		if ($need_users) $query .= ' LEFT JOIN users ON users.id=forecasts.user_id ';
		$query .= " WHERE $where";
		
		$result = $db->query($query, true, "Error calculating forecast totals");
		return $db->fetchByAssoc($result);
	}
	
}
?>