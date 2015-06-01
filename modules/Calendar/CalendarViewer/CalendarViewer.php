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
require_once("include/Sugar_Smarty.php");
require_once("modules/Calendar/CalendarTabViewer.php");
require_once("modules/Calendar/CalendarDateTime.php");
require_once("modules/Calendar/CalendarDataUtil.php");
require_once("modules/Calendar/CalendarMode.php");

class CalendarViewer {
	var $viewType = "";
	var $params;
	var $tpl;
	var $templatePath = "";
	var $calendar_strings;
	var $forDashlet = false;
	var $dashletId = '';
	var $async = false;
	var $calendarCtrl = "";
	var $js_activities = array();
	var $start_hour = 0;
	var $end_hour = 0;
	var $debug;
	
	function CalendarViewer(&$params) {
		$this->params = $params;
	}
	
	static function initRequest($forDashlet=false) {
		if(!isset($_REQUEST['view_type'])) {
			if(isset($_SESSION['calendar']['view_type']))
				$_REQUEST['view_type'] = $_SESSION['calendar']['view_type'];
			else
				$_REQUEST['view_type'] = 'day';
		}
		if($_REQUEST['view_type'] == 'resource_day' && $forDashlet)
			$_REQUEST['view_type'] = 'day';
		else if($_REQUEST['view_type'] == 'resource_week' && $forDashlet)
			$_REQUEST['view_type'] = 'week';

			
		if($spec = array_get_default($_REQUEST, 'target_date_special')) {
			if($spec == 'today')
				$_REQUEST['target_date'] = CalendarDataUtil::getLocalToDay();
		}
		
		$mode = null;
		if (isset($_REQUEST['view_mode'])) $mode = $_REQUEST['view_mode']; 
		$userId = null;
		if (isset($_REQUEST['user_id'])) $userId = $_REQUEST['user_id']; 
		$resourceId = null;
		if (isset($_REQUEST['resource_id'])) $resourceId = $_REQUEST['resource_id']; 
		
		if ($_REQUEST['view_type'] == 'resource_day' || $_REQUEST['view_type'] == 'resource_week') {
			$mode = $_REQUEST['view_mode'] =  'activities';
			if(! isset($_REQUEST['user_id'])) $_REQUEST['user_id'] = '';
			if(! isset($_REQUEST['resource_id'])) $_REQUEST['resource_id'] = '';
		}

		
		$calendarMode = new CalendarMode($mode);
		$targetId = $calendarMode->getTargetId($userId, $resourceId);
		$_REQUEST['target_id'] = $targetId;
		
		$timesheetId = null;
		if (isset($_REQUEST['timesheet_id'])) $timesheetId = $_REQUEST['timesheet_id'];
		$targetDate = '';
		if (isset($_REQUEST['target_date'])) {
			$targetDate = $_REQUEST['target_date'];
		} elseif (isset($_SESSION['calendar']['target_date'])) {
			$targetDate = $_SESSION['calendar']['target_date'];    
		}
		
		$_REQUEST['timesheet_id'] = $calendarMode->getSelectedTimesheet($timesheetId, $userId, $targetDate);
		
		$displayEntries = $calendarMode->getDisplayEntries($_REQUEST);
		$filters = $calendarMode->getFilters();
		
		for ($i = 0; $i < sizeof($filters); $i++) {
			$_REQUEST[$filters[$i]] = $displayEntries[$filters[$i]];
		}
		
		$vars = array(
			'view_type',
			'view_mode',
			'user_id', 
			'resource_id',
			'target_date',
			'target_user_id',
			'target_team_id',
			'target_id',
			'target_type',
			'target_user_type',
			'target_res_type',
			'project_type',
			'project_id',
			'timesheet_id'
		);
		
		foreach ($vars as $var) {
			if(!empty($_SESSION['calendar'][$var]) && !isset($_REQUEST[$var])) {
				$_REQUEST[$var] = $_SESSION['calendar'][$var];
			} elseif(isset($_REQUEST[$var])) {
				$_SESSION['calendar'][$var] = $_REQUEST[$var];
			} elseif ($var != 'view_type') {
				$_REQUEST[$var] = '';
			}
		}
	}
	
	function getLayoutHeader() {
		$inc_mgr =& $this->tpl->inc_mgr;
		$inc_mgr->add_js_language('Meetings');
		$inc_mgr->add_js_language('Calendar');
		$inc_mgr->add_include_group('Calendar', null, LOAD_PRIORITY_APP_INIT);
		$head = '';
		if(! $this->async)
			$head .= '<div id="calendar_body"' . ($this->forDashlet ? ' class="dashlet-body"' : '') .'>';
		return $head;
	}
	
	function getLayoutFooter() {
		return '</div>';
	}
	
	function setupTemplate() {
		$this->tpl = new Sugar_Smarty();
	}


	function assignLangs() {
		global $app_strings;
		global $current_language;
		global $app_list_strings;
		$this->calendar_strings = return_module_language($current_language, "Calendar");
		$this->tpl->assign('MONTHS', $app_list_strings['months_long_dom']);
		
		$this->tpl->assign('APP', $app_strings);
		$this->tpl->assign('MOD', $this->calendar_strings);
		
		$mtg_mod_strings = return_module_language($current_language, "Meetings");
		$this->tpl->assign('MTG_MOD', $mtg_mod_strings);
		$this->tpl->assign('APP_LIST', $app_list_strings);
	}
	
	function assignStyles() {
		global $theme;
		
		$this->tpl->assign('THEME', $theme);
	}
	
	function assignJavaScript() {
		$forDashlet = $this->forDashlet ? "'$this->dashletId'" : 0;
		$async = $this->async ? 1 : 0;
		$calCls = "'{$this->calendarCtrl}'";
		$json = getJSONobj();
		$params = array(
			'activities' => $this->js_activities,
			'start_hour' => $this->start_hour,
			'end_hour' => $this->end_hour,
		);
		$params = $json->encode($params);
		$GLOBALS['pageInstance']->add_js_literal("YAHOO.util.Event.onDOMReady(function() {CalendarCtrl.globalInitCalendar($calCls, $async, $forDashlet, $params);});", null, LOAD_PRIORITY_FOOT);
	}
	
	function assignTab() {
		$targetDate = "";
		$targetUserId = "";
		$targetTeamId = "";

		if(isset($this->params['target_date'])) {
			$targetDate = $this->params['target_date'];
		}
		if(isset($this->params['target_user_id'])) {
			$targetUserId = $this->params['target_user_id'];
		}
		if(isset($this->params['target_team_id'])) {
			$targetTeamId = $this->params['target_team_id'];
		}

		$tabViewer = new CalendarTabViewer($this->viewType, $targetDate, $targetUserId, $targetTeamId, $this->forDashlet);
		$this->tpl->assign('calendar_tab', $tabViewer->execute());
	}

	function assignFields() {
		global $current_user;
		$displayFields = array(
			'display_meetings' => 1,
			'display_calls' => 1,
			'display_tasks' => 1,
			'display_project_tasks' => 0,
			'display_events' => 1,
			'display_leave' => 1,
			'display_booked_hours' => 0		
		);
		foreach ($displayFields as $f => $default) {
			if (!isset($this->params[$f]) || $this->params[$f]) {
				if (isset($this->params[$f]) || $default) {
					$this->tpl->assign($f, 'value="1"');
					$this->tpl->assign($f . "_sel", 'checked="checked"');
					$this->tpl->assign($f . "_checked", 'checked');
					CalendarDataUtil::param($f, true);
				}
			}
		}

		$moreCount = 0;
		$more = array(
			'display_project_tasks' => 'LBL_SHOW_PTASKS',
			'display_events' => 'LBL_SHOW_EVENTS',
			'display_leave' => 'LBL_SHOW_LEAVE',
		);
		$moreOpts = array();
		foreach ($more as $f => $lbl) {
			$checked = CalendarDataUtil::param($f);
			if ($checked) $moreCount++;
			$moreOpts['keys'][] = $f;
			$moreOpts['values'][] = array('icon' => 'icon-checkbox' . ($checked ? ' checked' : ''), 'label' => translate($lbl, 'Calendar'));
		}
		if ($moreCount) {
			$this->tpl->assign('MORE_COUNT', '(' . $moreCount . ')');
		} else {
			$this->tpl->assign('MORE_COUNT', '');
		}
		$json = getJSONobj();
		$this->tpl->assign('MORE_OPTIONS', $json->encode($moreOpts));

		if (isset($this->params['selected_targets'])) {
			$this->tpl->assign('selected_targets', $this->params['selected_targets']);
		}
		$this->tpl->assign('USERS',get_user_array(false, 'Active', "", true));
		if (!empty($this->params['selected_targets']))
			$targets = explode(',', $this->params['selected_targets']);
		else
			$targets = array($current_user->id);
		$this->tpl->assign('SELECTED_USERS', $targets);
		if(count($targets) == 1 && $targets[0] != $current_user->id) {
			$target_user = new User();
			$target_user->retrieve($targets[0]);
			$startHour = $target_user->day_begin_hour;
			$endHour = $target_user->day_end_hour;
			$target_user->cleanup();
		} else {
			$startHour = $current_user->day_begin_hour;
			$endHour = $current_user->day_end_hour;
		}
		if (!empty($this->params['target_department'])) {
			$this->tpl->assign('target_department', $this->params['target_department']);
		}
		$print_url = get_printable_link();
		if ($this->viewType == 'day') {
			$targetDate = $this->params['target_date'];
			if (empty($targetDate)) {
				$targetDate = gmdate('Y-m-d');
			}
			$print_url = 'index.php?module=Calendar&action=PDF&display_date=' . $targetDate;
		}
		$this->tpl->assign('PRINT_LINK', $print_url);
		$this->tpl->assign('forDashlet', $this->forDashlet ? 1 : 0);
		$this->tpl->assign('dashletId', $this->dashletId);

		if(empty($startHour) && empty($endHour)) {
			$startHour = 9;
			$endHour = 18;
		}
		$this->tpl->assign('START_HOUR', $startHour);

		$canEditMeetings = ACLController::checkAccess('Meetings', 'edit', true);
		$canEditCalls = ACLController::checkAccess('Calls', 'edit', true);
		$canEdit = $canEditMeetings || $canEditCalls;
		$this->tpl->assign('canEditMeetings', $canEditMeetings);
		$this->tpl->assign('canEditCalls', $canEditCalls);
		$this->tpl->assign('canEdit', $canEdit);
		if ($canEditMeetings && $canEditCalls) {
			$this->tpl->assign('defaultEditModule', '');
		} else if($canEditMeetings) {
			$this->tpl->assign('defaultEditModule', 'Meetings');
		} else {
			$this->tpl->assign('defaultEditModule', 'Calls');
		}

        $canEditHours = ACLController::checkAccess('Booking', 'edit', true);
        $this->tpl->assign('canEditHours', $canEditHours);

		$forDashlet = $this->forDashlet ? 'true' : 'false';
		$s = <<<EOQ
<script type="text/javascript">
var dayStartHour = $startHour;
var forDashlet = $forDashlet;
</script>
EOQ;
		$this->tpl->inc_mgr->add_js_literal($s);
	}
	
	function getMode() {
		$mode = "activities";

		if(isset($this->params['view_mode']) && !empty($this->params['view_mode'])) {
			$mode = $this->params['view_mode'];
		}

		if($this->params['view_type'] == 'resource_day' || $this->params['view_type']  == 'resource_week') {
			$mode = 'activities';
		}
		
		return $mode;
	}
	
	function assignMode() {
		$mode = $this->getMode();
		$modesList = $this->getModesCheckedStatus($mode);
		
		$this->tpl->assign('MODE', $mode);
		$this->tpl->assign('MODE_NAME', $this->getModeName($mode));
		$this->tpl->assign('MODE_ICON', $this->getModeIcon($mode));
		
		$opts = array('keys' => array('activities', 'resources', 'projects', 'timesheets'));
		foreach($opts['keys'] as $m)
			$opts['values'][$m] = array(
				'icon' => $this->getModeIcon($m),
				'label' => translate(strtoupper('LBL_MODE_'.$m), 'Calendar'),
			);
		$json = getJSONobj();
		$this->tpl->assign('MODE_OPTIONS', $json->encode($opts));

		if (isset($this->params['project_id'])) {
			$this->tpl->assign('PROJECT_ID', $this->params['project_id']);
			CalendarDataUtil::param("project_id", $this->params['project_id']);
			CalendarDataUtil::param("project_type", $this->params['project_type']);									
		}

		if (!empty($this->params['timesheet_id'])) {
			CalendarDataUtil::param("timesheet_id", $this->params['timesheet_id']);			
		}
	}
	
	function getModesCheckedStatus($selectedMode) {
		$modes = array(
			'activities' => '',
			'resources' => '',
			'projects' => '',
			'timesheets' => ''		
		);
		
		$modes[$selectedMode] = 'checked';
		
		return $modes;
	}

	function getModeName($mode) {
		$mod = $this->calendar_strings;
		$modeNames = array(
			'activities' => $mod["LBL_MODE_ACTIVITIES"],
			'resources' => $mod["LBL_MODE_RESOURCES"],
			'projects' => $mod["LBL_MODE_PROJECTS"],
			'timesheets' => $mod["LBL_MODE_TIMESHEETS"]		
		);
		
		return $modeNames[$mode];
	}
	
	function getModeIcon($mode) {
		global $image_path;
		$modeIcons = array(
			'activities' => 'Activities',
			'resources' => 'Resources',
			'projects' => 'Project',
			'timesheets' => 'Timesheets',
		);
		return 'theme-icon module-'.$modeIcons[$mode];
	}
	
	function assignModeData($targetId) {
		global $current_user, $image_path;
		$mode = $this->getMode();
		$mod = $this->calendar_strings;				
		
		if($mode == "resources") {
			require_once("modules/Resources/Resource.php");
			$resourceId = null;	
			if (isset($this->params['resource_id'])) $resourceId = $this->params['resource_id'];
			if($resourceId)
				$targetName = Resource::getNameById($resourceId);
			else
				$targetName = $mod['LBL_RESOURCE_ALL'];
			$targetIcon = 'theme-icon module-Resources';
			
			$this->tpl->assign('res_name_selected', $targetName);
			$this->tpl->assign('res_id_selected', $resourceId);
			$this->tpl->assign('target_res_id', $resourceId);
		} else {
			
			if ($targetId == $current_user->id) {
				$targetName = 'Me';
			} else {
				$targetName = UserEx::getUserFullNameById($targetId);					
			}	

			$targetIcon = 'icon-user';

			if ($mode == "projects") {
				require_once("modules/Project/Project.php");				
				$projectId = null;	
				if (isset($this->params['project_id'])) $projectId = $this->params['project_id'];
				$this->tpl->assign('project_id_selected', $projectId);
				$projectName = $mod['LBL_MULTIPLE_PROJECTS'];
				
				if (!empty($this->params['project_type']) && $this->params['project_type'] == 'project') {
					$projectName = Project::getNameById($projectId);
				}										
				$this->tpl->assign('PROJECT_NAME', $projectName);			
				
				$projectIcon = 'theme-icon module-Project';
				$this->tpl->assign('project_icon', $projectIcon);

				$checkedProject = '';
				$checkedProjectAll = 'checked';				

				if (!empty($this->params['project_type'])) {
					$checkedProject = ($this->params['project_type'] == 'project') ? 'checked' : '';
					$checkedProjectAll = ($this->params['project_type'] == 'all') ? 'checked' : '';										
				}
				
				$this->tpl->assign('CHECKED_PROJECT', $checkedProject);
				$this->tpl->assign('CHECKED_PROJECT_ALL', $checkedProjectAll);						

				if (!empty($this->params['target_user_type']) && $this->params['target_user_type'] == 'all') {
					$targetName = $mod['LBL_ALL_USERS'];
					$targetId = '';
					$targetIcon = 'icon-users';
					$this->tpl->assign('target_user_type', 'all');
				}
			}
		}		
		
		$this->tpl->assign('target_icon', $targetIcon);
		$this->tpl->assign('target_name', $targetName);
		$this->tpl->assign('target_id', $targetId);
		$this->tpl->assign('user_name_selected', $targetName);
		$this->tpl->assign('user_id_selected', $targetId);
		$this->tpl->assign('target_user_id', $targetId);
	}

    function assignTimesheets($targets, $calDateTime) {
        global $image_path, $current_user;
        
        $mode = $this->getMode();

        if ($mode == "timesheets") {
            $timesheetIcon = 'theme-icon module-Timesheets';
            $this->tpl->assign('timesheet_icon', $timesheetIcon);

            $timesheets = CalendarMode::getTimesheets($calDateTime, $targets);
            $this->tpl->assign("TIMESHEETS", $timesheets);
            $this->tpl->assign("TIMESHEETS_NUM", count($timesheets));

            if (!empty($this->params['timesheet_id'])) {
                $currentTimesheet = CalendarMode::getCurrentTimesheet($this->params['timesheet_id']);

                if ($currentTimesheet != null) {
                    $this->tpl->assign("TIMESHEET_SELECTED", $currentTimesheet->name);
                    $this->tpl->assign("timesheet_id", $currentTimesheet->id);

                    $status = '';
                    if ($current_user->id == $currentTimesheet->assigned_user_id) {
                        $status = $currentTimesheet->status;
                    } else {
                        $status = "approve_reject";
                    }
                    $this->tpl->assign("TIMESHEET_STATUS", $status);
                }
            }
        }
    }

    function assignAddEntryInputs() {
        $form = new EditableForm('editview', 'settings_form');
        $empty_result = new RowResult();

        $user_spec = array('name' => 'user_name_selected', 'id_name' => 'user_id_selected', 'bean_name' => 'User');
        $this->tpl->assign('add_user', $form->renderRef($empty_result, $user_spec));

        $mode = $this->getMode();

        if ($mode == "resources" || ($mode == "activities" && $this->viewType == 'resource_day' || $this->viewType == 'resource_week')) {
            $res_spec = array('name' => 'res_name_selected', 'id_name' => 'res_id_selected', 'bean_name' => 'Resource');
            $this->tpl->assign('add_resource', $form->renderRef($empty_result, $res_spec));
        }

        $form->exportIncludes();
    }

    function execute() {
		$this->setupTemplate();
		
		$this->assignLangs();
		
		$this->assignStyles();

		$this->assignTab();

		$this->assignFields();

		$this->assignJavaScript();

		return $this->getLayoutHeader()
			. $this->tpl->fetch($this->templatePath)
			. $this->getLayoutFooter();
	}
	
	function addActivity($act) {
		$this->js_activities[] = $act;
	}
	
	function exportIncludes() {
		$this->tpl->export_includes();
	}
	
}
