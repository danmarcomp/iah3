<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
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

require_once("modules/Calendar/CalendarViewer/CalendarViewer.php");

class WeekCalendarViewer extends CalendarViewer {
	var $viewType = "week";
	var $templatePath = "modules/Calendar/WeekCalendarView.tpl";
	var $calendarCtrl = "WeekCalendarCtrl";	
	
	function WeekCalendarViewer(&$params) {
		parent::CalendarViewer($params);
	}

	function assignFields() {
		global $current_user;
		global $mod_strings, $app_list_strings, $app_strings;
		global $sugar_flavor;

		parent::assignFields();
		$this->assignMode();
		
		$targetDate = "";
		if(isset($this->params['target_date'])) {
			$targetDate = $this->params['target_date'];
		}
        $calDateTime = new CalendarDateTime($targetDate, true, CAL_BASE_LOCAL, true);
		
		$targetType= "user";
		if(isset($this->params['target_type']) && !empty($this->params['target_type'])) {
			$targetType = $this->params['target_type'];
		}
		$this->tpl->assign('target_type', $targetType);
		
		$targetId = $current_user->id;
		if(isset($this->params['target_id']) && !empty($this->params['target_id'])) {
			$targetId = $this->params['target_id'];
		}

		$calendarMode = new CalendarMode($this->getMode());
		$targets = $calendarMode->getTargetsList($targetId, $targetType);
		
		$this->assignModeData($targetId);
        $this->assignTimesheets($targets, $calDateTime);        
		
		if ($sugar_flavor == "PRO") {
			$target_team_id = "";
			if(isset($this->params['target_team_id']) && !empty($this->params['target_team_id'])) {
				$target_team_id = $this->params['target_team_id'];
			} else {
				$target_team_id = $current_user->default_team;
			}
			$this->tpl->assign('target_team_id', $target_team_id);
		}		
		
		$this->tpl->assign('cal_dt', $calDateTime->toArrayLocalDateTime());
		$this->tpl->assign('weekdayfonts', CalendarDataUtil::getWeekDayFontCss());
		$this->tpl->assign('target_date', $calDateTime->localDate);
		$this->tpl->assign('formated_target_date', $calDateTime->formattedWeekRange);
		$this->tpl->assign('next_date', $calDateTime->localNextWeekDate);
		$this->tpl->assign('prev_date', $calDateTime->localPrevWeekDate);
		$this->tpl->assign('to_day', CalendarDataUtil::getLocalToDay());
		$this->tpl->assign('local_current_hour', CalendarDataUtil::getLocalCurrentHour());
		
		$showWeekends = ! $this->forDashlet;
		$weekDays = CalendarDataUtil::getWeekDays($calDateTime, null, $showWeekends);
		$weekDays = array_values($weekDays);
		$this->tpl->assign('weekdays', $weekDays);
		$this->tpl->assign('weekdayfonts', CalendarDataUtil::getWeekDayFontCss());

        $hours_full = CalendarDataUtil::getHourArray();
        $hours = array_keys($hours_full);
        $this->tpl->assign('hours', $hours_full);
        $this->tpl->assign('start_hour', $hours[0]);
        $this->tpl->assign('end_hour', $hours[sizeof($hours) - 1]);
        $this->start_hour = $hours[0];
        $this->end_hour = $hours[sizeof($hours) - 1];

        //ToDo refine the query
		$activities = array();

		$offsetIndex = -1;
		$summary = array();

		$startHour = $current_user->day_begin_hour;
		$endHour = $current_user->day_end_hour;

		if(empty($startHour) && empty($endHour)) {
			$startHour = 9;
			$endHour = 18;
		}

		foreach($weekDays as $weekIndex => $weekDay) {
			$offsetIndex ++;
			$targetDT = new CalendarDateTime($weekDay['date'], true, CAL_BASE_LOCAL, true);
			global $sugar_flavor;
			if ($sugar_flavor == "PRO") {
				$activitiesOnDay = CalendarDataUtil::getActivitiesOnDay($targetDT, $targetType, $targets, true,
						120, 120*$offsetIndex, 18, false, array(), true);
			} else {
				if ($targetType == 'all' && is_null($targets)) {
					$activitiesOnDay = array();
				} else {
					$activitiesOnDay = CalendarDataUtil::getActivitiesOnDay($targetDT, $targetType, $targets, false,
							115, 120*$offsetIndex, 18, false, array(), true);
				}
			}
			if (isset($activitiesOnDay['summary'])) {
				$summary[$offsetIndex] = array(
					'text' => $activitiesOnDay['summary'],
					'offset' => 50 + $offsetIndex * 120,
				);
				unset($activitiesOnDay['summary']);
			}
			foreach ($activitiesOnDay as $i => $act) {
				$activitiesOnDay[$i]['offsetIndex'] = $offsetIndex;
			}
			$activities = array_merge($activities, $activitiesOnDay);
		}

		foreach ($activities as $i => $act) {
			if ($act['module'] == 'Tasks') {
				$activities[$i]['displayTime'] = $act['startTime'];
			} elseif ($act['module'] == 'Vacations') {
				$activities[$i]['displayTime'] = '';
			} elseif ($act['module'] == 'EventSessions') {
				$startDate = substr($act['startDateTime'], 0, 10);
				$endDate = substr($act['endDateTime'], 0, 10);
				if ($startDate >= $act['forDate']) {
					$start = substr($act['startDateTime'], 11,5);
				} else {
					$start = $startHour . ':00';
				}
				if ($endDate <= $act['forDate']) {
					$end = substr($act['endDateTime'], 11,5);
				} else {
					$end = $endHour . ':00';
				}
				$activities[$i]['displayTime'] = "{$start}-{$end}";
			} else {
				$activities[$i]['displayTime'] = "{$act['startTime']}-{$act['endTime']}";
			}
			if(! empty($act['isViewAble'])) {
				$this->addActivity(array('id' => $act['id'], 'module' => $act['module']));
			}
		}


		$this->tpl->assign('summary', $summary);
		$this->tpl->assign('activities', $activities);		
		
		$this->tpl->assign('week_first_date', $calDateTime->localWeekFirstDate);
		
		$todayWeekIndex = -1;
		foreach($weekDays as $index => $weekDay) {
			if($weekDay['isToday']) {
				$todayWeekIndex = $index;
				break;				
			}
		}
		$this->tpl->assign('today_weekindex', $todayWeekIndex);

		if (! $showWeekends) {
			$this->tpl->assign('body_width', 648);
		} else  {
			$this->tpl->assign('body_width', 888);
		}
		$this->tpl->assign('top_offset', 22);
	}
}
