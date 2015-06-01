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
require_once('modules/ReportData/ReportData.php');

class Report extends SugarBean {

	// stored fields
	var $id;
	var $name;
	var $description;
	var $modified_user_id;
	var $assigned_user_id;
	var $created_by;
	var $date_entered;
	var $date_modified;
	var $shared_with;

	var $primary_module;
	var $sources;
	var $report_fields;
	var $totals;
	var $filters;
	var $sort_order;
	var $run_method;
	var $next_run_date;
	var $next_run_time;
	var $run_interval = '1';
	var $run_interval_unit = 'days';
	var $last_run;
	var $from_template_id;
	var $chart_type, $chart_options, $chart_title, $chart_rollover, $chart_description, $chart_series;

	// runtime fields
	var $primary_source;
	var $sources_arr; // serialized to $sources
	var $fields_arr; // serialized to $report_fields
	var $totals_arr; // serialized to $totals
	var $filters_arr; // serialized to $filters
	var $sort_order_arr; // serialized to $sort_order
	var $validation_error_msg;
	var $seeds_arr; // stores loaded sugarbeans
	var $links_arr; // stores loaded link objects
	var $loaded_links;
	var $link_parents;
	var $mod_strings;
	var $timedate;
	var $fy_start_month;

	// display-only fields
	var $primary_module_name;
	var $next_run;
	var $assigned_user_name;
	var $created_by_name;
	var $modified_by_name;

	// static fields
	var $table_name = "reports";
	var $object_name = "Report";
	var $object_names = "Reports";
	var $module_dir = "Reports";
	var $new_schema = true;

	static $serialized_fields = array(
		'sources_spec',
		'columns_spec',
		'filters_spec',
		'filter_values',
		'sort_order',
	);

	// This is used to retrieve related fields from form posts.
	var $additional_column_fields = Array(
		//'assigned_user_name', 'task_id', 'note_id', 'meeting_id', 'call_id', 'email_id'
	);

	var $valid_totals = array(
		'name' => array('count'),
		'int' => array('sum', 'avg', 'min', 'max', 'stddev'),
		'num' => array('sum', 'avg', 'min', 'max', 'stddev'),
		'float' => array('sum', 'avg', 'min', 'max', 'stddev'),
		'double' => array('sum', 'avg', 'min', 'max', 'stddev'),
		'currency' => array('sum', 'avg', 'min', 'max'),
		'date' => array('min', 'max'),
		'datetime' => array('min', 'max'),
	);

	var $valid_formats = array(
		'date' => array(/*'dayspast', 'daysfuture', 'day',*/ 'month', 'qtr', 'year', 'yearmonth', 'fqtr', 'fy'),
		'datetime' => array('dateonly', 'timeonly', /*'dayspast', 'daysfuture', 'day',*/ 'month', 'qtr', 'year', 'yearmonth', 'fqtr', 'fy'),
		/*'user_name' => array('full_user_name'),
		'assigned_user_name' => array('full_user_name'),*/
	);
	
	var $module_name_fields = array('full_name', 'name', 'contract_no');
	
	var $multienum_split_fields = array('cont.category' => 10);

    const DEFAULT_RUN_INTERVAL = '1';
    const DEFAULT_RUN_INTERVAL_UNIT = 'days';

	function Report() {
		parent::SugarBean();
		global $current_language;
		$this->mod_strings = return_module_language($current_language, 'Reports');
		$this->timedate = new TimeDate();
	}

	function mark_deleted($id) {
		$report = new Report();
		$report = $report->retrieve($id);
		if(! $report->deleted) {
			$report->load_relationship('report_data');
			$keys = $report->report_data->get();
			$data = new ReportData();
			if(is_array($keys)) foreach($keys as $k)
				$data->mark_deleted($k);
			$report->deleted = 1;
			$report->save();
			$data->cleanup();
		}
		$report->cleanup();
	}


	function &get_chartable_reports() {
		$lq = new ListQuery('Report', array('id', 'name', 'chart_name', 'chart_type'));
		$lq->addSimpleFilter('chart_type', '', 'length');
		$lq->addAclFilter('view');
		$lq->setOrderBy('name');
		$charts = array();
		foreach($lq->fetchAllRows() as $row) {
			$key = empty($row['chart_name']) ? $row['id'] : $row['chart_name'];
			$charts[$key] = array('type' => 'report', 'id' => $row['id'], 'name' => $row['name'], 'chart_type' => $row['chart_type']);
		}
		return $charts;
	}

	function get_search_module_options()
	{
		$models = AppConfig::setting('model.index.reportable', array());
		$report_modules = array();
		foreach($models as $m) {
			$mod = AppConfig::module_for_model($m);
			$report_modules[$mod] = translate('LBL_MODULE_TITLE', $mod);
		}
		asort($report_modules);
		return $report_modules;
	}
	
	function cleanup() {
		if(isset($this->seeds_arr)) {
			$this->cleanup_list($this->seeds_arr);
			$this->seeds_arr = array();
		}
		if(isset($this->links_arr)) {
			$this->cleanup_list($this->links_arr);
			$this->links_arr = array();
		}
		parent::cleanup();
	}
	
	
    static function blank_report() {
    	$rec = array(
			'run_method' => 'manual',
			'chart_title' => translate('LBL_DEFAULT_CHART_TITLE', 'Reports'),
			'chart_description' => translate('LBL_DEFAULT_CHART_DESCRIPTION', 'Reports'),
			'chart_rollover' => translate('LBL_DEFAULT_CHART_ROLLOVER', 'Reports'),
    	);
    	return $rec;
    }

	static function load_report($module, $id, $user_id=null, $as_result=false) {
		$lq = new ListQuery('Report');
		if($module)
			$lq->addSimpleFilter('primary_module', $module);
		if(! isset($user_id)) {
			if(! AppConfig::is_admin())
				$user_id = AppConfig::current_user_id();
		}
		$lq->setAclUserId($user_id);
		$lq->addAclFilter('view');
		$ret = $lq->queryRecord($id);
		if (! $ret || $ret->failed)
			return null;
		if($as_result)
			return $ret;
		return $ret->row;
	}
	
	static function save_report($module, $id, $values, $user_id=null) {
		if($id)
			$base = self::load_report($module, $id, $user_id, true);
		if(empty($base))
			$ru = RowUpdate::blank_for_model('Report');
		else
			$ru = RowUpdate::for_result($base);
		$ru->set('primary_module', $module);
		if(! $ru->getField('assigned_user_id')) {
			if(! isset($user_id)) $user_id = AppConfig::current_user_id();
			$ru->set('assigned_user_id', $user_id);
		}
		if($values)
			$ru->loadInput($values);
		if($ru->save())
			return $ru;
		return false;
	}
	
	static function delete_report($module, $id, $user_id=null) {
		$base = self::load_report($module, $id, $user_id, true);
		if(! $base)
			return false;
		$ru = new RowUpdate($base);
		return $ru->markDeleted();
	}
	
	static function render_module_link($module) {
		$label = translate('LBL_MODULE_TITLE', $module);
		$icon = get_image($module, '');
		return "$icon&nbsp;<a href=\"index.php?module=$module&action=index&layout=Reports\" class=\"listViewExtLink\">$label</a>";
	}
	
	static function load_template(ReportTemplate $tpl, $set_id=false, $unserialize=false) {
		$report = RowUpdate::blank_for_model('Report');
		
		$update = array();
		foreach(ReportTemplate::$template_fields as $field)
			if(isset($tpl->$field))
				$update[$field] = $tpl->$field;
		if(empty($update['sources_spec']))
			throw new IAHInternalError('No sources defined by template');
		if(empty($update['columns_spec']))
			throw new IAHInternalError('No fields defined by template');

		foreach(self::$serialized_fields as $f)
			if(isset($update[$f]) && is_array($update[$f]))
				$update[$f] = serialize(from_html($update[$f]));
		$update['from_template_id'] = $tpl->id;

		foreach(array('name', 'description', 'chart_title', 'chart_rollover', 'chart_description') as $f) {
			if(isset($update[$f])) $update[$f] = translate($update[$f], 'Reports');
		}
		
		if($set_id && ! empty($update['chart_name'])) {
            $id = md5(strtolower(str_replace(' ', '_',$update['chart_name'])));
            $check_id_result = ListQuery::quick_fetch('Report', $id);
            if ($check_id_result) {
                $report = RowUpdate::for_result($check_id_result);
            } else {
                $update['id'] = $id;
            }
		}
		$update['assigned_user_id'] = AppConfig::current_user_id();

        if (empty($update['run_interval']))
            $update['run_interval'] = self::DEFAULT_RUN_INTERVAL;

        if (empty($update['run_interval_unit']))
            $update['run_interval_unit'] = self::DEFAULT_RUN_INTERVAL_UNIT;

        $report->set($update);
		return $report;
	}
	
	static function get_last_data($report_id) {
		$lq = new ListQuery('ReportData');
		$lq->addSimpleFilter('report_id', $report_id);
		return $lq->runQuerySingle('date_entered desc');
	}
}


function cmp_order_by($a, $b) {
	if($a['depth'] == $b['depth']) {
		if($a['sort'] == 'nested') {
			if($a['source'] == $b['source'])
				return -1;
			else if($a['parent'] == $b['source'])
				return 1;
		}
		else if($b['sort'] == 'nested')
			return - cmp_order_by($b, $a);
		else if($a['sort'] == 'grouped' && $b['sort'] != 'grouped')
			return 1;
		else
			return ($a['pos'] < $b['pos']) ? -1 : 1;
	}
	return ($a['depth'] < $b['depth']) ? -1 : 1;
}
