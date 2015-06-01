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

class Chart_pipeline_by_lead_source extends BaseChart
{
	var $is_sidebar = false;
	var $modules = array('Opportunities');
	var $default_title = 'LBL_LEAD_SOURCE_FORM_TITLE';
	var $chart_type = 'pie';
	var $dashletIcon = 'Leads';

	var $user_ids;
	var $lead_sources;
    var $display_percent;

	var $chart_select_options = array('lead_sources', 'user_ids');
	var $chart_text_options = array('display_percent');

	function init_chart_options($options) {
		$this->user_ids = array_get_default($options, 'user_ids');
		$this->lead_sources = array_get_default($options, 'lead_sources');
		$this->display_percent = array_get_default($options, 'display_percent');
		
		$entropy = $this->create_options_hash($options);
		$this->cache_filename = filename_safe_string(implode('_', array(
			$entropy, 'pipeline_by_lead_source',
			date('Y-m-d'), $this->is_sidebar,
		)));
		$svg = $this->svg_charts_enabled();
		$this->cache_filename .= ($svg ? '.svg' : '.xml');
		$GLOBALS['log']->debug("cache file name is: $this->cache_filename");
	}
	
	
	function chart_display_options() {
		$lbl_sources = translate('LBL_LEAD_SOURCES', 'Charts');
		$lbl_users = translate('LBL_USERS', 'Charts');
		$lbl_percent = translate('LBL_DISPLAY_PERCENT_VALUES', 'Charts');

        return array(
            'labels' => array(
                'lead_sources' => $lbl_sources,
                'display_percent' => $lbl_percent,
                'user_ids' => $lbl_users
            ),
            'spec' => array(
                'lead_sources' => array('type' => 'multienum', 'options' => 'lead_source_dom'),
                'display_percent' => array('type' => 'bool'),
                'user_ids' => array('type' => 'multienum', 'options' => get_user_array(false))
            ),
            'values' => array(
                'lead_sources' => $this->lead_sources,
                'display_percent' => $this->display_percent,
                'user_ids' => $this->user_ids
            )
        );
	}


	function get_footer_text() {
		return translate('LBL_LEAD_SOURCE_FORM_DESC', 'Charts');
	}

	function gen_data()
	{
		global $db, $app_strings, $pieChartColors, $current_user;

		$vars = array(
			'legends',
			'total',
			'subtitle',
			'leadSourceArr',
		);
		$cache_file_name = $this->get_cache_file();

		require_once('modules/Currencies/Currency.php');
		$legends = $this->get_selected_values($this->lead_sources, 'lead_source_dom');
		$user_id = $this->user_ids ? $this->get_selected_values($this->user_ids, get_user_array(false)) : array();


		$lq = new ListQuery('Opportunity');
		$lq->primary_added = true;
		$lq->addField('lead_source');
		$lq->addFieldLiteral('total', 'sum(amount_usdollar/1000)', 'double');
		$lq->addFieldLiteral('opp_count', 'count(*)', 'int');
		if(count($legends))
			$lq->addSimpleFilter('lead_source', array_keys($legends));
		if(count($user_id))
			$lq->addSimpleFilter('assigned_user_id', array_keys($user_id));
		$lq->addAclFilters('report');
		$lq->setGroupBy('lead_source');
		$lq->setOrderBy('total');
		$query = $lq->getSql();


		//build pipeline by lead source data
		$total = 0;
		$div = 1;
		$symbol = AppConfig::setting('locale.base_currency.symbol');
		global $current_user;
		if($current_user->getPreference('currency') ) {
			require_once('modules/Currencies/Currency.php');
			$currency = new Currency();
			$currency->retrieve($current_user->getPreference('currency'));
			$div = $currency->conversion_rate;
			$symbol = $currency->symbol;
		}
		$subtitle = translate('LBL_OPP_SIZE', 'Charts').' '.$symbol.'1'.translate('LBL_OPP_THOUSANDS', 'Charts');

		$result = $db->query($query) or sugar_die("Error selecting sugarbean: ".mysql_error());
		$leadSourceArr =  array();
		while($row = $db->fetchByAssoc($result, -1, false))
		{
			if($row['lead_source'] == ''){
				$leadSource = translate('NTC_NO_LEGENDS', 'Charts');
			} else {
				$leadSource = $row['lead_source'];
			}
			if($row['total']*$div<=100){
				$sum = round($row['total']*$div, 2);
			} else {
				$sum = round($row['total']*$div);
			}

			$leadSourceArr[$leadSource]['opp_count'] = $row['opp_count'];
			$leadSourceArr[$leadSource]['sum'] = $sum;
		}

		return compact($vars);
	}
	/**
	* Creates PIE CHART image of opportunities by lead_source.
	* param $datax- the sales stage data to display in the x-axis
	* param $datay- the sum of opportunity amounts for each opportunity in each sales stage
	* to display in the y-axis
	* Portions created by SugarCRM are Copyright (C) SugarCRM, Inc..
	* All Rights Reserved..
	* Contributor(s): ______________________________________..
	*/
	function gen_xml() {
		global $app_strings, $pieChartColors, $current_user;

		$cache_file_name = $this->get_cache_file();
		if (!file_exists($cache_file_name) || $this->refresh) {
			$kDelim = $current_user->getPreference('num_grp_sep');

			extract($this->gen_data());


			$fileContents = '';
			$fileContents .= '     <pie defaultAltText="'.translate('LBL_ROLLOVER_WEDGE_DETAILS', 'Charts').'" legendStatus="on">'."\n";

			$i=0;
			foreach ($legends as $lead_source_key=>$translation) {
				if ($lead_source_key == '') {
					$lead_source_key = translate('NTC_NO_LEGENDS', 'Charts');
					$translation = translate('NTC_NO_LEGENDS', 'Charts');
				}
				if(!isset($leadSourceArr[$lead_source_key])) {
					$leadSourceArr[$lead_source_key] = $lead_source_key;
					$leadSourceArr[$lead_source_key]['sum'] = 0;
				}
				$color = $this->generate_graphcolor($lead_source_key,$i);
				$fileContents .= '          <wedge title="'.$translation.'" kDelim="'.$kDelim.'" value="'.$leadSourceArr[$lead_source_key]['sum'].'" color="'.$color.'" labelText="'.currency_format_number($leadSourceArr[$lead_source_key]['sum'], array('currency_symbol' => false)).'" url="index.php?module=Opportunities&action=index&lead_source='.urlencode($lead_source_key).'&query=true&searchFormTab=advanced_search" altText="'.format_number($leadSourceArr[$lead_source_key]['opp_count'], 0, 0).' '.translate('LBL_OPPS_IN_LEAD_SOURCE', 'Charts').' '.$translation.'"/>'."\n";
				if(isset($leadSourceArr[$lead_source_key])){$total += $leadSourceArr[$lead_source_key]['sum'];}
				$i++;
			}

			$fileContents .= '     </pie>'."\n";
			$fileContents .= '     <graphInfo>'."\n";
			$fileContents .= '          <![CDATA[]]>'."\n";
			$fileContents .= '     </graphInfo>'."\n";
			$fileContents .= '     <chartColors ';
			foreach ($pieChartColors as $key => $value) {
				$fileContents .= ' '.$key.'='.'"'.$value.'" ';
			}
			$fileContents .= ' />'."\n";
			$fileContents .= '</graphData>'."\n";
			$total = round($total, 2);
			$title = translate('LBL_TOTAL_PIPELINE', 'Charts').currency_format_number($total, array('currency_symbol' => true)).$app_strings['LBL_THOUSANDS_SYMBOL'];
			$fileContents = '<graphData title="'.$title.'" subtitle="'.$subtitle.'">'."\n" . $fileContents;
			$GLOBALS['log']->debug("total is: $total");
			if ($total == 0) {
				return (translate('ERR_NO_OPPS', 'Charts'));
			}

			$this->save_xml_file($cache_file_name, $fileContents);
		}
		return $cache_file_name;
	}

	function gen_svg()
	{
		global $app_strings, $current_user;
		$cache_file_name = $this->get_cache_file();		
		if (!file_exists($cache_file_name) || $this->refresh) {
			extract($this->gen_data());
			require_once 'include/SVGCharts/Pie.php';
			require_once('include/SVGCharts/impl/SVGChartData.php');
			$data = new SVGChartData;
			$size = $this->get_chart_info();
			$w = $size[1];
			$chart = new SVGChartPie($w, $w/2);
			$chart->setOffset(10);
			$chart->setPercentFormat($this->display_percent ? 1 : 0);

			$series = array();
			$seriesLabels = array();
			$labels = array();
			$seriesVars = array();


			foreach ($legends as $lead_source_key=>$translation) {
				if ($lead_source_key == '') {
					$lead_source_key = translate('NTC_NO_LEGENDS', 'Charts');
					$translation = translate('NTC_NO_LEGENDS', 'Charts');
				}
				if(!isset($leadSourceArr[$lead_source_key])) {
					$leadSourceArr[$lead_source_key] = $lead_source_key;
					$leadSourceArr[$lead_source_key]['sum'] = 0;
				}
				$series['points'][$lead_source_key] = array(
					'n' => $leadSourceArr[$lead_source_key]['sum'],
					'c' => format_number($leadSourceArr[$lead_source_key]['opp_count'], 0, 0),
					//currency_format_number($leadSourceArr[$lead_source_key]['sum'], array('currency_symbol' => false))

				);
				$labels[$lead_source_key] = $translation;
				//$fileContents .= '          <wedge title="'.$translation.'" kDelim="'.$kDelim.'" value="'.$leadSourceArr[$lead_source_key]['sum'].'" color="'.$color.'" labelText="'.currency_format_number($leadSourceArr[$lead_source_key]['sum'], array('currency_symbol' => false)).'" url="index.php?module=Opportunities&action=index&lead_source='.urlencode($lead_source_key).'&query=true&searchFormTab=advanced_search" altText="'.format_number($leadSourceArr[$lead_source_key]['opp_count'], 0, 0).' '.translate('LBL_OPPS_IN_LEAD_SOURCE', 'Charts').' '.$translation.'"/>'."\n";
				if(isset($leadSourceArr[$lead_source_key])){$total += $leadSourceArr[$lead_source_key]['sum'];}
			}

			$title = translate('LBL_TOTAL_PIPELINE', 'Charts').currency_format_number($total, array('currency_symbol' => true, 'symbol_space'=>'')).$app_strings['LBL_THOUSANDS_SYMBOL'];

			$data->addSeries('', $series);
			$data->setLabels($labels);

			$valueRollover = ' $count$ '.translate('LBL_OPPS_IN_LEAD_SOURCE', 'Charts').' $group1$';
			$chart->setStatus(translate('LBL_ROLLOVER_WEDGE_DETAILS', 'Charts'), $valueRollover, $valueRollover);

			$url =
				  AppConfig::site_url()
				. '/index.php?module=Opportunities&action=index&lead_source=$series$&query=true&view_closed=1&layout=Standard';

				
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
