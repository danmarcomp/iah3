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


require_once 'modules/Forecasts/Forecast.php';	
require_once 'modules/Forecasts/Forms.php';	

class ForecastCalculator
{
	var $total		= 0;
    var $weighted	= 0;
    var $best_case	= 0;
    var $commit		= 0;
    var $forecast	= 0;
	var $quota		= 0;
	var $actual		= 0;
	
    var $period;
	var $users;
	
	var $filled_ids = array();
	
	function ForecastCalculator($period, $users = null)
	{
		static $all_users;
		$this->period = $period;
		if($users == null) {
			if(!isset($all_users))
				$all_users = $this->get_all_users();
			$users =& $all_users;
		}
		$this->users =& $users;
	}
	
	function get_all_users() {
		global $db;
		$result = $db->query("select id, user_name, reports_to_id, status from users where deleted != 1 and portal_only = 0", true, "Error retrieving user list");
		$users = array();
		while ($user = $db->fetchByAssoc($result))
			$users[$user['id']] = $user;
		return $users;
	}

	function which_totals($category)
	{
		static $map = array(
			'Actual' => 'actual',
			'Commit' => 'commit',
			'Best Case' => 'best_case',
			'Pipeline' => 'total',
		);
		static $hierarchy = array(
			'total',
			'best_case',
			'commit',
			'actual',
		);
		if (!isset($map[$category])) return array();
		$ret = array();
		$stop_on = $map[$category];
		foreach ($hierarchy as $total_name) {
			$ret[] = $total_name;
			if ($total_name == $stop_on) {
				break;
			}
		}
		return $ret;
	}	

	function fill_my(&$value, $key)
	{
		$totals = $this->which_totals($value->forecast_category);
		foreach ($totals as $total) {
			$this->$total 	+= $value->amount_usdollar;
		}
		$this->weighted += $value->amount_usdollar * $value->probability / 100;
	}
	
	function getMonthlyData($offset, $fiscal_year_start, &$start_date, &$end_date, &$forecast_name)
	{
		$start_date = gmdate("Y-m-d", gmmktime(0, 0, 0, date('n') + $offset, 1));
		$end_date = gmdate("Y-m-d", gmmktime(0, 0, 0, date('n') + $offset + 1, 0));
		$forecast_name = gmdate('M Y', gmmktime(0, 0, 0, date('n') + $offset, 1));
	}
	
	function getQuarterlyData($offset, $fiscal_year_start, &$start_date, &$end_date, &$forecast_name)
	{
		$time = gmmktime(0, 0, 0, date('n') + $offset * 3);
		$month = date('n', $time);
		$year = date('Y', $time);
		$start_month = $fiscal_year_start + floor(($month - $fiscal_year_start) / 3) * 3;
		if (0 > $start_month)
			$start_month = 12 + $start_month;
		$start_date = gmdate("Y-m-d", gmmktime(0, 0, 0, $start_month, 1, $year));
		$end_date = gmdate("Y-m-d", gmmktime(0, 0, 0, $start_month + 3, 0, $year));
		if ($fiscal_year_start <= $start_month) {
			$quart_number = floor(($start_month - $fiscal_year_start) / 3) + 1;
			$fy_year = $year;
		} else {
			$quart_number = floor(($start_month + 12 - $fiscal_year_start) / 3) + 1;
			$fy_year = $year - 1;
		}
		$forecast_name = 'FY' . $fy_year . '-Q' . $quart_number;
	}
	
	function getLatestDate($fiscal_year_start, $forecast_periods) {
		return self::get_latest_date($this->period, $fiscal_year_start, $forecast_periods);
	}

	function getRemoveDate($fiscal_year_start, $history_periods) {
		return self::get_remove_date($this->period, $fiscal_year_start, $history_periods);
	}
	
	static function get_latest_date($period, $fiscal_year_start, $forecast_periods)
	{
		switch ($period) {
			case 'Monthly':
				return gmdate("Y-m-d", gmmktime(0, 0, 0, date('n') + $forecast_periods, 2));
			case 'Quarterly':
				$month = date('n');
				$start_month = $fiscal_year_start + floor(($month - $fiscal_year_start) / 3) * 3;
				if (0 > $start_month)
					$start_month = 12 + $start_month;
				return gmdate("Y-m-d", gmmktime(0, 0, 0, $start_month + $forecast_periods * 3, 2));
			default:
				return;
		}
	}
	
	static function get_remove_date($period, $fiscal_year_start, $history_periods)
	{
		switch ($period) {
			case 'Monthly':
				return gmdate("Y-m-d", gmmktime(0, 0, 0, date('n') - $history_periods, 2));
			case 'Quarterly':
				$month = date('n');
				$start_month = $fiscal_year_start + floor(($month - $fiscal_year_start) / 3) * 3;
				if (0 > $start_month)
					$start_month = 12 + $start_month;
				return gmdate("Y-m-d", gmmktime(0, 0, 0, $start_month - $history_periods * 3, 2));
			default:
				return;
		}
	}
	
	function fill_personal_forecast(RowUpdate &$forecast, $period=null) {
		require_once 'modules/Opportunities/Opportunity.php';
		$info = Forecast::getPeriodInfo($period);

		//check user has opportunities in the period
		$seedOpportunity = new Opportunity();
		$opportunities = $seedOpportunity->get_full_list('', "opportunities.date_closed >= '".PearDatabase::quote($info['start_date'])."' and opportunities.date_closed <= '".PearDatabase::quote($info['end_date'])."'"
													. " AND opportunities.assigned_user_id = '".PearDatabase::quote($forecast->getField('user_id'))."'"
													. " AND opportunities.forecast_category != 'Omitted' "
													. " AND opportunities.deleted = 0 "
													);
		if (!$opportunities)
			$opportunities = array();
		
		$temp = new ForecastCalculator($this->period, $this->users);
		
		array_walk($opportunities, array(&$temp, 'fill_my'));
		
		$forecast->set(array(
			'opportunities'	=> count($opportunities),
			'total' => $temp->total,
			'weighted' => $temp->weighted,
			'best_case' => $temp->best_case,
			'commit' => $temp->commit,
			'actual' => $temp->actual,
			'type' => 'personal',
		));

		SugarBean::cleanup_list($opportunities);
	}
	
	function fill_team_individual(RowUpdate &$forecast, $team, $period)
	{
		$team_list = count($team) ? ("'" . implode("','", $team) . "'") : '';
		$where = "forecasts.user_id IN(" . $team_list . ") "
				. " AND forecasts.start_date = '" . PearDatabase::quote($period) . "'"
				. " AND forecasts.type = 'personal'";
		
		if(($row = Forecast::getTotals($where)) !== null) {
			$forecast->set(array(
				'opportunities' => $row['opportunities'],
				'total' => $row['total'],
				'weighted' => $row['weighted'],
				'best_case' => $row['best_case'],
				'commit' => $row['commit'],
				'actual' => $row['actual'],
				'forecast' => $row['forecast'],
				'quota' => $row['quota'],
			));
		}
		$forecast->set('type', 'team_individual');
	}
	
	function fill_team_rollup(RowUpdate &$forecast, $period)
	{
		$user_id = $forecast->getField('user_id');
		$where = "forecasts.start_date = '" . PearDatabase::quote($period) . "'"
				. " AND (
						(
							forecasts.type = 'personal' AND 
							forecasts.user_id = '" . PearDatabase::quote($user_id) . "'
						)
						OR 
						(
							users.reports_to_id='" . PearDatabase::quote($user_id) . "' AND 
							forecasts.type = 'team_individual' AND
							users.status='Active'
						)
					)";
	
		if(($row = Forecast::getTotals($where)) !== null) {
			$forecast->set(array(
				'opportunities' => $row['opportunities'],
				'total' => $row['total'],
				'weighted' => $row['weighted'],
				'best_case' => $row['best_case'],
				'commit' => $row['commit'],
				'actual' => $row['actual'],
			));
			// if nobody reports to this user, act like a team_individual forecast
			if($row['user_count'] == 1) {
				$forecast->set('forecast', $row['forecast']);
				$forecast->set('quota', $row['quota']);
			}
		}
		$forecast->set('type', 'team_rollup');
	}

	
	function fill_forecasts($offset, $fiscal_year_start, $force = false)
	{
		global $db;
		
		$start_date = 0;
		$end_date = 0;
		$forecast_name = 0;
		
		$funcname = 'get' . $this->period . 'Data';
		
		$this->$funcname($offset, $fiscal_year_start, $start_date, $end_date, $forecast_name);
		
		if (!$force) {
			$result = $db->query("select count(id) from forecasts where start_date='" . PearDatabase::quote($start_date) . "' and deleted != 1");
			if ($row = $db->fetchByAssoc($result)) {
				if (0 < current($row))
					return;
			}
		}
		
		foreach ($this->users as $id => $user) {
			$focus = RowUpdate::blank_for_model('Forecast');

			$focus->set(array(
				'name' => $forecast_name,
				'user_id' => $id,
				'start_date' => $start_date,
				'end_date' => $end_date,
			));
			
			$this->fill_personal_forecast($focus, $start_date);
			
			$focus->save();
			unset($focus);
			// remove and archive old forecasts by user
		}

		foreach ($this->users as $id => $user) {
			$team = getTeamList($this->users, $id, true);
			$team[] = $id;
			
			$focus = RowUpdate::blank_for_model('Forecast');

			$focus->set(array(
				'name' => $forecast_name,
				'user_id' => $id,
				'start_date' => $start_date,
				'end_date' => $end_date,
			));
			
			$this->fill_team_individual($focus, $team, $start_date);
			
			$focus->save();
			unset($focus);
		}
		foreach ($this->users as $id => $user) {
			$focus = RowUpdate::blank_for_model('Forecast');
			
			$focus->set(array(
				'name' => $forecast_name,
				'user_id' => $id,
				'start_date' => $start_date,
				'end_date' => $end_date,
			));
			
			$this->fill_team_rollup($focus, $start_date);
			
			$focus->save();
			unset($focus);
		}
	}
	
	function update_user_team_forecasts($user_id, $period_id=null) {
		global $db;
		
		if($period_id !== null && ! Forecast::retrieve_by_period($user_id, $period_id))
			return;
		$updateFor = getReportsToList($this->users, $user_id);

		$result = null;
		if (!empty($updateFor)) {
			$lq = new ListQuery('Forecast', true);
			$lq->addSimpleFilter('user_id', $updateFor);
			if($period_id)
				$lq->addSimpleFilter('start_date', $period_id);
			$lq->addSimpleFilter('type', array('team_individual', 'team_rollup'));
			$lq->setOrderBy('type');
			$result = $lq->fetchAll();
		}
		
		$teams = array();

		if($result && ! $result->failed) {
			foreach($result->getRowIndexes() as $idx) {
				$base = $result->getRowResult($idx);
				$focus = RowUpdate::for_result($base);
				$uid = $focus->getField('user_id');
				$period = $focus->getField('start_date');
				if ($focus->getField('type') == 'team_individual') {
					if (!isset($teams[$uid])) {
						$teams[$uid] = getTeamList($this->users, $uid, true);
						if(! in_array($uid, $teams[$uid]))
							$teams[$uid][] = $uid;
					}
					$this->fill_team_individual($focus, $teams[$uid], $period);
				} else {
					$this->fill_team_rollup($focus, $period);
				}
				$focus->save();
				unset($focus);
			}
		}
	}
	
	function cleanup() {
		if(isset($this->users)) {
			SugarBean::cleanup_list($this->users);
			unset($this->users);
		}
	}

}

?>
