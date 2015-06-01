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



require_once('data/SugarBean.php');
require_once('include/utils.php');

define('DAY_LENGTH', 3600*24);

/*
function pull_pending(&$arr, $member, &$val) {
	if(!array_key_exists($member, $arr))
		return null;
	$succ = false;
	if(count($arr[$member])) {
		$val = array_shift($arr[$member]);
		$succ = true;
	}
	if(!count($arr[$member]))
		unset($arr[$member]);
	return $succ;
}*/

function split_int_list($val) {
	$vals = array();
	foreach(explode(',', $val) as $v)
		if(is_numeric($v))
			$vals[] = $v;
	$vals = array_unique($vals);
	sort($vals);
	return $vals;
}

function iso_year_week_info($wkst, $year) {
	$start = gmmktime(0, 0, 0, 1, 1, $year);
	$wd_start = gmdate('w', $start);
	$first_day_offset = $wkst - $wd_start;
	if($first_day_offset < -3) $first_day_offset += 7;
	else if($first_day_offset > 3) $first_day_offset -= 7;
	
	//if($wkst > $wd_start) $first_day_offset = -$first_day_offset;
	//if($first_day_offset > 3) $first_day_offset -= 7;
	//else if($first_day_offset < -3) $first_day_offset += 7;
	$num_days = ($year % 4 == 0 && ($year % 100 != 0 || $year % 400 == 0)) ? 366 : 365;
	$num_weeks = round(($num_days - $first_day_offset) / 7);
	$iso_year_start = $start + ($first_day_offset * DAY_LENGTH);
	$iso_year_end = $iso_year_start + ($num_weeks * 7) * DAY_LENGTH;
	$info = compact('first_day_offset', 'num_days', 'num_weeks', 'iso_year_start', 'iso_year_end');
	return $info;
}

function iso_week_for_day($wkst, $dt) {
	list($year, $year_day) = explode('|', gmdate('Y|z', $dt));
	$week_info = iso_year_week_info($wkst, $year);
	if($week_info['iso_year_start'] > $dt) {
		$iso_year = $year - 1;
		$week_info = iso_year_week_info($wkst, $iso_year);
		$iso_week = $week_info['num_weeks'];
	}
	else if($week_info['iso_year_end'] <= $dt) {
		$iso_year = $year + 1;
		$iso_week = 1;
	}
	else {
		$iso_year = $year;
		$iso_week = (int)floor(($year_day - $week_info['first_day_offset']) / 7) + 1;
	}
	$info = compact('iso_year', 'iso_week');
	return $info;
}

/**
 *
 */
class RecurrenceRule extends SugarBean {
	// database table columns
	var $id;
	var $date_entered;
	var $date_modified;
	var $deleted;
	
	var $freq;
	var $freq_interval = '1';
	var $until;
	var $limit_count;
	var $rule;
	var $is_restriction;
	var $instance_count;
	var $date_last_instance;
	var $forward_times;

	var $parent_type;
	var $parent_id;
	
	// runtime-only
	var $updated = false;
	var $parent_name;
	var $times;

	var $required_fields = array('freq'=>1, );

	var $object_name = 'RecurrenceRule';
	var $module_dir = 'Recurrence';
	var $new_schema = true;
	var $table_name = 'recurrence_rules';

	var $column_fields = array(
		'id',
		'date_entered',
		'date_modified',
		'parent_type',
		'parent_id',
		'freq',
		'freq_interval',
		'until',
		'limit_count',
		'rule',
		'is_restriction',
		'instance_count',
		'date_last_instance',
		'deleted',
	);

	var $list_fields = array(
		'id',
		'freq',
		'freq_interval',
		'limit_count',
		'until',
		'is_restriction',
		'rule',
	);

	var $additional_column_fields = Array();


	function RecurrenceRule()
	{
		parent::SugarBean();

	}
	
	function toString() {
		return "RRULE:FREQ={$this->freq};{$this->rule}";
	}
	
	function &retrieve_by_parent($ptype, $pid, $filter_expired=false) {
		$rules = array();
		if(empty($pid))
			return $rules;
		$query = "SELECT * FROM `$this->table_name`"
			. " WHERE parent_type='". $this->db->quote($ptype) ."' AND parent_id='". $pid ."'"
			. ($filter_expired ? ' AND (limit_count IS NULL OR limit_count = 0 OR IFNULL(instance_count,0) < limit_count) ' : '')
			. " AND NOT deleted"
			. " ORDER BY date_entered";
		$result = $this->db->query($query, true, "Error retrieving recurrence rules");
		while($row = $this->db->fetchByAssoc($result)) {
			$rule = new RecurrenceRule();
			$rule->populateFromRow($row);
			if(preg_match('/^[0\-: ]+$/', $rule->until))
				$rule->until = '';
			$rule->check_date_relationships_load();
			$rules[] = $rule;
		}
		return $rules;
	}
	
	function is_recurring($module, $id) {
		$query = "SELECT count(*) as c FROM `$this->table_name`"
			. " WHERE parent_type='". $module ."' AND parent_id='". $id ."'"
			. " AND NOT deleted";
		$result = $this->db->query($query, true, "Error retrieving recurrence information");

		if(! ($row = $this->db->fetchByAssoc($result)))
			return false;

		return $row['c'] > 0;
	}
	
	function rules_to_JSON(&$rules) {
		require_once('include/JSON.php');
		$json = new JSON(JSON_LOOSE_TYPE);
		$ruleset = array();
		require_once('include/TimeDate.php');
		$timedate = new TimeDate();
		foreach($rules as $rule) {
			$jsrule = array(
				'id' => $rule->id,
				'freq' => $rule->freq,
				'interval' => $rule->freq_interval,
				'count' => $rule->limit_count,
				'until' => $timedate->to_db($rule->until),
				'is_restriction' => $rule->is_restriction,
			);
			$ruleset[] = array_merge($jsrule, $this->split_rule($rule->rule));
		}
		$ret = '[]';
		if(count($ruleset))
			$ret = $json->encode($ruleset);
		return $ret;
	}
	
	function update_rules_from_JSON($ptype, $pid, $jsobj, $save=false) {
		global $timedate;
		require_once('include/JSON.php');
		$json = new JSON(JSON_LOOSE_TYPE);
		$data = $json->decode($jsobj);
		$idx_by_id = array();
		$removed = array();
		$rules = $this->retrieve_by_parent($ptype, $pid);
		for(reset($rules); ($idx = key($rules)) !== null; next($rules)) {
			$idx_by_id[$rules[$idx]->id] = $idx;
			$removed[$rules[$idx]->id] = true;
		}
		$field_map = array(
			'id' => 'id',
			'freq' => 'freq',
			'interval' => 'freq_interval',
			'until' => 'until',
			'count' => 'limit_count',
			'is_restriction' => 'is_restriction',
		);
		$allNew = true;
		$hasUpdated = false;
		if(is_array($data))
		foreach($data as $update) {
			if(empty($update['id']) || ! isset($update['id'], $idx_by_id)) {
				$rule_idx = count($rules);
				$rules[] = new RecurrenceRule();
				$rules[$rule_idx]->parent_type = $ptype;
				$rules[$rule_idx]->parent_id = $pid;
			}
			else {
				$allNew = false;
				$rule_idx = $idx_by_id[$update['id']];
				$removed[$update['id']] = false;
			}
			$rule =& $rules[$rule_idx];
			foreach($field_map as $from=>$to) {
				if(!array_key_exists($from, $update))
					continue;
				if(!isset($rule->$to) || $rule->$to != $update[$from]) {
					$hasUpdated = true;
				}
				$rule->$to = (string)$update[$from]; // NULL values would not be updated
				unset($update[$from]);
				if($from == 'until' && $rule->until != '')
					$rule->until = $timedate->to_display_date_time($rule->until, true, false);
			}
			ksort($update);
			$new_rule = $this->join_rule($update);
			if(!isset($rule->rule) || $rule->rule != $new_rule) {
				$hasUpdated = true;
			}
			$rule->rule = $new_rule;
		}
		foreach($removed as $id=>$gone)
			if($gone) {
				$rules[$idx_by_id[$id]]->deleted = 1;
				$hasUpdated = true;
			}
		if($save)
			$this->save_rules($rules);
		return $hasUpdated && !$allNew;
	}
	
	function save_rules(&$rules) {
		for(reset($rules); ($k = key($rules)) !== null; next($rules)) {
			$rules[$k]->save();
		}
	}
	
	function split_rule($rule) {
		$parts = array();
		foreach(explode(';', $rule) as $p)
			if(preg_match('/([a-zA-Z]+)=(.*)/', $p, $r))
				$parts[strtolower($r[1])] = $r[2];
		return $parts;
	}
	
	function join_rule($rule) {
		$parts = array();
		foreach($rule as $k=>$v) {;
			if(isset($v) && $v !== '')
				$parts[] = strtoupper($k)."=$v";
		}
		return implode(';', $parts);
	}
	
	// static method. enum_from inclusive, enum_to exclusive
	// called for daily, monthly, or yearly frequency (when byweekno is not set)
	function &enum_days_monthly($dtstart, $enum_from, $enum_to, $freq, $interval, $rule) {
		// need to fix interval
		$days = array();
		if($enum_to < $enum_from || $enum_to <= $dtstart)
			return $days;

		extract($rule);
		if(isset($bymonth)) {
			$bymonths = array();
			foreach(split_int_list($bymonth) as $m)
				if($m >= 1 && $m <= 12)
					$bymonths[] = $m;
		}
		else if($freq == 'YEARLY')
			$bymonths = array(gmdate('n', $dtstart));
		
		if(isset($byyearday))
			$byyeardays = split_int_list($byyearday);

		if(isset($bymonthday))
			$bymonthdays = split_int_list($bymonthday);
		else if($freq != 'DAILY' && !isset($byday))
			$bymonthdays = array(gmdate('j', $dtstart));
		
		$weekday_short = array('SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA');
		if(isset($byday)) {
			$bydays = array();
			foreach(explode(',', $byday) as $bd) {
				if(preg_match('/(-?[0-9]+)?(SU|MO|TU|WE|TH|FR|SA)/', $bd, $match))
					$bydays[$match[1]][array_search($match[2], $weekday_short)] = 1;
			}
		}

		list($start_year, $start_month, $start_yearday) = explode('|', gmdate("Y|n|z", $dtstart));
		list($from_year, $from_month, $from_yearday) = explode('|', gmdate('Y|n|z', $enum_from));
		if($freq == 'YEARLY')
			$ival_pos = ($from_year - $start_year) % $interval;
		else if($freq == 'MONTHLY')
			$ival_pos = (($from_year - $start_year)*12 + ($start_month < $from_month ? 1 : -1) * ($from_month - $start_month)) % $interval;
		else { // DAILY
			$from_days = floor($enum_from / DAY_LENGTH / 60);
			$start_days = floor($dtstart / DAY_LENGTH / 60);
			$ival_pos = ($from_days - $start_days) % $interval;
		}

		$to_year = (int)gmdate('Y', $enum_to);
		$dtstart_time = $dtstart % DAY_LENGTH;
		$from_time = $enum_from % DAY_LENGTH;
		
		for($year = $from_year; $year <= $to_year; $year++) {
			$year_num_days = ($year % 4 == 0 && ($year % 100 != 0 || $year % 400 == 0)) ? 366 : 365;
			if(isset($byyeardays)) {
				$year_days = array();
				foreach($byyeardays as $yd)
					$year_days[] = ($yd < 0 ? $year_num_days + $yd + 1: $yd);
			}
			else	unset($year_days);
			
			if($freq == 'YEARLY' && isset($bydays)) {
				// NOTE: if freq=YEARLY, we could have BYDAY=20MO (twentieth monday in the year) - not handled
			}
			
			for($m = ($year == $from_year ? $from_month : 1); $m <= 12; $m++) {
				
				if(isset($bymonths) && !in_array($m, $bymonths)) {
					if($freq == 'MONTHLY')
						$ival_pos = ($ival_pos + 1) % $interval;
					continue;
				}
				
				$month_start = gmmktime(0, 0, 0, $m, 1, $year);
				list($yearday, $weekday, $num_days) = explode('|', gmdate("z|w|t", $month_start));
				
				if(isset($bymonthdays)) {
					foreach($bymonthdays as $md) {
						$md = $md < 0 ? $num_days + $md + 1 : $md;
						if($md > $num_days || $md < 1)
							continue;
						$month_days[] = $md;
					}
					$month_days = array_unique($month_days);
					sort($month_days);
				}
				else	unset($month_days);
				
				if(($freq == 'DAILY' || $freq == 'MONTHLY' || ($freq == 'YEARLY' && isset($bymonths))) && isset($bydays)) {
					$count_by_wd = array();
					$month_bydays = array();
					for($i = 0; $i < 7; $i++)
						$count_by_wd[($weekday + $i) % 7] = (int)floor(($num_days - $i  - 1) / 7) + 1;
					foreach($bydays as $pos => $wdays) {
						foreach(array_keys($wdays) as $wd) {
							if(is_numeric($pos) && $pos < 0) {
								$pos = $count_by_wd[$wd] + $pos + 1;
								if($pos < 1) continue;
							}
							$daynum = $wd - $weekday + 1;
							if($daynum < 1) $daynum += 7;
							if($pos == '')
								for($i = 0; $i < $count_by_wd[$wd]; $i++) {
									$month_bydays[] = $daynum;
									$daynum += 7;
								}
							else
								$month_bydays[] = $daynum + 7 * ($pos-1);
						}
					}
					$month_bydays = array_unique($month_bydays);
					sort($month_bydays);
				}
				else	unset($month_bydays);

				$day_start = $month_start;
				for($md = 1; $md <= $num_days; $md++) {
					$dt = $day_start + $dtstart_time;
					if( $dt >= $enum_from
					    && (!isset($year_days) || in_array($yearday, $year_days))
					    && (!isset($month_days) || in_array($md, $month_days))
					    && (!isset($month_bydays) || in_array($md, $month_bydays))
					) {
						if($dt >= $enum_to)
							return $days;

						if($ival_pos == 0) {
							//$GLOBALS['log']->fatal('<br>'.$this->timestamp_to_display_date($dt).'<br>');
							$days[] = $dt;
						}
						if($freq == 'DAILY')
							$ival_pos = ($ival_pos + 1) % $interval;
					}
					$day_start += DAY_LENGTH;
					$yearday++;
				}
				
				if($freq == 'MONTHLY')
					$ival_pos = ($ival_pos + 1) % $interval;
			}
			
			if($freq == 'YEARLY')
				$ival_pos = ($ival_pos + 1) % $interval;
		}
		return $days;
	}

	// called for weekly frequency, or yearly when byweekno is set
	function &enum_days_weekly($dtstart, $enum_from, $enum_to, $freq, $interval, $rule) {
		$days = array();
		if($enum_to < $enum_from || $enum_to <= $dtstart /* || $count < 1*/)
			return $days;

		extract($rule);
		if(isset($bymonth)) {
			$bymonths = array();
			foreach(split_int_list($bymonth) as $m)
				if($m >= 1 && $m <= 12)
					$bymonths[] = $m;
		}
		
		if(isset($byyearday))
			$byyeardays = split_int_list($byyearday);

		if(isset($bymonthday))
			$bymonthdays = split_int_list($bymonthday);
		
		if(isset($byweekno))
			$byweeknos = split_int_list($byweekno);
		
		$weekday_short = array('SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA');
		if(isset($byday)) {
			$bydays = array();
			foreach(explode(',', $byday) as $bd) {
				if(preg_match('/(-?[0-9]+)?(SU|MO|TU|WE|TH|FR|SA)/', $bd, $match))
					$bydays[$match[1]][array_search($match[2], $weekday_short)] = 1;
			}
		}
		
		if(!isset($wkst) || !in_array($wkst, $weekday_short))
			$wkst = 'MO';
		$wkst_no = array_search($wkst, $weekday_short);
		
		if($freq == 'YEARLY') {
			list($start_year, $start_month, $start_yearday, $start_weekday) = explode('|', gmdate("Y|n|z|w", $dtstart));
			list($from_year, $from_month, $from_yearday) = explode('|', gmdate('Y|n|z', $enum_from));
			if(!isset($bydays))
				$bydays = array(); // ?
			$ival_pos = ($from_year - $start_year) % $interval;
			$to_year = (int)gmdate('Y', $enum_to);
		}
		else { // WEEKLY
			if(!isset($bydays))
				$bydays = array('' => array($start_weekday => 1));
			$ival_pos = 0;
			list($start_year, $start_weekno) = array_values(iso_week_for_day($wkst_no, $dtstart));
			list($from_year, $from_weekno) = array_values(iso_week_for_day($wkst_no, $enum_from));
			for($year = $start_year; $year < $from_year; $year++) {
				$year_week_info = iso_year_week_info($wkst_no, $year);
				$ival_pos = ($ival_pos + $year_week_info['num_weeks']) % $interval;
			}
			$ival_pos = ($ival_pos + ($start_weekno < $from_weekno ? 1 : -1) * ($from_weekno - $start_weekno)) % $interval;
			list($to_year,) = array_values(iso_week_for_day($wkst_no, $enum_to));
		}
		
		$dtstart_time = $dtstart % DAY_LENGTH;
		
		for($year = $from_year; $year <= $to_year; $year++) {
			$year_week_info = iso_year_week_info($wkst_no, $year);
			$num_weeks = $year_week_info['num_weeks'];

			if(isset($byyeardays)) {
				$year_days = array();
				foreach($byyeardays as $yd)
					$year_days[] = ($yd < 0 ? $year_num_days + $yd + 1: $yd);
			}
			else	unset($year_days);
			
			if(isset($byweeknos)) {
				$year_weeknos = array();
				foreach($byweeknos as $wn)
					$year_weeknos[] = ($wn < 0 ? $num_weeks + $wn + 1: $wn);
			}
			else	unset($year_weeknos);
			
			$year_bydays = array();
			foreach($bydays as $pos => $wdays) {
				foreach(array_keys($wdays) as $wd) {
					if(is_numeric($pos) && $pos < 0) {
						$pos = $num_weeks + $pos + 1;
						if($pos < 1) continue;
					}
					$year_bydays[$pos][$wd] = 1;
				}
			}
			
			$day_start = $year_week_info['iso_year_start'];
			$yearday = $year_week_info['first_day_offset'];
			for($weekno = 1; $weekno <= $num_weeks; $weekno++) {
			
				if($year == $from_year && $weekno < $from_weekno) {
					$day_start += 7 * DAY_LENGTH;
					$yearday += 7;
					continue;
				}

				if(isset($year_weeknos) && !in_array($weekno, $year_weeknos)) {
					$day_start += 7 * DAY_LENGTH;
					$yearday += 7;
					if($freq == 'WEEKLY')
						$ival_pos = ($ival_pos + 1) % $interval;
					continue;
				}
				
				for($wdi = 0; $wdi < 7; $wdi++) {
					$wd = ($wkst_no + $wdi) % 7;
					$dt = $day_start + $dtstart_time;

					if($dt >= $enum_to)
						return $days;

					$bymonthcheck = true;
					// FIXME

					if( $dt >= $enum_from
					    && (!isset($year_days) || in_array($yearday, $year_days))
					    && ((!empty($year_bydays['']) && !empty($year_bydays[''][$wd]))
						|| (!empty($year_bydays[$weekno]) && !empty($year_bydays[$weekno][$wd])) )
					    && $bymonthcheck)
					{
						if($ival_pos == 0)
							$days[] = $dt;
					}
					
					$day_start += DAY_LENGTH;
					$yearday ++;
				}
				
				if($freq == 'WEEKLY')
					$ival_pos = ($ival_pos + 1) % $interval;
			}
			if($freq == 'YEARLY')
				$ival_pos = ($ival_pos + 1) % $interval;
		}
		
		return $days;
	}
	
	
	// $dtstart is the beginning of the recurrence
	// $enum_from and $enum_to define the range of time we're interested in
	// must be passed in seconds from unix epoch UTC
	function get_recurrence_times($dtstart, $enum_from, $enum_to, $max_count) {
		global $timedate;
		$parts = $this->split_rule($this->rule);
		$count = $this->limit_count;
		if($count === '' || $count > $max_count)
			$count = $max_count;
		if($this->until != '' && !preg_match('/[0\\-: ]/', $this->until)) {
			$until = $this->date_to_ts($this->until);
			if($until < $enum_to)
				$enum_to = $until+1;
		}
		// debug code
		/*for($year = 1995; $year < 2000; $year++) {
			print $year.'<br>';
			$info = iso_year_week_info(1, $year);
			print_r(iso_year_week_info(1, $year)); print '<br>';
			print gmdate('Y-m-d H:i:s', $info['iso_year_start']) .'<br>';
			print gmdate('Y-m-d H:i:s', $info['iso_year_end']) .'<br>';
			print '<blockquote>';
			for($d = -9; $d < 10; $d++) {
				$dt = gmmktime(0,0,0, 1, 1, $year);
				print_r(iso_week_for_day(1, $dt + $d * DAY_LENGTH));
				if($d == 0) $GLOBALS['log']->fatal(' &lt;-');
				$GLOBALS['log']->fatal('<br>');
			}
			print '</blockquote>';
			print '<br>';
		}*/
		/*
		for($d = -9; $d < 10; $d++) {
			$dt = gmmktime(0,0,0, 1, 1, 1994);
			print_r(iso_week_for_day(1, $dt + $d * DAY_LENGTH));
			if($d == 0) $GLOBALS['log']->fatal(' &lt;-');
			$GLOBALS['log']->fatal('<br>');
		}*/
		if($this->freq == 'WEEKLY' || ($this->freq == 'YEARLY' && !empty($parts['byweekno'])))
			$fn = 'enum_days_weekly';
		else
			$fn = 'enum_days_monthly';
		$days = $this->$fn($dtstart, $enum_from, $enum_to,
			$this->freq, $this->freq_interval, $parts);
		if($max_count)
			$days = array_slice($days, 0, $max_count);
		return $days;
	}
	
	function date_to_timestamp($date, $time='') {
		global $timedate;
		if($time != '')
			$date = $timedate->merge_date_time($date, $time);
		$dt = $timedate->to_db($date);
		return strtotime($dt."Z");
	}
	
	function timestamp_to_display_date($date) {
		global $timedate;
		$dt = gmdate($timedate->get_db_date_time_format(), $date);
		return $timedate->to_display_date_time($dt);
	}

	function _fill_in_additional_parent_fields()
	{
		global $app_strings;

		switch($this->parent_type)
		{
			case 'Meetings':
				require_once("modules/Meetings/Meeting.php");
				$parent = new Meeting();
				break;
		}

		if(!empty($parent))
		{
			$parent->retrieve($this->parent_id);
			$this->parent_name = $parent->name;
			$parent->cleanup();
		}
	}

	function updateInstances(RowUpdate $upd, $type = 'all')
	{
		if ($type != 'all')
			return;

		if (! ($id = $upd->getPrimaryKeyValue()) )
			return;
		

		if (! $upd->getField('recurrence_of_id')) {
			$rec_id = $id;
		} else {
			$rec_id = $upd->getField('recurrence_of_id');
		}

        $bean = new $upd->model_name;
		$date_field = $bean->get_recurrence_date_field();
		
		$update = array();
		foreach ($bean->recur_chain_fields as $f) {
			$update[] = "`$f` = '" . $this->db->quote($upd->getField($f)) . "'";
		}

		$where = "(recurrence_of_id = '$rec_id' OR id = '$rec_id') AND id !='$id'";
		$query = "UPDATE {$upd->model->table_name} SET " . join(', ', $update) . " WHERE $where";
		$this->db->query($query, true);
    }


	function deleteInstances(RowUpdate $upd, $type = 'this')
	{

		$id = $upd->getPrimaryKeyValue();

		if (! $upd->getField('recurrence_of_id')) {
			$rec_id = $id;
		} else {
			$rec_id = $upd->getField('recurrence_of_id');
		}

		if ($type == 'this' && $rec_id != $id)
			return;

		$module = $upd->model->getModuleDir();
        if (is_array($module))
            $module = $module[0];

		$now = gmdate('Y-m-d H:i:s');
		$where = "(recurrence_of_id = '$rec_id' OR id = '$rec_id') AND id !='$id'";
		$query = "UPDATE {$upd->model->table_name} SET deleted=1, date_modified='$now' WHERE $where";
		$this->db->query($query, true);

		$query = "UPDATE recurrence_rules SET deleted=1, date_modified='$now' WHERE parent_type = '{$module}' AND parent_id='$rec_id'";
		$this->db->query($query, true);
	}
}

?>
