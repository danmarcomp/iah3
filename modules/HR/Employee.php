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


require_once("data/SugarBean.php");
require_once("modules/Users/User.php");
require_once("modules/HR/EmployeeDependant.php");
require_once("modules/HR/EmployeeLeave.php");

class Employee extends SugarBean {

	// Stored fields
	var $id;
	var $user_id;
	var $employee_num;
	var $sex;
	var $sin_ssn;
	var $emergency_name;
	var $emergency_phone;
	var $dob;
	var $start_date;
	var $end_date;
	var $benefits_start_date;
	var $benefits_end_date;
	var $salary_currency_id;
	var $salary_exchange_rate;
	var $salary_amount;
	var $salary_amount_usdollar;
	var $salary_period;
	var $last_review;
	var $education;
	var $vacation_carryover;
	var $sickleave_carryover;
	var $vacation_accrual_rate;
	var $sickleave_accrual_rate;
	var $std_hourly_rate;
	var $std_hourly_rate_usdollar;
	var $date_modified;
	var $modified_user_id;
	
	// Fields stored in User - loaded from fields_array
	var $user_fields;
	
	var $first_name;
	var $last_name;
	var $user_name;
	var $department;
	var $location;
	var $sms_address;
	var $photo_filename;
	var $in_directory;
	var $description;
	var $phone_home;
	var $phone_mobile;
	var $phone_work;
	var $phone_other;
	var $phone_fax;
	var $email1;
	var $email2;
	var $address_street;
	var $address_city;
	var $address_state;
	var $address_postalcode;
	var $address_country;
	var $status;
	var $title;
	var $employee_status;
	var $reports_to_id;
	var $reports_to_name;
	
	var $name; // reqd for docs subpanel
	var $full_name;

	// Reference to EmployeeLeave object
	var $employee_leave;
	
	var $object_name = "Employee";
	var $table_name = "employees";
	var $module_dir = "HR";
	var $new_schema = true;

	
	var $default_order_by = "users.last_name, users.first_name";
	
	var $dependants_table = "employee_dependants";

	
	function Employee() {
		parent::SugarBean();
		
		// ajrw - needs to be done differently
		/*$this->user_fields = AppConfig::setting('model.detail.Employee.user_fields');
		$this->additional_column_fields = array_merge(
			$this->additional_column_fields, $this->user_fields);
		$user_fields_arr = AppConfig::setting('model.fields_compat.User');
		
		foreach($this->user_fields as $f) {
			if (!isset($this->field_defs[$f])) {
				$this->field_defs[$f] = $user_fields_arr[$f];
				$this->field_defs[$f]['source'] = 'non-db';
				$this->field_defs[$f]['dbType'] = $this->field_defs[$f]['type'];
				$this->field_defs[$f]['type'] = 'relate';
				$this->field_defs[$f]['table'] = 'users';
				$this->field_defs[$f]['rname'] = $f;
				$this->field_defs[$f]['id_name'] = 'user_id';
				$this->field_defs[$f]['link'] = 'user_link';
				$this->field_defs[$f]['join_name'] = 'users';
			}
		}
		if(isset($this->field_defs['department']))
			$this->field_defs['department']['massupdate'] = true;*/
	}
	
	function get_summary_text() {
		$this->fill_in_additional_detail_fields();
		return '' . $this->full_name;
	}
	
	function get_dependants() {
		$query = "SELECT * FROM $this->dependants_table ".
			"WHERE employee_id='".PearDatabase::quote($this->id)."' AND deleted=0";

		$fields = Array('id', 'employee_id', 'first_name', 'last_name',
			'dob', 'relationship');
		return $this->build_related_list2($query, new EmployeeDependant(), $fields);
	}

    /**
     * @param null $fiscal_year
     * @return EmployeeLeave 
     */
	function &get_employee_leave($fiscal_year = null) {
		if(! isset($this->employee_leave))
			$this->employee_leave = new EmployeeLeave($this->id, $fiscal_year);
		else
			$this->employee_leave->load($this->id, $fiscal_year);
			
		return $this->employee_leave;
	}
	
	function date_difference($y1, $m1, $d1, $y2, $m2, $d2) {
		$d1_earlier_in_year = ($m2 > $m1 || ($m2 == $m1 && $d2 >= $d1));
		if($y1 > $y2 || ($y1 == $y2 && !$d1_earlier_in_year))
			return '';
		$years = $y2 - $y1;
		if($d1_earlier_in_year) {
			$months = $m2 - $m1;
		}
		else {
			$years --;
			$months = 12 - $m1 + $m2;
		}
		if($d2 < $d1)
			$months --;
		$lbl = $GLOBALS['app_strings']['LBL_TIME_PERIOD_YEARS_MONTHS'];
		$lbl = str_replace(array('%1', '%2'), array((int)$years, (int)$months), $lbl);
		return $lbl;
	}
	
	function &get_user() {
		$user = new User();
		$user->retrieve($this->user_id);
		return $user;
	}
	
	function retrieve_for_user_id($user_id) {
		$query = "SELECT id from employees WHERE user_id='$user_id'";
		//$result = $this->db->requireSingleResult($query, false);
		$result = $this->db->limitQuery($query,0,1,false);
		if(!empty($result)) {
			$row = $this->db->fetchByAssoc($result);
			$this->retrieve($row['id']);
			return $this;
		}
		return '';
	}
	
	// static method to create Employees for new users
	function create_for_user($user) {
		if($user instanceof RowUpdate) {
			$empl = RowUpdate::blank_for_model('Employee');
			$empl->set('user_id', $user->getField('id'));
			$empl->set('modified_user_id', $user->getField('id'));
			$empl->save();
		}
	}
	
	function save($check_notify = FALSE) {
		
		$this->unformat_all_fields();
		
		require_once('modules/Currencies/Currency.php');
		$salary_currency = new Currency();
		$salary_currency->retrieve($this->salary_currency_id);
		$params = array('currency_field' => 'salary_currency_id', 'rate_field' => 'salary_exchange_rate');
		$rate_changed = adjust_exchange_rate($this, $salary_currency, $params);

		if(isset($this->salary_amount))
			$this->salary_amount_usdollar = $salary_currency->convertToDollar($this->salary_amount);
		if(isset($this->std_hourly_rate))
			$this->std_hourly_rate_usdollar = $salary_currency->convertToDollar($this->std_hourly_rate);
		
		// remove user fields from field_defs
		//foreach($this->user_fields as $f)
		//	unset($this->field_defs[$f]);
		
		$ret = parent::save($check_notify);
		
		if(isset($this->employee_leave))
			$this->employee_leave->save();
		
		// ajrw - currently disabled
		/*$user = new User();
		$result = $user->retrieve($this->user_id);
		if(!empty($result)) {
			foreach($this->user_fields as $f)
				$user->$f = $this->$f;
			$user->save();
		}*/
		return $ret;
	}
	
	/* obsolete
    function get_hourly_rates($use_id = false)
    {
        global $db;
        $query = 'SELECT user_id, id, std_hourly_rate_usdollar FROM employees WHERE deleted != 1';
        $res = $db->query($query);
        $ret = array();
        while ($row = $db->fetchByAssoc($res)) {
            if ($use_id) {
                $ret[$row['id']] = (float)$row['std_hourly_rate_usdollar'];
            } else {
                $ret[$row['user_id']] = (float)$row['std_hourly_rate_usdollar'];
            }
        }
        return $ret;
    } 
	*/   
	
	function bean_implements($interface)
	{
		switch($interface) {
			case 'ACL':
				return true;
		}
		return false;
	}
	
	function ACLAccess($view,$is_owner='not_set')
	{
		if (strtolower($view) == 'delete') {
			return false;
		}
		return parent::ACLAccess($view, $is_owner);
	}

	function filter_portal_users()
	{
		return 'users.portal_only = 0';
	}

	function _create_proper_name_field() {
        global $locale;
        $full_name = $locale->getLocaleFormattedName($this->first_name, $this->last_name);
        $this->name = $full_name;
        $this->full_name = $full_name; 

	}

    /**
     * Get list of fields from User model
     *
     * @static
     * @return array
     */
    static function get_user_fields() {
        $employee_fields = AppConfig::setting('model.fields.Employee');
        $user_fields = array();

        foreach($employee_fields as $field_def) {
            if ( (isset($field_def['source']) && isset($field_def['source']['type'])) && $field_def['source']['type'] == 'field' && $field_def['name'] != '')  {
                $user_fields[] = $field_def['name'];

            }
        }

        return $user_fields;
    }

    /**
     * Update related user
     *
     * @static
     * @param RowUpdate $upd
     */
    static function update_user(RowUpdate &$upd) {
        $user_fields = self::get_user_fields();
        $user_update = array();

        if (sizeof($user_fields) > 0) {
            for ($i = 0; $i < sizeof($user_fields); $i++) {
                if (isset($_REQUEST[$user_fields[$i]]))
                    $user_update[$user_fields[$i]] = $_REQUEST[$user_fields[$i]];
            }
        }

        $rel_files = $upd->related_files;
        if (isset($rel_files['photo_filename']) && get_class($rel_files['photo_filename']) == 'UploadFile') {
            $user_update['photo_filename'] = $rel_files['photo_filename']->stored_file_name;
        }

        if (sizeof($user_update) > 0) {
            $user_result = ListQuery::quick_fetch('User', $upd->getField('user_id'));

            if ($user_result) {
                $user_upd = RowUpdate::for_result($user_result);
                $user_upd->set($user_update);
                $user_upd->save();
            }
        }
    }
}
?>