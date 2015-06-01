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

class ContractType extends SugarBean {

	// Stored fields
  	var $id;
	var $name;
	var $response_time;
	var $vendor_name;
	var $date_entered;
	var $created_by;
	var $date_modified;
	var $modified_user_id;
	var $description;
	var $deleted;
	var $contract_no;
	
	// not stored
	var $created_by_name;
	var $modified_user_name;
	
	
	var $table_name = 'service_contracttypes';
	var $object_name = 'ContractType';
	var $module_dir = 'ContractTypes';
	var $new_schema = true;


	// This is used to retrieve related fields from form posts.
	var $additional_column_fields = Array(
		'created_by_name',
		'modified_user_name',
	);

	
	function ContractType() {
		parent::SugarBean();
	}


	function get_summary_text()
	{
		// do not show in tracker
		return null;
	}

	/*function create_list_query($order_by, $where)
	{
		$query = "
			SELECT assets.name as nhouse_product_name,
				`".$this->table_name."`.*
				
			FROM `".$this->table_name."`, assets
		";
		
		$where_auto = " assets.id = nhouse_product_id ";

		if($where != "")
			$query .= "where $where AND ".$where_auto;
		else
			$query .= "where ".$where_auto;

		if($order_by != "")
			$query .= " ORDER BY $order_by";
		else
			$query .= " ORDER BY `".$this->table_name."`.name";


		return $query;
	}*/


        function create_export_query($order_by, $where)
        {

        }


	function save_relationship_changes( $is_update )
	{
	
	}



	/// This function fills in data for the list view only.
	function fill_in_additional_list_fields()
	{
	}


	/// This function fills in data for the detail view only.
	function fill_in_additional_detail_fields()
	{
		$this->created_by_name = get_assigned_user_name($this->created_by);
		$this->modified_user_name = get_assigned_user_name($this->modified_user_id);
	}


	function get_list_view_data()
	{

		$temp_array = $this->get_list_view_array();
		
		return $temp_array;
	}


	/// Called when this object is created or modified.
	///
	function save($check_notify = FALSE) {
	
		return parent::save($check_notify);
	}

	function parse_additional_headers(&$list_form, $xTemplateSection) {

	}

	function list_view_parse_additional_sections(&$list_form, $xTemplateSection) {

		return $list_form;
	}

}

/// returns an array of all ContractTypes in the format $ar[my_id] = my_name
/// returns false if there are no ContractTypes
function get_contract_types_list()
{
	$seed = new ContractType();
	$query = "SELECT id, name FROM `service_contracttypes` WHERE NOT deleted ORDER BY name ";
	$result = $seed->db->query($query, false, "Error retrieving contract type list");

	$ar = array();
	while(($row = $seed->db->fetchByAssoc($result)) != null)
		$ar[$row['id']] = $row['name'];
	return $ar;
}

?>
