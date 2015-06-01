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


function scheduler_update_config() {
	global $timedate;
	$last = AppConfig::setting('scheduler.last_cron_run');
	if(strlen($last)) {
		$last = strtotime($last.' GMT');
		AppConfig::set_local('scheduler.last_cron_interval', strtotime('now') - $last);
	}
	AppConfig::set_local('scheduler.last_cron_run', $timedate->get_gmt_db_datetime());
}


function scheduler_retrieve_pending($type=null, $where='') {
	global $timedate;
	$now = $timedate->get_gmt_db_datetime();
	$lq = new ListQuery('Schedule', true);
	$filters = array();
	if($type != null)
		$filters[] = array('field' => 'type', 'value' => $type);
	$filters[] = array('field' => 'enabled', 'operator' => 'true');
	$filters[] = "(next_run IS NULL OR next_run < '$now')";
	$lq->addFilterClauses($filters);
	return $lq->fetchAll('last_run ASC');
}

function scheduler_run_pending($type=null, $where='') {
	$pending = scheduler_retrieve_pending($type, $where);
	if($pending)
		scheduler_run_list($pending);
}

function scheduler_run_list(ListResult $lst, $force=false) {
	global $current_user;
	if (!defined('inScheduler')) {
		define('inScheduler', 1);
	}
	$last_ignore = ignore_user_abort(true);
	@set_time_limit(290); // 5 minutes less 10 seconds

	foreach($lst->getRowIndexes() as $idx) {
		$sched = $lst->getRowResult($idx);
		scheduler_run_result($sched, $force);
	}
	
	ignore_user_abort($last_ignore);
}


function scheduler_run_result(RowResult $sched, $force=false) {
	global $timedate, $current_user;
	if($sched->getField('status') == 'running' && strtotime('now') - scheduler_last_run_ts($sched) < 3600 && !$force)
		return; // don't run again until an hour after a failed (non-terminating or error-throwing) run

	$upd = RowUpdate::for_result($sched);
	$upd->set('last_run', $timedate->get_gmt_db_datetime());
	$upd->set('next_run', scheduler_get_next_run($sched));
	$upd->set('status', 'running');
	$upd->save();
	
	$orig_user = ! empty($current_user) ? clone($current_user) : null;
	scheduler_perform($sched->getField('type'));
	
	$upd->set('status', '');
	$upd->save();

	$current_user->cleanup();
	$current_user = $orig_user;
}


function scheduler_perform($type) {
	$spec = AppConfig::setting("scheduler_tasks.$type");
	if($spec) {
		$restore = array();
		if (!empty($spec['set_globals'])) {
			foreach ($spec['set_globals'] as $name => $value) {
				$restore[$name] = array_get_default($GLOBALS, $name);
				$GLOBALS[$name] = $value;
			}
		}
		require_once($spec['file']);
		if(isset($spec['function']))
			call_user_func($spec['function']);
		if($restore)
			array_extend($GLOBALS, $restore);
	}
}


function scheduler_last_run_ts(RowResult &$sched) {
	$last_run = $sched->getField('last_run');
	if(!empty($last_run)) {
		$t = strtotime($last_run.' GMT');
		if($t == -1 || $t === false)
			return -1;
		return $t;
	}
	return -1;
}

	
function scheduler_get_next_run(RowResult $sched) {
	global $timedate;
	$last = scheduler_last_run_ts($sched);
	if($last == -1)
		$last = strtotime('now');
	$size = $sched->getField('run_interval');
	$unit = $sched->getField('run_interval_unit');
	if($unit == 'quarters') {
		$unit = 'months';
		$size *= 3;
	}
	$next = strtotime("+$size $unit", $last);
	return gmdate($timedate->get_db_date_time_format(), $next);
}

	
function scheduler_rebuild_tasks($force=false) {
	$tasks = AppConfig::setting('scheduler_tasks');
	$lq = new ListQuery('Schedule', true, array('filter_deleted' => false));
	$result = $lq->fetchAll();
	$db_tasks = array();
	if($result) {
		foreach($result->getRowIndexes() as $k) {
            $task_row = $result->getRowResult($k);
            $task = RowUpdate::for_result($task_row);
			$db_tasks[$task->getField('type')] = $task;
		}
	}
	
	foreach ($tasks as $type => $def) {
		if(isset($db_tasks[$type])) {
			if($force) {
				$db_tasks[$type]->set($def);
				$db_tasks[$type]->set('deleted', 0);
				$db_tasks[$type]->save();
			} else {
				if($db_tasks[$type]->getField('deleted'))
					$db_tasks[$type]->markDeleted(false);
			}
		} else {
			$task = RowUpdate::blank_for_model('Schedule');
			$def['type'] = $type;
			$task->set($def);
			$task->save();
			$db_tasks[$type] = $task;
		}
	}
	
	foreach(array_diff_key($db_tasks, $tasks) as $obsolete => $_) {
		$db_tasks[$obsolete]->markDeleted();
	}
}
