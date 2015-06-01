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
require_once('modules/Invoice/Invoice.php');

class InvoicePDF extends QuotePDF {
	var $focus_type = 'Invoice';
		
	function create_filename() {
		$mod_strings =& $this->mod_strings();
		$filename_pfx = $this->get_detail_title();
		if($this->pdf_type == 'statement') {
			if(! empty($mod_strings['LBL_FILENAME_STATEMENT']))
				$filename_pfx = $mod_strings['LBL_FILENAME_STATEMENT'];
		}
		else if(! empty($mod_strings['LBL_FILENAME_INVOICE']))
			$filename_pfx = $mod_strings['LBL_FILENAME_INVOICE'];
		$filename = $filename_pfx . '_' . $this->focus->getField('prefix');
		$filename .= $this->focus->getField('invoice_number') . '_' . $this->focus->getField('billing_account_name');
		$filename = $filename . '.pdf';
		return $filename;
	}
	
	function get_detail_title() {
		$s =& $this->mod_strings();
		if($this->pdf_type == 'statement')
			return $s['LBL_PDF_STATEMENT_TITLE'];
		return $s['LBL_PDF_INVOICE_TITLE'];
	}
	
	function get_grand_totals_title() {
		$s =& $this->mod_strings();
		if($this->pdf_type == 'statement')
			return $s['LBL_STATEMENT_TOTALS'];
		return $s['LBL_GRAND_TOTALS'];
	}
	
	function get_detail_fields() {
		global $app_list_strings, $timedate;
		$row =& $this->row;

		$date_label = 'LBL_PDF_INVOICE_DATE';
		$number_label = 'LBL_PDF_INVOICE_NUMBER';
		

		$fields = array(
			$number_label => $row['prefix'] . $row['invoice_number'],
			'LBL_PDF_PURCHASE_ORDER' => $row['purchase_order_num'],
			$date_label => $timedate->to_display_date($row['invoice_date'], false),
			'LBL_PDF_TERMS' => $app_list_strings['terms_dom'][$row['terms']],
			'LBL_PDF_DUE_DATE' => $timedate->to_display_date($row['due_date'], false),
		);
		$tax_info = $this->_company_tax_info();
		if(! empty($tax_info))
			$fields['LBL_PDF_TAX_INFO'] = $tax_info;
		
		return $fields;
	}
	
	function get_terms() {
		$terms = from_html(trim(AppConfig::setting('company.std_invoice_terms', '')));
		return $terms;
	}
	
	function get_note_title() {
		$s =& $this->mod_strings();
		return $s['LBL_PREPARED_INVOICE'];
	}

	function get_email_title() {
		$s =& $this->mod_strings();
		return $s['LBL_EMAILED_INVOICE'];
	}
	
	function print_extra_foot() {
		global $app_list_strings;
		$currency =& $this->get_currency();
		$symbol = $currency->symbol;
		$mod_strings =& $this->mod_strings();		
		$cols = array(
			'payment_date' => array(
				'title' => $mod_strings['LBL_PAYMENT_DATE'],
				'width' => '15%',
			),
			'payment_id' => array(
				'title' => $mod_strings['LBL_PDF_PAYMENT_ID'],
				'width' => '15%',
			),
			'payment_type' => array(
				'title' => $mod_strings['LBL_PDF_PAYMENT_TYPE'],
				'width' => '15%',
			),
			'customer_reference' => array(
				'title' => $mod_strings['LBL_PDF_PAYMENT_CUSTOMER_REFERENCE'],
				'width' => '27%',
			),
			'amount' => array(
				'title' => $mod_strings['LBL_AMOUNT'],
				'width' => '14%',
				'text-align' => 'right',
			),
			'allocated' => array(
				'title' => $mod_strings['LBL_PDF_PAYMENT_ALLOCATED'],
				'width' => '14%',
				'text-align' => 'right',
			),
		);
		
		if ($this->pdf_type == 'statement') {
			$lq = new ListQuery('Invoice', true, array('link_name' => 'payments'));
			$lq->setParentKey($this->focus->getField('id'));
			$lq->addField('~join.amount', 'allocated');
			$res = $lq->runQuery();
			$payments = $res->getRows();
			
			$data = array();
			$payments_total = 0;
			global $timedate;
			foreach ($payments as $payment) {
				$data[] = array(
					'payment_date' => $timedate->to_display_date($payment['payment_date'], false),
					'payment_id' => $payment['prefix'] . $payment['payment_id'],
					'amount' => $this->currency_format($payment['amount'], $currency->id),
					'allocated' => $this->currency_format($payment['allocated'], $currency->id),
					'payment_type' => $app_list_strings['payment_type_dom'][$payment['payment_type']],
					'customer_reference' => $payment['customer_reference'],
				);
				$payments_total += $payment['allocated'];
			}

			$table_title = $mod_strings['LBL_PDF_PAYMENTS_CREDITED'];

			$this->moveY('20pt');
			$opts = array('border' => 0);
			$this->DrawTable($data, $cols, $table_title, true, $opts);
			$cols = $this->get_totals_columns();
			$cols['value']['font-weight'] = 'bold';
			$data = array();
			$data[] = array(
				'name' => "{$mod_strings['LBL_BALANCE_DUE']}",
				'value' => $this->currency_format($this->row['amount_due'], $currency->id),
			);
			$this->moveY('10pt');
			$opts = array('border' => 0, 'font-size' => 10);
			$this->DrawTable($data, $cols, false, false, $opts);
		}

	}

	function _address($pfx, $altfocus=null) {		
		if($altfocus)
			$focus =& $altfocus;
		else
			$focus =& $this->focus;
		
		$ret = parent::_address($pfx, $altfocus);
		$phone = $pfx."phone";
		$email = $pfx."email";
		$ret[] = $focus->getField($phone);
		$ret[] = $focus->getField($email);
		
		return $ret;
	}
	
	function get_config_title() {
		if($this->pdf_type == 'statement')
			return translate('LBL_PDF_STATEMENT');
		return translate('LBL_PDF_INVOICE');
	}

    function handle_mass_request($ids = null) {
        if (!isset($ids)) {
            if (isset($_POST['uid'])) {
                $ids = explode(',', $_POST['uid']);
            } else {
                $ids = array($_REQUEST['record']);
            }
        }
        foreach($ids as $id) {
            $this->startPageGroup();
            $lq = $this->createListQuery($id);
            $focus = $lq->runQuerySingle();
            if(!$focus) {
                sugar_die("Record ID missing or unknown");
            }
            if (!ACLController::checkAccess($focus->getModuleDir(), 'view', AppConfig::current_user_id())) {
                ACLController::displayNoAccess(true);
                sugar_cleanup(true);
            }
            $this->do_print($focus);
        }
        ob_clean();
        $this->serve_dynamic(true);
    }

    static function listupdate_perform($mu, $perform, &$listFmt, &$list_result, $uids) {
        if ($perform == 'PrintInvoices') {
            $pdf = new self;
            $pdf->handle_mass_request($uids);
        }
    }
}
?>
