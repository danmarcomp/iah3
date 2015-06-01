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


require_once('modules/Reports/Report.php');
require_once('modules/ReportData/ReportData.php');

class ReportFormatter {

	var $focus;
	var $report;
	var $report_strings;
	var $target;
	
	var $headers;
	var $cols;
	var $fields;
	var $sources;
	var $totals;
	var $group_levels;
	var $group_sums;
	var $grand_total;


	function ReportFormatter() {
	}
	
	function init(&$reportdata) {
		global $current_language;
        if ($reportdata instanceof RowUpdate) {
            $data = new ReportData();
            $data->retrieve($reportdata->getPrimaryKeyValue());
            $this->focus =& $data;
        } else {
            $this->focus =& $reportdata;
        }
		$this->report =& $this->focus->get_report();
		$this->report_strings = return_module_language($current_language, 'Reports');
		$this->group_levels = count($this->focus->groups_arr);
	}
	
	function create_filename() {
		global $current_user;
		$focus =& $this->focus;
		$pretty = $this->report_strings['LBL_FILENAME_PREFIX'].'-'.$focus->report_name;
		return $pretty;
	}
	
	function get_title() {
		$title = $this->focus->report_name. ' - '. $this->focus->date_entered;
		if(!empty($this->focus->name)) $title .= " ({$this->focus->name})";
		return $title;
	}
	
	function handle_request() {
		if (isset($GLOBALS['subaction'])) $action = $GLOBALS['subaction'];
		else $action = $GLOBALS['action'];
		$record = array_get_default($_REQUEST, 'record');
		if($record && (!isset($GLOBALS['focus']) || !is_a($GLOBALS['focus'], 'ReportData'))) {
			$focus = new ReportData();
			if(! $focus->retrieve($record)) {
				sugar_die('Cannot export report - record ID not given or unknown');
			}
		}
		elseif((!isset($GLOBALS['focus']) || !is_a($GLOBALS['focus'], 'ReportData'))) {
			sugar_die('Cannot export report - record ID not given or unknown');
		} else {
			$focus =& $GLOBALS['focus'];
		}
		switch($action) {
			case 'PDF': $method = 'pdf'; break;
			case 'HTML': $method = 'html'; break;
			default:
				if(!empty($_REQUEST['export_method']))
					$method = $_REQUEST['export_method'];
				else
					$method = 'csv';
		}
		$this->init($focus);
		$output =& $this->format_output($method);
		$this->display_output($output);
	}

	function &format_output($format) {
		switch($format) {
			case 'html':
			case 'pdf':
				$cls = 'Report'.strtoupper($format);
				break;
			default:
				$cls = 'ReportExport';
		}
		$this->format = $format;
		require_once("modules/ReportData/$cls.php");
		$this->target = new $cls();
		if(method_exists($this->target, 'report_init_output'))
			$this->target->report_init_output($this);
		if(method_exists($this->target, 'report_format_output'))
			$output = $this->target->report_format_output();
		else {
			$this->pre_format();
			$output = $this->hier_output();
		}
		return $output;
	}
	
	function pre_format() {
		$focus =& $this->focus;
		$report =& $this->report;
		
		$headers = array(); // depth -> column names
		$cols = array(); // name -> column attributes 
		$sources = $focus->sources_arr;
		$report->add_source_translations($sources);
		$fields = $focus->fields_arr;
		$report->add_field_translations($fields);
		$totals = $focus->totals_arr;
		$report->add_total_translations($totals, $fields);

		foreach($fields as $name => $def) {
			if($def['display'] != 'hidden' && $def['display'] != 'query_only') {
				$title = empty($def['display_name']) ? $def['name_translated'] : $def['display_name'];
				$hdr = array('title' => $title);
				$type = array_get_default($def, 'type');
				if($type == 'float' || $type == 'double' || $type == 'currency')
					$hdr['is_float'] = true;
				$w = array_get_default($def, 'width', '');
				if($w)
					$hdr['width'] = "$w%";
				$headers[0][$name] = $hdr;
			}
		}
		$sum_titles = array();
		if(is_array($totals)) {
			foreach($totals as $name => $def) {
				$sum_titles[$name] = empty($def['display_name']) ? $def['name_translated'] : $def['display_name'];
				$fields[$name] = $fields[$def['field']];
				foreach(array('format', 'display_format') as $f)
					if(isset($def[$f]))
						$fields[$name][$f] = $def[$f];
				$fields[$name]['is_total'] = true;
			}
		}
		
		$focus->get_rows(); // load rows, sum_rows if necessary
	
		$group_sums = array(); // depth -> group identifier -> summation values
		$sum_headers = array(); // depth -> sum identifiers
		$grand_total = null;
		$group_levels = $this->group_levels;
		foreach($focus->groups_arr as $d => $grp) {
			$headers[$d] = array();
			foreach($headers[$d-1] as $name => $hdr) {
				$srcname = $fields[$name]['source'];
				$is_single_related = false;
				$src =& $sources[$srcname];
				if($src['type'] == 'link' && $src['parent'] == $grp['source'] && $src['link_type'] == 'one')
					$is_single_related = true;
				list($grpsrc, $grpfld) = explode('.', $grp['field']);
				$is_grouping = ($grpsrc == $grp['source']);
				if($name != $grp['field'] && ($srcname == $grp['source'] || $is_single_related || $is_grouping)) {
					$headers[$d][$name] = $hdr;
					unset($headers[$d-1][$name]);
				}
			}
		}
	
		if(is_array($focus->sum_rows) && count($focus->sum_rows)) {
			foreach($focus->sum_rows as $row) {
				$d = $row['depth'];
				$sum_values = array_slice($row, 2*$group_levels + 1);
				
				$fake_row = array();
				foreach($sum_values as $sum_id => $val) {
					if($val === '#SKIP#')
						unset($sum_values[$sum_id]);
					else {
						$fake_row[$sum_id] = $val;
						$sum_values[$sum_id] = $focus->format_field_data($fields, $sum_id, $fake_row, $this->format);
					}
				}
				
				if($d == 0) {
					$grand_total = $sum_values;
					$store_sums = 1;
				}
				else {
					$key = array();
					for($k = 1; $k <= $d; $k++) $key[] = $row['value'.$k];
					$key = implode("\t", $key);
					$store_sums = ! isset($group_sums[$d]);
					if (!empty($key)) {
						$group_sums[$d][$key] = $sum_values;
					} else {
						$group_sums[$d][] = $sum_values;
					}
				}
				
				if($store_sums) {
					foreach($sum_values as $sum_id => $val) {
						$hdr = array(
							'title' => $sum_titles[$sum_id],
							'is_total' => true,
						);
						$type = $fields[$sum_id]['type'];
						if($type == 'float' || $type == 'double' || $type == 'currency')
							$hdr['is_float'] = true;
						$sum_headers[$d][$sum_id] = $hdr;
					}
				}
			}
		}
		
		$fs = array(
			'headers', 'sum_headers', 'cols',
			'sources', 'fields', 'totals',
			'group_sums', 'grand_total',
		);
		foreach($fs as $f)
			$this->$f =& $$f;
	}
	

	function &hier_output() {
		$last_group = array();
		$pending_rows = array();
		$depth_change = 0;
		$depth = 0;
		$group_rows = array();
		$group_depth = 0;
		$row_counts = array();
		$first_row = true;
		$skipped_row = null;
	
		$focus =& $this->focus;
		$rows =& $focus->rows;
		$i = 0;
		$nrows = count($rows);
		reset($rows);
		
		while ($i < $nrows) {
			$row = $rows[$i];
			while ($depth < $this->group_levels) {
				if(count($group_rows) && $group_depth != $depth) {
					$this->hier_output_rows($group_depth, $group_rows);
					$group_rows = array();
				}
				$field = $focus->groups_arr[$depth+1]['field'];
				$last_group[$field] = $row[$field];
				$key = implode("\t", $last_group);
	
				if(isset($this->group_sums[$depth+1][$key])) {
					$row = array_merge($row, $this->group_sums[$depth+1][$key]);
				}
				$group_rows[] = $row;
				$group_depth = $depth;
				$depth++;
			}
			$pending_rows = array();
			$depth_change = 0;
			while (!$depth_change && $i < $nrows) {
				$key = implode("\t", $last_group);
				if(isset($group_sums[$depth][$key])) {
					$row = array_merge($row, $this->group_sums[$depth][$key]);
				}
				if(isset($this->headers[$depth]) && is_array($this->headers[$depth]))
					$pending_rows[] = $row;
				if(++$i >= $nrows)
					break;
				$row = $rows[$i];
				$depth_change = 0;
				$ngroups = count($last_group);
				$j = 0;
				foreach($last_group as $k => $v) {
					if($row[$k] != $v) {
						array_pop($last_group);
						$depth_change = $j - $ngroups;
						break;
					}
					$j++;
				}
			}
			if(count($pending_rows)) {
				$this->hier_output_rows($group_depth, $group_rows);
				$group_rows = array();
				$this->hier_output_rows($depth, $pending_rows);
			}
			$depth += $depth_change;
		}
		if(count($group_rows))
			$this->hier_output_rows($group_depth, $group_rows);
		
		if($this->grand_total !== null) {
			$total_rows = array($this->grand_total);
			$headers = $this->sum_headers[0];
			$this->target->report_format_totals($headers, $total_rows, $this->report_strings['LBL_PDF_GRAND_TOTALS']);
		}
	
		$output =& $this->target->report_get_output();
		return $output;
	}
	
	
	function hier_output_rows($depth, &$rows) {
		if(count($rows)) {
			$headers = $this->headers[$depth];
			foreach($rows as $idx => $r) {
				foreach(array_keys($headers) as $f)
					$r[$f] = $this->focus->format_field_data($this->fields, $f, $r, $this->format);
				$rows[$idx] = $r;
			}
			if(isset($this->sum_headers[$depth+1]))
				$headers = array_merge($headers, $this->sum_headers[$depth+1]);
			$this->target->report_format_rows($depth, $headers, $rows);
		}
	}

	
	function display_output(&$output, $suppress_headers=false) {
		if($output['format'] == 'file') {
			header('Location: '. $output['path']);
		}
		else {
			if(!headers_sent() && !$suppress_headers) {
				//while(@ob_end_clean());
				header("Pragma: cache");
				if(!empty($output['filename'])) {
					$disposition = 'inline';
					if (!empty($output['disposition'])) {
						$disposition = $output['disposition'];
					}

					$name = $output['filename'];
					if(isset($_SERVER['HTTP_USER_AGENT']) && (
						preg_match("/MSIE/", $_SERVER['HTTP_USER_AGENT'])
						|| preg_match("/KHTML/", $_SERVER['HTTP_USER_AGENT']))) {
						$name = str_replace("+", "%20", urlencode($name));
					} else {
						// ff 1.5+
						$name = mb_encode_mimeheader($name, 'UTF-8', 'Q');
						// longreach - added -- remove wrapping
						$name = preg_replace('/[\r\n]+\s*/', '' , $name);
					}

					header('Content-Disposition: ' . $disposition . '; filename="' . $name . '"');
				}
				if(!empty($output['mimetype'])) {
					$type = $output['mimetype'];
					if(!empty($output['charset']))
						$type .= '; charset='. $output['charset'];
					header('Content-Type: '. $type);
				}


				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
				header('Last-Modified: '. gmdate('D, d M Y H:i:s') . ' GMT');
				header('Content-Length: '.strlen($output['data']));
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			}
			echo $output['data'];
		}
	}
}

?>
