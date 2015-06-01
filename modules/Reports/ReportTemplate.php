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
require_once('include/utils.php');


class ReportTemplate extends SugarBean {
	// stored fields
	var $id;
	var $name;
	var $description;
	var $modified_user_id;
	var $created_by;
	var $date_entered;
	var $date_modified;
	
	var $primary_module;
	var $sources_spec;
	var $columns_spec;
	var $filters_spec;
	var $filter_values;
	var $sort_order;
	var $run_method;
	var $run_interval;
	var $run_interval_unit;
	
	var $filename;
	var $file_modified;
	
	// static fields
	var $table_name = "reports_templates";
	var $object_name = "ReportTemplate";
	var $object_names = "ReportTemplates";
	var $module_dir = "Reports";
	var $new_schema = true;

	static $template_dir = "modules/Reports/templates";

	static $template_fields = array(
		'name', 'description','primary_module',
		'run_method', 'run_interval', 'run_interval_unit',
		'chart_name', 'chart_type', 'chart_title', 'chart_rollover', 'chart_description', 'chart_series',
		'sources_spec', 'columns_spec', 'filters_spec', 'filter_values', 'sort_order',
	);
	static $serialized_fields = array(
		'sources_spec',
		'columns_spec',
		'filters_spec',
		'filter_values',
		'sort_order',
	);
	
	
	function populateFromRow(array $row) {
		parent::populateFromRow($row);
		foreach(self::$serialized_fields as $f) {
			if(! empty($row[$f]))
				$this->$f = unserialize(from_html($row[$f]));
		}
	}
	
	function save($check_notify=false) {
		foreach(self::$serialized_fields as $f) {
			if(isset($this->$f) && is_array($this->$f))
				$this->$f = to_html(serialize($this->$f));
		}
		parent::save($check_notify);
	}
	

	static function &all_by_filename($hide_converted=false) {
		global $db;
		$query = "SELECT tpl.* FROM reports_templates tpl ";
		if($hide_converted)
			$query .= " LEFT JOIN reports ON tpl.id = reports.from_template_id
				AND NOT reports.deleted ";
		$query .= " WHERE tpl.filename IS NOT NULL AND NOT tpl.deleted ";
		if($hide_converted)
			$query .= " AND reports.id IS NULL ";
		$result = $db->query($query, true, "Error retrieving report template list");
		$all = array();
		while($row = $db->fetchByAssoc($result)) {
			$obj = new ReportTemplate();
			$obj->populateFromRow($row);
			$all[$obj->filename] = $obj;
		}
		return $all;
	}
	
	static function scan_template_dir() {
		$templates = self::all_by_filename();
		$dir = self::$template_dir;
		$status = array('added' => array(), 'updated' => array(), 'invalid' => array());
		
		$tpl_files = array();
		foreach(glob_unsorted($dir . '/*.php') as $path)
			$tpl_files[$path]['last_modified'] = filemtime($path);
		
		require_once('include/config/format/ConfigParser.php');
		
		foreach($tpl_files as $fname => $data) {
			$name = substr(basename($fname), 0, -4);
			if(!isset($templates[$fname])) {
				$obj = new ReportTemplate();
				$obj->filename = $fname;
			}
			else {
				$obj =& $templates[$fname];
				if($obj->file_modified == $data['last_modified'])
					continue;
			}
			$obj->file_modified = $data['last_modified'];
			
			try {
				$content = ConfigParser::load_file($fname);
			} catch(IAHConfigFileError $e) {
				$GLOBALS['log']->warn("Invalid report template file: $fname");
				$status['invalid'][] = $name;
				continue; // skip non-config files
			}
			if(empty($content['detail']) || empty($content['detail']['name'])) {
				$status['invalid'][] = $name;
				continue;
			}
			
			array_extend($content, $content['detail']);
			
			if(isset($content['filters'])) {
				foreach($content['filters'] as &$filt) {
					if(isset($filt['field'])) {
						if(array_key_exists('value', $filt)) {
							$content['filter_values'][$filt['field']] = $filt['value'];
							unset($filt['value']);
						}
						if(array_key_exists('operator', $filt)) {
							$content['filter_values'][$filt['field'].'-operator'] = $filt['operator'];
							unset($filt['operator']);
						}
						if(array_key_exists('period', $filt)) {
							$content['filter_values'][$filt['field'].'-period'] = $filt['period'];
							unset($filt['period']);
						}
					}
				}
			}
			foreach(array('sources', 'columns', 'filters') as $f)
				if(isset($content[$f])) $content[$f.'_spec'] = $content[$f];
			
			foreach(self::$template_fields as $f) {
				$obj->$f = array_get_default($content, $f);
			}
			if($obj->id)
				$status['updated'][] = $name;
			else
				$status['added'][] = $name;
			$obj->save();
			$obj->cleanup();
		}
		
		return $status;
	}
}

?>
