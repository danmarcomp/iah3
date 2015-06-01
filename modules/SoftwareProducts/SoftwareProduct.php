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

class SoftwareProduct extends SugarBean {	

	// Stored fields
  	var $id;
	var $name;
	var $description;
	var $deleted;
	
	
	var $table_name = 'software_products';
	var $object_name = 'SoftwareProduct';
	var $module_dir = 'SoftwareProducts';
	var $new_schema = true;


	// This is used to retrieve related fields from form posts.
	var $additional_column_fields = Array(
	);

	
	function SoftwareProduct() {
		parent::SugarBean();
	}


	function get_summary_text()
	{
		return "$this->name";
	}

	function create_list_query($order_by, $where)
	{
		$query = "
			SELECT 
				`".$this->table_name."`.*
				
			FROM `".$this->table_name."`
		";
		
		$where_auto = " deleted = 0 ";

		if($where != "")
			$query .= "where $where AND ".$where_auto;
		else
			$query .= "where ".$where_auto;

		if($order_by != "")
			$query .= " ORDER BY $order_by";
		else
			$query .= " ORDER BY `".$this->table_name."`.name";

		return $query;
	}


        function create_export_query($order_by, $where)
        {
		$query = "
			SELECT 
				`".$this->table_name."`.*
			FROM `".$this->table_name."`
		";
		
		$where_auto = " deleted = 0 ";

		if($where != "")
			$query .= "where $where AND ".$where_auto;
		else
			$query .= "where ".$where_auto;

		if($order_by != "")
			$query .= " ORDER BY $order_by";
		else
			$query .= " ORDER BY `".$this->table_name."`.name";

		return $query;
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
	
	function mark_deleted($id) {
		parent::mark_deleted($id);
		require_once('modules/Releases/Release.php');
		$release = new Release;
		$releases = $release->get_for_product($id);
		foreach($releases as $r) {
			if($r['id'] !== '')
				$release->mark_deleted($t['id']);
		}
		$release->cleanup();
	}

	function bean_implements($interface){
	    switch($interface){
			case 'ACL':return true;
		}
		return false;
	}
	
}


/// returns an array of all product_categories in the format $ar[my_id] = my_name
/// returns false if there are no product_categories
function get_software_products_list($forSelect = false, $raw = false)
{
	$seed = new SoftwareProduct;
	$query = "SELECT id, name FROM `$seed->table_name` WHERE NOT deleted ORDER BY name ";
	$result = $seed->db->query($query, false, "Error retrieving $seed->object_name list");

	$ar = array();
	while(($row = $seed->db->fetchByAssoc($result)) != null) {
		if ($forSelect) {
			$ar[$row['id']] = $row;
		} else {
			$ar[$row['id']] = $row['name'];
		}
	}
	$seed->cleanup();
	return $ar;
}

