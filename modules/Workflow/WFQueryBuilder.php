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

require_once 'include/JSON.php';

class WFQueryBuilder
{
	protected $wf;
	protected $table_name;
	protected $join;

	function __construct($wf)/*{{{*/
	{
		global $beanList, $beanFiles;
		$this->wf = $wf;
		$bean_name = $beanList[$wf->trigger_module];
		$bean_file = $beanFiles[$bean_name];
		require_once $bean_file;
		$bean = new $bean_name;
		$this->table_name = $bean->table_name;
		$this->field_defs = $bean->field_defs;
		$this->json = getJSONObj();
	}/*}}}*/
	public function getModifiedBeans()/*{{{*/
	{
		$ret = array();
		$query = $this->getTimedQuery();
		$res = $this->wf->db->query($query);
		while ($row = $this->wf->db->fetchByAssoc($res)) {
			$ret[] = array(
				'id' => $row['id'],
				'modified_user_id' => $row['modified_user_id'],
			);
		}
		return $ret;
	}/*}}}*/
	public function getTimedQuery()/*{{{*/
	{
		$this->join = array();
		$n = 0;
		$tree = $this->wf->getConditionsTree();
		$where = $this->getQueryFromTree($tree[0], $n);

		$this->join[] = " workflow_data_audit_link wdal ON wdal.workflow_id='{$this->wf->id}' AND wdal.related_id={$this->table_name}.id ";

		$query = "SELECT `{$this->table_name}`.`id`, wda_0.modified_user_id FROM `{$this->table_name}` ";
		$query .= ' LEFT JOIN ' . join(' LEFT JOIN ', $this->join);
		$query .= ' WHERE (' . $where . ') ';
		$query .= " AND !`{$this->table_name}`.`deleted` ";
		$query .= " AND wdal.related_id IS NULL ";
		return $query;
	}/*}}}*/
	protected function getQueryFromTree($tree, &$num)/*{{{*/
	{
		$q = array();

		if ($this->wf->execute_mode == 'existingRecordOnly') $is_update = ' = 1';
		else if ($this->wf->execute_mode != 'newRecordAndExisting') $is_update = '= 0';
		else $is_update = "IN('0','1')";
		if (!empty($tree['condition'])) {
			$this->join[] = "`workflow_data_audit` `wda_$num` ON `{$this->table_name}`.`id` = `wda_$num`.related_id AND `wda_$num`.`related_type` = '{$this->wf->trigger_module}' AND `wda_$num`.`field_name` = '{$tree['condition']->field_name}' AND !`wda_$num`.`deleted` AND `wda_$num`.`is_update` $is_update";
			$q[] = $this->getSQLCondition($tree['condition'], $num);
			$num++;
		}

		if (!empty($tree['children'])) {
			if (!empty($q)) {
				$q[] = ' ' . $tree['children']['0']['condition']->glue . ' ';
			}
			$i = 0;
			$q[] = '(';
			foreach ($tree['children'] as $child) {
				if ($i) {
					$q[] = ' ' . $child['condition']->glue . ' ';
				}
				$q[] = $this->getQueryFromTree($child, $num);
				$i++;
			}
			$q[] = ')';
		}
		$where = join('', $q);
		return $where;
	}/*}}}*/
	protected function getSQLCondition($crit, $n)/*{{{*/
	{
		$operator = $crit->operator;
		$method = 'SQL_' . $operator;
		if (method_exists($this, $method)) {
			$sql = $this->$method($crit, $n);
		} else {
			$sql = "(`wda_$n`.`field_name`='" . $crit->field_name . "' AND `wda_$n`.`field_value` = '" . $this->wf->db->quote($crit->field_value) . "')";
		}
		$sql = '(' . $sql . ' AND ' . $this->getTimeSQL($crit, $n) . ')';
		return $sql;
	}/*}}}*/
	function getTimeSQL($crit, $n)/*{{{*/
	{
		$coded = $crit->time_interval;
		$interval = '';
		if (preg_match('/^-?(\d+)([hdwmy])$/', strtolower($coded), $m)) {
			switch ($m[2]) {
				case 'h':
					$interval = 'hours';
					break;
				case 'd':
					$interval = 'days';
					break;
				case 'w':
					$interval = 'weeks';
					break;
				case 'm':
					$interval = 'months';
					break;
				case 'y':
					$interval = 'years';
					break;
			}
			if ($interval) {
				$date = gmdate('Y-m-d H:i:s', strtotime("NOW - {$m[1]} $interval"));
				$sql = "`wda_$n`.`date_modified` <= '{$date}'";
				if ($crit->operator == 'does_not_change') {
					$sql = '(' . $sql . " OR `wda_$n`.`related_id` IS NULL) ";
				}
				return $sql;
			}
		}

		return '0';
	}/*}}}*/
	protected function SQL_does_not_change($crit, $n)/*{{{*/
	{
		$sql = "(`wda_$n`.`field_name`='" . $crit->field_name . "')";
		return $sql;
	}/*}}}*/
	protected function SQL_enum_one_of($crit, $n)/*{{{*/
	{
		$cond_value = $crit->field_value;
		$selection = $this->json->decode(from_html(strtolower($cond_value)));
		$selection = array_map(array($this->wf->db, 'quote'), $selection);
		$sql = "(`wda_$n`.`field_value` in ('" . join("','", $selection) . "'))";
		return $sql;
	}/*}}}*/
	protected function SQL_operator($crit, $n, $op)/*{{{*/
	{
		$sql = "(`wda_$n`.`field_name`='" . $crit->field_name . "' AND `wda_$n`.`field_value` {$op} '" . $this->wf->db->quote($crit->field_value) . "')";
		return $sql;
	}/*}}}*/
	protected function SQL_is_not_equal($crit, $n)/*{{{*/
	{
		return $this->SQL_operator($crit, $n, '!=');
	}/*}}}*/
	protected function SQL_num_is_not_equal_to($crit, $n)/*{{{*/
	{
		return $this->SQL_is_not_equal($crit, $n);
	}/*}}}*/
	protected function SQL_txt_is_not_equal_to($crit, $n)/*{{{*/
	{
		return $this->SQL_is_not_equal($crit, $n);
	}/*}}}*/
	protected function SQL_user_is_not($crit, $n)/*{{{*/
	{
		return $this->SQL_is_not_equal($crit, $n);
	}/*}}}*/
	protected function SQL_num_is_less_than($crit, $n)/*{{{*/
	{
		return $this->SQL_operator($crit, $n, '<');
	}/*}}}*/
	protected function SQL_num_is_not_less_than($crit, $n)/*{{{*/
	{
		return $this->SQL_operator($crit, $n, '>=');
	}/*}}}*/
	protected function SQL_num_is_greater_than($crit, $n)/*{{{*/
	{
		return $this->SQL_operator($crit, $n, '>');
	}/*}}}*/
	protected function SQL_num_is_not_greater_than($crit, $n)/*{{{*/
	{
		return $this->SQL_operator($crit, $n, '<=');
	}/*}}}*/
	protected function SQL_txt_contains($crit, $n)/*{{{*/
	{
		$like = str_replace(array('%', '_'), array('\\%', '\\_'), $crit->field_value);
		$sql = "(`wda_$n`.`field_name`='" . $crit->field_name . "' AND `wda_$n`.`field_value` LIKE '%" . $this->wf->db->quote($crit->field_value) . "%')";
		return $sql;
	}/*}}}*/
	protected function SQL_txt_not_contain($crit, $n)/*{{{*/
	{
		$like = str_replace(array('%', '_'), array('\\%', '\\_'), $crit->field_value);
		$sql = "(`wda_$n`.`field_name`='" . $crit->field_name . "' AND `wda_$n`.`field_value` NOT LIKE '%" . $this->wf->db->quote($crit->field_value) . "%')";
		return $sql;
	}/*}}}*/
	protected function SQL_txt_begins_with($crit, $n)/*{{{*/
	{
		$like = str_replace(array('%', '_'), array('\\%', '\\_'), $crit->field_value);
		$sql = "(`wda_$n`.`field_name`='" . $crit->field_name . "' AND `wda_$n`.`field_value` LIKE '" . $this->wf->db->quote($crit->field_value) . "%')";
		return $sql;
	}/*}}}*/
	protected function SQL_txt_ends_with($crit, $n)/*{{{*/
	{
		$like = str_replace(array('%', '_'), array('\\%', '\\_'), $crit->field_value);
		$sql = "(`wda_$n`.`field_name`='" . $crit->field_name . "' AND `wda_$n`.`field_value` LIKE '" . $this->wf->db->quote($crit->field_value) . "%')";
		return $sql;
	}/*}}}*/
	protected function SQL_dat_is_equal_to($crit, $n)/*{{{*/
	{
		$sql = "(`wda_$n`.`field_name`='" . $crit->field_name . "' AND SUBSTR(`wda_$n`.`field_value`,1,10) = '" . $this->wf->db->quote($crit->field_value) . "')";
		return $sql;
	}/*}}}*/
	protected function SQL_dat_before($crit, $n)/*{{{*/
	{
		$sql = "(`wda_$n`.`field_name`='" . $crit->field_name . "' AND SUBSTR(`wda_$n`.`field_value`,1,10) < '" . $this->wf->db->quote($crit->field_value) . "')";
		return $sql;
	}/*}}}*/
	protected function SQL_dat_after($crit, $n)/*{{{*/
	{
		$sql = "(`wda_$n`.`field_name`='" . $crit->field_name . "' AND SUBSTR(`wda_$n`.`field_value`,1,10) > '" . $this->wf->db->quote($crit->field_value) . "')";
		return $sql;
	}/*}}}*/
	protected function SQL_bol_is_false($crit, $n)/*{{{*/
	{
		$sql = "(`wda_$n`.`field_name`='" . $crit->field_name . "' AND `wda_$n`.`field_value` IN ('off','no','0'))";
		return $sql;
	}/*}}}*/
	protected function SQL_bol_is_true($crit, $n)/*{{{*/
	{
		$sql = "(`wda_$n`.`field_name`='" . $crit->field_name . "' AND `wda_$n`.`field_value` IN ('on','yes','1'))";
		return $sql;
	}/*}}}*/
	protected function SQL_dat_relative_before($crit, $n)/*{{{*/
	{
		$type = $this->field_defs[$crit->field_name]['type'];
		$interval = $this->getInterval($crit->field_value, $type='datetime');
		$sql = "(`wda_$n`.`field_name`='" . $crit->field_name . "' AND `wda_$n`.`field_value` < '{$interval['start']}')";
		return $sql;
	}/*}}}*/
	protected function SQL_dat_relative_after($crit, $n)/*{{{*/
	{
		$type = $this->field_defs[$crit->field_name]['type'];
		$interval = $this->getInterval($crit->field_value, $type='datetime');
		$sql = "(`wda_$n`.`field_name`='" . $crit->field_name . "' AND `wda_$n`.`field_value` > '{$interval['start']}')";
		return $sql;
	}/*}}}*/
	protected function SQL_dat_within($crit, $n)/*{{{*/
	{
		$type = $this->field_defs[$crit->field_name]['type'];
		$interval = $this->getInterval($crit->field_value, $type='datetime');
		$sql = "(`wda_$n`.`field_name`='" . $crit->field_name . "' AND `wda_$n`.`field_value` BETWEEN '{$interval['start']}' AND '{$interval['end']}' )";
		return $sql;
	}/*}}}*/
	protected function SQL_dat_not_within($crit, $n)/*{{{*/
	{
		$type = $this->field_defs[$crit->field_name]['type'];
		$interval = $this->getInterval($crit->field_value, $type='datetime');
		$sql = "(`wda_$n`.`field_name`='" . $crit->field_name . "' AND !(`wda_$n`.`field_value` BETWEEN '{$interval['start']}' AND '{$interval['end']}') )";
		return $sql;
	}/*}}}*/
	protected function getInterval($coded, $field_type='datetime')/*{{{*/
	{
		$start = $end = '';
		if($field_type == 'date') {
			$fmt = 'Y-m-d';
			$fn = 'date';
		}
		else {
			$fmt = 'Y-m-d H:i:s';
			$fn = 'gmdate';
		}
		$m = array();
		if (preg_match('/^-?(\d+)([hdwmy])$/', strtolower($coded), $m)) {
			switch ($m[2]) {
				case 'h':
					$interval = 'hours';
					break;
				case 'd':
					$interval = 'days';
					break;
				case 'w':
					$interval = 'weeks';
					break;
				case 'm':
					$interval = 'months';
					break;
				case 'y':
					$interval = 'years';
					break;
			}
			$mod_date = gmdate('Y-m-d H:i:s');
			$start = $fn($fmt, strtotime($mod_date . ' GMT  -' . $m[1] . ' ' . $interval));
			$end = $fn($fmt, strtotime($mod_date . ' GMT  +' . $m[1] . ' ' . $interval));
		}
		return array('start' => $start, 'end' => $end, );
	}/*}}}*/
}

