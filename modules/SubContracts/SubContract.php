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


class SubContract extends SugarBean 
{
	// Stored fields
	var $id;
	var $name;
	var $main_contract_id;
	var $contract_type_id;
	var $description;
	var $date_start;
	var $date_expire;
	var $date_billed;
	var $date_entered;
	var $date_modified;
	
	var $vendor_contract;
	var $employee_contact_id;
	var $customer_contact_id;
	
	
	var $is_active;
	var $status_colour;
	
	var $main_contract_name;
	var $employee_contact_name;
	var $employee_contact_user_name;
	var $customer_contact_name;
	var $customer_contact_phone;
	var $contract_type_name;
	var $account_id; // from parent
	var $account_name;
	var $created_by_name;
	var $modified_user_name;
	
	
	//var $total_cost;
	//var $total_list;
	var $total_purchase;
	var $total_support_cost;
	//var $total_book;
	var $currency_symbol;
	
	// relationship fields
	var $asset_id;
	var $case_id;

	var $table_name = 'service_subcontracts';
	var $types_table_name = 'service_contracttypes';
	var $object_name = 'SubContract';
	var $module_dir = 'SubContracts';
	var $new_schema = true;
	
	// This is used to retrieve related fields from form posts.
	var $additional_column_fields = Array(
		'created_by_name',
		'modified_user_name',
		'main_contract_name',
		'account_name',
		'employee_contact_name',
		'employee_contact_user_name',
		'customer_contact_name',
		'customer_contact_phone',
		'contract_type_name',
	);
	
	var $relationship_fields = Array(
		'asset_id' => 'assets',
		'case_id' => 'cases',
	);

	function SubContract() {
		parent::SugarBean();
	}


	function get_summary_text()
	{
		return $this->name;
	}
	
	function track_view($user_id, $current_module) {}

	function create_list_query($order_by, $where)
	{
		/* notes on status colour:
			show grey when subcontract is inactive
			show green when it is active and expiring in over a month or has no expiry date
			show yellow when it's active and expiring in under a month
			show red when it's active but expired
		*/
		//$custom_join = $this->custom_fields->getJOIN();
        $custom_join = null;
		$query = "
			SELECT DISTINCT 
				main_contract.contract_no as main_contract_name,
				main_contract.created_by as main_contract_name_owner,
				accounts.id as account_id, accounts.name as account_name,
				con_type.name  as contract_type_name, '-1' AS contract_type_name_owner,
				CONCAT_WS(' ', if(employee.first_name = '', NULL, employee.first_name), employee.last_name)  employee_contact_name,
				employee.user_name as employee_contact_user_name,
				CONCAT_WS(' ', if(customer.first_name = '', NULL, customer.first_name), customer.last_name)  customer_contact_name,
				customer.assigned_user_id as customer_contact_name_owner,
				customer.phone_work as customer_contact_phone,
				
				CASE
					WHEN `$this->table_name`.is_active = 'no'
						THEN 'grey'
					WHEN `$this->table_name`.date_expire = 0
					   OR `$this->table_name`.date_expire > DATE_ADD(CURDATE(),INTERVAL 30 DAY)
						THEN 'green'
					WHEN `$this->table_name`.date_expire > CURDATE()
						THEN 'yellow'
					ELSE
						'red'
				END as status_colour,

				`".$this->table_name."`.*,
				SUM(assets.purchase_usdollar * assets.quantity) AS total_purchase,
				SUM(assets.unit_support_price * assets.quantity) AS total_support_cost
		";
		if($custom_join)  $query .= $custom_join['select'];
		$query .= " FROM `".$this->table_name."` ";
		if($custom_join)  $query .= $custom_join['join'];
		$query .= "
			LEFT JOIN users employee ON (employee.id = employee_contact_id)
			LEFT JOIN contacts customer ON (customer.id = customer_contact_id)
			LEFT JOIN $this->types_table_name con_type ON (con_type.id = contract_type_id)
			LEFT JOIN service_maincontracts main_contract ON (main_contract.id = main_contract_id)
			LEFT JOIN accounts ON (accounts.id = main_contract.account_id)
			LEFT OUTER JOIN assets ON (`".$this->table_name."`.id = assets.service_subcontract_id)
		";

		$where_auto = "  
			`".$this->table_name."`.deleted != 1
		";

		if($where != "")
			$query .= "WHERE $where AND ".$where_auto;
		else
			$query .= "WHERE ".$where_auto;

		$query .= "GROUP BY `".$this->table_name."`.id";
			
		if($order_by != "")
			$query .= " ORDER BY $order_by";
		else
			$query .= " ORDER BY vendor_contract";
			
		return $query;
		
	}
	
	// fix incompatibility between default implementation and created query
	function create_list_count_query($where) {
		$q = parent::create_list_count_query($where);
		$q = preg_replace ('/select\s+count\(\*\)\s+c/i', "SELECT COUNT(DISTINCT `$this->table_name`.id) c", $q);
        $q = preg_replace("/GROUP\s+BY\s+`$this->table_name`.id\s*$/i", '', $q);
		return $q;
	}
	

	function create_export_query($order_by, $where)
	{
		print "<h1>SubContracts::create_export_query not yet defined.</h1>";
		return;
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
				con_type.name  as contract_type_name,
				employee.first_name AS employee_first_name, employee.last_name AS employee_last_name,
				customer.first_name AS customer_first_name, customer.last_name AS customer_last_name, customer.salutation AS customer_salutation,
				customer.assigned_user_id as customer_contact_name_owner,
				customer.phone_work as customer_contact_phone,
				mcon.contract_no as main_contract_name,
				mcon.created_by as main_contract_name_owner,
				acct.id as account_id,
				acct.name as account_name

			FROM	`".$this->table_name."`

			LEFT JOIN $this->types_table_name con_type ON (con_type.id = contract_type_id)
			LEFT JOIN users employee ON (employee.id = employee_contact_id)
			LEFT JOIN contacts customer ON (customer.id = customer_contact_id)
			LEFT JOIN service_maincontracts mcon ON (mcon.id = main_contract_id)
			LEFT JOIN accounts acct ON (acct.id = mcon.account_id)
			WHERE `".$this->table_name."`.id = '".$this->id."'
		";

		$result = $this->db->query($query, true, "Database error in SubContracts::fill_in_additional_detail_fields()");

			
		if(($row = $this->db->fetchByAssoc($result)) !== false) {
			$this->customer_contact_name = $GLOBALS['locale']->getLocaleFormattedName($row['customer_first_name'], $row['customer_last_name'], $row['customer_salutation']);
			$this->employee_contact_name = $GLOBALS['locale']->getLocaleFormattedName($row['employee_first_name'], $row['employee_last_name'], '');
			foreach($row as $f => $v)
				$this->$f = $v;
		}
		
		$this->fill_in_totals();
		
		$this->created_by_name = get_assigned_user_name($this->created_by);
		$this->modified_user_name = get_assigned_user_name($this->modified_user_id);
		$this->employee_contact_user_name = get_assigned_user_name($this->employee_contact_id);

	}
	
	
	function fill_in_totals() {
		$query = "
				SELECT
					SUM(purchase_usdollar * quantity) AS total_purchase,
					SUM(unit_support_price * quantity) AS total_support_cost
				FROM `assets`
				WHERE service_subcontract_id = '".$this->id."' AND NOT deleted";
		
		$result = $this->db->query($query, true, "Error calculating total purchase value");
		
		global $current_user;
		list($this->total_purchase, $this->total_support_cost) = $this->db->fetchByRow( $result );
		/*if(!isset($current_user->currency_for_subcon)) {
			require_once('modules/Currencies/Currency.php');
			$currency = new Currency();
			$currency->retrieve($current_user->getPreference('currency'));
			$current_user->currency_for_subcon = $currency;
		} else
			$currency = $current_user->currency_for_subcon;
		$this->currency_symbol = $currency->symbol;
		$this->total_purchase = $currency->convertFromDollar($total_purchase);
		$this->total_support_cost = $currency->convertFromDollar($total_support_cost);*/
	}


	function get_list_view_data()
	{
		global $current_language, $current_user, $mod_strings, $app_list_strings;
		$app_strings = return_application_language($current_language);
		$this->fill_in_totals();

		$temp_array = $this->get_list_view_array();
		
		if($temp_array['DATE_BILLED'] == 0)
			$temp_array['DATE_BILLED'] = '';
		
		$temp_array['TOTAL_PURCHASE'] = currency_format_number($this->total_purchase, array('convert' => true, 'currency_symbol' => true));
		$temp_array['TOTAL_SUPPORT_COST'] = currency_format_number($this->total_support_cost, array('convert' => true, 'currency_symbol' => true));
		
		return $temp_array;
	}

	function save($check_notify = FALSE) {
		return parent::save($check_notify);
	}

	function parse_additional_headers(&$list_form, $xTemplateSection) {

	}

	function list_view_parse_additional_sections(&$list_form, $xTemplateSection) {
		return $list_form;
	}

    function listviewACLHelper(){
        global $current_user;
		$array_assign = parent::listviewACLHelper();
		$array_assign['MAIN_CONTRACT'] = $this->getACLTagName('main_contract_name_owner', 'Service');
		$array_assign['CUSTOMER_CONTACT'] = $this->getACLTagName('customer_contact_name_owner', 'Contacts');
		$array_assign['CONTRACT_TYPE'] = is_admin($current_user) ? 'a' : 'span';
		$array_assign['EMPLOYEE_CONTACT'] = is_admin($current_user) ? 'a' : 'span';
		return $array_assign;
	}

    static function convert_amount($value) {
        global $current_user;

        if( !isset($current_user->currency_for_contract)) {
            require_once('modules/Currencies/Currency.php');
            $currency = new Currency();
            $currency->retrieve($current_user->getPreference('currency'));
            $current_user->currency_for_contract = $currency;
        } else {
            $currency = $current_user->currency_for_contract;
        }

        $total_value = stripslashes( $value );
        $converted = $currency->convertFromDollar($total_value);

        return $converted;
    }

    static function calc_total_perchase($spec) {
        $cost = 0;

        if (isset($spec['raw_values']['id'])) {
            global $db;

            $query = "SELECT SUM(purchase_usdollar * quantity) AS total_purchase
                FROM `assets`
                WHERE service_subcontract_id = '".$spec['raw_values']['id']."' AND NOT deleted";

            $result = $db->query($query);
            $data = $db->fetchByAssoc($result);

            if (isset($data['total_purchase']))
                $cost = SubContract::convert_amount($data['total_purchase']);
        }

        $cost = format_number($cost);

        return $cost;
    }

    static function calc_total_support($spec) {
        $cost = 0;

        if (isset($spec['raw_values']['id'])) {
            global $db;

            $query = "SELECT SUM(unit_support_price * quantity) AS total_support_cost
                FROM `assets`
                WHERE service_subcontract_id = '".$spec['raw_values']['id']."' AND NOT deleted";

            $result = $db->query($query);
            $data = $db->fetchByAssoc($result);

            if (isset($data['total_support_cost']))
                $cost = SubContract::convert_amount($data['total_support_cost']);
        }

        $cost = format_number($cost);

        return $cost;
    }

    static function convert_balance($spec) {
        $balance = 0;

        if (! empty($spec['raw_values']['id'])) {
            $subcontract = ListQuery::quick_fetch_row('SubContract', $spec['raw_values']['id'], array('prepaid_balance_usd'));
            if ($subcontract != null)
                $balance = SubContract::convert_amount($subcontract['prepaid_balance_usd']);
        }

        $balance = format_number($balance);

        return $balance;
    }

    static function set_warranty_dates($id) {
        global $db;

        $query = "UPDATE `assets`, `service_subcontracts` SET `assets`.`warranty_start_date` = `service_subcontracts`.`date_start`,
            `assets`.`warranty_expiry_date` = `service_subcontracts`.`date_expire` WHERE
            `service_subcontracts`.`id` = '{$id}' AND `service_subcontracts`.`id` = `assets`.`service_subcontract_id`";

        $db->query($query, true, 'Error setting new warranty dates: ');
    }

    static function update_prepaid_balance($id) {
        global $db;
        if(! $id)
            return;

        $query = "SELECT SUM(CASE transaction_type WHEN 'credit' THEN amount_usdollar ELSE -amount_usdollar END) AS balance_usd
            FROM `prepaid_amounts` ".
            "WHERE subcontract_id = '".$id."' AND NOT deleted";

        $result = $db->query($query);

        if($row = $db->fetchByAssoc($result, -1, false)) {
            $prepaid_balance_usd = $row['balance_usd'] * 1;

            $update_query = "UPDATE `service_subcontracts` SET prepaid_balance_usd = '".$prepaid_balance_usd."'
                WHERE id = '".$id."'";

            $db->query($update_query);
        }
    }

    static function status_subselect($colspec, $table, $for_order=false) {
        return "CASE
            WHEN $table.is_active = 'no' THEN 'grey'
            WHEN $table.date_expire = 0 OR $table.date_expire > DATE_ADD(CURDATE(),INTERVAL 30 DAY)
            THEN 'green' WHEN $table.date_expire > CURDATE() THEN 'yellow'
            ELSE 'red'
            END ";
    }

    static function init_record(RowUpdate &$upd, $input) {
        $update = array('employee_contact_id' => AppConfig::current_user_id());
        if (! empty($input['contract_id'])) {
            $contract = ListQuery::quick_fetch_row('Contract', $input['contract_id'], array('contract_no', 'account_id'));

            if ($contract) {
                $update['main_contract_id'] = $contract['id'];
                $update['account_id'] = $contract['account_id'];
            }
        }
        $upd->set($update);
    }

    static function add_view_popups(DetailManager $mgr) {
        require_bean('Account');
        Account::add_account_popup($mgr->getRecord(), 'main_contract.account_id', 'service');
    }

    static function init_form(DetailManager &$mgr) {
        $contract_id = $mgr->record->getField('main_contract_id');

        if ($contract_id) {
            $contract = ListQuery::quick_fetch_row('Contract', $contract_id, array('account_id'));
            if (isset($contract['account_id'])) {
                global $pageInstance;
                $pageInstance->add_js_literal("init_form('".$mgr->form_gen->form_obj->form_name."', '".$contract['account_id']."');", null, LOAD_PRIORITY_FOOT);
            }
        }
    }

    static function update_balance(RowUpdate $upd) {
        $id = $upd->getPrimaryKeyValue();
        SubContract::update_prepaid_balance($id);
    }
}


?>
