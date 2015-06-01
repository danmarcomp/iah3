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
require_once('modules/Service/Contract.php');
require_once('modules/SubContracts/SubContract.php');
require_once('modules/Assets/Asset.php');
require_once('modules/SupportedAssemblies/SupportedAssembly.php');

class SubContractPDF extends QuotePDF {
	var $focus_type = 'SubContract';
	var $default_font_size = '10';
	
	function create_filename() {
		$mod_strings =& $this->mod_strings();
		$filename_pfx = $this->get_detail_title();
		if(! empty($mod_strings['LBL_FILENAME_ASSET_LIST']))
			$filename_pfx = $mod_strings['LBL_FILENAME_ASSET_LIST'];
		$filename = $filename_pfx . '_' . $this->focus->getField('account_name');
		$filename = $filename . '.pdf';
		return $filename;
	}
	
	function get_detail_title() {
		$s =& $this->mod_strings();
		return $s['LBL_ASSET_LIST_TITLE'];
	}
	
	function &get_main_contract() {
		static $main;
		if(! isset($main)) {
			$main = ListQuery::quick_fetch('Account', $this->row['main_contract_id']);
			if (!$main)
				$main = false;
		}
		return $main;
	}
	
	function get_detail_fields() {
		global $app_list_strings;
		$row =& $this->row;
		//$currency =& $this->get_currency();
		$contract =& $this->get_main_contract();
		$fields = array(
			'LBL_PDF_SUBC_MAIN_CONTRACT' => $contract ? $contract->getField('contract_no') : '',
			'LBL_PDF_SUBC_NAME' => $row['name'],
			'LBL_PDF_SUBC_START_DATE' => $row['date_start'],
			'LBL_PDF_SUBC_EXPIRY_DATE' => $row['date_expire'],
		);
		return $fields;
	}
	
	function get_addresses() {
		$contract =& $this->get_main_contract();
		if ($contract) {
			$acc = ListQuery::quick_fetch('Account', $contract->getField('account_id'));
			if ($acc) {
				$acc->assign('billing_account_name', $acc->getField('name'));
				$bill = $this->_address('billing_', $acc);
				$tax_information = $acc->getField('tax_information');
				if(! empty($tax_information))
					$bill[] = $tax_information;
				$addrs = array(
					'LBL_PDF_ACCOUNT' => $bill,
				);
			}
		}
		$ctc = ListQuery::quick_fetch('Contact', $this->row['customer_contact_id']);
		if ($ctc) {
			$ctc->assign('primary_contact_name', $ctc->getField('name'));
			$home = $this->_address('primary_', $ctc);
			$addrs['LBL_PDF_CONTACT'] = $home;
		}
		return $addrs;
	}
	
	function get_terms() {
	}
	
	function get_serial_numbers(&$focus) {
		$lq = new ListQuery($focus->base_model, true, array('link_name' => 'serial_numbers')); 
		$lq->setParentKey($focus->getField('id'));
		$res = $lq->runQuery();
		$serials = $res->getRows();
		$ret = array();
		foreach ($serials as $serial) {
			$ret[] = $serial['serial_no'];
		}
		return join(', ', $ret); 
	}
	
	function print_main() {
		$hide_components = array_get_default($_REQUEST, 'pdf_type') == 'hide_components';
		$mod_strings =& $this->mod_strings();
		$focus =& $this->focus;
		$cols = array(
			'number' => array(
				'title' => $mod_strings['LBL_PDF_LIST_NUMBER'],
				'width' => '4%',
			),
			'number2' => array(
				'width' => '4%',
			),
			'quantity' => array(
				'title' => $mod_strings['LBL_PDF_LIST_QTY'],
				'width' => '6%',
			),
			'product' => array(
				'title' => $mod_strings['LBL_PDF_LIST_PRODUCT'],
				'width' => '45%',
			),
			'serial' => array(
				'title' => $mod_strings['LBL_PDF_LIST_SERIAL'],
				'width' => '41%',
			),
		);
		$opts = array('border' => 0);

		$data = array();
		$lq = new ListQuery('SubContract', true, array('link_name' => 'assets'));
		$lq->setParentKey($focus->getField('id'));
		$filter = array(
			'operator' => 'OR',
			'multiple' => array(
				array(
					'field' => 'supported_assembly_id',
					'value' => '',
				),
				array(
					'field' => 'supported_assembly_id',
					'operator' => 'null',
				),
			),
		);
		$lq->addFilterClause($filter);
		$res = $lq->runQuery();
		$items = $res->getRowResults();

		$num = 1;
		foreach ($items as $row) {
			$serials = $this->get_serial_numbers($row);
				$data[] = array(
					'number' => $num++,
					'quantity' => $row->getField('quantity'),
					'product' => $row->getField('name'),
					'serial' => $serials,
				);
		}
		$this->moveY('10pt');
		$this->DrawTable($data, $cols, '', true, $opts);

		$lq = new ListQuery('SubContract', true, array('link_name' => 'supportedassemblies'));
		$lq->setParentKey($focus->getField('id'));
		$res = $lq->runQuery();
		$items = $res->getRowResults();

		foreach ($items as $row) {
			$lq = new ListQuery('SupportedAssembly', true, array('link_name' => 'assets'));
			$lq->setParentKey($row->getField('id'));
			$res = $lq->runQuery();
			$components = $res->getRowResults();
			$data = array();
			$serials = $this->get_serial_numbers($row);
			$data[] = array(
				'number' => $num++,
				'quantity' => $row->getField('quantity'),
				'product' => $row->getField('name'),
				'serial' => $serials,
			);
        	$this->DrawTable($data, $cols, '', false, $opts);

			$data = array();        
			if (!$hide_components) {
				$component_num = 1;
				foreach ($components as $component) {
					$serials = $this->get_serial_numbers($component);
					$data[] = array(
						'number2' => $component_num++,
						'quantity' => $component->getField('quantity'),
						'product' => $component->getField('name'),
						'serial' => $serials,
					);
				}
			}
        	$this->DrawTable($data, $cols, '', false, $opts);
		}

		//$this->RuleLine(array('padding' => '15pt'));
	}
	
	function print_extra_foot() {
	}
}


?>
