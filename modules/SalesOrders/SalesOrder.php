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
require_once('modules/SalesOrders/SalesOrderLineGroup.php');
require_once('modules/Accounts/Account.php');

class SalesOrder extends SugarBean {
	// Stored fields
	var $id;
	var $date_entered;
	var $date_modified;
	var $modified_user_id, $modified_by_name;
	var $created_by, $created_by_name;
	var $assigned_user_id, $assigned_user_name;
	//
	var $name;
	var $description;
	var $due_date;	
	var $terms;
	var $prefix;
	var $so_number;
	var $so_stage;
	var $related_quote_id;
	var $opportunity_id, $opportunity_name;

	var $billing_account_name, $billing_account_id;
	var $billing_address_street, $billing_address_city, $billing_address_state, $billing_address_postalcode, $billing_address_country;
	// Used by quick-creation form
	var $account_id;
	//
	var $shipping_account_name, $shipping_account_id;
	var $shipping_address_street, $shipping_address_city, $shipping_address_state, $shipping_address_postalcode, $shipping_address_country;
	//
	var $billing_contact_id, $billing_contact_name;
	var $shipping_contact_id, $shipping_contact_name;
	var $currency_id, $exchange_rate;
	var $shipping_provider_id;
	var $tax_information;

	var $amount, $amount_usdollar;
	var $cancelled;
	var $show_components;
	var $tax_exempt;
	var $discount_before_taxes;

	//
	var $allocated; // for subpanel on payments
	
	// Static members
	var $table_name = "sales_orders";
	var $object_name = "SalesOrder";
	var $group_object_name = "SalesOrderLineGroup";
	var $module_dir = "SalesOrders";
	var $new_schema = true;
	//
	var $account_table = "accounts";
	var $contact_table = "contacts";
	
	var $additional_column_fields = Array(
		'assigned_user_name',
	   	'line_items',
		'account_id',
		'highlight',
		'related_quote_name',
		'modified_by_name',
		'created_by_name',
		'opportunity_name',
		'billing_account_name',
		'shipping_account_name',
		'billing_contact_name',
		'shipping_contact_name',
		'update_quote_id',
	);


	static $inherit_quote_fields = array(
		'assigned_user_id',
		'name',
		'opportunity_id',
		'partner_id',
		'purchase_order_num',
		'billing_account_id',
		'billing_contact_id',
		'billing_address_street',
		'billing_address_city',
		'billing_address_state',
		'billing_address_postalcode',
		'billing_address_country',
		'shipping_account_id',
		'shipping_contact_id',
		'shipping_address_street',
		'shipping_address_city',
		'shipping_address_state',
		'shipping_address_postalcode',
		'shipping_address_country',
		'currency_id',
		'exchange_rate',
		'shipping_provider_id',
		'description',
		'amount',
		'amount_usdollar',
		'terms',
		'show_components',
		'tax_information',
		'tax_exempt',
		'id' => 'from_quote_id',
	);
	

	function SalesOrder()
	{
		parent::SugarBean();
	}
	
	
	static function init_from_account($account_id) {
        $account = ListQuery::quick_fetch_row('Account', $account_id);
        $data = array();

        if ($account != null) {
            $data['billing_account_id'] = $account['id'];
            $data['shipping_account_id'] = $account['id'];
            $data['shipping_provider_id'] = $account['default_shipper_id'];
            if ($account['tax_code_id'] == EXEMPT_TAXCODE_ID)
                $data['tax_exempt'] = 1;

            $fields = array(
                'address_street', 'address_city', 'address_state',
                'address_postalcode', 'address_country',
                'currency_id', 'exchange_rate',
                'default_discount_id',
                'default_terms' => 'terms',
                'tax_information', 'tax_code_id' => 'default_tax_code_id',
            );

            foreach($fields as $k => $f) {
                if(is_int($k)) $k = $f;
                if (strpos($k, 'address') !== false) {
                    $index = 'billing_' . $k;
                    $data[$index] = $account[$index];
                    $index = 'shipping_' . $k;
                    $data[$index] = $account[$index];
                } else {
                    $data[$f] = $account[$k];
                }
            }
        }

        return $data;
	}
	
	
	function getCurrentPrefix()
	{
		return AppConfig::get_sequence_prefix('sales_order_prefix');
	}
	

	function getCurrentSequenceValue()
	{
		return AppConfig::current_sequence_value('sales_order_sequence');
	}
	
	function getNextSequenceValue()
	{
		return AppConfig::next_sequence_value('sales_order_sequence');
	}
	
	
	function &get_line_groups($set_old_items=false, $reload=false) {
		if($reload || ! isset($this->line_groups) || ! is_array($this->line_groups)) {
			$lgm =& $this->get_line_group_manager();
			$encode = empty($this->pdf_output_mode);
			$this->line_groups =& $lgm->retrieve_all($encode);
			if($set_old_items) {
				$items =& $lgm->lineItemsFromGroups($this->line_groups);
				$this->line_items = $items;
			}
			$lgm->cleanup();
		}
		return $this->line_groups;
	}
	
	function cleanup_line_groups(&$groups) {
		$this->cleanup_list($groups);
	}
	
	function &get_line_group_manager() {
		$ret = SalesOrderLineGroup::newForParent($this);
		return $ret;
	}
	
	function line_items_editable() {
		return true;
	}
	

	function save($check_notify = FALSE)
	{
		// need an ID to save line items
		if(empty($this->id)) {
			$this->id = create_guid();
			$this->new_with_id = true;
		}
		
		// must do this before manipulating numeric fields
		$this->unformat_all_fields();
		
		$this->save_line_groups();
		
		$ret = parent::save($check_notify);

		return $ret;
	}
	
	function save_line_groups() {
		if(isset($this->line_groups) && is_array($this->line_groups)) {
			foreach(array_keys($this->line_groups) as $k)
				$this->line_groups[$k]->save();
			$total = $this->line_groups['GRANDTOTAL']->total;
			$this->amount = $this->number_formatting_done ? format_number($total) : sprintf('%0.2f', $total);
		}
	}

	function get_payments($include_refunded = true)
	{
		$query = $this->payment_allocations($include_refunded);
		$payments = array();
		$res = $this->db->query($query);
		while ($row = $this->db->fetchByAssoc($res)) {
			$payments[] = $row;
		}
		return $payments;
	}
	
	// subpanel query
	function payment_allocations($include_refunded) {
		$query = sprintf(
		    "SELECT 
			payments.*, CONCAT(payments.prefix, payments.payment_id) AS full_number,
			'%s' as sales_orders_currency_id,
			invoices_payments.amount AS allocated,
			invoices_payments.amount_usdollar AS allocated_usdollar
		    FROM invoices_payments
		    LEFT JOIN payments
		    ON invoices_payments.payment_id = payments.id
		    WHERE 
			!invoices_payments.deleted 
			    AND 
			!payments.deleted
			%s
			    AND
			invoices_payments.invoice_id = '%s'
		    ",
		    PearDatabase::quote($this->currency_id),
		    $include_refunded ? '' : ' AND payments.refunded = 0',
		    PearDatabase::quote($this->id)
		);
		return $query;
	}

    
	function sales_order_allocations(){
		    $query="SELECT inv.*,CONCAT(inv.prefix,inv.invoice_number) AS full_number from invoice inv LEFT JOIN sales_orders po ON inv.id=po.related_invoice_id
		    WHERE po.id='".$this->id."'";
		
		return $query;
	}
	
	function get_view_closed_where_advanced($param)
	{
		return '1';
	}

	function get_view_closed_where_basic($param)
	{
		return empty($param['value']) ? "substring(sales_orders.so_stage, 1, 7) != 'Closed '" : '1';
	}
	
	function getDefaultListWhereClause()
	{
		return "sales_orders.so_stage != 'Closed - Shipped and Invoiced'";
	}

	function get_assigned_contact_name(&$contact_id, $owner_field = '')
	{
        if (!empty($owner_field)) $this->$owner_field = '';
		$query = "SELECT contact.first_name, contact.last_name,contact.salutation, contact.assigned_user_id
		          FROM $this->contact_table contact
		          WHERE contact.id = '$contact_id' AND NOT contact.deleted
		          LIMIT 1";
		$result = $this->db->query($query);
		$encode = empty($this->pdf_output_mode);
		$row = $this->db->fetchByAssoc($result, -1, $encode);
        if (!empty($owner_field)) $this->$owner_field = $row['assigned_user_id'];
		if ($row) {
			return $GLOBALS['locale']->getLocaleFormattedName($row['first_name'], $row['last_name'], $row['salutation']);
		} else {
			return '';
		}
	}


	function search_by_number($param) {
		if (empty($param['value'])) {
			return 1;
		} else {
			return "(sales_orders.so_number = '" . PearDatabase::quote($param['value']) . "' OR CONCAT(sales_orders.prefix, sales_orders.so_number) = '" . PearDatabase::quote($param['value']) . "' ) ";
		}
	}

	function cleanup() {
		if(isset($this->line_groups)) {
			$this->cleanup_line_groups($this->line_groups);
			unset($this->line_groups);
		}
		parent::cleanup();
	}
	
	// assumes that date_entered is in user format
	function getTaxDate() {
		if (empty($this->date_entered)) {
			return gmdate('Y-m-d');
		}
		global $timedate;
		return $timedate->to_db($this->date_entered);
	}

    static function init_record(RowUpdate &$upd, $input) {
        $update = array();
        
        if (!empty($input['opportunity_id'])) {
            $opportunity = ListQuery::quick_fetch_row('Opportunity', $input['opportunity_id']);
            if ($opportunity != null) {
                $update['opportunity_id'] = $opportunity['id'];
                $update['currency_id'] = $opportunity['currency_id'];
                $update['exchange_rate'] = $opportunity['exchange_rate'];
                $update['partner_id'] = $opportunity['partner_id'];
                $account_id = $opportunity['account_id'];
                if (!empty($input['account_id']))
                    $account_id = $input['account_id'];

                $account = self::init_from_account($account_id);
                $update = $update + $account;
            }
        } elseif(!empty($input['billing_account_id'])) {
            $update = self::init_from_account($input['billing_account_id']);
        }

        $from_quote_id = null;
        if(! empty($_REQUEST['related_quote_id'])) {
            $from_quote_id = $_REQUEST['related_quote_id'];
        }

        if($from_quote_id) {
        	$quote = ListQuery::quick_fetch('Quote', $from_quote_id);
        	$quote_upd = RowUpdate::for_result($quote);
        	self::init_from_quote($upd, $quote_upd);
        }
		
		if (empty($update['terms']))
            $update['terms'] = AppConfig::setting('company.invoice_default_terms', 'COD');

        global $timedate;
        $update['show_components'] = 'all';
        $update['show_list_prices'] = AppConfig::setting('company.quotes_show_list') ? '1' : '0';
        $update['valid_until'] = date('Y-m-d', strtotime('+30 day'));

        if (empty($update['terms'])) {
            $terms = 'COD';
            if ($upd->getField('terms'))
                $terms = $upd->getField('terms');
            $update['terms'] = $terms;
        }
        if (empty($update['due_date']))
            $update['due_date'] = Invoice::calc_due_date($update['terms']);

        $upd->set($update);
    }

	static function init_from_quote(RowUpdate &$self, RowUpdate &$quote) {
		foreach (self::$inherit_quote_fields as $key => $value) {
			if(is_int($key)) $key = $value;
			$val = $quote->getField($key);
			if(isset($val))
				$self->set($value, $val);
		}
        $self->set('so_stage', 'Ordered');

		$groups = $quote->getGroups(true);
		$self->replaceGroups($groups);
		$self->set('related_quote_id', $quote->record_id);
	}
	
    static function before_save(RowUpdate $upd) {
        if($upd->new_record && ! $upd->getField('amount')) {
            $upd->set(array(
                'amount' => 0,
            ));
        }
    }
    
    static function after_save(RowUpdate $upd) {
        if($upd->new_record) {
    		$qt_id = $upd->getField('related_quote_id');
            if(! empty($qt_id) && ($base = ListQuery::quick_fetch('Quote', $qt_id))) {
    			$qt_up = RowUpdate::for_result($base);
    			if($qt_up->getField('quote_stage') != 'Closed Accepted') {
    				$qt_up->set('quote_stage', 'Closed Accepted');
    				$qt_save = true;
    			}
    			if(! $qt_up->getField('sales_order_id')) {
    				$qt_up->set('sales_order_id', $upd->getPrimaryKeyValue());
    				$qt_save = true;
				}
				if(! empty($qt_save))
					$qt_up->save();
    		}
        }
    }
	
	static function send_notification(RowUpdate $upd) {
        $vars = array(
            'SO_SUBJECT' => array('field' => 'name', 'in_subject' => true),
            'SO_DESCRIPTION' => array('field' => 'description'),
            'SO_STATUS' => array('field' => 'so_stage'),
        );

        $manager = new NotificationManager($upd, 'SalesOrderAssigned', $vars);

        if ($manager->wasRecordReassigned())
            $manager->sendMails();
	}


	static function get_new_activity_status(RowUpdate $upd) {
        $status = 'created';

		if ($upd->getField('from_quote_id')) {
			$status = array(
				'status' => 'created',
				'converted_to_type' => 'Quotes',
				'converted_to_id' => $upd->getField('from_quote_id'),
			);
		}
		return $status;
    }

}
?>
