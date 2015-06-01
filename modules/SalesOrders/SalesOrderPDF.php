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
require_once('modules/SalesOrders/SalesOrder.php');

class SalesOrderPDF extends QuotePDF {
	var $focus_type = 'SalesOrder';

	var $pdf_type = 'confirmation';

	function create_filename() {
		$mod_strings =& $this->mod_strings();
		$filename_pfx = $this->get_detail_title();
		if(! empty($mod_strings['LBL_FILENAME_SALESORDER']))
			$filename_pfx = $mod_strings['LBL_FILENAME_SALESORDER'];
		$filename = $filename_pfx . '_' . $this->focus->getField('prefix');
		$filename .= $this->focus->getField('so_number') . '_' . $this->focus->getField('billing_account_name');
		$filename = $filename . '.pdf';
		return $filename;
	}
	
	function get_detail_title() {
		$s =& $this->mod_strings();
		if ($this->pdf_type == 'slip') {
			return $s['LBL_PDF_PACKAGING_SLIP_TITLE'];
		} else {
			return $s['LBL_PDF_SALESORDER_TITLE'];
		}
	}

	function get_config_title() {
		return translate('LBL_PDF_SALESORDER');
	}
	
	function get_detail_fields() {
		global $app_list_strings, $timedate;
		$row =& $this->row;
		$fields = array(
			'LBL_PDF_SO_NUMBER' => $row['prefix'] . $row['so_number'],
			'LBL_PDF_ORDER_DATE' => $timedate->to_display_date($row['date_entered']),
			'LBL_PDF_TERMS' => $app_list_strings['terms_dom'][$row['terms']],
			'LBL_PDF_DUE_DATE' => $timedate->to_display_date($row['due_date'], false),
			'LBL_PDF_SALES_PERSON' => $row['assigned_user_name'],
		);
		$tax_info = $this->_company_tax_info();
		if(! empty($tax_info))
			$fields['LBL_PDF_TAX_INFO'] = $tax_info;
		//if ($this->pdf_type == 'slip') {
			//$fields['LBL_PDF_TRACKING_REFERENCE'] = $focus->tracking_reference;
		//}
		return $fields;
	}
	
	/*function get_addresses() {
		
	}*/
	
	function get_terms() {
		if($this->pdf_type == 'slip')
			return '';
        $terms = from_html(trim(AppConfig::setting('company.std_so_terms', '')));
		return $terms;
	}
	
	function get_note_title() {
		$s =& $this->mod_strings();
		return $s['LBL_PREPARED_SALESORDER'];
	}

	function get_email_title() {
		$s =& $this->mod_strings();
		return $s['LBL_EMAILED_SALESORDER'];
	}
	
	function get_main_columns() {
		$cols = QuotePDF::get_main_columns();
		if ($this->pdf_type == 'slip') {
			unset($cols['listprice']);
			unset($cols['unitprice']);
			unset($cols['extprice']);
			$w = 100;
			foreach ($cols as $name => $col) {
				if ($name != 'product') $w -= intval($col['width']);
			}
			$cols['product']['width'] = $w . '%';
		}
		return $cols;
	}
	
	function print_extra_foot() {
		// do nothing
	}

	function showTotals()
	{
		return $this->pdf_type != 'slip' && parent::showTotals();
	}
}


?>
