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


require_once 'data/SugarBean.php';

class Timesheet extends SugarBean {

    var $name;
    var $description;
    var $timesheet_period;
    var $date_starting;
    var $date_ending;
    var $status = 'draft';
    var $id;
    var $date_entered;
    var $date_modified;
    var $assigned_user_id;
    var $modified_user_id;
    var $created_by;
    var $deleted;
    
    // not stored
    var $assigned_user_name;
    var $modified_user_name;
    var $created_by_name;

    
    var $table_name = 'timesheets';
    var $object_name = 'Timesheet';
    var $module_dir = 'Timesheets';


    var $new_schema = true;

	function fill_in_additional_list_fields()
	{
		// Fill in the assigned_user_name
		$this->assigned_user_name = get_assigned_user_name($this->assigned_user_id);
	}
	
    function fill_in_additional_detail_fields()
    {
		$this->assigned_user_name = get_assigned_user_name($this->assigned_user_id);
		$this->modified_user_name = get_assigned_user_name($this->modified_user_id);
		$this->created_by_name = get_assigned_user_name($this->created_by);
    }

    function get_list_view_data() {
		global $app_list_strings;
		$the_array = parent::get_list_view_data();
		$the_array['TOTAL_HOURS'] = format_number($this->total_hours, 2, 2);
		return  $the_array;
	}

    function get_booked_hours()
    {
        require_once 'modules/Booking/BookedHours.php';
		$this->load_relationship('booked_hours');
		return $this->booked_hours->getBeans(new BookedHours);
    }

    function get_summary_text()
	{
		return $this->name;
	}

    function check_date_range($id, $user_id, $date_starting, $date_ending)
    {
        $query = sprintf("
            SELECT COUNT(*) AS n FROM timesheets WHERE assigned_user_id = '%s'
            AND id != '%s' AND date_starting >= '%s' AND date_ending <= '%s'
            AND deleted != 1",
            $this->db->quote($user_id),
            $this->db->quote($id),
            $this->db->quote($date_starting),
            $this->db->quote($date_ending)
        );
        $res = $this->db->query($query);
        $row = $this->db->fetchByAssoc($res);
        return $row['n'] == 0;
    }
    

    function mark_deleted($id)
    {
        require_once 'modules/Project/Project.php';
        $this->retrieve($id);
        //$lines = $this->get_lines();
        parent::mark_deleted($id);
        /*$this->db->query ("UPDATE timesheet_lines SET deleted = 1 WHERE timesheet_id='".PearDatabase::quote($id)."'");
        $projects = array();
        foreach ($lines as $line) $projects[$line->project_id] = true;
        $project = new Project;
        foreach ($projects as $pid => $unused) {
            if ($project->retrieve($pid)) {
                $project->get_financials();
                $project->financials->calculate_costs($project->use_timesheets);
                $project->financials->save();
            }
        }*/
    }

    
    function bean_implements($interface)
    {
        switch($interface) {
            case 'ACL':return true;
        }
        return false;
    }
	function create_export_query(&$order_by, &$where)
	{
		$custom_join = $this->custom_fields->getJOIN();
		$query = "SELECT timesheets.*, users.user_name AS assigned_user_name";
		if($custom_join) {
			$query .= $custom_join['select'];
		}
		$query .= " FROM timesheets LEFT JOIN users ON users.id=timesheets.assigned_user_id ";
		$where_auto = "timesheets.deleted=0";

		if($custom_join) {
			$query .= $custom_join['join'];
 		}

		if($where != "") {
			$query .= "WHERE $where AND " . $where_auto;
		} else {
			$query .= "WHERE ".$where_auto;
		}
		if($order_by != "") {
			$query .=  " ORDER BY ". $this->process_order_by($order_by, null);		
		} else {
			$query .= " ORDER BY timesheets.date_starting ";
		}
		return $query;
	}
	
	function get_search_user_options($param) {
		global $current_user;
		$sel_uid = array_get_default($param, 'value', '');
		if(is_admin($current_user))
			$all = get_user_array(false);
		else {
			global $locale;
			$all = array($current_user->id => $locale->getLocaleFormattedName($current_user->first_name, $current_user->last_name));
			foreach(get_direct_reports() as $u)
				$all[$u['id']] = $locale->getLocaleFormattedName($u['first_name'],$u['last_name']);
			asort($all);
		}
		return $all;
	}

    function getListForCurrentCalendarPeriod($user_ids, $cal_prev_date, $cal_next_date) {
        global $db;

        $sql = "SELECT `id`, `name` FROM ".$this->table_name."
            WHERE ('" . PearDatabase::quote($cal_next_date) . "' >= `date_starting`
            AND '" .PearDatabase::quote($cal_next_date). "' <= `date_ending`) ";

        if (!empty($user_ids)) {
            $str_ids = join("','", $user_ids);
            $sql .= "AND ( `assigned_user_id` IN ('{$str_ids}'))";
        }

        $sql .= "AND ( `deleted` = 0)";

        $res = $db->query($sql, true);
        $timesheetsList = array();

        while ($row = $db->fetchByAssoc($res)) {

            $timesheetsList[] = array(
                "id" => $row['id'],
                "name" => $row['name']
            );
        }

        return $timesheetsList;
    }

    function getDefaultForCurrentCalendarPeriod($user_id, $cal_next_date) {
        global $db;

        $sql = "SELECT `id` FROM ".$this->table_name."
            WHERE `assigned_user_id` = '" . PearDatabase::quote($user_id) . "'
            AND ('" . PearDatabase::quote($cal_next_date) . "' >= `date_starting`
            AND '" .PearDatabase::quote($cal_next_date). "' <= `date_ending`) ";

        $res = $db->query($sql, true);

        if ($row = $db->fetchByAssoc($res)) {
            return $row['id'];
        } else {
            return '';
        }
	}

	function getTotalsByWeek()
	{
		global $timedate, $app_list_strings;
        require_once 'modules/Booking/BookedHours.php';
		$hours_seed = new BookedHours();
		$start = $timedate->to_db_date($this->date_starting, false);
		$end = $timedate->to_db_date($this->date_ending, false);

		$hours = $hours_seed->query_hours(true, $this->id, $this->assigned_user_id, '', $start, $end, false);

		$weekStartDay = (int)array_get_default($company_info->settings, 'company_week_start_day', 0);
		if ($weekStartDay > 6) $weekStartDay = 0;
		$weekEndDay =  $weekStartDay - 1;
		if ($weekEndDay < 0) $weekEndDay = 6;

		$firstDay = $start;
		$lastDay = $end;

		$bydate = array();
		$hasWeekends = false;
		foreach ($hours as $i => $h) {
			$wd = date('w', strtotime($h['date_start']));
			if ($wd == 0 || $wd == 6) {
				$hasWeekends = true;
			}
            if (isset($bydate[$h['date_start']])) {
                $bydate[$h['date_start']] += $h['quantity'];
            } else {
                $bydate[$h['date_start']] = $h['quantity'];
            }
		}
		for(;;) {
			$wd = date('w', strtotime($firstDay));
			if ($wd == $weekStartDay) break;
			$firstDay = date('Y-m-d', strtotime($firstDay . ' - 1 day'));
		}
		
		for(;;) {
			$wd = date('w', strtotime($lastDay));
			if ($wd == $weekEndDay) break;
			$lastDay = date('Y-m-d', strtotime($lastDay . ' + 1 day'));
		}

		$date = $firstDay;
		$grid = array();
		$guard = 40;
		$wd = $weekStartDay;
		for (;;) {
			if (!--$guard) break;
			if ($wd == $weekStartDay) {

				$weekTitle = $timedate->to_display_date($date, false);
			}
			if ( ($wd != 0 && $wd != 6)  || $hasWeekends) {
				$weekDayName = $app_list_strings['weekdays_dom'][$wd];
				$grid[$weekTitle][$weekDayName] = array_get_default($bydate, $date, '');
			}
			$wd++;
			$wd %= 7;
			if ($date == $lastDay) break;
			$date = date('Y-m-d', strtotime($date . ' + 1 day'));
		}
		return $grid;
	}

	static function init_record(RowUpdate &$upd, $input) {
		$update = array();
        $date = null;
        if (isset($input['date']))
            $date = $input['date'];
        $timesheet_period = AppConfig::setting('company.timesheet_period');

		if (! $upd->getField('date_starting')) {
			$update['date_starting'] = Timesheet::startPeriod($timesheet_period, $date);
			$update['date_ending'] = Timesheet::endPeriod($timesheet_period, $date);
		}

		$update['timesheet_period'] = $timesheet_period;
        $upd->set($update);
    }

    static function set_name(RowUpdate $upd) {
        if (! $upd->getField('name')) {
            $name = self::formatName($upd->getField('assigned_user_id'), $upd->getField('date_starting'));
            $upd->set(array('name' => $name));
        }
    }

	static function startPeriod($period_type, $date = null) {
        switch ($period_type) {
            case 2:
                $weeknum = date('W', strtotime("+1 days"));
                $weekday = date('w');
                if (!($weeknum % 2)) $weekday += 7;
                return date('Y-m-d', strtotime("-$weekday days"));
            case 3:
                if (date('d') > 14) $day = 15;
                else $day = '01';
                return date('Y-m-'.$day);
            case 4:
                return date('Y-m-01');
            default:
                if ($date != null) {
                    $weekday = date('w', strtotime($date));
                    $start_date = date('Y-m-d', strtotime($date . " -$weekday days"));
                } else {
                    $weekday = date('w');
                    $start_date = date('Y-m-d', strtotime("-$weekday days"));
                }
                return $start_date;
        }
	}

	static function endPeriod($period_type, $date = null) {
		switch ($period_type) {
			case 2:
				$weeknum = gmdate('W', strtotime("+1 days"));
				$weekday = gmdate('w');
				if (!($weeknum % 2)) $weekday += 7;
				$weekday = 13 - $weekday;
				return gmdate('Y-m-d', strtotime("+$weekday days"));
			case 3:
				if (gmdate('d') <15) $day = '14';
				else $day = 't';
				return gmdate('Y-m-'.$day);
			case 4:
				return gmdate('Y-m-t');
			default:
				if ($date != null) {
					$weekday = 6 - gmdate('w', strtotime($date));
					$end_date = gmdate('Y-m-d', strtotime($date . " +$weekday days"));
				} else {
					$weekday = 6 - gmdate('w');
					$end_date = gmdate('Y-m-d', strtotime("+$weekday days"));                
				}
				return $end_date;
		}
	}

    static function formatName($user_id=null, $date_start=null) {
        // note: ensure date_start is database-formatted
        $date = strtotime($date_start);
        $date2 = strtotime($date_start .' +1 days');
        $year = date('Y', $date);
        switch (AppConfig::setting('company.timesheet_period')) {
            case 2:
                $period = ceil(date('W', $date2)/2);
                $marker = 'B';
                break;
            case 3:
                $period = (date('d', $date) == 1 ? 1 : 2) + (date('m', $date)-1)*2;
                $marker = 'S';
                break;
            case 4:
                $period = date('m', $date);
                $marker = 'M';
                break;
            default:
                $period = date('W', $date2);
                $marker = 'W';
                // ISO8601 year - PHP 5.1 has date('o'), but we're staying 5.0-compatible
                if($period == 1) {
                    $year2 = date('Y', $date + 6 * 24 * 3600);
                    $year = max($year, $year2);
                }
                break;
        }

        $user = ListQuery::quick_fetch_row('User', $user_id, array('first_name', 'last_name'));
        if ($user)
            $name = sprintf('%04d-%s%02d: %s %s', $year, $marker, $period, $user['first_name'], $user['last_name']);

        return $name;
    }
}
?>