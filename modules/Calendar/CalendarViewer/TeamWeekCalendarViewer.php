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
global $sugar_flavor;
if ($sugar_flavor == "PRO") {
	require_once("modules/Calendar/ExtClassForCalendar/TeamEx.php");
}

class TeamWeekCalendarViewer extends CalendarViewer {
	var $viewType = "team_week";
	var $templatePath = "modules/Calendar/TeamWeekCalendarView.tpl";
	var $calendarCtrl = "TeamWeekCalendarCtrl";
	
	function TeamWeekCalendarViewer(&$params) {
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
		
		$this->tpl->assign('target_date', $calDateTime->localDate);
		$this->tpl->assign('target_team_id', $target_team_id);
		$this->tpl->assign('target_team_name', $target_team_name);
		
		$this->tpl->assign('formated_target_date', $calDateTime->localWeekFirstDate . ' - ' . $calDateTime->localWeekEndDate);
		$this->tpl->assign('next_date', $calDateTime->localNextWeekDate);
		$this->tpl->assign('prev_date', $calDateTime->localPrevWeekDate);
		$this->tpl->assign('to_day', CalendarDataUtil::getLocalToDay());
		$this->tpl->assign('local_current_hour', CalendarDataUtil::getLocalCurrentHour());
		
		$weekDays = CalendarDataUtil::getWeekDays($calDateTime);
		$this->tpl->assign('weekdays', $weekDays);
		$this->tpl->assign('weekdayfonts', CalendarDataUtil::getWeekDayFontCss());
		
		$publicTeams = TeamEx::getPublicTeams();
		if(!array_key_exists($target_team_id, $publicTeams)) {
			$publicTeams[$target_team_id] = $target_team_name;
		}
		$this->tpl->assign('team_options', get_select_options_with_id($publicTeams, $target_team_id));
		$teams = array($target_team_id => array('name' => $target_team_name));
		//Team::getChildTeams($target_team_id, 0, $teams);
		$users = UserEx::getUsersBelongingByTeamIds(array_keys($teams));
		
		$activitiesEveryUserArray = CalendarDataUtil::getWeekActivitiesEveryUser($calDateTime, $users);
		$this->tpl->assign('activities_every_user_array', $activitiesEveryUserArray);
	}
}
