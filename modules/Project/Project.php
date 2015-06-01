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


require_once('data/SugarBean.php');
require_once('modules/Project/ProjectFinancials.php');


// Project is used to store customer information.
class Project extends SugarBean {
	
	// Standard stored fields
	var $id;
	var $date_entered;
	var $date_modified;
	var $modified_user_id;
	var $assigned_user_id;
	
	// Relevant stored fields
	var $name;
	var $lead_source;
	var $description;
	var $project_type;
	var $amount;
	var $amount_usdollar;
	var $currency_id;
	var $exchange_rate;
	var $date_starting;
	var $date_ending;
	var $project_phase;
	var $percent_complete;

    var $use_timesheets;

	// These are related
	var $account_id;
	var $account_name;
	var $financials;
	
	// Not stored
	var $assigned_user_name;
	var $revenue_thismonth;
	var $revenue_nextmonth;
	var $revenue_thismonth_usd;
	var $revenue_nextmonth_usd;
	
	// now uses ProjectRelation like Sugar's Project module
	var $relation_id;
	var $relation_name;
	var $relation_type;

	var $contact_id;
	var $opportunity_id;
	var $task_id;
	var $note_id;
	var $meeting_id;
	var $call_id;
	var $email_id;
    var $project_task_id;

    var $led_color;
    var $unapproved_status;

    var $object_name = 'Project';
	var $module_dir = 'Project';
	var $new_schema = true;
	var $table_name = 'project';
	var $financials_table = "project_financials";


	function Project() {
		parent::SugarBean();
	}
	
	
	static function get_thismonth_nextmonth() {
		$ret = Array();
		$t = localtime(time(), true);
		$year  = $t['tm_year'] + 1900;
		$month = $t['tm_mon'] + 1;
		$ret[] = sprintf("%04d-%02d", $year, $month);
		$month ++;
		if($month > 12) {
			$month -= 12;
			$year ++;
		}
		$ret[] = sprintf("%04d-%02d", $year, $month);
		return $ret;
	}

	
	static function financials_link($spec) {
		global $db;
		$base = $spec['base_table'];
		$id_f = $db->quoteField('id', $base);
		$alias = $spec['link_alias'];
		$pid_f = $db->quoteField('project_id', $alias);
		$period_f = $db->quoteField('period', $alias);
		$periods = self::get_thismonth_nextmonth();
		$idx = ($alias == 'next_month' ? 1 : 0);
		$spec['clause'] = "$pid_f=$id_f AND $period_f='{$periods[$idx]}-01'";
		return $spec;
	}
	
	static function led_color($spec, $table, $for_order=false) {
		global $db;
		$phase = $db->quoteField('project_phase', $table);
		$pid = $db->quoteField('id', $table);
		$tasks = $db->quoteField(AppConfig::setting("model.detail.ProjectTask.table_name"));
		$hrs = $db->quoteField(AppConfig::setting("model.detail.BookedHours.table_name"));
		return "CASE WHEN $phase='Active - In Progress' THEN
			CASE WHEN ( SELECT COUNT(IF(hrs.status = 'submitted', 1, NULL)) FROM $hrs hrs
			  LEFT JOIN $tasks pt ON pt.id=hrs.related_id AND NOT pt.deleted
			  WHERE hrs.related_type='ProjectTask' AND pt.parent_id=$pid
			  AND NOT hrs.deleted ) > 0 THEN 'yellow' ELSE 'green' END
			ELSE 'grey' END";
	}
	
	static function this_month_label() {
		$mos = self::get_thismonth_nextmonth();
		return $mos[0];
	}

	static function next_month_label() {
		$mos = self::get_thismonth_nextmonth();
		return $mos[1];
	}

	/*
	function save($check_notify = FALSE) {
		require_once('modules/Currencies/Currency.php');
		
		// this must be done before interpreting numeric values
		$this->unformat_all_fields();

		$currency = new Currency();
		$currency->retrieve($this->currency_id);
		$rate_changed = adjust_exchange_rate($this, $currency);
		$this->amount_usdollar = $currency->convertToDollar($this->amount);
		
		if (!empty($this->id)) {
			require_once 'modules/ProjectTask/ProjectTask.php';
			$seedProjectTask = new ProjectTask;
			$this->load_relationship('project_tasks');
			$tasks = $this->project_tasks->getBeans($seedProjectTask);
			$estimated = $actual = $total = 0;
			foreach ($tasks as $task) {
				$estimated += $task->estimated_effort;
				$actual += $task->actual_effort;
				$total += $task->percent_complete;
			}
			if ($this->use_timesheets) {
				$this->percent_complete = ($estimated == 0) ? 0 : round($actual / $estimated * 100);
			} else {
				$this->percent_complete = (count($tasks) == 0) ? 0 : round($total / count($tasks));
			}
			$this->cleanup_list($tasks);
			$seedProjectTask->cleanup();
		}
		$currency->cleanup();
		return parent::save($check_notify);
	}*/

	// produce subpanel data
	function booked_hours() {
		$query = "SELECT hrs.*, acc.name AS account_name ".
			"FROM booked_hours hrs ".
			"LEFT JOIN project_task pt ON pt.id=hrs.related_id ".
				"AND hrs.related_type='ProjectTask' AND NOT pt.deleted ".
			"LEFT JOIN accounts acc ON acc.id=hrs.account_id AND NOT acc.deleted ".
			"WHERE pt.parent_id='$this->id' AND NOT hrs.deleted ";
		return $query;
	}

	function getNameById($id) {
		global $db;
		$sql = "SELECT name FROM project WHERE id='" . PearDatabase::quote($id) . "'";
		$res = $db->query($sql, true);
		if ($row = $db->fetchByAssoc($res)) {
			return $row['name'];
		}
		return '';
	}

	function get_view_closed_basic($param)
	{
		return empty($param['value']) ? "(substring(project.project_phase, 1, 8) = 'Active -')" : '1';
	}

	function get_view_closed_advanced($param)
	{
		return '1';
	}

	function getDefaultListWhereClause()
	{
		return "(substring(project.project_phase, 1, 8) = 'Active -')";
	}

	function get_search_phase_options()
	{
		global $app_list_strings, $app_strings, $mod_strings;
		$project_phase_dom = $app_list_strings['project_status_dom'];
		unset($project_phase_dom['']);
		$project_phase_dom = array_merge(array('empty'=>$app_strings['LBL_NONE'], "Active" => $mod_strings['LBL_ACTIVE']), $project_phase_dom);
		return $project_phase_dom;
	}
	
	function get_search_phase_where_basic($param)
	{
		return '1';
	}

	function get_search_phase_where_advanced($param)
	{
		switch ($param['value']) {
			case 'empty':
				return '1';
			case 'Active':
				return "(substring(project.project_phase, 1, 8) = 'Active -')";
			default:
				return "project.project_phase = '" . $this->db->quote($param['value']) . "'";
		}
	}
	
	function cleanup() {
		if(isset($this->financials)) {
			$this->financials->cleanup();
			unset($this->financials);
		}
		parent::cleanup();
	}

    /**
     * @return array
     */
    function getRelatedApprovedExpenseReports() {
        $lq = new ListQuery('ExpenseReport');
        $lq->addFilterClause(array('field' => 'parent_type', 'value' => 'Project'));
        $lq->addFilterClause(array('field' => 'parent_id', 'value' => $this->id));
        $lq->addFilterClause(array('field' => 'status', 'value' => 'Approved'));

        return $lq->fetchAllRows();
    }

    static function init_record(RowUpdate &$upd, $input) {
        $update = array();
        $return_module = array_get_default($input, 'return_module', '');
        $opportunity_id = array_get_default($input, 'opportunity_id', '');
        if (empty($opportunity_id))
            $opportunity_id = array_get_default($input, 'return_record', '');

        if($return_module == 'Opportunities' && ! empty($opportunity_id)) {
            $opp = ListQuery::quick_fetch_row('Opportunity', $opportunity_id);

            if($opp != null) {
                $copy_fields = array(
                    'lead_source', 'name', 'description',
                    'account_id', //'account_name',
                    'amount', 'amount_usdollar',
                    'currency_id', 'exchange_rate',
                );
                foreach($copy_fields as $f) {
                    if(isset($opp[$f]))
                        $update[$f] = $opp[$f];
                }
            }

        } elseif (isset($input['salesorder_id'])) {
            $sorder = ListQuery::quick_fetch_row('SalesOrder', $input['salesorder_id']);

            if ($sorder != null) {
                $copy_fields = array(
                    'name' => 'name',
                    'account_id' => 'billing_account_id',
                    //'account_name' => 'billing_account_name',
                    'currency_id' => 'currency_id',
                    'amount' => 'amount',
                    'description' => 'notes',
                );
                foreach($copy_fields as $focus_field => $sorder_field) {
                    if(isset($sorder[$sorder_field])) {
                        $update{$focus_field} = $sorder[$sorder_field];
                    }
                }

                $update['project_phase'] = 'Active - In Progress';
            }

        }

        if (isset($input['account_id'])) {
            $account = ListQuery::quick_fetch_row('Account', $input['account_id']);
            if($account != null) {
                $update['account_id'] = $account['id'];
                $update['currency_id'] = $account['currency_id'];
                $update['exchange_rate'] = $account['exchange_rate'];
            }
        }

        if (isset($input['contact_id']))
            $update['contact_id'] = $input['contact_id'];
        if (isset($input['relation_type']))
            $update['relation_type'] = $input['relation_type'];
        if (isset($input['relation_id']))
            $update['relation_id'] = $input['relation_id'];

        if(empty($update['date_starting'])) {
            $update['date_starting'] = array_get_default($input, 'date_starting', date('Y-m-d'));
			if (! empty($upd->duplicate_of_id)) {
				$start = $upd->getField('date_starting');
				$end = $upd->getField('date_ending');
				if($start && $end) {
					$offset = strtotime($end) - strtotime($start);
					$update['date_ending'] = date("Y-m-d", strtotime($update['date_starting']) + $offset);
				}
			}
        }
		if(empty($update['date_ending'])) {
			// this gives us the last day of the month
			$update['date_ending'] = date("Y-m-t", strtotime($update['date_starting']));
		}

        $upd->set($update);
    }
    
    static function copy_tasks(RowUpdate &$upd) {
    	if($upd->duplicate_of_id) {
	    	$parent = ListQuery::quick_fetch('Project', $upd->duplicate_of_id, array('date_starting'));
	    	if($parent) {
	    		$old_ds = $parent->getField('date_starting');
	    		$new_ds = $upd->getField('date_starting');
	    		$offset = strtotime($new_ds) - strtotime($old_ds);
				$lq = new ListQuery('Project', array('date_start'));
				$lq = new ListQuery('Project', null, array('link_name' => 'project_tasks', 'parent_key' => $upd->duplicate_of_id));
				$tasks = $lq->fetchAll();
				$to_save = array();
				
				if($tasks && ! $tasks->failed) {
					foreach($tasks->getRowIndexes() as $idx) {
						$ntask = $tasks->getRowResult($idx);
						$old_id = $ntask->getField('id');
						$ntask->new_record = true;
						$ntask->assign('id', create_guid());
						$to_save[$old_id] = $ntask;
					}
				}
				
				$task_upd = new RowUpdate('ProjectTask');
				foreach($to_save as $ntask) {
					$task_start = strtotime($ntask->getField('date_start'));
					$task_due = strtotime($ntask->getField('date_due'));
					$task_upd->setOriginal($ntask);
					$depends = $ntask->getField('depends_on_id');
					$task_upd->set(array(
						'parent_id' => $upd->getPrimaryKeyValue(),
						'status' => 'Not Started',
						'percent_complete' => 0,
						'date_start' => date('Y-m-d', $task_start + $offset),
						'date_due' => date('Y-m-d', $task_due + $offset),
					));
					if($depends && isset($to_save[$depends])) {
						$task_upd->set('depends_on_id', $to_save[$depends]->getField('id'));
					}
					if(! $ntask->getField('currency_id')) {
						$task_upd->set('currency_id', $upd->getField('currency_id'));
					}
					$task_upd->save();
				}
			}
		}
    }

    static function update_opportunity(RowUpdate &$upd) {
        $return_module = array_get_default($_REQUEST, 'return_module', '');
        $opportunity_id = array_get_default($_REQUEST, 'return_record', '');

        if ($return_module == 'Opportunities' && ! empty($opportunity_id)) {
            $result = ListQuery::quick_fetch('Opportunity', $opportunity_id);
            if ($result) {
                if (! empty($_REQUEST['close_opp']))
                    self::close_related_opportunity($result);
                $upd->addUpdateLink('opportunities', $opportunity_id);
            }

        }
    }

    static function set_account_relationchip(RowUpdate &$upd) {
        if (isset($upd->saved['account_id']))
            $upd->addUpdateLink('accounts', $upd->getField('account_id'));
    }

    static function close_related_opportunity(RowResult $opportunity) {
        $upd = new RowUpdate($opportunity);
        $upd->set(array(
            'sales_stage' => 'Closed Won',
            'probability' => 100,
            'forecast_category' => 'Closed',
        ));
        $upd->save();
    }

	static function listupdate_perform($mu, $perform, &$listFmt, &$list_result, $uids) {
		if ($perform !== 'create_contracts') return;
		require_once 'include/database/ListQuery.php';
		require_once 'include/database/RowUpdate.php';
		require_bean('Contract');
		$action = $_POST['contract_action'];
		if ($action == 'create_new') {
			$lq = new ListQuery('Contract');
			$lq->addSimpleFilter('account_id', $_POST['account_id']);
			$result = $lq->runQuery(0, 1);
			if ($result->getResultCount()) {
				$idx = $result->getRowIndexes();
				$row = $result->getRowResult($idx[0]);
				$contract = new RowUpdate($row);
			} else {
				$counts = get_contract_counts();
				$initial = substr($_POST['account_name'], 0, 1);
				if (isset($counts[$initial])) $number = $counts[$initial];
				else $number = 1;
				$contract = RowUpdate::blank_for_model('Contract');
				$contract->set('account_id', $_POST['account_id']);
				$contract->set('contract_no',  $initial . sprintf('%04d', $number));
				$contract->set('status', 'Active');
				$contract->save();
			}
			$subcontract = RowUpdate::blank_for_model('SubContract');
			$subcontract->set('main_contract_id', $contract->getField('id'));
			$subcontract->set('name',  $_POST['contract_name']);
			$subcontract->set('status', 'Active');
			$subcontract->set('contract_type_id', $_POST['contract_type']);
			$subcontract->save();
		} else {
			$row = ListQuery::quick_fetch('SubContract', $_POST['contract']);
			$subcontract = new RowUpdate($row);
		}
		foreach ($uids as $uid) {
			$row = ListQuery::quick_fetch('Asset', $uid);
			if ($row) {
				$asset = new RowUpdate($row);
				$asset->set('service_subcontract_id', $subcontract->getField('id'));
				$asset->save();
			}
		}
	}
}
?>
