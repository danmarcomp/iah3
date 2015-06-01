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
require_once('include/charts/Charts.php');
require_once('modules/Currencies/Currency.php');
require_once('modules/Reports/utils.php');
require_once('include/layout/FieldFormatter.php');


class ChartData {
	var $svg;
	var $chart_type;
	var $report_data;
	var $title;
	var $options;
	var $description;
	var $rollover;
	var $sources = array();
	var $primary_source;
	var $groups = array();
	var $series = array();
	var $fields = array();
	var $data_groups;
	var $currency;
	var $formatter;
	var $validate_error;

	static $all_opts = array('3d', 'stacked', 'exploded', 'percent');
	static $chart_swf = array('hbar' => 'hBarF', 'vbar' => 'vBarF', 'pie' => 'pieF', 'line' => 'lineF', 'area' => 'lineF');
	
	function __construct() {
		$this->svg = AppConfig::setting('layout.svg_charts_enabled');
	}
	
	function getSwfName() {
		return array_get_default(self::$chart_swf, $this->chart_type);
	}
	
	function getCacheFileName() {
		$xml_fname = AppConfig::setting('site.paths.xml_dir'). 'Report_';
		$rid = $this->report_data->getField('report_id');
		$rname = $this->report_data->getField('report_name');
		$rdnum = $this->report_data->getField('reportdata_number');
		$xml_fname .= preg_replace('~[^A-Za-z0-9\-.]~', '_', $rname . '-' . $rdnum);
		$xml_fname .= substr($rid, 0, 4) . ($this->svg ? '.svg' : '.xml');
		return $xml_fname;
	}
	
	function getReportSummations() {
		$result = load_report_result_sums($this->report_data);
		return $result;
	}
	
	function loadReportData(RowResult &$data) {
		global $app_list_strings, $current_user;
		
		$this->report_data = $data;
		$this->chart_type = $data->getField('chart_type');
		foreach(array('title', 'description', 'rollover') as $f)
			$this->$f = $data->getField('chart_'.$f);
		$opts = maybe_unserialize($data->getField('chart_options'));
		if(! is_array($opts)) $opts = array();
		foreach(self::$all_opts as $f)
			$this->options[$f] = array_get_default($opts, $f, 1);
		
		
		$source_fs = maybe_unserialize($data->getField('sources_spec'));
		$this->sources = array();
		if($source_fs) {
			foreach($source_fs as $src) {
				$nsrc = $src;
				if($src['display'] == 'primary')
					$this->primary_source = $src['name'];
				if(empty($nsrc['label']))
					$nsrc['label'] = translate('LBL_MODULE_TITLE', $nsrc['module']);
				$this->sources[$nsrc['name']] = $nsrc;
			}
		}
		
		$series_fs = explode(',', $data->getField('chart_series', ''));
		$this->series = array();
		foreach($series_fs as $f) {
			if($f) {
				$fb = preg_replace('~:.*~', '', $f);
				$this->series[] = array('base_field' => $fb, 'field' => $f, 'found' => 0, 'prefix' => '', 'suffix' => '');
			}
		}

		$cols = maybe_unserialize($data->getField('columns_spec'));
		$this->data_groups = maybe_unserialize($data->getField('groups'));
		$this->groups = array();
		$group_ord = array();
		foreach($cols as $col) {
			$fmt = ! empty($col['format']) ? '!'.$col['format'] : '';
			$link = (! empty($col['source']) && $col['source'] != $this->primary_source) ? $col['source'] . '.' : '';

			if(array_get_default($col, 'grouped')) {
				$group_ord[$link.$col['field'].$fmt] = count($this->groups);
				$this->groups[] = array('label' => get_name_translated($col), 'field' => $link.$col['field'].$fmt, 'depth' => -1);
			}
			
			if(isset($col['total'])) {
				$cname = $link.$col['field'].$fmt . ':'.$col['total'];
				foreach($this->series as &$s) {
					if($s['field'] == $cname) {
						$s['type'] = $col['type'];
						$s['total_type'] = $col['total'];
						$s['label'] = get_name_translated($col);
						$s['found'] = 1;
						$s['display_format'] = array_get_default($col, 'display_format');
						$s['source'] = array_get_default($col, 'link', $this->primary_source);
						$s['source_label'] = $this->sources[$s['source']]['label'];
						$s['type_label'] = array_get_default($app_list_strings['total_type_dom'], $col['total'], $col['total']);
						$s['type_short_label'] = array_get_default($app_list_strings['total_type_short_dom'], $col['total'], $col['total']);
						if(strpos($s['base_field'], '!') !== false)
							list(, $s['format']) = explode('!', $s['base_field'], 2);
						else
							$s['format'] = '';
					}
				}
				unset($s);
			}
		}
		$this->fields = array();
		$this->loadFields($cols);
		
		foreach($this->data_groups as $d => $grp) {
			$gf = $grp['field'];
			if(isset($group_ord[$gf]))
				$this->groups[$group_ord[$gf]]['depth'] = $d;
		}
		foreach($this->groups as $idx => &$grp) {
			$fmt = ! empty($grp['format']) ? '!'.$grp['format'] : '';
			$fdef = array_get_default($this->fields, $grp['field'].$fmt);
			if($fdef)
				$grp = array_merge($fdef, $grp);
		}
		unset($grp);
		
		foreach($this->series as $idx => &$ser) {
			$currency_id = $show_after = null;
			if(! $ser['found']) continue;
			if($ser['type'] == 'currency' || $ser['type'] == 'base_currency') {
				if($ser['type'] == 'currency')
					$currency_id = $current_user->getPreference('currency');
				if(empty($currency_id))
					$currency_id = '-99';
				$this->currency = AppConfig::db_row('Currency', $currency_id);
				$ser['prefix'] = $this->currency['symbol'];
				if($this->currency['symbol_place_after'])
					$show_after = true;
			}
			if($ser['format'] == 'thousands')
				$ser['suffix'] = 'K';
			else if($ser['format'] == 'millions')
				$ser['suffix'] = 'M';
			if($show_after) {
				if($ser['suffix']) $ser['suffix'] .= ' ';
				$ser['suffix'] .= $ser['prefix'];
				$ser['prefix'] = '';
			}
			
			if(empty($ser['base_label'])) {
				$fdef = array_get_default($this->fields, $ser['base_field']);
				$ser['base_label'] = $fdef ? get_name_translated($fdef) : $ser['label'];
			}
		}
	}
	
	function loadFields(array $cols) {
		foreach($cols as $col) {
			if(empty($col['total']) && ! empty($col['field'])) {
				$add_cols = array_get_default($col, 'add_fields');
				unset($col['add_fields']);
				$fmt = ! empty($col['format']) ? '!'.$col['format'] : '';
				$link = (! empty($col['source']) && $col['source'] != $this->primary_source) ? $col['source'] . '.' : '';
				$this->fields[$link.$col['field'].$fmt] = $col;
				if($add_cols)
					$this->loadFields($add_cols);
			}
		}
	}
	
	function validate() {
		if(! $this->chart_type)
			$err = 'Missing chart type';
		else if(!count($this->groups))
			$err = 'No grouped fields';
		else if($this->groups[0]['depth'] < 0)
			$err = 'Grouping depth error';
		else if(!count($this->series) || ! $this->series[0]['found'])
			$err = 'Missing chart series';
		if(isset($err)) {
			$this->validate_error = $err;
			return false;
		}
		return true;
	}
	
	function getReplaceVars() {
		$vars = array();
		$gi = '';
		foreach($this->groups as $g) {
			$vars['GROUP'.$gi] = $g['label'];
			if(! $gi) $gi = 1;
			$gi ++;
		}
		
		$si = '';
		foreach($this->series as $s) {
			$vars['SERIES'.$si] = $s['label'];
			$vars['FIELD'.$si] = $s['base_label'];
			$vars['SOURCE'.$si] = $s['source_label'];
			$vars['SERIES_TYPE'.$si] = $s['type_label'];
			$vars['SERIES_PREFIX'.$si] = $s['prefix'];
			$vars['SERIES_SUFFIX'.$si] = $s['suffix'];
			if(! $si) $si = 1;
			$si ++;
		}
		
		if($this->currency)
			$chart_vars['CURRENCY'] = $this->currency['name'];
		
		return $vars;
	}
	
	function getFormatter() {
		if(! $this->formatter)
			$this->formatter = new FieldFormatter($this->svg ? 'svg' : 'swf', 'chart');
		return $this->formatter;
	}
	
	function formatSeriesValue(array $row, $series_index=0, $display=true) {
		$fdef = $this->series[$series_index];
		$val = $row[$fdef['field']];
		if($fdef['display_format'] == 'thousands')
			$val /= 1000;
		else if($fdef['display_format'] == 'millions')
			$val /= 1000000;
		if(! $display)
			return $val;
		$val = $this->getFormatter()->formatRowValue($fdef, $row, $val);
		if(! empty($fdef['prefix']))
			$val = $fdef['prefix'] . $val;
		if(! empty($fdef['suffix']))
			$val .= $fdef['suffix'];
		return $val;
	}
	
	function formatGroupValue($row, $group_index=0) {
		if(! isset($this->groups[$group_index])) return '';
		$fdef = $this->groups[$group_index];
		$val = $row['value' . $fdef['depth']];
		$val = $this->getFormatter()->formatRowValue($fdef, $row, $val);
		if(! strlen($val)) $val = translate('LBL_MISSING_VALUE', 'app');
		return $val;
	}

}


function open_element($name, $attrs, $close=false) {
	$elt = "<$name";
	foreach($attrs as $name => $val) {
		$elt .= " $name=\"" . preg_replace('/"/', '&quot;', $val). '"';
	}
	if($close) $elt .= ' /';
	$elt .= '>';
	return $elt;
}

function get_name_translated(&$obj) {
	if(isset($obj['label'])) return $obj['label'];
	if(isset($obj['vname']))
		return translate($obj['vname'], array_get_default($obj, 'vname_module'));
}


function generate_report_chart_xml(ChartData &$data, $params=null) {
	global $current_language;
	$chart_strings = return_module_language($current_language, 'Charts');
	$chart_vars = $data->getReplaceVars();
	
	if(! $params) $params = array();
	
	$group_levels = count($data->groups);
	$summation = $data->getReportSummations();
	$grp_field = $grp_field2 = null;
	$grp_field_depth = $grp_field2_depth = null;
	foreach($data->groups as $idx => $g) {
		if($idx == 0) {
			$grp_field = $g['field'];
			$grp_field_depth = $g['depth'];
		} else if($idx == 1) {
			$grp_field2 = $g['field'];
			$grp_field2_depth = $g['depth'];
		}
		$max_group_d = $g['depth'];
	}

	$points = array();
	$mins = array();
	$maxes = array();
	$rollovers = array();
	$sums = array();
	$axis_labels = array();
	$legend_labels = array();
	
	foreach($summation->rows as $sum_row) {
		$d = $sum_row['depth'];
		
		if($d == (empty($grp_field2) ? $grp_field_depth : $grp_field2_depth)) {
			$sum_values = array_slice($sum_row, 2*$group_levels + 1);
			$values = array();
			$grp_val = $sum_row['value'.$grp_field_depth];
			$chart_vars['GROUP_VALUE'] = $data->formatGroupValue($sum_row);
			$axis_labels[$grp_val] = $chart_vars['GROUP_VALUE'];
			
			if(!empty($grp_field2)) {
				$grp_val2 = $sum_row['value'.$grp_field2_depth];
				$key = "$grp_val\t$grp_val2";
				$legend_labels[$grp_val2] = $data->formatGroupValue($sum_row, 1);
			}
			else {
				$grp_val2 = '';
				$key = $grp_val;
			}
			
			$sidx = '';
			foreach($data->series as $idx => $s) {
				$val = $sum_values[$s['field']];
				$chart_vars['RAW_VALUE'.$sidx] = $val;
				$values[] = $data->formatSeriesValue($sum_row, $idx, false);
				$chart_vars['SERIES_VALUE'.$sidx] = $data->formatSeriesValue($sum_row, $idx);
				$rollovers[$key][$s['field']] = preg_replace('/{([A-Z][A-Z0-9_]+)}/e', 'array_get_default($chart_vars, "\1", "")', $data->rollover);
				if(! $sidx) $sidx = 1;
				$sidx ++;
			}
			
			$points[$grp_val][$grp_val2] = $values;
			if(empty($grp_field2))
				$sums[$grp_val] = array_sum($values);
		}
		else if(!empty($grp_field2) && $d == $grp_field_depth) {
			$sums[$sum_row['value'.$grp_field_depth]] = $data->formatSeriesValue($sum_row, 0, false);
		}
		else if($d == 0) {
			//$grand_totals = array_slice($row, 2*$group_levels + 1);
			$sidx = '';
			foreach($data->series as $idx => $s) {
				$chart_vars['TOTAL'.$sidx] = $data->formatSeriesValue($sum_row, $idx);
				if(! $sidx) $sidx = 1;
				$sidx ++;
			}
		}
	}
	if(!count($sums))
		return false;
	$max = get_max($sums, $data->chart_type);
	$min = min(array(0) + $sums);
	
	if($data->chart_type == 'pie') {
		$caption_default = $chart_strings['LBL_ROLLOVER_WEDGE_DETAILS'];
		
		$fileContents = "     ".open_element('pie', array('defaultAltText' => $caption_default, 'legendStatus' => 'on'))."\n";
		
		$fake_row = array();
		foreach(array_keys($points) as $idx => $key) {
			$series = $data->series[0]['field'];
			$attrs = array();
			$attrs['title'] = $axis_labels[$key];
			$attrs['value'] = $points[$key][''][0];
			$attrs['color'] = generate_graphcolor($key, $idx);
			$fake_row[$series] = $attrs['value'];
			$attrs['labelText'] = $data->formatSeriesValue($fake_row, 0);
			$attrs['url'] = '';
			$attrs['altText'] = $rollovers[$key][$series];
			$fileContents .= "          ".open_element('wedge', $attrs, true)."\n";
		}
	
		$fileContents .= "     </pie>\n";
	}
	else {
		$caption_default = $chart_strings['LBL_ROLLOVER_DETAILS'];
		if($data->chart_type == 'hbar') {
			$elt_range = 'xData';
			$elt_domain = 'yData';
			$subelt = 'bar';
			$range_length = 10; // maybe 13-14 if no legend
			$domain_length = 10; // ??
		}
		else if($data->chart_type == 'vbar') {
			$elt_range = 'yData';
			$elt_domain = 'xData';
			$subelt = 'bar';
			$range_length = 20;
			$domain_length = 10; // higher would be better, but need to pick a better max to get normal values along v-axis
		}
		else { // line
			$caption_default = $chart_strings['LBL_ROLLOVER_NODE_DETAILS'];
			// element names don't seem to matter.. current .swf ignores them
			$elt_range = 'yData';
			$elt_domain = 'xData';
			$subelt = 'node'; // ?
			$range_length = 20;
			$domain_length = 12;
		}
		
		$fileContents = '     '.open_element($elt_range, array('length' => $range_length, 'defaultAltText' => $caption_default))."\n";
		if (!empty($points)) {
			if(isset($fields[$grp_field]['options_keys'])) {
				$order = array_get_default($fields[$grp_field], 'sort_order', 'asc');
				$sorter = new KeySorter($fields[$grp_field]['options_keys'], $order=='desc');
				uksort($points, array(&$sorter, 'cmp'));
			}

			foreach ($points as $key => $vals) {
				$series = $data->series[0]['field'];
				if(empty($grp_field2)) {
					$fake_row[$series] = $vals[''][0];
					$display_val = $data->formatSeriesValue($fake_row, 0);
				}
				else {
					$fake_row[$series] = $sums[$key];
					$display_val = $data->formatSeriesValue($fake_row, 0);
				}
				$attrs = array('title' => $axis_labels[$key], 'endLabel' => $display_val);
				$fileContents .= '          '.open_element('dataRow', $attrs)."\n";
				if(empty($grp_field2)) {
					foreach($data->series as $idx => $series) {
						$attrs = array();
						$attrs['id'] = $series['field'];
						$attrs['totalSize'] = $vals[''][$idx];
						$attrs['altText'] = $rollovers[$key][$series['field']];
						$attrs['url'] = '';
						$fileContents .= '               '.open_element($subelt, $attrs, true)."\n";
					}
				}
				else {
					foreach($vals as $grp_val2 => $val) {
						$attrs = array();
						$attrs['id'] = $grp_val2;
						$attrs['totalSize'] = $val[0];
						$attrs['altText'] = $rollovers["$key\t$grp_val2"][$series];
						$attrs['url'] = '';
						$fileContents .= '               '.open_element($subelt, $attrs, true)."\n";
					}
				}
				$fileContents .= "          </dataRow>\n";
			}
		} else {
			$fileContents .= '          <dataRow title="" endLabel="">'."\n";
			$fileContents .= '               <bar id="" totalSize="0" altText="" url=""/>'."\n";
			$fileContents .= '          </dataRow>'."\n";
		}
		$fileContents .= "     </$elt_range>\n";
		
		$attrs = array('min' => $min, 'max' => $max, 'length' => $domain_length, 'prefix' => $data->series['0']['prefix'], 'suffix' => $data->series[0]['suffix'], 'defaultAltText' => $caption_default);
		$fileContents .= '     '.open_element($elt_domain, $attrs, true)."\n";
		
		$fileContents .= "     <colorLegend status=\"on\">\n";
		if(empty($grp_field2)) {
			foreach($data->series as $idx => $series) {
				$attrs = array('id' => $series['field']);
				$attrs['color'] = generate_graphcolor($series['field'], $idx);
				$attrs['name'] = $series['label'];
				$fileContents .= '          '.open_element('mapping', $attrs, true)."\n";
			}
		}
		else {
			foreach(array_keys($legend_labels) as $idx => $id) {
				$attrs = array('id' => $id);
				$attrs['color'] = generate_graphcolor($id, $idx);
				$attrs['name'] = $legend_labels[$id];
				$fileContents .= '          '.open_element('mapping', $attrs, true)."\n";
			}
		}
		$fileContents .= "     </colorLegend>\n";
	}
	
		
	// currently nothing in 'info' field
	$fileContents .= "     <graphInfo>\n";
	$fileContents .= "          <![CDATA[]]>\n";
	$fileContents .= "     </graphInfo>\n";
	
	$colours = $data->chart_type == 'pie' ? $GLOBALS['pieChartColors'] : $GLOBALS['barChartColors'];
	$fileContents .= '     '.open_element('chartColors', $colours, true)."\n";
	
	$title = preg_replace('/{([A-Z][A-Z0-9_]+)}/e', 'array_get_default($chart_vars, "\1", "")', $data->title);
	$title = open_element('graphData', array('title' => $title, 'subtitle' => ''))."\n";
	$fileContents = $title.$fileContents."</graphData>\n";

	//echo $fileContents;
	save_xml_file($data->getCacheFileName(), $fileContents);
	return true;
}


function generate_report_chart_svg(ChartData &$data, $params=null) {
	require_once('include/SVGCharts/impl/SVGChartData.php');

	global $current_language;
	$chart_strings = return_module_language($current_language, 'Charts');
	$chart_vars = $data->getReplaceVars();
	
	if(! $params) $params = array();

	$w = !empty($params['requestedWidth']) ? $params['requestedWidth'] : 800;
	if (!preg_match('~^[0-9]+$~', $w)) {
		$w = 800;
	}
	$h = $w / 2;

	$svg_data = new SVGChartData;
	$threeD = $data->options['3d'];

	switch ($data->chart_type) {
		case 'pie':
			$caption_default = $chart_strings['LBL_ROLLOVER_WEDGE_DETAILS'];
			require_once('include/SVGCharts/Pie.php');
			$chart = new SVGChartPie($w, $h);
			$chart->set3D($threeD);
			$chart->setOffset($data->options['exploded'] ? 10 : 0);
			$chart->setPercentFormat($data->options['percent']);
			break;
		case 'hbar':
		case 'vbar':
			$caption_default = $chart_strings['LBL_ROLLOVER_DETAILS'];
			require_once('include/SVGCharts/Bar.php');
			$chart = new SVGChartBar($w, $h);
			$chart->set3D($threeD);
			$chart->setStacked($data->options['stacked']);
			if($data->chart_type == 'hbar')
				$chart->setHorizontal();
			break;
		case 'line':
			$caption_default = $chart_strings['LBL_ROLLOVER_NODE_DETAILS'];
			require_once('include/SVGCharts/Line.php');
			$chart = new SVGChartLine($w, $h);
			break;
		case 'area':
			$caption_default = $chart_strings['LBL_ROLLOVER_NODE_DETAILS'];
			require_once('include/SVGCharts/Area.php');
			$chart = new SVGChartArea($w, $h);
			break;
		default:
			return false;
	}

	$dataTotals = array();
	$grandTotal = '';

	$seriesFieldName = $data->series[0]['field'];
	$display_format = $data->series[0]['display_format'];

	$labels = $points = array();
	$dataSeries = array();
	
	$group_levels = count($data->groups);
	$summation = $data->getReportSummations();
	$grp_field = $grp_field2 = null;
	$grp_field_depth = $grp_field2_depth = null;
	foreach($data->groups as $idx => $g) {
		if($idx == 0) {
			$grp_field = $g['field'];
			$grp_field_depth = $g['depth'];
		} else if($idx == 1) {
			$grp_field2 = $g['field'];
			$grp_field2_depth = $g['depth'];
		}
		$max_group_d = $g['depth'];
	}
	
	foreach ($summation->rows as $sum_row) {
		$d = $sum_row['depth'];
		if($d == (empty($grp_field2) ? $grp_field_depth : $grp_field2_depth)) {
			$sum_values = array_slice($sum_row, 2*$group_levels + 1);
			$values = $strValues = array();
			$grp_val = $sum_row['value'.$grp_field_depth];
			$labels[$grp_val] = $data->formatGroupValue($sum_row);
			if(!empty($grp_field2))
				$grp_val2 = $sum_row['value'.$grp_field2_depth];
			else
				$grp_val2 = '';
			$sidx = '';
			foreach($data->series as $idx => $s) {
				$val = $sum_values[$s['field']];
				$chart_vars['RAW_VALUE'.$sidx] = $val;
				$values[] = $val;
				$strValues[] = $data->formatSeriesValue($sum_row, $idx);
				if(! $sidx) $sidx = 1;
				$sidx ++;
			}

			foreach($values as $idx => $n) {
				$dataSeries[$grp_val2]['points'][$grp_val] = array(
					'n' => $n,
					's' => $strValues[$idx],
				);
			}
			$dataSeries[$grp_val2]['n'] = array_sum($values);
			$dataSeries[$grp_val2]['s'] = $data->formatGroupValue($sum_row, 1);
		} if(!empty($grp_field2) && $d == $grp_field_depth) {
			$dataTotals[$sum_row['value1']] = $data->formatSeriesValue($sum_row);
		} else if ($d == 0) {
			$chart_vars['TOTAL'] = $data->formatSeriesValue($sum_row);
		}
	}
	
	$ser_labels = $fake_row = array();
	
	if(empty($grp_field2)) {
		foreach($data->series as $idx => $series) {
			$ser_labels[$grp_field2] = $series['label'];
		}
	}
	foreach ($dataSeries as $k => $ser) {
		if(! empty($grp_field2)) {
			$fake_row['value'.$grp_field2_depth] = $k;
			$ser_labels[$k] = $data->formatGroupValue($fake_row, 1);
		}
		$svg_data->addSeries($k, $ser);
	}
	/*if(isset($fields[$grp_field]['options_keys'])) {
		$order = array_get_default($fields[$grp_field], 'sort_order', 'asc');
		$sorter = new KeySorter($fields[$grp_field]['options_keys'], $order=='desc');
		uksort($labels, array(&$sorter, 'cmp'));
	}*/
	$svg_data->setLabels($labels);
	$svg_data->setSeriesLabels($ser_labels);
	$svg_data->setTotalsDisplay($dataTotals);
	$chart->setData($svg_data);

	$title = preg_replace('/{([A-Z][A-Z0-9_]+)}/e', 'array_get_default($chart_vars, "\1", "")', $data->title);
	$chart->setTitle($title);
	
	$chart_vars['GROUP_VALUE'] = '$group1$';
	$chart_vars['GROUP2_VALUE'] = '$group2$';
	$chart_vars['SERIES_VALUE'] = '$value$';
	$valueRollover = preg_replace('/{([A-Z][A-Z0-9_]+)}/e', 'array_get_default($chart_vars, "\1", "")', $data->rollover);

	$chart->setStatus($caption_default, $valueRollover, $valueRollover);

	$f = fopen($data->getCacheFileName(), 'w');
	fwrite($f, $chart->render());
	fclose($f);

	return $w;
}


function display_report_chart(RowResult &$report_data, $align="center", $show_date=false, $width = null, $refresh = false, $context = null) {
	global $current_language;
	$chart_strings = return_module_language($current_language, 'Charts');
	
	$chart_data = new ChartData();
	$chart_data->loadReportData($report_data);
	if($chart_data->validate()) {
		$xml_fname = $chart_data->getCacheFileName();
		
		$refresh = true; // DEBUG
		
		$cache_file_date = chart_check_cache_date($xml_fname);
		if (!$cache_file_date || $refresh) {
			$fn = 'generate_report_chart_' . ($chart_data->svg ? 'svg' : 'xml');
			$params = array('requestedWidth' => $width);
			$ok = $fn($chart_data, $params);
		}
	}
	else $err = $chart_data->validate_error;
	
	$div_class = $context == 'dashlet' ? '' : 'form-mid opaque';
	
	if(empty($ok)) {
		echo '<div class="' . $div_class . '">';
		echo '<h4 class="dataLabel" style="text-align: center">' . translate('LBL_CHART_RENDER_ERROR', 'app') . " ($err)</h4>";
		echo '</div>';
		return false;
	}
	
	if($chart_data->svg) {
		$width = isset($result) ? $result : 800;
		$height = '';
		$f = @fopen($xml_fname, 'r');
		if ($f) {
			$str = fread($f, 1024);
			fclose($f);
			$m = array();
			if(preg_match('~<svg[^>]*width="(\d+)"~', $str, $m)) {
				$width = $m[1];
			}
			if (preg_match('~<!--SVGCHARTHEIGHT:(\d+)-->~', $str, $m)) {
				$height = 'height="' . $m[1] . '"';
			}
		}
		echo '<div class="' . $div_class . '" style="text-align: '.$align.'; padding: 0px">';
		echo '<object data="' . $xml_fname . '" type="image/svg+xml" width="'.$width .'" ' . $height . ' title="" ></object>';
		echo '</div>';
	} else {
		$chart_type = $chart_data->getSwfName();
		echo '<div class="' . $div_class . '" style="text-align: '.$align.'; padding: 0px">';
		echo create_chart($chart_type, $xml_fname);
		echo '</div>';
	}
		
	$desc = nl2br($chart_data->description);
	if($desc !== '')
		echo "<p align='center'><span class='chartFootnote'>$desc</span></p>";
	
	if($show_date) {
		$date = $report_data->date_entered;
		echo '<span class="chartFootnote"><p align="right"><i>'.$chart_strings['LBL_CREATED_ON'].' '.$date."</i></p></span>";
	}
	return true;
}

/*if(!isset($focus) || $focus->object_name != 'ReportData') {
	if(empty($_REQUEST['record']))
		sugar_die('Missing report data id');
	$focus = new ReportData();
	if(($focus->retrieve($_REQUEST['record'])) == null)
		sugar_die('An error occurred while retrieving the report data.');
	$xml = 'cache/xml/test.xml';
	echo generate_report_chart_xml($focus, $xml);
}*/

function chart_check_cache_date($path)
{
	if (file_exists($path)) return gmdate('Y-m-d H:i', filemtime($path));
	else return '';
}

class KeySorter {
	function KeySorter($keys, $reverse=false) {
		if(! is_array($keys)) $keys = array();
		if($reverse) $keys = array_reverse($keys);
		$this->keymap = array_flip($keys);
	}
	function cmp($a, $b) {
		if($a == $b)
			return 0;
		if(isset($this->keymap[$a])) {
			if(isset($this->keymap[$b]))
				return $this->keymap[$a] < $this->keymap[$b] ? -1
					: ($this->keymap[$a] > $this->keymap[$b] ? 1 : 0);
			return -1;
		}
		if(isset($this->keymap[$b]))
			return 1;
		return strcasecmp($a, $b);
	}
}
