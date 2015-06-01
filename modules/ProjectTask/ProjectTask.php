<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/**
 * Data access layer for the project_task table
 *
 * The contents of this file are subject to the SugarCRM Public License Version
 * 1.1.3 ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied.  See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * All copies of the Covered Code must include on each user interface screen:
 *    (i) the "Powered by SugarCRM" logo and
 *    (ii) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.  See full license for
 * requirements.
 *
 * The Original Code is: SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) 2004-2006 SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 */

// $Id: ProjectTask.php 7243 2010-05-10 04:49:11Z andrey $

require_once('data/SugarBean.php');

class ProjectTask extends SugarBean {
	// database table columns
	var $id;
	var $date_entered;
	var $date_modified;
	var $assigned_user_id;
	var $modified_user_id;
	var $created_by;


	// longreach - start added
	var $actual_cost;
	var $actual_cost_usd;
	var $estimated_cost;
	var $estimated_cost_usd;
	var $currency_id;
	var $exchange_rate;
	//var $hourly_cost;
	//var $hourly_cost_custom;
	// not stored
	var $calculated_cost;
	var $calculated_effort;
	var $use_timesheets; // this is carried over prom project

	var $portal_flag;
	// longreach - end added


	var $name;
	var $status;
	var $date_due;
	var $date_start;
	var $parent_id;
	var $priority;
	var $description;
	var $order_number;
	var $task_number;
	var $depends_on_id;
	var $milestone_flag;
	var $estimated_effort;
	var $actual_effort;
	var $utilization;
	var $percent_complete;
	var $deleted;

	// related information
	var $assigned_user_name;
	var $parent_name;
	var $depends_on_name;
	var $email_id;
	var $project_manager_id;
	var $dependency_type;

	var $include_weekends;




	var $table_name = 'project_task';
	var $object_name = 'ProjectTask';
	var $module_dir = 'ProjectTask';


	var $field_name_map;
	var $new_schema = true;

	// longreach - added
	var $additional_column_fields = array('project_manager_id');
	var $relationship_fields = array(
		'email_id' => 'emails',
	);
	
	static $book_prefetch = array();

	//////////////////////////////////////////////////////////////////
	// METHODS
	//////////////////////////////////////////////////////////////////

	/*
	 *
	 */
	function ProjectTask()
	{
		parent::SugarBean();
	}
	
	/*
	function save($notify = false)
    {
    	// this must be done before interpreting numeric values
		$this->unformat_all_fields();
		
        if ($this->use_timesheets) {
			$this->_get_booking_info(); // override any submitted values
			$this->percent_complete = ($this->estimated_effort == 0) ? 0 : round($this->actual_effort / $this->estimated_effort * 100);
		}
		if($this->status == 'Completed')
			$this->percent_complete = 100;
		else if($this->status == 'Not Started')
			$this->percent_complete = 0;

		$is_new = empty($this->id);
		//$currency_changed = $this->field_value_has_changed('currency_id');
		
		require_once('modules/Currencies/Currency.php');
		$currency = new Currency();
		// inits $currency
		$rate_changed = adjust_exchange_rate($this, $currency);
		
        $ret = parent::save($notify);
		
		$this->load_relationship('booking_users');
		$this->booking_users->add($this->assigned_user_id);
		
		// update custom hourly rates for assigned resources
		if(!$is_new && $rate_changed) {
    		global $locale;
			$real_round = (int)$locale->getPrecedentPreference('default_currency_significant_digits');
			$rate = (float)$currency->conversion_rate;
			$query = "UPDATE projecttasks_users SET hourly_cost = round(hourly_cost_usdollar * $rate, $real_round) WHERE projecttask_id='{$this->id}'";
			$this->db->query($query, true, "Error updating hourly rates");
			$GLOBALS['log']->debug("updated hourly rates for ProjectTask $this->id");
		}
		
		// update progress indicators
		if(empty($this->disable_project_update)) {
			require_once('modules/Project/Project.php');
			$project = new Project;
			if($project->retrieve($this->parent_id) && ! $project->deleted) {
				$project->save(); // recalculate progress
				$financials =& $project->get_financials();
				$financials->calculate_costs($project->use_timesheets);
				$financials->save();
			}
			$project->cleanup();
		}
		
		$currency->cleanup();
		return $ret;
    }*/
	

	// longreach - start added - produce subpanel data
	function booking_users() {
		$rate = "IF(ptu.hourly_cost_custom, ptu.hourly_cost_usdollar, empl.std_hourly_rate_usdollar)";
		$query = "SELECT ptu.*, $rate AS task_hourly_rate, empl.std_hourly_rate_usdollar, empl.std_hourly_rate, ".
			"ptu.estim_hours * $rate AS estim_cost, ".
			"SUM(booked_hours.quantity) AS actual_hours, SUM(booked_hours.paid_total_usd) AS actual_cost, ".
			"pt.currency_id, pt.exchange_rate, user.id, user.first_name, user.last_name, user.user_name ".
			"FROM projecttasks_users ptu ".
			"LEFT JOIN project_task pt ON pt.id=ptu.projecttask_id ".
			"LEFT JOIN booked_hours ON booked_hours.related_type='ProjectTask' AND NOT booked_hours.deleted ".
				"AND booked_hours.related_id='$this->id' AND booked_hours.assigned_user_id=ptu.user_id ".
			"LEFT JOIN users user ON user.id=ptu.user_id ".
			"LEFT JOIN employees empl ON empl.user_id=ptu.user_id ".
			"WHERE ptu.projecttask_id='$this->id' AND NOT ptu.deleted ".
			"GROUP BY ptu.user_id";
		return $query;
	}

    function mark_deleted($id)
    {
        $this->retrieve($id);
        parent::mark_deleted($id);
        require_once('modules/Project/Project.php');
        $project = new Project;
        if ($project->retrieve($this->parent_id)) {
            $financials =& $project->get_financials();
            $financials->calculate_costs($project->use_timesheets);
            $financials->save();
        }
	}

	function handleDates()
	{
		global $timedate;
		
		$mergetime = $timedate->merge_date_time($this->date_due,$this->time_due);
		$this->date_due = $timedate->to_db_date($mergetime);
		$this->time_due = $timedate->to_db_time($mergetime);
		$mergetime = $timedate->merge_date_time($this->date_due,$this->time_due);
		$this->date_due = $timedate->to_display_date($mergetime, false);
		$this->time_due = $timedate->to_display_time($mergetime, true, false);

		$mergetime = $timedate->merge_date_time($this->date_start,$this->time_start);
		$this->date_start = $timedate->to_db_date($mergetime);
		$this->time_start = $timedate->to_db_time($mergetime);
		$mergetime = $timedate->merge_date_time($this->date_start,$this->time_start);
		$this->date_start = $timedate->to_display_date($mergetime, false);
		$this->time_start = $timedate->to_display_time($mergetime, true, false);
	}

	function set_notification_body($xtpl, $task)
	{
		global $app_list_strings;		

		$this->_get_parent_name($this->parent_id);

		$xtpl->assign("TASK_SUBJECT", $task->name);
		$xtpl->assign("TASK_PRIORITY", (isset($task->priority)? $app_list_strings['project_task_priority_options'][$task->priority]:"") );
		$xtpl->assign("TASK_DUEDATE", $task->date_due);
		$xtpl->assign("TASK_STARTDATE", $task->date_start);
		$xtpl->assign("TASK_STATUS", (isset($task->status)?$app_list_strings['task_status_dom'][$task->status]:""));
		$xtpl->assign("TASK_DESCRIPTION", $task->description);
		$xtpl->assign("TASK_PROJECTNAME", $task->parent_name);

		return $xtpl;
	}
	
	// longreach - end added


	

	// longreach - rewritten using new
	// getACLTagName helper	
	function listviewACLHelper(){
		$array_assign = parent::listviewACLHelper();
		$array_assign['PARENT'] = $this->getACLTagName('parent_name_owner', 'Project');
		$array_assign['DEPENDS'] = $this->getACLTagName('depends_on_name_owner', 'ProjectTask');
        return $array_assign;
	}


    /**
     * @static
     * @param string $project_id
     * @return bool
     */
    static function use_timesheets($project_id) {
        $use = 0;
        $project = ListQuery::quick_fetch_row('Project', $project_id, array('use_timesheets'));
        if ($project != null)
            $use = $project['use_timesheets'];

        return (bool)$use;
    }

    /**
     * Load booking info like costs and efforts
     *
     * @static
     * @param string $id
     * @param string $currency_id
     * @param float|null $exchange_rate
     * @return array
     */
    static function get_booking_info($id, $currency_id = '-99', $exchange_rate = null) {
        require_once('modules/Currencies/Currency.php');
        global $db;
        $ret = array();
        $fields = array('estimated_effort', 'estimated_cost_usd', 'actual_effort', 'actual_cost_usd');
        foreach ($fields as $f) $ret[$f] = 0.0;

        $rate = "IF(ptu.hourly_cost_custom, ptu.hourly_cost_usdollar, empl.std_hourly_rate_usdollar)";

        $query = "SELECT $rate as hourly_rate, ptu.estim_hours AS estimated_effort, ".
            "ptu.estim_hours * $rate AS estimated_cost_usd ".
            "FROM projecttasks_users ptu ".
            "LEFT JOIN employees empl ON empl.user_id = ptu.user_id ".
            "WHERE ptu.projecttask_id = '{$id}' AND NOT ptu.deleted ".
            "GROUP BY ptu.projecttask_id, ptu.user_id";

        $result = $db->query($query);

        while($row = $db->fetchByAssoc($result)) {
            $ret['estimated_effort'] += $row['estimated_effort'];
            $ret['estimated_cost_usd'] += $row['estimated_cost_usd'];
        }

        $query = "SELECT hrs.paid_total, hrs.paid_total_usd, hrs.quantity, hrs.paid_currency_id,
            hrs.paid_exchange_rate, pt.currency_id, pt.exchange_rate
            FROM booked_hours hrs
            LEFT JOIN projecttasks_users ptu ON ptu.projecttask_id = '{$id}'
            AND hrs.assigned_user_id = ptu.user_id AND hrs.related_id = ptu.projecttask_id
            LEFT JOIN project_task pt ON pt.id = ptu.projecttask_id
            WHERE NOT hrs.deleted AND hrs.status = 'approved' AND hrs.related_type= 'ProjectTask' AND NOT ptu.deleted";

        $result = $db->query($query);

        while($row = $db->fetchByAssoc($result)) {
            $ret['actual_effort'] += $row['quantity'];
            $rate = $row['exchange_rate'];

            if($row['paid_currency_id'] == $row['currency_id'] && $rate) {
                $ret['actual_cost_usd'] += $row['paid_total'] / $rate;
            } else {
                $ret['actual_cost_usd'] += $row['paid_total_usd'];
            }
        }

        $currency = new Currency();
        $currency->retrieve($currency_id);
        //if(! empty($exchange_rate))
            //$currency->conversion_rate = $exchange_rate;

        $ret['estimated_cost'] = $currency->convertFromDollar($ret['estimated_cost_usd']);
        $ret['actual_cost'] = $currency->convertFromDollar($ret['actual_cost_usd']);
        $currency->cleanup();

        return $ret;
    }

    static function init_record(RowUpdate &$upd, $input) {
        $update = array();
        $update['portal_flag'] = 1;
        $update['priority'] = 'P1';

        if(! empty($input['parent_id'])) {
            $project = ListQuery::quick_fetch_row('Project', $input['parent_id']);
            if($project != null) {
                $update['currency_id'] = $project['currency_id'];
                $update['exchange_rate'] = $project['exchange_rate'];
            }
        }
        $upd->set($update);
    }

    static function recalc_financial(RowUpdate $upd) {
    	$proj = ListQuery::quick_fetch('Project', $upd->getField('parent_id'));
    	$proj_upd = RowUpdate::for_result($proj);
        require_once('modules/Project/ProjectFinancials.php');
		$financials = new ProjectFinancials();
		$financials->load($proj_upd);
		$financials->calculate_costs($proj_upd->getField('use_timesheets'));
		$financials->save();
		self::recalc_resource_utilization($upd);
	}

	function recalc_resource_utilization(RowUpdate $upd)
	{
		$fields = array('date_start' => 'date_start!date_only', 'date_due' => 'date_due!date_only', 'include_weekends' => 'include_weekends');
		foreach ($fields as $f => $f2) {
			$$f = $upd->getField($f);
			if (is_null($$f))
			$$f = $upd->getField($f2, null, true);
		}
		$task_hours =  calc_task_hours($date_start, $date_due, $include_weekends);

		$lq = new ListQuery(
			'ProjectTask', 
			array('id', '~join.estim_hours'), 
			array('link_name' => 'booking_users', 'parent_key' => $upd->getPrimaryKeyValue())
		);
		foreach($lq->fetchAllRows() as $user) {
            $upd->addUpdateLink('booking_users', $user['id'], array(
				$user['id'] => array(
					'name' => 'utilization',
					'value' => $user['~join.estim_hours'] / $task_hours * 100,
				))
			);
		}
	}

    static function preupdate_data(RowUpdate $upd) {
        $id = $upd->getPrimaryKeyValue();
        $status = $upd->getField('status');
        $project_id = $upd->getField('parent_id');
        $use_timesheets = ProjectTask::use_timesheets($project_id);
        $percent_complete = -1;

        if ($use_timesheets && ! $upd->new_record) {
            $exchange_rate = $upd->getField('exchange_rate');
            $currency_id = $upd->getField('currency_id');

            if (! $currency_id)
                $currency_id = '-99';

            $booking_info = ProjectTask::get_booking_info($id, $currency_id, $exchange_rate);
			$percent_complete = ($booking_info['estimated_effort'] == 0) ? 0 : round($booking_info['actual_effort'] / $booking_info['estimated_effort'] * 100);
		}

		if($status == 'Completed')
			$percent_complete = 100;
		else if($status == 'Not Started')
			$percent_complete = 0;

        if ($percent_complete != -1)
            $upd->set(array('percent_complete' => $percent_complete));
    }

    static function send_notification(RowUpdate $upd) {
        $vars = array(
            'TASK_SUBJECT' => array('field' => 'name', 'in_subject' => true),
            'TASK_PRIORITY' => array('field' => 'priority'),
            'TASK_DUEDATE' => array('field' => 'date_due!date_only'),
            'TASK_STARTDATE' => array('field' => 'date_start!date_only'),
            'TASK_STATUS' => array('field' => 'status'),
            'TASK_PROJECTNAME' => array('field' => 'parent'),
            'TASK_DESCRIPTION' => array('field' => 'description')
		);
		
        $manager = new NotificationManager($upd, 'ProjectTaskAssigned', $vars);

        if ($manager->wasRecordReassigned())
            $manager->sendMails();
    }
	
	function _get_booking_info($id_list=null)
	{
	}
	
	static function update_assigned_user(RowUpdate $update) {
        $new_assigned_user = $update->getField('assigned_user_id');
		$update->addUpdateLink('booking_users', $new_assigned_user);
	}

    static function get_activity_status(RowUpdate $upd) {
        $status = null;

        if ($upd->getField('status') == 'Completed' && $upd->getField('status', null, true) != 'Completed') {
            $status = 'closed';
        } elseif ($upd->getField('status') == 'In Progress' && $upd->getField('status', null, true) == 'Completed') {
            $status = 'reopened';
        }

        return $status;
    }
}
?>
