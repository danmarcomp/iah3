<?php

require_once('modules/Calls/Call.php');

class CallEx extends Call {
	
	function CallEx() {
		parent::Call();
	}
	
	static function getParticipantsById($id) {
		$user_array = array();
		
		$sql = "SELECT u.id AS id, u.first_name AS first_name, u.last_name AS last_name "
			. "FROM users u "
			. "INNER JOIN calls_users mu ON(mu.user_id=u.id) "
			. "WHERE u.deleted=0 "
			. "AND mu.deleted=0 "
			. "AND mu.call_id='{$id}' ";
		 	
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
			. "INNER JOIN calls_contacts mu ON(mu.contact_id=u.id) "
			. "WHERE u.deleted=0 "
			. "AND mu.deleted=0 "
		 	. "AND mu.call_id='{$id}' ";
		 	
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
		return array();	
	}
	
}

?>
