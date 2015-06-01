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
require_once('modules/Quotes/QuoteLineGroup.php');
require_once('modules/TaxCodes/TaxCode.php');

class Quote extends SugarBean {
	// Stored fields
	var $id;
	var $quote_number;
	var $prefix;
	var $date_entered;
	var $date_modified;
	var $modified_user_id, $modified_by_name;
	var $created_by, $created_by_name;
	var $assigned_user_id, $assigned_user_name;
	//
	var $name;
	var $quote_stage;
	var $purchase_order_num;
	var $description;
	var $valid_until;
	var $terms;
	var $approval;
	var $approval_problem;
	var $approved_user_id, $approved_user_name;
	//
	var $opportunity_id, $opportunity_name;
	// These are related
	var $billing_account_name, $billing_account_id;
	var $billing_address_street, $billing_address_city, $billing_address_state, $billing_address_postalcode, $billing_address_country;
	// Used by quick-creation form
	var $account_id;
	var $note_id;
	//
	var $shipping_account_name, $shipping_account_id;
	var $shipping_address_street, $shipping_address_city, $shipping_address_state, $shipping_address_postalcode, $shipping_address_country;
	//
	var $billing_contact_id, $billing_contact_name;
	var $shipping_contact_id, $shipping_contact_name;
	var $currency_id, $exchange_rate;
	var $shipping_provider_id;
	var $tax_information;
	var $sales_order_id;
	var $tax_exempt;
	var $discount_before_taxes;

	// line items
	var $line_items = array();
	var $grand_total;
    
	var $amount, $amount_usdollar;
	var $cost;
	var $markup, $margin;
	var $show_list_prices;
	var $show_components;
	var $budgetary_quote;
	var $gross_profit, $gross_profit_usdollar;
	// ...
	
    //
	var $table_name = "quotes";
	var $object_name = "Quote";
	var $group_object_name = "QuoteLineGroup";
	var $module_dir = "Quotes";
	var $new_schema = true;
	//
	var $account_table = "accounts";
	var $contact_table = "contacts";
	var $opportunity_table = "opportunities";
	
	
	var $additional_column_fields = Array(
		'assigned_user_name',
		'modified_by_name',
		'created_by_name',
		'approved_user_name',
		'account_id',
		'opportunity_name',
		'billing_account_name',
		'shipping_account_name',
		'billing_contact_name',
		'shipping_contact_name',
		'sales_order_name',
	); 

	var $relationship_fields = array(
		'note_id' => 'notes',
	);

	function Quote()
	{
		parent::SugarBean();
	}


	function initFromAccount($account_id) {
		if(empty($this->billing_account_id)) {
			$set_shipping = empty($this->shipping_account_id);
			require_once('modules/Accounts/Account.php');
			$acct = new Account();
			if($acct->retrieve($account_id)) {
				$this->billing_account_id = $acct->id;
				$this->billing_account_name = $acct->name;
				if($set_shipping) {
					$this->shipping_account_id = $acct->id;
					$this->shipping_account_name = $acct->name;
				}
				$fields = array(
					'billing_address_street', 'billing_address_city', 'billing_address_state',
					'billing_address_postalcode', 'billing_address_country',
					'currency_id', 'exchange_rate',
					'default_discount_id',
					'default_terms' => 'terms',
					'tax_information', 'tax_code_id' => 'default_tax_code_id',
				);
				foreach($fields as $k=>$f) {
					if(is_int($k)) $k = $f;
					$this->$f = $acct->$k;
					if($set_shipping) {
						$k = str_replace('billing', 'shipping', $k);
						$f = str_replace('billing', 'shipping', $f);
						$this->$f = $acct->$k;
					}
				}
				if($set_shipping)
					$this->shipping_provider_id = $acct->default_shipper_id;
				if($acct->tax_code_id == EXEMPT_TAXCODE_ID)
					$this->tax_exempt = 1;
			}
			$acct->cleanup();
		}
	}


	function getCurrentPrefix()
	{
		return AppConfig::get_sequence_prefix('quotes_prefix');
	}
	
	function getCurrentSequenceValue()
	{
		return AppConfig::current_sequence_value('quotes_number_sequence');
	}
	
	function getNextSequenceValue()
	{
		return AppConfig::next_sequence_value('quotes_number_sequence');
	}
	

	function get_summary_text()
	{
		return "{$this->prefix}{$this->quote_number}: $this->name";
	}
	
	
	function &get_line_groups($set_old_items=false, $reload=false) {
		if($reload || ! isset($this->line_groups) || ! is_array($this->line_groups)) {
			$lgm =& $this->get_line_group_manager();
			$encode = empty($this->pdf_output_mode);
			$this->line_groups =& $lgm->retrieve_all($encode);
			if($set_old_items) {
				$items =& $lgm->lineItemsFromGroups($this->line_groups);
				if(! count($items) && ! empty($this->line_items))
					$this->decode_line_items();
				else
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
		$ret = QuoteLineGroup::newForParent($this);
		return $ret;
	}
	
	function line_items_editable() {
		return true;
	}
	
	function get_assigned_contact_name(&$contact_id, $owner_field = '')
	{
        if (!empty($owner_field)) $this->$owner_field = '';
		$query = "SELECT contact.first_name, contact.last_name, contact.salutation, contact.assigned_user_id
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

		return parent::save($check_notify);
	}
    
    
	function save_line_groups() {
		if(isset($this->line_groups) && is_array($this->line_groups)) {
			foreach(array_keys($this->line_groups) as $k)
				$this->line_groups[$k]->save();
			
			$gt =& $this->line_groups['GRANDTOTAL'];
			$this->amount = $gt->total;
			$this->cost = $gt->cost;
			
			$cost = $gt->cost;
			$std = $gt->subtotal - $gt->discount;
			$this->gross_profit = $gp = $std - $cost;
			if($cost != 0)
				$this->markup = number_format($gp * 100.0 / $cost, 2);
			else
				$this->markup = 0;
			if($std != 0)
				$this->margin = number_format($gp * 100.0 / $std, 2);
			else
				$this->margin = 0;
		}
	}

	// obsolete
	function encode_line_items()
	{
		if(isset($this->line_items) && is_array($this->line_items)) {
			$qlg =& QuoteLineGroup::newForParent($this);
			$groups =& $qlg->groupsFromLineItems($this->line_items);
			
			foreach(array_keys($groups) as $idx) {
				$g =& $groups[$idx];
				$g->save();
			}
			
			unset($this->line_items); // don't update stored value

			$total = $groups['GRANDTOTAL']->total;
			$this->amount = $this->number_formatting_done ? format_number($total) : sprintf('%0.2f', $total);
		}
    }

	function decode_line_items()
	{
		$this->line_items = unserialize(base64_decode(($this->line_items)));
	}
    
    
	function isSubmitted()
	{
		return 'Submitted' == $this->approval;
	}
	
	static function useApprovals()
	{
		return !! AppConfig::setting('company.approve_quotes');
	}

	static function approvalEnabled(&$row, &$flag, &$val)
	{
		$val = false;
		if (!self::useApprovals() || !canApprove(AppConfig::current_user_id(), $row))
			$val = true;
	}


	static function needsApproval(&$row, &$flag, &$val)
	{
		self::approvalEnabled($row, $flag, $val);
		if ($val) {
			self::submitEnabled($row, $flag, $val);
			$val = !$val;
		}
	}


	static function submitEnabled(&$row, &$flag, &$val)
	{
		$val = false;
		// set $val to true to hide Submit button 
		if(! self::useApprovals()) {
			$val = true;
			return;
		}
		$threshold = (int)AppConfig::setting('company.quote_threshold');
		$margin = (int)AppConfig::setting('company.margin_threshold');
		$val = !(
				(($row->getField('amount_usdollar') >= $threshold)
				|| ($row->getField('margin') <= $margin))
				&& 'Approved' != $row->getField('approval')
			);
	}
	
	
	function get_view_closed_where_basic($param)
	{
		return $param['value'] ? '1' : "substring(quotes.quote_stage, 1, 7) != 'Closed '";
	}

	function getDefaultListWhereClause()
	{
		return "substring(quotes.quote_stage, 1, 7) != 'Closed '";
	}


	function get_view_closed_where_advanced()
	{
		return '1';
	}

	function get_search_stage_options()
	{
		global $app_list_strings, $mod_strings;
		return array_merge(array(''=>'', "Active" => $mod_strings['LBL_ACTIVE']), $app_list_strings['quote_stage_dom']);
	}


	function get_stage_where_advanced($param)
	{
		return ($param['value'] == 'Active') ? "substring(quotes.quote_stage, 1, 7) != 'Closed '" : ("quotes.quote_stage='" . PearDatabase::quote($param['value']) . "'");
	}

	function get_stage_where_basic($param)
	{
		return '1';
	}

	function search_by_number($param) {
		if (empty($param['value'])) {
			return 1;
		} else {
			return "(quotes.quote_number = '" . PearDatabase::quote($param['value']) . "' OR CONCAT(quotes.prefix, quotes.quote_number) = '" . PearDatabase::quote($param['value']) . "' ) ";
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

	static function add_view_popups(DetailManager $mgr) {
        require_bean('Account');
        Account::add_account_popup($mgr->getRecord(), 'billing_account_id', 'sales');
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

		if (empty($update['terms']))
            $update['terms'] = AppConfig::setting('company.invoice_default_terms', 'COD');
        
        $update['show_list_prices'] = AppConfig::setting('company.quotes_show_list') ? '1' : '0';
        $update['show_components'] = 'all';
        $update['valid_until'] = date('Y-m-d', strtotime('+30 day'));

        $upd->set($update);        
    }
    
    static function before_save(RowUpdate $upd) {
		$changes = $upd->getChanges();
		if (isset($changes['approval'])){
			if ($changes['approval'] == 'Approved')
				$upd->set('approved_user_id', AppConfig::current_user_id());
			else 
				$upd->set('approved_user_id', null);
		}
    }

    static function after_save(RowUpdate $upd) {
    	if($upd->new_record) {
			$opp_id = $upd->getField('opportunity_id');
			if($opp_id && ($opp = ListQuery::quick_fetch('Opportunity', $opp_id))) {
				$opp_up = RowUpdate::for_result($opp);
				if(! preg_match('~^Closed ~', $opp_up->getField('sales_stage'))) {
                    global $app_list_strings;
                    $stage = 'Proposal/Price Quote';
                    $forecast_cat = array_get_default($app_list_strings['sales_forecast_dom'], $stage, '');
                    $probability = array_get_default($app_list_strings['sales_probability_dom'], $stage, '');

                    $updated_fields = array('sales_stage' => $stage, 'forecast_category' => $forecast_cat, 'probability' => $probability);
					$opp_up->set($updated_fields);
					$opp_up->save();
				}
			}
    	}
    }

	function get_notification_recipients($nmgr)
	{
		if ($nmgr->getContext() != 'submit')
			return array($nmgr->loadAssignedUser());
		global $current_user;
		$approver = findApprover($current_user, $nmgr->updated_bean);
		if (!$approver)
			return array();
		$user_fields = array('receive_notifications', 'email1', 'email2', 'full_name', 'user_name');
		$user = array();
		foreach ($user_fields as $f) {
			$user[$f] = $approver->$f;
		}
		return array($user);
	}

    static function send_notification(RowUpdate $upd) {
        $vars = array(
            'QUOTE_SUBJECT' => array('field' => 'name', 'in_subject' => true),
            'QUOTE_STATUS' => array('field' => 'quote_stage'),
            'QUOTE_CLOSEDATE' => array('field' => 'valid_until'),
            'QUOTE_PROBLEM' => array('field' => 'approval_problem'),
            'QUOTE_DESCRIPTION' => array('field' => 'description')
        );

        
		if (isset($upd->updates['approval'])) {
			$context = null;
            $template_name = '';

            if ($upd->updates['approval'] == 'Approved') {
                $template_name = 'QuoteApproved';
            } elseif ($upd->updates['approval'] == 'Not Approved') {
                $template_name = 'QuoteRejected';
            } elseif ($upd->updates['approval'] == 'Submitted') {
				$template_name = 'QuoteSubmitted';                                                
				$context = 'submit';
            }

            if ($template_name != '') {
				$manager = new NotificationManager($upd, $template_name, $vars);
				$manager->setContext($context);
                $manager->sendMails();
            }
        } else {
            $manager = new NotificationManager($upd, 'QuoteAssigned', $vars);

            if ($manager->wasRecordReassigned())
                $manager->sendMails();
        }
	}

	static function get_new_activity_status(RowUpdate $upd) {
        $status = 'created';

		if ($upd->getField('opportunity_id')) {
			$status = array(
				'status' => 'created',
				'converted_to_type' => 'Opportunities',
				'converted_to_id' => $upd->getField('opportunity_id'),
			);
		}


        return $status;
    }

}
?>
