<?php

require_once('modules/Users/User.php');

class UserEx extends User {
	
	function UserEx() {
		parent::User();
	}
	
	static function getUserFullNameById($id) {
		$sql = "SELECT first_name, last_name FROM users WHERE id='{$id}'";
		
		$db = & PearDatabase::getInstance();
		$resultSet = $db->query($sql, true);
		if($resultSet === false) {
			return false;
		}
		$row = $db->fetchByAssoc($resultSet);
		if($row === false) {
			return false;
		}
		
		global $locale;
		return $locale->getLocaleFormattedName($row['first_name'], $row['last_name']);
	}
	
	//CBI... LoginHistory
	function isAdmin(){
		if($this->is_admin == '1'){
			return true;
		}else{
			return false;
		}
	}
	//...CBI
	
	//CBI... Calendar
	static function getUsersBelongingByTeamIds($ids) {
		$quotedIds = array();
		foreach($ids as $id) {
			$quotedIds[] = "'{$id}'";	
		}
		
	    $db = & PearDatabase::getInstance();
	    $query = "SELECT u.id AS id, u.last_name AS last_name, u.first_name AS first_name FROM users u "
	    		. "INNER JOIN team_memberships tm ON(u.id=tm.user_id AND tm.deleted=0) "
	    		. "WHERE u.deleted=0 "
	    		. "AND u.is_group=0 "
	    		. "AND u.sugar_login=1 "
	    		. "AND u.status='Active' "
	    		. "AND tm.team_id IN(" . implode(",", $quotedIds) .") ";
	    
		$result = $db->query($query, true, "Error filling in team array: ");
		if($result === false) {
			return false;
		}

		$user_array = array();
		global $locale;
		while($row = $db->fetchByAssoc($result)) {
			$user_array[$row['id']] = $locale->getLocaleFormattedName($row['first_name'], $row['last_name']);
		}

		return 	$user_array;
	}
	
	static function getUsersByIds($userIds) {
		$result = array();
		
		$quouteUserIds = array();
		foreach ($userIds as $userId) {
			$userId = trim($userId);
			if(!empty($userId)) {
				$quouteUserIds[] = "'{$userId}'";
			}
		}
		$strUserIds = implode(",", $quouteUserIds);
		if (empty($strUserIds)) {
			return $result;
		}
		
		$sql = "SELECT DISTINCT u.id AS id, "
			. " u.first_name AS first_name, "
			. " u.last_name AS last_name, "
			. " u.title AS title, "
			. " u.department AS department "
			. "FROM users u "
			. "WHERE u.deleted = 0 "
			. "AND u.status = 'Active' "
			. "AND u.id IN({$strUserIds})";

		$db = & PearDatabase::getInstance();
		$resultSet = $db->query($sql, true);
		if($resultSet === false) {
			return false;
		}

		global $locale;
		while($row = $db->fetchByAssoc($resultSet)) {
			$name = $locale->getLocaleFormattedName($row['first_name'], $row['last_name']);
			$result[$row['id']] = $name;
		}
		return $result;
	}
	
	function searchForCalendar($teamId, $lastName, $firstName) {
		$result = array();
		
		$sql = "SELECT DISTINCT u.id AS id, "
			. " u.first_name AS first_name, "
			. " u.last_name AS last_name, "
			. " u.title AS title, "
			. " u.department AS department "
			. "FROM users u "
			. "INNER JOIN team_memberships tm ON(tm.user_id = u.id) "
			. "WHERE u.deleted = 0 "
			. "AND tm.deleted = 0 "
			. "AND u.status = 'Active' ";

		if(!empty($lastName)) {
			$sql .= " AND u.last_name LIKE '{$lastName}%' ";				
		}
		if(!empty($firstName)) {
			$sql .= " AND u.first_name LIKE '{$firstName}%' ";				
		}
		if(!empty($teamId)) {
			$sql .= " AND tm.team_id = '{$teamId}' ";				
		}

		$db = & PearDatabase::getInstance();
		$resultSet = $db->query($sql, true);
		if($resultSet === false) {
			return false;
		}

		global $locale;
		while($row = $db->fetchByAssoc($resultSet)) {
			$name = $locale->getLocaleFormattedName($row['first_name'], $row['last_name']);
			if(!empty($row['title'])) {
				$name .= " ({$row['title']})";
			}
			if(!empty($row['department'])) {
				$name .= "/{$row['title']}";
			}
			$result[$row['id']] = $name;
		}
		return $result;
	}
	//...CBI
}
?>