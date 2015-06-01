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
require_once("modules/Resources/Resource.php");

class ResourceWeekCalendarViewer extends CalendarViewer {
	var $viewType = "resource_week";
	var $templatePath = "modules/Calendar/ResourceWeekCalendarView.tpl";
	var $calendarCtrl = "ResourceWeekCalendarCtrl";
	
	function ResourceWeekCalendarViewer(&$params) {
		parent::CalendarViewer($params);
	}

	function assignFields() {
		global $app_list_strings;
		global $current_user, $current_language;

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
		if(isset($this->params['target_date'])) {
			$targetDate = $this->params['target_date'];
		} 
		
		$calDateTime = new CalendarDateTime($targetDate, true, CAL_BASE_LOCAL, true);
		$this->tpl->assign('target_date', $calDateTime->localDate);
		
		$this->assignMode();
        $targetId = $current_user->id;
        $this->assignModeData($targetId);
        $this->assignAddEntryInputs();

		$this->tpl->assign('formated_target_date', $calDateTime->formattedWeekRange);
		$this->tpl->assign('next_date', $calDateTime->localNextWeekDate);
		$this->tpl->assign('prev_date', $calDateTime->localPrevWeekDate);
		$this->tpl->assign('to_day', CalendarDataUtil::getLocalToDay());
		$this->tpl->assign('local_current_hour', CalendarDataUtil::getLocalCurrentHour());
		
		$weekDays = CalendarDataUtil::getWeekDays($calDateTime);
		if ($this->forDashlet) {
			unset($weekDays[0]);
			unset($weekDays[6]);
		}
		$this->tpl->assign('weekdays', $weekDays);
		$this->tpl->assign('weekdayfonts', CalendarDataUtil::getWeekDayFontCss());
		
		$target_res_type = "";
		if(isset($this->params['target_res_type'])) {
			$target_res_type = $this->params['target_res_type'];
		} else {
			$target_res_type = "";
		}
		$this->tpl->assign("target_res_type", $target_res_type);
		

		$res_strings = return_module_language($current_language, "Resources");
		$this->tpl->assign("RES_MOD", $res_strings);
		$this->tpl->assign("RES_TYPE_OPTIONS", get_select_options_with_id(array('' => '') + $app_list_strings['resource_type_dom'], $target_res_type));
		
		if ($sugar_flavor == "OS") {
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
		$this->tpl->assign('resource_ids', implode(',', array_keys($resources)));
		
		
		$activitiesEveryResourceArray = CalendarDataUtil::getWeekActivitiesEveryResource($calDateTime, $resources);
		if ($this->forDashlet) {
			foreach ($activitiesEveryResourceArray as $res_id => $acts) {
				unset($activitiesEveryResourceArray[$res_id]['activities_of_day_array'][0]);
				unset($activitiesEveryResourceArray[$res_id]['activities_of_day_array'][6]);
			}
		}


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
		$this->tpl->assign('user_ids', implode(',', array_keys($users)));

		$activitiesEveryUserArray = CalendarDataUtil::getWeekActivitiesEveryUser($calDateTime, $users, false, true, false);
		if ($this->forDashlet) {
			foreach ($activitiesEveryUserArray as $user_id => $acts) {
				unset($activitiesEveryUserArray[$user_id]['activities_of_day_array'][0]);
				unset($activitiesEveryUserArray[$user_id]['activities_of_day_array'][6]);
			}
		}
		
		
		$this->tpl->assign('activities_every_resource_array', $activitiesEveryResourceArray);
		$this->tpl->assign('activities_every_user_array', $activitiesEveryUserArray);
	}
}
