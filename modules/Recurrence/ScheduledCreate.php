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
if (!defined('inScheduler')) die('Unauthorized access');

$time_limit = ini_get('max_execution_time');
set_time_limit(0);
$now = time();

require_once('modules/Recurrence/RecurrenceRule.php');
require_once('modules/Meetings/ActivityInvite.php');
require_once('include/TimeDate.php');
$timedate = new TimeDate();

global $beanList, $beanFiles;

$recur = new RecurrenceRule();

$time = strtotime(gmdate($timedate->get_db_date_time_format()));

$max_invocation_create = 30;
$start = time();

$empty_time = date($timedate->get_time_format(), strtotime("1999-10-10 00:00:00"));


$query = "SELECT parent_type, parent_id, MAX(instance_count) as c FROM recurrence_rules WHERE (limit_count IS NULL OR limit_count = 0 OR IFNULL(instance_count,0) < limit_count) GROUP BY parent_id";
$result = $recur->db->query($query, true, "Error retrieving recurrence list");

while($row = $recur->db->fetchByAssoc($result)) {
    $pid = $row['parent_id'];
	$max_instance_count = $row['c'];
	$parent_module = $row['parent_type'];
	if (isset($beanList[$parent_module])) {
        $bean_name = AppConfig::module_primary_bean($parent_module);
        require_bean($bean_name);
	} else {
		continue;
	}
	
	$focus = new $bean_name();
    $focus_result = ListQuery::quick_fetch($bean_name, $pid);
	if(! $focus_result)
		continue;

	$forward_times = explode("\n", $focus->forward_times);
	$date_field = $focus->get_recurrence_date_field();
	//$time_field = $focus->get_recurrence_time_field();
    $time_field = '';

	$max_forward = $focus->get_recurrence_forward_instances();
	$enum_to = $time + 32*24*3600; // $time + $focus->get_recurrence_scheduled_interval();

	$rules = $recur->retrieve_by_parent($parent_module, $pid, true);
	$rule_times = array();
	while(list($idx, $rule) = each($rules)) {
		$count = $rule->limit_count;
		if($count)
			$count = min($count - $rule->instance_count, $max_invocation_create);
		else
			$count = $max_invocation_create;
		if ($time_field) {
			$start = $recur->date_to_timestamp($timedate->to_display_date($focus_result->getField($date_field)), $timedate->to_display_time($focus_result->getField($time_field)));
		} else {
            if ($parent_module == 'Invoice') {
			    $start = $recur->date_to_timestamp($timedate->to_display_date($focus_result->getField($date_field)), $empty_time);
            } else {
                $start = $recur->date_to_timestamp($timedate->to_display_date_time($focus_result->getField($date_field)));
            }
        }
		if(empty($rule->date_last_instance))
			$enum_from = $start;
		else {
            $last_instance = strtotime($timedate->to_db($rule->date_last_instance) . ' GMT');
            $enum_from = $last_instance;
            if ($last_instance < $start)
                $enum_from = $start;
        }

		if ($rule->until) {
			$to = strtotime($timedate->to_db($rule->until) . ' GMT');
		} else {
			$to = $enum_to;
		}
		$times = $rule->get_recurrence_times($start, $enum_from + 60, $to, $count);
		foreach ($forward_times as $i => $tm) {
			if ($tm <= $time) unset($forward_times[$i]);
		}
		foreach ($times as $i => $tm) {
			if ($tm <= $enum_from) unset($times[$i]);
		}
		if(count($times))
			$rule_times[$idx] = $times;
	}

	$added_times = array();
	$modified = array();
	while(count($rule_times)) {
		$t = array();
		$idxs = array_keys($rule_times);
		foreach($idxs as $idx)
			$t[$idx] = $rule_times[$idx][0];
		$min_t = min($t);
		$rule_ids = array();
		foreach($idxs as $idx) {
			if($min_t == $rule_times[$idx][0]) {
				array_shift($rule_times[$idx]);
				if(!count($rule_times[$idx]))
					unset($rule_times[$idx]);
				$rule_ids[] = $idx;
			}
		}
		$added_times[] = array($min_t, $rule_ids);
	}
	
	$created = 0;
	foreach($added_times as $at) {
		list($add_time, $rule_ids) = $at;
		if (count($forward_times) >= $max_forward) break;
		if ($add_time > $time) {
			$forward_times[] = $add_time;
		}

        $new_upd = RowUpdate::blank_for_model($bean_name);
        $max_instance_count ++;
        $db_dt = gmdate($timedate->get_db_date_time_format(), $add_time);

        $update = $focus_result->row;
        unset($update['id']);
        unset($update['date_entered']);
        unset($update['date_modified']);
        $update['recurrence_of_id'] = $focus_result->getField('id');
        $update['recurrence_index'] = $max_instance_count;
        $update[$date_field] = $db_dt;

        $new_upd->set($update);
        $new_upd->save();

        if (! empty($focus->recur_copy_relations)) {
            $activityInvite = new ActivityInvite($focus_result->base_model);
            $invitees = $activityInvite->getList($focus_result);

            if (sizeof($invitees) > 0) {
                for ($i = 0; $i < sizeof($invitees); $i++) {
                    $link = $focus->recur_copy_relations[$invitees[$i]['module']];
                    if (! empty($link))
                        $new_upd->addUpdateLink($link, $invitees[$i]['id']);
                }
            }
        }

        $created ++;
		foreach($rule_ids as $idx) {
			$rules[$idx]->instance_count ++;
			$rules[$idx]->date_last_instance = $recur->timestamp_to_display_date($add_time);
			$modified[$idx] = true;
		}
	}

	$query = "UPDATE `{$focus->table_name}` SET forward_times = '" . $focus->db->quote(join("\n", $forward_times)) . "' WHERE id='{$focus->id}'";
	$focus->db->query($query);
	
	foreach($modified as $idx => $z) {
		$rules[$idx]->save();
	}
	
	/*if ($focus->module_dir == 'Invoice' && $created) {
		$account = new Account;
		if($account->retrieve($focus->billing_account_id)) {
			$account->update_balance(false, true, false);
			$account->save();
		}
		$account->cleanup();
	} */
	
	$focus->cleanup_list($rules);
	$focus->cleanup();

	if (time() - $now >= $time_limit - 2) break;
}

set_time_limit($time_limit);


?>
