<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/*********************************************************************************
 * The contents of this file are subject to the SugarCRM Public License Version
 * 1.1.3 ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied.  See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * All copies of the Covered Code must include on each user interface screen:
 *    (i) the "Powered by SugarCRM" logo and
 *    (ii) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.  See full license for
 * requirements.
 *
 * The Original Code is: SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) 2004-2006 SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 ********************************************************************************/
/*********************************************************************************

 * Description:  returns HTML for client-side image map.
 ********************************************************************************/

require_once('include/charts/BaseChart.php');

class Chart_outcome_by_month extends BaseChart
{
	var $is_sidebar = false;
	var $modules = array('Opportunities');
	var $default_title = 'LBL_OUTCOME_BY_MONTH_TITLE';
	var $chart_type = 'vBar';
	var $dashletIcon = 'Opportunities';

	var $user_ids;
	var $date_start;
	var $date_end;
	var $year;
	
	var $chart_text_options = array('year');
	var $chart_date_options = array('date_start', 'date_end');
	var $chart_select_options = array('user_ids');

	function init_chart_options($options) {
		global $current_user, $timedate;
		
		if($this->is_sidebar)
			$this->user_ids = array($current_user->id);
		else
			$this->user_ids = array_get_default($options, 'user_ids');
		
		$this->year = array_get_default($options, 'year', date('Y'));
		$this->date_start = array_get_default($options, 'date_start');
		if(! $this->date_start)
			$this->date_start = "{$this->year}-01-01";

		$this->date_end = array_get_default($options, 'date_end');
		if(! $this->date_end)
			$this->date_end = "{$this->year}-12-31";

		$entropy = $this->create_options_hash($options);
		$this->cache_filename = filename_safe_string(implode('_', array(
			$entropy, 'outcome_by_month', $this->is_sidebar,
		)));
		$svg = $this->svg_charts_enabled();
		$this->cache_filename .= ($svg ? '.svg' : '.xml');
		$GLOBALS['log']->debug("cache file name is: $this->cache_filename");
	}


	function get_footer_text() {
		return translate('LBL_MONTH_BY_OUTCOME_DESC', 'Charts');
	}
	
	
	function chart_display_options() {
        global $app_strings;
        $year_fmt = $app_strings['NTC_YEAR_FORMAT'];
        $lbl_year = translate('LBL_YEAR', 'Charts');
		$lbl_users = translate('LBL_USERS', 'Charts');

        return array(
            'labels' => array(
                'year' => $lbl_year .' '. $year_fmt .'',
                'user_ids' => $lbl_users
            ),
            'spec' => array(
                'year' => array('type' => 'varchar'),
                'user_ids' => array('type' => 'multienum', 'options' => get_user_array(false))
            ),
            'values' => array(
                'year' => $this->year,
                'user_ids' => $this->user_ids,
            )
        );
	}


	function gen_data()
	{
		global $db, $app_strings, $app_list_strings, $barChartColors, $current_user, $timedate;

		$vars = array(
			'monthArr',
			'symbol',
			'salesStages',
			'dateStartDisplay',
			'dateEndDisplay',
			'total',
			'months',
		);

		$cache_file_name = $this->get_cache_file();		
		$user_id = $this->user_ids ? $this->get_selected_values($this->user_ids, get_user_array(false)) : array();
		$date_start = $this->date_start;
		$date_end = $this->date_end;


		$lq = new ListQuery('Opportunity');
		$lq->primary_added = true;
		$lq->addField('sales_stage');
		$lq->addFieldLiteral('m', "date_format(opportunities.date_closed,'%Y-%m')");
		$lq->addFieldLiteral('total', 'sum(amount_usdollar/1000)', 'double');
		$lq->addFieldLiteral('opp_count', 'count(*)', 'int');
		if(count($user_id))
			$lq->addSimpleFilter('assigned_user_id', array_keys($user_id));
		$lq->addFilterClause(array(
			'field' => 'date_closed',
			'operator' => 'between',
			'value' => $date_start,
			'end' => $date_end,
		));
		$lq->addAclFilters('report');
		$lq->setGroupBy('sales_stage, m');
		$lq->setOrderBy('m');
		$query = $lq->getSql();


		//Now do the db queries
		//query for opportunity data that matches $datay and $user
		$result = $db->query($query)
		or sugar_die("Error selecting sugarbean: ".mysql_error());
		//build pipeline by sales stage data
		$total = 0;
		$div = 1;
		$symbol = AppConfig::setting('locale.base_currency.symbol');
		$other = translate('LBL_LEAD_SOURCE_OTHER', 'Charts');
		$rowTotalArr = array();
		$rowTotalArr[] = 0;
		global $current_user;
		$salesStages = array("Closed Lost"=>$app_list_strings['sales_stage_dom']["Closed Lost"],"Closed Won"=>$app_list_strings['sales_stage_dom']["Closed Won"],"Other"=>$other);
		if($current_user->getPreference('currency') ){
			require_once('modules/Currencies/Currency.php');
			$currency = new Currency();
			$currency->retrieve($current_user->getPreference('currency'));
			$div = $currency->conversion_rate;
			$symbol = $currency->symbol;
		}
		$months = array();
		$monthArr = array();
		while($row = $db->fetchByAssoc($result, -1, false))
		{
			if($row['total']*$div<=100){
				$sum = round($row['total']*$div, 2);
			} else {
				$sum = round($row['total']*$div);
			}
			if(strpos($row['sales_stage'], 'Closed Won') !== false){
				$salesStage = "Closed Won";
				$salesStageT = $app_list_strings['sales_stage_dom'][$row['sales_stage']];
			} elseif(strpos($row['sales_stage'], 'Closed Lost') !== false) {
				$salesStage = "Closed Lost";
				$salesStageT = $app_list_strings['sales_stage_dom'][$row['sales_stage']];
			} else {
				$salesStage = "Other";
				$salesStageT = $other;
			}

			$months[$row['m']] = $row['m'];
			if(!isset($monthArr[$row['m']]['row_total'])) {$monthArr[$row['m']]['row_total']=0;}
			$monthArr[$row['m']][$salesStage]['opp_count'][] = $row['opp_count'];
			$monthArr[$row['m']][$salesStage]['total'][] = $sum;
			$monthArr[$row['m']]['outcome'][$salesStage]=$salesStageT;
			$monthArr[$row['m']]['row_total'] += $sum;

			$total += $sum;
		}

		return compact($vars);

	}

	/**
	* Creates opportunity pipeline image as a VERTICAL accumlated bar graph for multiple users.
	* param $datax- the month data to display in the x-axis
	* Portions created by SugarCRM are Copyright (C) SugarCRM, Inc..
	* All Rights Reserved..
	* Contributor(s): ______________________________________..
	*/
	function gen_xml() {
		global $app_strings, $app_list_strings, $barChartColors, $current_user, $timedate;

		$cache_file_name = $this->get_cache_file();		
		if (!file_exists($cache_file_name) || $this->refresh) {
			extract($this->gen_data());
			require_once('modules/Currencies/Currency.php');
			$kDelim = $current_user->getPreference('num_grp_sep');	
			$fileContents = '     <xData length="20">'."\n";
			if (!empty($months)) {
				foreach ($months as $month){
					$rowTotalArr[]=$monthArr[$month]['row_total'];
					if($monthArr[$month]['row_total']>100)
					{
						$monthArr[$month]['row_total']=round($monthArr[$month]['row_total']);
					}
					$fileContents .= '          <dataRow title="'.$month.'" endLabel="'.currency_format_number($monthArr[$month]['row_total'], array('currency_symbol' => false)).'">'."\n";
					arsort($salesStages);
					foreach ($salesStages as $outcome=>$outcome_translation){
						if(isset($monthArr[$month][$outcome])) {
						$fileContents .= '               <bar id="'.$outcome.'" totalSize="'.array_sum($monthArr[$month][$outcome]['total']).'" altText="'.$month.': '.format_number(array_sum($monthArr[$month][$outcome]['opp_count']), 0, 0).' '.translate('LBL_OPPS_WORTH', 'Charts').' '.currency_format_number(array_sum($monthArr[$month][$outcome]['total']),array('currency_symbol' => true)).translate('LBL_OPP_THOUSANDS', 'Charts').' '.translate('LBL_OPPS_OUTCOME', 'Charts').' '.$outcome_translation.'" url="index.php?module=Opportunities&action=index&date_closed='.$month.'&sales_stage='.urlencode($outcome).'&query=true&searchFormTab=advanced_search"/>'."\n";

						}
					}
					$fileContents .= '          </dataRow>'."\n";
				}
			} else {
				$fileContents .= '          <dataRow title="" endLabel="">'."\n";
				$fileContents .= '               <bar id="" totalSize="0" altText="" url=""/>'."\n";
				$fileContents .= '          </dataRow>'."\n";
				$rowTotalArr[] = 1000;
			}
			$fileContents .= '     </xData>'."\n";
			$max = $this->get_useful_max($rowTotalArr);
			$fileContents .= '     <yData min="0" max="'.$max.'" length="10" prefix="'.$symbol.'" suffix="" kDelim="'.$kDelim.'" defaultAltText="'.translate('LBL_ROLLOVER_DETAILS', 'Charts').'"/>'."\n";
			$fileContents .= '     <colorLegend status="on">'."\n";
			$i=0;
			asort($salesStages);
			foreach ($salesStages as $outcome=>$outcome_translation) {
				$color = $this->generate_graphcolor($outcome,$i);
				$fileContents .= '          <mapping id="'.$outcome.'" name="'.$outcome_translation.'" color="'.$color.'"/>'."\n";
				$i++;
			}
			$fileContents .= '     </colorLegend>'."\n";
			$fileContents .= '     <graphInfo>'."\n";
			$fileContents .= '          <![CDATA['.translate('LBL_DATE_RANGE', 'Charts')." ".$dateStartDisplay." ".translate('LBL_DATE_RANGE_TO', 'Charts')." ".$dateEndDisplay."<br/>".translate('LBL_OPP_SIZE', 'Charts').' '.$symbol.'1'.translate('LBL_OPP_THOUSANDS', 'Charts').']]>'."\n";
			$fileContents .= '     </graphInfo>'."\n";
			$fileContents .= '     <chartColors ';
			foreach ($barChartColors as $key => $value) {
				$fileContents .= ' '.$key.'='.'"'.$value.'" ';
			}
			$fileContents .= ' />'."\n";
			$fileContents .= '</graphData>'."\n";
			$total = round($total, 2);
			$title = '<graphData title="'.translate('LBL_TOTAL_PIPELINE', 'Charts').currency_format_number($total, array('currency_symbol' => true)).$app_strings['LBL_THOUSANDS_SYMBOL'].'">'."\n";
			$fileContents = $title.$fileContents;

			//echo $fileContents;
			$this->save_xml_file($cache_file_name, $fileContents);
		}
		return $cache_file_name;
	}
	
	function gen_svg()
	{
		global $app_strings, $app_list_strings, $barChartColors, $current_user, $timedate;

		$cache_file_name = $this->get_cache_file();		
		if (!file_exists($cache_file_name) || $this->refresh) {
			extract($this->gen_data());
			require_once 'include/SVGCharts/Bar.php';
			require_once('include/SVGCharts/impl/SVGChartData.php');
			$data = new SVGChartData;
			$size = $this->get_chart_info();
			$w = $size[1];
			$chart = new SVGChartBar($w, $w/2);
			$chart->setStacked(true);

			$series = array();
			$seriesLabels = array();
			$labels = array();
			$seriesVars = array();
			require_once('modules/Currencies/Currency.php');
			$kDelim = $current_user->getPreference('num_grp_sep');

			foreach ($months as $month){
				$labels[$month] = $month;
				$rowTotalArr[]=$monthArr[$month]['row_total'];
				if($monthArr[$month]['row_total']>100)
				{
					$monthArr[$month]['row_total']=round($monthArr[$month]['row_total']);
				}
				//$fileContents .= '          <dataRow title="'.$month.'" endLabel="'.currency_format_number($monthArr[$month]['row_total'], array('currency_symbol' => false)).'">'."\n";
				arsort($salesStages);
				foreach ($salesStages as $outcome=>$outcome_translation){
					if(isset($monthArr[$month][$outcome])) {
						$seriesLabels[$outcome] = $outcome_translation;
						$series[$outcome]['points'][$month] = array(
							'n' => array_sum($monthArr[$month][$outcome]['total']),
							'c' => format_number(array_sum($monthArr[$month][$outcome]['opp_count']), 0, 0),
							's' => currency_format_number(array_sum($monthArr[$month][$outcome]['total']),array('currency_symbol' => true, 'symbol_space' => '')),
						);
						$seriesVars[$month][$outcome]['sales_stage'] = $outcome == 'Other' ? '' : $outcome;
						//$fileContents .= '               <bar id="'.$outcome.'" totalSize="'.array_sum($monthArr[$month][$outcome]['total']).'" altText="'.$month.': '.format_number(array_sum($monthArr[$month][$outcome]['opp_count']), 0, 0).' '.translate('LBL_OPPS_WORTH', 'Charts').' '.currency_format_number(array_sum($monthArr[$month][$outcome]['total']),array('currency_symbol' => true)).translate('LBL_OPP_THOUSANDS', 'Charts').' '.translate('LBL_OPPS_OUTCOME', 'Charts').' '.$outcome_translation.'" url="index.php?module=Opportunities&action=index&date_closed='.$month.'&sales_stage='.urlencode($outcome).'&query=true&searchFormTab=advanced_search"/>'."\n";
					}
				}
				//$fileContents .= '          </dataRow>'."\n";
			}

			$valueRollover = '$group1$: $count$ '.translate('LBL_OPPS_WORTH', 'Charts').' $value$'.translate('LBL_OPP_THOUSANDS', 'Charts').' '.translate('LBL_OPPS_OUTCOME', 'Charts').' $group2$';
			$chart->setStatus(translate('LBL_ROLLOVER_DETAILS', 'Charts'), $valueRollover, $valueRollover);

			$title = translate('LBL_TOTAL_PIPELINE', 'Charts').currency_format_number($total, array('currency_symbol' => true, 'symbol_space'=>'')).$app_strings['LBL_THOUSANDS_SYMBOL'];
			$url =
				  AppConfig::site_url()
				. '/index.php?module=Opportunities&action=index'
				. '&date_closed-operator=month&date_closed=$series$&sales_stage=$sales_stage$'
				. '&query=true&layout=Standard&view_closed=1'
				;
				
			foreach ($series as $k => $s) {
				$data->addSeries($k, $s);
			}
			$data->setLabels($labels);
			$data->setSeriesLabels($seriesLabels);
			$data->setSeriesVars($seriesVars);

			$chart->setData($data);
			$chart->setTitle($title);
			$chart->setLinkTemplate($url);
			$fileContents = $chart->render();
			$this->save_xml_file($cache_file_name, $fileContents);

		}
		return $cache_file_name;
	}
}

?>
