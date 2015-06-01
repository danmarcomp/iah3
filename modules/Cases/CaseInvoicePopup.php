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

require_once('include/Popups/PopupManager.php');

class CaseInvoicePopup extends PopupManager {
	var $parent_model = 'aCase';
	var $multi_select = true;
	var $show_filter = false;
	var $link_names = false;
	var $force_mass_actions_enabled = true;
	
	function initPopup($module, $link_name, $record, $stage, $next=null) {
		$this->loadRequest();
		$this->module = $module;
		$this->layout_module = $module;
		$this->model_name = AppConfig::module_primary_bean($module);
		$this->subpanel_name = $link_name;
		$this->parent_key = $record;
		$this->stage = $stage;
		$this->next = $next;
		$this->loadLayoutInfo();
	}

	function render()
	{
		$this->addHiddenFields(array('stage' => $this->stage, 'record' => $this->parent_key));
		return parent::render();
	}
	
	function getButtons() {
		return array(
			'add' => array(
				'label' => translate('LBL_ADD_SELECTED_BUTTON_LABEL', 'app'),
				'icon' => 'icon-add',
				'type' => 'button',
				'onclick' => "return sListView.sendMassUpdate('{$this->list_id}', 'AddSelectedToInvoice')",
				'params' => array(
					'stage' => 'add_products',
					'toplevel' => true,
				),
			),
			'skip' => array(
				'label' => translate('LBL_SKIP_BUTTON_LABEL', 'app'),
				'icon' => 'icon-cancel',
				'type' => 'button',
				'onclick' => $this->next ? "return sListView.sendMassUpdate('{$this->list_id}', '', null, {stage: '$this->next'}, true);" : "SUGAR.popups.close();",
				'params' => array(
					'stage' => 'hours',
					'toplevel' => true,
				),
			),
		);
	}
	
	function resetRequest() {
		global $perform, $uids;
		$perform = '';
		$uids = array();
		unset($_POST['mass_perform']);
		unset($_REQUEST['mass_perform']);
		unset($_REQUEST['list_uids']);
		unset($_REQUEST['list_offset']);
		unset($_REQUEST['list_limit']);
	}
	
	static function listupdate_perform($mu, $perform, &$listFmt, &$list_result, $uids)
	{
		if ($perform == 'AddSelectedToInvoice') {
			$module = $list_result->module_dirs[0];
			$popup = $listFmt->getListViewManager();
			if ($module == 'ProductCatalog')
				$popup->addProducts($list_result);
			elseif ($module == 'Booking')
				$popup->addHours($list_result);
			$mu->addHiddenFields(array('stage' => $popup->stage));
			return true;
		}
	}

	function addProducts(&$list_result)
	{
		global $pageInstance;
		$uids = array();
		while(! $list_result->failed) {
			foreach($list_result->getRowIndexes() as $idx) {
				$row = $list_result->getRowResult($idx);
				$uids[] = $row->getField('id');
			}
			if($list_result->page_finished)
				break;
			$listFmt->pageResult($list_result, true);
		}
		$prods = new ListQuery('aCase', true, array('link_name' => 'products', 'parent_key' => $this->parent_key));
		$prods->addField('~join.quantity', 'quantity');
		$prods->addSimpleFilter('id', $uids);
		$result = $prods->runQuery();
		if(! $result || ! $result->total_count || ! $uids)
			$this->stage = 'hours';
		else {
			$fmap = array(
				'quantity',
				'related_id' => 'id',
				'name',
				'purchase_name',
				'tax_class_id' => 'tax_code_id',
				'mfr_part_no' => 'manufacturers_part_no',
				'vendor_part_no',
				'currency_id',
				'exchange_rate',
				'description',
				'raw_cost_price' => 'cost',
				'raw_list_price' => 'list_price',
				'raw_unit_price' => 'purchase_price',
				'cost_price' => 'cost_usdollar',
				'list_price' => 'list_usdollar',
				'unit_price' => 'purchase_usdollar',
			);
			$lines = array();
			foreach($result->rows as $row) {
				$line = array('related_type' => 'ProductCatalog');
				foreach($fmap as $k => $f) {
					if(is_int($k)) $k = $f;
					$line[$k] = $row[$f];
				}
				$lines[] = $line;
			}
			$json = getJSONobj();
			$add_lines = $json->encode(array('add_lines' => $lines, 'status' => 'Draft', 'group_type' => 'products'));
			$js = "TallyEditor.add_group($add_lines);";
			$pageInstance->add_js_literal($js, null, LOAD_PRIORITY_FOOT);
			$this->stage = 'hours';
		}
		$this->resetRequest();
	}

	function addHours(&$list_result)
	{
		global $pageInstance;
		$uids = array();
		while(! $list_result->failed) {
			foreach($list_result->getRowIndexes() as $idx) {
				$row = $list_result->getRowResult($idx);
				$uids[] = $row->getField('id');
			}
			if($list_result->page_finished)
				break;
			$listFmt->pageResult($list_result, true);
		}
		$hours = new ListQuery('aCase', true, array('link_name' => 'booked_hours', 'parent_key' => $this->parent_key));
		$hours->addField('booking_category');
		$hours->addSimpleFilter('id', $uids);
		$hours->addSimpleFilter('status', 'approved', '=');
		$result = $hours->runQuery();
		$add_comments = AppConfig::setting('company.add_booked_hours');
		if(! $result || ! $result->total_count || ! $uids)
			$this->stage = 'done';
		else {
			$fmap = array(
				'related_id' => 'id',
				'tax_class_id' => 'tax_code_id',
				'currency_id' => 'billing_currency',
				'exchange_rate' => 'billing_exchange_rate',
				'raw_cost_price' => 'paid_rate',
				'raw_list_price' => 'billing_rate',
				'raw_unit_price' => 'billing_rate',
				'cost_price' => 'paid_rate_usd',
				'list_price' => 'billing_rate_usd',
				'unit_price' => 'billing_rate_usd',
			);
			$lines = array();
			foreach($result->rows as $row) {
				$line = array('related_type' => 'Booking');
				$line['id'] = 'booked~' . rand(1000, 2000);
				$line['quantity'] = $row['quantity'] / 60;
				$line['name'] = $row['booking_category'];
				if(! $add_comments)
					$line['name'] .= ': '.$row['name'];
				foreach($fmap as $k => $f) {
					if(is_int($k)) $k = $f;
					$line[$k] = $row[$f];
				}
				$lines[] = $line;
				if($add_comments)
					$lines[] = array('related_type' => 'Notes', 'parent_id' => $line['id'], 'body' => $row['name']);
			}
			$json = getJSONobj();
			$add_lines = $json->encode(array('add_lines' => $lines, 'status' => 'Draft', 'group_type' => 'service'));
			$js = "TallyEditor.add_group($add_lines);";
			$pageInstance->add_js_literal($js, null, LOAD_PRIORITY_FOOT);
			$this->stage = 'done';
		}
		$this->resetRequest();
	}
}
