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

class ProductType extends SugarBean {	

	// Stored fields
  	var $id;
	var $name;
	var $description;
	var $deleted;

    var $category_id;
    var $category_name;
	
	
	var $table_name = 'product_types';
	var $object_name = 'ProductType';
	var $module_dir = 'ProductTypes';
	var $new_schema = true;


	// This is used to retrieve related fields from form posts.
	var $additional_column_fields = Array(
        'category_name',
	);

	
	function ProductType() {
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
				`".$this->table_name."`.*,
                product_categories.name as category_name
				
			FROM `".$this->table_name."`
            LEFT JOIN product_categories ON product_categories.id=product_types.category_id
		";
		
		$where_auto = " not product_types.deleted ";

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
		$query = "
			SELECT 
				product_categories.name AS category_name
			FROM product_categories
			WHERE '{$this->category_id}' = product_categories.id
			";

		$result = $this->db->query($query, true,"Error filling in additional detail fields: ");

		$row = $this->db->fetchByAssoc($result);
		if($row) {
			$this->category_name = $row['category_name'];
        } else {
			$this->category_name = '';
        }
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

    function get_for_category($category_id, $for_select = false, $encode = true)
    {
        global $app_strings;
        if (empty($app_strings)) $app_strings = return_application_language('');
        if ($for_select) {
            $types = array('' => $app_strings['LBL_NONE']);
        } else {
            $types = array(array('id' => '', 'name' =>$app_strings['LBL_NONE']));
        }
        $query = sprintf(
            "SELECT id, name FROM product_types WHERE category_id='%s' AND !deleted ORDER BY name",
            PearDatabase::quote($category_id)
        );
        
        $res = $this->db->query($query);
        while ($row = $this->db->fetchByAssoc($res, -1, $encode)) {
            if ($for_select) {
                $types[$row['id']]  = $row['name'];
            } else {
                $types[] = $row;
            }
        }
        return $types;
    }
    
	function get_search_categories($param, &$searchFields)
	{
		require_once 'modules/ProductCategories/ProductCategory.php';
		return array('' => '') + get_product_categories_list(true);
	}

}

/// returns an array of all product_types in the format $ar[my_id] = my_name
/// returns false if there are no product_types
function get_product_types_list()
{
	$seed = new ProductType();
	$query = "SELECT id, name FROM `$seed->table_name` WHERE NOT deleted ORDER BY name ";
	$result = $seed->db->query($query, false, "Error retrieving $seed->object_name list");

	$ar = array();
	while(($row = $seed->db->fetchByAssoc($result)) != null)
		$ar[$row['id']] = $row['name'];
	$seed->cleanup();
	return $ar;
}

?>
