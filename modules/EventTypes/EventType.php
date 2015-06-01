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
require_once('include/utils.php');

class EventType extends SugarBean {
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

	var $table_name = "event_types";

	var $object_name = "EventType";
	var $module_dir = 'EventTypes';
	var $new_schema = true;

	// This is used to retrieve related fields from form posts.
	var $additional_column_fields = Array();

	function EventType() {
		parent::SugarBean();
	}

	function get_summary_text()
	{
		return "$this->name";
	}

	function get_event_types($add_blank=false)
	{
		$query = "SELECT id, name FROM $this->table_name where deleted=0 ";
		$query .= " order by name asc";
		$result = $this->db->query($query, false);
		//$GLOBALS['log']->debug("get_event_types : result is ".$result);

		$list = array();
		if ($add_blank) {
			$list['']='';
		}
			while (($row = $this->db->fetchByAssoc($result)) != null) {
				$list[$row['id']] = $row['name'];
			}
		return $list;
	}

	function fill_in_additional_list_fields()
	{
		$this->fill_in_additional_detail_fields();
	}

	function fill_in_additional_detail_fields() {

	}

	function get_list_view_data(){
		$temp_array = $this->get_list_view_array();
        $temp_array["ENCODED_NAME"]=$this->name;
    	return $temp_array;

	}

}

function get_event_types($add_blank=true)
{
	$seed = new EventType;
	return $seed->get_event_types($add_blank);
}

?>
