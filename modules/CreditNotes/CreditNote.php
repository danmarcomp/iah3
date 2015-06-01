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
require_once('modules/CreditNotes/CreditNoteLineGroup.php');
require_once('modules/Accounts/Account.php');

class CreditNote extends SugarBean {
	// Stored fields
	var $id;
	var $invoice_number;
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
	// These are related
	var $billing_account_name, $billing_account_id;
	var $billing_address_street, $billing_address_city, $billing_address_state, $billing_address_postalcode, $billing_address_country;
	var $billing_phone;
	var $billing_email;
	// Used by quick-creation form
	var $account_id;

	var $populate_addresses = true;

	//
	var $billing_contact_id, $billing_contact_name;
	var $currency_id, $exchange_rate;
	var $tax_information;
	var $tax_exempt;
	var $discount_before_taxes;

	// line items
	var $line_items = array();
	var $grand_total;

	var $amount, $amount_usdollar;
	var $amount_due;
	var $amount_due_usdollar;
	var $cancelled;
	var $show_components;
	// ...
	
	var $affect_invoice_balance;
	var $allocated; // for subpanel on payments
	var $gross_profit, $gross_profit_usdollar;

	var $note_id;

	//
	var $table_name = "credit_notes";
	var $object_name = "CreditNote";
	var $group_object_name = "CreditNoteLineGroup";
	var $module_dir = "CreditNotes";
	var $new_schema = true;
	//
	var $account_table = "accounts";
	var $contact_table = "contacts";
	var $opportunity_table = "opportunities";
	
	
	static $inherit_invoice_fields = array(
		'assigned_user_id',
		'deleted',
		'name',
		'opportunity_id',
		'purchase_order_num',
		'billing_account_id',
		'billing_contact_id',
		'billing_address_street',
		'billing_address_city',
		'billing_address_state',
		'billing_address_postalcode',
		'billing_address_country',
		'currency_id',
		'exchange_rate',
		'description',
		'terms',
		'show_components',
		'tax_information',
		'tax_exempt',
	);
	
	
	static function init_from_tally(RowUpdate &$self, RowUpdate &$tally) {
		$map = self::$inherit_invoice_fields;
		$type = $tally->getModelName();
		
		foreach ($map as $key => $value) {
			if(is_int($key)) $key = $value;
			$fv = $tally->getField($key);
			if(isset($fv))
				$self->set($value, $fv);
		}
		$groups = $tally->getGroups(true);
		$self->replaceGroups($groups);
	}

	
	static function init_from_account($account_id) {
        $account = ListQuery::quick_fetch_row('Account', $account_id);
        $data = array();

        if ($account != null) {
            $data['billing_account_id'] = $account['id'];
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
                } else {
                    $data[$f] = $account[$k];
                }
            }
        }

        return $data;
	}
	
	
	function get_view_closed_where_advanced($param)
	{
		return '1';
	}

	function get_view_closed_where_basic($param)
	{
		return empty($param['value']) ? "ABS(credit_notes.amount_due) > 0.005 AND ! credit_notes.cancelled" : '1';
	}

	function getDefaultListWhereClause()
	{
		return "(ABS(credit_notes.amount_due) > 0.005 AND ! credit_notes.cancelled)";
	}

	function search_by_number($param) {
		if (empty($param['value'])) {
			return 1;
		} else {
			return "(credit_notes.credit_number = '" . PearDatabase::quote($param['value']) . "' OR CONCAT(credit_notes.prefix, credit_notes.credit_number) = '" . PearDatabase::quote($param['value']) . "' ) ";
		}
	}

	function cleanup() {
		if(isset($this->line_groups)) {
			$this->cleanup_line_groups($this->line_groups);
			unset($this->line_groups);
		}
		parent::cleanup();
	}
	
    static function payment_allocations($id, $currency_id, $include_refunded = true) {
    	$tbl = 'credits_payments';
    	$rel_id = 'credit_id';
        $query = sprintf(
            "SELECT
            payments.*, CONCAT(payments.prefix, payments.payment_id) AS full_number,
            '%s' as invoice_currency_id,
            link.amount AS allocated,
            link.amount_usdollar AS allocated_usdollar
            FROM `$tbl` link
            LEFT JOIN payments
            ON link.payment_id = payments.id
            WHERE
            !link.deleted
                AND
            !payments.deleted
                AND
            payments.direction = 'credit'
            %s
                AND
            link.$rel_id = '%s'
            ",
            PearDatabase::quote($currency_id),
            $include_refunded ? '' : ' AND payments.refunded = 0',
            PearDatabase::quote($id)
        );

        return $query;
    }

    static function get_payments($id, $currency_id) {
        global $db;
		$payments = array();

        $query = self::payment_allocations($id, $currency_id);
        $res = $db->query($query);

        while ($row = $db->fetchByAssoc($res, -1, true)) {
            $payments[] = $row;
        }

		return $payments;
	}

	static function update_account_balance(RowUpdate $upd) {
		$billing_account_id = $upd->getField('billing_account_id');
		$invoice_id = $upd->getField('invoice_id');

		if ($invoice_id) {
			$invoice = ListQuery::quick_fetch('Invoice', $invoice_id);
			if ($invoice) {
	            $invUpd = RowUpdate::for_result($invoice);
				$invUpd->save();
			}
		}

        $account = ListQuery::quick_fetch('Account', $billing_account_id);

        if ($account) {
            $accUpdate = RowUpdate::for_result($account);
            //Call pre_update_balance through account's before_save hook
            $accUpdate->save();
        }
    }

    static function after_delete(RowUpdate $upd) {
        global $db;
        self::update_account_balance($upd);
    }

	static function before_save(RowUpdate $upd) {
		if (!$upd->getField('invoice_id'))
			$upd->set('apply_credit_note', 0);
        if($upd->new_record) {
            $upd->set(array(
                'amount_due' => $upd->getField('amount')
            ));
        } else {
            $amount = (float)$upd->getField('amount');
            $id = $upd->getPrimaryKeyValue();

            if ($amount > 0) {
				$payments = self::get_payments($id, $upd->getField('currency_id'));
                $total_paid = 0;

                foreach ($payments as $payment)
                    $total_paid += $payment['allocated'];

                $amount_due = $amount - $total_paid;
                $upd->set('amount_due', $amount_due);
            }
        }
    }

    static function after_save(RowUpdate $upd) {
        self::update_account_balance($upd);
    }

    static function init_record(RowUpdate &$upd, $input) {
        $inited = false;

		$invoice_id = array_get_default($input, 'invoice_id');
        if ($invoice_id) {
        	$inv = ListQuery::quick_fetch('Invoice', $invoice_id);
        	$inv_upd = RowUpdate::for_result($inv);
        	self::init_from_tally($upd, $inv_upd);
        	$inited = true;
        }

        if(! $inited && ! empty($input['billing_account_id'])) {
            $account_data = self::init_from_account($input['billing_account_id']);
            $upd->set($account_data);
        }

        if(! $upd->getField('due_date'))
			$upd->set('due_date', date('Y-m-d'));
    }
}
?>
