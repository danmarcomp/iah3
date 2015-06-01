<?php
/*
 *
 * The contents of this file are subject to the info@hand Software License Agreement Version 1.3
 *
 * ("License"); You may not use this file except in compliance with the License.
 * You may obtain a copy of the License at <http://1crm.com/pdf/swlicense.pdf>.
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the
 * specific language governing rights and limitations under the License,
 *
 * All copies of the Covered Code must include on each user interface screen:
 * (i) the 1CRM copyright notice,
 * (ii) the "Powered by the 1CRM Engine" logo, 
 *
 * (iii) the "Powered by SugarCRM" logo, and
 * (iv) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.
 * See full license for requirements.
 *
 * The Original Code is : 1CRM Engine proprietary commercial code.
 * The Initial Developer of this Original Code is 1CRM Corp.
 * and it is Copyright (C) 2004-2012 by 1CRM Corp.
 *
 * All Rights Reserved.
 * Portions created by SugarCRM are Copyright (C) 2004-2008 SugarCRM, Inc.;
 * All Rights Reserved.
 *
 */
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');


require_once('data/SugarBean.php');

// Assets is used to store customer information.
class Resource extends SugarBean {
	
	// Stored fields
  	var $id;
	var $name;
	var $location;
	var $type;
	var $contact_id;
	var $has_tv;
	var $has_dvd;
	var $has_vcr;
	var $has_projector;
	var $has_screen;
	var $has_pc;
	var $has_conf_phone;
	var $assigned_user_id;
	
	var $description;
	
	var $assigned_user_name;

	var $table_name = "resources";
	var $object_name = "Resource";
	var $module_dir = "Resources";
	var $new_schema = true;	
	

	var $column_fields = Array(
		"id",
		"name",
		"location",
		"type",
		"assigned_user_id",
		"has_tv",
		"has_dvd",
		"has_vcr",
		"has_projector",
		"has_screen",
		"has_pc",
		"has_conf_phone",
		"description",
	);
		
	// This is used to retrieve related fields from form posts.
	var $additional_column_fields = Array( 
		"assigned_user_name",
	);

	// This is the list of fields that are in the lists.
	var $list_fields = Array(
		"id",
		"name",
		"type",
		"location",
		"assigned_user_id",
		"assigned_user_name",
	);
	
	
	static function get_free_busy_rows($resource_id) {
		global $db;
		$now = date('Y-m-d');
		$rid = $db->quote($resource_id);
		$query = "SELECT DISTINCT m.date_start, ".
			"DATE_ADD(m.date_start, INTERVAL m.duration MINUTE) AS date_end, m.duration ".
			"FROM meetings m ".
			"LEFT JOIN meetings_resources lnk ON lnk.meeting_id=m.id AND NOT lnk.deleted ".
			"WHERE NOT m.deleted AND lnk.resource_id = '$rid' ".
			"AND m.date_start > DATE_SUB('$now', INTERVAL 7 DAY)";
		$result = $db->query($query);
		$rows = $db->fetchRows($result, true, false);
		return $rows;
	}
	
	
	static function get_free_busy_array($resource_id, $gmt=false, $date_format='db') {
		global $timedate;
		
		$rows = self::get_free_busy_rows($resource_id);
		$offs = $timedate->getTimeZoneOffset($timedate->getUserTimeZone(), true);
		if($date_format == 'user')
			$date_format = $timedate->get_date_time_format();
		else if($date_format == 'js')
			$date_format = 'Y/m/d H:i:s';
		else if($date_format == 'fb')
			$date_format = 'Ymd\THis\Z';
		else
			$date_format = 'db';
		if($date_format == 'db' && ! $gmt)
			$date_format = $timedate->get_db_date_time_format();
		$ret = array();

		foreach($rows as $row) {
			$times = array($row['date_start'], $row['date_end']);
			if($date_format != 'db') {
				foreach($times as $idx => $t) {
					$ts = strtotime($t);
					if(! $gmt) $ts += $offs;
					$times[$idx] = date($date_format, $ts);
				}
			}
			$ret[] = $times;
		}
		return $ret;
	}
	
	
	static function get_free_busy($resource_id, $gmt=false, $date_format='db') {
		$busy = '';
		$rows = self::get_free_busy_array($resource_id, $gmt, 'fb');
		foreach($rows as $row) {
			$busy .= "FREEBUSY:$row[0]/$row[1]\n";
		}
		return $busy;
	}
	

	function getResources($resType = '') {
		$sql = "SELECT id, name FROM resources "
			. "WHERE deleted=0 ";
		if (!empty($resType)) {
			$sql .= " AND type='{$resType}'";
		}
		
		$sql .= " ORDER BY name, type desc ";
		
		$db = & PearDatabase::getInstance();
		$resultSet = $db->query($sql, true);
		if($resultSet === false) {
			return false;
		}
		
		$result = array();
		while($row = $db->fetchByAssoc($resultSet)) {
			$result[$row['id']] = $row['name'];
		}

		return $result;
	}

	static function getNameById($id)
	{
		global $db;
		$sql = "SELECT name FROM resources WHERE id='" . PearDatabase::quote($id) . "'";
		$res = $db->query($sql, true);
		if ($row = $db->fetchByAssoc($res)) {
			return $row['name'];
		}
		return '';
	}

	function getResourcesByResType($resType, $narrowDownTeamIds = array()) {
		global $db;
		$sql = "SELECT id, name FROM resources "
			. "WHERE deleted=0 AND type='{$resType}'";
		
		if(!empty($narrowDownTeamIds)) {
			$strInCondition = Resource::getInCondition($narrowDownTeamIds);
			$sql .= " AND team_id IN({$strInCondition})";
		}
		
		$sql .= " ORDER BY name, type desc ";
		
		$resultSet = $db->query($sql, true);
		if($resultSet === false) {
			return false;
		}
		
		$result = array();
		while($row = $db->fetchByAssoc($resultSet)) {
			$result[$row['id']] = $row['name'];
		}

		return $result;
	}

	function getResourcesByConditionSQL($conditionSQL, $narrowDownTeamIds = array()) {
		global $db;
		$sql = "SELECT id, name FROM resources "
			. "WHERE deleted=0 ";
		
		if(!empty($narrowDownTeamIds)) {
			$strInCondition = Resource::getInCondition($narrowDownTeamIds);
			$sql .= " AND team_id IN({$strInCondition})";
		}
		
		if(!empty($conditionSQL)) {
			$sql .= " AND " . $conditionSQL;
		}
		
		$sql .= " ORDER BY name, type desc ";
		
		$resultSet = $db->query($sql, true);
		if($resultSet === false) {
			return false;
		}
		
		$result = array();
		while($row = $db->fetchByAssoc($resultSet)) {
			$result[$row['id']] = $row['name'];
		}

		return $result;
	}
}

?>
