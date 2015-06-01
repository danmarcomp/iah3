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


define('REPORT_PAGE_SIZE', 500);
define('REPORT_CACHE_FILENAME', 'report.tsv');

function run_report_id($report_id, $return_id=true) {
	$result = ListQuery::quick_fetch('Report', $report_id);
	if($result)
		return run_report_result($result, false, $return_id);
	return $result;
}

function report_cache_filename($id, $name_base, $sums=false) {
	$fname = AppConfig::setting('site.paths.reports_dir') . $id;
	if($sums)
		$fname .= '_sums_';
	return $fname . $name_base;
}

function run_report_result(RowResult &$report, $scheduled=false, $return_id=false, $name_base=null) {
	global $db, $timedate;
	$mod = $report->row['primary_module'];
	$bean = AppConfig::module_primary_bean($mod);
	if(! $bean)
		return false;
	require_once('include/ListView/ListFormatter.php');
	$fmt = new ListFormatter($bean);
	$fmt->loadReportResult($report, null, true);

	if(! $fmt)
		return false;

	// archive previous report data
	$report_id = $report->row['id'];
	$q = "UPDATE reports_data SET archived=1 WHERE report_id='$report_id' AND NOT deleted";
	$db->query($q);
	
	$lq = $fmt->getQuery();
	$fmt->applyFilterClauses($lq);
	$lq->default_use_key_index = false;
	$lq->addAclFilter('list');
	$result = $lq->runQuery(0, null, false, null, constant('REPORT_PAGE_SIZE'));
	if(! empty($GLOBALS['debug_report'])) {
		pr2($result->fields, 'fields', false, true);
		//pr2($fmt->getFilterForm()->filter_options, 'filter options', false, true);
		pr2($fmt->getFilterForm()->getFilterValues(), 'filter values', false, true);
		pr2($result->query, 'report query', true);
	}
	
	$id = create_guid();
	if(! isset($name_base))
		$name_base = constant('REPORT_CACHE_FILENAME');

	$filepath = report_cache_filename($id, $name_base);
	if(($cache = @fopen($filepath, 'wb')) === FALSE)
		sugar_die("Error: could not write to cache file $filepath - check directory permissions.");
	$header = array_keys($result->fields);
	fwrite($cache, implode("\t", $header));
	fwrite($cache, "\r\n");
	
	$idx = 0;
	$offsets = array(
		'span' => 100,
		'pos' => array(ftell($cache)),
	);
	while(! $result->failed) {
		$output = make_separated_values($result->rows, "\t", '    ');
		foreach($output as $line) {
			fwrite($cache, $line."\r\n");
			$idx ++;
			if($idx % $offsets['span'] == 0)
				$offsets['pos'][] = ftell($cache);
		}
		if($result->total_counted)
			break;
		$lq->pageResult($result);
	}
	fclose($cache);
	
	$cols = $fmt->getColumns();
	$seen = array();
	expand_report_columns($cols, $result->fields, $seen);
	foreach($result->fields as $k => $fdef) {
		if(empty($seen[$k])) {
			$cdef = array(
				'field' => $k,
				'hidden' => true,
			);
			foreach($fdef as $kk => $vv) {
				if($kk != 'source' && $kk != 'name' && ! isset($cdef[$kk]))
					$cdef[$kk] = $vv;
			}
			$cols[] = $cdef;
		}
	}
	
	$grouping = null;
	run_report_summation($fmt, $id, $grouping, $name_base);
	
	$rdata = RowUpdate::blank_for_model('ReportData');
	$upd = array(
		'id' => $id,
		'report_id' => $report_id,
		'primary_module' => $report->row['primary_module'],
		'record_count' => $result->total_count,
		//'record_offsets' => serialize($offsets),
		'archived' => 0,
		'columns_spec' => serialize($cols),
		'sources_spec' => serialize($fmt->getSources()),
		'groups' => serialize($grouping),
		'sort_order' => $report->row['sort_order'],
		'cache_filename' => $name_base,
		'chart_type' => $report->row['chart_type'],
		'chart_options' => $report->row['chart_options'],
		'chart_title' => $report->row['chart_title'],
		'chart_rollover' => $report->row['chart_rollover'],
		'chart_description' => $report->row['chart_description'],
		'chart_series' => $report->row['chart_series'],
		'export_ids' => $report->row['export_ids'],
	);
	if($scheduled)
		$upd['assigned_user_id'] = $report->row['assigned_user_id'];
	else
		$upd['assigned_user_id'] = AppConfig::current_user_id();
	$rdata->set($upd);
	if(! $rdata->save())
		throw new IAHInternalError("Error saving report data");

	$repup = RowUpdate::for_result($report);
	if($scheduled) {
		$last_run = $report->getField('last_run');
		if (!$last_run)
			$last_run = gmdate('Y-m-d H:i:s');
		$ival = $report->row['run_interval'];
		$ival_unit = $report->row['run_interval_unit'];
		if($ival && $ival_unit) {
			if($ival > 0) $ival = '+' . $ival;
			$repup->set('next_run', gmdate('Y-m-d H:i:s', strtotime("$last_run GMT $ival $ival_unit")));
		}
	}
	$repup->set('last_run', $timedate->get_gmt_db_datetime());
	$repup->save();
	
	return $return_id ? $id : $rdata;
}


function run_report_summation(ListFormatter &$fmt, $id, &$groups, $name_base=null) {
	$lq = $fmt->getQuery();
	$sum_result = $lq->runSummationQuery();
	if($sum_result && ! empty($GLOBALS['debug_report']))
		pr2($sum_result->query, 'summation query', true);

	$groups = $lq->summation_groups;
	
	if(! $sum_result || $sum_result->failed || ! $sum_result->rows)
		return false;
	
	$filepath = report_cache_filename($id, $name_base, true);
	if(($cache = @fopen($filepath, 'wb')) === FALSE)
		sugar_die("Error: could not write to cache file $filepath - check directory permissions.");
		
	$header = array_keys($sum_result->rows[0]);
	fwrite($cache, implode("\t", $header));
	fwrite($cache, "\r\n");
	
	while(! $sum_result->failed) {
		$output = make_separated_values($sum_result->rows, "\t", '    ');
		foreach($output as $line) {
			fwrite($cache, $line."\r\n");
		}
		if($sum_result->total_counted)
			break;
		$lq->pageResult($sum_result);
	}
	fclose($cache);
	
	return $cache;
}


function expand_report_columns(&$cols, $fdefs, &$seen) {
	foreach($cols as &$col) {
		if(isset($col['add_fields']))
			expand_report_columns($col['add_fields'], $fdefs, $seen);
		if(isset($col['alias']))
			$name = $col['alias'];
		else if(isset($col['field']))
			$name = $col['field'];
		else
			continue;
		$seen[$name] = 1;
		if(isset($fdefs[$name])) {
			foreach($fdefs[$name] as $k => $v) {
				if($k != 'source' && $k != 'name' && ! isset($col[$k])) {
					if($k == 'link') $k = 'source';
					$col[$k] = $v;
				}
			}
		}
		if(array_get_default($col, 'type') == 'name') {
			$src = array_get_default($col, 'source');
			if(is_string($src)) {
				if(isset($fdefs[$src.'.id']))
					$col['id_name'] = $src.'.id';
			}
		}
	}
}

function extract_report_fields($cols, &$ret) {
	foreach($cols as $col) {
		if(isset($col['add_fields']))
			extract_report_fields($col['add_fields'], $ret);
		if(isset($col['alias']))
			$name = $col['alias'];
		else if(isset($col['field']))
			$name = $col['field'];
		else
			continue;
		if(isset($col['source']) && is_string($col['source']))
			$col['link'] = $col['source'];
		$col['source'] = array('type' => 'db');
		$col['name'] = $name;
		unset($col['field']);
		unset($col['alias']);
		if(! isset($col['type'])) $col['type'] = 'varchar';
		$ret[$name] = $col;
	}
}


function load_report_result_id($id, $offset=null, $limit=null) {
	$result = ListQuery::quick_fetch('ReportData', $id, null);
	if($result)
		return load_report_result($result, $offset, $limit);
	return $result;
}


function load_report_result(RowResult &$rdata, $offset=null, $limit=null, $page_size=null, $row_indexes=null, $use_key_index=true) {
	$fname = report_cache_filename($rdata->row['id'], $rdata->row['cache_filename']);
	if(! is_readable($fname))
		throw new IAHInternalError("Report data file not found/readable: $fname");
	
	if(! $cache = fopen($fname, 'rb'))
		throw new IAHInternalError("Error opening report data file: $fname");
	
	$result = new ListResult();
	$result->result_source = 'report';
	$result->base_model = AppConfig::module_primary_bean($rdata->row['primary_module']);
	
	if(is_array($rdata->row['columns_spec'])) {
		// ok
	}
	else if(! strlen($rdata->row['columns_spec']) && isset($rdata->row['report_fields'])) {
		require_once('modules/Reports/upgrade_util.php');
		$spec = upgrade_report_spec($rdata->row, true);
		$rdata->row = array_merge($rdata->row, $spec);
	} else {
		foreach(array('columns_spec', 'filters_spec', 'filter_values', 'sources_spec', 'groups') as $f)
			$rdata->row[$f] = unserialize($rdata->row[$f]);
	}
	if(! empty($rdata->row['columns_spec'])) {
		extract_report_fields($rdata->row['columns_spec'], $result->fields);
	}
	$result->links = $rdata->row['sources_spec'];
	$result->grouping = $rdata->row['groups'];
	
	$mods = array();
	$mods[0] = $rdata->row['primary_module'];
	
	$psource = null;
	if(is_array($result->links)) {
		foreach($result->links as $k => $l) {
			if(array_get_default($l, 'display') == 'primary')
				$psource = $k;
			$mods[$k] = array_get_default($l, 'module');
		}
		if(isset($result->fields[$psource.'.id']))
			$result->primary_key = $psource.'.id';		
	}
	if(! $result->primary_key)
		$result->primary_key = 'id';
	$result->module_dirs = $mods;
	
	$fp_idx = 0;
	if(! isset($offset)) $offset = 0;
	if(isset($rdata->row['record_count'])) {
		if(! isset($row_indexes)) {
			$result->total_counted = true;
			$result->total_count = $rdata->row['record_count'];
			$offset = min($offset, $result->total_count);
		} else {
			$result->total_count = 0;
		}
	}
	$header = fgets($cache);
	$header = explode("\t", rtrim($header, "\r\n"));
	if($psource) {
		$lp = strlen($psource);
		foreach($header as &$h) {
			if(substr($h, 0, $lp+1) == $psource.'.')
				$h = substr($h, $lp+1);
		}
	}
	$result->page_header = $header;

	if($offset > 0 && isset($rdata->row['record_offsets'])) {
		$o = unserialize($rdata->row['record_offsets']);
		if( ($span = $o['span']) ) {
			$skipto = floor($offset / $span);
			fseek($cache, $o['pos'][$skipto]);
			$fp_idx = $skipto * $span;
		}
	}
	
	if(isset($row_indexes)) {
		$row_indexes = array_unique($row_indexes);
		sort($row_indexes);
		$result->limit_keys = $row_indexes;
	}
	
	while($fp_idx < $offset) {
		if(fgets($cache) === false) break;
		$fp_idx ++;
	}
	
	$result->offset = $offset;
	$result->limit = $limit;
	$result->page_index = 0;
	$result->page_pointer = $fp_idx;
	$result->page_size = $page_size;
	$result->page_result = $cache;
	$result->use_key_index = $use_key_index;
	
	if(! load_report_page($result))
		$result->failed = true;
	return $result;
}

function load_report_page(ListResult &$result) {
	$result->result_count = 0;
	$result->rows = array();
	
	if($result->page_result && ! $result->page_finished) {
		if(! empty($result->page_size)) {
			$c = $result->page_size;
			$result->total_counted = false;
		} else
			$c = $result->limit;
		
		$fp_idx = $result->page_pointer;
		$row_indexes = $result->limit_keys;
		if($row_indexes) {
			$seek_idx = array_shift($row_indexes);
			while($fp_idx < $seek_idx) {
				if(fgets($result->page_result) === false) break;
				$fp_idx ++;
			}
		}
		
		if($result->use_key_index)
			$key = $result->primary_key;
		else
			$key = null;
		
		$line = false;
		while((! isset($c) || $c-- > 0) && ($line = fgets($result->page_result)) !== false) {
			if(isset($seek_idx) && $seek_idx != $fp_idx) {
				$fp_idx ++;
				continue;
			}
			$line = rtrim($line, "\r\n");
			$line = preg_replace('/\\$\\\\n\\$/', "\n", $line);
			$data = explode("\t", $line);
			$row = array_combine($result->page_header, $data);
			if($key)
				$result->rows[$row[$key]] = $row;
			else
				$result->rows[] = $row;
			$result->result_count ++;
			$fp_idx ++;
			if(isset($seek_idx)) {
				if(! $row_indexes)
					break;
				$seek_idx = array_shift($row_indexes);
			}
		}
		
		$result->page_pointer = $fp_idx;
		$result->page_index ++;
		
		if($result->limit_keys) {
			if(! $row_indexes)
				$line = false; // we're done
			$result->limit_keys = $row_indexes;
			$result->total_count += $result->result_count;			
		}
		if($line === false) {
			$result->total_counted = true;
			if(! empty($result->page_size))
				$result->page_finished = true;
			fclose($result->page_result);
			$result->page_result = null;
		}
	}
	
	return true;
}


function load_report_result_sums(RowResult &$rdata, $page_size=null) {
	// paging not implemented
	
	$fname = report_cache_filename($rdata->row['id'], $rdata->row['cache_filename'], true);
	
	if(! is_readable($fname))
		throw new IAHInternalError("Report summations file not found/readable: $fname");
	
	if(! $cache = fopen($fname, 'rb'))
		throw new IAHInternalError("Error opening report summations file: $fname");
	
	$result = new ListResult();
	$result->result_source = 'report_sums';
	$result->base_model = AppConfig::module_primary_bean($rdata->row['primary_module']);
	
	if(is_array($rdata->row['columns_spec'])) {
		// ok
	}
	else if(! strlen($rdata->row['sources_spec']) && isset($rdata->row['sources'])) {
		require_once('modules/Reports/upgrade_util.php');
		$spec = upgrade_report_spec($rdata->row, true);
		$rdata->row = array_merge($rdata->row, $spec);
	} else {
		foreach(array('columns_spec', 'filters_spec', 'filter_values', 'sources_spec', 'groups') as $f)
			$rdata->row[$f] = unserialize($rdata->row[$f]);
	}
	if(! empty($rdata->row['columns_spec'])) {
		extract_report_fields($rdata->row['columns_spec'], $result->fields);
	}
	$result->links = $rdata->row['sources_spec'];
	$result->grouping = $rdata->row['groups'];
	
	$row_count = 0;
	$header = fgets($cache);
	$header = explode("\t", rtrim($header, "\r\n"));
	$result->page_header = $header;
	
	$result->offset = 0;
	$result->limit = -1;
	$result->page_index = 0;
	$result->page_size = $page_size;
	$result->page_result = $cache;

	$line = false;
	while(($line = fgets($result->page_result)) !== false) {
		$line = rtrim($line, "\r\n");
		$line = preg_replace('/\\$\\\\n\\$/', "\n", $line);
		$data = explode("\t", $line);
		$result->rows[$row_count ++] = array_combine($result->page_header, $data);
	}
	$result->result_count = $row_count;
	
	fclose($result->page_result);
	$result->page_result = null;
	
	return $result;
}


function user_run_report($rid, $return_id=true) {
	require_once('modules/Reports/Report.php');
	$report = Report::load_report(null, $rid, null, true);
	if($report)
		$ret = run_report_result($report, false, $return_id);
	else
		$ret = false;
	return $ret;
}

function maybe_unserialize($val) {
	if(is_array($val)) return $val;
	else if(! isset($val)) return array();
	return unserialize($val);
}


?>
