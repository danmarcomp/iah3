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

 * Description:  Defines the Account SugarBean Account entity with the necessary
 * methods and variables.
 ********************************************************************************/




require_once('data/SugarBean.php');
require_once('modules/Contacts/Contact.php');
require_once('modules/Opportunities/Opportunity.php');
require_once('modules/Cases/Case.php');
require_once('modules/Calls/Call.php');
require_once('modules/Notes/Note.php');
require_once('modules/Emails/Email.php');
require_once('modules/Bugs/Bug.php');
require_once('modules/TaxCodes/TaxCode.php');
require_once('modules/Invoice/Invoice.php');

// Account is used to store account information.
class Account extends SugarBean {
	var $field_name_map = array();
	// Stored fields
	var $date_entered;
	var $date_modified;
	var $modified_user_id;
	var $assigned_user_id;
	var $annual_revenue;
	var $billing_address_street;
	var $billing_address_city;
	var $billing_address_state;
	var $billing_address_country;
	var $billing_address_postalcode;

    var $billing_address_street_2;
    var $billing_address_street_3;
    var $billing_address_street_4;
    
	var $description;
	var $email1;
	var $email2;
	var $employees;
	var $id;
	var $industry;
	var $name;
	var $ownership;
	var $parent_id;
	var $phone_alternate;
	var $phone_fax;
	var $phone_office;
	var $rating;
	var $shipping_address_street;
	var $shipping_address_city;
	var $shipping_address_state;
	var $shipping_address_country;
	var $shipping_address_postalcode;
    
    var $shipping_address_street_2;    
    var $shipping_address_street_3;    
    var $shipping_address_street_4;    
    
    var $tax_code_id;    
    
	var $sic_code;
	var $ticker_symbol;
	var $account_type;
	var $website;
	var $custom_fields;

	var $created_by;
	var $created_by_name;
	var $modified_by_name;

	// These are for related fields
	var $opportunity_id;
	var $case_id;
	var $contact_id;
	var $task_id;
	var $note_id;
	var $meeting_id;
	var $call_id;
	var $email_id;
	var $member_id;
	var $parent_name;
	var $assigned_user_name;
	var $account_id = '';
	var $account_name = '';
	var $bug_id ='';
	var $module_dir = 'Accounts';
	

	// longreach - start added - relate to a service contract
	var $main_service_contract_id;
	var $main_service_contract_no;
	//-- for quotes and invoices
	var $is_supplier;
	var $balance;
	var $balance_payable;
	var $balance_updated = false;

	var $credit_limit;
	var $purchase_credit_limit;
	var $credit_limit_usd;
	var $purchase_credit_limit_usd;
	var $default_terms;
	var $default_purchase_terms;
	var $default_discount_id;
	var $default_purchase_discount_id;
	var $default_shipper_id;
	var $default_purchase_shipper_id;
	var $currency_id;
	var $exchange_rate;
	var $tax_information;
	//-- virtual fields
	var $raw_balance;
	var $raw_balance_payable;
	var $default_discount_name;
	var $default_purchase_discount_name;
	var $default_shipper_name;
	var $default_purchase_shipper_name;
	
	var $primary_contact_id;
	var $partner;
	var $partner_id;
	var $temperature;

	var $first_invoice_id;
	var $first_invoice_date;
	var $last_invoice_id;
	var $last_invoice_date;

	var $partner_name;
	var $partner_code;

	var $last_activity_date;
	var $account_popups;
	var $account_popup;
	var $sales_popup;
	var $service_popup;
	// longreach - end added






	var $table_name = "accounts";





	var $object_name = "Account";

	var $new_schema = true;

	// This is used to retrieve related fields from form posts.
	var $additional_column_fields = Array('assigned_user_name', 'assigned_user_id', 'opportunity_id', 'bug_id', 'case_id', 'contact_id', 'task_id', 'note_id', 'meeting_id', 'call_id', 'email_id', 'parent_name', 'member_id', 'tax_code_id',

		'partner_name', 'partner_code', 'first_invoice_id', 'first_invoice_date',
		'last_invoice_id', 'last_invoice_date',
	);
	var $relationship_fields = Array('opportunity_id'=>'opportunities', 'bug_id' => 'bugs', 'case_id'=>'cases', 
									'contact_id'=>'contacts', 'task_id'=>'tasks', 'note_id'=>'notes',
									'meeting_id'=>'meetings', 'call_id'=>'calls', 'email_id'=>'emails','member_id'=>'members',



									);

	function Account() {
        parent::SugarBean();
	}

	function get_summary_text()
	{
		return $this->name;
	}

	function get_contacts() {
		return $this->get_linked_beans('contacts','Contact');
	}

	function clear_account_case_relationship($account_id='', $case_id='')
	{
		if (empty($case_id)) $where = '';
		else $where = " and id = '$case_id'";
		$query = "UPDATE cases SET account_name = '', account_id = '' WHERE account_id = '$account_id' AND deleted = 0 " . $where;
		$this->db->query($query,true,"Error clearing account to case relationship: ");
	}

	// This method is used to provide backward compatibility with old data that was prefixed with http://
	// We now automatically prefix http://
	function remove_redundant_http()
	{
		if(preg_match("~^http://(.*)$~i", $this->website, $m))
		{
			$this->website = $m[1];
		}
	}

	function fill_in_additional_list_fields()
	{
	// Fill in the assigned_user_name
	//	$this->assigned_user_name = get_assigned_user_name($this->assigned_user_id);
		$this->remove_redundant_http();
	}

	function fill_in_additional_detail_fields()
	{
		// Fill in the assigned_user_name
        $this->assigned_user_name = get_assigned_user_name($this->assigned_user_id);

		// longreach - start added
		$this->get_partner();
		$query = "SELECT id AS contract_id, contract_no
			FROM service_maincontracts
			WHERE account_id='$this->id' AND NOT deleted";
		$result = $this->db->query($query, true,"Error filling in additional list fields: ");
		$row = $this->db->fetchByAssoc($result);
		if($row != null)
		{	$this->main_service_contract_id = $row['contract_id'];
			$this->main_service_contract_no = $row['contract_no'];
		}
		else {
			$this->main_service_contract_id = '';
			$this->main_service_contract_no = '';
		}
		// --
		$currency = new Currency();

		$currency->retrieve($this->currency_id);
		$params = array('convert' => true, 'currency_id' => $this->currency_id, 'entered_currency_id' => $this->currency_id, 'exchange_rate' => $this->exchange_rate, 'currency_symbol' => false);
		$this->raw_balance = $this->balance != 0 ? currency_format_number($this->balance, $params) : '';
		$this->raw_balance_payable = $this->balance_payable != 0 ? currency_format_number($this->balance_payable, $params) : '';
		if (!empty($this->partner_id)) $this->partner = 1;

		if (!empty($this->first_invoice_id)) {
			require_once 'modules/Invoice/Invoice.php';
			$invoice = new Invoice;
			if ($invoice->retrieve($this->first_invoice_id) && !$invoice->deleted) {
				list($this->first_invoice_date, $dummy) = explode(' ', $invoice->date_entered, 2);
				$this->first_invoice_owner = $invoice->assigned_user_id;
			}
			$invoice->cleanup();
		}
		if (!empty($this->last_invoice_id)) {
			require_once 'modules/Invoice/Invoice.php';
			$invoice = new Invoice;
			if ($invoice->retrieve($this->last_invoice_id) && !$invoice->deleted) {
				list($this->last_invoice_date, $dummy) = explode(' ', $invoice->date_entered, 2);
				$this->last_invoice_owner = $invoice->assigned_user_id;
			}
			$invoice->cleanup();
		}
		$currency->cleanup(); unset($currency);
		// longreach - end added



		/* longreach - replaced
		$query = "SELECT a1.name from accounts a1, accounts a2 where a1.id = a2.parent_id and a2.id = '$this->id' and a1.deleted=0";
		*/
		$query = "SELECT parent.name, parent.assigned_user_id, ".
			"discount.name discount_name, discount.rate discountrate_perc, shipper.name shipper_name, ".
			"discount2.name purchase_discount_name, discount2.rate purchase_discountrate_perc, shipper2.name purchase_shipper_name ".
			"FROM accounts acc ".
			"LEFT JOIN accounts parent ON parent.id = acc.parent_id AND parent.deleted=0 ".
			"LEFT JOIN discounts discount ON discount.id=acc.default_discount_id AND discount.deleted=0 ".
			"LEFT JOIN discounts discount2 ON discount2.id=acc.default_purchase_discount_id AND discount2.deleted=0 ".
			"LEFT JOIN shipping_providers shipper ON shipper.id=acc.default_shipper_id AND shipper.deleted=0 ".
			"LEFT JOIN shipping_providers shipper2 ON shipper2.id=acc.default_purchase_shipper_id AND shipper2.deleted=0 ".
			"WHERE acc.id = '$this->id'";
		$result = $this->db->query($query,true," Error filling in additional detail fields: ");

		// Get the id and the name.
		$row = $this->db->fetchByAssoc($result);

		if($row != null)
		{
			$this->parent_name = $row['name'];
			// longreach - added
			$this->parent_owner = $row['assigned_user_id'];
			$this->default_discount_name = $row['discount_name'];
			if(!empty($this->default_discount_name))
				$this->default_discount_name .= ' ('.str_replace('.00', '', number_format($row['discountrate_perc'], 2)).'%)';
			$this->default_purchase_discount_name = $row['purchase_discount_name'];
			if(!empty($this->default_purchase_discount_name))
				$this->default_purchase_discount_name .= ' ('.str_replace('.00', '', number_format($row['purchase_discountrate_perc'], 2)).'%)';
			$this->default_shipper_name = $row['shipper_name'];
			$this->default_purchase_shipper_name = $row['purchase_shipper_name'];
		}
		else
		{
			$this->parent_name = '';
			// longreach - added
			$this->parent_owner = '';
			$this->default_discount_name = '';
			$this->default_purchase_discount_name = '';
			$this->default_shipper_name = '';
			$this->default_purchase_shipper_name = '';
		}

		$this->created_by_name = get_assigned_user_name($this->created_by);
		$this->modified_by_name = get_assigned_user_name($this->modified_user_id);

		$this->remove_redundant_http();
	}
	function get_list_view_data(){
		// longreach - added
        require_once 'modules/Currencies/Currency.php';
		$temp_array = $this->get_list_view_array();
		$temp_array["ENCODED_NAME"]=$this->name;
//		$temp_array["ENCODED_NAME"]=htmlspecialchars($this->name, ENT_QUOTES);
		if(!empty($this->billing_address_state))
		{
			$temp_array["CITY"] = $this->billing_address_city . ', '. $this->billing_address_state;
		}
		else
		{
			$temp_array["CITY"] = $this->billing_address_city;
		}
		// longreach - start added

		$phone_fields = array(
			'phone_office',
			'phone_alternate',
			'phone_fax'
		);

		foreach ($phone_fields as $f) {
			if (isset($this->$f)) {
				$temp_array['RAW_'.strtoupper($f)] = $this->$f;
				$temp_array[strtoupper($f)] = make_skype_link($this->$f, 'listViewTdLinkS1');
			}
		}
		$temp_array['RAW_BALANCE'] = $this->balance;
		$cparams = array('convert' => true, 'currency_symbol' => true, 'entered_currency_id' => $this->currency_id, 'exchange_rate' => $this->exchange_rate);
        $balance = $temp_array['BALANCE'] != 0 ? currency_format_number($temp_array['BALANCE'], $cparams) : '';
        $now = gmdate('Y-m-d');
		$due = isset($this->invoice_due_date) ? $this->invoice_due_date : '';
		if($due < $now || ($this->credit_limit_usd && $this->credit_limit_usd < $this->balance)) {
            $temp_array['BALANCE'] = '<span class="overdueTask">' . $balance . '</span>';
        }
        else if ($due == $now) {
            $temp_array['BALANCE'] = '<span class="todaysTask">' . $balance . '</span>';
        } else {
            $temp_array['BALANCE'] = $balance ;
		}

		$temp_array['RAW_BALANCE_PAYABLE'] = $this->balance_payable;
        $balance_payable = $temp_array['BALANCE_PAYABLE'] > 0 ? currency_format_number($temp_array['BALANCE_PAYABLE'], $cparams) : '';
		if($this->purchase_credit_limit_usd && $this->purchase_credit_limit_usd < $this->balance_payable) {
            $temp_array['BALANCE_PAYABLE'] = '<span class="overdueTask">' . $balance_payable . '</span>';
        }
		else {
            $temp_array['BALANCE_PAYABLE'] = $balance_payable;
		}
		
		$temp_array['RAW_CREDIT_LIMIT'] = $this->credit_limit;
		$temp_array['CREDIT_LIMIT'] = $this->credit_limit_usd > 0 ? currency_format_number($this->credit_limit_usd, $cparams) : '';
		$temp_array['RAW_PURCHASE_CREDIT_LIMIT'] = $this->purchase_credit_limit;
		$temp_array['PURCHASE_CREDIT_LIMIT'] = $this->purchase_credit_limit_usd > 0 ? currency_format_number($this->purchase_credit_limit_usd, array('convert' => true, 'currency_symbol' => true)) : '';
		
		// longreach - end added
		return $temp_array;
	}

	function create_export_query(&$order_by, &$where)
        {
        	$custom_join = $this->custom_fields->getJOIN();
			$query = "SELECT
					accounts.*,
                    users.user_name as assigned_user_name ";



                     if($custom_join){
						$query .=  $custom_join['select'];
					}
                    $query .= "FROM accounts ";




			if($custom_join){
					$query .=  $custom_join['join'];
				}
            $query .= " LEFT JOIN users
                    	ON accounts.assigned_user_id=users.id ";




            $where_auto = " accounts.deleted=0 ";

            if($where != "")
                    $query .= "where ($where) AND ".$where_auto;
            else
                    $query .= "where ".$where_auto;

           if(!empty($order_by)){
            	//check to see if order by variable already has table name by looking for dot "."
            	$table_defined_already = strpos($order_by, ".");

            	if($table_defined_already === false){
            		//table not defined yet, define accounts to avoid "ambigous column" SQL error 
            		$query .= " ORDER BY $order_by";
            	}else{
            		//table already defined, just add it to end of query
            	    $query .= " ORDER BY $order_by";	
            	}
                    
            }

            return $query;
        }

		// longreach - start added
    	function create_list_count_query($q) {
	    	$q = parent::create_list_count_query($q);
		    $q = preg_replace("/GROUP BY.*$/", "", $q);
    		$q = preg_replace("/count\(\*\)\s+c/", "COUNT(DISTINCT accounts.id) as c", $q);
	    	return $q;
        }
		// longreach - end added

        function create_list_query($order_by, $where, $show_deleted= 0)
        {

			$custom_join = $this->custom_fields ? $this->custom_fields->getJOIN() : '';

                $query = "SELECT ";

                $query .= "
                    users.user_name assigned_user_name,
					/* longreach - added  */
                    MIN(IF(invoice.amount_due>0, invoice.due_date, '9999-12-31')) AS invoice_due_date,
                    MIN(IF(bills.amount_due>0, bills.due_date, '9999-12-31')) AS bills_due_date,
                    accounts.*";
                 if($custom_join){
					$query .=  $custom_join['select'];
				}



		// longreach - start added
		$query .= ", contracts.id as main_service_contract_id, 
			contracts.contract_no as main_service_contract_no";
		// longreach - end added



             $query .= " FROM  accounts ";
			


		// longreach - start added
		$query .= "LEFT JOIN service_maincontracts contracts 
			ON (accounts.id=contracts.account_id AND NOT contracts.deleted) ";
		// longreach - end added
		


			 $query .= "LEFT JOIN users
                    	ON accounts.assigned_user_id=users.id ";

			// longreach - start added
             $query .= "LEFT JOIN invoice ON invoice.billing_account_id=accounts.id ";
             $query .= "LEFT JOIN bills ON bills.supplier_id=accounts.id ";
			$query .= "LEFT JOIN contacts primary_contact ON accounts.primary_contact_id = primary_contact.id ";
			 // longreach - end added
			
             if($custom_join){
					$query .=  $custom_join['join'];
				}



     		$where_auto = '1=1';
			if($show_deleted == 0){
            	$where_auto = " accounts.deleted=0 ";
			}else if($show_deleted == 1){
				$where_auto = " accounts.deleted=1 ";	
			}

            if($where != "")
                    $query .= "where ($where) AND ".$where_auto;
            else
                    $query .= "where ".$where_auto;
            
			// longreach - added
            $query .= " GROUP BY accounts.id";

        if($order_by != "")
			$query .= " ORDER BY $order_by";
		else
			$query .= " ORDER BY $this->table_name.name";
                    return $query;
        }

	function set_notification_body($xtpl, $account)
	{
		$xtpl->assign("ACCOUNT_NAME", $account->name);
		$xtpl->assign("ACCOUNT_TYPE", $account->account_type);
		$xtpl->assign("ACCOUNT_DESCRIPTION", $account->description);

		return $xtpl;
	}
	
	function bean_implements($interface){
		switch($interface){
			case 'ACL':return true;
		}
		return false;
	}

	// longreach - start added
    function update_balance($do_save=true, $receivable = true, $payable = true)
	{
		if ($receivable) {
			$query = 'SELECT IFNULL(SUM(amount_due_usdollar), 0) AS balance FROM invoice WHERE deleted = 0 and !cancelled AND billing_account_id = \'' . $this->id . '\'';
			$res = $this->db->query($query, true);
			$row = $this->db->fetchByAssoc($res);
			$this->balance = $row['balance'];
		}
        
		if ($payable) {
			$query = 'SELECT IFNULL(SUM(amount_due_usdollar), 0) AS balance FROM bills WHERE deleted = 0 and !cancelled AND amount_due > 0 AND supplier_id =\'' . $this->id . '\'';
			$res = $this->db->query($query, true);
			$row = $this->db->fetchByAssoc($res);
			$this->balance_payable = $row['balance'];
		}
        
		$this->balance_updated = true;
		if($do_save) {
			$this->save();
		}
    }

	function save($check_notify=false) {
		$this->unformat_all_fields();
		$currency = new Currency();
		$currency->retrieve($this->currency_id);
		$rate_updated = adjust_exchange_rate($this, $currency);
		$this->credit_limit_usd = $currency->convertToDollar($this->credit_limit);
		$this->purchase_credit_limit_usd = $currency->convertToDollar($this->purchase_credit_limit);
		if (!$this->balance_updated) {
			$this->update_balance(false);
		}
		$ret = parent::save($check_notify);
		if (!empty($this->primary_contact_id)) {
			$this->db->query("UPDATE contacts SET primary_contact_for = '{$this->id}' WHERE id = '{$this->primary_contact_id}'", true);
		}
		$currency->cleanup();
		return $ret;
	}

    function listviewACLHelper(){
		$array_assign = parent::listviewACLHelper();
		$array_assign['PARENT'] = $this->getACLTagName('parent_owner', 'Accounts');
		$array_assign['FIRST_INVOICE'] = $this->getACLTagName('first_invoice_owner', 'Invoice');
		$array_assign['LAST_INVOICE'] = $this->getACLTagName('last_invoice_owner', 'Invoice');
		return $array_assign;
	}


	function get_nonzero_where($parms)
	{
		return 'accounts.balance != 0 ';
	}

	function createForPrimaryContact($contact)
	{
		$map = Account::getB2CContactFieldsMap();
		foreach ($map as $cfield => $afield) {
			if (isset($contact->$cfield)) {
				$this->$afield = $contact->$cfield;
			}
		}
		$this->primary_contact_id = $contact->id;
		$this->assigned_user_id = $contact->assigned_user_id;
	}

	/* static */ function getB2CContactFieldsMap()
	{
		return array(
			'phone_work' => 'phone_office',
			'phone_fax' => 'phone_fax',
			'phone_mobile' => 'phone_mobile',
			'phone_other' => 'phone_alternate',
			'email1' => 'email1',
			'email2' => 'email2',
			'primary_address_street' => 'billing_address_street',
			'primary_address_country' => 'billing_address_country',
			'primary_address_city' => 'billing_address_city',
			'primary_address_state' => 'billing_address_state',
			'primary_address_postalcode' => 'billing_address_postalcode',
			'alt_address_street' => 'shipping_address_street',
			'alt_address_country' => 'shipping_address_country',
			'alt_address_city' => 'shipping_address_city',
			'alt_address_state' => 'shipping_address_state',
			'alt_address_postalcode' => 'shipping_address_postalcode',
			'description' => 'description',
			'email_opt_out' => 'email_opt_out',
			'invalid_email' => 'invalid_email',
			'temperature' => 'temperature',
			'assigned_user_id' => 'assigned_user_id',
		);
	}

	function get_partner()
	{
		$this->partner_name = '';
		if(isset($this->partner_id) && !empty($this->partner_id)){
			$query = "SELECT name, code FROM partners WHERE id='{$this->partner_id}'";
			$result = $this->db->limitQuery($query,0,1,true, "Want only a single row");
			
			if(!empty($result)){
				$row = $this->db->fetchByAssoc($result);
				$this->partner_name = $row['name'];
				$this->partner_code = $row['code'];
			}
		}
	}

	function update_primary_contact($update_date_modified = false)
	{
		if (! AppConfig::is_B2C()) return;
		$contact = new Contact;
		if (!empty($this->primary_contact_id) && $contact->retrieve($this->primary_contact_id)) {
			$map = Account::getB2CContactFieldsMap();
			foreach ($map as $cfield => $afield) {
				if (isset($this->$afield)) {
					$contact->$cfield = $this->$afield;
				}
			}
			$contact->update_date_modified = $update_date_modified;
			$contact->save(false);
		}
	}

	function events_attendance()
	{
		$query = "SELECT event_sessions.*, events_customers.registered, events_customers.attended FROM event_sessions LEFT JOIN events_customers ON event_sessions.id = events_customers.session_id AND events_customers.deleted = 0 WHERE event_sessions.deleted = 0 AND events_customers.customer_id='{$this->id}'";
		return $query;
	}

	function mark_deleted($id)
	{
		if (AppConfig::is_B2C()) {
			$acc = new Account;
			$acc->retrieve($id);
			if (!empty($acc->primary_contact_id)) {
				$contact = new Contact;
				$contact->mark_deleted($acc->primary_contact_id);
			}
		}
		return parent::mark_deleted($id);
	}

    /**
	* getAccountingContacts;	This function retrieves a list of authorized accounting contacts for this account
	*
	* @access public
	* @return array
	*/
	function getAccountingContacts() {
        // Initialize the return array
        $arContacts = array();
		// Generate the query
        $strQuery = "
        	SELECT
        		C.id
        	FROM
        		contacts C
    		LEFT JOIN
    			accounts_contacts L
    		ON
				L.contact_id = C.id
			WHERE
         		C.email1 != ''
         	AND
         		C.email1 IS NOT NULL
            AND
         		C.email_accounts = '1'
         	AND
         		L.account_id = '{$this->id}'
        ";
		// Execute the query
        if ($hResult = $this->db->query($strQuery, true)) {
        	// Loop through the results
        	while ($arRow = $this->db->fetchByAssoc($hResult)) {
            	// Append this id to the return array
            	$arContacts[] = $arRow['id'];
			}
		}
		// Return the generated array
        return (count($arContacts) > 0) ? $arContacts : false;
	}

    /**
	* getUnpaidInvoiceIds;	This function retrieves a list of unpaid invoices for this account
	*
	* @access public
	* @return array
	*/
	function getUnpaidInvoiceIds() {

        // Initialize the return array
        $arInvoices = array();
		// Generate the query
        $strQuery = "
        	SELECT
        		id
        	FROM
        		invoice
    		WHERE
         		billing_account_id = '{$this->id}'
         	AND
         		amount_due > 0
            AND
				deleted = 0
        ";
        // Execute the query
        if ($hResult = $this->db->query($strQuery, true)) {
        	// Loop through the results
        	while ($arRow = $this->db->fetchByAssoc($hResult)) {
            	// Append this id to the return array
            	$arInvoices[] = $arRow['id'];
			}
		}
		// Return the generated array
        return (count($arInvoices) > 0) ? $arInvoices : false;
	}
	
    /**
	* generateJobInvoices;	This function generates
	* @access public
	* @return array
	*/
	function generateJobInvoices() {
		// Get access to required globals
		require_once 'modules/Invoice/Invoice.php';
		global $current_user;
		// Generate the query
        $strQuery = "
        	SELECT
        		B.id,
        		B.name,
        		B.quantity,
        		B.date_start,
        		B.paid_rate,
        		B.paid_rate_usd,
        		B.paid_currency_id,
        		B.billing_rate,
        		B.billing_rate_usd,
				B.billing_currency_id,
				B.billing_exchange_rate,
				B.account_id,
				B.related_id,
				B.related_type,
				B.tax_code_id,
				C.name as case_name,
				C.case_number,
				T.name as task_name
        	FROM
        		booked_hours B
			LEFT OUTER JOIN
				cases C
			ON
			(
				C.id = B.related_id  AND  B.related_type = 'Cases'
			)
			LEFT OUTER JOIN
				project_task T
			ON
			(
				T.id = B.related_id  AND  B.related_type = 'ProjectTask'
			)
    		WHERE
    			B.status = 'approved'
            AND
              B.account_id IS NOT NULL
    		AND
    			IFNULL(B.invoice_id,'') = ''
    		AND
    			B.booking_class = 'billable-work'
    		AND
    			IFNULL(B.related_id,'') != ''
    		AND
    			NOT B.deleted
    		ORDER BY
    			B.account_id
        ";

        // Initialize the invoice array
        $arInvoices = array();
        // Execute the query
        if ($hResult = $this->db->query($strQuery, true)) {
        	// Loop through the results
        	while ($arRow = $this->db->fetchByAssoc($hResult, -1, false)) {
            	// Is this a new account id?
            	if (empty($arInvoices[$arRow['account_id']])) {
            		// Initialize the account
            		$arInvoices[$arRow['account_id']] = array();
            	}
            	// Set the group name
            	$strGroupName = (($arRow['related_type'] == 'Cases')
            		? "Case #{$arRow['case_number']} - {$arRow['case_name']}"
            		: "Project Task - {$arRow['task_name']}");
            	// Is this a new case / task id?
            	if (empty($arInvoices[$arRow['account_id']][$strGroupName])) {
            		// Initialize the account
            		$arInvoices[$arRow['account_id']][$strGroupName] = array();
            	}
            	// Add this line item to the invoice
            	$arInvoices[$arRow['account_id']][$strGroupName][] = $arRow;
			}
		}

		// Initialize the invoice array
		$arInvoiceIds = $arAccountIds = array();
		// Got the indexed list of invoices - Loop through and create the invoices
		foreach ($arInvoices as $strAccountId => $arGroup) {
            $objInvoice = RowUpdate::blank_for_model('Invoice');
            $data = Invoice::init_from_account($strAccountId);
            if (! empty($data)) {
                $data['name'] .= ' - Outstanding Hours';
                $data['description'] = $data['name'];
                $data['assigned_user_id'] = AppConfig::current_user_id();
                if (empty($data['terms']))
                    $data['terms'] = 'COD';
                $data['due_date'] = Invoice::calc_due_date($data['terms']);
                $data['invoice_date'] = Invoice::calc_due_date();
                $data['amount'] = 0;

                $objInvoice->set($data);
                $new_groups = array();

                $currency = new Currency();
                $currency->retrieve($objInvoice->getField('currency_id'));
                if($objInvoice->getField('exchange_rate'))
                    $currency->conversion_rate = $objInvoice->getField('exchange_rate');

                // Loop through the products and calculate the amounts
                $grp_idx = 0;
                foreach ($arGroup as $strGroupName => $arLines) {
                    $grp_id = 'temp~'.($grp_idx ++);
                    $new_groups[$grp_id] = array(
                        'id' => $grp_id,
                        'lines' => array(),
                        'lines_order' => array(),
                        'adjusts' => array(),
                        'adjusts_order' => array(),
                        'group_type' => 'service',
                        'name' => substr($strGroupName, 0, 50)
                    );

                    // Loop through the invoice lines
                    foreach ($arLines as $arRow) {
                        if($arRow['billing_currency_id'] == $objInvoice->getField('currency_id'))
                            $list_price = $arRow['billing_rate'];
                        else
                            $list_price = $currency->convertFromDollar($arRow['billing_rate_usd']);
                        if($arRow['paid_currency_id'] == $objInvoice->getField('currency_id'))
                            $cost_price = $arRow['paid_rate'];
                        else
                            $cost_price = $currency->convertFromDollar($arRow['paid_rate_usd']);
                        if (! $list_price)
                            $list_price = 0;
                        if (! $cost_price)
                            $cost_price = 0;
                        $line = array(
                            'id' => 'temp~' . rand(1000, 2000),
                            'name' => mb_substr($arRow['name'], 0, 50),
                            'related_id' => $arRow['id'],
                            'related_type' => 'Booking',
                            'quantity' => $arRow['quantity'] / 60,
                            'tax_class_id' => $arRow['tax_code_id'],
                            'list_price' => $list_price,
                            'unit_price' => $list_price,
                            'cost_price' => $cost_price,
                        );
                        $new_groups[$grp_id]['lines'][$line['id']] = $line;
                        $new_groups[$grp_id]['lines_order'][] = $line['id'];
                        // Append this row to the booking ids
                        $arBookingIds[] = $arRow['id'];
                    }
                }

                $objInvoice->replaceGroups($new_groups);

                // Save the invoice
                $strInvoiceId = null;
                if ($objInvoice->save()) {
                    $strInvoiceId = $objInvoice->getPrimaryKeyValue();
                }
                $currency->cleanup();

                // Set the invoice id of the booked hours records
                if ($strInvoiceId && ! empty($arBookingIds)) {
                    $strQuery = "
                        UPDATE
                            booked_hours
                        SET
                            invoice_id = '{$strInvoiceId}'
                        WHERE
                            id IN ('" . implode("','", $arBookingIds) . "')
                    ";
                    $this->db->query($strQuery);
                    $arInvoiceIds[$strAccountId] = array($strInvoiceId);
                }

                // Add to the account list
                $arAccountIds[] = $strAccountId;
            }
		}

		// Store the given invoice ids
		$_SESSION['send_invoice_ids'] = $arInvoiceIds;
		// Store the given account ids
		$_SESSION['send_account_ids'] = array_unique($arAccountIds);
	}

	function setDataForAddressCopy($request, $tpl)
	{
		if (empty($request['account_id'])) return;
		
		$acc = new Account;
		if(! $acc->retrieve($request['account_id']))
			return;
		$tpl->assign('ACCOUNT_ID', $acc->id);
		$tpl->assign('ACCOUNT_NAME', $acc->name);

		$fields = array(
			'billing_address_street',
			'billing_address_country',
			'billing_address_city',
			'billing_address_state',
			'billing_address_postalcode',
			'shipping_address_street',
			'shipping_address_country',
			'shipping_address_city',
			'shipping_address_state',
			'shipping_address_postalcode',
		);
		foreach ($fields as $field) {
			$tpl->assign(strtoupper($field), $acc->$field);
		}

		$acc->cleanup();
	}

    static function get_duplicates_where(RowUpdate &$upd) {
        $clauses = array('address' => array('operator' => 'or', 'multiple' => array()));
        $fields = array('name', 'billing_address_city', 'shipping_address_city');

        for ($i = 0; $i < sizeof($fields); $i++) {
            if ($upd->getField($fields[$i])) {

                $clause = array(
                    'value' => $upd->getField($fields[$i]),
                    'field' => $fields[$i],
                    'operator' => 'like',
                    'match' => 'prefix'
                );

                if (strpos($fields[$i], 'address') !== false) {
                    $clauses['address']['multiple'][] = $clause;
                } else {
                    $clauses[$fields[$i]] = $clause;
                }
            }
        }

        if (sizeof($clauses['address']['multiple']) > 0 && sizeof($clauses['address']['multiple']) < 2) {
            $clauses['address'] = $clauses['address']['multiple'][0];
        }

        return $clauses;
    }

    static function find_duplicates(RowUpdate &$upd, $redirect = true) {
        require_once('include/layout/DuplicateManager.php');
        $manager = new DuplicateManager($upd, $_REQUEST);
        return $manager->check(self::get_duplicates_where($upd), $redirect);
    }

    /**
     * Add account popup to page
     *
     * @static
     * @param RowResult $record
     * @param string $account_field - name of account ID field
     * @param string $type - popup type: sales or service
     */
    static function add_account_popup(RowResult $record, $account_field, $type) {
        $acc_id = $record->getField($account_field);
        if($acc_id) {
            $acc_info = ListQuery::quick_fetch_row('Account', $acc_id, array('id', 'account_popups', $type . '_popup', 'name'));
            if($acc_info && $acc_info['account_popups'] && $acc_info[$type . '_popup']) {
                self::add_popup_to_page($acc_info[$type . '_popup'], $acc_info['name']);
            }
        }
    }

    static function add_view_popups(DetailManager $mgr) {
		$rec = $mgr->getRecord();
		if($rec->getField('account_popups')) {
			$popup = $rec->getField('account_popup');
			if($popup)
                self::add_popup_to_page($popup, $rec->getField('name'));
		}
    }

    static function add_popup_to_page($message, $account_name) {
        global $pageInstance;
        $popup = addcslashes(nl2br(to_html($message)), "\r\n\\\"");
        $name = addcslashes(nl2br(to_html($account_name)), "\r\n\\\"");
        $close = $GLOBALS['app_strings']['LBL_ADDITIONAL_DETAILS_CLOSE'];
        $pageInstance->add_js_literal(
            "YAHOO.util.Event.onDOMReady(function() {
						show_popup_message(\"$popup\", {timeout:12000, title:\"$name\", close_button:'$close'});
					});", null, LOAD_PRIORITY_FOOT);
    }

	static function pre_update_balance(RowUpdate &$upd) {
		global $db;
		$id = $upd->getField('id');
		$supplier = $upd->getField('is_supplier');
		$exr = $upd->getField('exchange_rate');
		$vals = array('balance' => 0, 'balance_payable' => 0);
		if($id && ! $upd->new_record) {
			$id = $db->quote($id);
			if($supplier)
				$query = "
					SELECT IFNULL(SUM(amount_due_usdollar), 0) AS balance_payable
					FROM bills WHERE deleted = 0 and !cancelled AND amount_due > 0 AND supplier_id='$id'
				";
			else
				$query = "
					SELECT SUM(IFNULL(b, 0)) AS balance
					FROM (
						(
							SELECT SUM(IFNULL(amount_due_usdollar, 0)) AS b
							FROM invoice WHERE deleted = 0 and !cancelled AND billing_account_id='$id'
						)
						UNION
						(
							SELECT -SUM(IFNULL(amount_due_usdollar, 0)) AS b
							FROM credit_notes WHERE deleted = 0 and !cancelled AND billing_account_id='$id' AND IFNULL(apply_credit_note, 0) = 0
						)
					) AS balance_stmt
				";

			$r = $db->fetchSQL($query);
			if($r) {
				foreach($r[0] as $k => $v)
					$vals[$k] = $v;
			}
			// FIXME: need to set raw_balance, raw_balance_payable
		}
		$upd->set($vals);
	}

    static function copy_address(RowUpdate &$upd) {
        $copy_billing_address = array_get_default($_REQUEST, 'copy_billing_address');
        $copy_shipping_address = array_get_default($_REQUEST, 'copy_shipping_address');

        if (! empty($copy_billing_address) || ! empty($copy_shipping_address)) {
            if ( ($copy_billing_address == $copy_shipping_address) || $copy_billing_address == 'both')
                $copy_shipping_address = '';
            if ($copy_shipping_address == 'both')
                $copy_billing_address = '';

            if (! empty($copy_billing_address))
                self::copy_address_to_rel_contacts($upd, 'billing_address_', $copy_billing_address);

            if (! empty($copy_shipping_address))
                self::copy_address_to_rel_contacts($upd, 'shipping_address_', $copy_shipping_address);

        }
    }

    static function copy_address_to_rel_contacts(RowUpdate $account_update, $from, $to) {
        $query = new ListQuery('Account', null, array('link_name' => 'contacts'));
        $query->setParentKey($account_update->getPrimaryKeyValue());
        $result = $query->fetchAll();

        if (! $result->failed) {
            $fields = array(
                'street',
                'city',
                'state',
                'postalcode',
                'country',
            );

            foreach ($result->rows as $contact_id => $contact_details) {
                $contact_result = ListQuery::quick_fetch('Contact', $contact_id);
                if ($contact_result) {
                    $update = array();

                    for ($i = 0; $i < sizeof($fields); $i++) {
                        $val = $account_update->getField($from . $fields[$i]);
                        if ($to == 'both') {
                            $update['primary_address_' . $fields[$i]] = $val;
                            $update['alt_address_' . $fields[$i]] = $val;
                        } else {
                            $update[$to . '_address_' . $fields[$i]] = $val;
                        }
                    }

                    $contact_update = RowUpdate::for_result($contact_result);
                    $contact_update->set($update);
                    $contact_update->save();
                }
            }
        }
    }

    static function init_record(RowUpdate &$upd, $input) {
        $update = array();

        $fields = array('website', 'name', 'phone_office', 'email1');
        $field = '';

        for ($i = 0; $i < sizeof($fields); $i++) {
            $field = $fields[$i];
            if (!empty($input[$field])) {
                $update[$field] = urldecode($input[$field]);
            }                
        }

        $upd->set($update);
    }

    static function send_notification(RowUpdate $upd) {
        $vars = array(
            'ACCOUNT_NAME' => array('field' => 'name', 'in_subject' => true),
            'ACCOUNT_TYPE' => array('field' => 'account_type'),
            'ACCOUNT_DESCRIPTION' => array('field' => 'description')
        );

        $manager = new NotificationManager($upd, 'AccountAssigned', $vars);

        if ($manager->wasRecordReassigned())
            $manager->sendMails();
	}

	static function add_massupdate_fields(&$fields) {
		$out = array();
		foreach ($fields as $f => $def) {
			$out[$f] = $def;
			if ($f == 'assigned_user') {
				$out['reassign_objects'] = array(
					'vname' =>  'LBL_REASSIGN_OBJECTS',
					'type' => 'multienum',
					'options' => 'reassign_accounts_dom',
					'multi_select_group' => 'reassign_objects_group',
					'multi_select_count' => 10,
					'source' => array('type' => 'non-db'),
                    'massupdate' => true
				);
			}
		}
		$fields = $out;
	}
	
	static function listupdate_perform($mu, $perform, &$listFmt, &$list_result, $uids) {
		if ($perform == 'update') {
			require_once 'include/Reassign/ObjectsReassign.php';
			$objects = array();
            $userId = null;

            if (isset($_REQUEST['assigned_user_id']))
                $userId = $_REQUEST['assigned_user_id'];
            if (isset($_REQUEST['reassign_objects']))
			    $objects = $_REQUEST['reassign_objects'];

            if (empty($userId) || empty($objects)) return;
            
			$objects = explode('^,^', $objects);
			if ($uids == 'all') $uids = null;

			ObjectsReassign::reassign('Account', $uids, $userId, $objects);
		} else if ($perform == 'SendPDFStatements') {
			$ids = array();
			while(! $list_result->failed) {
				foreach($list_result->getRowIndexes() as $idx) {
					$row = $list_result->getRowResult($idx);
					$ids[] = $module_id = $row->getField('id');
				}
				if($list_result->page_finished)
					break;
				$listFmt->pageResult($list_result, true);
			}
			$_REQUEST['list_uids'] = join(';', $ids);
			
			return array(
				'perform', 
				array(
					'module' => 'Accounts',
					'action' => 'SendPDFStatements',
				),
			);
		}
	}

    static function set_contact_primary_acc(RowUpdate $upd, $link_name) {
        if ($link_name == 'contacts') {
            $contact_id = array_get_default($upd->link_update->saved, 'contact_id');
            $result = ListQuery::quick_fetch('Contact', $contact_id);

            if($result && ! $result->getField('primary_account_id')) {
                $contact_upd = RowUpdate::for_result($result);
                $contact_upd->set('primary_account_id', $upd->getPrimaryKeyValue());
                $contact_upd->save();
            }
        }
    }
}
?>
