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
require_once('modules/Workflow/WFOperation.php');
require_once('modules/Workflow/WFConditionCriteria.php');

class Workflow extends SugarBean {
	
	// persistent fields
	var $id;
	var $name;
	var $description;
	var $modified_user_id;
	var $assigned_user_id;
	var $created_by;
	var $date_entered;
	var $date_modified;
	
	var $status = 'inactive';
	var $execute_mode;
	var $occurs_when;
	var $trigger_module;
	var $trigger_action;
	var $trigger_value;
	
	var $operation_mode = 'immediately';
	var $operation_days = '0';
	var $operation_hours = '0';
	var $operation_mins = '0';
	var $operation_owner = 'trigger';
	
	var $criteria;
	var $operations;
	
	// static fields
	var $table_name = "workflow";
	var $object_name = "Workflow";
	var $module_dir = "Workflow";
	var $new_schema = true;


	function Workflow() {
		parent::SugarBean();
		global $current_language;
		$this->mod_strings = return_module_language($current_language, 'Workflow');
	}
	
	function lazy_load() {
		$this->get_all_wf_criteria();
		$this->get_all_wf_operations();
	}
	
	function get_summary_text()
	{
		return "$this->name";
	}
	
	function validation_error($msg) {
		$this->validation_error_msg = $msg;
		return false;
	}
	
	function get_list_view_data() {

		global $app_list_strings;
		$temp_array = $this->get_list_view_array();
		$temp_array['NAME'] = $this->name;
		$temp_array['TRIGGER_MODULE'] = isset($app_list_strings['moduleList'][$this->trigger_module]) ? $app_list_strings['moduleList'][$this->trigger_module] : $this->trigger_module;
		$temp_array['TRIGGER_ACTION'] = $this->mod_strings['OPTIONS_TRIGGER_ACTION'][$this->trigger_action].(empty($this->trigger_value) ? "" : " '".$this->trigger_value."'");
		$temp_array['STATUS'] = $this->mod_strings['LBL_LIST_STATUS_'.$this->status];
    	return $temp_array;
	}

	function get_source_modules_list() {
		global $app_list_strings, $beanList, $beanFiles, $moduleList, $modInvisList, $modSemiInvisList;
		
		$all_modules = array_merge($moduleList, $modInvisList, $modSemiInvisList);
		$includeModules = array(
			'Accounts', 'Bugs', 'Bills', 'Booking', 'Calls', 'Campaigns', 'Cases', 'Contacts', 'Documents',
			'Emails', 'Forecasts', 'Leads', 'Meetings', 'Notes', 'Opportunities', 'Quotes',
			'Tasks', 'ProductCatalog', 'Contracts', 'SubContracts', 'SalesOrders', 'Invoice',
			'PurchaseOrders', 'Payments', 'HR', 'Project', 'ProjectTask', 'Timesheets', 'Vacations',
		);
		$queryable = array();
		foreach($all_modules as $mod) {
			if(!in_array($mod, $includeModules))
				continue;
			if(!isset($beanList[$mod]))
				continue; // remove modules with no primary bean
			$title = isset($app_list_strings['moduleList'][$mod]) ? $app_list_strings['moduleList'][$mod] : '';
			if(empty($title))
				continue; // remove modules with no translation for their name
			$type = isset($def['type']) ? $def['type'] : 'union';
			$queryable[] = array('module' => $mod, 'bean_name' => $beanList[$mod], 'type' => $type, 'bean_file' => $beanFiles[$beanList[$mod]]);
		}

		//asort($queryable);
		$this->add_source_translations($queryable);
		sort_by_field($queryable, 'name_translated');
		return $queryable;
	}	
	
	function add_source_translations(&$sources) {
		global $app_list_strings;
		
		foreach($sources as $idx => $source) {
			if($source['type'] == 'primary' || $source['type'] == 'union')
				$sources[$idx]['name_translated'] = $app_list_strings['moduleList'][$source['module']];
			else {
				$sources[$idx]['name_translated'] = strip_trailing_colon(translate($source['vname'], $source['vname_module']));
				$sources[$idx]['link_module_translated'] = $app_list_strings['moduleList'][$source['link_module']];
				$sources[$idx]['link_type_translated'] = $this->mod_strings['LBL_LINK_TYPE_'.strtoupper($source['link_type'])];
			}
		}
	}
	
	function get_search_module_options()
	{
		$all_sources = $this->get_source_modules_list();
		$trigger_modules = array('' => '');
		foreach($all_sources as $mod=>$info) {
			$trigger_modules[$info['module']] = $info['name_translated'];
		}
		return $trigger_modules;
	}
	
	function get_search_execute_mode_options() {
		global $mod_strings;
		$execute_mode_options = array('' => '');
		foreach($mod_strings['OPTIONS_EXECUTE_MODE'] as $name=>$value) {
			$execute_mode_options[$name] = $value;
		}
		return $execute_mode_options;	
		
	}
	
	function get_conditions($for_id=null) {
		global $timedate;
		$conditions = array();
		if(! isset($for_id))
			$for_id = $this->id;

		if (isset($for_id) && $this->trigger_module) {
			$beanName = AppConfig::module_primary_bean($this->trigger_module);
			$query = "select * from workflow_criteria as cond where cond.workflow_id = '".$for_id."' ORDER BY idx";
			$res = $this->db->query($query, true);
			$idx = 0;
			if($beanName) {
				$model = new ModelDef($beanName);
				$defs = $model->getFieldDefinitions();
			} else {
				$defs = array();
			}
			while ($row = $this->db->fetchByAssoc($res)) {
				$def = $defs[$row['field_name']];
				$type = $def['type'];
				$row['field_value'] = from_html($row['field_value']);
				if (($type == 'date' || $type == 'datetime') && !empty($row['field_value'])) {
					if ($row['operator'] != 'dat_relative_before' &&
						$row['operator'] != 'dat_relative_after' &&
						$row['operator'] != 'dat_within' &&
						$row['operator'] != 'dat_not_within') {
						$row['field_value'] = $timedate->to_display_date($row['field_value'], false);
					}
				}
				$conditions[$idx] = array(
					'field_name'=>$row['field_name'],
					'operator'=>$row['operator'],
					'field_value'=>$row['field_value'],
					'time_interval' => $row['time_interval'],
					'glue' => $row['glue'],
					'level' => $row['level'],
				);
				if ($type == 'ref' && isset($def['bean_name'])) {
					$obj = ListQuery::quick_fetch($def['bean_name'], $row['field_value'], array('_display'));
					if ($obj)
						$conditions[$idx]['field_value_name'] = $obj->getField('_display');
				}
				$idx++;
			}
		}
		
		return array('conditions' => $conditions);
	}

	function get_operations($for_id=null) {
		$operations = array();
		if(! isset($for_id))
			$for_id = $this->id;

		if (isset($for_id)) {
			$query = "select * from workflow_operation as opt where opt.workflow_id = '".$for_id."' ORDER BY display_order";
			$res = $this->db->query($query, true);
					
			while ($row = $this->db->fetchByAssoc($res)) {
				$row['notification_content'] = from_html($row['notification_content']);
				$entry = $row;
				if ($entry['operation_type'] == 'updateRelatedData' && !empty($entry['dm_module_name'])) {
					require_once 'include/config/ModelDef.php';
					$module_name = $entry['dm_module_name'];
					$module_strings = return_module_language($current_language, $module_name);

					$beanName = AppConfig::module_primary_bean($module_name);
					if($beanName) {
						$model = new ModelDef($beanName);
						$defs = $model->getFieldDefinitions();
					} else {
						$defs = array();
					}
	
					$field_list = array();
					$exclude = array('link', 'relate', 'id');
					foreach ($defs as $idx => $def) {
						if (!in_array($def['type'], $exclude)) {
							$def['name_translated'] = $module_strings[$def['vname']];
							$field_list[] = $def;
						}
					}
					
					$entry['field_list'] = $field_list;
				}
				
				$operations[] = $entry;
			}
		}
		
		return array('operations' => $operations);
	}

	function get_all_wf_criteria() {
		if ((!isset($this->criteria) || empty($this->criteria)) && isset($this->id)) {
			$this->criteria = array();
	
			$query = "select * from workflow_criteria where workflow_criteria.workflow_id = '".$this->id."' ORDER BY idx";
			
			$res = $this->db->query($query, true);
			while ($row = $this->db->fetchByAssoc($res)) {
				$crit = new WFConditionCriteria();
				$crit->level = $row['level'];
				$crit->time_interval = $row['time_interval'];
				$crit->glue = $row['glue'];
				$crit->field_name = $row['field_name'];
				$crit->field_name = $row['field_name'];
				$crit->operator= $row['operator'];
				$crit->field_value = $row['field_value'];
				$this->criteria[] = $crit;	
				
			}
		}
	}

	function get_all_wf_operations() {
		if ((!isset($this->operations) || empty($this->operations)) && isset($this->id)) {
			$this->operations = array();
	
			$query = "select * from workflow_operation where workflow_operation.workflow_id = '".$this->id."'";
			
			$res = $this->db->query($query, true);
			while ($row = $this->db->fetchByAssoc($res)) {
				$opt = new WFOperation();
				foreach ($row as $k => $v) {
					$opt->$k = $v;
				}
				$this->operations[] = $opt;	
			}
		}
	}

	function checkConditionTree($tree, &$rowUpdate, $timing)
	{
		static $json;
		global $timedate;
		if (!isset($json)) {
			$json = getJSONObj();
		}
		$result = false;
			if (isset($tree['condition'])) {
				$cond = $tree['condition'];
		
				$cond_value = strtoupper($cond->field_value);

				$operator = $cond->operator;

				$field_name = $cond->field_name;
				$field_value = strtoupper($rowUpdate->getField($field_name, ''));
				$field_type = $rowUpdate->getFieldType($cond->field_name);


				if 
					(
						$field_type == 'date' ||
						$field_type == 'datetime'
					)
				{
					$field_value = substr($field_value, 0, 10);
				}
				if (
					$operator == 'dat_relative_before' ||
					$operator == 'dat_relative_after' ||
					$operator == 'dat_within' ||
					$operator == 'dat_not_within'
				) {
					$interval = $this->getInterval($cond_value, null, $field_type);
				}

				switch ($operator) {
					case "enum_one_of":
						$selection = $json->decode(from_html(strtolower($cond_value)));
						if (!$selection) {
							$selection = array();
						}
						foreach ($selection as $si => $sv) {
							$selection[$si] = strtoupper($sv);
						}
						if (in_array($field_value, $selection)) $result = true;
						break;
						
					case "num_is_equal_to":
					case "txt_is_equal_to":
					case "user_is":
						if ($field_value == $cond_value) $result = true;
						break;
						
					case "num_is_not_equal_to":
					case "txt_is_not_equal_to":
					case "user_is_not":
						if ($field_value != $cond_value) $result = true;
						break;
						
					case "num_is_less_than":
						if ($field_value < $cond_value) $result = true;
						break;
						
					case "num_is_not_less_than":
						if ($field_value >= $cond_value) $result = true;
						break;
						
					case "num_is_greater_than":
						if ($field_value > $cond_value) $result = true;
						break;

					case "num_is_not_greater_than":
						if ($field_value <= $cond_value) $result = true;
						break;
						
					case "txt_contains":
						if ((string)$cond_value == '') $result = true;
						elseif (strpos((string)$field_value, (string)$cond_value) !== false) $result = true;
						break;
						
					case "txt_not_contain":
						if ((string)$cond_value !== '' && strpos((string)$field_value, (string)$cond_value) === false) $result = true;
						break;
						
					case "txt_begins_with":
						if ($this->beginsWith($field_value, $cond_value)) $result = true;
						break;

					case "txt_ends_with":
						if ($this->endsWith($field_value, $cond_value)) $result = true;
						break;

					case "dat_is_equal_to":
						if (strtotime($field_value) == strtotime($cond_value)) $result = true;
						break;

					case "dat_before":
						if (strtotime($field_value) < strtotime($cond_value)) $result = true;
						break;

					case "dat_after":
						if (strtotime($field_value) > strtotime($cond_value)) $result = true;
						break;

					case "bol_is_false":
						if (!$field_value) $result = true;
						break;

					case "bol_is_true":
						if ($field_value) $result = true;
						break;

					case 'dat_relative_before':
						if ($field_value < $interval['start']) $result = true;
						break;

					case 'dat_relative_after':
						if ($field_value > $interval['end']) $result = true;
						break;

					case 'dat_within':
						if ($field_value >= $interval['start'] && $field_value <= $interval['end']) $result = true;
						break;

					case 'dat_not_within':
						if ($field_value < $interval['start'] || $field_value > $interval['end']) $result = true;
						break;

					default:
						$GLOBALS['log']->error("Unknown operator: ".$operator);
				}
			} else {
            	// If no condition is set, return true - allows us to navigate to child conditions
                $result = true;
			}
			if (!isset($tree['condition']) && empty($tree['children'])) {
				$result = true;
			} elseif (!empty($tree['children'])) {
				if ($result && $tree['children']['0']['condition']->glue == 'OR') {
					return true;
				}
				if (!$result && $tree['children']['0']['condition']->glue == 'AND') {
					return false;
				}
				foreach ($tree['children'] as $child) {
					// OR short circuit
					if ($result && $child['condition']->glue == 'OR') {
						return true;
					}
					// AND short circuit
					if (!$result && $child['condition']->glue == 'AND') {
						return false;
					}
					$result = $this->checkConditionTree($child, $rowUpdate, $timing);
				}
			}
		return $result;
	}

	/*
	 * check if the workflow is applicable for the given bean/timing/action
	 */
	function is_applicable(&$rowUpdate, $timing, $action, $isUpdate)
	{
		global $timedate;
		$GLOBALS['log']->debug('trigger_action: '.$this->trigger_action);
		$GLOBALS['log']->debug('action: '.$action);
		$GLOBALS['log']->debug('timing: '.$timing);
		
		// match mode
		if (($this->execute_mode == 'newRecordOnly' && $isUpdate) || ($this->execute_mode == 'existingRecordOnly' && !$isUpdate))
			return false;
		

		// match action
		if ($action != $this->trigger_action) 
			return false;
		
		// match timing
		$timing_matched = false;
		$timing_val = ($timing == 'before') ? '1' : '0';

		$applicable_operations = array();
		foreach ($this->operations as $idx=>$opt) {
			$perform_before = $opt->performed_before_event;
			if($opt->operation_type == 'updateCurrentData')
				$perform_before = 1;
			else if($opt->operation_type == 'sendEmail')
				; // user selectable
			else
				$perform_before = 0;
			if ($timing_val == $perform_before) {
				$timing_matched = true;
				$applicable_operations[] = $opt;
			}
		}

		if (!$timing_matched)
			return false;
		
		$conditions = $this->getConditionsTree();

		$result = $this->checkConditionTree($conditions[0], $rowUpdate, $timing);

		if ($result) {
			return $applicable_operations;
		} else {
			return false;
		}
	}


	function beginsWith( $str, $sub ) {
	   return ( substr( $str, 0, strlen( $sub ) ) === $sub );
	}
	
	function endsWith( $str, $sub ) {
	   return ( substr( $str, strlen( $str ) - strlen( $sub ) ) === $sub );
	}
	
	function validate() {
		if (empty($this->name)) {
			throw new WFException(WFException::ERR_EMPTY_NAME);
		}
		if (empty($this->status)) {
			throw new WFException(WFException::ERR_EMPTY_WF_STATUS);
		}
		if (empty($this->execute_mode)) {
			throw new WFException(WFException::ERR_EMPTY_EXEC_MODE);
		}
		if (empty($this->trigger_module)) {
			throw new WFException(WFException::ERR_EMPTY_EXEC_MODULE);
		}
		if (empty($this->trigger_action)) {
			throw new WFException(WFException::ERR_EMPTY_ACTION);
		}
		if (empty($this->occurs_when)) {
			throw new WFException(WFException::ERR_EMPTY_OP_MODE);
		}
		if (empty($this->operation_owner)) {
			throw new WFException(WFException::ERR_EMPTY_OWNER);
		}
			
		foreach ($this->criteria as $cond) {
			$cond->validate();
		}
		
		foreach ($this->operations as $opt) {
			$opt->validate($this->trigger_action);
		}
		return true;
	}

	function ACLAccess($view, $is_owner = 'not_set')
	{
		switch ($view) {
			case 'edit':
			case 'Save':
			case 'PopupEditView':
			case 'EditView':
			case 'Delete':
			if ($this-> status == 'active') {
				return false;
			}
		}
		return parent::ACLAccess($view, $is_owner);
	}
	
	function getInterval($coded, $mod_date=null, $field_type='datetime')
	{
		$start = $end = $mid = '';
		if($field_type == 'date') {
			$fmt = 'Y-m-d';
			$fn = 'date';
		}
		else {
			$fmt = 'Y-m-d H:i:s';
			$fn = 'gmdate';
		}
		if(is_null($mod_date)) {
			$mod_date = gmdate('Y-m-d H:i:s');
			$mid = $fn($fmt);
		}
		else {
			$mid = $mod_date;
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
			$start = $fn($fmt, strtotime($mod_date . ' GMT  -' . $m[1] . ' ' . $interval));
			$end = $fn($fmt, strtotime($mod_date . ' GMT  +' . $m[1] . ' ' . $interval));
		}
		return array('start' => $start, 'end' => $end, 'mid' => $mid, 'gmt_mid' => $mod_date);
	}

	function getConditionsTree()
	{
		$groups = array(
			array(
				'level' => 0,
				'conditions' => array(),
			),
		);
		$gsize = 0;
		$level = 0;
		$parents = array();
		foreach ($this->criteria as $cond) {
			if ($level != $cond->level) {
				$gsize++;
				$groups[$gsize] = array(
					'level' => $cond->level,
					'conditions' => array(),
				);
			}
			$groups[$gsize]['conditions'][] = $cond;
			$level = $cond->level;
		}


		$tree = array(
			array(
				'children' => array(),
			),
		);
		$path = array();
		$path[0] = &$tree[0];
		$level = 0;
		foreach ($groups as $group) {
			$cur =& $path[count($path)-1];
			if ($group['level'] > $level) {
				$cur =& $cur['children'][count($cur['children'])-1];
				$path[$group['level']] =& $cur;
			} elseif ($group['level'] < $level) {
				$keys = array_keys($path);
				while ($keys[count($path)-1] > $group['level']) {
					array_pop($path);
				}
				$cur =& $path[count($path)-1];
			}
			foreach ($group['conditions'] as $cond) {
				$cur['children'][] = array(
					'condition' => $cond,
					'children' => array(),
				);
			}
			$level = $group['level'];
		}
		return $tree;
	}
	
	static function init_record(RowUpdate &$upd, $input) {
		if (empty($update['trigger_action'])) $update['trigger_action'] = 'saved';
		if (empty($update['occurs_when'])) $update['occurs_when'] = 'saved';
		if (empty($update['execute_mode'])) $update['execute_mode'] = 'newRecordAndExisting';
		if (empty($update['operation_owner'])) $update['operation_owner'] = 'trigger';
        $upd->set($update);
	}

	function get_target_options() {
        $include_modules = array(
            'Accounts', 'Bugs', 'Bills', 'Booking', 'Calls', 'Campaigns', 'Cases', 'Contacts', 'Documents',
            'Emails', 'Forecasts', 'Leads', 'Meetings', 'Notes', 'Opportunities', 'Quotes',
            'Tasks', 'ProductCatalog', 'Service', 'SubContracts', 'SalesOrders', 'Invoice',
            'PurchaseOrders', 'Payments', 'HR', 'Project', 'ProjectTask', 'Timesheets', 'Vacations',
        );

		$lang = AppConfig::setting("lang.lists.current.app.moduleList");
		$beans = AppConfig::setting('modinfo.primary_beans');
        $options = array();

		foreach ($beans as $mod => $name) {
            if (in_array($mod, $include_modules)) {
                $options[$mod] = array_get_default($lang, $mod, $name);
            }
		}

		asort($options);

        return $options;
	}
	
}
?>
