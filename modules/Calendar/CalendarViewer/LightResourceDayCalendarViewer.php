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

class LightResourceDayCalendarViewer extends CalendarViewer {
	var $viewType = "resource_day";
	var $templatePath = "modules/Calendar/CalendarViewer/LightResourceDayCalendarView.tpl";
	var $calendarCtrl = "ResourceDayCalendarCtrl";
	
	var $exceptMeetingIds = array();
	var $targetMeetingDate = "";
	var $targetMeetingStartTime = "";
	var $targetMeetingDurHour = "";
	var $targetMeetingDurMin = "";
	
	function LightResourceDayCalendarViewer(&$params) {
		parent::CalendarViewer($params);
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
		if(isset($this->params['target_date'])) {
			$targetDate = $this->params['target_date'];
		} 
		
		$calDateTime = new CalendarDateTime($targetDate, true, CAL_BASE_LOCAL, true);
		$this->tpl->assign('cal_dt', $calDateTime->toArrayLocalDateTime());
		$this->tpl->assign('weekdayfonts', CalendarDataUtil::getWeekDayFontCss());
		
		$this->tpl->assign('target_date', $calDateTime->localDate);
		
		$target_res_type = "";
		if(isset($this->params['target_res_type'])) {
			$target_res_type = $this->params['target_res_type'];
		}

		$capacity = "";
		if(isset($this->params['capacity']) && !empty($this->params['capacity'])) {
			$capacity = $this->params['capacity'];
		}

		$res_strings = return_module_language($current_language, "Resources");
		$this->tpl->assign("RES_MOD", $res_strings);
		$this->tpl->assign("RES_TYPE_OPTIONS", get_select_options_with_id(array('' => '') + $app_list_strings['resource_type_dom'], $target_res_type));

		if ($sugar_flavor == "PRO") {
			$teamIds = array_keys($_SESSION['TeamsBelongingTo']);
		} else {
			$teamIds = array();
		}
		$resources = array();
		
		$condition = "";
		if(!empty($target_res_type)) {
			$condition = " type='{$target_res_type}' ";
		}
		if(!empty($capacity)) {
			if(!empty($condition)) {
				$condition .= " AND ";
			}
			$condition .= " capacity >= {$capacity} ";
		}
		
		$resources = Resource::getResourcesByConditionSQL($condition, $teamIds);
		
		//TODO make this codes dynamic
		$displayStartDateTime = $calDateTime->localDate . " 07:00:00";
		$displayEndDateTime = $calDateTime->localDate . " 22:00:00";
		
		$isPreDisplay = false;
		$isAfterDisplay = false;

		$startDateTime = "{$this->targetMeetingDate} {$this->targetMeetingStartTime}";
		$targetMeetingDT = new CalendarDateTime($startDateTime, true);
		$durationMin = $this->targetMeetingDurHour * 60 + $this->targetMeetingDurMin;				
		$targetMeetingEndDateTime = date("Y-m-d H:i:s", strtotime("+{$durationMin} minute", strtotime($targetMeetingDT->localDateTime)));
		$targetMeetingEndDateTime_t = strtotime($targetMeetingEndDateTime);
		
		$activityEveryResources = array();
		foreach($resources as $resourceId => $resource) {
			$maxLevel = 0;

			$activityEveryResources[$resourceId] = array(
				'full_name' => $resource,
				'activities' => array(),
				'activities_of_level' => array()
			);
			
			global $sugar_flavor;
			if ($sugar_flavor == "PRO") {
				$activities = CalendarDataUtil::getActivitiesOnDay($calDateTime, 'resource', $resourceId, true,
															CAL_DAY_ACTIVITY_WIDTH, 0, CAL_DUP_OFFSET, true);
			} else {
				$activities = CalendarDataUtil::getActivitiesOnDay($calDateTime, 'resource', $resourceId, false,
															CAL_DAY_ACTIVITY_WIDTH, 0, CAL_DUP_OFFSET, true);
			}
			$isAvailable = true;
			foreach($activities as $activityId => $activity) {
				if($this->params['is_available_only'] == true) {
					$actStartDateTime_t = strtotime($activity['startDateTime']);
					$actEndDateTime_t = strtotime($activity['endDateTime']);
					
					if(($targetMeetingDT->localDateTime_t <= $actStartDateTime_t && $targetMeetingEndDateTime_t >= $actStartDateTime_t)
					|| ($targetMeetingDT->localDateTime_t <= $actEndDateTime_t && $targetMeetingEndDateTime_t >= $actEndDateTime_t)
					|| ($targetMeetingDT->localDateTime_t > $actStartDateTime_t && $targetMeetingEndDateTime_t < $actEndDateTime_t)) {
						$isAvailable = false;
						break;
					}
				}
				
				$level = $activity['duplicateLevel'];
				if($maxLevel < $level) {
					$maxLevel = $level;
				}
				if($level > 0) {
					$activityEveryResources[$resourceId]['activities_of_level'][$level][$activityId] = $activity;
				} else {
					$activityEveryResources[$resourceId]['activities'][$activityId] = $activity;
				}
				
				if($activity['startDateTime'] < $displayStartDateTime) {
					$isPreDisplay = true;
				}
				if($activity['endDateTime'] > $displayEndDateTime) {
					$isAfterDisplay = true;
				}
			}
			$activityEveryResources[$resourceId]['max_level'] = $maxLevel;
			
			if(!$isAvailable) {
				unset($activityEveryResources[$resourceId]);
			}
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
		
		//$GLOBALS['log']->fatal(var_export($activityEveryResources, true));

		$this->tpl->assign('activities_every_resource_array', $activityEveryResources);
		
		$outerDivStyle = "";
		if(count($activityEveryResources) > 8) {
			$outerDivStyle = "height:200px;overflow:auto";
		}
		$this->tpl->assign('OUTER_DIV_STYLE', $outerDivStyle);
		
	}
}
