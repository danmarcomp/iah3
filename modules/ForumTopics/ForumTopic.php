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
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

/*********************************************************************************
 * $Id
 * Description:
 ********************************************************************************/

require_once('data/SugarBean.php');
require_once('include/utils.php');

class ForumTopic extends SugarBean {
	// Stored fields
	var $id;
	var $deleted;
	var $date_entered;
	var $date_modified;
	var $modified_user_id;
	var $created_by;
	var $created_by_name;
	var $modified_by_name;
	var $name;

	var $table_name = "forumtopics";

	var $object_name = "ForumTopic";
	var $module_dir = 'ForumTopics';
	var $new_schema = true;

	// non-db fields
	var $can_delete;

	// This is used to retrieve related fields from form posts.
	var $additional_column_fields = Array();

	function ForumTopic() {
		parent::SugarBean();

		$this->disable_row_level_security = true;

	}

	function get_summary_text() {
		return "$this->name";
	}

	function bean_implements($interface) {
		switch ($interface) {
			case 'ACL':
				return false;
		}
		return false;
	}


	function get_topics($add_blank = false) {
		$query = "SELECT name FROM $this->table_name where deleted=0 ";
		$query .= " order by list_order asc";
		$result = $GLOBALS['db']->query($query, false);

		$list = array();
		if ($add_blank) {
			$list[''] = '';
		}
		while (($row = $this->db->fetchByAssoc($result)) != null) {
			$list[$row['name']] = $row['name'];
		}
		return $list;
	}

	public static function &get_order($category_name) {
		$seed = new ForumTopic();
		$query = "SELECT list_order from forumtopics where name='" . $GLOBALS['db']->quote($category_name) . "' ";
		$result = $GLOBALS['db']->query($query, false);
		$row = $seed->db->fetchByAssoc($result);

		return $row['list_order'];
	}

	function create_list_query($order_by, $where, $show_deleted = 0) {
		$custom_join = $this->custom_fields->getJOIN();
		$query = "SELECT ";

		$query .= " $this->table_name.* ";
		if ($custom_join) {
			$query .= $custom_join['select'];
		}
		$query .= " FROM " . $this->table_name . " ";
		if ($custom_join) {
			$query .= $custom_join['join'];
		}
		$where_auto = '1=1';
		if ($show_deleted == 0) {
			$where_auto = "$this->table_name.deleted=0";
		} else if ($show_deleted == 1) {
			$where_auto = "$this->table_name.deleted=1";
		}

		if ($where != "")
			$query .= "where ($where) AND " . $where_auto;
		else
			$query .= "where " . $where_auto;

		if (!empty($order_by))
			$query .= " ORDER BY $order_by";

		return $query;
	}

	function fill_in_additional_list_fields() {
		$this->fill_in_additional_detail_fields();

		$res = $GLOBALS['db']->query("select * from forums where category='" . $this->name . "' and deleted=0");
		$num_rows = $this->db->getRowCount($res);
		$this->can_delete = ($num_rows > 0 ? false : true);
		//echo $this->name." - can delete? - ".($this->can_delete ? "yes" : "no")."<BR>";
	}

	function fill_in_additional_detail_fields() {
		$res = $GLOBALS['db']->query("select * from forums where category='" . $this->name . "' and deleted=0");
		$num_rows = $this->db->getRowCount($res);
		$this->can_delete = ($num_rows > 0 ? false : true);
	}

	function get_list_view_data() {
		$temp_array = $this->get_list_view_array();
		$temp_array["ENCODED_NAME"] = $this->name;
		//	$temp_array["ENCODED_NAME"]=htmlspecialchars($this->name, ENT_QUOTES);
		return $temp_array;

	}

}

?>
