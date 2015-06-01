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

class MonthlyService extends SugarBean {

	// Stored fields
	var $id;
	var $instance_number;
	var $booking_category_id;
	var $account_id;
	var $quote_id;
	var $purchase_order_num;	
	var $invoice_value;
	var $total_sales;
	var $balance_due;
	var $start_date;
	var $end_date;
	var $billing_day;
	var $invoice_terms;
	var $paid_until;
	var $contact_id;
	var $cc_user_id;
	var $created_by;
	var $modified_user_id;
	var $date_modified;
	var $date_entered;
	var $frequency;
	var $next_invoice;
	var $assigned_user_id;
	var $address_id;
	
	// Looked up
	var $account_name;
	var $quote_name;
	var $booking_category_name;
	var $cc_user_name;

	var $assigned_user_name;
	
	var $name;	
	
	var $object_name = 'MonthlyService';
	var $module_dir = 'MonthlyServices';
	var $new_schema = true;
	
	var $table_name = "monthly_services";
	var $rel_user_table = "users";

	// This is used to retrieve related fields from form posts.
	var $additional_column_fields = Array(
		'booking_category_name',
		'account_name',	
		'cc_user_name'
	);

	var $relationship_fields = array();	


	/*function save($check_notify = false, $update_balance = true) {
		$this->unformat_all_fields();
		
		if (empty($this->id) || !empty($this->new_with_id))
			$this->instance_number = $this->getNextSequenceValue();
		if($this->start_date) {
			$start_date = $GLOBALS['timedate']->to_db_date($this->start_date, false);
			if(preg_match('~\d{4}-\d{2}-(\d{2})~', $start_date, $m))
				$this->billing_day = $m[1];
		}
		
		return parent::save($check_notify);
	}*/
	

	function getCurrentSequenceValue() {
		return AppConfig::current_sequence_value('mo_service_number_sequence');
	}
	
	function getNextSequenceValue() {
		return AppConfig::next_sequence_value('mo_service_number_sequence');
	}	
	
	function getSearchNameOptions($add_blank=true) {
		require_bean('BookingCategory.php');
		$serviceName = new BookingCategory();
		return $serviceName->get_option_list('services-monthly', $add_blank);
	}
	
	function getAllBillingToday() {
		$lq = new ListQuery('MonthlyService');
		$allFields = $lq->base_model->getFieldDefinitions();
		foreach ($allFields as $f) {
			if ($f['source']['type'] == 'db')
				$lq->addField($f['name']);
		}
		$lq->addField('booking_category.name', 'booking_category_name');
		$lq->addField('account.name', 'account_name');
		$lq->addField('cc_user.email1', 'cc_user_email');
		$lq->addField('contact.email1', 'contact_email');
		$lq->addField(
			array(
				'name' => 'cc_user_name',
				'type' => 'varchar',
				'source' => array (
					'type' => 'literal',
					'value' => "IFNULL( CONCAT(cc_user.`first_name`, ' ', cc_user.`last_name`), cc_user.`user_name`)",
				),
			)
		);
		$lq->addField(
			array(
				'name' => 'contact_name',
				'type' => 'varchar',
				'source' => array (
					'type' => 'literal',
					'value' => "CONCAT(contact.salutation, ' ', contact.`first_name`, ' ', contact.`last_name`)",
				),
			)
		);
		$lq->addField(
			array(
				'name' => 'invoice_start_date',
				'type' => 'varchar',
				'source' => array (
					'type' => 'literal',
					'value' => "DATE_FORMAT(NOW(), '%Y-%m-%d')",
				),
			)
		);
		$lq->addField(
			array(
				'name' => 'invoice_end_date',
				'type' => 'varchar',
				'source' => array (
					'type' => 'literal',
					'value' => "DATE_FORMAT( ADDDATE(
									CASE frequency
										WHEN 'annually' THEN
											ADDDATE(NOW(), INTERVAL 1 YEAR)
										WHEN 'quarterly' THEN
											ADDDATE(NOW(), INTERVAL 3 MONTH)
										ELSE
											ADDDATE(NOW(), INTERVAL 1 MONTH)
								END, INTERVAL -1 DAY), '%Y-%m-%d' )",
				),
			)
		);
		$lq->addFilterClause(" IFNULL(next_invoice, 0) <= NOW() ");
		$lq->addFilterClause(" end_date >= NOW() ");
		$lq->addFilterClause(" start_date <= NOW() ");
		$result = $lq->runQuery();
		return $result->rows;

	}

	function updateInvoiceDetails($id, $invoiceId, $amountDue, $promoteDate = false) {
		$result = ListQuery::quick_fetch('MonthlyService', $id);
		if ($result) {
			$monthlyService = RowUpdate::for_result($result);
			$monthlyService->set('total_sales', $monthlyService->getField('total_sales') + $amountDue);
			$monthlyService->set('balance_due', $monthlyService->getField('balance_due') + $amountDue);

			if ($promoteDate) {
				$last = $monthlyService->getField('next_invoice');
				if (!$last || $last == '0000-00-00') {
					$last = $monthlyService->getField('start_date');
				}

				$start_date = $monthlyService->getField('start_date');
				list(,,$d) = explode('-', $start_date);
				list($y, $m) = explode('-', $last);
				switch ($monthlyService->getField('frequency')) {
					case 'annually':
						$y++;
						break;
					case 'quarterly':
						$m += 3;
						break;
					default:
						$m ++;
						break;
				}
				if ($m > 12) {
					$m -= 12;
					$y ++;
				}
				$nDays = date('t', strtotime(sprintf('%04d-%02d-01', $y, $m)));
				if ($d > $nDays) $d = $nDays;
				$next = sprintf('%04d-%02d-%02d', $y, $m, $d);
				$monthlyService->set('next_invoice', $next);
			}
			$monthlyService->save();
			$monthlyService->addUpdateLink('invoices', $invoiceId);
		}			
	}
	
	static function updateBalances($invoiceId) {
		$invoice = ListQuery::quick_fetch('Invoice', $invoiceId);
		if (!$invoice)
			return;

		$lq = new ListQuery('Invoice', null, array('link_name' => 'monthlyservices'));
		$lq->setParentKey($invoiceId);
		$lq->addSimpleFilter('~join.invoice_id', $invoiceId);
		$result = $lq->runQuery();

		foreach ($result->getRowResults() as $service) {
			$upd = RowUpdate::for_result($service);
			if ($invoice->getField('amount_due_usdollar') == 0) {
				$dateEntered = $invoice->getField('date_entered');
				list($y, $m, $d) = explode('-', $dateEntered);
				list($d) = explode(' ', $d);
				$nDaysInvoice = date('t', strtotime(sprintf('%04d-%02d-01', $y, $m)));
				if ($d == 1) {
					switch ($service->getField('frequency')) {
						case 'annually':
							$m += 11;
							break;
						case 'quarterly':
							$m += 2;
							break;
						default:
							break;
					}
					if ($m > 12) {
						$m -= 12;
						$y++;
					}
					$nDaysUntil = date('t', strtotime(sprintf('%04d-%02d-01', $y, $m)));
					$d = $nDaysUntil;
				} else {
					switch ($service->getField('frequency')) {
						case 'annually':
							$y++;
							break;
						case 'quarterly':
							$m += 3;
							break;
						default:
							$m ++;
							break;
					}
					$nDaysUntil = date('t', strtotime(sprintf('%04d-%02d-01', $y, $m)));
					$d--;
					if ($d > $nDaysUntil) {
						$d = $nDaysUntil;
					}
				}
				$upd->set('paid_until',  sprintf('%04d-%02d-%02d', $y, $m, $d));
			}

			$upd->save(); // this will update balances
		}
	}
	
	static function getInvoicesBalance($serviceId)
	{
		$lq = new ListQuery('services_invoices');
		$lq->addField(
			array(
				'name' => 'balance',
				'type' => 'double',
				'source' => array (
					'type' => 'literal',
					'value' => "IFNULL(SUM(invoice.amount_due_usdollar), 0)",
				),
			)
		);
		$lq->addField(
			array(
				'name' => 'total_balance',
				'type' => 'double',
				'source' => array (
					'type' => 'literal',
					'value' => "IFNULL(SUM(invoice.amount_usdollar), 0)",
				),
			)
		);
		$lq->addSimpleFilter('invoice.cancelled', 0);
		$lq->addSimpleFilter('service_id', $serviceId);

		$result = $lq->runQuerySingle();
		$data = array('balance_due' => $result->getField('balance'), 'total_sales' => $result->getField('total_balance'));
		return $data;
	}

    static function before_save(RowUpdate &$update) {
        if (! $update->new_record) {
            $data = self::getInvoicesBalance($update->getPrimaryKeyValue());
			$update->set($data);
		}
    }
	
	static function after_add_link($parent, $link)
	{
		$invoiceId = null;
		$parentName = $parent->getModelName();
		if ($parentName == 'Invoice') {
			$invoiceId = $parent->getPrimaryKeyValue();
		} else if (($parentName == 'MonthlyService') && ($link == 'invoices')) {
			$invoiceId = $parent->link_update->saved['invoice_id'];
		}
		if ($invoiceId)
			self::updateBalances($invoiceId);
	}

    static function init_record(RowUpdate &$upd, $input) {
        $update = array();

        $update['frequency'] = 'monthly';
        $update['invoice_terms'] = 'COD';
        require_bean('CompanyAddress');
        $update['address_id'] = CompanyAddress::getMainWarehouseId();

        $upd->set($update);
    }
}
?>
