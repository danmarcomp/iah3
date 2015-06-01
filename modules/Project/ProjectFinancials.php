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


require_once('modules/Currencies/Currency.php');

class ProjectFinancials extends SugarBean {

	var $object_name = "ProjectFinancials";
	var $table_name = "project_financials";
	var $module_dir = "Project";
	var $new_schema = true;

    /**
     * @var RowUpdate
     */
    var $project;

    /**
     * @var Currency
     */
    var $currency;

	var $records  = Array();
	var $inserted = Array();
	var $updated  = Array();
	var $deleted = Array();
	var $usd_valid = Array();

	var $disable_num_format = true;

	static $value_fields = Array(
		"expected_cost", "expected_revenue", "actual_cost", "actual_revenue",
	);

    const MAIN_BEAN_NAME = 'Project';

    function ProjectFinancials() {
		parent::SugarBean();
	}
	
	function load(RowUpdate &$project, $loadUSD = false) {
		$this->project =& $project;
		$fields = self::$value_fields;
		if($loadUSD)
			foreach(self::$value_fields as $f)
				$fields[] = $f."_usd";

		$this->records = array();
		$proj_id = $project->getPrimaryKeyValue();
        if (! $proj_id && ! empty($project->updates['id']))
            $proj_id = $project->updates['id'];
        if(! $proj_id)
			return;

		$query = "SELECT
			YEAR(period) as year, MONTH(period) as month, ";
		$query .= join(', ', $fields);
		$query .= " FROM $this->table_name ";
		$query .= "WHERE project_id='{$proj_id}' AND deleted=0 ";
		$query .= "ORDER BY year, month";
		$result = $this->db->query($query, true, "Error listing project financials: ");

		while($val = $this->db->fetchByAssoc($result)) {
			$year = $val['year'];
			$month = $val['month'];
			unset($val['year']);
			unset($val['month']);
			$val = array_map('floatval', $val);
			$this->records[sprintf("%04d-%02d", $year, $month)] = $val;
		}

        $this->set_currency();
	}

    /**
     * Get main financials bean object
     *
     * @return Project
     */
    function get_main_bean() {
        require_bean(self::MAIN_BEAN_NAME);
        $bean = new Project();
        $bean->retrieve($this->project->getPrimaryKeyValue());
        return $bean;
    }

    function set_currency() {
        $project_currency = new Currency();
        $project_currency->retrieve($this->project->getField('currency_id'));
        $rate = $this->get_exchange_rate();
        if($rate) $project_currency->conversion_rate = $rate;
        $this->currency = $project_currency;
    }

    /**
     * Get Project Currency
     *
     * @return Currency
     */
    function get_currency() {
        return $this->currency;
    }

    function get_exchange_rate() {
        return $this->project->getField('exchange_rate', '');
    }

	function get_records() {
		return $this->records;
	}

    function cleanup() {
        unset($this->project);
        parent::cleanup();
    }

    // convert from hyphen-separated date to an array [year, month, day] of integers
	function split_date($d) {
		$d = explode("-", $d);
		foreach($d as $i => $v)
			$d[$i] = intval($v);
		return $d;
	}
	
	// return an array of financial periods between the project start and end dates
	function get_project_financial_periods() {
		$project =& $this->project;
		$dt_start = $project->getField('date_starting');
		$dt_end = $project->getField('date_ending');
		
		if(empty($dt_start) || empty($dt_end))
			return array();
		$start = $this->split_date($dt_start);
		$end = $this->split_date($dt_end);
		$months = Array();
		$year = $start[0];
		$month = $start[1];
		while($year < $end[0]) {
			while($month <= 12) {
				$months[] = sprintf("%04d-%02d", $year, $month);
				$month++;
			}
			$month = 1;
			$year++;
		}
		$end_month = $end[1];
		while($month <= $end_month) {
			$months[] = sprintf("%04d-%02d", $year, $month);
			$month++;
		}
		return $months;
	}
	
	function update_for_period($period, $values, $usd_valid=false) {
        $old = $this->get_for_period($period);
		if(!is_array($values)) {
			$this->log->error("Expected array of values as argument to update_for_period");
			return;
		}
		
		$fields = self::$value_fields;
		if($usd_valid) {
			foreach($fields as $k)
				$fields[] = $k.'_usd';
			$this->usd_valid[$period] = true;
		}
		else
			$this->usd_valid[$period] = false;
		
		$changed = 0;
		foreach(self::$value_fields as $key) {
			if(isset($values[$key])) {
				$values[$key] = floatval($values[$key]);
				if($values[$key] != $old[$key])
					$changed = 1;
			}
		}
		if(! $changed)
			return; // no change

		if(! isset($this->records[$period]))
			$this->inserted[$period] = 1;
		elseif(! isset($this->inserted[$period]))
			$this->updated[$period] = 1;

		$this->records[$period] = array_merge($old, $values);
    }
	
	function get_for_period($period) {
		if(isset($this->records[$period]))
			return $this->records[$period];
		$v = array();
		foreach(self::$value_fields as $key)
			$v[$key] = 0.0;
		return $v;
	}

    function is_set_period($period) {
        return isset($this->records[$period]);
    }

	function save() {
		$proj_id = $this->project->getPrimaryKeyValue();
		$currency = $this->get_currency();

        // do inserts
		foreach(array_keys($this->inserted) as $period) {
			$values =& $this->records[$period];
			$query = "INSERT INTO $this->table_name ";
			$newvals = array(
				'project_id' => $proj_id,
				'period' => "{$period}-01",
				'date_modified' => gmdate("Y-m-d H:i:s"), /** added by Jason Eggers */
			);
			$cancel = 1;
			foreach(self::$value_fields as $f) {
				if($values[$f] != 0)
					$cancel = 0;
				$newvals[$f] = $values[$f];
                if(!empty($this->usd_valid[$period]) && isset($values[$f.'_usd']))
					$newvals[$f.'_usd'] = $values[$f.'_usd'];
				else
					$newvals[$f.'_usd'] = $currency->convertToDollar($values[$f]);
            }
            if($cancel)
				continue;
			$query.= "(" . implode(", ", array_keys($newvals)) . ")";
			$query.= " VALUES ('" . implode("', '", array_values($newvals)) . "')";
			$this->db->query($query, true, "Error inserting project financials");
		}
		
		// do updates
        foreach(array_keys($this->updated) as $period) {
			$values =& $this->records[$period];
			$newvals = array();
			$delete = 1;
			foreach(self::$value_fields as $f) {
				if($values[$f] != 0)
					$delete = 0;
				$newvals[] = "$f='{$values[$f]}'";
                if(!empty($this->usd_valid[$period]) && isset($values[$f.'_usd']))
					$dollarAmt = $values[$f.'_usd'];
				else
					$dollarAmt = $currency->convertToDollar($values[$f]);
				$newvals[] = $f."_usd='$dollarAmt'";
			}
			if($delete) {
				$this->deleted[$period] = 1;
				continue;
			}
			$query = "UPDATE $this->table_name SET ";
			$query .= implode(", ", $newvals);
			$query .= " WHERE project_id='{$proj_id}' AND period='{$period}-01'";
			$this->db->query($query, true, "Error updating project financials");
		}

		// do deletes
		if(count($this->deleted)) {
			$periods = "'" . implode("-01', '", array_keys($this->deleted)) . "-01'";
			$query = "DELETE FROM $this->table_name ".
				"WHERE project_id='{$proj_id}' ".
				"AND period IN ($periods)";
			$this->db->query($query, true, "Error deleting removed project financials");
		}

        //Update Project 'total' values
        $this->update_totals();
	}

    function update_totals() {
        $totals = $this->calculate_totals();

        if (sizeof($totals) > 0) {
            $this->project->set($totals);
            $this->project->save();
        }
    }

    function calculate_totals() {
        $totals = array();

        if (sizeof($this->records) > 0) {

            foreach ($this->records as $period => $values) {
                foreach(self::$value_fields as $f) {
                    if (! isset($totals['total_' . $f]))
                        $totals['total_' . $f] = 0;

                    $totals['total_' . $f] += $values[$f];
                }
            }

        }

        return $totals;
    }

    function calculate_costs($actual = true) {
    	$hours_per_day = AppConfig::setting('company.hours_in_work_day', 8);
    	$proj_id = $this->project->getPrimaryKeyValue();
		$currency = $this->get_currency();
        $calculated = array();

		foreach ($this->records as $period => $values) {
		    $calculated[$period]['expected_cost'] = 0;
		    $calculated[$period]['expected_cost_usd'] = 0;
		    if ($actual) {
		    	$calculated[$period]['actual_cost'] = 0;
		    	$calculated[$period]['actual_cost_usd'] = 0;
		    }
        }

		$rate_usd = "IF(ptu.hourly_cost_custom, ptu.hourly_cost_usdollar, empl.std_hourly_rate_usdollar)";
		$rate_local = "IF(ptu.hourly_cost_custom, ptu.hourly_cost, empl.std_hourly_rate)";
		$query = "SELECT SUM(ptu.estim_hours) AS estimated_effort, ".
			"SUM(ptu.estim_hours * $rate_usd) AS estimated_cost_usd, ".
			"SUM(ptu.estim_hours * $rate_local) AS estimated_cost_local, ".
			"DATE(project_task.date_start) AS date_start, DATE(project_task.date_due) AS date_due, ".
			"project_task.currency_id, project_task.exchange_rate, ".
			"empl.salary_currency_id, empl.salary_exchange_rate ".
			"FROM projecttasks_users ptu ".
			"LEFT JOIN employees empl ON empl.user_id=ptu.user_id ".
			"LEFT JOIN project_task ON project_task.id = ptu.projecttask_id ".
			"WHERE project_task.parent_id='{$proj_id}' AND NOT ptu.deleted AND NOT project_task.deleted ".
			"GROUP BY project_task.id";
        $res = $this->db->query($query, 1);

        while ($row = $this->db->fetchByAssoc($res)) {
            $startArray = explode('-', $row['date_start']);
            $endArray = explode('-', $row['date_due']);
            if ($startArray[0].$startArray[1] == $endArray[0].$endArray[1]) { // within one month
                $ranges = array(
                    array($startArray, $endArray)
                );
            } else { //trans month
                $ranges = array();
                while ($startArray[0].$startArray[1] !== $endArray[0].$endArray[1]) {
                    $newArray = $startArray;
                    $newArray[2] = date('t', mktime(0,0,0, $startArray[1], $startArray[2], $startArray[0]));
                    $ranges[] = array($startArray, $newArray);
                    $startArray[2] = '01';
                    $startArray[1] = sprintf('%02d', $startArray[1]+1);
                    if ($startArray[1] == 13) {
                        $startArray[1] = '01';
                        $startArray[0] = sprintf('%04d', $startArray[0]+1);
                    }
                    if (count($ranges) > 200) break;
                }
                $ranges[] = array($startArray, $endArray);
            }

			$range_hours = array();
			$total_hours = 0;

            foreach ($ranges as $i => $range) {
                $startDate = @mktime(0,0,0, $range[0][1], $range[0][2], $range[0][0]);
                $endDate = @mktime(0,0,0, $range[1][1], $range[1][2], $range[1][0]);
                $diff = (int)(($endDate - $startDate)/24/60/60 + 1);
                $startDOW = date('w', $startDate);
                $endDOW = date('w', $endDate);
                $diff = ($diff - (7-$startDOW) - (1+$endDOW))/7*5 + (7-$startDOW) + (1+$endDOW);
                $diff--; // first saturday
                if ($startDOW == 0) $diff--; //first sunday
                $diff--; // last sunday
				if ($endDOW == 6) $diff--;  //last saturday
				$range_hours[$i] = $diff * $hours_per_day;
				$total_hours += $range_hours[$i];
            }

            foreach ($ranges as $i => $range) {
				$period = $range[0][0].'-'.$range[0][1];
            	$usd = $total_hours ? $row['estimated_cost_usd'] * $range_hours[$i] / $total_hours : 0;
                @$calculated[$period]['expected_cost_usd'] += $usd;
            	if($row['currency_id'] == $currency->id) {
            		$local = $total_hours ? $row['estimated_cost_local'] * $range_hours[$i] / $total_hours : 0;
            		@$calculated[$period]['expected_cost'] += $local;
            	} else
            		@$calculated[$period]['expected_cost'] += $currency->convertFromDollar($usd);
				$updated[$period] = true;
            }
        }

        if ($actual) {
			$query = "
			SELECT hrs.paid_total, hrs.paid_total_usd, hrs.date_start, hrs.quantity, hrs.paid_currency_id, hrs.paid_exchange_rate
			FROM
			booked_hours hrs
			LEFT JOIN project_task task ON hrs.related_id=task.id AND NOT task.deleted
			LEFT JOIN projecttasks_users ptu ON ptu.projecttask_id = task.id AND hrs.assigned_user_id=ptu.user_id
			WHERE task.parent_id='{$proj_id}' AND hrs.deleted != 1 AND hrs.status='approved' AND hrs.related_type='ProjectTask'
			AND ptu.deleted = 0
			";
			$res = $this->db->query($query, 1);

			while ($row = $this->db->fetchByAssoc($res)) {
				$startArray = explode('-', $row['date_start']);
				$period = $startArray[0].'-'.$startArray[1];
				@$calculated[$period]['actual_cost_usd'] += $row['paid_total_usd'];
				if($row['paid_currency_id'] == $currency->id)
					@$calculated[$period]['actual_cost'] += $row['paid_total'];
				else
					@$calculated[$period]['actual_cost'] += $currency->convertFromDollar($row['paid_total_usd']);
			}

            $this->add_expenses($calculated);
        }

        foreach ($calculated as $period => $value) {
            $this->update_for_period($period, $value, true);
        }
    }

    /**
     * @param array $calculated_costs
     */
    function add_expenses(&$calculated_costs) {
        $bean = $this->get_main_bean();
        $expense_reports = $bean->getRelatedApprovedExpenseReports();

        if (sizeof($expense_reports > 0)) {
            foreach ($expense_reports as $id => $data) {
                $this->add_expenses_to_actual_costs($id, $calculated_costs);
            }
        }
    }

    /**
     * Add approved Expense Report amounts to actual costs
     *
     * @param string $expense_report_id
     * @param array $calculated_costs
     */
    function add_expenses_to_actual_costs($expense_report_id, &$calculated_costs) {
        require_bean('ExpenseReport');
        $report_items = ExpenseReport::get_line_items($expense_report_id);

        if (sizeof($report_items) > 0) {
            $project_currency = $this->get_currency();

            foreach ($report_items as $item) {
                $period = date('Y-m', strtotime($item['date']));
                if (! $this->is_set_period($period))
                    continue;

                if (isset($calculated_costs[$period]['actual_cost_usd'])) {
                    $calculated_costs[$period]['actual_cost_usd'] += $item['total_usdollar'];
                } else {
                    $calculated_costs[$period]['actual_cost_usd'] = $item['total_usdollar'];
                }

                if (isset($calculated_costs[$period]['actual_cost'])) {
                    $calculated_costs[$period]['actual_cost'] += $project_currency->convertFromDollar($item['total_usdollar']);
                } else {
                    $calculated_costs[$period]['actual_cost'] = $project_currency->convertFromDollar($item['total_usdollar']);
                }
            }
        }
    }
}
?>
