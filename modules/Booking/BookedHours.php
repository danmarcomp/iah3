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
require_once('modules/BookingCategories/BookingCategory.php');


class BookedHours extends SugarBean {
	
	// Standard stored fields
	var $id;
	var $date_entered;
	var $date_modified;
	var $modified_user_id;
	var $assigned_user_id;
	
	// Relevant stored fields
	var $name;
	var $status;
	var $date_start;
	var $quantity;
	var $account_id;
	var $invoice_id;
	var $timesheet_id;
	var $related_type;
	var $related_id;
	var $tax_code_id;
	var $booking_category_id;
	var $booking_class;

	var $billing_rate;
	var $billing_rate_usd;
	var $billing_total;
	var $billing_total_usd;
	var $billing_currency_id;
	var $billing_exchange_rate;
	var $paid_rate;
	var $paid_rate_usd;
	var $paid_total;
	var $paid_total_usd;
	var $paid_currency_id;
	var $paid_exchange_rate;

	// Not stored
	var $assigned_user_name;
	var $account_name, $account_owner;
	var $invoice_name, $invoice_number, $invoice_owner;
	var $timesheet_name, $timesheet_owner;
	var $related_name, $related_owner;
	var $booking_category_name;
	var $tax_code_name;
	
    var $object_name = 'BookedHours';
	var $module_dir = 'Booking';
	var $new_schema = true;
	var $table_name = 'booked_hours';

	// This is used to populate additional fields on the DetailView
	var $additional_column_fields = Array(
		'assigned_user_name', 'modified_user_name', 'created_by_name',
		'account_name', 'invoice_name', 'invoice_number', 'timesheet_name', 'related_name',
		'booking_category_name', 'tax_code_name',
	);
	
	var $category_fields = array(
		'paid_rate', 'paid_rate_usd', 'paid_exchange_rate', 'paid_currency_id',
		'billing_rate', 'billing_rate_usd', 'billing_exchange_rate', 'billing_currency_id',
		'tax_code_id', 'booking_class',
	);
	
	var $bookable_classes = array(
		'work' => array('Cases' => 'BookableCases', 'ProjectTask' => 'BookableProjectTasks'),
	);

	
	function lookup_related_name($related_type, $related_id, $extended=false, $encoded=true) {
        $names = array(
			'Cases' => array(
				'table' => 'cases',
				'name_field' => 'rel.name',
				'parent_id' => "''",
				'parent_name' => "''",
				'parent_type' => "''",
				'join' => '',
			),
			'ProjectTask' => array(
				'table' => 'project_task',
				'name_field' => 'rel.name',
				'parent_id' => 'parent_id',
				'parent_name' => 'project.name',
				'join' => ' LEFT JOIN project ON project.id = rel.parent_id',
				'parent_type' => "'Project'",
			),
        );
		if($extended) {
			global $beanList, $beanFiles;
			if(! empty($this->related_id) && isset($names[$this->related_type])) {
				$objname = $beanList[$this->related_type];
				require_once($beanFiles[$objname]);
				$seed = new $objname;
				if($seed->retrieve($this->related_id, $encoded)) {
					$seed->fetch_account_name = true;
					$seed->fill_in_additional_list_fields();
					return array(
						'related_name' => $seed->name,
						'related_owner' => $seed->assigned_user_id,
						'account_id' => $seed->account_id,
						'account_name' => $seed->account_name,
					);
				}
			}
		}
		else {
			if(! empty($names[$related_type]) && ! empty($related_id)) {
				$n = $names[$related_type];
				extract($n);
				if(is_array($related_id)) {
					$ids = array();
					foreach($related_id as $id) $ids[] = $this->db->quote($id);
					$where = "rel.id IN ('".implode("','", $ids)."')";
				}
				else
					$where = sprintf("rel.id='%s' LIMIT 1", $this->db->quote($related_id));
				$query = sprintf(
					"SELECT rel.id, %s AS related_name, rel.assigned_user_id as related_owner,%s AS parent_id, %s AS parent_name, %s AS parent_type FROM %s rel %s WHERE $where",
					$name_field, $parent_id, $parent_name, $parent_type, $table, $join);
				$result = $this->db->query($query, true, "Error filling in additional detail fields: ");
				$ret = array();
				while($row = $this->db->fetchByAssoc($result, -1, $encoded))
					$ret[$row['id']] = $row;
				if(! is_array($related_id) && count($ret))
					return current($ret);
				return $ret;
			}
		}
		return array();
	}
	
	function fetch_related_name($extended=false) {
		$this->related_name = $this->related_owner = '';
		$data = $this->lookup_related_name($this->related_type, $this->related_id, $extended);
		foreach($data as $f => $v)
			if($f != 'id') $this->$f = $v;
	}
	
	function init_inherited_values($set_totals=true) {
		$hours = $this->quantity / 60.0;
		$this->fetch_related_name(true);
		$cat =& $this->get_booking_category();
		$cat->format_all_fields();
		foreach($this->category_fields as $f) {
			if(empty($this->$f) || $f == 'booking_class')
				$this->$f = $cat->$f;
		}
		if($this->booking_class != 'unpaid-leave') {
			$specific = $this->get_user_assignment_info($this->related_type, $this->related_id, $this->assigned_user_id);
			if(! $specific)
				return false;
			if(is_array($specific) && ! empty($specific['hourly_cost_custom'])) {
				$this->paid_rate = $specific['hourly_cost'];
				$this->paid_rate_usd = $specific['hourly_cost_usdollar'];
				$this->paid_currency_id = $specific['custom_currency_id'];
				$this->paid_exchange_rate = $specific['custom_exchange_rate'];
			}
			else {
				$salary = $this->get_user_salary();
				$this->paid_rate = $salary['std_hourly_rate'];
				$this->paid_rate_usd = $salary['std_hourly_rate_usdollar'];
				$this->paid_currency_id = $salary['salary_currency_id'];
				$this->paid_exchange_rate = $salary['salary_exchange_rate'];
			}
		}
		if($set_totals) {
			$this->paid_total = $this->paid_rate * $hours;
			$this->paid_total_usd = $this->paid_rate_usd * $hours;
			$this->billing_total = $this->billing_rate * $hours;
			$this->billing_total_usd = $this->billing_rate_usd * $hours;
		}
		return true;
	}

	
	function &get_booking_category($reload=false) {
		static $cat;
		if(! isset($cat) || $reload) {
			$cat = new BookingCategory();
			$cat->retrieve($this->booking_category_id);
		}
		return $cat;
	}

	function save($check_notify = FALSE) {
		require_once('modules/Currencies/Currency.php');

		$hours = $this->quantity / 60.0;

		// this must be done before interpreting numeric values
		$this->unformat_all_fields();
		
		// booking class must match that of booking category
		$cat =& $this->get_booking_category(true);
		$this->booking_class = $cat->booking_class;

		$bill_currency = new Currency();
		$bill_currency->retrieve($this->billing_currency_id);
		$params = array('currency_field' => 'billing_currency_id', 'rate_field' => 'billing_exchange_rate');
		$rate_changed = adjust_exchange_rate($this, $bill_currency, $params);
		$this->billing_rate_usd = $bill_currency->convertToDollar($this->billing_rate);
		$this->billing_total = $this->billing_rate * $hours;
		$this->billing_total_usd = $bill_currency->convertToDollar($this->billing_total);
		
		$paid_currency = new Currency();
		$paid_currency->retrieve($this->paid_currency_id);
		$params = array('currency_field' => 'paid_currency_id', 'rate_field' => 'paid_exchange_rate');
		$rate_changed = adjust_exchange_rate($this, $paid_currency, $params);
		$this->paid_rate_usd = $paid_currency->convertToDollar($this->paid_rate);
		$this->paid_total = $this->paid_rate * $hours;
		$this->paid_total_usd = $paid_currency->convertToDollar($this->paid_total);
		

		if (empty($this->id) || $this->new_with_id) {
			if (AppConfig::setting('company.approve_booking_' . $this->related_type)) {
				$this->status = 'Approved';
			}
		}
		
		$ret = parent::save($check_notify);
		
		if($this->related_type == 'ProjectTask' && empty($this->disable_project_update)) {
			$this->updateProject();
		}
		if($this->related_type == 'Cases' && empty($this->disable_case_update)) {
			$this->updateCaseHours();
		}
		
		return $ret;
	}

	function updateCaseHours()
	{
		$case_id = $this->related_id;
		require_once 'modules/Cases/Case.php';
		$case = new aCase;
		if($case->retrieve($case_id) && ! $case->deleted) {
			$query = "SELECT SUM(quantity) AS total FROM booked_hours WHERE related_id='{$case_id}' AND deleted = 0 AND status = 'Approved'";
			$res = $this->db->query($query, true);
			$row = $this->db->fetchByAssoc($res);
			$case->effort_actual = (float)$row['total'];
			$case->effort_actual_unit = 'hours';
			$case->prohibit_workflow = true;
			$case->save(false);
		}
		$case->cleanup();
	}

	function updateProject()
	{
		// saving the ProjectTask causes an update of the related Project
		require_once('modules/ProjectTask/ProjectTask.php');
		$task = new ProjectTask;
		if ($task->retrieve($this->related_id) && !$task->deleted) {
			$task->save();
		}
		$task->cleanup();
	}
    
	/*function getDefaultListWhereClause() {
		return "";
	}*/
	
	// FIXME - function mark_deleted must update related Timesheet's total hours?
	function mark_deleted($id)
	{
		$hr = new BookedHours;
		$hr->retrieve($id);
		parent::mark_deleted($id);
		if($hr->id && $hr->related_type == 'Cases') {
			$hr->updateCaseHours();
		}
		if($hr->id && $hr->related_type == 'ProjectTask') {
			$hr->updateProject();
		}
		$hr->cleanup();
	}

	// used by JSON interface and Timesheets module
	function &query_hours($encode, $ts_id, $uid='', $status='', $date_start='', $date_end='', $offset = false) {
        require_once('include/layout/forms/EditableForm.php');
		global $timedate, $db;
		// more fields may be made accessible to certain users
		$fields = array(
			'tbl.id', 'tbl.date_start',
			'tbl.name', 'tbl.status', 'tbl.quantity',
			'tbl.related_type', 'tbl.related_id',
			'tbl.booking_class', 'tbl.account_id',
			'acc.name as account_name',
		);

		$fields = implode(', ', $fields);
		$where = array();
		if($ts_id && $ts_id != 'none')
			$where[] = sprintf("tbl.timesheet_id='%s'", $db->quote($ts_id));
		else {
			if($ts_id == 'none')
				$where[] = "(tbl.timesheet_id='' || tbl.timesheet_id IS NULL)";
			$where[] = sprintf("tbl.assigned_user_id='%s'", $db->quote($uid));
			if($status)
				$where[] = sprintf("tbl.status='%s'", $db->quote($status));

			$tz_offset = $timedate->getTimeZoneOffset($timedate->getUserTimeZone(), true);
			$start_epoch = strtotime($date_start." 00:00:00") - $tz_offset;
			$end_epoch = strtotime($date_end." 23:59:59") - $tz_offset;

			$where[] = sprintf(
				"tbl.date_start BETWEEN '%s' AND '%s'",
				$db->quote(date("Y-m-d H:i:s", $start_epoch)),
				$db->quote(date("Y-m-d H:i:s", $end_epoch)));
		}
		$where[] = 'NOT tbl.deleted';
		$where = implode(' AND ', $where);
		$query = "SELECT $fields FROM {$this->table_name} tbl ";
		$query .= "LEFT JOIN accounts acc ON acc.id=tbl.account_id ";
		$query .= "WHERE $where ORDER BY tbl.date_start";
		$result = $db->query($query, true, "Error retrieving booked hours");
		$ret = array();
		$to_lookup = array();
		while($row = $db->fetchByAssoc($result, -1, $encode)) {
			if ($offset) {
				$datetime = $timedate->handle_offset($row['date_start'], 'Y-m-d H:i:s');
				$row['date_start'] = $datetime;
			}
            $row['quantity_formatted'] = EditableForm::format_duration($row['quantity']);
			$row['related_name'] = '';
			$ret[$row['id']] = $row;
			if($row['related_type'] && $row['related_id'])
				$to_lookup[$row['related_type']][] = $row['related_id'];
		}
		if(count($to_lookup)) {
			$related_names = array();
			foreach($to_lookup as $mod => $ids) {
				$related_names[$mod] = $this->lookup_related_name($mod, $ids, false, $encode);
			}
			foreach($ret as $id => $row) {
				if(isset($related_names[$row['related_type']])) {
					if($info = array_get_default($related_names[$row['related_type']], $row['related_id'])) {
						$ret[$id]['related_name'] = $info['related_name'];
						$ret[$id]['parent_id'] = $info['parent_id'];
						$ret[$id]['parent_name'] = $info['parent_name'];
						$ret[$id]['parent_type'] = $info['parent_type'];
					}
				}
			}
		}
		return $ret;
	}
	
	function get_user_salary($uid=null) {
		if(empty($uid))
			$uid = $this->assigned_user_id;
		$query = sprintf(
			"SELECT empl.std_hourly_rate_usdollar, empl.std_hourly_rate, ".
			"empl.salary_currency_id, empl.salary_exchange_rate ".
			"FROM employees empl WHERE empl.user_id='%s'",
			$this->db->quote($uid));
		$result = $this->db->query($query, true, "Error retrieving user salary information");
		if($row = $this->db->fetchByAssoc($result))
			return $row;
		return false;	
	}
	
	// static
	function get_user_assignment_info($related_type, $related_id, $uid=null) {
		global $db;
		global $current_user;
		if(! $uid) $uid = $current_user->id;
		if($related_type == 'ProjectTask') {
			//$rate = "IF(ptu.hourly_cost_custom, ptu.hourly_cost_usdollar, empl.std_hourly_rate_usdollar)";
			$query = "SELECT ptu.*, ".
				"pt.currency_id AS custom_currency_id, pt.exchange_rate as custom_exchange_rate ".
				"FROM project_task pt ".
				"LEFT JOIN project proj ON proj.id=pt.parent_id AND NOT proj.deleted ".
				"LEFT JOIN projecttasks_users ptu ON pt.id=ptu.projecttask_id AND ptu.user_id='%s' AND NOT ptu.deleted ".
				"WHERE pt.id='%s' ".
					"AND ptu.booking_status='Active' ".
					"AND NOT pt.deleted AND pt.status='In Progress' ".
					"AND proj.project_phase='Active - In Progress' ".
				"LIMIT 1";
			$query = sprintf($query, $db->quote($uid), $db->quote($related_id));
			$result = $db->query($query, true, "Error retrieving project task assignments");
			if($row = $db->fetchByAssoc($result))
				return $row;
			return false;
		}
		else if($related_type == 'Cases') {
			$result = ListQuery::quick_fetch_row('aCase', $related_id, array('status', 'assigned_user_id'));
			if($result && substr($result['status'], 0, 8) != 'Closed -' && $result['assigned_user_id'] == $uid)
				return true;
		}
		return false;
	}
	
	function get_view_billed_where_advanced($param)
	{
		return '1';
	}

	function get_view_billed_where_basic($param)
	{
		return empty($param['value']) ? "(booked_hours.booking_class != 'billable-work' OR IFNULL(booked_hours.invoice_id,'')='')" : '1';
	}
	
	function get_billing_status_where_advanced($param)
	{
		if($param['value'] == 'billed')
			return "(booked_hours.booking_class = 'billable-work' AND IFNULL(booked_hours.invoice_id,'') <> '')";
		else if($param['value'] == 'notbilled')
			return "(booked_hours.booking_class = 'billable-work' AND IFNULL(booked_hours.invoice_id,'') = '')";
		return '1';
	}

	function get_billing_status_where_basic($param)
	{
		return '1';
	}
	
	function getDefaultListWhereClause()
	{
		return "(booked_hours.booking_class != 'billable-work' OR IFNULL(booked_hours.invoice_id,'')='')";
	}

	
	// static - TODO: move logic into Bookable* classes?
	static function can_book_related_to($related_type, $related_id, $uid=null) {
		return BookedHours::get_user_assignment_info($related_type, $related_id, $uid);
	}
	
	// static - TODO: move logic into Bookable* classes?
	static function get_hours_pending_invoice($id, $model) {
		global $db;
		if($model == 'Project') {
			$query = sprintf(
				"SELECT hrs.id FROM booked_hours hrs ".
				"LEFT JOIN project_task pt ON pt.id=hrs.related_id AND NOT pt.deleted ".
				"LEFT JOIN project ON pt.parent_id=project.id ".
				"WHERE project.id='%s' AND NOT project.deleted ".
					"AND hrs.booking_class='billable-work' AND hrs.status='approved' ".
					"AND IFNULL(hrs.invoice_id,'')='' AND NOT hrs.deleted ",
				$db->quote($id));
		}
		else if($model == 'aCase') {
			$query = sprintf(
				"SELECT hrs.id FROM booked_hours hrs ".
				"LEFT JOIN cases ON cases.id=hrs.related_id AND NOT cases.deleted ".
				"WHERE cases.id='%s' AND NOT cases.deleted ".
					"AND hrs.booking_class='billable-work' AND hrs.status='approved' ".
					"AND IFNULL(hrs.invoice_id,'')='' AND NOT hrs.deleted ",
				$db->quote($id));
		}
		if(! empty($query)) {
			$seed = new BookedHours();
			// results are not to be HTML encoded
			$result = $seed->build_related_list($query, $seed, 0, -1, false);
		}
		else
			$result = array();
		return $result;
	}
	
	
	static function init_record(RowUpdate &$upd, $input) {
        global $timedate, $current_user;
        $update = array();

        $related_ids = array(
            'projecttask_id' => 'ProjectTask',
            'acase_id' => 'Cases',
        );

        foreach($related_ids as $f => $mod) {
            if(! empty($input[$f])) {
                $update['related_type'] = $mod;
                $update['related_id'] = $input[$f];
            }
        }

        if(! empty($input['duration_hours']) || !empty($input['duration_minutes'])) {
			$update['quantity'] = array_get_default($input, 'duration_hours', 0) * 60 + array_get_default($input, 'duration_minutes', 0);
		}
        if(! empty($input['related_type']))
            $update['related_type'] = $input['related_type'];
        if(! empty($input['related_id']))
            $update['related_id'] = $input['related_id'];

        if (! empty($input['related_id']) && ! empty($input['related_type'])) {
            $bean_name = AppConfig::setting("modinfo.primary_beans.".$input['related_type']);
            $relate_obj = null;
            if ($bean_name)
                $relate_obj = ListQuery::quick_fetch_row($bean_name, $input['related_id']);

            if ($relate_obj != null) {
                if ($bean_name == 'ProjectTask') {
                    $parent_project = ListQuery::quick_fetch_row('Project', $relate_obj['parent_id'], array('account_id'));
                    if ($parent_project != null && isset($parent_project['account_id']))
                        $update['account_id'] = $parent_project['account_id'];
                } elseif (isset($relate_obj['account_id'])) {
                    $update['account_id'] = $relate_obj['account_id'];
                }
            }
        }

        if (! empty($input['assigned_user_id'])) {
            $update['assigned_user_id'] = $input['assigned_user_id'];
        } else {
            $update['assigned_user_id'] = $current_user->id;
        }

		$ts = floor(time() / 3600) * 3600;
        $update['date_start'] = gmdate($timedate->get_db_date_time_format(), $ts);
        $update['status'] = 'pending';
        $update['tax_code_id'] = '-99';

		$upd->set($update);
		self::init_rates($upd);
	}

	static function init_rates(RowUpdate $upd)
	{
		if (!$upd->getField('paid_rate')) {
			$rate = null;
			$lq = new ListQuery('Employee');
			$lq->addSimpleFilter('user_id', $upd->getField('assigned_user_id'));
			$emp = $lq->runQuerySingle();
			if (($task_id = $upd->getField('related_id')) && $upd->getField('related_type') == 'ProjectTask') {
				$task = ListQuery::quick_fetch('ProjectTask', $task_id);
				if ($task && ! $task->failed) {
					$lq = new ListQuery('ProjectTask', true, array('link_name' => 'booking_users'));
					$lq->addField('~join.hourly_cost', 'hourly_cost');
					$lq->addField('~join.hourly_cost_custom', 'hourly_cost_custom');
					$lq->setParentKey($task_id);
					$lq->addSimpleFilter('~join.user_id', $upd->getField('assigned_user_id'));
					$res = $lq->runQuerySingle();
					if (!$res->failed && $res->getField('hourly_cost_custom')) {
						$rate = $res->getField('hourly_cost');
						$currency = $task->getField('currency_id');
						$exchr = $task->getField('exchange_rate');
					}
				}
			}
			if (is_null($rate)) {
				if ($emp && !$emp->failed) {
					$rate = $emp->getField('std_hourly_rate');
					$currency = $emp->getField('salary_currency_id');
					$exchr = $emp->getField('salary_exchange_rate');
				} else {
					return;
				}
			}
			$upd->set('paid_rate', $rate);
			$upd->set('paid_currency_id', $currency);
			$upd->set('paid_exchange_rate', $exchr);
			$upd->set('paid_total', $upd->getField('paid_rate') * ($upd->getField('quantity') /  60));
		}
	}

    static function init_dialog(RowUpdate &$upd, $input) {
        global $timedate;
        $update = array();

        if (! empty($input['timesheet_id']))
            $update['timesheet_id'] = $input['timesheet_id'];

        if (! empty($input['date_start']) && (! empty($input['time_hour_start']) || ! empty($input['time_start'])) ) {
            $dt_start = $input['date_start'];
            if(! preg_match('/\d{4}-\d{2}-\d{2}/', $dt_start))
                $dt_start = gmdate('Y-m-d');

            if (! empty($input['time_start'])) {

                $tm_start = $input['time_start'];
                if(! preg_match('/\d{2}:\d{2}(:\d{2})/', $tm_start))
                    $tm_start = gmdate('H:i:s');

            } else if(isset($input['time_hour_start'])) {

                $h = $input['time_hour_start'];
                $m = array_get_default($input, 'time_minute_start', '00');
                $tm_start = sprintf('%02d:%02d:00', $h, $m);

            } else {
                $tm_start = '';
            }

            $update['date_start'] = $timedate->handle_offset($dt_start .' '.$tm_start, $timedate->get_db_date_time_format(), false);
        } elseif (! empty($input['date_start'])) {
            $update['date_start'] = $timedate->handle_offset($input['date_start'], $timedate->get_db_date_time_format(), false);
        }
        
        if (isset($input['duration_hours']) || isset($input['duration_minutes'])) {
            $duration_mins = ($input['duration_hours'] * 60) + $input['duration_minutes'];
            $update['quantity'] = $duration_mins;
        }

        $update['status'] = 'pending';
        $update['tax_code_id'] = '-99';

        if (sizeof($update) > 0)
            $upd->set($update);
    }

    static function set_booking_class(RowUpdate $upd) {
        $cat_id = $upd->getField('booking_category_id');

        if ($cat_id) {
            $cat = ListQuery::quick_fetch_row('BookingCategory', $cat_id, array('booking_class'));

            if (! empty($cat['booking_class']))
                $upd->set(array('booking_class' => $cat['booking_class']));
        }
    }

    static function recalc_project_financial(RowUpdate $upd) {
        if ($upd->getField('related_type') == 'ProjectTask') {
            $project_task = ListQuery::quick_fetch('ProjectTask', $upd->getField('related_id'));

            if ($project_task) {
                require_bean('ProjectTask');
                ProjectTask::recalc_financial(RowUpdate::for_result($project_task));
            }
        }
    }

	static function update_case(RowUpdate $upd) {
        if ($upd->getField('related_type') == 'Cases') {
            $case_id = $upd->getField('related_id');

            if ($case_id) {
                require_bean('aCase');
                aCase::update_time_used($case_id);
            }
        }
    }

    static function can_book(DetailManager $mgr) {
        $record = $mgr->getRecord();

        if ($record->new_record) {
            global $mod_strings, $app_strings;
            $related_type = $record->getField('related_type');
            $related_id = $record->getField('related_id');

            if((! empty($related_type) && ! empty($related_id)) && ! BookedHours::can_book_related_to($related_type, $related_id)) {
                $msg = $mod_strings['NTC_BOOKING_NOT_ALLOWED'];
                $type = strtoupper($related_type);

                if(isset($mod_strings['NTC_BOOKING_NOT_ALLOWED_'.$type]))
                    $msg .= '<br><br>'.$mod_strings['NTC_BOOKING_NOT_ALLOWED_'.$type];
                if(isset($_REQUEST['return_module'])) {
                    $return_module = $_REQUEST['return_module'];
                    $return_action = $_REQUEST['return_action'];
                    $return_id = $_REQUEST['return_record'];
                } else {
                    $return_module = $related_type;
                    $return_action = 'DetailView';
                    $return_id = $related_id;
                }
                $msg .= sprintf('<br><br><a href="index.php?module=%s&action=%s&record=%s" class="body">%s</a>',
                    $return_module, $return_action, $return_id, $app_strings['LBL_RETURN']);
                sugar_die($msg);
            }
        }
    }

    static function select_booking_object(DetailManager $mgr) {
        $record = $mgr->getRecord();

        //if new record and not load from calendar
        if ($record->new_record && ! isset($_REQUEST['timesheet_id'])) {
            $related_type = $record->getField('related_type');
            $related_id = $record->getField('related_id');

			if (empty($related_type) && empty($related_id)) {
                require_once('modules/Booking/utils.php');
                booking_go_to_target_list();
            }
        }
    }

    static function bookable_objects($user_id=null, $as_array=true) {
        $related_type = array_get_default($_REQUEST, 'related_type');
        if(! isset($user_id)) $user_id = AppConfig::current_user_id();
        $lq1 = null;
        $lq2 = null;

        if (empty($related_type) || $related_type == 'Cases') {
            $lq1 = new ListQuery('aCase', null, array('acl_user_id' => $user_id, 'acl_checks' => array('view')));
            $lq1->addFields(array('account', 'account_id'));
            $lq1->addFilterClause(array('field' => 'status', 'value' => 'Closed -', 'operator' => 'not_like', 'match' => 'prefix'));
            $lq1->addSimpleFilter('assigned_user_id', $user_id);
        }

        if (empty($related_type) || $related_type == 'ProjectTask') {
            $lq2 = new ListQuery('ProjectTask', null, array('acl_user_id' => $user_id, 'acl_checks' => array('view')));
            $lq2->addFields(array('account' => 'parent.account', 'account_id' => 'parent.account_id'));
            $lq2->addSimpleFilter('booking_users~join.user_id', $user_id);
            $lq2->addSimpleFilter('booking_users~join.booking_status', 'Active');
            $lq2->addSimpleFilter('status', 'In Progress');
            $lq2->addSimpleFilter('parent.project_phase', 'Active - In Progress');
        }

        $lq = new ListQuery();
        if ($lq1)
            $lq->addUnionQuery($lq1, 'cases');
        if ($lq2)
            $lq->addUnionQuery($lq2, 'project_tasks');

		if ($lq1 || $lq2) {
			$lq->addField('id');
			$lq->addDisplayName();
			$lq->addModuleNameField('related_type');
			$lq->addFields(array('account', 'account_id'));
	    	$ret = $lq->fetchAll('date_entered desc');
		} else {
			$ret = $lq->getBlankResult();
		}

    	if(! $ret->failed) {
    		$drows = array();
    		foreach($ret->rows as &$r) {
    			$r['icon'] = 'input-icon theme-icon module-'.$r['related_type'];
    			$drows[$r['id']] = $r;
    		}
    		$ret->rows = $drows;
            //self::add_selected_object($ret);
    	}

    	if($as_array) return $ret->rows;

    	return $ret;
    }

    /**
     * Add related object from subpanel
     * FIXME We have to hide Booked Hours subpanel Create button by clauses and remove this method
     */
    static function add_selected_object(ListResult &$result) {
        if (! empty($_REQUEST['related_id']) && ! empty($_REQUEST['related_type'])) {
            $rel_id = $_REQUEST['related_id'];
            $rel_type = $_REQUEST['related_type'];
            if (! isset($result->rows[$rel_id])) {
                $lq3 = new ListQuery(AppConfig::module_primary_bean($rel_type));
                $lq3->addFilterPrimaryKey($rel_id);
                $lq3->addDisplayName();
                $lq3->addModuleNameField('related_type');
                if ($rel_type == 'ProjectTasks') {
                    $lq3->addFields(array('account' => 'parent.account', 'account_id' => 'parent.account_id'));
                } else {
                    $lq3->addFields(array('account', 'account_id'));
                }
                $selected_ret = $lq3->runQuerySingle();
                if (! $selected_ret->failed) {
                    if (! isset($selected_ret->row['_display']))
                        $selected_ret->row['_display'] = $selected_ret->row['name'];
                    $selected_ret->row['icon'] = 'input-icon module-'.$selected_ret->row['related_type'];
                    $result->rows[$rel_id] = $selected_ret->row;
                }
            }
        }
    }
}
?>
