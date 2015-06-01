<?php
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/******************************************************************************
* The contents of this file are subject to the CareBrains Software End User
* License Agreement ('License') which can be viewed at
* http://www.sugarforum.jp/download/cbieula.shtml
* By installing or using this file, You have unconditionally agreed to the
* terms and conditions of the License, and You may not use this file except in
* compliance with the License.  Under the terms of the license, You shall not,
* among other things: 1) sublicense, resell, rent, lease, redistribute, assign
* or otherwise transfer Your rights to the Software, and 2) use the Software
* for timesharing or service bureau purposes such as hosting the Software for
* commercial gain and/or for the benefit of a third party.  Use of the Software
* may be subject to applicable fees and any use of the Software without first
* paying applicable fees is strictly prohibited.
* Your Warranty, Limitations of liability and Indemnity are expressly stated
* in the License.  Please refer to the License for the specific language
* governing these rights and limitations under the License.
*****************************************************************************/
global $current_language;

require_once 'modules/Calendar/utils.php';
load_holidays();

include_once ("modules/Calendar/language/lunacalendar.php");
global $sugar_flavor;
if ($sugar_flavor =="PRO") {
	require_once('modules/Calendar/ExtClassForCalendar/TeamEx.php');
}
require_once('modules/Calendar/ExtClassForCalendar/UserEx.php');
require_once('modules/Calendar/ExtClassForCalendar/MeetingEx.php');
require_once('modules/Calendar/ExtClassForCalendar/CallEx.php');

define('CAL_HEIGHT_PER_HOUR', 40);
define('CAL_HEIGHT_TUNE_OFFSET', -2);

define('CAL_DAY_LEFT_OFFSET', 50);
define('CAL_DAY_ACTIVITY_WIDTH', 200);
define('CAL_DAY_WIDTH', 475);
define('CAL_DUP_OFFSET', 42);
define('CAL_MIN_DUP_OFFSET', 18);

class CalendarDataUtil {

	function CalendarDataUtil() {

	}

	function param($name, $value = null)
	{
		static $params = array();
		if (!is_null($value)) {
			$params[$name] = $value;
		}

		return isset($params[$name]) ? $params[$name] : null;
	}

	//static function
	function getLocalToDay() {
		global $timedate;
		$now = $timedate->to_display_date_time(gmdate("Y-m-d H:i:s"));
		return $timedate->to_display($now, $timedate->get_date_time_format(), 'Y-m-d');
	}

	//static function
	function getLocalCurrentHour() {
		global $timedate;
		$now = $timedate->to_display_date_time(gmdate("Y-m-d H:i:s"));
		return $timedate->to_display($now, $timedate->get_date_time_format(), 'H');
	}

	//static function
	function getHourArray() {
		global $current_user;
		$startHour = $current_user->day_begin_hour;
		$endHour = $current_user->day_end_hour;
		$dateFormats = CalendarDateTime::userDateTimeFormats();
		if(empty($startHour) && empty($endHour)) {
			$startHour = 9;
			$endHour = 18;
		}
		$ret = array();
		for ($i = $startHour; $i < $endHour; $i++) {
			if($i == $startHour || $i == 12 || $i == 24 || $i == $endHour-1) {
				$merid = $dateFormats['meridiem'];
				$fmt = $merid ? $dateFormats['time_hour'] . $merid : $dateFormats['time_no_meridiem'];
			} else
				$fmt = $dateFormats['time_no_meridiem'];
			$ret[$i] = array('display' => date($fmt, strtotime(sprintf("%02d:00", $i))));
		}
		return $ret;
	}
	
	function formatActivityTimes($startDT, $endTS=null) {
		$showStartMinutes = ($startDT->localMin != 0 || ! isset($endTS));
		$dateFormats = CalendarDateTime::userDateTimeFormats();
		if(isset($endTS)) {
			$duration = $endTS - $startDT->localDateTime_t;
			$showEndMinutes = (($startDT->localMin + $duration / 60) % 60 != 0);
			$max_dur = ((12 - $startDT->localHour % 12) * 60 - $startDT->localMin) * 60;
			$endFmt = $showEndMinutes
						? $dateFormats['time_no_zero']
						: $dateFormats['time_hour'] . $dateFormats['meridiem'];
			$endStr = date($endFmt, $endTS);
			$showStartMerid = ($duration >= $max_dur);
		} else {
			$endStr = '';
			$showStartMerid = true;
		}
		$startFmt = $showStartMerid
					? ($showStartMinutes
						? $dateFormats['time_no_zero']
						: $dateFormats['time_hour'] . $dateFormats['meridiem'])
					: ($showStartMinutes
						? $dateFormats['time_no_meridiem']
						: $dateFormats['time_hour']);
		return array(date($startFmt, $startDT->localDateTime_t), $endStr);
	}

	//static function
	function getWorkHourArray($format = "", $isPre7 = false, $isAfter22 = false) {
		global $current_user;

		$result = array ();

		$start = isset($current_user->day_begin_hour) ? $current_user->day_begin_hour : 7;
		$end = isset($current_user->day_end_hour) ? $current_user->day_end_hour : 22;
		
		if(empty($start) && empty($end)) {
			$start = 9;
			$end = 18;
		}

		if ($isPre7) {
			$start = 0;
		}
		if ($isAfter22) {
			$end = 24;
		}

		if($format === 'user' || $format === 'user_short') {
			$dateFormats = CalendarDateTime::userDateTimeFormats();		
			for ($i = $start; $i < $end; $i++) {
				if($format == 'user_short') {
					$fmt = $dateFormats['time_hour'];
					//if(($i == $start || $i == $end-1) && ($i != 12 && $i != 24))
					//	$fmt .= $dateFormats['meridiem'];
				} else if($i == $start || $i == 12 || $i == 24 || $i == $end-1) {
					$merid = $dateFormats['meridiem'];
					$fmt = $merid ? $dateFormats['time_hour'] . $merid : $dateFormats['time_no_meridiem'];
				} else
					$fmt = $dateFormats['time_no_meridiem'];
				$result[$i] = array (
					'display' => date($fmt, strtotime(sprintf("%02d:00", $i)))
				);
			}			
		} else if (!empty ($format)) {
			for ($i = $start; $i < $end; $i++) {
				$result[$i] = array (
					'display' => sprintf($format,
					$i
				));
			}
		} else {
			for ($i = $start; $i < $end; $i++) {
				$result[$i] = array (
					'display' => $i
				);
			}
		}
		return $result;
	}

	//static function
	function getWeekDays($calDateTime, $checkMonth=null, $weekends=true) {
		global $current_language, $app_list_strings;

		$localToDay = CalendarDataUtil :: getLocalToDay();
		$lang = return_module_language($current_language, 'Calendar');
		$baseDate_t = strtotime($calDateTime->localWeekFirstDate);
		$dateFormats = CalendarDateTime::userDateTimeFormats();

		$weekDays = array ();
		$i = $calDateTime->systemWeekFirstDay - 1;
		for ($idx = 0; $idx < 7; $idx++) {
			$i++;
			if ($i > 6) {
				$i = 0;
			}
			if(! $weekends && ($i == 0 || $i == 6))
				continue;
			$timestamp = strtotime("+{$idx} day", $baseDate_t);
			$targetDate = date("Y-m-d", $timestamp);

			$holiday = "";
			$rokuyou = "";

			$matches = array ();
			$selMonth = false;
			if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $targetDate, $matches) > 0) {
				$Year = $matches[1];
				$Month = $matches[2];
				$Day = $matches[3];

				if (function_exists('holiday')) {
					$holiday = holiday($Year, $Month, $Day);
					if ($current_language == "ja") {
						$rokuyou = get_rokuyou($Year, $Month, $Day);
					}
				}
				if(isset($checkMonth) && $Month == $checkMonth)
					$selMonth = true;
			}

			if ($i == 0)
				$colClass = "sunday";
			else if ($i == 6)
				$colClass = "saturday";
			else
				$colClass = "workday";
			$weekdayCssClass = " week_day $colClass";
			
			$dateCssClass = ' '.$colClass;
			if ($holiday != "")
				$dateCssClass .= " holiday";
			if ( ($isToday = ($targetDate == $localToDay)) )
				$dateCssClass .= " today";
			if(isset($checkMonth) && ! $selMonth)
				$dateCssClass .= " outside_month";

			$weekDays[$i] = array (
				'weekDayIndex' => $i,
				'date' => $targetDate,
				'day' => ltrim($Day, '0'),
				'date_short' => date($dateFormats['date_short'], $timestamp), //custom_strftime($lang['LBL_FORMAT_SHORT_DATE'], $gmt_timestamp),
				'weekday' => $app_list_strings['weekdays_dom'][$i],
				'holiday' => $holiday,
				'rokuyo' => $rokuyou,
				'dateCssClass' => $dateCssClass,
				'weekdayCssClass' => $weekdayCssClass,
				'isToday' => $isToday
			);
		}


		return $weekDays;
	}

	//static function
	function getWeekDayFontCss() {
		return array (
			0 => 'week_day sunday',
			1 => 'week_day workday',
			2 => 'week_day workday',
			3 => 'week_day workday',
			4 => 'week_day workday',
			5 => 'week_day workday',
			6 => 'week_day saturday',
		);
	}

	function getRecurrenceFieldName()
	{
		if ($GLOBALS['sugar_flavor'] == 'IAH') {
			return 'recurrence_of_id';
		} else {
			return 'recurrence_id';
		}
	}

	function isModuleEnabled($module, $access)
	{
		return !ACLController::moduleSupportsACL($module) || ACLController::checkAccess($module, $access, true);
	}

	//static function
	function getLeaveOnDayByUserSql(&$calDateTime, $userIds) {
		global $current_user;

		$ret_query = "SELECT m.id AS id, "
			. "m.assigned_user_id, "
			. "1 AS is_daylong, "
			. "0 as is_private, "
			. "m.days AS name, "
			. "(TO_DAYS(m.date_end) - TO_DAYS(m.date_start) ) * 24 + 1 AS duration_hours, "
			. "0 AS duration_minutes, "
			. "m.date_start AS date_start, "
			. "'' AS time_start, "
			. "'' AS recurrence_id, "
			. "'Vacations' AS module, "
            . "'' AS calendar_color, "
            . "'' AS status_color, "
			. "1 AS is_publish, ";
		$ret_query .= "'accept' AS accept_status "
			. "FROM vacations m "
			. "WHERE m.deleted=0 AND m.leave_type='vacation' AND m.status IN('planned', 'approved')  "
			//. "AND (m.date_start <= '{$calDateTime->gmtNextDate}' AND m.date_end >= '{$calDateTime->gmtPrevDate}') "
            . "AND ('{$calDateTime->gmtDate}' BETWEEN m.date_start AND m.date_end) ";

		$strIds = join("','", $userIds);
		if (!empty($userIds)) {
			$ret_query .= "AND m.assigned_user_id IN ('{$strIds}') ";
		}

		$ret_query .= CalendarDataUtil::ownerWhere('Vacations');

		return $ret_query;
	}
	
	function getMeetingsOnDayByUserSql(&$calDateTime, $userIds) {
        global $timedate;
        $ret_query = "SELECT m.id AS id, "
			. "m.assigned_user_id, "
			. "m.is_daylong, "
			. "m.is_private, "
			. "m.name AS name, "
            . "0 AS duration_hours, "
            . "m.duration AS duration_minutes, "
			. "DATE(m.date_start) AS date_start, "
            . "TIME(m.date_start) AS time_start, "
            . "IFNULL("
            	. "(SELECT id FROM recurrence_rules rr WHERE rr.parent_id=m.id AND !rr.deleted)"
            	. ", m.".CalendarDataUtil::getRecurrenceFieldName().") AS recurrence_id, "
			. "'Meetings' AS module, "
            . "'' AS calendar_color, "
            . "IF(m.status = 'Held' || m.status = 'Not Held', 'grey', IF(m.date_start < '".$timedate->get_gmt_db_datetime()."', 'red', 'green')) AS status_color, "
			. "m.is_publish AS is_publish, ";
		global $sugar_flavor;
		if ($sugar_flavor == "PRO") {
			$ret_query .= "m.team_id AS team_id, ";
		}
		if (!empty($userIds)) {
			$strUserIds = join("','", $userIds);
			$mu_join = "LEFT JOIN (SELECT * FROM meetings_users WHERE user_id IN ('{$strUserIds}') AND NOT deleted GROUP BY meeting_id) mu ON mu.meeting_id=m.id ";
			$mu_where = "AND mu.meeting_id IS NOT NULL ";
		}
		else $mu_join = $mu_where = '';
		$ret_query .= "mu.accept_status AS accept_status "
			. "FROM meetings m "
			. $mu_join
			. "WHERE m.deleted=0 "
			. $mu_where
			. "AND (m.date_start <= '{$calDateTime->gmtNextDateTime}' AND DATE_ADD(m.date_end, INTERVAL m.duration MINUTE) >= '{$calDateTime->gmtPrevDateTime}') "
			;

		$ret_query .= CalendarDataUtil::ownerWhere('Meetings');

		return $ret_query;
	}
	
	function getEventsOnDayByUserSql(&$calDateTime, $userIds) {
			$sql = "SELECT m.id AS id, "
				. "m.assigned_user_id, "
				. "0 AS is_daylong, "
				. "0 AS is_private, "
				. "m.name AS name, "
				. "IF(!m.no_date_start && !m.no_date_end,
					TIMEDIFF(m.date_end, m.date_start),
					1
					)	AS duration_hours, "
				. " '###' AS duration_minutes, "
				. "SUBSTR(IF(!m.no_date_start, m.date_start, m.date_end), 1, 10) AS date_start, "
				. "SUBSTR(IF(!m.no_date_start, m.date_start, m.date_end), 12, 8) AS time_start, "
				. "'' AS recurrence_id, "
				. "'EventSessions' AS module, "
                . "'' AS status_color, "
                . "m.calendar_color AS calendar_color, ";
				$sql .= "1 AS is_publish, "
				. "'accept' AS accept_status "
				. "FROM event_sessions m "
				. "WHERE m.deleted=0 "
				. "AND ("
				. " (IF(!m.no_date_start, m.date_start, m.date_end) >= '{$calDateTime->gmtPrevDate} 00:00:00' AND IF(!m.no_date_start, m.date_start, m.date_end) <= '{$calDateTime->gmtNextDate} 23:59:59') "
				. " OR (IF(!m.no_date_end, m.date_end, m.date_start) >= '{$calDateTime->gmtPrevDate} 00:00:00' AND IF(!m.no_date_end, m.date_end, m.date_start) <= '{$calDateTime->gmtNextDate} 23:59:59') "
				. ") "
				. " AND (!m.no_date_start OR !m.no_date_end) ";

		$sql .= CalendarDataUtil::ownerWhere('EventSessions');
		return $sql;
	}
	
	function getCallsOnDayByUserSql(&$calDateTime, $userIds) {
        global $timedate;
		$ret_query = "SELECT m.id AS id, "
			. "m.assigned_user_id, "
			. "0 AS is_daylong, "
			. "m.is_private, "
			. "m.name AS name, "
            . "0 AS duration_hours, "
            . "m.duration AS duration_minutes, "
            . "DATE(m.date_start) AS date_start, "
            . "TIME(m.date_start) AS time_start, "
			. "'' AS recurrence_id, "
			. "'Calls' AS module, "
            . "'' AS calendar_color, "
            . "IF(m.status = 'Held' || m.status = 'Not Held', 'grey', IF(m.date_start < '".$timedate->get_gmt_db_datetime()."', 'red', 'green')) AS status_color, "
            . "m.is_publish AS is_publish, ";
		global $sugar_flavor;
		if ($sugar_flavor == "PRO") {
			$ret_query .= "m.team_id AS team_id, ";
		}
		if (!empty($userIds)) {
			$strUserIds = join("','", $userIds);
			$mu_join = "LEFT JOIN (SELECT * FROM calls_users WHERE user_id IN ('{$strUserIds}') AND NOT deleted GROUP BY call_id) mu ON mu.call_id=m.id ";
			$mu_where = "AND mu.call_id IS NOT NULL ";
		}
		else $mu_join = $mu_where = '';
		$ret_query .= "mu.accept_status AS accept_status "
			. "FROM calls m "
			. $mu_join
			. "WHERE m.deleted=0 "
			. $mu_where
			. "AND (m.date_start <= '{$calDateTime->gmtNextDateTime}' AND DATE_ADD(m.date_end, INTERVAL m.duration MINUTE) >= '{$calDateTime->gmtPrevDateTime}') "
			;

		$ret_query .= CalendarDataUtil::ownerWhere('Calls');

		return $ret_query;
	}
	
	function getProjectTasksOnDayByUserSql(&$calDateTime, $userIds) {
        global $timedate;
		$ret_query = "SELECT m.id AS id, "
			. "m.assigned_user_id, "
			. "1 AS is_daylong, "
			. "0 AS is_private, "
			. "m.name AS name, "
			. " TIMEDIFF(CONCAT(CONCAT(m.date_due, ' '), '23:59:59'), CONCAT(CONCAT(m.date_start, ' '), '00:00:00')) AS duration_hours, "
			. " '###' AS duration_minutes, "
            . "DATE(m.date_start) AS date_start, "
            . "'00:00:00' AS time_start, "
			. "'' AS recurrence_id, "
			. "'ProjectTask' AS module, "
            . "'' AS calendar_color, "
            . "IF(SUBSTR(m.status, 1, 6) = 'Closed', 'grey', IF(m.date_due < '".$timedate->get_gmt_db_date()."', 'red', 'green')) AS status_color, "
            . "1 AS is_publish, ";
		global $sugar_flavor;
		if ($sugar_flavor == "PRO") {
			$ret_query .= "m.team_id AS team_id, ";
		}
		$ret_query .= "'accept' AS accept_status "
			. "FROM project_task m "
			. "LEFT JOIN projecttasks_users ptu ON ptu.projecttask_id=m.id "
			. "WHERE m.deleted=0 "
			. "AND (DATE(m.date_start) <= '{$calDateTime->gmtNextDate}' AND DATE(m.date_due) >= '{$calDateTime->gmtPrevDate}') ";
		
		$projectType = CalendarDataUtil::param('project_type');
		$projectId = CalendarDataUtil::param('project_id');
		if (($projectType != null && $projectType != 'all') && $projectId != null) {
			$ret_query .= "AND ( m.parent_id = '{$projectId}') ";
		}	
		if (!empty($userIds)) {
			$strIds = join("','", $userIds);
			$ret_query .= "AND ( m.assigned_user_id IN ('{$strIds}') "
				. "OR ptu.user_id IN ('{$strIds}')) ";
		}

		$ret_query .= CalendarDataUtil::ownerWhere('ProjectTask');
		return $ret_query;
	}

	function getBookedHoursOnDayByUserSql(&$calDateTime, $userIds) {
		$ret_query = "SELECT m.id AS id, "
			. "m.assigned_user_id, "
			. "0 AS is_daylong, "
			. "0 AS is_private, "
			. "m.name AS name, "
            . "0 AS duration_hours, "
            . "m.quantity AS duration_minutes, "
            . "DATE(m.date_start) AS date_start, "
			. "TIME(m.date_start) AS time_start, "
			. "'' AS recurrence_id, "
			. "'Booking' AS module, "
            . "'' AS calendar_color, "
            . "'' AS status_color, "
			. "1 AS is_publish, "
			. "m.status AS accept_status "
			. "FROM booked_hours m "
			. "WHERE m.deleted=0 "
			. "AND (DATE(m.date_start) >= '{$calDateTime->gmtDate}' "
			. "AND DATE(m.date_start) <= '{$calDateTime->gmtNextDate}') "; 

		$timesheetId = CalendarDataUtil::param('timesheet_id');
		if ($timesheetId != null) {
			$ret_query .= "AND ( m.timesheet_id = '{$timesheetId}') ";
		}	
		if (!empty($userIds)) {
			$strIds = join("','", $userIds);
			$ret_query .= "AND ( m.assigned_user_id IN ('{$strIds}')) ";
		}

		$ret_query .= CalendarDataUtil::ownerWhere('Booking');

		return $ret_query;
	}	
	
	function getTasksOnDayByUserSql(&$calDateTime, $userIds) {
        global $timedate;
		$ret_query = "SELECT m.id AS id, "
			. "m.assigned_user_id, "
			. "0 AS is_daylong, "
			. "m.is_private, "
			. "m.name AS name, "
			. "0 AS duration_hours, "
			. "IF(effort_actual, effort_actual, effort_estim) AS duration_minutes, "
			. "DATE(IFNULL(m.date_due, m.date_start)) AS date_start, "
			. "TIME(IFNULL(m.date_due, m.date_start)) AS time_start, "
			. "'' AS recurrence_id, "
			. "'Tasks' AS module, "
            . "'' AS calendar_color, "
            . "IF(m.status = 'Completed' || m.status = 'Pending Input' || m.status = 'Deferred', 'grey', IF(IFNULL(m.date_due, m.date_start) < '".$timedate->get_gmt_db_datetime()."', 'red', 'green')) AS status_color, "
            . "1 AS is_publish, ";
		global $sugar_flavor;
		if ($sugar_flavor == "PRO") {
			$ret_query .= "m.team_id AS team_id, ";
		}
		$ret_query .= "'accept' AS accept_status "
			. "FROM tasks m "
			. "WHERE m.deleted=0 "
			. "AND DATE(IFNULL(m.date_due, m.date_start)) <= '{$calDateTime->gmtNextDate}' "
			. "AND DATE(IFNULL(m.date_due, m.date_start)) >= '{$calDateTime->gmtPrevDate}' ";
		if (!empty($userIds)) {
			$strIds = join("','", $userIds);
			$ret_query .= " AND m.assigned_user_id IN ('{$strIds}')";
		}

		$ret_query .= CalendarDataUtil::ownerWhere('Tasks');
		return $ret_query;
	}
	
	function getMeetingsOnDayByResourceSql(&$calDateTime, $resourceId) {
        global $timedate;
		$ret_query = "SELECT m.id AS id, "
			. "is_daylong, "
			. "m.name AS name, "
            . "0 AS duration_hours, "
            . "m.duration AS duration_minutes, "
            . "DATE(m.date_start) AS date_start, "
            . "TIME(m.date_start) AS time_start, "
            . "IFNULL("
            	. "(SELECT id FROM recurrence_rules rr WHERE rr.parent_id=m.id AND !rr.deleted)"
            	. ", m.".CalendarDataUtil::getRecurrenceFieldName().") AS recurrence_id, "
			. "'Meetings' as module, "
            . "'' AS calendar_color, "
            . "IF(m.status = 'Held' || m.status = 'Not Held', 'grey', IF(m.date_start < '".$timedate->get_gmt_db_datetime()."', 'red', 'green')) AS status_color, "
            . "m.is_publish AS is_publish ";
		global $sugar_flavor;
		if ($sugar_flavor == "PRO") {
			$ret_query .= ", m.team_id AS team_id ";
		}
		$ret_query .= "FROM meetings m "
			. "INNER JOIN meetings_resources mr ON(m.id=mr.meeting_id) "
			. "WHERE m.deleted=0 "
			. "AND mr.deleted=0 "
			. "AND (DATE(m.date_start) <= '{$calDateTime->gmtNextDate}' AND DATE(m.date_end) >= '{$calDateTime->gmtPrevDate}') "
		/*			. "AND ("  
					. " (m.date_start >= '{$calDateTime->gmtDate}' AND m.date_start <= '{$calDateTime->gmtNextDate}') "
					. " OR (m.date_end >= '{$calDateTime->gmtDate}' AND m.date_end <= '{$calDateTime->gmtNextDate}') " 
					. " OR (m.date_start <= '{$calDateTime->gmtDate}' AND m.date_end >= '{$calDateTime->gmtNextDate}')"
					. ") "*/
		 	. "AND mr.resource_id='{$resourceId}' " . "ORDER BY m.date_start ";
//		 	. "AND mr.resource_id='{$resourceId}' " . "ORDER BY m.date_start ";
		$ret_query .= CalendarDataUtil::ownerWhere('Meetings');
		return $ret_query;
	}

	//static function
	function getActivitiesOnDay(&$calDateTime, $targetType, $targets, $teamSecurity = false,
		$activityWidth = CAL_DAY_ACTIVITY_WIDTH, $leftOffset = 0, $dupOffset = -1, $isMerge = false,
		$exceptMeetingIds = array(), $holidays=false, $daylongDupe=false, $debug=false) {

		global $image_path;
		global $timedate;
		global $current_user, $current_language;
		global $sugar_flavor;
		global $db;

		$dateFormats = CalendarDateTime::userDateTimeFormats();
		$strings = return_module_language($current_language, "Calendar");
		if(! is_array($activityWidth)) $activityWidth = array($activityWidth, $activityWidth);

		$activities = array ();
		if($holidays && function_exists('holiday')) {
			$holiday = holiday($calDateTime->localYear, $calDateTime->localMonth, $calDateTime->localDay);
			if($holiday)
				$activities[] = array(
					'module' => 'Holidays',
					'isDuplicate' => false,
					'is_daylong' => true,
					'startTime' => '',
					'endTime' => '',
					'height' => CAL_HEIGHT_PER_HOUR + CAL_HEIGHT_TUNE_OFFSET,
					'left' => $leftOffset + CAL_DAY_LEFT_OFFSET,
					'width' => $activityWidth[0],
					'forDate' => $calDateTime->localDate,
					'subject' => $holiday);
		}

		if ($sugar_flavor != "PRO") {
			$teamSecurity = false;
		}

		if ($targetType == 'user' || $targetType == 'all') {
			if ($targetType == 'all') {
				$targetId = '';
			}
			$sql = '';
			if (CalendarDataUtil::param('display_leave') && CalendarDataUtil::isModuleEnabled('Vacations', 'list')) {
				$sql .= '(';
				$sql .= CalendarDataUtil::getLeaveOnDayByUserSql($calDateTime, $targets);
				$sql .= ')';
			}
			if (CalendarDataUtil::param('display_meetings') && CalendarDataUtil::isModuleEnabled('Meetings', 'list')) {
				if (!empty($sql)) {
					$sql .= ' UNION ';
				}
				$sql .= '(';
				$sql .= CalendarDataUtil::getMeetingsOnDayByUserSql($calDateTime, $targets);
				$sql .= ')';
			}
			if (CalendarDataUtil::param('display_calls') && CalendarDataUtil::isModuleEnabled('Calls', 'list')) {
				if (!empty($sql)) {
					$sql .= ' UNION ';
				}
				$sql .= ' ( ';
				$sql .=  CalendarDataUtil::getCallsOnDayByUserSql($calDateTime, $targets);
				$sql .= ' ) ';
			}
			if (CalendarDataUtil::param('display_events') && CalendarDataUtil::isModuleEnabled('EventSessions', 'list')) {
				if (!empty($sql)) {
					$sql .= ' UNION ';
				}
				$sql .= ' ( ';
				$sql .=  CalendarDataUtil::getEventsOnDayByUserSql($calDateTime, $targets);
				$sql .= ' ) ';
			}
			if (CalendarDataUtil::param('display_tasks') && CalendarDataUtil::isModuleEnabled('Tasks', 'list')) {
				if (!empty($sql)) {
					$sql .= ' UNION ';
				}
				$sql .= ' (';
				$sql .=  CalendarDataUtil::getTasksOnDayByUserSql($calDateTime, $targets);
				$sql .= ')';
			}
			if (CalendarDataUtil::param('display_project_tasks') && CalendarDataUtil::isModuleEnabled('ProjectTask', 'list')) {
				if (!empty($sql)) {
					$sql .= ' UNION ';
				}
				$sql .= ' (';
				$sql .=  CalendarDataUtil::getProjectTasksOnDayByUserSql($calDateTime, $targets);
				$sql .= ')';
			}
			if (CalendarDataUtil::param('display_booked_hours') && CalendarDataUtil::isModuleEnabled('Booking', 'list')) {
				if (!empty($sql)) {
					$sql .= ' UNION ';
				}
				$sql .= ' (';
				$sql .=  CalendarDataUtil::getBookedHoursOnDayByUserSql($calDateTime, $targets);
				$sql .= ')';
			}			
			if (!empty ($sql)) {
				$sql .= ' ORDER BY date_start, time_start ';
			}
		} elseif (CalendarDataUtil::isModuleEnabled('Meetings', 'list')) {
			$sql = CalendarDataUtil::getMeetingsOnDayByResourceSql($calDateTime, $targets);
		}

		if (empty($sql)) {
			return $activities;
		}

		if($debug)
			pr2($sql, 'calendar sql', true);
		
		$resultSet = $db->query($sql, true);
		if ($resultSet === false) {
			return $activities;
		}

		$recurrenceImg = get_image($image_path . 'refresh', "border='0' hspace='0' vspace='0' style='vertical-align: middle'");
		$meeting_strings = return_module_language($current_language, "Meetings");

		if ($teamSecurity) {
			if (isset ($_SESSION['TeamsBelongingTo'])) {
				$teamsBelongingTo = $_SESSION['TeamsBelongingTo'];
			} else {
                $teamsBelongingTo = TeamEx::getTeamsBelongingTo($current_user->id);
			}
		}

		$maxDupLevel = 0;

		while ($row = $db->fetchByAssoc($resultSet)) {
			if ($row['date_start'] == '0000-00-00') {
				continue;
			}
			if(in_array($row['id'], $exceptMeetingIds)) {
				continue;
			}
			if ($row['module'] == 'Vacations') {
				$row['name'] = get_assigned_user_name($row['assigned_user_id']) . ' : ' .  sprintf($strings['LBL_VACATION_DAYS'], $row['name']);
			}
			if ($row['duration_minutes'] == '###') {
				list ($row['duration_hours'], $row['duration_minutes'] ) = explode(':', $row['duration_hours']);
			}
			$tz = ($row['module'] == 'ProjectTask' ? CAL_BASE_LOCAL : CAL_BASE_GMT);
			$startDT = new CalendarDateTime("{$row['date_start']} {$row['time_start']}", false, $tz, false);

			$durationMin = $row['duration_hours'] * 60 + $row['duration_minutes'];
			$localEnd_ts = strtotime("{$startDT->localDateTime} +{$durationMin} minutes");
			$localEndDateTime = date("Y-m-d H:i:s", $localEnd_ts);
			if(substr($localEndDateTime, -9) == ' 00:00:00' && $startDT->localDateTime_t != $localEnd_ts) {
				// for events ending at midnight, don't carry over into next day
				$localEnd_ts --;
				$localEndDateTime = date("Y-m-d H:i:s", $localEnd_ts);
			}

			//check time zone
			if ( $row['module'] != 'Vacations' && ($localEnd_ts < $calDateTime->localDateTime_t || $startDT->localDateTime > $calDateTime->localNextDateTime)) {
				continue;
			}
			//...check time zone

			if ($row['module'] == 'Tasks') {
				if ($localEndDateTime < $calDateTime->localDateTime || $localEndDateTime > $calDateTime->localNextDateTime) {
					continue;
				}
			}

			global $current_user;
			$startHour = $current_user->day_begin_hour;
			$endHour = $current_user->day_end_hour;
			if(empty($startHour) && empty($endHour)) {
				$startHour = 9;
				$endHour = 18;
			}

			$fromPrevDay = $toNextDay = false;
			$startMin = ($startHour - floor($startHour)) * 60;
			$startHour = floor($startHour);
			//Start Time and End Time
			$localDateTime = date(CAL_DATE_FORMAT, $calDateTime->localDateTime_t) . sprintf(" %02d:00:00", $startHour);
			if ($startDT->localDateTime < $localDateTime) {
				$fromPrevDay = true;
			//if ($startDT->localDateTime <= $calDateTime->localDateTime) {
				$top = 0;
				//$diffSec = strtotime($calDateTime->localDateTime) - strtotime($startDT->localDateTime);
				$diffSec = strtotime($localDateTime) - $startDT->localDateTime_t;
				$height = CAL_HEIGHT_PER_HOUR * ($row['duration_hours'] + $row['duration_minutes'] / 60 - $diffSec / 3600);
				//$displayStartTime = date('(n/j)G:i', $startDT->localDateTime_t);
				$startOffsetMinutes = 0;
				$durationMin = $durationMin - ($diffSec/60);
			} else {
				$top = CAL_HEIGHT_PER_HOUR * (($startDT->localHour + $startDT->localMin / 60) - $startHour);
				$height = CAL_HEIGHT_PER_HOUR * ($row['duration_hours'] + $row['duration_minutes'] / 60);
				$startOffsetMinutes = $startDT->localHour * 60 + $startDT->localMin;
			}
			//check step over the day
			$top = min($top, CAL_HEIGHT_PER_HOUR * ($endHour - $startHour) - CAL_HEIGHT_PER_HOUR / 2);
			
			/*$displayStartTime = date($dateFormats['time_no_zero'], $startDT->localDateTime_t);
			$displayEndTime = date($dateFormats['time_no_zero'], $localEnd_ts);*/
			$showEndTs = ($row['module'] == 'Tasks') ? null : $localEnd_ts;
			list($displayStartTime, $displayEndTime) = CalendarDataUtil::formatActivityTimes($startDT, $showEndTs);
			
			if (($top + $height) > CAL_HEIGHT_PER_HOUR * ($endHour - $startHour)) {
				$toNextDay = true;
				$height = CAL_HEIGHT_PER_HOUR * ($endHour - $startHour) - $top;
			}

			if ($row['module'] == 'ProjectTask') {
				$displayEndTime = date($dateFormats['date_short'], $localEnd_ts);
				$displayStartTime = date($dateFormats['date_short'], $startDT->localDateTime_t);
			}
			/*
			if ($row['module'] == 'Tasks') {
				$displayEndTime = date("n/j", strtotime($localEndDateTime));
				$displayStartTime = date("n/j", $startDT->localDateTime_t);
			}
			 */

			$top += CAL_HEIGHT_PER_HOUR;

			if ($fromPrevDay && $toNextDay) {
				$row['is_daylong'] = 1;
			}

			if ($row['module'] == 'EventSessions') {
				$row['is_daylong'] = 1;
			}
			if ($row['module'] == 'ProjectTask') {
				$row['is_daylong'] = 1;
			}

			if (!empty($row['is_daylong'])) {
				$top = 0;
				$height = CAL_HEIGHT_PER_HOUR;
			}

			if ($height < CAL_HEIGHT_PER_HOUR/2) {
				$height = CAL_HEIGHT_PER_HOUR/2;
			}

			$row['contacts'] = array();

			if (CalendarDataUtil::param('add_contacts')) {
				if ($row['module'] == 'Meetings') {
					$row['contacts'] = MeetingEx::getParticipantsById($row['id']);
					foreach (MeetingEx::getContactsById($row['id']) as $cid => $cname) {
						$row['contacts'][$cid] = $cname;
					}
				}
				if ($row['module'] == 'Calls') {
					$row['contacts'] = CallEx::getParticipantsById($row['id']);
					foreach (CallEx::getContactsById($row['id']) as $cid => $cname) {
						$row['contacts'][$cid] = $cname;
					}
				}
			}
			
			//check duplication... 
			$dupLevel = 0;
			$isDuplicate = false;
			if(empty($row['is_daylong']) || $daylongDupe) {
			foreach ($activities as $id => $regActivity) {
				if($regActivity['is_daylong'] && !$daylongDupe)
					continue;
				if ($startDT->localDateTime < $regActivity['endDateTime'] && $localEndDateTime > $regActivity['startDateTime'])
				{
					$isDuplicate = $activities[$id]['isDuplicate'] = true;
					$dupLevel = max($dupLevel, $regActivity['duplicateLevel'] + 1);
					$maxDupLevel = max($dupLevel, $maxDupLevel);
					
					if($isMerge) {
						$isModified = false;
						if($startDT->localDateTime < $regActivity['startDateTime']) {
							$activities[$id]['startDateTime'] = $startDT->localDateTime;
							$activities[$id]['startTime'] = $displayStartTime;
							$activities[$id]['startOffsetMinutes'] = $startOffsetMinutes;
							$isModified = true;
						}
						if($localEndDateTime > $regActivity['endDateTime']) {
							$activities[$id]['endDateTime'] = $localEndDateTime;
							$activities[$id]['endTime'] = $displayEndTime;
							$isModified = true;
						}
						if($isModified) {
							if ($startDT->localDateTime <= $calDateTime->localDateTime) {
								$startDateTime = $calDateTime->localDate . ' 00:00:00';							
							} else {
								$startDateTime = $calDateTime->localDate . ' ' . $activities[$id]['startTime'];
							}
							$endDateTime = $localEndDateTime;	
							$diffSec = strtotime($endDateTime) - strtotime($startDateTime);	
							$activities[$id]['durationMin'] = $diffSec / 60;							
						}
						//break;
					}
				}
			}
			}
			
			if($isMerge) {
				if($isDuplicate) {
					continue;
				}
			}

			$workRecurrenceImg = "";
			if (!empty ($row['recurrence_id'])) {
				$workRecurrenceImg = $recurrenceImg;
			}

			$isViewAble = true;
			//Team Security...
			if ($teamSecurity) {
				if (array_key_exists($row['team_id'], $teamsBelongingTo)) {
					$subject = $row['name'];
				} else {
					$subject = "";
					$isViewAble = false;
				}
			} else {
				$subject = $row['name'];
			}
			//...Team Security

			//if non Publish...
			if($row['is_publish'] == 0) {
				$cls = substr($row['module'], 0, -1) . 'Ex';
				$participants = call_user_func(array($cls, 'getParticipantsById'), $row['id']);
				if(!array_key_exists($current_user->id, $participants)) {
					$subject = "({$meeting_strings['LBL_NON_PUBLISH']})";
					$isViewAble = false;
				} else {
					$subject .= "({$meeting_strings['LBL_NON_PUBLISH']})";
				}
			}
			//...if non Publish

			if (@$row['is_private'] && $current_user->id != $row['assigned_user_id']) {
				$subject = $strings['LBL_PRIVATE_' . strtoupper($row['module'])];
				$isViewAble = false;
			}
			
			$assigned_user = array_get_default($row, 'assigned_user_id');
			$isViewAble = $isViewAble &&
				CalendarDataUtil::hasAccess($row['module'], $row['id'], $assigned_user, 'view');
			$canEdit = $isViewAble &&
				CalendarDataUtil::hasAccess($row['module'], $row['id'], $assigned_user, 'edit');

			$activities[] = array (
				'id' => $row['id'],
				'top' => $top,
				'left' => $leftOffset + CAL_DAY_LEFT_OFFSET,
				'width' => $activityWidth[0],
				'height' => $height + CAL_HEIGHT_TUNE_OFFSET,
				'startTime' => $displayStartTime,
				'endTime' => $displayEndTime,
				'startDateTime' => $startDT->localDateTime,
				'endDateTime' => $localEndDateTime,
				'startOffsetMinutes' => $startOffsetMinutes,
				'subject' => $subject,
				'imgHTML' => get_image($image_path . $row['module'], "border='0' hspace='0' vspace='0'"),
				'recurrenceImgHTML' => $workRecurrenceImg,
				'durationMin' => $durationMin,
				'duplicateLevel' => $dupLevel,
				'isDuplicate' => $isDuplicate,
				'isPublish' => $row['is_publish'],
				'is_daylong' => $row['is_daylong'],
				'isViewAble' => $isViewAble,
				'canEdit' => $canEdit,
				'module' => $row['module'],
				'forDate' => $calDateTime->localDate,
				'contacts' => $row['contacts'],
				'is_private' => array_get_default($row, 'is_private', 0),
                'status_color' => $row['status_color'],
                'calendar_color' => (! empty($row['calendar_color'])) ? 'background-color: '.$row['calendar_color'].';' : ''
			);
		}

		// adjust left position for overlapping events
		if($dupOffset == -1 && $maxDupLevel) {
			$dupOffset = (CAL_DAY_WIDTH - CAL_DAY_ACTIVITY_WIDTH - CAL_MIN_DUP_OFFSET) / $maxDupLevel;
			$dupOffset = round(max(CAL_MIN_DUP_OFFSET, min(CAL_DAY_ACTIVITY_WIDTH+3, $dupOffset)));
		}
		foreach($activities as $id => $regActivity) {
			if(! $maxDupLevel)
				$activities[$id]['width'] = $activityWidth[1];
			if($regActivity['isDuplicate'])
				$activities[$id]['left'] += $regActivity['duplicateLevel'] * $dupOffset;
		}

		if (count($activities) > 1) {
			global $current_language;
			$counts = array();
			$texts = array();
			foreach ($activities as $activity) {
				if ($activity['is_daylong']) {
					@$counts[$activity['module']]++;
				}
			}
			if (array_sum($counts) > 1) {
				foreach($counts as $module => $n) {
					$texts[] = sprintf($strings['LBL_ACTIVITY_COUNT_' . strtoupper($module)], $n);
				}
				$activities['summary'] = join(', ', $texts);
			}
		}

		return $activities;
	}

	//static function security
	function getWeekActivitiesEveryUser(& $calDateTime, & $users, $teamSecurity = false, $weekends=true, $removeEmpty = true) {
		global $image_path;
		global $timedate;
		global $current_user, $current_language;
		global $db;
		$strings = return_module_language($current_language, "Calendar");
		
		$dateFormats = CalendarDateTime::userDateTimeFormats();
		$firstDay = $calDateTime->systemWeekFirstDay;

		$activitiesEveryUserArray = array();
		if (array_key_exists($current_user->id, $users)) {
			$activitiesEveryUserArray[$current_user->id] = "";
		}
		if (empty($users)) {
			$users = array($current_user->id => get_user_full_name($current_user->id));
		}
		
		$quoteUserIds = array();
		foreach ($users as $userId => $userName) {
			$quoteUserIds[] = "'{$userId}'";
			if(! isset($stdDayArray)) {
				$stdDayArray = array();
				for ($i = 0; $i < 7; $i++) {
					if(! $weekends && ($i == 0 || $i == 6))
						continue;
					$stdDayArray[$i] = array(
						'activities' => array()
					);
				}
			}
			$activitiesEveryUserArray[$userId] = array (
				'user_full_name' => $userName,
				'activities_of_day_array' => $stdDayArray,
			);
		}
		$strUserIds = implode(",", $quoteUserIds);

		$gmtEndDateTime = date("Y-m-d H:i:s", strtotime("+7 day", strtotime($calDateTime->gmtWeekFirstDateTime)));
		$gmtEndDate = date("Y-m-d", strtotime($gmtEndDateTime));
		$sql = '';

		if (CalendarDataUtil::param('display_leave') && CalendarDataUtil::isModuleEnabled('Vacations', 'list')) {
			$sql .= " (SELECT m.id AS id, "
				. "m.assigned_user_id, "
				. "0 AS is_private, "
				. "concat('', m.days) AS name, "
				. "(TO_DAYS(m.date_end) - TO_DAYS(m.date_start) + 1) * 24 AS duration_hours, "
				. "0 AS duration_minutes, "
				. "m.date_start AS date_start, "
				. "'' AS time_start, "
				. "'' AS recurrence_id, "
				. "'Vacations' AS module, "
                . "'' AS status_color, "
                . "'' AS calendar_color, ";
				$sql .= "1 AS is_publish, "
				. "'accept' AS accept_status, "
				. "m.assigned_user_id AS user_id "
				. "FROM vacations m "
				. "WHERE m.deleted=0 "
				. "AND ("
				. " m.date_start <= '{$gmtEndDate}'"
                . " AND m.date_end >= '{$calDateTime->gmtWeekFirstDate}'"
				. ") "
				. "AND m.assigned_user_id IN ({$strUserIds}) ";

				$sql .= CalendarDataUtil::ownerWhere('Vacations');
				$sql .= ')';
		}	

		if (CalendarDataUtil::param('display_meetings') && CalendarDataUtil::isModuleEnabled('Meetings', 'list')) {
			if (!empty($sql)) {
				$sql .= ' UNION ';
			}
			$sql .= " (SELECT m.id AS id, "
				. "m.assigned_user_id, "
				. "m.is_private, "
				. "m.name AS name, "
                . "0 AS duration_hours, "
                . "m.duration AS duration_minutes, "
                . "DATE(m.date_start) AS date_start, "
                . "TIME(m.date_start) AS time_start, "
				. "IFNULL("
					. "(SELECT id FROM recurrence_rules rr WHERE rr.parent_id=m.id AND !rr.deleted)"
					. ", m.".CalendarDataUtil::getRecurrenceFieldName().") AS recurrence_id, "
				. "'Meetings' AS module, "
                . "IF(m.status = 'Held' || m.status = 'Not Held', 'grey', IF(m.date_start < '".$timedate->get_gmt_db_datetime()."', 'red', 'green')) AS status_color, "
                . "'' AS calendar_color, ";
				global $sugar_flavor;
				if ($sugar_flavor == "PRO") {
					$sql .= "m.team_id AS team_id, ";
				}
				$sql .= "m.is_publish AS is_publish, "
				. "mu.accept_status AS accept_status, "
				. "mu.user_id AS user_id "
				. "FROM meetings m "
				. "INNER JOIN (SELECT * FROM meetings_users WHERE user_id IN ({$strUserIds}) AND NOT deleted) mu ON mu.meeting_id=m.id "
				. "WHERE m.deleted=0 "
				. "AND mu.user_id IS NOT NULL "
				. "AND (DATE(m.date_start) <= '{$gmtEndDate}' AND DATE(m.date_end) >= '{$calDateTime->gmtWeekFirstDate}')";
//				. "ORDER BY m.date_start ";

				$sql .= CalendarDataUtil::ownerWhere('Meetings');
				$sql .= ')';
		}		

		if (CalendarDataUtil::param('display_calls') && CalendarDataUtil::isModuleEnabled('Calls', 'list')) {
			if (!empty($sql)) {
				$sql .= ' UNION ';
			}
			$sql .= "(SELECT m.id AS id, "
				. "m.assigned_user_id, "
				. "m.is_private, "
				. "m.name AS name, "
                . "0 AS duration_hours, "
                . "m.duration AS duration_minutes, "
                . "DATE(m.date_start) AS date_start, "
                . "TIME(m.date_start) AS time_start, "
				. "'' AS recurrence_id, "
				. "'Calls' AS module, "
                . "IF(m.status = 'Held' || m.status = 'Not Held', 'grey', IF(m.date_start < '".$timedate->get_gmt_db_datetime()."', 'red', 'green')) AS status_color, "
                . "'' AS calendar_color, ";
                global $sugar_flavor;
				if ($sugar_flavor == "PRO") {
					$sql .= "m.team_id AS team_id, ";
				}
				$sql .= "m.is_publish AS is_publish, "
				. "mu.accept_status AS accept_status, "
				. "mu.user_id AS user_id "
				. "FROM calls m "
				. "INNER JOIN (SELECT * FROM calls_users WHERE user_id IN ({$strUserIds}) AND NOT deleted) mu ON mu.call_id=m.id "
				. "WHERE m.deleted=0 "
				. "AND mu.user_id IS NOT NULL "
				. "AND (DATE(m.date_start) <= '{$gmtEndDate}' AND DATE(m.date_end) >= '{$calDateTime->gmtWeekFirstDate}') "
				;

				$sql .= CalendarDataUtil::ownerWhere('Calls');
				$sql .= ')';
		}
	
		if (CalendarDataUtil::param('display_tasks') && CalendarDataUtil::isModuleEnabled('Tasks', 'list')) {
			if (!empty($sql)) {
				$sql .= ' UNION ';
			}
			$sql .= "(SELECT m.id AS id, "
				. "m.assigned_user_id, "
				. "m.is_private, "
				. "m.name AS name, "
				. "IF(ISNULL(m.date_due) OR ISNULL(m.date_start), 1, TIMEDIFF(m.date_due, m.date_start))	AS duration_hours, "
				. " '###' AS duration_minutes, "
                . "DATE(IFNULL(m.date_due, m.date_start)) AS date_start, "
                . "TIME(IFNULL(m.date_due, m.date_start)) AS time_start, "
				. "'' AS recurrence_id, "
				. "'Tasks' AS module, "
                . "IF(m.status = 'Completed' || m.status = 'Pending Input' || m.status = 'Deferred', 'grey', IF(IFNULL(m.date_due, m.date_start) < '".$timedate->get_gmt_db_datetime()."', 'red', 'green')) AS status_color, "
                . "'' AS calendar_color, ";
				global $sugar_flavor;
				if ($sugar_flavor == "PRO") {
					$sql .= "m.team_id AS team_id, ";
				}
				$sql .= "1 AS is_publish, "
				. "'accept' AS accept_status, "
				. "m.assigned_user_id AS user_id "
				. "FROM tasks m "
				. "WHERE m.deleted=0 "
				. "AND ("
		//		. " (IF(m.date_start_flag = 'off', m.date_start, m.date_due) >= '{$calDateTime->gmtWeekFirstDate}' AND IF(m.date_start_flag = 'off', m.date_start, m.date_due) <= '{$gmtEndDate}') "
		//		. " OR (IF(m.date_due_flag = 'off', m.date_due, m.date_start) >= '{$calDateTime->gmtWeekFirstDate}' AND IF(m.date_due_flag = 'off', m.date_due, m.date_start) <= '{$gmtEndDate}') "
		//		. " OR (IF(m.date_start_flag = 'off', m.date_start, m.date_due) <= '{$calDateTime->gmtWeekFirstDate}' AND IF(m.date_due_flag = 'off', m.date_due, m.date_start) >= '{$gmtEndDate}')"
				. " DATE(IFNULL(m.date_due, m.date_start)) >= '{$calDateTime->gmtWeekFirstDate}' "
                . " AND DATE(IFNULL(m.date_due, m.date_start)) <= '{$gmtEndDate}' "
				. ") "
				. " AND (m.date_due IS NOT NULL OR m.date_start IS NOT NULL) "
				. "AND m.assigned_user_id IN ({$strUserIds}) ";

				$sql .= CalendarDataUtil::ownerWhere('Tasks');
				$sql .= ')';
		}

		if (CalendarDataUtil::param('display_project_tasks') && CalendarDataUtil::isModuleEnabled('ProjectTask', 'list')) {
			if (!empty($sql)) {
				$sql .= ' UNION ';
			}
			$sql .= "(SELECT m.id AS id, "
				. "m.assigned_user_id, "
				. "0 AS is_private, "
				. "m.name AS name, "
				.	"TIMEDIFF(CONCAT(CONCAT(m.date_due, ' '), '23:59:59'), CONCAT(CONCAT(m.date_start, ' '), '00:00:00'))	AS duration_hours, "
				. " '###' AS duration_minutes, "
                . "DATE(m.date_start) AS date_start, "
				. "TIME(m.date_start) AS time_start, "
				. "'' AS recurrence_id, "
				. "'ProjectTask' AS module, "
                . "IF(SUBSTR(m.status, 1, 6) = 'Closed', 'grey', IF(m.date_due < '".$timedate->get_gmt_db_date()."', 'red', 'green')) AS status_color, "
                . "'' AS calendar_color, ";
				global $sugar_flavor;
				if ($sugar_flavor == "PRO") {
					$sql .= "m.team_id AS team_id, ";
				}
				$sql .= "1 AS is_publish, "
				. "'accept' AS accept_status, "
				. "ptu.user_id AS user_id "
				. "FROM project_task m "
				. "INNER JOIN (SELECT * FROM projecttasks_users WHERE user_id IN ({$strUserIds}) AND NOT deleted) ptu ON projecttask_id=m.id "
				. "WHERE m.deleted=0 "
				. "AND ("
				. " (DATE(m.date_start) >= '{$calDateTime->gmtWeekFirstDate}' AND DATE(m.date_start) <= '{$gmtEndDate}') "
				. " OR (DATE(m.date_due) >= '{$calDateTime->gmtWeekFirstDate}' AND DATE(m.date_due) <= '{$gmtEndDate}') "
				. " OR (DATE(m.date_start) <= '{$calDateTime->gmtWeekFirstDate}' AND DATE(m.date_due) >= '{$gmtEndDate}')"
			//	. " m.date_due >= '{$calDateTime->gmtWeekFirstDate}' AND m.date_due <= '{$gmtEndDate}' "
				. ") "
				. "AND ( m.assigned_user_id IN ({$strUserIds}) "
				. "OR ptu.user_id IS NOT NULL ) ";
			// FIXME - need to query for tasks with assigned_user_id, then tasks from projecttasks_users if we want both
				
				$projectType = CalendarDataUtil::param('project_type');
				$projectId = CalendarDataUtil::param('project_id');
				if (($projectType != null && $projectType != 'all') && $projectId != null) {
					$sql.= "AND ( m.parent_id = '{$projectId}') ";
				}					
				
				$sql .= CalendarDataUtil::ownerWhere('ProjectTask');
				$sql .= ')';
		}

		if (CalendarDataUtil::param('display_booked_hours') && CalendarDataUtil::isModuleEnabled('Booking', 'list')) {
			if (!empty($sql)) {
				$sql .= ' UNION ';
			}	
					
			$sql = "SELECT m.id AS id, "
				. "m.assigned_user_id, "
				. "1 AS is_daylong, "
				. "0 AS is_private, "
				. "m.name AS name, "
                . "0 AS duration_hours, "
                . "m.quantity AS duration_minutes, "
                . "DATE(m.date_start) AS date_start, "
                . "TIME(m.date_start) AS time_start, "
				. "'' AS recurrence_id, "
				. "'Booking' AS module, "
                . "'' AS status_color, "
                . "'' AS calendar_color, "
				. "1 AS is_publish, "
				. "m.assigned_user_id AS user_id, "				
				. "m.status AS accept_status "
				. "FROM booked_hours m "
				. "WHERE m.deleted=0 "
				. "AND (DATE(m.date_start) >= '{$calDateTime->gmtWeekFirstDate}' AND DATE(m.date_start) <= '{$gmtEndDate}') ";

			$timesheetId = CalendarDataUtil::param('timesheet_id');
			if ($timesheetId != null) {
				$sql .= "AND ( m.timesheet_id = '{$timesheetId}') ";
			}				
			if (!empty($strUserIds)) {
				$sql .= "AND ( m.assigned_user_id IN ({$strUserIds})) ";
			}
	
			$sql .= CalendarDataUtil::ownerWhere('Booking');
		}

		if (CalendarDataUtil::param('display_events') && CalendarDataUtil::isModuleEnabled('EventSessions', 'list')) {
			if (!empty($sql)) {
				$sql .= ' UNION ';
			}
			$sql .= "(SELECT m.id AS id, "
				. "m.assigned_user_id, "
				. "0 AS is_private, "
				. "m.name AS name, "
				. "IF(!m.no_date_start && !m.no_date_end,
					TIMEDIFF(m.date_end, m.date_start),
					1
					)	AS duration_hours, "
				. " '###' AS duration_minutes, "
				. "DATE(IF(!m.no_date_start, m.date_start, m.date_end)) AS date_start, "
				. "TIME(IF(!m.no_date_start, m.date_start, m.date_end)) AS time_start, "
				. "'' AS recurrence_id, "
				. "'EventSessions' AS module, "
                . "'' AS status_color, "
                . "m.calendar_color AS calendar_color, ";
				$sql .= "1 AS is_publish, "
				. "'accept' AS accept_status, "
				. "m.assigned_user_id AS user_id "
				. "FROM event_sessions m "
				. "WHERE m.deleted=0 "
				. "AND ("
				. " (IF(!m.no_date_start, m.date_start, m.date_end) >= '{$calDateTime->gmtWeekFirstDate} 00:00:00' AND IF(!m.no_date_start, m.date_start, m.date_end) <= '{$gmtEndDate} 23:59:59') "
				. " OR (IF(!m.no_date_end, m.date_end, m.date_start) >= '{$calDateTime->gmtWeekFirstDate} 00:00:00' AND IF(!m.no_date_end, m.date_end, m.date_start) <= '{$gmtEndDate} 23:59:59') "
				. " OR (IF(!m.no_date_start, m.date_start, m.date_end) <= '{$calDateTime->gmtWeekFirstDate} 23:59:00' AND IF(!m.no_date_end, m.date_end, m.date_start) >= '{$gmtEndDate} 00:00:00')"
				. ") "
				. " AND (!m.no_date_start OR !m.no_date_end) ";

				$sql .= CalendarDataUtil::ownerWhere('EventSessions');
				$sql .= ')';
		}

		if (!empty($sql)) {
			$sql .= "ORDER BY date_start ";
		}

		if (empty($sql)) {
			return $activitiesEveryUserArray;
		}
		$resultSet = $db->query($sql, true);
		if ($resultSet === false) {
			return $activitiesEveryUserArray;
		}

		$meetingImg = get_image($image_path . 'Meetings', "border='0' hspace='0' vspace='0'");
		$recurrenceImg = get_image($image_path . 'refresh', "border='0' hspace='0' vspace='0'");
		$meeting_strings = return_module_language($current_language, "Meetings");

		if ($teamSecurity) {
			if (isset ($_SESSION['TeamsBelongingTo'])) {
				$teamsBelongingTo = $_SESSION['TeamsBelongingTo'];
			} else {
				$teamsBelongingTo = TeamEx::getTeamsBelongingTo($current_user->id);
			}
		}

		while ($row = $db->fetchByAssoc($resultSet)) {
			if ($row['date_start'] == '0000-00-00') {
				continue;
			}
			if ($row['duration_minutes'] == '###') {
				$s = explode(':', $row['duration_hours'], 2);
				$row['duration_hours'] = $s[0];
				$row['duration_minutes'] = count($s) > 1 ? $s[1] : '00';
			}
			
			if ($row['module'] == 'Vacations') {
				$row['name'] = get_assigned_user_name($row['assigned_user_id']) . ' : ' .  sprintf($strings['LBL_VACATION_DAYS'], $row['name']);

			}
			
			$tz = CAL_BASE_GMT;
			//$tz = ( ($row['module'] == 'ProjectTask' || $row['module'] == 'Vacations') ? CAL_BASE_LOCAL : CAL_BASE_GMT);
            if (empty($row['time_start'])) {
                $row['time_start'] = '00:00:00';
                $tz = CAL_BASE_LOCAL;
            }
			$startDT = new CalendarDateTime("{$row['date_start']} {$row['time_start']}", false, $tz, false);

			$durationMin = $row['duration_hours'] * 60 + $row['duration_minutes'];
			if($row['module'] == 'Tasks')
				$localEndDateTime_t = $startDT->localDateTime_t;
			else
				$localEndDateTime_t = strtotime("+{$durationMin} minutes", $startDT->localDateTime_t);
			$localEndDateTime = date("Y-m-d H:i:s", $localEndDateTime_t);
			if(substr($localEndDateTime, -9) == ' 00:00:00' && $localEndDateTime_t != $startDT->localDateTime_t) {
				// for events ending at midnight, don't carry over into next day
				$localEndDateTime_t --;
				$localEndDateTime = date("Y-m-d H:i:s", $localEndDateTime_t);
			}

			//check time zone
			if ($localEndDateTime < $calDateTime->localWeekFirstDate.' 00:00:00' || $startDT->gmtDateTime >= $gmtEndDateTime) {
				continue;
			}

			$startWeekDayIndex = date("w", $startDT->localDateTime_t);
			$endWeekDayIndex = date("w", $localEndDateTime_t);

			$activityImg = $meetingImg;

			$workRecurrenceImg = "";
			if (!empty ($row['recurrence_id'])) {
				$workRecurrenceImg = $recurrenceImg;
			}
			
			if ($row['module'] == 'ProjectTask' /* || $row['module'] == 'Tasks'*/) {
				$displayEndTime = date($dateFormats['date_short'], $localEndDateTime_t);
				if($row['module'] == 'Tasks') {
					$displayEndTime .= ' '.date($dateFormats['time_no_zero'], $localEndDateTime_t);
					$displayStartTime = '';
				} else
					$displayStartTime = date($dateFormats['date_short'], $startDT->localDateTime_t);
			}
			else {
				/*$displayStartTime = date($dateFormats['time_no_zero'], $startDT->localDateTime_t);
				$displayEndTime = date($dateFormats['time_no_zero'], $localEndDateTime_t);*/
				$showEnd = ($row['module'] == 'Tasks') ? null : $localEndDateTime_t;
				list($displayStartTime, $displayEndTime) = CalendarDataUtil::formatActivityTimes($startDT, $showEnd);
			}
			
			//Adjust loop variable.
			$startIndex = $startWeekDayIndex;
			$endIndex = $endWeekDayIndex;
			
			if($startDT->localDateTime_t < strtotime($calDateTime->localWeekFirstDate)) {
				$startIndex = $weekends ? 0 : 1;
			} else if($localEndDateTime_t > strtotime($calDateTime->localNextWeekDate)) {
				$endIndex = $weekends ? 6 : 5;
			}
			//...Adjust 

			if ($startIndex > $endIndex) {
				$endIndex = $startIndex;
			}

			for ($weekIndex = $startIndex; $weekIndex <= $endIndex; $weekIndex++) {
			
				if(! $weekends && ($weekIndex == 0 || $weekIndex == 6))
					continue;
			
				//check duplication... 
				$is_duplicate = 0;
				if ($row['module'] != 'EventSessions' && $row['module'] != 'ProjectTask' && $row['module'] != 'Booking')
				foreach ((array)@$activitiesEveryUserArray[$row['user_id']]['activities_of_day_array'][$weekIndex]['activities'] as $id => $regActivity) {
					if ($regActivity['module'] == 'EventSessions' || $regActivity['module'] == 'ProjectTask' || $regActivity['module'] == 'Booking') continue;
					if (($startDT->localDateTime >= $regActivity['startDateTime'] && $startDT->localDateTime < $regActivity['endDateTime']) || ($localEndDateTime >= $regActivity['startDateTime'] && $localEndDateTime < $regActivity['endDateTime']) || ($startDT->localDateTime < $regActivity['startDateTime'] && $localEndDateTime > $regActivity['endDateTime'])) {
						$activitiesEveryUserArray[$row['user_id']]['activities_of_day_array'][$weekIndex]['activities'][$id]['is_duplicate'] = 1;
						$is_duplicate = 1;
					}
				}
				//...check duplication

				$isViewAble = true;
				if ($teamSecurity) {
					if (array_key_exists($row['team_id'], $teamsBelongingTo)) {
						$subject = $row['name'];
					} else {
						$subject = "";
						$isViewAble = false;
					}
				} else {
					$subject = $row['name'];
				}

				//if non Publish...
				if($row['is_publish'] == 0) {
					$cls = substr($row['module'], 0, -1) . 'Ex';
					$participants =  call_user_func(array($cls, 'getParticipantsById') , $row['id']);
					if(!array_key_exists($current_user->id, $participants)) {
						$subject = "({$meeting_strings['LBL_NON_PUBLISH']})";
						$isViewAble = false;
					} else {
						$subject .= "({$meeting_strings['LBL_NON_PUBLISH']})";
					}
				}
				//...if non Publish

				if ($row['is_private'] && $current_user->id != $row['assigned_user_id']) {
					$subject = $strings['LBL_PRIVATE_' . strtoupper($row['module'])];
					$isViewAble = false;
				}
				
				$assigned_user = array_get_default($row, 'assigned_user_id');
				$isViewAble = $isViewAble &&
					CalendarDataUtil::hasAccess($row['module'], $row['id'], $assigned_user, 'view');
				$canEdit = $isViewAble &&
					CalendarDataUtil::hasAccess($row['module'], $row['id'], $assigned_user, 'edit');

				if ($row['module'] == 'Vacations') {
					$displayStartTime = '';
					$displayEndTime = '';
				}

				$activitiesEveryUserArray[$row['user_id']]['activities_of_day_array'][$weekIndex]['activities'][$row['id']] = array (
					'startTime' => $displayStartTime,
					'endTime' => $displayEndTime,
					'startDateTime' => $startDT->localDateTime,
					'endDateTime' => $localEndDateTime,
					'subject' => $subject,
					'activityImgHTML' => get_image($image_path . $row['module'], "border='0' hspace='0' vspace='0'"),
					'recurrenceImgHTML' => $workRecurrenceImg,
					'is_duplicate' => $is_duplicate,
					'module' => $row['module'],
					'isPublish' => $row['is_publish'],
					'isViewAble' => $isViewAble,
					'canEdit' => $canEdit,
                    'status_color' => $row['status_color'],
                    'calendar_color' => (! empty($row['calendar_color'])) ? 'background-color: '.$row['calendar_color'].';' : ''
				);

				/*
				$activitiesEveryUserArray[$row['user_id']]['activities_of_day_array'][$weekIndex]['tasks'] =
				CalendarDataUtil::getMyTasksOnDay($startDT->gmtDate, $row['user_id']); 
				 */
			}
		};

		foreach ($activitiesEveryUserArray as $user_id => $acts) {
			$sum = 0;
			foreach($acts['activities_of_day_array'] as $perday) {
				if (!empty($perday['activities'])) {
					$sum = 1;
					break;
				}
			}
			if (!$sum && $removeEmpty) {
				unset($activitiesEveryUserArray[$user_id]);
			} else {
				$replace = array();
				for ($i = $firstDay; $i < 7; $i++) {
					if(! $weekends && ($i == 0 || $i == 6))
						continue;
					$replace[$i] = array_get_default($acts['activities_of_day_array'], $i, array());
				}
				for ($i = 0; $i < $firstDay; $i++) {
					if(! $weekends && ($i == 0 || $i == 6))
						continue;
					$replace[$i] = array_get_default($acts['activities_of_day_array'], $i, array());
				}
				$activitiesEveryUserArray[$user_id]['activities_of_day_array'] = $replace; 
			}
		}
		return $activitiesEveryUserArray;
	}

	function getWeekActivitiesEveryResource(& $calDateTime, &$resources, $teamSecurity = false, $weekends=true) {
		global $image_path;
		global $timedate;
		global $current_user, $current_language;
		global $db;

		$dateFormats = CalendarDateTime::userDateTimeFormats();
		$activitiesEveryResourceArray = array ();

		if (empty ($resources)) {
			return $activitiesEveryResourceArray;
		}

		$quouteResourceIds = array ();

		if (array_key_exists($current_user->id, $resources)) {
			$activitiesEveryResourceArray[$current_user->id] = "";
		}

		foreach ($resources as $resourceId => $resourceName) {
			$quouteResourceIds[] = "'{$resourceId}'";
			$activitiesEveryResourceArray[$resourceId] = array (
				'user_full_name' => $resourceName,
				'activities_of_day_array' => array ()
			);

			for ($i = 0; $i < 7; $i++) {
				if(! $weekends && ($i == 0 || $i == 6))
					continue;
				$activitiesEveryResourceArray[$resourceId]['activities_of_day_array'][$i] = array (
					'activities' => array ()
				);
			}
		}
		$strResourceIds = implode(",", $quouteResourceIds);

		$gmtEndDateTime_t = strtotime("{$calDateTime->gmtWeekFirstDateTime} +7 day");
		$gmtEndDateTime = date("Y-m-d H:i:s", $gmtEndDateTime_t);
		$gmtEndDate = substr($gmtEndDateTime, 0, 10);

		$sql = "SELECT m.id AS id, "
				. "m.name AS name, "
                . "0 AS duration_hours, "
                . "m.duration AS duration_minutes, "
                . "DATE(m.date_start) AS date_start, "
                . "TIME(m.date_start) AS time_start, "
				. " 'Meetings' AS module, "
				. "IFNULL("
					. "(SELECT id FROM recurrence_rules rr WHERE rr.parent_id=m.id AND !rr.deleted)"
					. ", m.".CalendarDataUtil::getRecurrenceFieldName().") AS recurrence_id, ";
		global $sugar_flavor;
		if ($sugar_flavor == "PRO") {
				$sql .= "m.team_id AS team_id, ";
		}
				$sql .= "m.is_publish AS is_publish, "
				. "mr.resource_id AS resource_id "
				. "FROM meetings m "
				. "INNER JOIN meetings_resources mr ON(m.id=mr.meeting_id) "
				. "WHERE m.deleted=0 "
				. "AND mr.deleted=0 "
				. "AND ("
				. " (m.date_start >= '{$calDateTime->gmtWeekFirstDate}' AND m.date_start <= '{$gmtEndDate}') "
				. " OR (m.date_end >= '{$calDateTime->gmtWeekFirstDate}' AND m.date_end <= '{$gmtEndDate}') "
				. " OR (m.date_start <= '{$calDateTime->gmtWeekFirstDate}' AND m.date_end >= '{$gmtEndDate}')"
				. ") "
				. "AND mr.resource_id IN ({$strResourceIds}) "
				. "ORDER BY m.date_start";

		$resultSet = $db->query($sql, true);
		if ($resultSet === false) {
			return $activitiesEveryResourceArray;
		}

		$meetingImg = get_image($image_path . 'Meetings', "border='0' hspace='0' vspace='0'");
		$recurrenceImg = get_image($image_path . 'refresh', "border='0' hspace='0' vspace='0' style='vertical-align: middle'");
		$meeting_strings = return_module_language($current_language, "Meetings");

		if ($teamSecurity) {
			if (isset ($_SESSION['TeamsBelongingTo'])) {
				$teamsBelongingTo = $_SESSION['TeamsBelongingTo'];
			} else {
				$teamsBelongingTo = TeamEx::getTeamsBelongingTo($current_user->id);
			}
		}

		while ($row = $db->fetchByAssoc($resultSet)) {
			if ($row['date_start'] == '0000-00-00') {
				continue;
			}
			$startDT = new CalendarDateTime("{$row['date_start']} {$row['time_start']}", true, CAL_BASE_GMT, false);

			$durationMin = $row['duration_hours'] * 60 + $row['duration_minutes'];
			$localEndDateTime_t = strtotime("+{$durationMin} minutes", $startDT->localDateTime_t);
			$localEndDateTime = date("Y-m-d H:i:s", $localEndDateTime_t);

			//check time zone
			if ($localEndDateTime < $calDateTime->localWeekFirstDate || $startDT->gmtDateTime > $gmtEndDateTime) {
				continue;
			}

			$startWeekDayIndex = $startDT->localWeekDayIndex;
			$endWeekDayIndex = date("w", $localEndDateTime_t);

			$activityImg = $meetingImg;

			$workRecurrenceImg = "";
			if (!empty ($row['recurrence_id'])) {
				$workRecurrenceImg = $recurrenceImg;
			}

			//Adjust loop variable.
			$startIndex = $startWeekDayIndex;
			$endIndex = $endWeekDayIndex;
			
			if($startDT->localDateTime_t < strtotime($calDateTime->localWeekFirstDate)) {
				$startIndex = $weekends ? 0 : 1;
			} else if($localEndDateTime_t > strtotime($calDateTime->localNextWeekDate)) {
				$endIndex = $weekends ? 6 : 5;
			}
			//...Adjust 

			for ($weekIndex = $startIndex; $weekIndex <= $endIndex; $weekIndex++) {
				if(! $weekends && ($weekIndex == 0 || $weekIndex == 6))
					continue;
				
				list($displayStartTime, $displayEndTime) = CalendarDataUtil::formatActivityTimes($startDT, $localEndDateTime_t);

				//check duplication... 
				$is_duplicate = 0;
				foreach ($activitiesEveryResourceArray[$row['resource_id']]['activities_of_day_array'][$weekIndex]['activities'] as $id => $regActivity) {
					if (($startDT->localDateTime >= $regActivity['startDateTime'] && $startDT->localDateTime < $regActivity['endDateTime']) || ($localEndDateTime >= $regActivity['startDateTime'] && $localEndDateTime < $regActivity['endDateTime']) || ($startDT->localDateTime < $regActivity['startDateTime'] && $localEndDateTime > $regActivity['endDateTime'])) {
						$activitiesEveryResourceArray[$row['resource_id']]['activities_of_day_array'][$weekIndex]['activities'][$id]['is_duplicate'] = 1;
						$is_duplicate = 1;
					}
				}
				//...check duplication

				$isViewAble = true;

				if ($teamSecurity) {
					if (array_key_exists($row['team_id'], $teamsBelongingTo)) {
						$subject = $row['name'];
					} else {
						$subject = "";
						$isViewAble = false;
					}
				} else {
					$subject = $row['name'];
				}
				
				//if non Publish...
				if($row['is_publish'] == 0) {
					$cls = substr($row['module'], 0, -1) . 'Ex';
					$participants =  call_user_func(array($cls, 'getParticipantsById'), $row['id']);
					if(!array_key_exists($current_user->id, $participants)) {
						$subject = "({$meeting_strings['LBL_NON_PUBLISH']})";
						$isViewAble = false;
					} else {
						$subject .= "({$meeting_strings['LBL_NON_PUBLISH']})";
					}
				}
				//...if non Publish

				$assigned_user = array_get_default($row, 'assigned_user_id');
				$isViewAble = $isViewAble &&
					CalendarDataUtil::hasAccess($row['module'], $row['id'], $assigned_user, 'view');
				$canEdit = $isViewAble &&
					CalendarDataUtil::hasAccess($row['module'], $row['id'], $assigned_user, 'edit');

				$activitiesEveryResourceArray[$row['resource_id']]['activities_of_day_array'][$weekIndex]['activities'][$row['id']] = array (
					'startTime' => $displayStartTime,
					'endTime' => $displayEndTime,
					'startDateTime' => $startDT->localDateTime,
					'endDateTime' => $localEndDateTime,
					'subject' => $subject,
					'activityImgHTML' => get_image($image_path . $row['module'], "border='0' hspace='0' vspace='0'"),
					'recurrenceImgHTML' => $workRecurrenceImg,
					'is_duplicate' => $is_duplicate,
					'module' => $row['module'],
					'isPublish' => $row['is_publish'],
					'isViewAble' => $isViewAble,
					'canEdit' => $canEdit,
				);
			}
		};

		foreach ($activitiesEveryResourceArray as $user_id => $acts) {
				$replace = array();
				for ($i = $calDateTime->systemWeekFirstDay; $i < 7; $i++) {
					if(! $weekends && ($i == 0 || $i == 6))
						continue;
					$replace[$i] = $acts['activities_of_day_array'][$i];
				}
				for ($i = 0; $i < $calDateTime->systemWeekFirstDay; $i++) {
					if(! $weekends && ($i == 0 || $i == 6))
						continue;
					$replace[$i] = $acts['activities_of_day_array'][$i];
				}
				$activitiesEveryResourceArray[$user_id]['activities_of_day_array'] = $replace; 
		}
		return $activitiesEveryResourceArray;
	}

	//Demo
	function getMyTasksOnDay($localDate, $userId) {
		global $image_path;
		global $timedate;
		global $current_user;

		$tasks = array ();

		if($current_user->id != $userId) {
			return $tasks;
		}

		$sql = "SELECT t.id AS id, "
				. "t.name AS name, "
				. "DATE(t.date_start) AS date_start, "
				. "DATE(t.date_start) AS time_start, "
				. "DATE(t.date_due) AS date_due, "
				. "DATE(t.date_due) AS time_due";
		global $sugar_flavor;
		if ($sugar_flavor == "PRO") {
			$sql .= ", t.team_id AS team_id ";
		}
				$sql .= " FROM tasks t "
				. "WHERE t.deleted=0 "
				. "AND DATE(t.date_due) = '{$localDate}' "
				. "AND t.assigned_user_id = '{$userId}' "
				. "ORDER BY t.date_start ";

		$db = & PearDatabase :: getInstance();
		$resultSet = $db->query($sql, true);
		if ($resultSet === false) {
			return $tasks;
		}

		while ($row = $db->fetchByAssoc($resultSet)) {
			$tasks[$row['id']] = array(
				'subject' => $row['name'],
				'date_due' => $row['date_due'],
			);
		};

		return $tasks;
	}



	/*
	function addWeekTasksEveryUser(&$activitiesEveryUserArray, &$calDateTime, $users) {
		global $image_path;
		global $timedate;
		global $current_user;
		
		$quouteUserIds = array();
		foreach($users as $userId => $userName) {
			$quouteUserIds[] = "'{$userId}'";
		}
		$strUserIds = implode(",", $quouteUserIds);
		
		$gmtEndDateTime = date("Y-m-d H:i:s", strtotime("+7 day", strtotime($calDateTime->gmtWeekFirstDateTime)));
		$gmtEndDate = date("Y-m-d", strtotime($gmtEndDateTime));
		
		$sql = "SELECT t.id AS id, "
			. "t.name AS name, "
			. "t.date_due AS date_due, "
			. "t.time_due AS time_due, "
			. "t.date_start AS date_start, "
			. "t.time_start AS time_start, "
			. "t.team_id AS team_id, "
			. "t.assigned_user_id AS assigned_user_id "
			. "FROM tasks t "
			. "WHERE t.deleted=0 "
			. "AND (t.date_due >= '{$calDateTime->gmtWeekFirstDate}' AND t.date_due <= '{$gmtEndDate}') "
			. "AND t.user_id IN ({$strUserIds}) "
			. "ORDER BY t.date_due, t.time_due ";
	
		$db = & PearDatabase::getInstance();
		$resultSet = $db->query($sql, true);
		if($resultSet === false) {
			return $activitiesEveryUserArray;
		}
		
		$taskImg = get_image($image_path . 'Tasks', "border='0' hspace='0' vspace='0' valign='bottom' style='vertical-align:bottom;'");
	
		while($row = $db->fetchByAssoc($resultSet)){
			if ($row['due_start'] == '0000-00-00') {
				continue;
			}
			$dueDT = new CalendarDateTime("{$row['due_start']} {$row['time_due']}", true, CAL_BASE_GMT, false);
			
			$dueWeekDayIndex = $dueDT->localWeekDayIndex;
			
			$activityImg = $taskImg;
			
			for($weekIndex = $startWeekDayIndex; $weekIndex <= $endWeekDayIndex; $weekIndex++) {
				if($weekIndex > $startWeekDayIndex) {
					$displayStartTime = date("(n/j)G:i", $dueDT->localDateTime_t);
				} else {
					$displayStartTime = date("G:i", $dueDT->localDateTime_t);
				}
				
				if($weekIndex < $endWeekDayIndex) {
					$displayEndTime = date("(n/j)G:i", $localEndDateTime_t);
				} else {
					$displayEndTime = date("G:i", $localEndDateTime_t);
				}
				
				//check duplication... 
				$is_duplicate = 0;
				foreach($activitiesEveryUserArray[$row['user_id']]['activities_of_day_array'][$weekIndex]['activities'] as $id => $regActivity) {
					if(($dueDT->localDateTime >= $regActivity['startDateTime'] && $dueDT->localDateTime < $regActivity['endDateTime'])
					|| ($localEndDateTime >= $regActivity['startDateTime'] && $localEndDateTime < $regActivity['endDateTime'])
					|| ($dueDT->localDateTime < $regActivity['startDateTime'] && $localEndDateTime > $regActivity['endDateTime'])) {
						$activitiesEveryUserArray[$row['user_id']]['activities_of_day_array'][$weekIndex]['activities'][$id]['is_duplicate'] = 1;
						$is_duplicate = 1;
					}
				}
				//...check duplication
				
				$activitiesEveryUserArray[$row['user_id']]['activities_of_day_array'][$weekIndex]['activities'][$row['id']] = array(
					'startTime' => $displayStartTime,
					'endTime' => $displayEndTime,
					'subject' => $row['name'],
					'activityImgHTML' => $activityImg,
					'recurrenceImgHTML' => "",
					'is_duplicate' => 0,
					'module' => 'Tasks',
				);
			}
		};
		
		return $activitiesEveryUserArray;
	}
	 */

	function ownerWhere($module, $table = 'm', $field = 'assigned_user_id')
	{
		global $current_user, $beanList, $beanFiles;
		static $clauses = array();
		if (isset($clauses[$module])) {
			return $clauses[$module];
		}
		$clsname = $beanList[$module];
		require_once $beanFiles[$clsname];
		if ( ACLController::requireOwner($module, 'list') ){
			$bean = new $clsname;
			$level = ACLAction::getUserActions($current_user->id, false, $module, 'module', 'list');
			$ownerWhere = $bean->getOwnerWhere($current_user->id, $level['aclaccess'], $table, $field);
			$clause = 'AND ' . $ownerWhere;
		} else {
			$clause = '';
		}
		$clauses[$module] = $clause;
		return $clause;
	}

	function hasAccess($module, $id, $assigned_user_id, $action)
	{
	
		if(($module == 'Tasks' || $module == 'ProjectTask') && $action == 'edit')
			return false; // cannot edit using meeting editor
		
		require_once("modules/SecurityGroups/SecurityGroup.php");
		$in_group = SecurityGroup::groupHasAccess($module, $id); 

		return ACLController::checkAccess($module, $action, $assigned_user_id, $in_group);
	}
}
?>
