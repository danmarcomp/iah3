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

class EmployeeDependant extends SugarBean {

	// Stored fields
	var $id;
	var $employee_id;
	var $first_name;
	var $last_name;
	var $dob;
	var $relationship;
	var $date_modified;
	var $modified_user_id;

	// Related fields
	var $employee_name;

	var $table_name = "employee_dependants";
	var $object_name = "EmployeeDependant";
	var $module_dir = "HR";

	var $new_schema = true;
	
	function EmployeeDependant() {
		parent::SugarBean();
		/*$this->log = LoggerManager::getLogger('Dependant');
		$this->db = new PearDatabase();*/
	}

	function fill_in_additional_detail_fields() {
		$query = "SELECT first_name, last_name from users LEFT JOIN employees emp ON emp.user_id=users.id WHERE emp.id='{$this->employee_id}' AND users.deleted=0";
		$result =$this->db->query($query, true, "Error filling in additional detail fields: ");
		// Get the id and the name.
		$row = $this->db->fetchByAssoc($result);

		if($row != null)
		{
			$this->employee_name = return_name($row, 'first_name', 'last_name');
		}
	}

	function create_list_query(&$order_by, &$where) {
		$query = "SELECT id, first_name, last_name, dob, relationship FROM $this->table_name ";
		$where_auto = "deleted=0";

		if($where != "")
			$query .= "where $where AND ".$where_auto;
		else
			$query .= "where ".$where_auto;

		$query .= " ORDER BY last_name, first_name";

		return $query;
	}

    static function remove_relation(RowUpdate $upd, $link_name=null) {
        $link = $upd->getLinkUpdate();
        if ($link)
            $link->markDeleted();
    }
}
?>