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


require_once('include/charts/BaseChart.php');

class Chart_project_revenue_by_month extends BaseChart {
	var $is_sidebar = false;
	var $modules = array('Project');
	var $default_title = 'LBL_PROJECT_PHASE_FORM_TITLE';
	var $chart_type = 'vBar';
	var $dashletIcon = 'Project';

	var $user_ids;
	var $date_start;
	var $project_phases;
	var $currency_symbol;
	var $first_month;
	var $final_month;
	
	var $chart_date_options = array('date_start');
	var $chart_select_options = array('project_phases', 'user_ids');
	

	function init_chart_options($options) {
		global $app_list_strings, $timedate;
		
		$this->user_ids = array_get_default($options, 'user_ids');
		$this->project_phases = array_get_default($options, 'project_phases');
		$this->requestedWidth = array_get_default($options, 'requestedWidth');
		
		$this->date_start = array_get_default($options, 'date_start');
		if(! $this->date_start)
			$this->date_start = date($timedate->dbDayFormat, time());
		
		$entropy = $this->create_options_hash($options);
		$this->cache_filename = filename_safe_string(implode('_', array(
			$entropy, 'project_revenue', $this->is_sidebar,
		)));
		$svg = $this->svg_charts_enabled();
		$this->cache_filename .= ($svg ? '.svg' : '.xml');
		$GLOBALS['log']->debug("cache file name is: $this->cache_filename");
	}


	function chart_display_options() {
		global $timedate;
		$lbl_date_start = translate('LBL_DATE_START', 'Charts');
		$lbl_phases = translate('LBL_PROJECT_PHASES', 'Charts');
		$lbl_users = translate('LBL_USERS', 'Charts');
		$date_start = $timedate->to_display_date($this->date_start, false);

        return array(
            'labels' => array(
                'date_start' => $lbl_date_start,
                'project_phases' => $lbl_phases,
                'user_ids' => $lbl_users
            ),
            'spec' => array(
                'date_start' => array('type' => 'date'),
                'project_phases' => array('type' => 'multienum', 'options' => 'project_status_dom'),
                'user_ids' => array('type' => 'multienum', 'options' => get_user_array(false))
            ),
            'values' => array(
                'date_start' => $date_start,
                'project_phases' => $this->project_phases,
                'user_ids' => $this->user_ids
            )
        );
	}

	
	function get_footer_text() {
		return translate('LBL_PROJECT_PHASE_FORM_DESC', 'Charts');
	}

	function gen_data()
	{
		global $db, $app_strings, $app_list_strings, $log, $charset, $lang, $barChartColors, $current_user;

		$vars = array(
			'data',
			'projectPhases',
			'month_totals',
		);
		
		$cache_file_name = $this->get_cache_file();
		$user_id = $this->user_ids ? $this->get_selected_values($this->user_ids, get_user_array(false)) : array();
		$projectPhases = $this->get_selected_values($this->project_phases, 'project_status_dom');
		$date_start = $this->date_start;
		
		$this->first_month = substr($date_start, 0, 7);
		$year_start = substr($this->first_month, 0, 4);
		$month_start = substr($this->first_month, 5, 7);
		
		$year_end = $year_start;
		$month_end = $month_start + 6;
		if($month_end > 12) {
			$month_end -= 12;
			$year_end += 1;
		}
		$this->final_month = sprintf('%04d-%02d', $year_end, $month_end);
		$date_start = sprintf('%04d-%02d-01', $year_start, $month_start);
		$date_end = date('Y-m-t', strtotime("$year_end-$month_end-01"));
		
		
		$lq = new ListQuery('Project');
		$lq->primary_added = true;
		$lq->addLink('project_financials', null, true, 'nested');
		$lq->setTableAlias('fin', 'project_financials');
		$lq->addField('project_financials.expected_revenue', 'expected_revenue');
		$lq->addField('project_phase');
		$lq->addField('currency_id');
		$lq->addFieldLiteral('m', "date_format(fin.period,'%Y-%m')");
		if(count($projectPhases))
			$lq->addSimpleFilter('project_phase', array_keys($projectPhases));
		if(count($user_id))
			$lq->addSimpleFilter('assigned_user_id', array_keys($user_id));
		$lq->addFilterClause(array(
			'field' => 'project_financials.period',
			'format' => 'date',
			'operator' => 'between_dates',
			'value' => $date_start,
			'end' => $date_end,
		));
		$lq->addFilterClause(
			"extract(year_month from fin.period) BETWEEN extract(year_month from project.date_starting) AND extract(year_month from project.date_ending)"
		);
		$lq->addAclFilters('report');
		$lq->setOrderBy('m');
		$query = $lq->getSql();
		
		
		$result = $db->query($query, true, "Error performing project query: ");
		
		// must manually sum expected revenue because value depends on the project currency
		$data = array();
		$currencies = array();
		
		$y = $year_start;
		$m = $month_start;
		for($i = 0; $i < 6; $i++) {
			$data[$y.'-'.$m] = array();
			$m ++;
			if($m > 12) {
				$m -= 12;
				$y ++;
			}
			if($m < 10)
				$m = '0'.$m;
		}
		
		while($row = $db->fetchByAssoc($result)) {
			$month = $row['m'];
			if(!isset($data[$month]))
				$data[$month] = array();
				
			$phase = $row['project_phase'];
			if(! isset($data[$month][$phase]))
				$data[$month][$phase] = array(
					'proj_count' => 0,
					'revenue' => 0.0,
				);
			
			$currency_id = $row['currency_id'];
			if(isset($currencies[$currency_id]))
				$currency = $currencies[$currency_id];
			else {
				$currency = new Currency();
				$currency->retrieve(empty($currency_id) ? -99 : $currency_id);
				$currencies[$currency_id] = $currency;
			}
			$proj_amount = $currency->convertToDollar($row['expected_revenue']);
			
			$data[$month][$phase]['proj_count'] += 1;
			$data[$month][$phase]['revenue'] += $proj_amount;
		}
		
		// load user's preferred currency
		$user_currency = new Currency();
		if($current_user->getPreference('currency'))
			$user_currency->retrieve($current_user->getPreference('currency'));
		else
			$user_currency->retrieve(-99); // default currency
		$this->currency_symbol = $user_currency->symbol;
		
		// sum totals by month by phase into totals by month
		$month_totals = array();
		foreach($data as $month => $by_phase) {
			$month_totals[$month] = 0;
			foreach($by_phase as $phase => $row_data) {
				$adj_revenue = $user_currency->convertFromDollar($row_data['revenue']);
				$adj_revenue /= 1000;
				$data[$month][$phase]['revenue'] = round($adj_revenue, 2);
				$month_totals[$month] += $adj_revenue;
			}
		}
		foreach($month_totals as $month => $value)
			$month_totals[$month] = round($value, 2);
		
		//$projectPhases = $app_list_strings['project_status_dom'];
		arsort($projectPhases);
		
		ksort($data);

		return compact($vars);

	}	

	function  gen_xml() {
		global $app_strings, $app_list_strings, $log, $charset, $lang, $barChartColors, $current_user;
		
		$cache_file_name = $this->get_cache_file();
		if( !file_exists($cache_file_name) || $this->refresh) {

			extract($this->gen_data());

			$fileContents = '     <xData length="20">'."\n";
			if (!empty($data)) {
				foreach ($data as $month => $by_phase){
					$fileContents .= '          <dataRow title="'.$month.'" endLabel="'.currency_format_number($month_totals[$month], array('currency_symbol' => false)).'">'."\n";
					foreach($projectPhases as $phase => $phase_name)
						if(isset($data[$month][$phase]))
							$fileContents .= '               <bar id="'.$phase.'" totalSize="'.$data[$month][$phase]['revenue'].'" '.
								'altText="'.$month.': '.$data[$month][$phase]['proj_count'].' '.translate('LBL_PROJECTS_WORTH', 'Charts').' '.$data[$month][$phase]['revenue'].translate('LBL_OPP_THOUSANDS', 'Charts').' '.translate('LBL_PROJECTS_PHASE', 'Charts').' '.$phase_name.'" '.
								'url="index.php?module=Project&amp;action=index&amp;active_on_date='.$month.'&amp;project_phase='.urlencode($phase).'&amp;query=true&searchFormTab=advanced_search"/>'."\n";
					$fileContents .= '          </dataRow>'."\n";
				}
			} else {
				$fileContents .= '          <dataRow title="" endLabel="">'."\n";
				$fileContents .= '               <bar id="" totalSize="0" altText="" url=""/>'."\n";
				$fileContents .= '          </dataRow>'."\n";
			}
			$fileContents .= '     </xData>'."\n";
			$max = $this->get_useful_max($month_totals);
			$total = round(array_sum($month_totals));
			$fileContents .= '     <yData min="0" max="'.$max.'" length="10" prefix="'.$this->currency_symbol.'" suffix="" defaultAltText="'.translate('LBL_ROLLOVER_DETAILS', 'Charts').'"/>'."\n";
			
			asort($projectPhases);
			$fileContents .= '     <colorLegend status="on">'."\n";
			$i=0;
			foreach($projectPhases as $phase => $phase_name) {
				$color = $this->generate_graphcolor($phase, $i);
				$fileContents .= '          <mapping id="'.$phase.'" name="'.$phase_name.'" color="'.$color.'"/>'."\n";
				$i++;
			}
			$fileContents .= '     </colorLegend>'."\n";
			$fileContents .= '     <graphInfo>'."\n";
			$fileContents .= '          <![CDATA['.translate('LBL_DATE_RANGE', 'Charts')." ".$this->first_month." ".translate('LBL_DATE_RANGE_TO', 'Charts')." ".$this->final_month."<br/>".translate('LBL_PROJECTS_SIZE', 'Charts').' '.$this->currency_symbol.'1'.translate('LBL_OPP_THOUSANDS', 'Charts').']]>'."\n";
			$fileContents .= '     </graphInfo>'."\n";
			$fileContents .= '     <chartColors ';
			foreach ($barChartColors as $key => $value) {
				$fileContents .= ' '.$key.'='.'"'.$value.'" ';
			}
			$fileContents .= ' />'."\n";
			$fileContents .= '</graphData>'."\n";
			$total = currency_format_number($total);
			$title = '<graphData title="'.translate('LBL_TOTAL_PIPELINE', 'Charts').$total.$app_strings['LBL_THOUSANDS_SYMBOL'].'">'."\n";
			$fileContents = $title.$fileContents;

			//echo $fileContents;
			$this->save_xml_file($cache_file_name, $fileContents);
		}
		return $cache_file_name;
	}
	
	function  gen_svg() {
		global $app_strings, $app_list_strings, $log, $charset, $lang, $barChartColors, $current_user;
		
		$cache_file_name = $this->get_cache_file();
		if( !file_exists($cache_file_name) || $this->refresh) {

			extract($this->gen_data());

			require_once 'include/SVGCharts/Bar.php';
			require_once('include/SVGCharts/impl/SVGChartData.php');
			$chartData = new SVGChartData;
			$w = $this->requestedWidth ? $this->requestedWidth : 800;
			$chart = new SVGChartBar($w, $w/2);
			$series = array();
			$labels = array();
			$seriesVars = array();
			
			$total = round(array_sum($month_totals));

			$title = translate('LBL_TOTAL_PIPELINE', 'Charts'). $total . $app_strings['LBL_THOUSANDS_SYMBOL'];

			if (!empty($data)) {
				foreach ($data as $month => $by_phase){
					$labels[$month] = $month;
					foreach ($by_phase as $phase => $numbers) {
						$phase_name = $projectPhases[$phase];
						$series[$phase_name]['points'][$month] = array(
							'c' => $numbers['proj_count'],
							'n' => $numbers['revenue'],
						);
						$seriesVars[$month][$phase_name] = array(
							'phase' => $phase,
						);
					}
				}
			}

			$chart->setTitle($title);
			$chart->setData($chartData);
			foreach ($series as $k => $s) {
				$chartData->addSeries($k, $s);
			}
			$chartData->setLabels($labels);
			$chartData->setSeriesVars($seriesVars);

			$url = 
				  AppConfig::site_url()
				  . '/index.php?module=Project&action=index'
				  . '&active_on_date=$series$&project_phase=$phase$'
				  . '&query=true&layout=Standard&view_closed=1'
				  ;
			$chart->setLinkTemplate($url);
			$chart->setStacked(true);

			$valueRollover = '$group1$: $count$ '.translate('LBL_PROJECTS_WORTH', 'Charts').' $value$ ' . translate('LBL_OPP_THOUSANDS', 'Charts').' '.translate('LBL_PROJECTS_PHASE', 'Charts').' $group2$';
			$chart->setStatus(translate('LBL_ROLLOVER_DETAILS', 'Charts'), $valueRollover, $valueRollover);

			$fileContents = $chart->render();

			$this->save_xml_file($cache_file_name, $fileContents);
		}
		return $cache_file_name;
	}
	
}
?>
