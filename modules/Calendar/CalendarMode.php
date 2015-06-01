<?php
require_once("modules/Calendar/CalendarDateTime.php");
require_once("modules/Resources/Resource.php");
require_once("modules/Timesheets/Timesheet.php");

class CalendarMode {
	
	const DEFAULT_MODE = "activities";
	
	/**
	 * Current calendar mode
	 * 
	 * @var string
	 */
	var $mode;
	
	/**
	 * Constructor
	 * 
	 * @param string $mode:
	 * 'activities', 'resources',
	 * 'projects', 'timesheets'
	 */
	public function __construct($mode) {
		if ( $mode == null || $mode == "" ) {
			if (!empty($_SESSION['calendar']['view_mode'])) {
				$mode = $_SESSION['calendar']['view_mode'];
			} else {
				$mode = CalendarMode::DEFAULT_MODE;
			}	
		}
		
		$this->mode = $mode;
	}
	
	/**
	 * Get target ID for current mode
	 * 
	 * @param string $userId
	 * @param string $resourceId
	 * @return string
	 */
	public function getTargetId($userId = null, $resourceId = null) {
		$targetId = "";
		
		if ($this->mode == 'resources') {
			if ($resourceId != null) {
				$targetId = $resourceId;
			} elseif(isset($_SESSION['calendar']['resource_id'])) {
				$targetId = $_SESSION['calendar']['resource_id'];		
			}
		} else {
			if (isset($userId)) {
				$targetId = $userId;
			} elseif (isset($_SESSION['calendar']['user_id'])) {
				$targetId = $_SESSION['calendar']['user_id']; 	
			}
			if($this->mode == 'timesheets') {
				$team = $GLOBALS['current_user']->getTeam(false);
				if(! in_array($targetId, $team))
					$targetId = '';
			}
		}
		return $targetId;
	}
	
	/**
	 * Get targets list
	 * 
	 * @param string $targetId
	 * @param string $userType: 'all' or 'user' 
	 * @param string $isMonth - is for month view or not
	 * @return array
	 */
	public function getTargetsList($targetId, $userType = 'user', $isMonth = false) {
		$targets = array();
		if (!$isMonth) {
			if ($this->mode == 'resources') {
				$targets = $targetId;			
			} elseif ($userType == 'all') {
				$u = new User();
				$users = $u->get_list();
				foreach ($users['list'] as $user) {
					$targets[] = $user->id;
				}
				if (empty($targets)) $targets = null;
			} else {
				$targets = array($targetId);
			}
		} else {
			if ($this->mode == 'resources') {
				$targets = array($targetId => Resource::getNameById($targetId));				
			} elseif ($userType == 'all') {
				$u = new User();
				$users = $u->get_list();
				foreach ($users['list'] as $user) {
					$targets[$user->id] = $user->name;
				}
				if (empty($targets)) $targets = null;
			} else {
				$users = get_user_array(false, array('Active', 'Info Only'), "", true);
                $targets = array();
				if(isset($users[$targetId]))
					$targets[$targetId] = $users[$targetId];
			}
		}	

		return $targets;
	}	

    /**
     * Get selected Timesheet ID
     *
     * @param  string $timesheetId
     * @param  string $userId
     * @param  string $targetDate
     * @return string
     */
    public function getSelectedTimesheet($timesheetId, $userId, $targetDate) {
        global $current_user;

        if ($this->mode == 'timesheets') {
            $timesheet = new Timesheet();
            $id = null;

            if (($timesheetId != null && $timesheetId != '') && $timesheet->retrieve($timesheetId)) {
                $id = $timesheetId;
            } elseif (!empty($_SESSION['calendar']['timesheet_id']) && $timesheet->retrieve($_SESSION['calendar']['timesheet_id'])) {
                $targetId = $this->getTargetId($userId);
                if ($timesheet->assigned_user_id == $targetId) {
                    $id = $_SESSION['calendar']['timesheet_id'];
                }
            }

            if ($id == null) {
                if ($userId == null) $userId = $current_user->id;
                $calDateTime = new CalendarDateTime($targetDate, true, CAL_BASE_LOCAL, true);
                $id = $timesheet->getDefaultForCurrentCalendarPeriod($userId, $calDateTime->gmtNextDate);
            }

            return $id;
        } else {
            return null;
        }
    }

    /**
     * Get timesheets list for current calendar period
     *
     * @param CalendarDateTime $calDateTime
     * @param array $userIds
     * @return array
     */
    public static function getTimesheets($calDateTime, $userIds) {
        $timesheet = new Timesheet();
        $list = $timesheet->getListForCurrentCalendarPeriod($userIds, $calDateTime->gmtPrevDate, $calDateTime->gmtNextDate);

        return $list;
    }

    /**
     * Load current timesheet object
     *
     * @static
     * @param  sstring $id
     * @return Timesheet
     */
    public static function getCurrentTimesheet($id) {
        $timesheet = new Timesheet();

        if ($timesheet->retrieve($id)) {
            return $timesheet;
        } else {
            return null;
        }
    }

    /**
     * Get a list of display calendar entries
     *
     * @param  array $params
     * @return array
     */
	public function getDisplayEntries($params = null) {
		$displayEntries = array();		
		$filters = $this->getFilters();
		$previousMode = $this->getPreviousMode();
		$currentMode = $this->mode;
		
		for ($i = 0; $i < sizeof($filters); $i++) {
			$displayEntries[$filters[$i]] = 0;
		}
		
		if ($currentMode == "resources") {
			$displayEntries['display_meetings'] = 1;
		} elseif ($currentMode == "projects") {
			$displayEntries['display_project_tasks'] = 1;
		} elseif ($currentMode == "timesheets") {
			$displayEntries['display_booked_hours'] = 1;						
		} else {

			foreach ($displayEntries as $field => $value) {
				if ( ($previousMode == $currentMode) && isset($params[$field]) ) {
					$displayEntries[$field] = $params[$field];
					$_SESSION['calendar'][$field] = $params[$field];
				} elseif (isset($_SESSION['calendar'][$field])) {
					$displayEntries[$field] = $_SESSION['calendar'][$field]; 					
				} elseif ( $field == "display_leave" || $field == "display_meetings"
                    || $field == "display_calls" || $field == "display_tasks" ) {

                    $displayEntries[$field] = 1;
				}
			}			
		}
		
		$this->setPreviousMode($currentMode);

		return $displayEntries;
	}
	
	/**
	 * Get calendar filters 
	 * 
	 * @return array
	 */
	public static function getFilters() {
		$filters = array (
			"display_meetings",
			"display_calls",
			"display_tasks",
			"display_project_tasks",
			"display_events",
			"display_leave",
			"display_booked_hours"				
		);
		
		return $filters;
	}

	/**
	 * Get previous calendar mode
	 * 
	 * @return string
	 */
	private function getPreviousMode() {
		$mode = null;
		if (isset($_SESSION['calendar']['previous_mode'])) $mode = $_SESSION['calendar']['previous_mode']; 
		
		return $mode;
	}
	
	/**
	 * Set previous calendar mode
	 * 
	 * @param string $mode
	 */
	private function setPreviousMode($mode) {
		$_SESSION['calendar']['previous_mode'] = $mode;  
	}	
}
?>