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
require_once('modules/PurchaseOrders/PurchaseOrder.php');

class PurchaseOrderPDF extends QuotePDF {
	var $focus_type = 'PurchaseOrder';
	
	function create_filename() {
		$mod_strings =& $this->mod_strings();
		$filename_pfx = $this->get_detail_title();
		if(! empty($mod_strings['LBL_FILENAME_PURCHASEORDER']))
			$filename_pfx = $mod_strings['LBL_FILENAME_PURCHASEORDER'];
		$filename = $filename_pfx . '_' . $this->focus->getField('prefix');
		$filename .= $this->focus->getField('po_number') . '_' . $this->focus->getField('supplier_name');
		$filename = $filename . '.pdf';
		return $filename;
	}
	
	function createListQuery($id)
	{
		$lq = parent::createListQuery($id);
		$lq->addField('assigned_user.name', 'assigned_user_name');
		$lq->addField('supplier.name', 'supplier_name');
		$lq->addField('supplier_contact.name', 'supplier_contact_name');
		return $lq;
	}
	
	function get_detail_title() {
		$s =& $this->mod_strings();
		return $s['LBL_PDF_PURCHASEORDER_TITLE'];
	}
	
	function get_config_title() {
		return translate('LBL_PDF_PURCHASEORDER');
	}
	
	function get_detail_fields() {
		global $app_list_strings, $timedate;
		$row =& $this->row;
		$fields = array(
			'LBL_PDF_PO_NUMBER' => $row['prefix'] . $row['po_number'],
			'LBL_PDF_ORDER_DATE' => $timedate->to_display_date($row['date_entered']),
			'LBL_PDF_TERMS' => $app_list_strings['terms_dom'][$row['terms']],
			'LBL_PDF_BUYER' => $row['assigned_user_name'],
		);
		$tax_info = $this->_company_tax_info();
		if(! empty($tax_info))
			$fields['LBL_PDF_TAX_INFO'] = $tax_info;
		return $fields;
	}
	
	function get_addresses() {
		$ret = array();

		$supp = array();
		$acc = ListQuery::quick_fetch('Account', $this->focus->getField('supplier_id'));
		if ($acc) {
			$acc->assign('billing_contact_name', $this->focus->getField('supplier_contact_name'));
			$acc->assign('billing_account_name', $acc->getField('name'));
			$supp = $this->_address('billing_', $acc);
		}
		$tax_information = $this->focus->getField('tax_information');
		if(! empty($tax_information))
			$supp[] = $tax_information;
		$ret['LBL_PDF_SUPPLIER'] = $supp;

		if ($this->focus->getField('drop_ship') == 1)
		{
			$ship = $this->_address('shipping_');
			$ret['LBL_PDF_SHIP_TO'] = $ship;
		}

		return $ret;
	}
	
	function get_terms() {
        $terms = from_html(trim(AppConfig::setting('company.std_po_terms', '')));
		return $terms;
	}
	
	function get_note_title() {
		$s =& $this->mod_strings();
		return $s['LBL_PREPARED_PURCHASEORDER'];
	}

	function get_email_title() {
		$s =& $this->mod_strings();
		return $s['LBL_EMAILED_PURCHASEORDER'];
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
		

	}
}


?>
