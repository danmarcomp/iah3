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


require_once('modules/Quotes/QuotePDF.php');
require_once('modules/Payments/Payment.php');
require_once('modules/Invoice/Invoice.php');

class PaymentPDF extends QuotePDF {
	var $focus_type = 'Payment';
	
	function create_filename() {
		$mod_strings =& $this->mod_strings();
		$filename_pfx = $this->get_detail_title();
		if(! empty($mod_strings['LBL_FILENAME_RECEIPT']))
			$filename_pfx = $mod_strings['LBL_FILENAME_RECEIPT'];
		$filename = $filename_pfx . '_' . $this->focus->getField('prefix');
		$filename .= $this->focus->getField('payment_id') . '_' . $this->focus->getField('account_name');
		$filename = $filename . '.pdf';
		return $filename;
	}
	
	function get_config_title() {
		if ($this->pdf_type == 'advice')
			return translate('LBL_PDF_ADVICE');
		return translate('LBL_PDF_RECEIPT');
	}
	
	function get_detail_title() {
		$s =& $this->mod_strings();
		return $s['LBL_PDF_RECEIPT'];
	}
	
	function get_detail_fields() {
		global $app_list_strings;
		$row =& $this->row;
		$currency =& $this->get_currency();
		$fields = array(
			'LBL_PDF_PAYMENT_ID' => $row['prefix'] . $row['payment_id'],
			'LBL_PDF_CUSTOMER_REFERENCE' => $row['customer_reference'],
			'LBL_PDF_PAYMENT_DATE' => $row['payment_date'],
			'LBL_PDF_CURRENCY' => $currency->name.': '.$currency->symbol,
			'LBL_PDF_AMOUNT' => $this->currency_format($row['amount']),
			'LBL_PDF_PAYMENT_TYPE' => $app_list_strings['payment_type_dom'][$row['payment_type']],
		);
		return $fields;
	}
	
	function get_addresses() {
		$acc = ListQuery::quick_fetch('Account', $this->focus->getField('account_id'));
		if ($acc) {
			$acc->assign('billing_account_name', $acc->getField('name'));
			if ($this->focus->getField('direction') == 'outgoing') {
				$supp = $this->_address('billing_', $acc);
				$tax_information = $this->focus->getField('tax_information');
				if(! empty($tax_information))
					$supp[] = $tax_information;
				return array(
					'LBL_PDF_SUPPLIER' => $supp,
				);
			}
			$acc->assign('shipping_account_name', $acc->getField('name'));
			$bill = $this->_address('billing_', $acc);
			$tax_information = $acc->getField('tax_information');
			if(! empty($tax_information))
				$bill[] = $tax_information;
			$ship = $this->_address('shipping_', $acc);
			return array(
				'LBL_PDF_BILL_TO' => $bill,
				'LBL_PDF_SHIP_TO' => $ship,
			);
		}
		return array();
	}
	
	function get_terms() {
		/*$settings = $this->get_admin_settings('company');
		$terms = trim(array_get_default($settings, 'company_std_invoice_terms', ''));
		return $terms;*/
	}
	
	function get_note_title() {
		$s =& $this->mod_strings();
		return $s['LBL_PREPARED_RECEIPT'];
	}

	function get_email_title() {
		$s =& $this->mod_strings();
		return $s['LBL_EMAILED_RECEIPT'];
	}
	
	function get_grand_totals_title() {
    	$mod_strings =& $this->mod_strings();
    	return $mod_strings['LBL_PDF_TOTAL_ALLOCATED'];
    }
	
	function print_main() {
		global $timedate;
		$currency =& $this->get_currency();
		$symbol = $currency->symbol;
		$mod_strings =& $this->mod_strings();		
		$cols = array(
			'number' => array(
				'title' => $mod_strings['LBL_LIST_NUMBER'],
				'width' => '20%',
			),
			'subject' => array(
				'title' => $mod_strings['LBL_LIST_SUBJECT'],
				'width' => '20%',
			),
			'amount' => array(
				'title' => $mod_strings['LBL_PDF_AMOUNT'],
				'width' => '15%',
				'text-align' => 'right',
			),
			'amount_due' => array(
				'title' => $mod_strings['LBL_LIST_AMOUNT_DUE'],
				'width' => '15%',
				'text-align' => 'right',
			),
			'allocated' => array(
				'title' => $mod_strings['LBL_PDF_ALLOCATED'],
				'width' => '15%',
				'text-align' => 'right',
			),
			'due_date' => array(
				'title' => $mod_strings['LBL_LIST_DUE_DATE'],
				'width' => '15%',
			),
		);
		$opts = array('border' => 0);

		$data = array();
		$items = Payment::query_line_items($this->focus->getField('id'), true);
		$items_key = ($this->focus->getField('direction') == 'incoming') ? 'invoices' : 'bills';
		foreach($items[$items_key] as $key => $value) {
			$data[] = array(
				'number' => $value['invoice_no'],
				'subject' => $value['invoice_name'],
				'amount' => $this->currency_format($value['invoice_amount'], $currency->id),
				'amount_due' => $this->currency_format($value['invoice_amount_due'], $currency->id),
				'allocated' => $this->currency_format($value['amount'], $currency->id),
				'due_date' => $timedate->to_display_date($value['invoice_due_date'], false),
			);
		}
		$this->moveY('10pt');
		$this->DrawTable($data, $cols, '', true, $opts);

		$this->RuleLine(array('padding' => '15pt'));
		
		$cols = array(
			'total' => array(
				'width' => '100%',
				'text-align' => 'right',
			),
		);
		$opts = array('border' => 0, 'font-weight' => 'bold');
		$data = array(array('total' => $this->currency_format($this->focus->getField('amount'), $currency->id)));
		$title = array('text' => $mod_strings['LBL_PDF_TOTAL_ALLOCATED'], 'font-size' => 12);
		$this->DrawTable($data, $cols, $title, false, $opts);
	}
	
	function do_print(&$focus) {
		if($focus->getField('direction') == 'outgoing')
			$this->alt_lang_module = 'PaymentsOut'; // load alternate mod_strings
		parent::do_print($focus);
	}
	
	function print_extra_foot() {
	}
}


?>
