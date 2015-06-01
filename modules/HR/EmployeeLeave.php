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
require_once('log4php/LoggerManager.php');
require_once('include/database/PearDatabase.php');
require_once 'modules/HR/Employee.php';


class EmployeeLeave extends SugarBean {
	// Stored fields
	var $employee_id;
	var $fiscal_year;
	var $vacation_allowed;
	var $sick_days_allowed;
	var $vacation_carried_over;
	var $description;
	var $date_modified;
	var $modified_user_id;

	var $object_name = "EmployeeLeave";
	var $table_name = "employee_leave";
	var $module_dir = "HR";
	var $monthly_table_name = "employee_leave_monthly";
	
	var $column_fields = Array(
		'vacation_allowed', 'sick_days_allowed',
		'vacation_carried_over', 'description',
		);
	
	var $monthly_column_fields = Array(
		"vacation_taken", "sickleave_taken");
	
	var $records  = Array();
	var $inserted = Array();
	var $updated  = Array();
	
	function EmployeeLeave($employee_id = null, $fiscal_year = null) {
		parent::SugarBean();
		if($employee_id != null)
			$this->load($employee_id, $fiscal_year);
	}
	
	function load($employee_id, $fiscal_year = null) {
		$emp = new  Employee;
		$emp->retrieve($employee_id);
		$user = $emp->get_user();

		$this->employee_id = $employee_id;
		$fiscal_year = intval($fiscal_year);
		$this->fiscal_year = $fiscal_year;
		
		if($fiscal_year != 0) {
			$query = "SELECT * FROM $this->table_name WHERE ".
				"employee_id='".PearDatabase::quote($this->employee_id)."' ".
				"AND fiscal_year='$fiscal_year'";
	        //requireSingleResult has beeen deprecated.
			//$result = $this->db->requireSingleResult($query, true,
			$result = $this->db->limitQuery($query,0,1,true, "Error retrieving employee leave");
			$val = $this->db->fetchByAssoc($result);
			if(empty($val))
				$this->new_schema = true;
			else {
				foreach($this->column_fields as $f)
					$this->$f = $val[$f];
				$this->date_modified = $val['date_modified'];
				$this->new_schema = false;
			}
		}
		
		$query = "SELECT ".
			"date_end, SUM(days) AS days, leave_type, status, 
			YEAR(date_end) AS year_end,
			MONTH(date_end) AS month_end
			FROM vacations ".
			"WHERE assigned_user_id='".PearDatabase::quote($user->id)."' ";
		if($fiscal_year != 0) {
			$month = fiscal_year_start_month();
			$first = sprintf('%d-%02d-%02d', $fiscal_year, $month, 1);
			$last = sprintf('%d-%02d-%02d', $fiscal_year+1, $month, 1);
			$query .= "AND (date_start >= '$first' OR DATE_ADD(date_start, INTERVAL days-1 DAY) < '$last') ";
		}
		$query .= " AND (leave_type='vacation' AND (status='approved' OR status='completed')  OR (leave_type = 'sick' AND status = 'days_taken'))  ";
		$query .= "GROUP BY year_end, month_end, leave_type ORDER BY year_end, month_end";
		$result = $this->db->query($query, true, "Error listing employee leave: ");
		
		while($val = $this->db->fetchByAssoc($result)) {
				$year = $val['year_end'];
				$month = $val['month_end'];
				$total = $val['days'];
				if (!isset($this->records[$year][$month])) {
					$this->records[$year][$month] = array('vacation_taken' => 0.00, 'sickleave_taken' => 0.00);
				}
				if ($val['status'] == 'days_taken') {
					$this->records[$year][$month]['vacation_taken'] += $total;
					$this->records[$year][$month]['sickleave_taken'] += $total;					
				} elseif ($val['leave_type'] == 'vacation') {
					$this->records[$year][$month]['vacation_taken'] += $total;
				} else {
					$this->records[$year][$month]['sickleave_taken'] += $total;
				}
		}
	}
	
	function update_for_period($year, $month, $values) {
		$old = $this->get_for_period($year, $month);
		if(!is_array($values)) {
			$this->log->error("Expected array of values as argument to update_for_period");
			return;
		}
		
		$changed = 0;
		foreach($this->monthly_column_fields as $key) {
			if(isset($values[$key])) {
				$values[$key] = floatval($values[$key]);
				if($values[$key] != $old[$key])
					$changed = 1;
			}
		}
		if(! $changed)
			return; // no change

		if(! isset($this->records[$year][$month]))
			$this->inserted[$year][$month] = 1;
		elseif(! isset($this->inserted[$year][$month]))
			$this->updated[$year][$month] = 1;
		
		$this->records[$year][$month] = array_merge($old, $values);
	}
	
	function get_for_period($year, $month) {
		if(isset($this->records[$year][$month]))
			return $this->records[$year][$month];
		$v = Array();
		foreach($this->monthly_column_fields as $key)
			$v[$key] = 0.0;
		return $v;
	}
	
	function &get_records_for_fiscal_year($year = null) {
		if($year == null)
			$year = $this->fiscal_year;
			
		$fy_records = array();
		$month = fiscal_year_start_month();
		for($i = 0; $i < 12; $i++) {
			$period = sprintf('%d-%02d', $year, $month);
			$fy_records[$period] = $this->get_for_period($year, $month);
			$month ++;
			if($month > 12) {
				$month = 1;
				$year ++;
			}
		}
		return $fy_records;
	}

	function save() {
		if(!isset($this->employee_id) || empty($this->employee_id)) {
			sugar_die("Cannot save employee leave records, missing user ID");
		}
		
		$this->unformat_all_fields();
		
		// insert or update year-specific data
		if(isset($this->fiscal_year) && !empty($this->fiscal_year)) {
			if($this->new_schema)
				$query = "INSERT INTO $this->table_name SET ";
			else
				$query = "UPDATE $this->table_name SET ";
			$comma = 0;
			foreach($this->column_fields as $f) {
				if($comma)
					$query .= ', ';
				$comma = 1;
				$query .= "$f='".PearDatabase::quote(from_html($this->$f))."'";
			}
			$query .= ", date_modified='".date("Y-m-d H:i:s")."'";
			$query .= $this->new_schema ? ', ' : ' WHERE ';
			$query .= "employee_id='".PearDatabase::quote($this->employee_id)."'";
			$query .= $this->new_schema ? ', ' : ' AND ';
			$query .= "fiscal_year='".PearDatabase::quote($this->fiscal_year)."'";
			
			$this->db->query($query, true, "Error updating employee leave");
		}
	
		// insert month-specific data
		foreach($this->inserted as $year => $months) {
			foreach(array_keys($months) as $month) {
				$query = "INSERT INTO $this->monthly_table_name ";
				$query.= "(employee_id, period, vacation_taken, sickleave_taken) VALUES ";
				$values = $this->records[$year][$month];
				$newvals = Array(
					$this->employee_id, "$year-$month-1",
					$values['vacation_taken'], $values['sickleave_taken'],
				);
				$cancel = 1;
				foreach($newvals as $i=>$f) {
					if($f != 0)
						$cancel = 0;
					$newvals[$i] = "\"$f\"";
				}
				$query.= "(" . implode(", ", $newvals) . ")";
				if(! $cancel)
					$this->db->query($query, true, "Error inserting employee leave");
			}
		}
		
		// update month-specific data
		foreach($this->updated as $year => $months) {
			foreach(array_keys($months) as $month) {
				$values = $this->records[$year][$month];
				$newvals = Array();
				$delete = 1;
				foreach($this->monthly_column_fields as $f) {
					if(!empty($values[$f]))
						$delete = 0;
					$v = $this->db->quote($values[$f]);
					$newvals[] = "$f='$v'";
				}
				if($delete)
					$query = "DELETE FROM $this->monthly_table_name";
				else {
					$query = "UPDATE $this->monthly_table_name SET ";
					$query .= implode(", ", $newvals);
				}
				$query .= " WHERE employee_id='$this->employee_id'
					AND period='$year-$month-1'";
				$this->db->query($query, true, "Error updating employee leave");
			}
		}
	}

    static function init_by_employee(DetailManager &$mgr) {
        if ( ! empty($_REQUEST['employee_id']) && ! empty($_REQUEST['fiscal_year']) ) {
            $fields = array('vacation_allowed', 'sick_days_allowed', 'vacation_carried_over', 'description', 'date_modified');
            $lq = new ListQuery('EmployeeLeave', $fields);

            $clauses = array(
                "employee" => array(
                    "value" => $_REQUEST['employee_id'],
                    "field" => "employee_id"
                ),
                "fiscal_year" => array(
                    "value" =>  $_REQUEST['fiscal_year'],
                    "field" => "fiscal_year",
                )
            );

            $lq->addFilterClauses($clauses);
            $result = $lq->runQuerySingle();

            if (! $result->failed) {
                global $timedate;
                require_once('include/database/RowUpdate.php');

                $upd = new RowUpdate($mgr->record);
                $date_modified = $timedate->to_display_date_time($result->getField('date_modified'));
                $upd->set(array('vacation_allowed' => $result->getField('vacation_allowed'), 'sick_days_allowed' => $result->getField('sick_days_allowed'),
                    'vacation_carried_over' => $result->getField('vacation_carried_over'), 'description' => $result->getField('description'),
                    'date_modified' => $date_modified));
                $upd->updateResult($mgr->record);
            }
        }
    }
}
?>