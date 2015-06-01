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
require_once('modules/Bills/Bill.php');

class BillPDF extends QuotePDF {
	var $focus_type = 'Bill';
	
	function create_filename() {
		$mod_strings =& $this->mod_strings();
		$filename_pfx = $this->get_detail_title();
		if(! empty($mod_strings['LBL_FILENAME_BILL']))
			$filename_pfx = $mod_strings['LBL_FILENAME_BILL'];
		$filename = $filename_pfx . '_' . $this->focus->getField('prefix');
		$filename .= $this->focus->getField('bill_number') . '_' . $this->focus->getField('supplier.name');
		$filename = $filename . '.pdf';
		return $filename;
	}
	
	function createListQuery($id)
	{
		$lq = parent::createListQuery($id);
		$lq->addField('supplier.name');
		$lq->addField('supplier_contact.name');
		return $lq;
	}
	
	function get_detail_title() {
		return translate('LBL_PDF_BILL_TITLE');
	}
	
	function get_config_title() {
		return translate('LBL_PDF_BILL');
	}
	
	function get_detail_fields() {
		global $app_list_strings, $timedate;
		$row =& $this->row;
		$user = ListQuery::quick_fetch('User', $row['assigned_user_id']);
		$fields = array(
			'LBL_PDF_BILL_NUMBER' => $row['prefix'] . $row['bill_number'],
			'LBL_PDF_INVOICE_NUMBER' => $row['invoice_reference'],
			'LBL_PDF_BILL_DATE' => $timedate->to_display_date($row['bill_date'], false),
			'LBL_PDF_TERMS' => $app_list_strings['terms_dom'][$row['terms']],
			'LBL_PDF_DUE_DATE' => $timedate->to_display_date($row['due_date'], false),
			'LBL_PDF_BUYER' => $user ? $user->getField('name') : '',
		);
		$tax_info = $this->_company_tax_info();
		if(! empty($tax_info))
			$fields['LBL_PDF_TAX_INFO'] = $tax_info;
		return $fields;
	}
	
	function get_addresses() {
		$acc = ListQuery::quick_fetch('Account', $this->focus->getField('supplier_id'));
		if ($acc) {
			$acc->assign('billing_contact_name', $this->focus->getField('supplier_contact.name'));
			$acc->assign('billing_account_name', $acc->getField('name'));
			$supp = $this->_address('billing_', $acc);
			$tax_information = $this->focus->getField('tax_information');
			if(! empty($tax_information))
				$supp[] = $tax_information;
			return array(
				'LBL_PDF_SUPPLIER' => $supp,
			);
		}
		return array();
	}
	
	function get_terms() {
		//$settings = $this->get_admin_settings('company');
		//$terms = trim(array_get_default($settings, 'company_std_invoice_terms', ''));
		$terms = '';
		return $terms;
	}
	
	function get_note_title() {
		$s =& $this->mod_strings();
		return $s['LBL_PREPARED_BILL'];
	}

	function get_email_title() {
		$s =& $this->mod_strings();
		return $s['LBL_EMAILED_BILL'];
	}
	
	function get_main_columns() {
		$cols = parent::get_main_columns();
		$mod_strings =& $this->mod_strings();
		$cols2 = array();
		foreach($cols as $idx => $v) {
			$cols2[$idx] = $v;
			if($idx == 'product') {
				$cols2[$idx]['width'] = '40%';
				$cols2['mfr_part_no'] = array(
					'title' => $mod_strings['LBL_PDF_PART_NO'],
					'width' => '18%',
				);
			}
		}
		return $cols2;
	}
	
	function print_extra_foot() {
		$currency =& $this->get_currency();
		$symbol = $currency->symbol;
		$mod_strings =& $this->mod_strings();		
		$cols = array(
			'payment_date' => array(
				'title' => $mod_strings['LBL_PAYMENT_DATE'],
				'width' => '80pt',
			),
			'payment_id' => array(
				'title' => $mod_strings['LBL_PDF_PAYMENT_ID'],
				'width' => '70pt',
			),
			'amount' => array(
				'title' => $mod_strings['LBL_AMOUNT'],
				'width' => '80pt',
			),
			'payment_type' => array(
				'title' => $mod_strings['LBL_PDF_PAYMENT_TYPE'],
				'width' => '90pt',
			),
			'customer_reference' => array(
				'title' => $mod_strings['LBL_PDF_PAYMENT_CUSTOMER_REFERENCE'],
				'width' => '150pt',
			),
		);
		
		//$groups =& $this->focus->get_line_groups();
		//$gtotal = $groups['GRANDTOTAL']->total;

		/*	
		if ($_REQUEST['pdf_type'] == 'statement') {
			$payments = $this->focus->get_payments();
			$data = array();
			$payments_total = 0;
			foreach ($payments as $payment) {
				$data[] = array(
					'payment_date' => $payment['payment_date'],
					'payment_id' => $payment['prefix'] . $payment['payment_id'],
					'amount' => $symbol . format_number($payment['amount'], 2, 2),
					'payment_type' => $app_list_strings['payment_type_dom'][$payment['payment_type']],
					'customer_reference' => $payment['customer_reference'],
				);
				$payments_total += $payment['amount'];
			}
			$data[] = array(
				'payment_date' => '',
				'amount' => '',
			);
			$data[] = array(
				'payment_date' => "{$mod_strings['LBL_BALANCE_DUE']}",
				'amount' => $symbol . format_number($gtotal - $payments_total, 2, 2),
			);
			$this->moveY('10pt');
			$this->setX($this->lv('-470pt'));
			$opts = array('border' => 0);
			$this->DrawTable($data, $cols, $mod_strings['LBL_PDF_PAYMENTS_CREDITED'], true, $opts);
		} 
		 */

	}
}


?>
