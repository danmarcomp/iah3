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
require_once('include/TimeDate.php');
require_once('modules/Scheduler/utils.php');

class Schedule extends SugarBean {
	// stored fields
	var $id;
	var $type;
	var $options;
	var $description;
	var $last_run;
	var $run_interval;
	var $run_interval_unit;
	var $next_run_date;
	var $next_run_time;
	var $run_on_user_login;
	var $run_on_page_load;
	var $status;
	var $enabled;
	var $last_modified_id;
	
	// runtime fields
	var $options_arr;
	var $timedate;
	
	// static fields
	var $table_name = "schedules";
	var $object_name = "Schedule";
    var $bean_name = "Schedule";
	var $module_dir = "Scheduler";
	var $new_schema = true;
	
	
	function Schedule() {
		global $current_language;
		parent::SugarBean();
		$this->timedate = new TimeDate();
	}
	
	function &retrieve($id, $encode=true) {
		$result = parent::retrieve($id, $encode);
		if($result !== null) {
			$options = $encode ? from_html($this->options) : $this->options;
			$this->options_arr = unserialize($options);
			return $this;
		}
		return $result;
	}
	
	function save($check_notify=true) {
		$this->options = serialize($this->options_arr);
		return parent::save($check_notify);
	}
	

	function last_run_ts() {
		if(!empty($this->last_run)) {
			$t = strtotime($this->timedate->to_db($this->last_run).' GMT');
			if($t == -1 || $t === false)
				return -1;
			return $t;
		}
		return -1;
	}
	
	function trigger_page_load() {
		$this->run_pending(null, 'run_on_page_load');
	}
	
	function trigger_user_login() {
		$this->run_pending(null, 'run_on_user_login');
	}
	

	function get_scheduler_status() {
		global $mod_strings;
		$status = array('has_run' => false, 'last_run' => $mod_strings['LBL_NEVER']);
		$last = AppConfig::setting('scheduler.last_cron_run');
		if(strlen($last)) {
			$status['last_run'] = $this->timedate->to_relative_date_time($last);
			$last = strtotime($last.' GMT');
			if(strtotime('now') - $last < 3600)
				$status['has_run'] = true;
		}
		$colour = $status['has_run'] ? 'green' : 'red';
		$status['image'] = '<div class="input-icon icon-led' . $colour . '"></div>';
		$last_interval = AppConfig::setting('scheduler.last_cron_interval');
		$status['last_interval'] = empty($last_interval) ? '' : gmdate('H:i:s', $last_interval);
		return $status;
	}
	
	
	static function status_color_sql($spec, $table, $for_order=false) {
		
		global $db;
		if(is_demo_site())
			return "'grey'";
		$tbl = $db->quoteField($table);
		return "
			CASE WHEN NOT $tbl.enabled THEN 'grey'
			WHEN $tbl.last_run IS NULL THEN 'yellow'
			WHEN $tbl.status='running' THEN
				CASE WHEN DATE_SUB($tbl.last_run, INTERVAL 2 MINUTE) < NOW() THEN 'red'
				ELSE 'yellow' END
			ELSE 'green' END
		";
	}
	
	
	static function status_text($enabled, $status, $last_run) {
		$mod_strings = return_module_language(null, 'Scheduler');
		$message = $mod_strings['LBL_RUN_RECENTLY'];
		if(is_demo_site() || ! $enabled) {
			$message = $mod_strings['LBL_DISABLED'];
		}
		else if(! $last_run || substr($last_run, 0, 4) == '0000') {
			$message = $mod_strings['LBL_NEVER_RUN'];
		}
		else if($status == 'running') {
			$message = $mod_strings['LBL_RUNNING_NOW'];
			$last = strtotime($last_run);
			if(strtotime('now') - $last > 120) {
				// should not take more than 2 minutes to run
				$message = $mod_strings['LBL_ERROR_RUNNING'];
			}
		}
		return $message;
	}
	
	static function get_interval_text($interval, $unit) {
		global $app_list_strings;
		$dom = $interval == 1 ? 'interval_unit_dom' : 'interval_units_dom';
		return format_number($interval, -1). ' '. $app_list_strings[$dom][$unit];
	}
	
	static function translate_type($type) {
		$dom = AppConfig::setting('lang.lists.current.Scheduler.schedule_type_dom', array());
		return array_get_default($dom, $type);
	}

	static function translate_description($type) {
		$dom = AppConfig::setting('lang.lists.current.Scheduler.schedule_description_dom', array());
		return array_get_default($dom, $type);
	}
}

?>
