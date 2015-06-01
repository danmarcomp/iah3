<?php
require_once("modules/Calendar/CalendarDateTime.php");
require_once("modules/Resources/Resource.php");

class GridEntry {

    const GRID_USERS = "grid_users";

    const GRID_RESOURCES = "grid_resources";

	/**
	 * Constructor
	 * 
	 */
	public function __construct() {}

    /**
     * Get Users list
     *
     * @param  string $selectedUser - user selected on the another view
     * @return array
     */
    public function getUsersList($selectedUser = null) {
        $list = $this->getList('user');
        if(! count($list)) {
            if($selectedUser) {
                $list = $this->addUser($selectedUser);
            } else {
                $list = $this->addUser($GLOBALS['current_user']->id);
            }
        }
        return $list;
    }

    /**
     * Get Participants list for Meetings / Calls editor
     *
     * @return array
     */
    public function getParticipantsList() {
        $list = $this->getList("user");
        $participantsList = array();
        
        if (count($list) > 0) {
            foreach ($list as $id => $name) {
                $participantsList[$id] = array("name" => $name);
            }
        }

        return $participantsList;
    }

    /**
     * Get Resource list
     *
     * @param  string $selectedResource - resource selected on the another view
     * @return array
     */
    public function getResourcesList($selectedResource = null) {
    	$list = $this->getList("resource");
    	if(! count($list) && $selectedResource)
			$list = $this->addResource($selectedResource);
        return $list;
    }

    /**
     * Add grid entry
     *
     * @param  string $id
     * @param  string $type: "user" or "resource"
     * @return void
     */
    public function addEntry($id, $type) {
        if ($id != null) {
            if ($type == "resource") {
                return $this->addResource($id);
            } else {
                return $this->addUser($id);
            }
        }   
    }
	
	public function setEntry($id, $type) {
        if ($id != null) {
            if ($type == "resource") {
                return $this->setResource($id);
            } else {
                return $this->setUser($id);
            }
        }   
    }

    /**
     * Delete grid entry
     *
     * @param  string $id
     * @param  string $type: "user" or "resource"
     * @return void
     */
    public function deleteEntry($id, $type) {
        if ($id != null) {
            if ($type == "resource") {
                return $this->deleteResource($id);
            } else {
                return $this->deleteUser($id);
            }
        }
    }

    /**
     * Add user to grid list
     *
     * @param  string $id
     * @return void
     */
    private function addUser($id) {
        $list = $this->getList("user");
        $ids = array_map('trim', explode(',', $id));
        foreach($ids as $id) {
            if($id && ! isset($list[$id])) {
                $name = get_user_full_name($id);
                if(strlen($name)) {
                    $list[$id] = $name;
                    $this->setList('user', $list);
                }
            }
        }
        return $list;
    }
	
	private function setUser($id) {
        $this->setList('user', null);
        return $this->addUser($id);
    }

    /**
     * Add resource to grid list
     *
     * @param  string$id
     * @return void
     */
    private function addResource($id) {
        $list = $this->getList("resource");
        $ids = array_map('trim', explode(',', $id));
        foreach($ids as $id) {
            if($id && ! isset($list[$id])) {
                $name = Resource::getNameById($id);
                $list[$id] = $name;
                $this->setList('resource', $list);
            }
        }
        return $list;
    }

	
	private function setResource($id) {
        $this->setList('resource', null);
        return $this->addResource($id);
    }
    /**
     * Delete user from grid list
     *
     * @param  string $id
     * @return void
     */
    private function deleteUser($id) {
        $list = $this->getList("user");
        if($id && isset($list[$id])) {
            unset($list[$id]);
            $this->setList('user', $list);
        }
        return $list;
    }

    /**
     * Delete resource from grid list
     *
     * @param  string $id
     * @return void
     */
    private function deleteResource($id) {
        $list = $this->getList("resource");
        if($id && isset($list[$id])) {
            unset($list[$id]);
            $this->setList('resource', $list);
        }
        return $list;
    }

    /**
     * Get entries list
     *
     * @param  string $type: "user" or "resource"
     * @return array
     */
    private function getList($type) {
    	global $current_user;
        $index = GridEntry::GRID_USERS;
        if ($type == "resource") {
            $index = GridEntry::GRID_RESOURCES;
        }

		$list = null;
        if (isset($_SESSION['calendar'][$index]))
            $list = $_SESSION['calendar'][$index];
		if (! isset($list) && AppConfig::setting('site.features.persist_calendar_grid')) {
			if(isset($current_user))
				$list = $current_user->getPreference('calendar_' . $index);
		}
		if (! isset($list))
			$list = array();
			
        return $list;
    }
    
    /**
     * Update entries list
     *
     * @param  string $type: "user" or "resource"
     * @return array
     */
    private function setList($type, $list) {
    	global $current_user;
        $index = GridEntry::GRID_USERS;
        if ($type == "resource") {
            $index = GridEntry::GRID_RESOURCES;
        }
        if(! $list) $list = array();
        $_SESSION['calendar'][$index] = $list;
		if(isset($current_user) && AppConfig::setting('site.features.persist_calendar_grid'))
			$current_user->setPreference('calendar_' . $index, $list);
        return $list;
    }

}
?>
