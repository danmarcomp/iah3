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


class LightUsersDayCalendarViewer extends CalendarViewer {
	var $viewType = "users_day";
	var $templatePath = "modules/Calendar/CalendarViewer/LightUsersDayCalendarView.tpl";
	//var $calendarCtrl = "UsersDayCalendarCtrl";

	var $users = array();
	var $exceptMeetingIds = array();
	var $targetMeetingDate = "";
	var $targetMeetingStartTime = "";
	var $targetMeetingDurHour = "";
	var $targetMeetingDurMin = "";
	
	function UsersDayCalendarViewer(&$params) {
		parent::CalendarViewer($params);
	}

	function assignTab() {
	}

	function setUsers(&$users) {
		$this->users = $users;
	}
	
	function setExceptMeetingIds(&$ids) {
		$this->exceptMeetingIds = $ids;
	}
	
	function setTargetMeetingTime($date, $startTime, $durHour, $durMin) {
		$this->targetMeetingDate = $date;
		$this->targetMeetingStartTime = $startTime;
		$this->targetMeetingDurHour = $durHour;
		$this->targetMeetingDurMin = $durMin;
	}

	function assignFields() {
		global $current_user;
		global $image_path;
		
		parent::assignFields();
		
		$targetDate = "";
		if(isset($this->params['target_date'])) {
			$targetDate = $this->params['target_date'];
		} 
		$calDateTime = new CalendarDateTime($targetDate, true, CAL_BASE_LOCAL, true);
		$this->tpl->assign('cal_dt', $calDateTime->toArrayLocalDateTime());
		$this->tpl->assign('weekdayfonts', CalendarDataUtil::getWeekDayFontCss());
		$this->tpl->assign('target_date', $calDateTime->localDate);

		//TODO 7時開始のハードコードを動的に
		$displayStartDateTime = $calDateTime->localDate . " 07:00:00";
		$displayEndDateTime = $calDateTime->localDate . " 22:00:00";
		
		$displayStartDateTime_t = strtotime($displayStartDateTime);
		$displayEndDateTime_t = strtotime($displayEndDateTime);
		
		
		$isPreDisplay = false;
		$isAfterDisplay = false;
		
		$activityEveryUsers = array();
		
		//add target meeting
		if(!empty($this->targetMeetingDate)) {
			$startDateTime = "{$this->targetMeetingDate} {$this->targetMeetingStartTime}";
			$startDT = new CalendarDateTime($startDateTime, true);
			
			if($startDT->localDate == $calDateTime->localDate) {
				//$GLOBALS['log']->fatal(var_export($startDT, true));
				$durationMin = $this->targetMeetingDurHour * 60 + $this->targetMeetingDurMin;				
				$endDateTime = date("Y-m-d H:i:s", strtotime("+{$durationMin} minute", strtotime($startDT->localDateTime)));

				if($startDT->localDateTime_t < $calDateTime->localDateTime_t) {
					$diffSec = strtotime($calDateTime->localDateTime) - strtotime($startDT->localDateTime);
					$startOffsetMinutes = 0;
					$durationMin = $durationMin - ($diffSec/60);
				} else {
					$startOffsetMinutes = $startDT->localHour * 60 + $startDT->localMin;
				}
				
				$activityEveryUsers[0] = array(
					'full_name' => $this->calendar_strings['LBL_TARGET_MEETING_TIME'],
					'activities' => array(
						'0' => array (
					        'startOffsetMinutes' => $startOffsetMinutes,
					        'durationMin' => $durationMin,
					        'subject' => '',
					        'startDateTime' => $startDateTime,
					        'endDateTime' => $endDateTime,
					        'imgHTML' => '',
					        'duplicateLevel' => 0,
					        'isDuplicate' => false,
					        'module' => 'Meetings',
					        'isPublish' => '1',
					        'isViewAble' => false,
					        'schedule_target' => true,
						),
					),
					'activities_of_level' => array()
				);
				
				if(strtotime($startDateTime) < $displayStartDateTime_t) {
					$isPreDisplay = true;
				}
				if(strtotime($endDateTime) > $displayEndDateTime_t) {
					$isAfterDisplay = true;
				}
			}
		}
		
		//add current user
		if(array_key_exists($current_user->id, $this->users)) {
			$activityEveryUsers[$current_user->id] = "";
		}
		
		CalendarDataUtil::param('display_project_tasks', 0); // ignore project tasks

		foreach($this->users as $user_id => $user) {
			$maxLevel = 0;

			$activityEveryUsers[$user_id] = array(
				'full_name' => $user,
				'activities' => array(),
				'activities_of_level' => array()
			);

			global $sugar_flavor;
			if ($sugar_flavor == "PRO") {
				$activities = CalendarDataUtil::getActivitiesOnDay($calDateTime, 'user', array($user_id), true, 
					CAL_DAY_ACTIVITY_WIDTH, 0, CAL_DUP_OFFSET, true, $this->exceptMeetingIds);
			} else {
				$activities = CalendarDataUtil::getActivitiesOnDay($calDateTime, 'user', array($user_id), false, 
					CAL_DAY_ACTIVITY_WIDTH, 0, CAL_DUP_OFFSET, true, $this->exceptMeetingIds);
			}
			
			foreach($activities as $activityId => $activity) {
				$level = $activity['duplicateLevel'];
				if($maxLevel < $level) {
					$maxLevel = $level;
				}
				if($level > 0) {
					$activityEveryUsers[$user_id]['activities_of_level'][$level][$activityId] = $activity;
				} else {
					$activityEveryUsers[$user_id]['activities'][$activityId] = $activity;
				}
				
				if(strtotime($activity['startDateTime']) < $displayStartDateTime_t) {
					$isPreDisplay = true;
				}
				if(strtotime($activity['endDateTime']) > $displayEndDateTime_t) {
					$isAfterDisplay = true;
				}
			}
			$activityEveryUsers[$user_id]['max_level'] = $maxLevel;
		}
		
		$hours = CalendarDataUtil::getWorkHourArray('user_short', $isPreDisplay, $isAfterDisplay);
		$this->tpl->assign('hours', $hours);

		if($isPreDisplay) {
			$this->tpl->assign('start_offset_min', 0);
		} else {
			$this->tpl->assign('start_offset_min', 7*60);
		}
		
		if($isAfterDisplay) {
			$this->tpl->assign('end_offset_min', 24*60);
		} else {
			$this->tpl->assign('end_offset_min', 22*60);
		}
		
		$this->tpl->assign('USER_IDS', implode(",", array_keys($this->users)));
		
		//$GLOBALS['log']->fatal(var_export($activityEveryUsers, true));
		
		$this->tpl->assign('activities_every_user_array', $activityEveryUsers);
	}
}
