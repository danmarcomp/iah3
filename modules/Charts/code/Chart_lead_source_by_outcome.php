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
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/

require_once('include/charts/BaseChart.php');

class Chart_lead_source_by_outcome extends BaseChart
{
	var $is_sidebar = false;
	var $modules = array('Opportunities');
	var $default_title = 'LBL_LEAD_SOURCE_BY_OUTCOME';
	var $chart_type = 'hBar';
	var $dashletIcon = 'Leads';

	var $user_ids;
	var $lead_sources;
	
	var $chart_select_options = array('lead_sources', 'user_ids');
	

	function init_chart_options($options) {
		global $app_list_strings;
		
		$this->user_ids = array_get_default($options, 'user_ids');
		$this->lead_sources = array_get_default($options, 'lead_sources');
		
		$entropy = $this->create_options_hash($options);
		$this->cache_filename = filename_safe_string(implode('_', array(
			$entropy, 'lead_source_by_outcome',
			date('Y-m-d'), $this->is_sidebar,
		)));
		$svg = $this->svg_charts_enabled();
		$this->cache_filename .= ($svg ? '.svg' : '.xml');
		$GLOBALS['log']->debug("cache file name is: $this->cache_filename");
	}


	function chart_display_options() {
		$lbl_sources = translate('LBL_LEAD_SOURCES', 'Charts');
		$lbl_users = translate('LBL_USERS', 'Charts');

        return array(
            'labels' => array(
                'lead_sources' => $lbl_sources,
                'user_ids' => $lbl_users
            ),
            'spec' => array(
                'lead_sources' => array('type' => 'multienum', 'options' => 'lead_source_dom'),
                'user_ids' => array('type' => 'multienum', 'options' => get_user_array(false))
            ),
            'values' => array(
                'user_ids' => $this->user_ids,
                'lead_sources' => $this->lead_sources
            )
        );
    }


	function get_footer_text() {
		return translate('LBL_LEAD_SOURCE_BY_OUTCOME_DESC', 'Charts');
	}


	function gen_data()
	{
		global $db, $app_strings, $app_list_strings, $barChartColors, $current_user;

		$vars = array(
			'datay',
			'salesStages',
			'leadSourceArr',
			'total',
			'rowTotalArr',
			'symbol',
			'kDelim',
		);

		$cache_file_name = $this->get_cache_file();		

		require_once('modules/Currencies/Currency.php');
		$kDelim = $current_user->getPreference('num_grp_sep');
		
		$datay = $this->get_selected_values($this->lead_sources, 'lead_source_dom');
		$user_id = $this->user_ids;
		
		
		$lq = new ListQuery('Opportunity');
		$lq->primary_added = true;
		$lq->addField('lead_source');
		$lq->addField('sales_stage');
		$lq->addFieldLiteral('total', 'sum(amount_usdollar/1000)', 'double');
		$lq->addFieldLiteral('opp_count', 'count(*)', 'int');
		if(count($datay))
			$lq->addSimpleFilter('lead_source', array_keys($datay));
		if(count($user_id))
			$lq->addSimpleFilter('assigned_user_id', array_keys($user_id));
		$lq->addAclFilters('report');
		$lq->setGroupBy('sales_stage, lead_source');
		$lq->setOrderBy('sales_stage, lead_source');
		$query = $lq->getSql();
		
		
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
		$leadSourceArr = array();
		while($row = $db->fetchByAssoc($result, -1, false))
		{
			if($row['total']*$div<=100){
				$sum = round($row['total']*$div, 2);
			} else {
				$sum = round($row['total']*$div);
			}
			if($row['lead_source'] == ''){
				$row['lead_source'] = translate('NTC_NO_LEGENDS', 'Charts');
			}
			if(strpos($row['sales_stage'], 'Closed Won') !== false){
				$salesStage = $salesStages["Closed Won"];
				$salesStageT = $app_list_strings['sales_stage_dom'][$row['sales_stage']];
			} elseif(strpos($row['sales_stage'], 'Closed Lost') !== false) {
				$salesStage = $salesStages["Closed Lost"];
				$salesStageT = $app_list_strings['sales_stage_dom'][$row['sales_stage']];
			} else {
				$salesStage = $salesStages["Other"];
				$salesStageT = $other;
			}
			if(!isset($leadSourceArr[$row['lead_source']]['row_total'])) {$leadSourceArr[$row['lead_source']]['row_total']=0;}
			$leadSourceArr[$row['lead_source']][$salesStage]['opp_count'][] = $row['opp_count'];
			$leadSourceArr[$row['lead_source']][$salesStage]['total'][] = $sum;
			$leadSourceArr[$row['lead_source']]['outcome'][$salesStage]=$salesStageT;
			$leadSourceArr[$row['lead_source']]['row_total'] += $sum;

			$total += $sum;
		}
		return compact($vars);
	}


	/**
	* Creates lead_source_by_outcome pipeline image as a HORIZONAL accumlated bar graph for multiple users.
	* Portions created by SugarCRM are Copyright (C) SugarCRM, Inc..
	* All Rights Reserved..
	* Contributor(s): ______________________________________..
	*/
	function gen_xml() {
		global $app_strings, $app_list_strings, $barChartColors, $current_user;
	
		$cache_file_name = $this->get_cache_file();		
		if (!file_exists($cache_file_name) || $this->refresh) {
			extract($this->gen_data());
			$fileContents = '     <yData defaultAltText="'.translate('LBL_ROLLOVER_DETAILS', 'Charts').'">'."\n";
			foreach ($datay as $key=>$translation) {
				if ($key == '') {
					$key = translate('NTC_NO_LEGENDS', 'Charts');
					$translation = translate('NTC_NO_LEGENDS', 'Charts');
				}
				if(!isset($leadSourceArr[$key])){
					$leadSourceArr[$key] = $key;
				}
				if(isset($leadSourceArr[$key]['row_total'])){$rowTotalArr[]=$leadSourceArr[$key]['row_total'];}
				if(isset($leadSourceArr[$key]['row_total']) && $leadSourceArr[$key]['row_total']>100){
					$leadSourceArr[$key]['row_total'] = round($leadSourceArr[$key]['row_total']);
				}
				$fileContents .= '          <dataRow title="'.$translation.'" endLabel="'.currency_format_number($leadSourceArr[$key]['row_total'], array('currency_symbol' => false)) . '">'."\n";
				if(is_array($leadSourceArr[$key]['outcome'])){
					foreach ($leadSourceArr[$key]['outcome'] as $outcome=>$outcome_translation){
						$fileContents .= '               <bar id="'.$outcome.'" totalSize="'.array_sum($leadSourceArr[$key][$outcome]['total']).'" altText="'.format_number(array_sum($leadSourceArr[$key][$outcome]['opp_count']),0,0).' '.translate('LBL_OPPS_WORTH', 'Charts').' '.currency_format_number(array_sum($leadSourceArr[$key][$outcome]['total']),array('currency_symbol' => true)).translate('LBL_OPP_THOUSANDS', 'Charts').' '.translate('LBL_OPPS_OUTCOME', 'Charts').' '.$outcome_translation.'" url="index.php?module=Opportunities&action=index&lead_source='.$key.'&sales_stage='.urlencode($outcome).'&query=true&searchFormTab=advanced_search"/>'."\n";
					}
				}
				$fileContents .= '          </dataRow>'."\n";
			}
			$fileContents .= '     </yData>'."\n";
			$max = $this->get_useful_max($rowTotalArr);
			$fileContents .= '     <xData min="0" max="'.$max.'" length="10" kDelim="'.$kDelim.'" prefix="'.$symbol.'" suffix=""/>' . "\n";
			$fileContents .= '     <colorLegend status="on">'."\n";
			$i=0;

				foreach ($salesStages as $outcome=>$outcome_translation) {
					$color = $this->generate_graphcolor($outcome,$i);
					$fileContents .= '          <mapping id="'.$outcome.'" name="'.$outcome_translation.'" color="'.$color.'"/>'."\n";
					$i++;
				}
			$fileContents .= '     </colorLegend>'."\n";
			$fileContents .= '     <graphInfo>'."\n";
			$fileContents .= '          <![CDATA['.translate('LBL_OPP_SIZE', 'Charts').' '.$symbol.'1'.translate('LBL_OPP_THOUSANDS', 'Charts').']]>'."\n";
			$fileContents .= '     </graphInfo>'."\n";
			$fileContents .= '     <chartColors ';
			foreach ($barChartColors as $key => $value) {
				$fileContents .= ' '.$key.'='.'"'.$value.'" ';
			}
			$fileContents .= ' />'."\n";
			$fileContents .= '</graphData>'."\n";
			$total = round($total, 2);
			$title = '<graphData title="'.translate('LBL_ALL_OPPORTUNITIES', 'Charts').currency_format_number($total, array('currency_symbol' => true)).$app_strings['LBL_THOUSANDS_SYMBOL'].'">'."\n";
			$fileContents = $title.$fileContents;

			$this->save_xml_file($cache_file_name, $fileContents);
		}
		return $cache_file_name;
	}
	
	function gen_svg()
	{
		global $app_strings, $app_list_strings, $barChartColors, $current_user;
	
		$cache_file_name = $this->get_cache_file();		
		if (!file_exists($cache_file_name) || $this->refresh) {
			extract($this->gen_data());
			require_once 'include/SVGCharts/Bar.php';
			require_once('include/SVGCharts/impl/SVGChartData.php');
			$data = new SVGChartData;
			$size = $this->get_chart_info();
			$w = $size[1];
			$chart = new SVGChartBar($w, $w/2);
			$chart->setHorizontal();
			$chart->setStacked(true);

			$series = array();
			$labels = array();
			$seriesVars = array();

			foreach ($datay as $key=>$translation) {
				if ($key == '') {
					$key = translate('NTC_NO_LEGENDS', 'Charts');
					$translation = translate('NTC_NO_LEGENDS', 'Charts');
				}
				$labels[$key] = $translation;

				if(!isset($leadSourceArr[$key])){
					$leadSourceArr[$key] = $key;
				}
				if(isset($leadSourceArr[$key]['row_total'])){$rowTotalArr[]=$leadSourceArr[$key]['row_total'];}
				if(isset($leadSourceArr[$key]['row_total']) && $leadSourceArr[$key]['row_total']>100){
						$leadSourceArr[$key]['row_total'] = round($leadSourceArr[$key]['row_total']);
				}
				if(is_array($leadSourceArr[$key]['outcome'])){
					foreach ($leadSourceArr[$key]['outcome'] as $outcome=>$outcome_translation){
						$seriesVars[$key][$outcome] = array(
							'lead_source' => $key,
							'outcome' => $outcome,
						);
						$series[$outcome]['points'][$key] = array(
							'n' => array_sum($leadSourceArr[$key][$outcome]['total']),
							'c' => format_number(array_sum($leadSourceArr[$key][$outcome]['opp_count']),0,0),
							's' => currency_format_number(array_sum($leadSourceArr[$key][$outcome]['total']),array('currency_symbol' => true, 'symbol_space'=>'')),
						);
					}
				}
			}

			foreach ($series as $k => $s) {
				$data->addSeries($k, $s);
			}
			$data->setLabels($labels);
			$data->setSeriesVars($seriesVars);

			$valueRollover = '$count$ '.translate('LBL_OPPS_WORTH', 'Charts').' $value$' . translate('LBL_OPP_THOUSANDS', 'Charts').' '.translate('LBL_OPPS_OUTCOME', 'Charts').' $group2$';
			$chart->setStatus(translate('LBL_ROLLOVER_DETAILS', 'Charts'), $valueRollover, $valueRollover);

			$url = 
				  AppConfig::site_url()
				. '/index.php?module=Opportunities&action=index'
				. '&lead_source=$lead_source$'
				. '&sales_stage=$outcome$'
				. '&view_closed=1'
				. '&query=true&layout=Standard'
				;

			$chart->setLinkTemplate($url);
			$chart->setData($data);
			$fileContents = $chart->render();
			$this->save_xml_file($cache_file_name, $fileContents);

		}
		return $cache_file_name;
	}
}

?>
