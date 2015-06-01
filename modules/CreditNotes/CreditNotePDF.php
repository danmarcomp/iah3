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


require_once 'modules/Quotes/QuotePDF.php';

class CreditNotePDF extends QuotePDF {
	var $focus_type = 'CreditNote';
		
	function create_filename() {
		$mod_strings =& $this->mod_strings();
		$filename_pfx = $this->get_detail_title();
		if(! empty($mod_strings['LBL_FILENAME_CREDIT']))
			$filename_pfx = $mod_strings['LBL_FILENAME_CREDIT'];
		$filename = $filename_pfx . '_' . $this->focus->getField('prefix');
		$filename .= $this->focus->getField('credit_number') . '_' . $this->focus->getField('billing_account_name');
		$filename = $filename . '.pdf';
		return $filename;
	}
	
	function get_detail_title() {
		$s =& $this->mod_strings();
		return $s['LBL_PDF_CREDIT'];
	}
	
	function get_grand_totals_title() {
		$s =& $this->mod_strings();
		return $s['LBL_CREDIT_TOTALS'];
	}
	
	function get_detail_fields() {
		global $app_list_strings, $timedate;
		$row =  $this->row;

		$date_label = 'LBL_PDF_CREDIT_DATE';
		$number_label = 'LBL_PDF_CREDIT_NUMBER';
		

		$fields = array(
			$number_label => $row['prefix'] . $row['credit_number'],
			$date_label => $timedate->to_display_date($row['date_entered']),
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
		return $s['LBL_PREPARED_CREDIT'];
	}

	function get_email_title() {
		$s =& $this->mod_strings();
		return $s['LBL_EMAILED_CREDIT'];
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

	}
	
	function get_addresses() {
		$ret = parent::get_addresses();
		unset($ret['LBL_PDF_SHIP_TO']);
		return $ret;
	}

	function _address($pfx, $altfocus=null) {		
		if($altfocus)
			$focus =& $altfocus;
		else
			$focus =& $this->focus;
		
		global $locale;
		
		$phone = $pfx."phone";
		$email = $pfx."email";
		$ret = $locale->getLocaleBeanFormattedAddress($focus, $pfx, false);
		$ret .= "\n".$focus->getField($phone);
		$ret .= "\n".$focus->getField($email);
		
		return explode("\n", $ret);		
	}
	
	function get_config_title() {
		return translate('LBL_PDF_CREDIT');
	}
}


?>
