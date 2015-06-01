<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/*********************************************************************************
 * The contents of this file are subject to the SugarCRM Public License Version
 * 1.1.3 ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied.  See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * All copies of the Covered Code must include on each user interface screen:
 *    (i) the "Powered by SugarCRM" logo and
 *    (ii) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.  See full license for
 * requirements.
 *
 * The Original Code is: SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) 2004-2006 SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 ********************************************************************************/
/*********************************************************************************

 * Description:
 ********************************************************************************/




require_once('data/SugarBean.php');
require_once('modules/Calendar/Calendar.php');

class vCal extends SugarBean {
	// Stored fields
	var $id;
	var $date_modified;
	var $user_id;
	var $content;
	var $deleted;
	var $type;
	var $source;
	var $module_dir = "vCals";
	var $table_name = "vcals";
	var $object_name = "vCal";

	var $new_schema = true;


	function vCal() {
		parent::SugarBean();
		$this->disable_row_level_security = true;
	}
	
	
	static function get_auto_expire_seconds() {
		return AppConfig::setting('site.performance.vcal_cache_time');
	}
	
	
	static function get_freebusy_vcals($user_id) {
		// First, get the list of IDs.
		$lq = new ListQuery('vCal', array('id', 'content', 'source', 'date_modified'));
		$lq->addSimpleFilter('user_id', $user_id, '=');
		$lq->addSimpleFilter('type', 'vfb', '=');
		return $lq->fetchAll();
	}
	
	
	static function get_vcal_freebusy_lines($vcal_content) {
		$ret = array();
		$lines = explode("\n", $vcal_content);
		foreach ($lines as $line)
			if ( substr($line, 0, 9) == 'FREEBUSY:')
				$ret[] = $line;
		return $ret;
	}


    // combines all freebusy vcals and returns just the FREEBUSY lines as a string
	static function get_freebusy_lines($user_id, $as_array=false, $start_date=null, $end_date=null) {
		$ret = array();
		$sugar_result = null;
		$sugar_found = false;
		$cals = self::get_freebusy_vcals($user_id);
		
		if($cals && ! $cals->failed) {
			foreach($cals->getRows() as $idx => $row) {
				if($row['source'] == 'sugar') {
					$dt = strtotime($row['date_modified'].' GMT');
					if(! $dt || time() - $dt > self::get_auto_expire_seconds()) {
						$sugar_result = $cals->getRowResult($idx);
						continue;
					}
					$sugar_found = true;
				}
				array_extend($ret, self::get_vcal_freebusy_lines($row['content']));
			}
		}
		
		if(! $sugar_found) {
			$result = self::create_sugar_freebusy($user_id, $sugar_result);
			if($result) {
				array_extend($ret, self::get_vcal_freebusy_lines($result->getField('content')));
			}
		}
		
		if($as_array) return $ret;

		return implode("\n", $ret);
	}
	
	
	static function get_freebusy_array($user_id, $gmt=false, $date_format='db') {
		global $timedate;
		$rows = self::get_freebusy_lines($user_id, true);
		$from = '/^(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})(\d{2})Z$/';
		$to = '\1-\2-\3 \4:\5:\6';
		$offs = $timedate->getTimeZoneOffset($timedate->getUserTimeZone(), true);
		if($date_format == 'user')
			$date_format = $timedate->get_date_time_format();
		else if($date_format == 'js')
			$date_format = 'Y/m/d H:i:s';
		else if($date_format == 'fb')
			$date_format = 'Ymd\THis\Z';
		else
			$date_format = $timedate->get_db_date_time_format();
		$ret = array();
		foreach($rows as $fb) {
			$times = explode('/', substr($fb, 9));
			if(count($times) == 2) {
				if($date_format != 'fb') {
					foreach($times as $idx => $t) {
						$t = strtotime(preg_replace($from, $to, $t));
						if(! $gmt) $t += $offs;
						$t = date($date_format, $t);
						$times[$idx] = $t;
					}
				}
				$ret[] = $times;
			}
		}
		return $ret;
	}
	
	
	static function get_date_range() {
		$start_ts = strtotime('-1 month');
		$end_ts = strtotime('+3 months');
		$start_ts -= $start_ts % 3600; // round down to hour for more stable timestamp
		$end_ts -= $end_ts % 3600;
		$start = gmdate('Y-m-d H:i:s', $start_ts);
		$end = gmdate('Y-m-d H:i:s', $end_ts);
		$start_utc = gmdate('Ymd\\THis\\Z', $start_ts);
		$end_utc = gmdate('Ymd\\THis\\Z', $end_ts);
		$now_utc = gmdate('Ymd\\THis\\Z');
		return compact('start', 'end', 'start_ts', 'end_ts', 'start_utc', 'end_utc', 'now_utc');
	}
	
	
	static function get_user_info($user_id) {
		global $current_user;
		static $cache;
		if(isset($cache[$user_id]))
			return $cache[$user_id];
		if($user_id == AppConfig::current_user_id() && ! empty($current_user)) {
			$name = from_html(trim($current_user->first_name. ' '. $current_user->last_name));
			$email = from_html($current_user->email1);
			if(! $email) $email = from_html($current_user->email2);
		} else {
			$user = ListQuery::quick_fetch('User', $user_id, array('first_name', 'last_name', 'email1', 'email2', 'status'));
			if(! $user || $user->getField('status') == 'Inactive')
				return null;
			$name = trim($user->getField('first_name'). ' '. $user->getField('last_name'));
			$email = $user->getField('email1');
			if(! $email) $email = $user->getField('email2');
		}
		$ret = compact('name', 'email');
		$cache[$user_id] = $ret;
		return $ret;
	}
	
	
	static function encode_vcal($name, $email, $dates, $lines) {
		$str = "BEGIN:VCALENDAR\n";
		$str .= "VERSION:2.0\n";
		$str .= "PRODID:-//InfoAtHand//InfoAtHand Calendar//EN\n";
		$str .= "BEGIN:VFREEBUSY\n";		
		$str .= "ORGANIZER;CN={$name}:{$email}\n";
		$str .= "DTSTART:{$dates['start_utc']}\n";
		$str .= "DTEND:{$dates['end_utc']}\n";
		if(is_array($lines)) $lines = rtrim(implode("\n", $lines)) . "\n";
		$str .= $lines;
		$str .= "DTSTAMP:{$dates['now_utc']}\n";
		$str .= "END:VFREEBUSY\n";
		$str .= "END:VCALENDAR\n";
		return $str;
	}
	
	
	static function get_combined_freebusy($user_id) {
		$user = self::get_user_info($user_id);
		if(! $user)
			return null;
		$dates = self::get_date_range();
		$lines = self::get_freebusy_lines($user_id, false, $dates['start'], $dates['end']);
		$content = self::encode_vcal($user['name'], $user['email'], $dates, $lines);
		return $content;
	}
	
	
	static function create_sugar_freebusy($user_id, $prev_result=null) {
		$user = self::get_user_info($user_id);
		if(! $user)
			return null;
		$dates = self::get_date_range();
		$lines = self::create_freebusy_lines($user_id, $dates['start'], $dates['end']);
		$content = self::encode_vcal($user['name'], $user['email'], $dates, $lines);
		
		if(! $prev_result) {
			$lq = new ListQuery('vCal');
			$lq->addSimpleFilter('user_id', $user_id, '=');
			$lq->addSimpleFilter('type', 'vfb', '=');
			$lq->addSimpleFilter('source', 'sugar', '=');
			$lq->filter_deleted = false;
			$prev_result = $lq->runQuerySingle();
		}

		if($prev_result && ! $prev_result->failed)
			$upd = RowUpdate::for_result($prev_result);
		else
			$upd = RowUpdate::blank_for_model('vCal');
		
		$upd->set(array(
			'content' => $content,
			'type' => 'vfb',
			'source' => 'sugar',
			'user_id' => $user_id,
			'deleted' => 0,
		));

		if($upd->save()) {
			$vcal_id = $upd->getPrimaryKeyValue();
			// remove any other 'sugar' vcals for this user (DB index does not prevent duplicates)
			$GLOBALS['db']->query("DELETE FROM vcals WHERE user_id='{$user_id}' AND type='vfb' AND source='sugar' AND id != '$vcal_id'", false);
			return $upd;
		}
	}
	
	
	// erase (invalidate) any old vcal
	static function clear_sugar_vcal($user_id) {
		global $db;
		if(! $user_id) return;
		$qid = $db->quote($user_id);
		$q = "UPDATE vcals SET deleted='1' WHERE type='vfb' AND source='sugar' AND user_id='$qid'";
		$db->query($q);
	}


	static function create_freebusy_lines($user_id, $start_date_time, $end_date_time) {
		global $db;
		$str = '';
		$format = '%Y%m%dT%H%i%sZ';
		$qid = $db->quote($user_id);
		$template = <<<EOT
			SELECT DATE_FORMAT(m.date_start, '%s') AS start,
			DATE_FORMAT(DATE_ADD(m.date_start, INTERVAL m.duration MINUTE), '%s') AS end
			FROM %s m
			LEFT JOIN %s mu
			ON m.id = mu.%s AND mu.user_id='%s' AND IFNULL(mu.accept_status,'accept') != 'decline' AND NOT mu.deleted
			WHERE m.date_start BETWEEN '$start_date_time' AND '$end_date_time'
			AND m.deleted = 0 AND mu.user_id IS NOT NULL
EOT;
		$query = '(' . sprintf($template, $format, $format, 'meetings', 'meetings_users', 'meeting_id', $qid) . ') UNION (';
		$query .= sprintf($template, $format, $format, 'calls', 'calls_users', 'call_id', $qid) . ') UNION (';
		
		$template = <<<EOT
			SELECT DATE_FORMAT(m.date_start, '%s') AS start,
			DATE_FORMAT(DATE_ADD(m.date_start, INTERVAL IF(m.effort_actual,m.effort_actual,m.effort_estim) MINUTE), '%s') AS end
			FROM %s m
			WHERE m.date_start IS NOT NULL AND m.date_start BETWEEN '$start_date_time' AND '$end_date_time'
			AND m.assigned_user_id='%s' AND m.status != 'Deferred' AND m.deleted = 0
EOT;
		$query .= sprintf($template, $format, $format, 'tasks', $qid) . ') ORDER BY start';

		$res = $db->query($query, true);
		while ($row = $db->fetchByAssoc($res, -1, false)) {
			$str .= "FREEBUSY:". $row['start'] . '/' . $row['end'] . "\n";
		}
		return $str;
	}


	function create_sugar_ical_todo($task, $moduleName) {
		$str = "";
		$str .= "BEGIN:VTODO\n";
		$validDueDate = (isset($task->date_due) && $task->date_due != "" && $task->date_due != "0000-00-00 00:00:00");
		if ($validDueDate) {
            $dateDue = explode(" ", $task->date_due);
			$dateDueArr = explode("-", $dateDue[0]);
			$timeDueArr = explode(":", $dateDue[1]);
			$date_arr = array(
             'day'=>$dateDueArr[2],
             'month'=>$dateDueArr[1],
             'hour'=>$timeDueArr[0],
             'min'=>$timeDueArr[1],
             'year'=>$dateDueArr[0]);
		} else {
			$date_arr = array(
			'day'=>"01",
             'month'=>"01",
             'hour'=>"01",
             'min'=>"01",
             'year'=>"1970");
		}
		$due_date_time = new DateTimeUtil($date_arr,true);
		$str .= "DTSTART:" . $due_date_time->get_utc_date_time() . "\n";
		$str .= "SUMMARY:" . ical_escape_value($task->name) . "\n";
		$str .= "UID:" . $task->id . "\n";
		if ($validDueDate) {
			$str .= "DUE:" . $due_date_time->get_utc_date_time() . "\n";
		}
		$str .= "DESCRIPTION:Parent: " . ical_escape_value($task->parent_name). "\\n\\n" . 
			ical_escape_value($task->description). "\n";
		$str .= "URL;VALUE=URI:" . AppConfig::site_url(). 
			"index.php?module=".$moduleName."&action=DetailView&record=". $task->id. "\n";
		if ($task->status == 'Completed') {
			$str .= "STATUS:COMPLETED\n";
			$str .= "COMPLETED:" . $due_date_time->get_utc_date_time() . "\n";
		}
		if ($task->priority == "Low") {
			$str .= "PRIORITY:9\n";
		} else if ($task->priority == "Medium") {
				$str .= "PRIORITY:5\n";
		} else if ($task->priority == "High") {
				$str .= "PRIORITY:1\n";
		}
		$str .= "END:VTODO\n";
		return $str;
	}

	// query and create the iCal Events for SugarCRM Meetings and Calls and 
        // return the string	
	function create_sugar_ical(&$user_bean, $start_date_time, $end_date_time)
	{
		$str = '';
		global $DO_USER_TIME_OFFSET;
		
		$start_dtu = DateTimeUtil::parse_date_time($start_date_time);
		$end_dtu = DateTimeUtil::parse_date_time($end_date_time);

		$DO_USER_TIME_OFFSET = true;
		// get activities.. queries Meetings and Calls
		$acts_arr = CalendarActivity::get_activities($user_bean->id, false, $start_dtu, $end_dtu);

		// loop thru each activity, get start/end time in UTC, and return iCal strings
		for ($i = 0;$i < count($acts_arr);$i++)
		{
			$act =$acts_arr[$i];
			
			$start_time = $act->start_time;
			$end_time = $act->end_time;

			$event = $act->sugar_bean;
			$str .= "BEGIN:VEVENT\n";
			$str .= "SUMMARY:".ical_escape_value($event->name). "\n";
			$str .= "DTSTART:" . $start_time->get_utc_date_time() . "\n";
			$str .= "DTEND:". $end_time->get_utc_date_time() . "\n";
			$str .= "DESCRIPTION:" . ical_escape_value($event->description) . "\n";
			$str .= "URL;VALUE=URI:" . AppConfig::site_url(). 
				"index.php?module=".$event->module_dir."&action=DetailView&record=". $event->id. "\n";
			$str .= "UID: " . $event->id . "\n";
			if ($event->object_name == "Meeting") {
				$str .= "LOCATION:" . ical_escape_value($event->location) . "\n";
				$eventUsers = $event->get_meeting_users();
				$query = "SELECT contact_id as id from meetings_contacts where meeting_id='$event->id' AND deleted=0";
				$eventContacts = $event->build_related_list($query, new Contact());
				$eventAttendees = array_merge($eventUsers, $eventContacts);
				if (is_array($eventAttendees)) {
					foreach($eventAttendees as $attendee) {
						if ($attendee->id != $user_bean->id) {
							$str .= 'ATTENDEE;CN="'.ical_escape_value($attendee->get_summary_text()).'":mailto:'. ical_escape_value($attendee->email1) . "\n";
						}
					}
				}
			}
			if ($event->object_name == "Call") {
				$eventUsers = $event->get_call_users();
				$eventContacts = $event->get_contacts();
				$eventAttendees = array_merge($eventUsers, $eventContacts);
				if (is_array($eventAttendees)) {
					foreach($eventAttendees as $attendee) {
						if ($attendee->id != $user_bean->id) {
							$str .= 'ATTENDEE;CN="'.ical_escape_value($attendee->get_summary_text()).'":mailto:'. ical_escape_value($attendee->email1) . "\n";
						}
					}
				}
			}
			if ($event->reminder_time > 0)
			{
				$str .= "BEGIN:VALARM\n";
				$str .= "TRIGGER:-PT" . $event->reminder_time/60 . "M\n";
				$str .= "ACTION:DISPLAY\n";
				$str .= "DESCRIPTION:" . ical_escape_value($event->name) . "\n";
				$str .= "END:VALARM\n";
			}
			$str .= "END:VEVENT\n";

		}
		
		global $timedate;
		$today = gmdate("Y-m-d");
		$today = $timedate->handle_offset($today, $timedate->dbDayFormat, false);
		
		require_once('modules/ProjectTask/ProjectTask.php');
		$where = "project_task.assigned_user_id='{$user_bean->id}' ".
			"AND (project_task.status IS NULL OR (project_task.status!='Deferred')) ".
			"AND (project_task.date_start IS NULL OR DATE(project_task.date_start) <= '$today')";
		$seedProjectTask = new ProjectTask();
		$projectTaskList = $seedProjectTask->get_full_list("", $where);
		if (is_array($projectTaskList)) {
			foreach($projectTaskList as $projectTask) {
				$str .= $this->create_sugar_ical_todo($projectTask, "ProjectTask");
			}
		}
		
		require_once('modules/Tasks/Task.php');
		$where = "tasks.assigned_user_id='{$user_bean->id}' ".
			"AND (tasks.status IS NULL OR (tasks.status!='Deferred')) ".
			"AND (tasks.date_start IS NULL OR DATE(tasks.date_start) <= '$today')";
		$seedTask = new Task();
		$taskList = $seedTask->get_full_list("", $where);
		if (is_array($taskList)) {
			foreach($taskList as $task) {
				$str .= $this->create_sugar_ical_todo($task, "Tasks");
			}
		}

		return $str;

	}


		// return a iCal vcal string 
	function get_vcal_ical(&$user_focus)
	{
		$name = ical_escape_value(trim($user_focus->first_name. " ". $user_focus->last_name));
		$email = ical_escape_value($user_focus->email1);
		
		$str = "BEGIN:VCALENDAR\n";
		$str .= "VERSION:2.0\n";
		$str .= "METHOD:PUBLISH\n";
		$str .= "X-WR-CALNAME:$name (info@hand)\n";
		$str .= "PRODID:-//InfoAtHand//InfoAtHand Calendar//EN\n";
		$str .= "X-WR-TIMEZONE:".$user_focus->getPreference('timezone')."\n";
		$str .= "CALSCALE:GREGORIAN\n";                                                                                                   
																							   
		$dates = self::get_date_range();
		
		$str .= "ORGANIZER;CN=$name:$email\n";
		$str .= "DTSTART:{$dates['start_utc']}\n";
		$str .= "DTEND:{$dates['end_utc']}\n";
		$str .= $this->create_sugar_ical($user_focus, $dates['start'], $dates['end']);

		// UID:20030724T213406Z-10358-1000-1-12@phoenix
		$str .= "DTSTAMP:{$dates['now_utc']}\n";
		$str .= "END:VCALENDAR\n";
		return $str;
	}

}

function ical_escape_value($val, $from_html=true) {
	if($from_html) $val = from_html($val);
	return addcslashes(str_replace("\r", '', $val), "\":;,\n");
}

?>
