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
require_once('modules/PurchaseOrders/PurchaseOrderLineGroup.php');
require_once('modules/Accounts/Account.php');

class PurchaseOrder extends SugarBean {
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
	var $terms;
	var $prefix;
	var $po_number;
	var $related_invoice_id;
	var $supplier_id;
	var $supplier_contact_id;

	// Used by quick-creation form
	var $account_id;
	
	//
	var $currency_id, $exchange_rate;
	var $shipping_provider_id;
	var $tax_information;
	//
	var $shipping_account_name, $shipping_account_id;
	var $shipping_address_street, $shipping_address_city, $shipping_address_state, $shipping_address_postalcode, $shipping_address_country;

	var $amount, $amount_usdollar;
	var $cancelled;
	var $show_components;
	var $from_so_id;
	var $drop_ship;
	//
	var $allocated; // for subpanel on payments
	
	// Static members
	var $table_name = "purchase_orders";
	var $object_name = "PurchaseOrder";
	var $group_object_name = "PurchaseOrderLineGroup";
	var $module_dir = "PurchaseOrders";
	var $new_schema = true;
	//
	var $account_table = "accounts";
	var $contact_table = "contacts";

	static $inherit_so_fields = array(
		'assigned_user_id',
		'assigned_user_name',
		'name',
		'opportunity_id',
		'opportunity_name',
		'purchase_order_num',
		'currency_id',
		'exchange_rate',
		'shipping_provider_id',
		'description',
		'amount',
		'amount_usdollar',
		'terms',
		'show_components',
		'tax_information',
		'id' => 'from_so_id',
		'shipping_contact_id',
		'shipping_account_id',
		'shipping_address_street',
		'shipping_address_city',
		'shipping_address_state',
		'shipping_address_postalcode',
		'shipping_address_country',
	);
	
	
	var $additional_column_fields = Array(
		'assigned_user_name',
	   	'line_items',
		'account_id',
		'highlight',
		'supplier_name',
		'supplier_contact_name',
		'related_invoice_name',
		'modified_by_name',
		'created_by_name',
		'from_so_name',
		'shipping_account_name'
	);
	
	function PurchaseOrder()
	{
		parent::SugarBean();
	}
	

	function getCurrentPrefix()
	{
		return AppConfig::get_sequence_prefix('purchase_order_prefix');
	}
	

	function getCurrentSequenceValue()
	{
		return AppConfig::current_sequence_value('purchase_order_sequence');
	}
	
	function getNextSequenceValue()
	{
		return AppConfig::next_sequence_value('purchase_order_sequence');
	}

	// obsolete
	function fetch_line_items($reload=false) {
		$this->get_line_groups(true, $reload);
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
		$ret = PurchaseOrderLineGroup::newForParent($this);
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
		
		return $ret;
	}
	
	function save_line_groups() {
		if(isset($this->line_groups) && is_array($this->line_groups)) {
			foreach(array_keys($this->line_groups) as $k)
				$this->line_groups[$k]->save();
			$total = $this->line_groups['GRANDTOTAL']->total;
			$this->amount = $total;
		}
	}

	function get_payments()
	{
		$query = $this->payment_allocations();
		$payments = array();
		$res = $this->db->query($query);
		while ($row = $this->db->fetchByAssoc($res)) {
			$payments[] = $row;
		}
		return $payments;
	}
	
	// subpanel query
	function payment_allocations() {
		$query = sprintf(
		    "SELECT 
			payments.*, CONCAT(payments.prefix, payments.payment_id) AS full_number,
			'%s' as purchase_orders_currency_id,
			invoices_payments.amount AS allocated,
			invoices_payments.amount_usdollar AS allocated_usdollar
		    FROM invoices_payments
		    LEFT JOIN payments
		    ON invoices_payments.payment_id = payments.id
		    WHERE 
			!invoices_payments.deleted 
			    AND 
			!payments.deleted
			    AND
			invoices_payments.invoice_id = '%s'
		    ",
		    PearDatabase::quote($this->currency_id),
		    PearDatabase::quote($this->id)
		);
		return $query;
	}


	function purchase_order_allocations(){
		    $query="SELECT inv.*,CONCAT(inv.prefix,inv.invoice_number) AS full_number from invoice inv LEFT JOIN purchase_orders po ON inv.id=po.related_invoice_id
		    WHERE po.id='".$this->id."'";
		
		return $query;
	}
	
	function get_view_closed_where_advanced($param)
	{
		return '1';
	}

	function get_view_closed_where_basic($param)
	{
		return empty($param['value']) ? " ! purchase_orders.cancelled AND shipping_stage != 'Received'" : '1';
	}
	
	function getDefaultListWhereClause()
	{
		return " ! purchase_orders.cancelled";
	}

	function search_by_number($param) {
		if (empty($param['value'])) {
			return 1;
		} else {
			return "(purchase_orders.po_number = '" . PearDatabase::quote($param['value']) . "' OR CONCAT(purchase_orders.prefix, purchase_orders.po_number) = '" . PearDatabase::quote($param['value']) . "' ) ";
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

    static function drop_ship(DetailManager $mgr) {
		global $pageInstance;
        $pageInstance->add_js_literal("dropShip();", null, LOAD_PRIORITY_FOOT);
    }

    static function init_from_account($account_id) {
           $account = ListQuery::quick_fetch_row('Account', $account_id);
           $data = array();

           if ($account != null) {
               $data['supplier_id'] = $account['id'];
               //$data['shipping_account_id'] = $account['id'];
               $data['shipping_provider_id'] = $account['default_shipper_id'];
               if ($account['tax_code_id'] == EXEMPT_TAXCODE_ID)
                   $data['tax_exempt'] = 1;

               /*'address_street', 'address_city', 'address_state',
               'address_postalcode', 'address_country',*/

               $fields = array(
                   'currency_id', 'exchange_rate',
                   'default_purchase_terms' => 'terms',
                   'tax_information', 'default_purchase_shipper_id' => 'shipping_provider_id',
               );

               foreach($fields as $k => $f) {
                   if(is_int($k)) $k = $f;
                   if (strpos($k, 'address') !== false) {
                       $index = 'shipping_' . $k;
                       $data[$index] = $account[$index];
                   } else {
                       $data[$f] = $account[$k];
                   }
               }
           }

           return $data;
   	}

   	static function init_from_so(TallyUpdate &$self, TallyUpdate $so) {
   		foreach (self::$inherit_so_fields as $key => $value) {
   			if(is_int($key)) $key = $value;
   			$self->set($value, $so->getField($key));
   		}
   		$groups = $so->getGroups(true);
   		foreach ($groups as $g => &$group) {
   			if (isset($group['lines']) && is_array($group['lines'])) {
   				foreach ($group['lines'] as $l => &$line) {
   					if(! empty($line['is_comment']))
   						continue;
   					$line['unit_price'] = $line['cost_price'];
   					$line['unit_price_usd'] = $line['cost_price_usd'];
   					$line['std_unit_price'] = $line['cost_price'];
   					$line['std_unit_price_usd'] = $line['cost_price_usd'];
   					$line['pricing_adjustment_id'] = '';

   					if ($line['related_id'] && ($line['related_type'] == 'ProductCatalog' || $line['related_type'] == 'Assemblies')) {
   						$bean_name = AppConfig::module_primary_bean($line['related_type']);
   						$rec = ListQuery::quick_fetch($bean_name, $line['related_id']);
   						if ($rec) {
   							$pname = $rec->getField('purchase_name');
   							if(! empty($pname))
   								$line['name'] = $pname;
   						}
   					}

   				}
   			}
   			// delete all discounts and line pricing adjustments
   			$adjusts = array();
   			foreach($group['adjusts'] as $idx => $adj) {
   				if($adj['type'] == 'ProductAttributes')
   					$adjusts[$idx] = $adj;
   			}
   			$group['adjusts'] = $adjusts;
   		}
   		$self->set('line_items', $groups);
   		$self->set('tax_information', '');
   	}

    static function init_record(RowUpdate &$upd, $input) {
        $update = array();

        if(! empty($_REQUEST['supplier_id'])) {
            $account_data = self::init_from_account($_REQUEST['supplier_id']);
            $update += $account_data;
        }

        if(!empty($_REQUEST['salesorder_id'])) {
        	$so = ListQuery::quick_fetch('SalesOrder', $_REQUEST['salesorder_id']);
        	$so_upd = RowUpdate::for_result($so);
            self::init_from_so($upd, $so_upd);
        }

        $update['show_components'] = 'all';
        if (empty($update['terms']))
            $update['terms'] = AppConfig::setting('company.bill_default_terms', 'COD');

        $upd->set($update);
    }

    static function after_save(RowUpdate $upd) {
        self::update_account_balance($upd->getField('supplier_id'));
    }
    
	static function update_account_balance($account_id) {
        $account = ListQuery::quick_fetch('Account', $account_id);

        if ($account) {
            $upd = RowUpdate::for_result($account);
            //Call pre_update_balance through account's before_save hook
            $upd->save();
        }
    }
	
	static function send_notification(RowUpdate $upd) {
        $vars = array(
            'PO_SUBJECT' => array('field' => 'name', 'in_subject' => true),
            'PO_DESCRIPTION' => array('field' => 'description'),
            'PO_STATUS' => array('field' => 'shipping_stage'),
        );

        $manager = new NotificationManager($upd, 'PurchaseOrderAssigned', $vars);

        if ($manager->wasRecordReassigned())
            $manager->sendMails();
	}
}
?>
