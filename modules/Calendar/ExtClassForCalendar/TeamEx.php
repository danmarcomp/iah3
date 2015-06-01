<?php

require_once('modules/Teams/Team.php');

class TeamEx extends Team {
	
	function TeamEx() {
		parent::Team();
	}
	
	function getNameById($id) {
	    $db = & PearDatabase::getInstance();
	    $sql = "SELECT name FROM teams WHERE id = '{$id}'";
	    
		$resultSet = $db->query($sql, true, "Error getNameById: ");
		if($resultSet === false) {
			return false;
		}
		if($row = $db->fetchByAssoc($resultSet)) {
			return $row['name'];
		} else {
			return false;
		}
	}
	
	function getPublicTeams() {
	    $db = & PearDatabase::getInstance();
	    
	    $sql = 'SELECT t1.id, t1.name FROM teams t1 WHERE t1.deleted = 0 AND t1.private = 0 ORDER BY t1.name ASC';
	    
		$resultSet = $db->query($sql, true, "Error getPublicTeams: ");
		if($resultSet === false) {
			return false;
		}
		
		$teams = array();
		while($row = $db->fetchByAssoc($resultSet)) {
			$teams[$row['id']] = $row['name'];
		}
		return $teams;
	}
	
	function getTeamsBelongingTo($userId) {
		$sql = "SELECT t.id AS id, t.name AS name, t.private private "
			. " FROM teams t "
			. " INNER JOIN team_memberships tm ON(t.id = tm.team_id) "
			. " WHERE t.deleted = 0 "
			. " AND tm.deleted = 0 "
			. " AND tm.user_id = '{$userId}' "
			. " ORDER BY t.private, t.name ASC ";
		
		$db = & PearDatabase::getInstance();
		$resultSet = $db->query($sql, true, "Error getTeamsBelongingTo: ");
		if($resultSet === false) {
			return false;
		}
		
		$teams = array();
		while($row = $db->fetchByAssoc($resultSet)){
			$teams[$row['id']] = array(
				'name' => $row['name'],
				'private' => $row['private'],
				'level' => 0
			);
			
			//Team::getParentTeams($row['id'], 1, $teams);
		}
		
		return $teams;
	}
	
}

?>