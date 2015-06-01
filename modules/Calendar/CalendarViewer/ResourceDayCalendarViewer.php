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
	require_once("modules/Teams/Team.php");
}
require_once("modules/Users/User.php");
require_once("modules/Resources/Resource.php");

class ResourceDayCalendarViewer extends CalendarViewer {
	var $viewType = "resource_day";
	var $templatePath = "modules/Calendar/CalendarViewer/ResourceDayCalendarView.tpl";
	var $calendarCtrl = "ResourceDayCalendarCtrl";
	
	function ResourceDayCalendarViewer(&$params) {
		parent::CalendarViewer($params);
	}

	function assignFields() {
		global $current_user, $current_language;
		global $app_list_strings;

		global $sugar_flavor;

		parent::assignFields();

        if ($sugar_flavor == "PRO") {
			$target_team_id = "";
			if(isset($this->params['target_team_id']) && !empty($this->params['target_team_id'])) {
				$target_team_id = $this->params['target_team_id'];
			} else {
				$target_team_id = $current_user->default_team;
			}
			$target_team_name = get_assigned_team_name($target_team_id);
			$this->tpl->assign('target_team_id', $target_team_id);
			$this->tpl->assign('target_team_name', $target_team_name);
		}
	
		$targetDate = "";
		if (isset($this->params['target_date'])) {
			$targetDate = $this->params['target_date'];
		}
				
		$calDateTime = new CalendarDateTime($targetDate, true, CAL_BASE_LOCAL, true);
		$this->tpl->assign('cal_dt', $calDateTime->toArrayLocalDateTime());
		$this->tpl->assign('weekdayfonts', CalendarDataUtil::getWeekDayFontCss());
		
		$this->tpl->assign('target_date', $calDateTime->localDate);
		
		$this->assignMode();
		$targetId = $current_user->id;
		if (isset($this->params['target_id'])) {
			$targetId = $this->params['target_id'];
		}
        $this->assignModeData($targetId);
        $this->assignAddEntryInputs();

		$target_res_type = "";
		if(isset($this->params['target_res_type'])) {
			$target_res_type = $this->params['target_res_type'];
		}

		$this->tpl->assign("target_res_type", $target_res_type);

		$res_strings = return_module_language($current_language, "Resources");
		$this->tpl->assign("RES_MOD", $res_strings);
		$this->tpl->assign("RES_TYPE_OPTIONS", get_select_options_with_id(array('' => '') + $app_list_strings['resource_type_dom'], $target_res_type));

		if ($sugar_flavor == "PRO") {
			$teamIds = array_keys($_SESSION['TeamsBelongingTo']);
		} else {
			$teamIds = array();
		}

        $gridEntry = new GridEntry();

        if (isset($this->params['resource_id']) && $this->params['resource_id'] != null) {
            $gridEntry->addEntry($this->params['resource_id'], "resource");
            unset($this->params['resource_id']);
            unset($_REQUEST['resource_id']);
        }
        if (isset($this->params['grid_res_id']) && $this->params['grid_res_id'] != null) {
            $gridEntry->addEntry($this->params['grid_res_id'], "resource");
            unset($this->params['grid_res_id']);
            unset($_REQUEST['grid_res_id']);
        }
        if (isset($this->params['grid_res_id_del']) && $this->params['grid_res_id_del'] != null) {
            $gridEntry->deleteEntry($this->params['grid_res_id_del'], "resource");
            unset($this->params['grid_res_id_del']);
            unset($_REQUEST['grid_res_id_del']);
        }

		$resources = array();
        $selectedResource = null;
        if(isset($this->params['resource_id'])) {
            $selectedResource = $this->params['resource_id'];
        }
        $resources = $gridEntry->getResourcesList($selectedResource);
		
		global $current_user;
		$start = $current_user->day_begin_hour;
		$end = $current_user->day_end_hour;
		if(empty($start) && empty($end)) {
			$start = 9;
			$end = 18;
		}
		$displayStartDateTime = $calDateTime->localDate . sprintf(" %02d:00:00", $start);
		$displayEndDateTime = $calDateTime->localDate . sprintf(" %02d:00:00", $end);
		
		$isPreDisplay = false;
		$isAfterDisplay = false;
		
		$activityEveryResources = array();
		$activityEveryUser = array();

		foreach($resources as $resourceId => $resource) {
			$maxLevel = 0;

			$activityEveryResources[$resourceId] = array(
				'full_name' => $resource,
				'activities_of_level' => array()
			);
			
			if ($sugar_flavor == "PRO") {
				$activities = CalendarDataUtil::getActivitiesOnDay($calDateTime, 'resource', $resourceId, true);
			} else {
				$activities = CalendarDataUtil::getActivitiesOnDay($calDateTime, 'resource', $resourceId, false,
					CAL_DAY_ACTIVITY_WIDTH, 0, -1, false, array(), false, true);
			}
			
			foreach($activities as $activityId => $activity) {
				if($activityId === 'summary')
					continue;
				$level = $activity['duplicateLevel'];
				if($maxLevel < $level) {
					$maxLevel = $level;
				}
				$activityEveryResources[$resourceId]['activities_of_level'][$level][$activityId] = $activity;
				
				if($activity['startDateTime'] < $displayStartDateTime) {
					$isPreDisplay = true;
				}
				if($activity['endDateTime'] > $displayEndDateTime) {
					$isAfterDisplay = true;
				}
			}
			$activityEveryResources[$resourceId]['max_level'] = $maxLevel;
			if(! count($activityEveryResources[$resourceId]['activities_of_level']))
				$activityEveryResources[$resourceId]['activities_of_level'][] = array();
		}
		$this->tpl->assign('resource_ids', implode(',', array_keys($resources)));
		

        if (isset($this->params['target_user_id']) && $this->params['target_user_id'] != null) {
            $gridEntry->addEntry($this->params['target_user_id'], "user");
            unset($this->params['target_user_id']);
            unset($_REQUEST['target_user_id']);
        }

        if (isset($this->params['grid_user_id']) && $this->params['grid_user_id'] != null) {
            $gridEntry->addEntry($this->params['grid_user_id'], "user");
            unset($this->params['grid_user_id']);
            unset($_REQUEST['grid_user_id']);
        }
        if (isset($this->params['grid_user_id_del']) && $this->params['grid_user_id_del'] != null) {
            $gridEntry->deleteEntry($this->params['grid_user_id_del'], "user");
            unset($this->params['grid_user_id_del']);
            unset($_REQUEST['grid_user_id_del']);
        }

        $selectedUser = null;
        if(isset($this->params['user_id'])) {
            $selectedUser = $this->params['user_id'];
        }
        $users = $gridEntry->getUsersList($selectedUser);

		foreach($users as $userId => $user) {
			$maxLevel = 0;

			$activityEveryUser[$userId] = array(
				'full_name' => $user,
				'activities_of_level' => array()
			);
			
			$activities = CalendarDataUtil::getActivitiesOnDay($calDateTime, 'user', array($userId), true,
				CAL_DAY_ACTIVITY_WIDTH, 0, -1, false, array(), false, true);
			
			
			foreach($activities as $activityId => $activity) {
				if($activityId === 'summary')
					continue;
				$level = $activity['duplicateLevel'];
				if($maxLevel < $level) {
					$maxLevel = $level;
				}
				$activityEveryUser[$userId]['activities_of_level'][$level][$activityId] = $activity;
				
				if($activity['startDateTime'] < $displayStartDateTime) {
					$isPreDisplay = true;
				}
				if($activity['endDateTime'] > $displayEndDateTime) {
					$isAfterDisplay = true;
				}
			}
			$activityEveryUser[$userId]['max_level'] = $maxLevel;
			if(! count($activityEveryUser[$userId]['activities_of_level']))
				$activityEveryUser[$userId]['activities_of_level'][] = array();
		}
		$this->tpl->assign('user_ids', implode(',', array_keys($users)));
		
		
		$hours = CalendarDataUtil::getWorkHourArray('user', $isPreDisplay, $isAfterDisplay);
		$this->tpl->assign('hours', $hours);

		if($isPreDisplay) {
			$this->tpl->assign('start_offset_min', 0);
		} else {
			$this->tpl->assign('start_offset_min', $start*60);
		}
		
		if($isAfterDisplay) {
			$this->tpl->assign('end_offset_min', 24*60);	
		} else {
			$this->tpl->assign('end_offset_min', $end * 60);
		}
		
		$this->tpl->assign('activities_every_resource_array', $activityEveryResources);
		$this->tpl->assign('activities_every_user_array', $activityEveryUser);
	}
}
