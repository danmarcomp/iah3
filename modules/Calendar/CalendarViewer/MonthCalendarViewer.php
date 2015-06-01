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

class MonthCalendarViewer extends CalendarViewer {
	var $viewType = "month";
	var $templatePath = "modules/Calendar/MonthCalendarView.tpl";
	var $calendarCtrl = "MonthCalendarCtrl";

	function MonthCalendarViewer(&$params) {
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
		$targets = $calendarMode->getTargetsList($targetId, $targetType, true);
		
		$this->assignModeData($targetId);
        $this->assignTimesheets($targets, $calDateTime);
		
		$target_team_id = "";
		if ($sugar_flavor == "PRO") {
			if(isset($this->params['target_team_id']) && !empty($this->params['target_team_id'])) {
				$target_team_id = $this->params['target_team_id'];
			} else {
				$target_team_id = $current_user->default_team;
			}
			$target_team_name = get_assigned_team_name($target_team_id);
			$this->tpl->assign('target_team_id', $target_team_id);
			$this->tpl->assign('target_team_name', $target_team_name);
		}
		
		$this->tpl->assign('cal_dt', $calDateTime->toArrayLocalDateTime());
		$this->tpl->assign('target_date', $calDateTime->localDate);
		$this->tpl->assign('next_date', $calDateTime->localNextMonthDate);
		$this->tpl->assign('prev_date', $calDateTime->localPrevMonthDate);
		$this->tpl->assign('to_day', CalendarDataUtil::getLocalToDay());
		$this->tpl->assign('local_current_hour', CalendarDataUtil::getLocalCurrentHour());
		
		$this->tpl->assign('weekdayfonts', CalendarDataUtil::getWeekDayFontCss());
		
		$activitiesOfWeekArray = array();
		$firstDate = "{$calDateTime->localYear}-{$calDateTime->localMonth}-01";
		$firstDayDT = new CalendarDateTime($firstDate, true, CAL_BASE_LOCAL, true);

		$nextMonthFirstDT = $firstDayDT->getNextMonthDateTime(true);

		$workDT = new CalendarDateTime($firstDayDT->localWeekFirstDate, true, CAL_BASE_LOCAL, true);
		$showWeekends = ! $this->forDashlet;
		
		while($workDT->localDateTime_t < $nextMonthFirstDT->localDateTime_t) {
			$weekDays = CalendarDataUtil::getWeekDays($workDT, $calDateTime->localMonth, $showWeekends);
			if($targetType == 'user' || $targetType == 'all') {
				$activitiesOfWeekArray[] = array(
					'week_days' => $weekDays,
					'weekDT'  => $workDT,
					'week_number' => sprintf($this->calendar_strings['FORMAT_WEEK_NUMBER'], $workDT->localWeekNumber),
					'activities_of_week' => is_null($targets) ? array() : CalendarDataUtil::getWeekActivitiesEveryUser($workDT, $targets, false, $showWeekends)
				);
			} else {
				$activitiesOfWeekArray[] = array(
					'week_days' => $weekDays,
					'weekDT'  => $workDT,
					'week_number' => sprintf($this->calendar_strings['FORMAT_WEEK_NUMBER'], $workDT->localWeekNumber),
					'activities_of_week' => CalendarDataUtil::getWeekActivitiesEveryResource($workDT, $targets, false, $showWeekends)
				);
			}
			
			$workDT = $workDT->getNextWeekDateTime(true);
		}
		$this->tpl->assign('activities_of_weeks_array', $activitiesOfWeekArray);
	}
}
