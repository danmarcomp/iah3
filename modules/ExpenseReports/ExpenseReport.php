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

/*********************************************************************************

 * Description:  Defines the Account SugarBean Account entity with the necessary
 * methods and variables.
 ********************************************************************************/

require_once 'data/SugarBean.php';
require_once 'modules/ExpenseReports/ExpenseItem.php';

// Account is used to store account information.
class ExpenseReport extends SugarBean {
	
	var $field_name_map = array();
	
	// Stored fields
	var $id;
	var $industry;
	var $prefix;
	var $report_number;
	var $status;
	var $type;
	var $description;
	var $date_submitted;
	var $date_approved;
	var $date_paid;
	var $assigned_user_id;
	var $assigned_user_name;
	var $account_id;
	
	var $total_amount;
	var $total_amount_usdollar;
	var $total_pretax;
	var $total_pretax_usdollar;
	var $tax;
	var $tax_usdollar;
	var $currency_id;
	var $exchange_rate;
	var $advance_currency_id;
	var $advance_exchange_rate;
	var	$advance;
	var $advance_usdollar;
	var $balance;
	var $balance_usdollar;
	
	var $approved_user_id;
	var $approved_user_name;

	var $table_name = "expense_reports";
	var $module_dir = 'ExpenseReports';

	var $object_name = "ExpenseReport";
	
	var $line_object = 'ExpenseItem';
	
	var $line_items_map = array(
		'id' => 'id',
		'item_number' => 'item_number',
	);

	var $new_schema = true;

	// This is used to retrieve related fields from form posts.
	var $additional_column_fields = Array(
		'assigned_user_name', 'assigned_user_id', 'approved_user_name', 'approved_user_id',
		'parent_name', 'parent_id',
		'account_name', 'account_id',
	);

	var $relationship_fields = Array();

    const STATUS_APPROVED = 'Approved';

	function getLineItemsList() {
		if (empty($this->id) || $this->new_with_id) {
			return array();
		}
		$expenseItem = new ExpenseItem();
		$itemsList = $expenseItem->get_list("line_order", "report_id='".$this->id."'", 0, 999999, 999999);
		return $itemsList['list'];
	}

	
	function user_can_approve($uid=null, $forPayment = false) {
    	global $current_user;
		if(! $uid) $uid = $current_user->id;
		if (!$forPayment) {
			return ($this->status == 'Submitted' || $this->status == 'Rejected') && canApprove($uid, $this);
		} else {
			return ($this->status == 'Submitted' || $this->status == 'Approved') && canApprove($uid, $this);
		}
    }
	
    static function get_line_items($id, $fields = true) {
        if (empty($id))
            return array();

        $lq = new ListQuery('ExpenseItem', $fields);
        $lq->addPrimaryKey();
        $lq->addAclFilter('list');
        $lq->addFilterClause(array('field' => 'report_id', 'value' => $id));
        return $lq->fetchAllRows();
    }

    static function delete_items($keep_ids, $report_id) {
        global $db;
        $id_str = implode("', '", $keep_ids);
        $query = "UPDATE `expense_items` SET `expense_items`.`deleted` = '1' WHERE `expense_items`.`id` NOT IN ('$id_str') AND `expense_items`.`report_id` = '".$report_id."'";
        $db->query($query);

        $notes_query = "UPDATE `notes` SET `notes`.`deleted` = '1' WHERE `notes`.`id` IN
            (SELECT `expense_items`.`note_id` FROM `expense_items`
            WHERE `expense_items`.note_id IS NOT NULL AND `expense_items`.`id` NOT IN ('$id_str') AND `expense_items`.`report_id` = '".$report_id."')";
        $db->query($notes_query, false);
    }

	function getCurrentPrefix() {
		return AppConfig::get_sequence_prefix('expense_prefix');
	}

	function getNextSequenceValue() {
		return AppConfig::next_sequence_value('expense_number_sequence');
	}

	static function init_record(RowUpdate &$upd, $input) {
        $update = array();
        $update['status'] = 'In Preparation';
        $update['date_submitted'] = null;
        $update['date_paid'] = null;
        $update['date_approved'] = null;
        $update['approved_user_id'] = null;
        if (isset($input['return_panel']) && (isset($input['return_module']) && $input['return_module'] == 'HR'))
            $update['parent_type'] = 'HR';
        $upd->set($update);
    }

    static function set_number(RowUpdate &$upd) {
        if ($upd->new_record) {
            $report = new ExpenseReport();
            $number = $report->getNextSequenceValue();
            $prefix = $report->getCurrentPrefix();
            $upd->set('prefix', $prefix);
            $upd->set('report_number', $number);
        }
    }

	static function process_status_action(RowUpdate &$upd, $action)
	{
		if (self::statusActionEnabled($upd, $action)) {
	        $date = gmdate("Y-m-d H:i:s");
			switch ($action) {
				case 'submit':
	                $upd->set('date_submitted', $date);
	                $upd->set('status', 'Submitted');
					return true;
				case 'approve':
	                $upd->set('date_approved', $date);
	                $upd->set('approved_user_id', AppConfig::current_user_id());
	                $upd->set('status', 'Approved');
					return true;
				case 'reject':
	                $upd->set('status', 'Rejected');
					return true;
				case 'unsubmit':
	                $upd->set('status', 'In Preparation');
					return true;
				case 'pay':
	                $upd->set('status', 'Paid');
					$upd->set('date_paid', $date);
					if (!$upd->getField('approved_user_id')) {
						$upd->set('approved_user_id', AppConfig::current_user_id());
		                $upd->set('status', 'Approved');
					}
					return true;
			}
		}
		return false;
	}


    static function set_status_date(RowUpdate &$upd) {
		$status_action = array_get_default($_POST, 'status_action');
		if ($status_action)
			self::process_status_action($upd, $status_action);
    }

    /**
     * Update related Project actual costs if Expense Report was Approved
     *
     * @static
     * @param RowUpdate $upd
     */
    static function update_project_costs(RowUpdate $upd) {
        if ($upd->getField('parent_type') == 'Project' && $upd->getField('parent_id')) {
            $project = ListQuery::quick_fetch('Project', $upd->getField('parent_id'));

            if ($project && $project->getField('use_timesheets')) {
                require_once('modules/Project/ProjectFinancials.php');
                $financials = new ProjectFinancials();
                $financials->load(RowUpdate::for_result($project));
                $financials->calculate_costs($project->getField('use_timesheets'));
                $financials->save();
            }
        }
    }

    static function calc_taxes($total_amount, $total_pretax, $currency_id) {
        $taxes = currency_format_number($total_amount - $total_pretax, array('currency_id' => $currency_id));
        $html = '<input type="text" name="tax_display" id="tax_display" style="border:none; background:transparent" value="'.$taxes.'" readonly="readonly">';

        return $html;
    }
	
	static function mutateDetailButtons($detail, &$buttons)
	{
		if (isset($buttons['edit']))
			$buttons['edit']['hidden'] = array(
				'type' => 'hook',
				'hook' => array(
					'file' => 'modules/ExpenseReports/ExpenseReport.php',
					'class' => 'ExpenseReport',
					'class_function' => 'actionButtonDisabled',
					'action' => 'edit',
				)
			);
	}

	static function statusActionEnabled(&$row, $action, $uid = null)
	{
		$val = true;
		if (!$uid)
			$uid = AppConfig::current_user_id();
		$status = $row->getField('status');
		switch ($action) {
			case 'submit':
				$val = ($status == 'In Preparation' || $status == 'Rejected');
				break;
			case 'unsubmit':
				$val = ($status == 'Submitted');
				break;
			case 'approve':
			case 'reject':
				$val = ($status == 'Submitted') && canApprove($uid, $row);
				break;
			case 'pay':
				$val = ($status == 'Approved') && canApprove($uid, $row);
				break;
		}
		return $val;
	}

	static function actionButtonDisabled(&$row, &$flag, &$val)
	{
		if (! ($row instanceof RowResult)) {
			$val = true;
			return;
		}
		$action = $flag['hook']['action'];
		if ($action == 'edit')
			$val = $row->getField('status') != 'In Preparation';
		else
			$val = !self::statusActionEnabled($row, $flag['hook']['action']);
	}

	/*
if ($focus->status == 'In Preparation' || $focus->status == 'Rejected') {
	$xtpl->parse('main.submit_button');
}
if ($focus->status == 'In Preparation' || $focus->status == 'Rejected') {
	if($focus->ACLAccess('EditView')){
		$xtpl->parse('main.edit_button');
	}
}

if ($focus->status == 'Submitted') {
	if($focus->ACLAccess('EditView')){
		$xtpl->parse('main.unsubmit_button');
	}
	if($focus->user_can_approve()) {
		$xtpl->parse('main.approve_button');
		$xtpl->parse('main.reject_button');
	}
}

if ($focus->status == 'Approved') {
	if($focus->user_can_approve(null, true)) {
		$xtpl->parse('main.pay_button');
	}
}
	 */



}

