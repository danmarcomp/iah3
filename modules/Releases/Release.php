<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/*********************************************************************************
 * The contents of this file are subject to the SugarCRM Public License Version
 * 1.1.3 ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied.  See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * All copies of the Covered Code must include on each user interface screen:
 *    (i) the "Powered by SugarCRM" logo and
 *    (ii) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.  See full license for
 * requirements.
 *
 * The Original Code is: SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) 2004-2006 SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 ********************************************************************************/
/*********************************************************************************

 * Description:
 ********************************************************************************/




require_once('data/SugarBean.php');
require_once('include/utils.php');

class Release extends SugarBean {
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
	var $status;
	var $product_id;
	var $product_name;

	var $table_name = "releases";

	var $object_name = "Release";
	var $module_dir = 'Releases';
	var $new_schema = true;

	// This is used to retrieve related fields from form posts.
	var $additional_column_fields = Array();

	function Release() {
		parent::SugarBean();



	}

	function get_summary_text()
	{
		return "$this->name";
	}

	function get_releases($add_blank=false,$status='Active')
	{
		$query = "SELECT id, name FROM $this->table_name where deleted=0 ";
		if ($status=='Active') {
			$query .= " and status='Active' ";
		}
		elseif ($status=='Hidden') {
			$query .= " and status='Hidden' ";
		}
		elseif ($status=='All') {
		}
		$query .= " order by list_order asc";
		$result = $this->db->query($query, false);

		$list = array();
		if ($add_blank) {
			$list['']='';
		}
		if($result) {
			while (($row = $this->db->fetchByAssoc($result)) != null) {
				$list[$row['id']] = $row['name'];
				$GLOBALS['log']->debug("row id is:".$row['id']);
				$GLOBALS['log']->debug("row name is:".$row['name']);
			}
		}
		return $list;
	}

	function create_list_query($order_by, $where, $show_deleted = 0)
	{
		$custom_join = $this->custom_fields->getJOIN();
                $query = "SELECT ";
               
                $query .= " $this->table_name.* ";
                if($custom_join){
   				$query .= $custom_join['select'];
 			}
                $query .= " FROM ".$this->table_name." ";
                if($custom_join){
  				$query .= $custom_join['join'];
			}
		$where_auto = '1=1';
				if($show_deleted == 0){
                	$where_auto = "$this->table_name.deleted=0";
				}else if($show_deleted == 1){
                	$where_auto = "$this->table_name.deleted=1";
				}

		if($where != "")
			$query .= "where ($where) AND ".$where_auto;
		else
			$query .= "where ".$where_auto;

		if(!empty($order_by))
			$query .= " ORDER BY $order_by";

		return $query;
	}

	function fill_in_additional_list_fields()
	{
		$this->fill_in_additional_detail_fields();
	}

	function fill_in_additional_detail_fields() {
		$query = "
			SELECT 
				name AS product_name
			FROM software_products
			WHERE '{$this->product_id}' = software_products.id
			";

		$result = $this->db->query($query, true,"Error filling in additional detail fields: ");

		$row = $this->db->fetchByAssoc($result);
		if($row) {
			$this->product_name = $row['product_name'];
        } else {
			$this->product_name = '';
        }
	}

	function get_list_view_data(){
		$temp_array = $this->get_list_view_array();
        $temp_array["ENCODED_NAME"]=$this->name;
        $temp_array['ENCODED_STATUS'] = $this->status;
//	$temp_array["ENCODED_NAME"]=htmlspecialchars($this->name, ENT_QUOTES);
    	return $temp_array;

	}

    function get_for_product($product_id, $for_select = false, $encode = true, $status='All')
    {
        global $app_strings;
        if (empty($app_strings)) $app_strings = return_application_language('');
        if ($for_select) {
            $releases= array('' => $app_strings['LBL_NONE']);
        } else {
            $releases = array(array('id' => '', 'name' =>$app_strings['LBL_NONE']));
        }
        $query = sprintf(
            "SELECT id, name FROM releases WHERE product_id='%s' AND !deleted",
            PearDatabase::quote($product_id)
        );
		
		if ($status=='Active') {
			$query .= " and status='Active' ";
		}
        
        $res = $this->db->query($query);
        while ($row = $this->db->fetchByAssoc($res, -1, $encode)) {
            if ($for_select) {
                $releases[$row['id']]  = $row['name'];
            } else {
                $releases[] = $row;
            }
        }
        return $releases;
    }

}

?>
