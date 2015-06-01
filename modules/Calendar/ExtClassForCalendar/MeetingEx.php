<?php

require_once('modules/Meetings/Meeting.php');

class MeetingEx extends Meeting {
	
	function MeetingEx() {
		parent::Meeting();
	}
	
	static function getParticipantsById($id) {
		$user_array = array();
		
		$sql = "SELECT u.id AS id, u.first_name AS first_name, u.last_name AS last_name "
			. "FROM users u "
			. "INNER JOIN meetings_users mu ON(mu.user_id=u.id) "
			. "WHERE u.deleted=0 "
			. "AND mu.deleted=0 "
		 	. "AND mu.meeting_id='{$id}' ";
		 	
		$db = & PearDatabase::getInstance();
		$result = $db->query($sql);

		global $locale;
		while($row = $db->fetchByAssoc($result)) {
			$user_array[$row['id']] = array(
				'name' => $locale->getLocaleFormattedName($row['first_name'], $row['last_name'])
			);
		}

		return 	$user_array;
	}

	static function getContactsById($id) {
		$user_array = array();
		
		$sql = "SELECT u.id AS id, u.first_name AS first_name, u.last_name AS last_name "
			. "FROM contacts u "
			. "INNER JOIN meetings_contacts mu ON(mu.contact_id=u.id) "
			. "WHERE u.deleted=0 "
			. "AND mu.deleted=0 "
		 	. "AND mu.meeting_id='{$id}' ";
		 	
		$db = & PearDatabase::getInstance();
		$result = $db->query($sql, true);

		global $locale;
		while($row = $db->fetchByAssoc($result)) {
			$user_array[$row['id']] = array(
				'name' => $locale->getLocaleFormattedName($row['first_name'], $row['last_name'])
			);
		}

		return 	$user_array;
	}

	static function getResourcesById($id) {
		$resources = array();
		
		$sql = "SELECT r.id AS id, r.name name "
			. "FROM resources r "
			. "INNER JOIN meetings_resources mr ON(mr.resource_id=r.id) "
			. "WHERE r.deleted=0 "
			. "AND mr.deleted=0 "
		 	. "AND mr.meeting_id='{$id}' ";
		 	
		$db = & PearDatabase::getInstance();
		$result = $db->query($sql);

		while($row = $db->fetchByAssoc($result)) {
			$resources[$row['id']] = array(
				'name' => $row['name']
			);
		}

		return 	$resources;
	}
}
?>