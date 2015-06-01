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
require_once('modules/Payments/Payment.php');

class StatementPDF extends QuotePDF {
	var $focus_type = 'Account';
	var $ids;

	function create_filename() {
		$mod_strings =& $this->mod_strings();
		if(count($this->ids) > 1)
			$filename = $mod_strings['LBL_FILENAME_MULTI_ACCOUNT_STATEMENT'];
		else {
			$filename_pfx = $mod_strings['LBL_FILENAME_ACCOUNT_STATEMENT'];
			$filename = $filename_pfx . '_' . $this->focus->getField('name');
		}
		$filename = $filename . '.pdf';
		return $filename;
	}
	
	function get_detail_title() {
		$mod_strings =& $this->mod_strings();
		return $mod_strings['LBL_PDF_ACCOUNT_STATEMENT'];
	}
	
	function get_addresses() {
		$this->focus->assign('billing_account_name', $this->focus->getField('name'));
		$this->focus->assign('shipping_account_name', $this->focus->getField('name'));
		return parent::get_addresses();
	}
		
	function print_details() {
		$pos = $this->layout->getDetailsPosition($this);
		if (empty($pos)) {
			return;
		}
		$this->setXY($pos['x'], $pos['y']);
		$data = array(array('text' => $this->get_detail_title()));
		$opts = array(
			'border' => 0,
			'padding' => 1,
			'font-size' => 14,
			'font-weight' => 'bold',
			'text-align' => 'center'
		);
		$this->CellRow($data, $opts);
		$this->moveY('25pt');
	}
	
	function print_main() {
		$mod_strings =& $this->mod_strings();
        $invoice_cols = array(
            'number' => array(
            	'title' => $mod_strings['LBL_PDF_INVOICE_NUMBER'],
            	'width' => '15%',
            ),
            'date' => array(
            	'title' => $mod_strings['LBL_PDF_DATE_DUE'],
            	'width' => '15%',
            ),
            'subject' => array(
            	'title' => $mod_strings['LBL_PDF_INVOICE_SUBJECT'],
            	'width' => '50%',
            ),
            'amount' => array(
            	'title' => $mod_strings['LBL_PDF_INVOICE_AMOUNT'],
            	'width' => '20%',
            ),
		);

		$credit_cols = $invoice_cols;
		$credit_cols['number']['title'] = $mod_strings['LBL_PDF_CREDIT_NUMBER'];
		$credit_cols['subject']['title'] = $mod_strings['LBL_PDF_CREDIT_SUBJECT'];

        $invoice_opts = array('border' => 0);

        $payment_cols = array(
            'payment_date' => array(
            	'title' => $mod_strings['LBL_PDF_PAYMENT_DATE'],
            	'width' => '20%',
            ),
            'payment_id' => array(
            	'title' => $mod_strings['LBL_PDF_PAYMENT_ID'],
            	'width' => '20%',
            ),
            'customer_reference' => array(
            	'title' => $mod_strings['LBL_PDF_PAYMENT_CUSTOMER_REFERENCE'],
            	'width' => '20%',
            ),
            'payment_type' => array(
            	'title' => $mod_strings['LBL_PDF_PAYMENT_TYPE'],
            	'width' => '20%',
            ),
            'amount' => array(
            	'title' => $mod_strings['LBL_PDF_PAYMENT_AMOUNT'],
            	'width' => '20%',
            ),
        );
        $payment_opts = array('border' => 0);

        $last_currency = 0;
        $single_currency = true;
        $balance = $balance_usdollar = 0;
        
        $lq = new ListQuery('Account', null, array('link_name' => 'invoice', 'parent_key' => $this->focus->getField('id')));
        $lq->addSimpleFilter('cancelled', null, 'false');
        $lq->addSimpleFilter('amount_due', null, 'non_zero');
        $invoices = $lq->fetchAllObjects();

        $lq = new ListQuery('Account', null, array('link_name' => 'credits', 'parent_key' => $this->focus->getField('id')));
        $lq->addSimpleFilter('cancelled', null, 'false');
        $lq->addSimpleFilter('amount_due', null, 'non_zero');
		$lq->addSimpleFilter('apply_credit_note', null, 'false');
		$credits = $lq->fetchAllObjects();
        
        $invoices += $credits;

        $this->moveY('20pt');

        $currency = new Currency;
        $currency->retrieve(-99);
		$symbol = $default_symbol = $currency->getPdfCurrencySymbol();
		$currency_id = $default_currency_id = $currency->id;
        $orig_lm = $this->lMargin;

		foreach ($invoices as $invoice) {
            $currency = new Currency();
            $currency->retrieve($invoice->currency_id, false);
            if ($last_currency && ($last_currency !== $currency->id)) {
                $single_currency = false;
            }
            $last_currency = $currency->id;
			$symbol = $currency->getPdfCurrencySymbol();
			$currency_id = $currency->id;
			$number_field = $invoice->object_name == 'CreditNote' ? 'credit_number' : 'invoice_number';
            $data = array(array(
                'date' => $invoice->due_date,
                'number' => $invoice->prefix.$invoice->$number_field,
                'subject' => $invoice->name,
                'amount' => $this->currency_format($invoice->amount, $currency->id),
            ));
			$this->setX($this->lMargin);
			$cols = $invoice->object_name == 'CreditNote' ? $credit_cols : $invoice_cols;
			$this->DrawTable($data, $cols, '', true, $invoice_opts);
			if ($invoice->object_name == 'CreditNote') {
				$payments = CreditNote::get_payments($invoice->id, $invoice->currency_id);
				$notes = array();
			} else {
				$notes = Invoice::get_attached_credit_notes($invoice->id, null, null);
				$payments = Invoice::get_payments($invoice->id, $invoice->currency_id);
			}
            $total_paid = 0;

			$this->lMargin = $orig_lm + $this->lv('15%');
			$this->setX($this->lMargin);
			if (count($payments) || count($notes)) {
				if (count($payments)) {
	                foreach ($payments as $i => $payment) {
		                $total_paid += $payment['allocated'];
			            $payments[$i]['amount'] = $this->currency_format($payment['allocated'], $currency->id);
						$payments[$i]['payment_type'] = $payment['payment_type'];
						$payments[$i]['payment_id'] = $payment['prefix'] . $payment['payment_id'];
						$payments[$i]['customer_reference'] = $payment['customer_reference'];
					}
					$title = $invoice->object_name == 'CreditNote' ? 'LBL_PDF_REFUND_PAYMENTS': 'LBL_PDF_PAYMENTS';
					$title = array('text' => $mod_strings[$title], 'font-size' => '12pt');
					$this->DrawTable($payments, $payment_cols, $title, true, $payment_opts);
				}

				if (count($notes)) {
					$data = array();
					foreach ($notes as $note) {
						$data[] = array(
			                'date' => $note['due_date'],
						    'number' => $note['prefix'].$note['credit_number'],
			                'subject' => $note['name'],
			                'amount' => $this->currency_format($note['amount'], $currency->id),
						);
		                $total_paid += $note['amount'];
					}
					$title = array('text' => $mod_strings['LBL_PDF_CREDITS_APPLIED'], 'font-size' => '12pt');
					$this->DrawTable($data, $credit_cols, $title, true, $invoice_opts);
				}
			} else {
				$data = array(array('text' => $mod_strings['LBL_PDF_NO_PAYMENTS']));
				$opts = array(
					'font-size' => 10,
					//'font-weight' => 'bold',
					'allow-page-break' => true,
				);
				$this->CellRow($data, $opts);
            }
            $this->lMargin = $orig_lm;
			$amount_due = $invoice->amount - $total_paid;

			if ($invoice->object_name == 'CreditNote') {
				$amount_due = -$amount_due;
			}

            $balance += $amount_due;
            $balance_usdollar += $currency->convertToDollar($amount_due);

			$this->moveY('10pt');
			$lbl = $mod_strings[$amount_due >= 0 ? 'LBL_PDF_AMOUNT_DUE' : 'LBL_PDF_AMOUNT_CREDITED'] . translate('LBL_SEPARATOR', 'app');
			$data = array(array('text' => $lbl . $this->currency_format($amount_due, $currency->id)));
			$opts = array(
				'font-size' => 11,
				'text-align' => 'right',
				'allow-page-break' => true,
			);
			if ($amount_due < 0) {
				$opts['color'] = array(255,0,0);
			}
			$this->CellRow($data, $opts);

			$this->RuleLine(array('padding' => '10pt'));
        }

		$text = $mod_strings['LBL_PDF_BALANCE'];
		$opts = array(
			'font-size' => 11,
			'font-weight' => 'bold',
			'text-align' => 'right',
			'allow-page-break' => true,
		);
        if ($single_currency)
        	$text .= $this->currency_format($balance, $currency_id);
        else
			$text .= $this->currency_format($balance_usdollar, $default_currency_id);
        $data = array(array('text' => $text));
        $this->CellRow($data, $opts);
	}
	
	function print_extra_foot() {
	}

	function do_print(&$focus) {
		$this->set_focus($focus);
		$this->filename = $this->create_filename();
		$this->new_page();
		$this->print_logo();
		if(method_exists($this->layout, 'printCompanyAddress'))
			$this->layout->printCompanyAddress($this);
		$this->print_details();
		$this->print_extra_head();
		if($this->layout_name == 'QuotePDF')
			$this->print_addresses();
		else
			$this->layout->print_addresses($this);
		$this->print_main();
		//$this->print_notes();
		$this->print_terms();
		$this->print_extra_foot();
	}
	
	function get_terms() {
		global $current_language;
		$quote_strings = return_module_language($current_language, 'Quotes');
		$terms = '';
		$tax_info = $this->_company_tax_info();
		if(! empty($tax_info))
			$terms = $quote_strings['LBL_PDF_TAX_INFO'] . ':  ' . $tax_info;
		return $terms;
	}
	
	function handle_request($ids = null) {
		if (!isset($ids)) {
			if (isset($_POST['uid'])) {
				$ids = explode(',', $_POST['uid']);
			} else {
				$ids = array($_REQUEST['record']);
			}
		}
		$this->ids = $ids;
		foreach($ids as $id) {
			$this->startPageGroup();
			$focus = ListQuery::quick_fetch($this->focus_type, $id);
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
		if ($perform == 'PrintStatements') {
			$pdf = new self;
			$pdf->handle_request($uids);
		}
	}
}

?>
