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
require_once('modules/Invoice/InvoiceLineGroup.php');
require_once('modules/Accounts/Account.php');

class Invoice extends SugarBean {
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
	var $purchase_order_num;
	var $description;
	var $due_date;	
	var $terms;
	var $prefix;
	//
	var $opportunity_id, $opportunity_name;
	var $event_id, $event_name;
	// These are related
	var $billing_account_name, $billing_account_id;
	var $billing_address_street, $billing_address_city, $billing_address_state, $billing_address_postalcode, $billing_address_country;
	var $billing_phone;
	var $billing_email;
	// Used by quick-creation form
	var $account_id;
	//
	var $shipping_account_name, $shipping_account_id;
	var $shipping_address_street, $shipping_address_city, $shipping_address_state, $shipping_address_postalcode, $shipping_address_country;
	var $shipping_phone;
	var $shipping_email;

	var $populate_addresses = true;

	//
	var $billing_contact_id, $billing_contact_name;
	var $shipping_contact_id, $shipping_contact_name;
	var $currency_id, $exchange_rate;
	var $shipping_provider_id;
	var $tax_information;
	var $tax_exempt;
	var $discount_before_taxes;
	var $shipping_stage;

	// line items
	var $line_items = array();
	var $grand_total;

	var $amount, $amount_usdollar;
	var $amount_due;
	var $amount_due_usdollar;
	var $cancelled;
	var $show_components;
	var $products_created;
	// ...
	
	var $from_quote_id;
	var $refunded_invoice_id, $refunded_invoice_number;
	var $affect_invoice_balance;
	var $from_so_id;
	var $from_so_number, $from_so_name; // not stored
	var $from_quote_number; // not stored
	var $allocated; // for subpanel on payments
	var $gross_profit, $gross_profit_usdollar;

	var $partner_id;
	var $partner_name;
	var $note_id;

	var $relationship_fields = array(
		'note_id' => 'notes',
		'billing_account_id' => 'billing_account_link',
		'shipping_account_id' => 'shipping_account_link',
		'project_id' => 'projects',
	);

	//
	var $table_name = "invoice";
	var $object_name = "Invoice";
	var $group_object_name = "InvoiceLineGroup";
	var $module_dir = "Invoice";
	var $new_schema = true;
	//
	var $account_table = "accounts";
	var $contact_table = "contacts";
	var $opportunity_table = "opportunities";
	

	static $inherit_quote_fields = array(
		'assigned_user_id',
		'deleted',
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
	);

    static $grp_idx = 0;

    function Invoice()
	{
		parent::SugarBean();
	}
	
	static function init_from_tally(RowUpdate &$self, RowUpdate &$tally) {
		$map = self::$inherit_quote_fields;
		$type = $tally->getModelName();
		if ($type == 'Quote') {
			$map['id'] = 'from_quote_id';
		} elseif ($type == 'SalesOrder') {
			$map['id'] = 'from_so_id';
			$map['related_quote_id'] = 'from_quote_id';
		}
		
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
            $data['shipping_account_id'] = $account['id'];
            $data['shipping_provider_id'] = $account['default_shipper_id'];
            if ($account['tax_code_id'] == EXEMPT_TAXCODE_ID)
                $data['tax_exempt'] = 1;
            if ($account['partner_id'])
                $data['partner_id'] = $account['partner_id'];

            $fields = array(
                'address_street', 'address_city', 'address_state',
                'address_postalcode', 'address_country',
                'currency_id', 'exchange_rate',
                'default_discount_id',
                'default_terms' => 'terms', 'name',
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
	
	static function init_from_case($case_id) {
	    $case = ListQuery::quick_fetch_row('aCase', $case_id);
        $data = array();

        if ($case != null) {
            global $current_language;
            $case_mod_strings = return_module_language($current_language, 'Cases');

            if(! empty($case['account_id'])) {
            	$account_data = self::init_from_account($case['account_id']);
            	array_extend($data, $account_data);
            }
            if(! empty($case['cust_contact_id'])) {
            	$data['billing_contact_id'] = $data['shipping_contact_id'] = $case['cust_contact_id'];
            }
            if(! empty($case['name'])) {
                $data['name'] = $case['name'];
            }
            if(! empty($case['description'])) {
                $data['description'] = $case_mod_strings['LBL_DESCRIPTION']."\n ".$case['description'];
            }

            if(! empty($data['description'])) $data['description'] .= "\n\n";
            
            if(! empty($case['resolution']))
                $data['description'] .= $case_mod_strings['LBL_RESOLUTION']."\n ".$case['resolution'];
        }

        return $data;
	}

    function getCurrentPrefix()
	{
		return AppConfig::get_sequence_prefix('invoice_prefix');
	}
	

	function getCurrentSequenceValue()
	{
		return AppConfig::current_sequence_value('invoice_number_sequence');
	}
	
	function getNextSequenceValue()
	{
		return AppConfig::next_sequence_value('invoice_number_sequence');
	}
	
	function save($check_notify = FALSE, $update_balance = true)
	{
		// need an ID to save line items
		if(empty($this->id)) {
			$this->id = create_guid();
			$this->new_with_id = true;
		}
		
		if(! $this->due_date)
			$this->due_date = Invoice::calc_due_date($this->terms, true);
		
		// must do this before manipulating numeric fields
		$this->unformat_all_fields();
		
		$this->save_line_groups();
		
		/*if($this->populate_addresses && isset($this->account_id) && !empty($this->account_id)) {
			// auto-populate billing and shipping account information
			$acct = new Account();
			if($acct->retrieve($this->account_id)) {
				$this->billing_account_id = $acct->id;
				$this->shipping_account_id = $acct->id;
				$copy_fields = array(
					'billing_address_street', 'billing_address_city', 'billing_address_state', 'billing_address_postalcode', 'billing_address_country',
					'shipping_address_street', 'shipping_address_city', 'shipping_address_state', 'shipping_address_postalcode', 'shipping_address_country');
				foreach($copy_fields as $fname)
					$this->$fname = $acct->$fname;
			}
			$acct->cleanup();
		}*/
	
		$ret = parent::save($check_notify);

		return $ret;
	}

	function create_supported_assembly(RowUpdate $upd, $line, $id)
	{
		if(! strlen($line['name']))
			return;
		$sa = RowUpdate::blank_for_model('SupportedAssembly');
		$a = ListQuery::quick_fetch('Assembly', $line['related_id']);
		$sa->set('id', $id);
		$transfer = array(
			'supplier_id',
			'manufacturer_id',
			'model_id',
			'vendor_part_no',
			'product_url',
			'product_category_id',
			'product_type_id',
		);

		if ($a && !$a->getField('deleted')) {
			foreach ($transfer as $f) {
				$sa->set($f, $a->getField($f));
			}
		}
		$sa->set(array(
			'manufacturers_part_no' => $line['mfr_part_no'],
			'name' => $line['name'],
			'quantity' => $line['quantity'],
			'account_id' => $upd->getField('billing_account_id'),
			'tax_code_id' => $line['tax_class_id'],
			'currency_id' => $upd->getField('currency_id'),
			'exchange_rate' => $upd->getField('exchange_rate'),
		));
		$sa->save();
	}

	static function create_supported_product(RowUpdate &$upd, $line, $parent_id = '')
	{
		if($line['related_type'] != 'ProductCatalog')
			return;
		if(! strlen($line['name']))
			return;
		$sp = RowUpdate::blank_for_model('Asset');
		$p = ListQuery::quick_fetch('Product', $line['related_id']);
		$transfer = array(
			'supplier_id',
			'manufacturer_id',
			'model_id',
			'vendor_part_no',
			'url',
			'product_category_id',
			'product_type_id',
		);

		if ($p && !$p->getField('deleted')) {
			foreach ($transfer as $f) {
				$sp->set($f, $p->getField($f));
			}
		}
		$name = $line['name'];
		$pnum = $line['mfr_part_no'];
		if(! strlen($name))
			$name = $pnum;
		else if(! strlen($pnum))
			$pnum = $name;
		$sp->set(array(
			'name' => $name,
			'manufacturers_part_no' => $pnum,
			'quantity' => $line['quantity'],
			'account_id' => $upd->getField('billing_account_id'),
			'supported_assembly_id' => $parent_id,
			'tax_code_id' => $line['tax_class_id'],
			'currency_id' => $upd->getField('currency_id'),
			'exchange_rate' => $upd->getField('exchange_rate'),
			'purchase_price' => $line['unit_price'],
			'purchase_usdollar' => $line['unit_price_usd'],
		));
		$sp->save();
	}

	function set_notification_body($xtpl, $oppty)
	{
		$xtpl->assign("OPPORTUNITY_NAME", $oppty->name);
		$xtpl->assign("OPPORTUNITY_AMOUNT", $oppty->amount);
		$xtpl->assign("OPPORTUNITY_CLOSEDATE", $oppty->date_closed);
		$xtpl->assign("OPPORTUNITY_STAGE", $oppty->sales_stage);
		$xtpl->assign("OPPORTUNITY_DESCRIPTION", $oppty->description);

		return $xtpl;
	}

	function save_line_groups() {
		if(isset($this->line_groups) && is_array($this->line_groups)) {
			foreach(array_keys($this->line_groups) as $k) {
				$this->line_groups[$k]->save();
			}
			$gt =& $this->line_groups['GRANDTOTAL'];
			$this->amount = $gt->total;
			$this->gross_profit = $gt->subtotal - $gt->discount - $gt->cost;
		}
	}

	function get_recurrence_forward_instances()
	{
		return 1;
	}

	function get_recurrence_scheduled_interval()
	{
		return 3600*24*30; // for one month by befault
	}

	function get_recurrence_date_field()
	{
		return 'due_date';
	}

	function get_recurrence_time_field()
	{
		return '';
	}

    function recur_pre_save()
	{
		$this->id = create_guid();
		$this->new_with_id = true;
		$this->prefix = '';
		$this->invoice_number = '';
		$lgm =& $this->get_line_group_manager();
		$template = new Invoice();
		$template->retrieve($this->recurrence_of_id);
		$groups =& $template->get_line_groups();
		$items = $lgm->convert_to_array($groups, true); // strip IDs
		$this->line_groups = array();
		$lgm->update_from_array($this->line_groups, $items, true);
		$this->cancelled = 0;
		$lgm->cleanup();
		$template->cleanup_line_groups($groups);
		$template->cleanup();
	}

	function extractQuantities($line_items, &$products, &$assemblies, $sign)
	{
		foreach ($line_items as $group) {
			if (!empty($group->lines)) foreach ($group->lines as $row) {
				if($row['related_type'] == 'ProductCatalog') {
					$id = $row['related_id'];
					if(! isset($products[$id])) $products[$id] = 0;
					$products[$id] += $row['ext_quantity'] * $sign;
				}
				else if($row['related_type'] == 'Assemblies') {
					$id = $row['related_id'];
					if(! isset($assemblies[$id])) $assemblies[$id] = 0;
					$assemblies[$id] += $row['ext_quantity'] * $sign;
				}
			}
		}
	}
	
	static function calc_due_date($terms = null, $display = false) {
		global $timedate;
		$offset = 0;
		if($terms) {
			if(preg_match('/(\d+) Days/i', $terms, $m))
				$offset = $m[1];
		}

		$now = gmdate('Y-m-d H:i:s', strtotime(gmdate('Y-m-d H:i:s') . ' GMT + ' . $offset . ' days'));
		$now = $timedate->to_display_date_time($now, false, true);
		list($now, $unused) = explode(' ', $now);

		if(!$display)
			$now = $timedate->swap_formats($now, $timedate->get_date_format(), $timedate->get_db_date_format());
		return $now;
	}

	function get_view_closed_where_advanced($param)
	{
		return '1';
	}

	function get_view_closed_where_basic($param)
	{
		return empty($param['value']) ? "ABS(invoice.amount_due) > 0.005 AND ! invoice.cancelled" : '1';
	}

	function getDefaultListWhereClause()
	{
		return "(ABS(invoice.amount_due) > 0.005 AND ! invoice.cancelled)";
	}

	function search_by_number($param) {
		if (empty($param['value'])) {
			return 1;
		} else {
			return "(invoice.invoice_number = '" . PearDatabase::quote($param['value']) . "' OR CONCAT(invoice.prefix, invoice.invoice_number) = '" . PearDatabase::quote($param['value']) . "' ) ";
		}
	}

	function cleanup() {
		if(isset($this->line_groups)) {
			$this->cleanup_line_groups($this->line_groups);
			unset($this->line_groups);
		}
		parent::cleanup();
	}

	function get_search_stage_options()
	{
		global $app_list_strings, $mod_strings;
		return array_merge(array(''=>''), $app_list_strings['shipping_stage_dom']);
	}

	function get_stage_where_advanced($param)
	{
		return "invoice.shipping_stage='" . PearDatabase::quote($param['value']) . "'";
	}

	function get_stage_where_basic($param)
	{
		return '1';
	}
	
	// assumes that date_entered is in user format
	function getTaxDate() {
		if (empty($this->date_entered)) {
			return gmdate('Y-m-d');
		}
		global $timedate;
		if (strlen($this->date_entered) > 10) {
			return $timedate->to_db($this->date_entered);
		} else {
			return $timedate->to_db_date($this->date_entered, false);
		}
	}

    function setAccountSalesDates() {
        $account = new Account;
        if($account->retrieve($this->billing_account_id)) {
            $invoice = new Invoice;
            if (empty($account->first_invoice_id) || !$invoice->retrieve($account->first_invoice_id) || $invoice->deleted) {
                $this->db->query("UPDATE accounts SET first_invoice_id = '{$this->id}' WHERE id='{$account->id}'", true);
            }
            $this->db->query("UPDATE accounts SET last_invoice_id = '{$this->id}' WHERE id='{$account->id}'", true);
            $invoice->cleanup();
        }
        $account->cleanup();
    }

	static function get_attached_credit_notes($id, $currency_id, $exchange_rate)
	{
		$lq = new ListQuery('CreditNote');
		$lq->addSimpleFilter('invoice_id', $id);
		$lq->addSimpleFilter('apply_credit_note', '', 'true');
		$lq->addSimpleFilter('cancelled', 0);
		return $lq->fetchAllRows();
	}

    static function payment_allocations($id, $currency_id, $include_refunded = true) {
    	$tbl = 'invoices_payments';
    	$rel_id = 'invoice_id';
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

    static function update_account_balance($billing_account_id) {
        $account = ListQuery::quick_fetch('Account', $billing_account_id);

        if ($account) {
            $upd = RowUpdate::for_result($account);
            //Call pre_update_balance through account's before_save hook
            $upd->save();
        }
    }

    static function after_delete(RowUpdate $upd) {
        global $db;
        $eid = $db->quote($upd->getPrimaryKeyValue());

        //$upd->removeAllLinks('payments');
        $db->query("UPDATE booked_hours bh SET bh.invoice_id = NULL WHERE bh.invoice_id = '$eid'");

        self::update_account_balance($upd->getField('billing_account_id'));
    }

    static function before_save(RowUpdate $upd) {
        if($upd->new_record) {
            $upd->set(array(
                'amount_due' => $upd->getField('amount')
            ));
            
            if(! $upd->getField('invoice_date'))
            	$upd->set('invoice_date', date('Y-m-d'));
        } else {
            $amount = (float)$upd->getField('amount');
            $id = $upd->getPrimaryKeyValue();
            $total_paid = 0;
            $amount_credited = 0;

            if ($amount > 0) {
                    $payments = self::get_payments($id, $upd->getField('currency_id'));
                    foreach ($payments as $payment)
                        $total_paid += $payment['allocated'];

					$notes = self::get_attached_credit_notes($id, null, null);
					foreach ($notes as $note) {
						$amount_credited += $note['amount_usdollar'];
                    }
            }

            $total_paid += $amount_credited * $upd->getField('exchange_rate');

            $amount_due = $amount - $total_paid;
            $upd->set('amount_due', $amount_due);
        }
     
		$products_created = $upd->getField('products_created', 'none');
		if ($products_created === 'none') {
			if ($upd->new_record) {
				$products_created = 0;
			} else {
				$u = RowUpdate::blank_for_model($upd->getModelName());
				if ($u->retrieveRecord($upd->getPrimaryKeyValue())) {
					$products_created = $u->getField('products_created');
				} else {
					$products_created = 1;
				}
			}
		}
		if (! $products_created && $upd->getField('amount_due') <= 0) {
			if (AppConfig::setting('company.create_invoice_products') && $upd instanceof TallyUpdate) {
				$id_map = array();
				$children = array();
				$groups = $upd->getGroups();
				foreach ($groups as $gid => $group) {
					foreach ($group['lines'] as $line) {
						if (!empty($line['is_comment'])) continue;
						if (!empty($line['parent_id'])) {
							$children[] = $line;
						} else {
							if (!empty($line['sum_of_components'])) {
								$id_map[$line['id']] = create_guid();
								self::create_supported_assembly($upd, $line, $id_map[$line['id']]);
							} else {
								self::create_supported_product($upd, $line);
							}
						}
					}
				}
				foreach ($children as $child) {
					self::create_supported_product($upd, $child, array_get_default($id_map, $line['parent_id'], ''));
				}
				$upd->set('products_created', 1);
			}
		}
    }

    static function after_save(RowUpdate $upd) {
    	if($upd->new_record) {
    		$acc_id = $upd->getField('billing_account_id');

            if(! empty($acc_id) && ($base = ListQuery::quick_fetch('Account', $acc_id))) {
    			$acc_up = RowUpdate::for_result($base);

                if(! $acc_up->getField('first_invoice_id'))
    				$acc_up->set('first_invoice_id', $upd->getField('id'));

                $acc_up->set('last_invoice_id', $upd->getField('id'));
				$acc_up->save();
    		}
    		
    		$qt_id = $upd->getField('from_quote_id');
            if(! empty($qt_id) && ($base = ListQuery::quick_fetch('Quote', $qt_id))) {
    			$qt_up = RowUpdate::for_result($base);
    			if($qt_up->getField('quote_stage') != 'Closed Accepted') {
    				$qt_up->set('quote_stage', 'Closed Accepted');
    				$qt_up->save();
    			}
    		}
    		
    		$opp_id = $upd->getField('opportunity_id');
            if(! empty($opp_id) && ($base = ListQuery::quick_fetch('Opportunity', $opp_id))) {
    			$opp_up = RowUpdate::for_result($base);
    			if(! preg_match('~^Closed ~', $opp_up->getField('sales_stage'))) {
    				$opp_up->set('sales_stage', 'Closed Won');
    				$opp_up->save();
    			}
    		}
		}
		
		if (isset($_REQUEST['recurrence_rules'])) {
			require_once 'modules/Recurrence/RecurrenceRule.php';
			$rule = new RecurrenceRule;
			$rule->update_rules_from_JSON('Invoice', $upd->getPrimaryKeyValue(), $_REQUEST['recurrence_rules'], true);
		}

        self::update_account_balance($upd->getField('billing_account_id'));
		require_once 'modules/MonthlyServices/MonthlyService.php';
		MonthlyService::updateBalances($upd->getPrimaryKeyValue());
		
		if($upd instanceof TallyUpdate)
			self::update_booked_hours($upd);
    }
    
    static function update_booked_hours(TallyUpdate $upd) {
    	$self_id = $upd->getPrimaryKeyValue();
    	$groups = $upd->getGroups();
    	if($groups && $self_id) {
			foreach ($groups as $gid => $group) {
				foreach ($group['lines'] as $line) {
					if (!empty($line['is_comment'])) continue;
					if (!empty($line['related_id']) && $line['related_type'] == 'Booking') {
						$row = ListQuery::quick_fetch('BookedHours', $line['related_id']);
						if($row && $row->getField('invoice_id') != $self_id) {
							$upd = RowUpdate::for_result($row);
							$upd->set('invoice_id', $self_id);
							$upd->save();
						}
					}
				}
			}
		}
    }

    static function add_view_popups(DetailManager $mgr) {
        require_bean('Account');
        Account::add_account_popup($mgr->getRecord(), 'billing_account_id', 'sales');
    }

    static function get_booked_hours_group($from_id, $from_model) {
        $hours = new ListQuery($from_model, true, array('link_name' => 'booked_hours', 'parent_key' => $from_id));
        $hours->addField('booking_category');
        $hours->addSimpleFilter('status', 'approved', '=');
        $hours->addSimpleFilter('booking_class', 'billable-work', '=');
        $hours->addSimpleFilter('invoice_id', '', 'null');
        $result = $hours->runQuery();
        $add_comments = AppConfig::setting('company.add_booked_hours');
        $new_groups = array();

        if($result && $result->total_count) {
            $grp_id = 'temp~'.(self::$grp_idx ++);
            $new_groups = array(
                $grp_id => array(
                    'id' => $grp_id,
                    'status' => 'Draft',
                    'lines' => array(),
                    'lines_order' => array(),
                    'adjusts' => array(),
                    'adjusts_order' => array(),
                    'group_type' => 'service',
                ),
            );

            $fmap = array(
                'related_id' => 'id',
                'tax_class_id' => 'tax_code_id',
                'currency_id' => 'billing_currency',
                'exchange_rate' => 'billing_exchange_rate',
                'raw_cost_price' => 'paid_rate',
                'raw_list_price' => 'billing_rate',
                'raw_unit_price' => 'billing_rate',
                'cost_price' => 'paid_rate_usd',
                'list_price' => 'billing_rate_usd',
                'unit_price' => 'billing_rate_usd',
            );
            foreach($result->rows as $row) {
                $line = array('related_type' => 'Booking');
                $line['id'] = 'temp~' . rand(1000, 2000);
                $line['quantity'] = $row['quantity'] / 60;
                $line['name'] = $row['booking_category'];
                if(! $add_comments)
                    $line['name'] .= ': '.$row['name'];
                foreach($fmap as $k => $f) {
                    if(is_int($k)) $k = $f;
                    $line[$k] = $row[$f];
                }

                $new_groups[$grp_id]['lines'][$line['id']] = $line;
				$new_groups[$grp_id]['lines_order'][] = $line['id'];
                if($add_comments) {
                    $comment_id = 'comment~' . rand(1000, 2000);
                    $new_groups[$grp_id]['lines'][$comment_id] = array('related_type' => 'Notes', 'parent_id' => $line['id'], 'body' => $row['name'], 'is_comment' => 1);
                    $new_groups[$grp_id]['lines_order'][] = $comment_id;
				}
            }
        }

        return $new_groups;
    }

    static function get_service_parts_group($case_id) {
        $prods = new ListQuery('aCase', true, array('link_name' => 'products', 'parent_key' => $case_id));
        $prods->addField('~join.quantity', 'quantity');
        $result = $prods->runQuery();
        $new_groups = array();

        if($result || $result->total_count) {
            $grp_id = 'temp~'.(self::$grp_idx ++);
            $new_groups = array(
                $grp_id => array(
                    'id' => $grp_id,
                    'status' => 'Draft',
                    'lines' => array(),
                    'lines_order' => array(),
                    'adjusts' => array(),
                    'adjusts_order' => array(),
                    'group_type' => 'products',
                ),
            );

            $fmap = array(
                'quantity',
                'related_id' => 'id',
                'name',
                'purchase_name',
                'tax_class_id' => 'tax_code_id',
                'mfr_part_no' => 'manufacturers_part_no',
                'vendor_part_no',
                'currency_id',
                'exchange_rate',
                'description',
                'raw_cost_price' => 'cost',
                'raw_list_price' => 'list_price',
                'raw_unit_price' => 'purchase_price',
                'cost_price' => 'cost_usdollar',
                'list_price' => 'list_usdollar',
                'unit_price' => 'purchase_usdollar',
            );

            foreach($result->rows as $row) {
                $line = array('related_type' => 'ProductCatalog');
                $line['id'] = 'temp~' . rand(1000, 2000);
                foreach($fmap as $k => $f) {
                    if(is_int($k)) $k = $f;
                    $line[$k] = $row[$f];
                }
                $new_groups[$grp_id]['lines'][$line['id']] = $line;
                $new_groups[$grp_id]['lines_order'][] = $line['id'];
            }
        }

        return $new_groups;
    }

	static function init_record(RowUpdate &$upd, $input) {
        $update = array();
        $inited = false;

        $from_quote_id = null;
        if(! empty($input['quote_id'])) {
            $from_quote_id = $input['quote_id'];
        } elseif (! empty($input['from_quote_id'])) {
            $from_quote_id = $input['from_quote_id'];
        }
        $from_so_id = null;
        if(! empty($input['salesorder_id'])) {
            $from_so_id = $input['salesorder_id'];
        } elseif (! empty($input['from_so_id'])) {
            $from_so_id = $input['from_so_id'];
        }

        if (empty($from_quote_id) && empty($from_so_id)) {

            $initFrom = array('opportunity_id' => 'Opportunity',
                'project_id' => 'Project', 'acase_id' => 'aCase');


            foreach($initFrom as $id_f => $bean_name) {
                if(! empty($input[$id_f])) {
                    $bean = ListQuery::quick_fetch_row($bean_name, $input[$id_f]);

                    if ($bean != null) {
                        if ($id_f == 'opportunity_id') {
                            $update['opportunity_id'] = $bean['id'];
                            $update['name'] = $bean['name'];
                            $update['partner_id'] = $bean['partner_id'];
                        }
                    } else {
                        $update['name'] = $bean['name'];
                    }

                    if (! empty($bean['billing_account_id'])) {
                        $account_data = self::init_from_account($bean['billing_account_id']);
                        $update = $update + $account_data;
                    }

                    if ($id_f == 'acase_id') {
                        $case_data = self::init_from_case($bean['id']);
                        $update = $update + $case_data;
                    }

                    if(isset($bean['currency_id'])) {
                        $update['currency_id'] = $bean['currency_id'];
                        $update['exchange_rate'] = $bean['exchange_rate'];
                    }

                    //Update Line Items Groups
                    self::update_groups($upd, $bean['id'], $bean_name);

                    $inited = true;
                    break;
                }
            }
        } else {
            $inited = true;
        }

        if(! $inited && ! empty($input['billing_account_id'])) {
            $account_data = self::init_from_account($input['billing_account_id']);
            $update = $update + $account_data;
        }

        if (isset($input['parent_id']) && isset($input['parent_name'])
            && isset($input['parent_type']) && $input['parent_type'] == 'EventSessions') {

            $session = ListQuery::quick_fetch_row('EventSession', $input['parent_id']);
            
            if ($session != null) {
                $event = ListQuery::quick_fetch_row('Event', $session['event_id']);

                if ($event != null) {
                    $update['event_id'] = $event['id'];
                    $update['event_name'] = $event['name'];
                }
            }
        }

        if ($from_quote_id) {
        	$quote = ListQuery::quick_fetch('Quote', $from_quote_id);
        	$quote_upd = RowUpdate::for_result($quote);
        	self::init_from_tally($upd, $quote_upd);
        } elseif($from_so_id) {
        	$so = ListQuery::quick_fetch('SalesOrder', $from_so_id);
        	$so_upd = RowUpdate::for_result($so);
            self::init_from_tally($upd, $so_upd);
        } else {
            $update['show_components'] = 'all';
        }

        if (empty($update['event_id']) && isset($input['event_id']))
            $update['event_id'] = $input['event_id'];
        if (empty($update['terms']))
            $update['terms'] = AppConfig::setting('company.invoice_default_terms', 'COD');
        if (empty($update['due_date']))
            $update['due_date'] = Invoice::calc_due_date($update['terms']);
        if (empty($update['invoice_date']))
			$update['invoice_date'] = Invoice::calc_due_date();

		global $timedate;
		if (!empty($upd->duplicate_of_id)) {
			$now = gmdate('Y-m-d H:i:s');
			$now = $timedate->to_display_date_time($now, false, true);
			list($now, $unused) = explode(' ', $now);
			$now = $timedate->swap_formats($now, $timedate->get_date_format(), $timedate->get_db_date_format());

			unset($update['terms']);
			$update['invoice_date'] = $now;
			$update['due_date'] = Invoice::calc_due_date($upd->getField('terms'));
		}

        $upd->set($update);
    }

    static function update_groups(RowUpdate &$invoice_upd, $from_id, $from_model) {
    	if($from_model == 'aCase' && ! AppConfig::setting('site.feature.auto_add_case_line_items'))
    		return;
		
        $hours = self::get_booked_hours_group($from_id, $from_model);
        $service_parts = array();
        if ($from_model == 'aCase')
            $service_parts = self::get_service_parts_group($from_id);

        $groups = array_merge($hours, $service_parts);
        if (sizeof($groups) > 0)
            $invoice_upd->replaceGroups($groups);
    }

	static function send_notification(RowUpdate $upd) {
        $vars = array(
            'INVOICE_SUBJECT' => array('field' => 'name', 'in_subject' => true),
            'INVOICE_DESCRIPTION' => array('field' => 'description')
        );

        $manager = new NotificationManager($upd, 'InvoiceAssigned', $vars);

        if ($manager->wasRecordReassigned())
            $manager->sendMails();
	}

    static function get_activity_status(RowUpdate $upd) {
        $status = null;

        if ($upd->getField('cancelled') &&  !$upd->getField('cancelled', null, true))
            $status = 'cancelled';

        return $status;
    }
	
	static function get_new_activity_status(RowUpdate $upd) {
        $status = 'created';

		if ($upd->getField('from_so_id')) {
			$status = array(
				'status' => 'created',
				'converted_to_type' => 'SalesOrders',
				'converted_to_id' => $upd->getField('from_so_id'),
			);
		} elseif ($upd->getField('from_quote_id')) {
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
