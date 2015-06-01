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
require_once('modules/Shipping/Shipping.php');

class ShippingPDF extends QuotePDF {
	var $focus_type = 'Shipping';

	function create_filename() {
		$mod_strings =& $this->mod_strings();
		$filename_pfx = $this->get_detail_title();
		$filename = $filename_pfx . '_' . $this->focus->getField('prefix');
		$filename .= $this->focus->getField('shipping_number') . '_' . $this->focus->getField('shipping_account_name');
		$filename = $filename . '.pdf';
		return $filename;
	}
	
	function createListQuery($id)
	{
		$lq = parent::createListQuery($id);
		$lq->addField('supplier.name', 'supplier_name');
		$lq->addField('shipping_provider.name', 'shipping_provider_name');
		$lq->addField('shipping_account.name', 'shipping_account_name');
		$lq->addField('assigned_user.name', 'assigned_user_name');
		return $lq;
	}

	function get_addresses() {
		$ship = $this->_address('shipping_');
		return array(
			'LBL_PDF_SHIP_TO' => $ship,
		);
	}
	
	function get_detail_title() {
		$s =& $this->mod_strings();
		return $s['LBL_PDF_PACKING_SLIP_TITLE'];
	}
	
	function get_config_title() {
		return translate('LBL_PDF_PACKING_SLIP');
	}
	
	function get_detail_fields() {
		global $app_list_strings;
		$row =& $this->row;
		$fields = array(
			'LBL_PDF_SHIPPING_NUMBER' => $row['prefix'] . $row['shipping_number'],
			'LBL_PDF_SALES_PERSON' => $row['assigned_user_name'],
			'LBL_PDF_PO_REF' => $row['purchase_order_num'],
			'LBL_PDF_PROVIDER' => $row['shipping_provider_name'],
			'LBL_PDF_TRACKING_NUM' => $row['tracking_number'],
		);
		return $fields;
	}
	
	/*function get_addresses() {
		
	}*/
	
	function get_terms() {
		return '';
	}
	
	function get_note_title() {
		$s =& $this->mod_strings();
		return $s['LBL_PREPARED_SHIPPING'];
	}

	function get_email_title() {
		$s =& $this->mod_strings();
		return $s['LBL_EMAILED_SHIPPING'];
	}
	
	function get_main_columns() {
		$cols = QuotePDF::get_main_columns();
		unset($cols['listprice']);
		unset($cols['unitprice']);
		unset($cols['extprice']);
		$w = 100;
		foreach ($cols as $name => $col) {
			if ($name != 'product') $w -= intval($col['width']);
		}
		$cols['product']['width'] = $w . '%';
		return $cols;
	}
	
	function print_extra_foot() {
		// do nothing
	}

	function showTotals()
	{
		return false;
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
}


?>
