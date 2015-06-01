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
require_once('modules/Reports/Report.php');
require_once('include/TimeDate.php');
require_once('modules/Currencies/Currency.php');

class ReportData extends SugarBean {

	// stored fields
	var $id;
	var $reportdata_number; // used in pdf generation
	var $name;
	var $description;
	var $report_id;
	var $cache_filename;
	var $date_entered;
	var $sources;
	var $report_fields;
	var $totals;
	var $groups;
	var $ordered_by;
	var $chart_type;
	var $chart_options;
	var $chart_title;
	var $chart_description;
	var $chart_series;
	var $archived;
	var $created_by;
	var $modified_user_id;
	var $assigned_user_id;
	
	// runtime fields
	var $report;
	var $query;
	var $sum_query;
	var $rows;
	var $sum_rows;
	var $sources_arr;
	var $fields_arr;
	var $totals_arr;
	var $groups_arr;
	var $module_primary_tables;
	var $name_fields;
	
	// used for field formatting
	var $reports_language;
	var $timedate;
	var $currencies;
	
	// display-only fields
	var $report_name;
	var $assigned_user_name;
	var $created_by_name;
	var $modified_by_name;

	// static fields
	var $table_name = "reports_data";

	var $object_name = "ReportData";
	var $object_names = "ReportData";
	var $module_dir = "ReportData";

	var $new_schema = true;
	

	function ReportData() {
		parent::SugarBean();
		global $current_language;
		$this->reports_language = return_module_language($current_language, 'Reports');
		$this->timedate = new TimeDate();
		$this->module_primary_tables = array();
		$this->name_fields = array();
	}
	
	function get_summary_text()
	{
		if(empty($this->name))
			return $this->date_entered; // substr($this->date_entered, 0, 10);
		else
			return $this->name;
	}
	
	function track_view($user_id, $current_module) {
		// don't want to show up in recently viewed
	}

	function save($check_notify = false) {
		$write_data = false;
		if(empty($this->cache_filename) && !empty($this->fields_arr)) {
			if(!empty($this->rows)) {
				$write_data = true;
			}
			else if(!empty($this->query)) {
				$this->run_query();
				$write_data = true;
			}
			else
				return false;
		}
		if($write_data) {
			$this->cache_filename = 'report.tsv';
		}
		if(empty($this->report_id) && !empty($this->report))
			$this->report_id = $this->report->id;
		$this->sources = serialize($this->sources_arr);
		$this->report_fields = serialize($this->fields_arr);
		$this->totals = serialize($this->totals_arr);
		$this->groups = serialize($this->groups_arr);
		
		$ret = parent::save($check_notify);
		
		if($write_data) {
			$filepath = $this->cache_file_path();
			if(($cache = @fopen($filepath, 'w')) === FALSE)
				sugar_die("Error: could not write to cache file $filepath - check directory permissions.");
			$header = array();
			foreach(array_keys($this->fields_arr) as $f)
				if($this->fields_arr[$f]['display'] != 'query_only')
					$header[] = $f;
			fwrite($cache, implode("\t", $header));
			fwrite($cache, "\r\n");
			$output = $this->make_separated_values($this->rows, "\t", '    ');
			for($i = 0; $i < count($output); $i++)
				fwrite($cache, $output[$i]."\r\n");
			fclose($cache);
			
			if(is_array($this->sum_rows) && count($this->sum_rows)) {
				$filepath = $this->sums_cache_file_path();
				if(($cache = @fopen($filepath, 'w')) === FALSE)
					sugar_die("Error: could not write to cache file $filepath - check directory permissions.");
				$data = $this->sum_rows;
				array_unshift($data, array_keys($data[0]));
				$output = $this->make_separated_values($data, "\t", '    ');
				for($i = 0; $i < count($output); $i++)
					fwrite($cache, $output[$i]."\r\n");
				fclose($cache);
			}
		}
		return $ret;
	}
	
	function mark_others_archived($user_id) {
		$others = $this->get_full_list('', "report_id='{$this->report->id}' AND id != '{$this->id}' AND NOT archived AND assigned_user_id = '{$user_id}'");
		if($others !== null)
			for(; ($d = current($others)) !== false; next($others))
				$this->mark_archived($d->id);
	}
	
	function &retrieve($id = -1, $encode = true) {
		$result = parent::retrieve($id, $encode);
		if($result !== null) {
			$sources = $encode ? from_html($this->sources) : $this->sources;
			$fields = $encode ? from_html($this->report_fields) : $this->report_fields;
			$totals = $encode ? from_html($this->totals) : $this->totals;
			$groups = $encode ? from_html($this->groups) : $this->groups;
			$this->sources_arr = unserialize($sources);
			$this->fields_arr = unserialize($fields);
			$this->totals_arr = unserialize($totals);
			$this->groups_arr = unserialize($groups);
			return $this;
		}
		$ret = null; return $ret;
	}
	
	function make_separated_values(&$rows, $sep=',', $replace=false) {
		$result = array();

		reset($rows);
		while (list(,$row) = each($rows)) {
			$vals = array();
			foreach($row as $val) {
				$val = preg_replace('/\r?\n/', '$\n$', $val);
				if(strpos($val, $sep) !== false) {
					if($replace !== false)
						$val = strtr($val, $sep, $replace);
					else
						$val = '"'.$val.'"';
				}
				$vals[] = $val;
			}
			$result[] = implode($sep, $vals);
		}
		return $result;
	}
	
	function show_totals_in_list() {
		foreach($this->fields_arr as $name => $def)
			if($def['display'] == 'normal' || $def['display'] == 'hidden')
				return false;
		return true;
	}
	
	function &get_fields_translated() {
		$fields = $this->fields_arr;
		$this->get_report();
		$this->report->add_field_translations($fields);
		$names = array();
		reset($fields);
		while(list($id) = each($fields)) {
			$field =& $fields[$id];
			if(empty($field['display_name'])) {
				$display_name = $field['name_translated'];
				$unique = $display_name;
				for($i = 2; isset($names[$unique]); $i++) {
					$unique = "$display_name ($i)";
				}
				$field['display_name'] = $unique;
			}
		}
		return $fields;
	}
	
	function &get_totals_translated(&$fields) {
		$totals = $this->totals_arr;
		$this->get_report();
		$this->report->add_total_translations($totals, $fields);
		$names = array();
		reset($totals);
		while(list($id) = each($totals)) {
			$total =& $totals[$id];
			if(empty($total['display_name'])) {
				$display_name = $total['name_translated'];
				$unique = $display_name;
				for($i = 2; isset($names[$unique]); $i++) {
					$unique = "$display_name ($i)";
				}
				$total['display_name'] = $unique;
			}
		}
		return $totals;
	}
	
	function &get_export_options() {
		$opts = array('csv' => 1, 'tsv' => 1, 'scsv' => 1, 'html' => 1, 'xml' => 0);
		// may wish to enable/disable certain export types here based on the report or user
		global $currentUser, $current_language;
		$mod_strings = return_module_language($current_language, 'ReportData');
		$ret = array();
		foreach($opts as $name => $enabled) {
			if(! $enabled) continue;
			$ret[$name] = $mod_strings['LBL_EXPORT_'.strtoupper($name)];
		}
		return $ret;
	}
	
	function csv_split($line) {
		$pat = "/(\"(.*?(?<!\\\\))\"|[^,\r\n]*),?/";
		$vals = false;
		preg_match_all($pat, $line, $vals);
		$vals = $vals[1];
		foreach($vals as $k=>$v) {
			if($v[0] == '"')
				$vals[$k] = substr($v, 1, -1);
		}
		return $vals;
	}
	
	function run_query() {
		$result = $this->db->query($this->query, true, "Error executing report query: ");
		$this->rows = array();
		while($row = $this->db->fetchByAssoc($result, -1, false)) {
			$this->rows[] = $row;
		}
		
		if(! empty($this->sum_query)) {
			$result = $this->db->query($this->sum_query, true, "Error executing report summation query: ");
			$this->sum_rows = array();
			while($row = $this->db->fetchByAssoc($result, -1, false)) {
				$this->sum_rows[] = $row;
			}
		}
	}
	
	function cache_file_path() {
		return AppConfig::setting('site.paths.reports_dir', 'files/reports/') . "{$this->id}{$this->cache_filename}";
	}
	function sums_cache_file_path() {
		return AppConfig::setting('site.paths.reports_dir', 'files/reports/') . "{$this->id}_sums_{$this->cache_filename}";
	}
	function load_cache_file() {
		$this->rows = array();
		$this->sum_rows = array();
		$path = $this->cache_file_path();
		if(!file_exists($path))
			return false;
		if(($cache = @fopen($path, 'r')) === FALSE)
			return false;
		$line = fgets($cache);
		$header = explode("\t", substr($line, 0, -2));
		while(($line = fgets($cache)) !== false) {
			$data = explode("\t", substr($line, 0, -2));
			$assoc = array();
			for($i = 0; $i < count($header); $i++)
				$assoc[$header[$i]] = preg_replace('/\\$\\\\n\\$/', "\n", $data[$i]);
			$this->rows[] = $assoc; //array_combine($field_names, $data); - php5
		}
		fclose($cache);
		
		$sums_file = $this->sums_cache_file_path();
		if(file_exists($sums_file)) {
			if(($cache = @fopen($sums_file, 'r')) === FALSE)
				return false;
			$header = explode("\t", substr(fgets($cache), 0, -2));
			while(($line = fgets($cache)) !== false) {
				$data = explode("\t", substr($line, 0, -2));
				$assoc = array();
				for($i = 0; $i < count($header); $i++)
					$assoc[$header[$i]] = $data[$i];
				$this->sum_rows[] = $assoc;
			}
			fclose($cache);
		}
		return true;
	}
	
	function &get_report() {
		if(empty($this->report) && !empty($this->report_id)) {
			$this->report = new Report();
			$this->report->retrieve($this->report_id);
		}
		return $this->report;
	}
	
	function calc_days_offset($val, $fmt='days_past') {
		$t = strtotime($val);
		$now = strtotime($this->timedate->get_gmt_db_datetime());
		$days = round(($now - $t) / (24*3600));
		if($fmt == 'days_future') $days = -$days;
		return $days.'d';
	}
	
	function &format_row_data(&$fields, &$row, $output, $location='') {
		foreach($row as $k=>$v)
			$row[$k] = $this->format_field_data($fields, $k, $row, $output, $location);
		return $row;
	}
	
	function format_field_data(&$fields, $fldId, &$row, $output='screen', $location='') {
		global $app_list_strings, $theme, $current_user, $current_language, $calendar_list_strings;
		
		$field =& $fields[$fldId];
		$format = empty($field['format']) ? $field['type'] : $field['format'];
		$data = $row[$fldId];
		if(($data === null && $format != 'bool') || ! empty($field['no_export']))
			return '';
		$display_format = empty($field['display_format']) ? '' : $field['display_format'];
		$style = array();
		// first stage
		switch($format) {
			case 'dateonly':
				if($display_format == 'days_past' || $display_format == 'days_future')
					$data = $this->calc_days_offset($data, $display_format);
				else
					$data = $this->timedate->to_display_date($data, true);
				break;
			case 'date':
				if($data == '0000-00-00') $data = '';
				if(! empty($data)) {
					if($display_format == 'days_past' || $display_format == 'days_future')
						$data = $this->calc_days_offset($data, $display_format);
					else if(!empty($field['rel_field'])) {
						$rel_field = $field['source']. '.'. $field['rel_field'];
						if(!empty($row[$rel_field])) {
							$rel_time = from_db_convert($row[$rel_field], 'time');
							$mergetime = $this->timedate->merge_date_time($data, $rel_time);
							$data = $this->timedate->to_display_date($mergetime);
						}
					}
					/*else if(!empty($field['format'])) {
						if($field['format'] == 'yearmonth') // used by EmployeeMonthlyLeave
							$data = $this->timedate->to_display($data, $this->timedate->dbDayFormat, "Y-m");
					}*/
					else
						$data = $this->timedate->to_display_date($data, false);
				}
				break;
			case 'time':  case 'timeonly':
				if(! empty($field['rel_field'])) {
					$rel_field = $field['source']. '.'. $field['rel_field'];
					if(!empty($row[$rel_field])) {
						$rel_date = from_db_convert($row[$rel_field], 'date');
						$mergetime = $this->timedate->merge_date_time($rel_date, $data);
						$data = $this->timedate->to_display_time($mergetime);
					}
				}
				else
					$data = $this->timedate->to_display_time($data, true, true);
				break;
			case 'datetime':
				if($display_format == 'days_past' || $display_format == 'days_future')
					$data = $this->calc_days_offset($data, $display_format);
				else
					$data = $this->timedate->to_display_date_time($data);
				break;
			case 'month':
				if(!empty($data))
					$data = $app_list_strings['months_long_dom'][$data];
				break;
			case 'enum':
				if($output != 'export' && is_array($field['options_keys'])) {
					$idx = array_search($data, $field['options_keys']);
					if($idx !== false)
						$data = $field['options_values'][$idx];
				}
				break;
			case 'multienum':
				if(isset($row[$fldId.'2'])) {
					for($ii = 2; isset($row[$fldId.$ii]) && strlen($row[$fldId.$ii]); $ii++)
						$data .= '^,^'.$row[$fldId.$ii];
				}
				if($output != 'export' && is_array($field['options_keys'])) {
					$mkeys = explode('^,^', $data);
					$show = array();
					foreach($mkeys as $k) {
						$idx = array_search($k, $field['options_keys']);
						if($idx !== false)
							$k = $field['options_values'][$idx];
						$show[] = $k;
					}
					$data = implode(', ', $show);
				}
				break;
			case 'currency':
				$src = $field['source'];
				$fld = isset($field['currency_id']) ? $field['currency_id'] : 'currency_id';
				if(isset($row["$src.$fld"]))
					$in_currency_id = $row["$src.$fld"];
				else
					$in_currency_id = '-99';
				// We we're looking at usdollar amounts, ignore currency ID
				$convert = false;
				if (preg_match('/_usd(ollar)?$/', $fldId) || $fldId == 'acco.balance') {
					$in_currency_id = '-99';
					$convert = true;
					$show_currency_id = $current_user->getPreference('currency');
				} else {
					$show_currency_id = $in_currency_id;
				}
				$show_symbol = ($output != 'chart' || $location != 'endlabel');
				$rate = isset($field['exchange_rate']) ? $field['exchange_rate'] : 'exchange_rate';
				$fparams = array(
					'currency_symbol' => $show_symbol,
					'entered_currency_id' => $in_currency_id,
					'currency_id' => $show_currency_id,
					'convert' => $convert,
					'type' => $output);
				if(! empty($row["$src.$rate"]))
					$fparams['exchange_rate'] = $row["$src.$rate"];
				$data = currency_format_number($data, $fparams);
				break;
			case 'float':
			case 'double':
				$thou_sep = '';
				$suffix = '';
				$decimals = 2;
				if($output != 'export') {
					$thou_sep = ',';
					if($display_format == 'rounded') {
						$decimals = 0;
					}
					else if($display_format == 'thousands') {
						if($output != 'chart')
							$data /= 1000;
						$suffix = 'K';
						$decimals = 0;
					}
					else if($display_format == 'millions') {
						if($output != 'chart')
							$data /= 1000000;
						$suffix = 'M';
						$decimals = 1;
					}
					if($output == 'chart')
						$decimals = 0;
				}
				$data = number_format($data, $decimals, '.', $thou_sep).$suffix;
				break;
			//case 'user_name':
			case 'assigned_user_name':
				if($output != 'export') {
					if($display_format == 'full_name') {
						$names = get_user_array(false, '0', 'all', true);
						$data = @$names[$data];
						break;
					}
				}
				$data = get_assigned_user_name($data);
				break;
		}
		if($display_format == 'strip_space') {
			// used to display invoice & quote numbers properly
			$data = preg_replace('/ /', '', $data);
		}
		if($output != 'export') {
			// second stage
			switch($format) {
				case 'name':
					$src = $field['source'];
					if(isset($row["$src.id"]) && ($output == 'screen' || $output == 'html')) {
						$icon = '';
						$module = $this->sources_arr[$src]['module'];
						$id_field = $fields["$src.id"];
						if(!empty($id_field['id_module_field'])) {
							$module_field = $src. '.'. $id_field['id_module_field'];
							if(!empty($row[$module_field])) {
								$module = $row[$module_field];
								$icon = get_image("themes/$theme/images/$module", 'alt="'.$app_list_strings['moduleList'][$module].'" align="absmiddle" border="0"') .' ';
							}
						}
						$link = 'index.php?module='. $module. '&action=DetailView&record='. $row["$src.id"];
						if($output == 'html')
							$link = AppConfig::site_url().'/'.$link;
						$data = $icon . '<a class="listViewTdLinkS1" href="'.$link.'">'. htmlspecialchars($data). '</a>';
					}
					break;
				case 'id':
					// this is currently only used to look up the parent name for activities (can't be done in one query)
					if(!empty($field['id_module_field'])) {
						$module_field = $field['source']. '.'. $field['id_module_field'];
						if(!empty($row[$module_field])) {
							$module = $row[$module_field];

							// Andrey : quick and dirty. Need to track the source of this issue
							if ($module == 'Projects') $module = 'Project';
							
							if(!isset($this->module_primary_tables[$module])) {
								global $beanFiles, $beanList;
								$bean_name = $beanList[$module];
								require_once($beanFiles[$bean_name]);
								$seed = new $bean_name();
								$this->module_primary_tables[$module] = $seed->table_name;
								if (array_search('name', $seed->column_fields)) {
									$this->name_fields[$module] = 'name';
								} elseif (array_search('subject', $seed->column_fields)) {
									$this->name_fields[$module] = 'subject AS name';
								} elseif (array_search('last_name', $seed->column_fields)) {
									$this->name_fields[$module] = 'CONCAT(first_name, \' \', last_name) AS name';
								} else {
									$this->name_fields[$module] = 'id AS name';
								}
							}
							
							$q = "SELECT {$this->name_fields[$module]} FROM {$this->module_primary_tables[$module]} WHERE id='".$this->db->quote($data)."' LIMIT 1";
							if( ($result = $this->db->query($q, false)) ) {
								if( ($fetched_row = $this->db->fetchByAssoc($result, -1, false)) ) {
									$name = $fetched_row['name'];
									if($output == 'screen' || $output == 'html') {
										$link = 'index.php?module='. $module. '&action=DetailView&record='. $data;
										if($output == 'html')
											$link = AppConfig::site_url().'/'.$link;
										$data = '<a class="listViewTdLinkS1" href="'.$link.'">';
										$data .= htmlspecialchars($name). '</a>';
									}
									else
										$data = $name;
								}
							}
						}
					}
					break;
				case 'email':
					if($data && ($output == 'screen' || $output == 'html'))
						$data = '<a class="listViewTdLinkS1" href="mailto:'. htmlentities($data). '">'. htmlspecialchars($data). '</a>';
					break;
				case 'url':
					if($data && ($output == 'screen' || $output == 'html')) {
						$schema = 'http://';
						if(preg_match('#([a-z]+:/?/?)(.*)#', $data, $m)) {
							$schema = $m[1]; $data = $m[2];
						}
						$display = $data;
						if(strlen($display) > 50)
							$display = substr($display, 0, 47).'...';
						$data = '<a class="listViewTdLinkS1" href="'.$schema. htmlentities($data). '">'. htmlspecialchars($display). '</a>';
					}
					break;
				case 'text':
					if($output == 'screen' || $output == 'html')
						$data = '<table class="reportMultilineText" cellpadding="0" cellspacing="0" style="width: 25em; border: none; margin: none; padding: none;"><tr><td style="background: inherit">'.nl2br(htmlspecialchars($data)).'</td></tr></table>';
					//else if($output == 'pdf')
					//	$data = nl2br($data);
					break;
				case 'currency':
					break;
				case 'int':
					$data = number_format($data, 0, '.', ',');
					break;
				case 'bool':
					$data = empty($data) ? 'FALSE' : 'TRUE';
					$data = $this->reports_language['LBL_FILTER_'.$data];
					break;
				default:
					if($output == 'screen' || $output == 'html')
						$data = htmlspecialchars($data);
			}
		}
		else { // export
			$data = preg_replace('/[\r\n]+/', ' ', $data);
		}
		if(!empty($field['is_total'])) {
			if($output == 'screen' || $output == 'html')
				$data = "<b>$data</b>";
			//else if($output == 'pdf')
			//	$style['font-weight'] = 'bold';
		}
		if(count($style)) {
			$style['text'] = $data;
			$data =& $style;
		}
		return $data;
	}
		
	function &get_rows() {
		if(empty($this->cache_filename)) {
			if(empty($this->rows) && !empty($this->query)) {
				$this->run_query();
			}
		}
		else if(empty($this->rows)) {
			$this->load_cache_file();
		}
		return $this->rows;
	}
	
	function &get_list($order_by='', $where='', $current_offset=0, $limit='', $max_per_page=-1) {
		if($max_per_page < 0)
			$max_per_page = AppConfig::setting('layout.list.max_entries_per_page');
		
		$rows = $this->get_rows();
		
		if( ($show_totals = $this->show_totals_in_list()) ) {
			$group_levels = count($this->groups_arr);
			$totals = array();
			if(is_array($this->sum_rows) && count($this->sum_rows)) {
				foreach($this->sum_rows as $row) {
					if($row['depth'] == $group_levels) {
						$sum_values = array_slice($row, 2*$group_levels + 1);
						$key = array();
						for($k = 1; $k <= $group_levels; $k++) $key[] = $row['value'.$k];
						$key = implode("\t", $key);
						$totals[$key] = $sum_values;
					}
				}
			}
		}
		
		$result = array();
		$result['row_count'] = count($rows);
		$result['previous_offset'] = max($current_offset - $max_per_page, 0);
		$result['next_offset'] = min($current_offset + $max_per_page, $result['row_count']);
		
		if($order_by && $order_by != $this->ordered_by) {
			if(preg_match('/([^ ]+)( asc| desc)?/i', $order_by, $match)) {
				$order_by = $match[1];
				$dir = trim(isset($match[2]) ? $match[2] : '');
				if(!$dir) $dir = 'asc';
			}
			else
				$order_by = '';
		}
		
		if($result['next_offset'] == $current_offset)
			$row_keys = array();
		else
			$row_keys = range($current_offset, $result['next_offset']-1);

		if($order_by) {
			if(isset($this->fields_arr[$order_by])) {
				// isolate the sorted column, to avoid reordering the whole results array
				$ordered_col = array();
				foreach($rows as $row) {
					$ordered_col[] = $row[$order_by];
				}
				if($dir == 'asc')
					asort($ordered_col);
				else
					arsort($ordered_col);
				$row_keys = array_slice(array_keys($ordered_col), (int)$current_offset, $max_per_page);
			}
		}

		$lst = array();
		foreach($row_keys as $k) {
			if($show_totals && $group_levels) {
				$key = array();
				foreach($this->groups_arr as $grp)
					$key[] = $rows[$k][$grp['field']];
				$key = implode("\t", $key);
				$rows[$k] = array_merge($rows[$k], $totals[$key]);
			}
			$lst[] = $rows[$k];
		}
		$result['list'] =& $lst;
		$result['parent_data'] = array();
		return $result;
	}
	
	function mark_deleted($id) {
		$data = new ReportData();
		$data = $data->retrieve($id);
		if(! $data->deleted) {
			if(! empty($data->cache_filename)) {
				unlink($data->cache_file_path());
				$sum_file = $this->sums_cache_file_path();
				if(file_exists($sum_file))
					unlink($sum_file);
			}
			$data->deleted = 1;
			$data->save();
		}
		$data->cleanup();
	}
	
	function mark_archived($id) {
		$data = new ReportData();
		$data = $data->retrieve($id);
		if(! $data->archived) {
			$data->archived = 1;
			$data->save();
		}
		$data->cleanup();
	}
	
	function fill_in_additional_detail_fields()
	{
		global $app_list_strings;
		$this->assigned_user_name = get_assigned_user_name($this->assigned_user_id);
		$this->created_by_name = get_assigned_user_name($this->created_by);
		$this->modified_by_name = get_assigned_user_name($this->modified_user_id);
		$this->get_report();
		$this->report_name = $this->report->name;
	}
	
    function bean_implements($interface){
	    switch($interface){
			case 'ACL':return true;
		}
		return false;
	}
	
	function cleanup() {
		if(isset($this->report)) {
			$this->report->cleanup();
			unset($this->report);
		}
		parent::cleanup();
	}
}

?>
