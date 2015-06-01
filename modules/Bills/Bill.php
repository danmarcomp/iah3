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
require_once('modules/Bills/BillFormBase.php');
require_once('modules/Bills/BillLineGroup.php');
require_once('modules/Accounts/Account.php');

class Bill extends SugarBean {
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
	var $bill_date;	
	var $terms;
	var $prefix;
	var $bill_number;
	var $related_purchase_order_id;
	var $supplier_id;
	var $supplier_contact_id;

	// Used by quick-creation form
	var $account_id;
	
	//
	var $currency_id, $exchange_rate;
	var $shipping_provider_id;
	var $tax_information;

	var $amount, $amount_usdollar;
	var $amount_due;
	var $amount_due_usdollar;
	var $cancelled;
	var $show_components;

	//
	var $allocated; // for subpanel on payments
	
	// Static members
	var $table_name = "bills";
	var $object_name = "Bill";
	var $group_object_name = "BillLineGroup";
	var $module_dir = "Bills";
	var $new_schema = true;
	//
	var $account_table = "accounts";
	var $contact_table = "contacts";
	
	var $additional_column_fields = Array(
		'assigned_user_name',
	   	'line_items',
		'account_id',
		'highlight',
		'supplier_name',
		'supplier_contact_name',
		'related_purchase_order_name',
		'related_purchase_order_number',
		'modified_by_name',
		'created_by_name',
	);
	
	static $inherit_purchase_order_fields = array(
		'assigned_user_id',
		'assigned_user_name',
		'deleted',
		'name',
		'due_date',
		'tax_information',
		'supplier_id',
		'supplier_contact_id',
		'supplier_name',
		'supplier_contact_name',
		'currency_id',
		'exchange_rate',
		'shipping_provider_id',
		'description',
		'amount',
		'amount_usdollar',
		'terms',
		'show_components',
		'tax_information',
	);
	
	
	static function init_from_account($account_id) {
        $account = ListQuery::quick_fetch_row('Account', $account_id);
        $data = array();

        if ($account != null) {
            $data['supplier_id'] = $account['id'];
            $data['supplier_name'] = $account['name'];

            $fields = array(
                'currency_id', 'exchange_rate',
                'default_purchase_terms' => 'terms',
                'tax_information', 'default_purchase_shipper_id' => 'shipping_provider_id',
            );

            foreach($fields as $k => $f) {
                if(is_int($k)) $k = $f;
                $data[$f] = $account[$k];
            }
        }

        return $data;
	}
	
	
	static function init_from_po(RowUpdate &$self, RowUpdate &$tally, array &$update) {
		$map = self::$inherit_purchase_order_fields;
		$update['from_purchase_order_id'] = $tally->getField('id');
		$update['related_purchase_order_id'] = $tally->getField('id');
		
		foreach ($map as $key => $value) {
			if(is_int($key)) $key = $value;
			$fv = $tally->getField($key);
			if(isset($fv))
				$update[$value] = $fv;
		}
		$groups = $tally->getGroups(true);
		$self->replaceGroups($groups);
	}

	
	function getCurrentPrefix()
	{
		return AppConfig::get_sequence_prefix('bill_prefix');
	}
	
	function getCurrentSequenceValue()
	{
		return AppConfig::current_sequence_value('bill_number_sequence');
	}
	
	function getNextSequenceValue()
	{
		return AppConfig::next_sequence_value('bill_number_sequence');
	}
	

	function getHighlightStyle(&$fields)
	{
		global $timedate;
		if ($fields['AMOUNT_DUE'] > 0) {
			$time = strtotime(date($timedate->dbDayFormat));
			if(! empty($fields['DUE_DATE'])) {
				$due_date = strtotime($timedate->to_db_date($fields['DUE_DATE'], false));
				if ($time > $due_date) {
					return 'overdueTask';
				} elseif ($time == $due_date) {
					return 'todaysTask';
				}
			}
		}
		
		return 'futureTask';
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
		$ret = BillLineGroup::newForParent($this);
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

		//$this->update_account_balance();
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
    


	function bill_allocations(){
		    $query="SELECT pur.*,CONCAT(pur.prefix,pur.po_number) AS full_number from purchase_orders pur LEFT JOIN bills bill ON pur.id=bill.related_purchase_order_id
		    WHERE bill.id='".$this->id."'";
		
		return $query;
	}
	
	function get_view_closed_where_advanced($param)
	{
		return '1';
	}

	function get_view_closed_where_basic($param)
	{
		return empty($param['value']) ? "bills.amount_due > 0 AND ! bills.cancelled" : '1';
	}
	
	function getDefaultListWhereClause()
	{
		return "bills.amount_due > 0 AND ! bills.cancelled";
	}

	function search_by_number($param) {
		if (empty($param['value'])) {
			return 1;
		} else {
			return "(bills.bill_number = '" . PearDatabase::quote($param['value']) . "' OR CONCAT(bills.prefix, bills.bill_number) = '" . PearDatabase::quote($param['value']) . "' ) ";
		}
	}
	
	function cleanup() {
		if(isset($this->line_groups)) {
			$this->cleanup_line_groups($this->line_groups);
			unset($this->line_groups);
		}
		parent::cleanup();
	}

	function getTaxDate() {
		if (!empty($this->bill_date)) {
			global $timedate;
			return $timedate->to_db_date($this->bill_date, false);
		}
		if (empty($this->date_entered)) {
			return gmdate('Y-m-d');
		}
		global $timedate;
		if (strlen($this->date_entered > 10)) {
			return $timedate->to_db($this->date_entered);
		} else {
			return $timedate->to_db_date($this->date_entered, false);
		}
	}

    static function payment_allocations($id, $currency_id) {
        $query = sprintf(
            "SELECT
            payments.*, CONCAT(payments.prefix, payments.payment_id) AS full_number,
            '%s' as bills_currency_id,
            link.amount AS allocated,
            link.amount_usdollar AS allocated_usdollar
            FROM bills_payments link
            LEFT JOIN payments
            ON link.payment_id = payments.id
            WHERE
            !link.deleted
                AND
            !payments.deleted
                AND
            link.bill_id = '%s'
            ",
            PearDatabase::quote($currency_id),
            PearDatabase::quote($id)
        );
        return $query;
    }
    
	static function get_payments($id, $currency_id) {
        global $db;
		$query = self::payment_allocations($id, $currency_id);
		$payments = array();
		$res = $db->query($query);

		while ($row = $db->fetchByAssoc($res)) {
			$payments[] = $row;
		}
        
		return $payments;
	}

    static function update_account_balance($supplier_id) {
        $account = ListQuery::quick_fetch('Account', $supplier_id);
        if ($account) {
            $upd = RowUpdate::for_result($account);
            //Call pre_update_balance through account's before_save hook
            $upd->save();
        }
    }

    static function after_delete(RowUpdate $upd) {
        self::update_account_balance($upd->getField('supplier_id'));
    }

    static function before_save(RowUpdate $upd) {
        if($upd->new_record) {
            $upd->set(array(
                'amount_due' => $upd->getField('amount'),
            ));
        } else {
            $amount = (float)$upd->getField('amount');

            if ($amount > 0) {
                $payments = self::get_payments($upd->getPrimaryKeyValue(), $upd->getField('currency_id'));
                $total_paid = 0;

                foreach ($payments as $payment)
                    $total_paid += $payment['allocated'];

                $amount_due = $amount - $total_paid;
                $upd->set('amount_due', $amount_due);
            }
        }
    }

    static function after_save(RowUpdate $upd) {
        self::update_account_balance($upd->getField('supplier_id'));
    }

    static function init_record(RowUpdate &$upd, $input) {
        $update = array();

        if(! empty($input['supplier_id'])) {
            $account_data = self::init_from_account($input['supplier_id']);
            $update += $account_data;
        }


        $from_po_id = null;
        if(! empty($input['from_purchaseorder_id'])) {
            $from_po_id = $input['from_purchaseorder_id'];
        }

        if($from_po_id) {
        	$po = ListQuery::quick_fetch('PurchaseOrder', $from_po_id);
        	$po_upd = RowUpdate::for_result($po);
            self::init_from_po($upd, $po_upd, $update);
        }

        if (empty($update['terms']))
            $update['terms'] = AppConfig::setting('company.bill_default_terms', 'COD');

        switch ($update['terms']) {
            case 'Net 7 Days':
                $offset = 7 * 24 * 3600;
                break;
            case 'Net 15 Days':
                $offset = 15 * 24 * 3600;
                break;
            case 'Net 30 Days':
                $offset = 30 * 24 * 3600;
                break;
            case 'Net 45 Days':
                $offset = 45 * 24 * 3600;
                break;
            case 'Net 60 Days':
                $offset = 60 * 24 * 3600;
                break;
            default:
                $offset = 0;
        }

        global $timedate;
        $update['due_date'] = date('Y-m-d', time() + $offset);
        $update['bill_date'] = date('Y-m-d');
        $update['show_components'] = 'all';

        $upd->set($update);
    }

	static function send_notification(RowUpdate $upd) {
        $vars = array(
            'BILL_SUBJECT' => array('field' => 'name', 'in_subject' => true),
            'BILL_DESCRIPTION' => array('field' => 'description')
        );

        $manager = new NotificationManager($upd, 'BillAssigned', $vars);

        if ($manager->wasRecordReassigned())
            $manager->sendMails();
	}
}
?>
