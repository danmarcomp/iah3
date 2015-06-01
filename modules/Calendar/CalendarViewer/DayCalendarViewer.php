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

class DayCalendarViewer extends CalendarViewer {
	var $viewType = "day";
	var $templatePath = "modules/Calendar/DayCalendarView.tpl";
	var $calendarCtrl = "DayCalendarCtrl";
	
	function DayCalendarViewer(&$params) {
		parent::CalendarViewer($params);
	}

	function assignFields() {
		global $current_user;
		global $mod_strings, $app_list_strings, $app_strings;		
		
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

		/*if ($targetType == 'user') {
			$grid = new GridEntry();
			$grid->setEntry($targetId, 'user');
		}*/
		
		$calendarMode = new CalendarMode($this->getMode());
		$targets = $calendarMode->getTargetsList($targetId, $targetType);

		$this->assignModeData($targetId);
        $this->assignTimesheets($targets, $calDateTime);
		
		$this->tpl->assign('target_date', $calDateTime->localDate);
		$this->tpl->assign('target_year', $calDateTime->localYear);
		$this->tpl->assign('target_month', $calDateTime->localMonth);
		$this->tpl->assign('target_day', $calDateTime->localDay);
		$this->tpl->assign('target_weekday', $calDateTime->localWeekDay);
		$this->tpl->assign('target_weekday_index', $calDateTime->localWeekDayIndex);
		$this->tpl->assign('target_rokuyo', $calDateTime->rokuyo);
		$this->tpl->assign('target_holiday', $calDateTime->holiday);

		$this->tpl->assign('weekdayfonts', CalendarDataUtil::getWeekDayFontCss());

		$this->tpl->assign('next_date', $calDateTime->localNextDate);
		$this->tpl->assign('prev_date', $calDateTime->localPrevDate);
		$this->tpl->assign('to_day', CalendarDataUtil::getLocalToDay());
		$this->tpl->assign('cal_dt', $calDateTime->toArrayLocalDateTime());
		
		$hours_full = CalendarDataUtil::getHourArray();
        $hours = array_keys($hours_full);
		$this->tpl->assign('hours', $hours_full);
        $this->tpl->assign('start_hour', $hours[0]);
        $this->tpl->assign('end_hour', $hours[sizeof($hours) - 1]);
        $this->start_hour = $hours[0];
        $this->end_hour = $hours[sizeof($hours) - 1];

		global $sugar_flavor;
		if ($sugar_flavor == "PRO") {
			$activities = CalendarDataUtil::getActivitiesOnDay($calDateTime, $targetType, $targets, true, array(CAL_DAY_ACTIVITY_WIDTH, CAL_DAY_ACTIVITY_WIDTH*2));
		} else {
			$activities = CalendarDataUtil::getActivitiesOnDay($calDateTime, $targetType, $targets, false, array(CAL_DAY_ACTIVITY_WIDTH, CAL_DAY_ACTIVITY_WIDTH*2), 0, -1, false, array(), false, false, $this->debug);
		}

		if($this->debug)
			pr2($activities, 'calendar activities');

		$summary = null;
		if (isset($activities['summary'])) {
			$summary = $activities['summary'];
			unset($activities['summary']);
		}

		$js_acts = array();
		foreach ($activities as $i => $act) {
			if ($act['module'] == 'Tasks') {
				$activities[$i]['displayTime'] = $act['startTime'];
			} elseif ($act['module'] == 'Vacations') {
				$activities[$i]['displayTime'] = '';
			} else {
				$activities[$i]['displayTime'] = "{$act['startTime']}-{$act['endTime']}";
			}
			if(! empty($act['isViewAble'])) {
				$this->addActivity(array('id' => $act['id'], 'module' => $act['module']));
			}
		}

		$this->tpl->assign('summary', $summary);
		unset($activities['summary']);

		$this->tpl->assign('activities', $activities);	
		$this->tpl->assign('hour_width', 46);
		$this->tpl->assign('top_offset', 1);
		$this->tpl->assign('day_min_width', CAL_DAY_WIDTH);
	}
}
