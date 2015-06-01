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
require_once("modules/Users/User.php");
global $sugar_flavor;
if ($sugar_flavor == "PRO") {
	require_once("modules/Calendar/ExtClassForCalendar/TeamEx.php");
}

class TeamDayCalendarViewer extends CalendarViewer {
	var $viewType = "team_day";
	var $templatePath = "modules/Calendar/TeamDayCalendarView.tpl";
	var $calendarCtrl = "TeamDayCalendarCtrl";
	
	function TeamDayCalendarViewer(&$params) {
		parent::CalendarViewer($params);
	}

	function assignFields() {
		global $current_user;

		parent::assignFields();
		$target_team_id = "";
		if(isset($this->params['target_team_id']) && !empty($this->params['target_team_id'])) {
			$target_team_id = $this->params['target_team_id'];
		} else {
			$target_team_id = $current_user->default_team;
		}
		$target_team_name = TeamEx::getNameById($target_team_id);
		if($target_team_name === false) {
			$target_team_id = $current_user->default_team;
			$target_team_name = TeamEx::getNameById($target_team_id);
		}
	
		$targetDate = "";
		if(isset($this->params['target_date'])) {
			$targetDate = $this->params['target_date'];
		} 
		
		$calDateTime = new CalendarDateTime($targetDate, true, CAL_BASE_LOCAL, true);
		$this->tpl->assign('cal_dt', $calDateTime->toArrayLocalDateTime());
		$this->tpl->assign('weekdayfonts', CalendarDataUtil::getWeekDayFontCss());
		
		$this->tpl->assign('target_date', $calDateTime->localDate);
		$this->tpl->assign('target_team_id', $target_team_id);
		$this->tpl->assign('target_team_name', $target_team_name);

		$publicTeams = TeamEx::getPublicTeams();
		if(!array_key_exists($target_team_id, $publicTeams)) {
			$publicTeams[$target_team_id] = $target_team_name;
		}
		$this->tpl->assign('team_options', get_select_options_with_id($publicTeams, $target_team_id));

		$teams = array($target_team_id => array('name' => $target_team_name));
		//Team::getChildTeams($target_team_id, 0, $teams);
		$users = UserEx::getUsersBelongingByTeamIds(array_keys($teams));
		
		//TODO make this code dynamic
		$displayStartDateTime = $calDateTime->localDate . " 07:00:00";
		$displayEndDateTime = $calDateTime->localDate . " 22:00:00";
		
		$isPreDisplay = false;
		$isAfterDisplay = false;
		
		$activityEveryUsers = array();
		
		if(is_array($users)) {
			if(array_key_exists($current_user->id, $users)) {
				$activityEveryUsers[$current_user->id] = "";
			}
		}
		
		foreach($users as $user_id => $user) {
			$maxLevel = 0;

			$activityEveryUsers[$user_id] = array(
				'full_name' => $user,
				'activities' => array(),
				'activities_of_level' => array()
			);

			global $sugar_flavor;
			if ($sugar_flavor == "PRO") {
				$activities = CalendarDataUtil::getActivitiesOnDay($calDateTime, 'user', $user_id, true);
			} else {
				$activities = CalendarDataUtil::getActivitiesOnDay($calDateTime, 'user', $user_id, false);
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
				
				if($activity['startDateTime'] < $displayStartDateTime) {
					$isPreDisplay = true;
				}
				if($activity['endDateTime'] > $displayEndDateTime) {
					$isAfterDisplay = true;
				}
			}
			$activityEveryUsers[$user_id]['max_level'] = $maxLevel;
		}
		
		$hours = CalendarDataUtil::getWorkHourArray("", $isPreDisplay, $isAfterDisplay);
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
		
		$this->tpl->assign('activities_every_user_array', $activityEveryUsers);
	}
}
