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

 * Description:  TODO: To be written.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/

require_once('data/SugarBean.php');
require_once('include/utils.php');

class Prospect extends SugarBean {

    var $field_name_map;
	// Stored fields
	var $id;
	var $name = '';
	var $date_entered;
	var $date_modified;
	var $modified_user_id;
	var $assigned_user_id;
	var $created_by;
	var $created_by_name;
	var $modified_by_name;
	var $description;
	var $salutation;
	var $first_name;
	var $last_name;
	var $full_name;
	var $title;
	var $department;
	var $birthdate;
	var $do_not_call;
	var $phone_home;
	var $phone_mobile;
	var $phone_work;
	var $phone_other;
	var $phone_fax;
	var $email1;
	var $email_and_name1;
	var $email_and_name2;
	var $email2;
	var $assistant;
	var $assistant_phone;
	var $email_opt_out;
	var $primary_address_street;
	var $primary_address_city;
	var $primary_address_state;
	var $primary_address_postalcode;
	var $primary_address_country;
	var $alt_address_street;
	var $alt_address_city;
	var $alt_address_state;
	var $alt_address_postalcode;
	var $alt_address_country;
	var $tracker_key;
	var $invalid_email;
	var $lead_id;
	var $account_name;
	var $assigned_real_user_name;
	// These are for related fields
	var $assigned_user_name;
	var $last_activity_date;

	var $module_dir = 'Prospects';
	var $table_name = "prospects";
	var $object_name = "Prospect";

	var $new_schema = true;

	// This is used to retrieve related fields from form posts.
	var $additional_column_fields = Array('assigned_user_name');

	function Prospect() {
		global $current_user;
		parent::SugarBean();
	}
	
	// need to override to have a name field created for this class
	function retrieve($id = -1, $encode=true) {
		global $locale;

		$ret_val = parent::retrieve($id, $encode);

		// make a properly formatted first and last name
		$full_name = $locale->getLocaleFormattedName($this->first_name, $this->last_name, $this->salutation);
		$this->name = $full_name;
		$this->full_name = $full_name; 

		return $ret_val;
	}
    
    /**
     * Generate the name field from the first_name and last_name fields.
     */
    function _create_proper_name_field() {
        global $locale;
        $full_name = $locale->getLocaleFormattedName($this->first_name, $this->last_name, $this->salutation);
        $this->name = $full_name;
        $this->full_name = $full_name; 
    }
    
	function get_summary_text() {
        $this->_create_proper_name_field();
        return $this->name;
	}

	function create_list_query($order_by, $where, $show_deleted=0) {
		$custom_join = $this->custom_fields->getJOIN();
		$query = "SELECT ";
		$query .= db_concat($this->table_name,array('first_name','last_name')) . " name, ";
		$query .= "
                users.user_name as assigned_user_name ";

		if($custom_join){
   				$query .= $custom_join['select'];
 		}
        $query .= " ,$this->table_name.* 
                FROM prospects ";

		$query .=		"LEFT JOIN users
	                    ON prospects.assigned_user_id=users.id ";

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

    function create_export_query(&$order_by, &$where) {
        $custom_join = $this->custom_fields->getJOIN();
        $query = "SELECT
                prospects.*,
                users.user_name as assigned_user_name ";

        if($custom_join){
            $query .= $custom_join['select'];
        }
        $query .= " FROM prospects ";

        $query .= "LEFT JOIN users ON prospects.assigned_user_id=users.id ";

        if($custom_join){
            $query .= $custom_join['join'];
        }

        $where_auto = " prospects.deleted=0 ";

        if($where != "")
        $query .= "where ($where) AND ".$where_auto;
        else
        $query .= "where ".$where_auto;

        if(!empty($order_by))
        $query .= " ORDER BY $order_by";

        return $query;
    }

	function save_relationship_changes($is_update) { }

	function fill_in_additional_list_fields() {
		global $locale;

		$full_name = $locale->getLocaleFormattedName($this->first_name, $this->last_name, $this->salutation);
		$this->name = $full_name;
		$this->full_name = $full_name; 

		$this->email_and_name1 = $full_name." &lt;".$this->email1."&gt;";
		$this->email_and_name2 = $full_name." &lt;".$this->email2."&gt;";
	}

	function fill_in_additional_detail_fields() {
		global $locale;

		$full_name = $locale->getLocaleFormattedName($this->first_name, $this->last_name, $this->salutation);
		$this->name = $full_name;
		$this->full_name = $full_name; 

		// Fill in the assigned_user_name
		$this->assigned_user_name = get_assigned_user_name($this->assigned_user_id);
		$this->created_by_name = get_assigned_user_name($this->created_by);
		$this->modified_by_name = get_assigned_user_name($this->modified_user_id);
	}

	function get_list_view_data() {
		global $locale;
		
		$full_name = $locale->getLocaleFormattedName($this->first_name, $this->last_name, $this->salutation);
		$this->name = $full_name;
		$this->full_name = $full_name; 

		$temp_array = $this->get_list_view_array();
//        $temp_array["ENCODED_NAME"]=$this->first_name.' '.$this->last_name;
		$temp_array["ENCODED_NAME"] = $full_name;
		$temp_array["FULL_NAME"] = $full_name;
		// longreach - start added
		$phone_fields = array(
			'phone_home', 'phone_mobile', 'phone_work', 'phone_other', 'phone_fax', 'assistant_phone'
		);
		foreach ($phone_fields as $f) {
			if (isset($this->$f)) $temp_array[strtoupper($f)] = make_skype_link($this->$f);
		}
		// longreach - end added
    	return $temp_array;
	}

	function get_prospect_id_by_email($email) {
		$where_clause = "(email1='$email' OR email2='$email') AND deleted=0";

        $query = "SELECT * FROM $this->table_name WHERE $where_clause";
        $GLOBALS['log']->debug("Retrieve $this->object_name: ".$query);

        //requireSingleResult has beeen deprecated.
        //$result = $this->db->requireSingleResult($query, true, "Retrieving record $where_clause:");
        $result = $this->db->limitQuery($query,0,1,true, "Retrieving record $where_clause:");

        if( empty($result)) {
            return null;
        }

        $row = $this->db->fetchByAssoc($result, -1, true);
		return $row['id'];
	}

    function converted_prospect($prospectid, $contactid, $accountid, $opportunityid) {
    	$query = "UPDATE prospects set  contact_id=$contactid, account_id=$accountid, opportunity_id=$opportunityid where  id=$prospectid and deleted=0";
		$this->db->query($query,true,"Error converting prospect: ");
		//todo--status='Converted', converted='1',
    }	

    function bean_implements($interface) {
        switch($interface){
            case 'ACL':return true;
        }
        return false;
    }
	
    /**
     * This method will be used by Mail Merge in order to retrieve the targets as specified in the query
     *
     * @param string $query - this is the query which contains the where clause for the query
     * @param $fields
     * @param int $offset
     * @param $limit
     * @param $max
     * @param int $deleted
     * @param string $module
     * @return array
     */
    function retrieveTargetList($query, $fields, $offset = 0, $limit= -1, $max = -1, $deleted = 0, $module = ''){
        global  $beanList, $beanFiles; 
        $module_name = $this->module_dir;
       
        if(empty($module)){
            $pattern = '/AND related_type = #(.*)#/i';
            if(preg_match($pattern, $query, $matches) && count($matches) > 1){
                $module_name = $matches[1];
                $query = preg_replace($pattern, "", $query);
            }
             $GLOBALS['log']->debug("PROSPECT QUERY: ".$query);
        }
        $GLOBALS['log']->debug(var_export($matches, true));
        $count = count($fields);
        $index = 1;
        $sel_fields = "";
        if(!empty($fields)){
            foreach($fields as $field){
                if($field == 'id'){
                	$sel_fields .= 'prospect_lists_prospects.id id';
                }else{
                	$sel_fields .= $module_name.".".$field;
                }
                if($index < $count){
                    $sel_fields .= ",";  
                }
                $index++;
            }
        }
       
        $module_name = ucfirst($module_name);
        $class_name = $beanList[$module_name];
        require_once($beanFiles[$class_name]);
        $seed = new $class_name();
        if(empty($sel_fields)){
            $sel_fields = $seed->table_name.'.*';
        }
        $select = "SELECT ".$sel_fields." FROM ".$seed->table_name;
        $select .= " INNER JOIN prospect_lists_prospects ON prospect_lists_prospects.related_id = ".$seed->table_name.".id";
        $select .= " INNER JOIN prospect_list_campaigns ON prospect_list_campaigns.prospect_list_id = prospect_lists_prospects.prospect_list_id";
        $select .= " INNER JOIN campaigns on campaigns.id = prospect_list_campaigns.campaign_id";
        if (!empty($query)) {
            $select .= " WHERE ".$query;
        }
        
        return $this->process_list_query($select, $offset, $limit, $max, $query);
    }
    
    /**
     *  Given an id, looks up in the prospect_lists_prospects table
     *  and retrieve the correct type for this id
     */
    function retrieveTarget($id){
        $query = "SELECT related_id, related_type FROM prospect_lists_prospects";  
        $query .= " INNER JOIN prospect_list_campaigns ON prospect_list_campaigns.prospect_list_id = prospect_lists_prospects.prospect_list_id";
        $query .= " INNER JOIN campaigns ON campaigns.id = prospect_list_campaigns.campaign_id";
        $query .= " WHERE campaigns.id = '".$id."'"; 
        $result = $this->db->query($query);
        if(($row = $this->db->fetchByAssoc($result))){
             global  $beanList, $beanFiles; 
             $module_name = $row['related_type'];
             $class_name = $beanList[$module_name];
             require_once($beanFiles[$class_name]);
             $seed = new $class_name();
             return $seed->retrieve($row['related_id']);        
        }else{
            return null;   
        }
    }

	static function find_duplicates(RowUpdate &$upd, $redirect = true, $type = '') {
		$op = 'AND';
		switch ($type) {
			case 'name_only':
				$check_name = true;
				$check_email = false;
				break;
			case 'email_only':
				$check_name = false;
				$check_email = true;
				break;
			case 'name_and_email':
				$check_name = true;
				$check_email = true;
				break;
			default:
				$check_name = true;
				$check_email = true;
				$op = 'OR';
				break;
		}
		$clauses = array('multiple' => array(), 'operator' => $op);
		if ($check_name) {
			if($upd->getField('first_name') && $upd->getField('last_name')) {
				$clauses['multiple'][] = array(
					'operator' => 'AND',
					'multiple' => array(
						array(
							'field' => 'first_name',
							'value' => $upd->getField('first_name'),
						),
						array(
							'field' => 'last_name',
							'value' => $upd->getField('last_name'),
						),
					),
				);
			} else {
				$clauses['multiple'][] = array(
					'field' => 'last_name',
					'value' => $upd->getField('last_name'),
				);
			}
		}

		if ($check_email) {
			if($upd->getField('email1')) {
				$clauses['multiple'][] = array(
					'operator' => 'OR',
					'multiple' => array(
						array(
							'field' => 'email1',
							'value' => $upd->getField('email1'),
						),
						array(
							'field' => 'email2',
							'value' => $upd->getField('email1'),
						),
					),
				);
			}

			if($upd->getField('email2')) {
				$clauses['multiple'][] = array(
					'operator' => 'OR',
					'multiple' => array(
						array(
							'field' => 'email1',
							'value' => $upd->getField('email2'),
						),
						array(
							'field' => 'email2',
							'value' => $upd->getField('email2'),
						),
					),
				);
			}
		}

		$clauses = array($clauses);

        require_once('include/layout/DuplicateManager.php');
        $manager = new DuplicateManager($upd, $_REQUEST);
        return $manager->check($clauses, $redirect);
    }
}

