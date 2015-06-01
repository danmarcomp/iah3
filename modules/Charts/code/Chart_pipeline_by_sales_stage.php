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

class Chart_pipeline_by_sales_stage extends BaseChart
{
	var $is_sidebar = false;
	var $modules = array('Opportunities');
	var $default_title = 'LBL_SALES_STAGE_FORM_TITLE';
	var $chart_type = 'hBar';
	var $dashletIcon = 'Forecasts';

	var $user_ids;
	var $date_start;
	var $date_end;
	var $sales_stages;
	
	var $chart_date_options = array('date_start', 'date_end');
	var $chart_select_options = array('user_ids', 'sales_stages');
	
	
	function init_chart_options($options) {
		global $current_user, $timedate;
		
		if($this->is_sidebar)
			$this->user_ids = array($current_user->id);
		else
			$this->user_ids = array_get_default($options, 'user_ids');
		
		$this->date_start = array_get_default($options, 'date_start');
		if(! $this->date_start)
			$this->date_start = date($timedate->dbDayFormat, time());

		$this->date_end = array_get_default($options, 'date_end');
		if(! $this->date_end)
			$this->date_end = date($timedate->dbDayFormat, strtotime('now +2 year'));
		
		$this->sales_stages = array_get_default($options, 'sales_stages');
		
		$entropy = $this->create_options_hash($options);
		$this->cache_filename = filename_safe_string(implode('_', array(
			$entropy, 'pipeline_by_sales_stage', $this->is_sidebar,
		)));
		$svg = $this->svg_charts_enabled();
		$this->cache_filename .= ($svg ? '.svg' : '.xml');

		$GLOBALS['log']->debug("cache file name is: $this->cache_filename");
	}
	

	function chart_display_options() {
		global $timedate;
		$lbl_date_start = translate('LBL_DATE_START', 'Charts');
		$lbl_date_end = translate('LBL_DATE_END', 'Charts');
		$lbl_stages = translate('LBL_SALES_STAGES', 'Charts');
		$lbl_users = translate('LBL_USERS', 'Charts');
		$date_start = $timedate->to_display_date($this->date_start, false);
		$date_end = $timedate->to_display_date($this->date_end, false);

        $options = array(
            'labels' => array(
                'date_start' => $lbl_date_start,
                'date_end' => $lbl_date_end,
                'sales_stages' => $lbl_stages
            ),
            'spec' => array(
                'date_start' => array('type' => 'date'),
                'date_end' => array('type' => 'date'),
                'sales_stages' => array('type' => 'multienum', 'options' => 'sales_stage_dom')
            ),
            'values' => array(
                'date_start' => $date_start,
                'date_end' => $date_end,
                'sales_stages' => $this->sales_stages
            )
        );

        if(! $this->is_sidebar) {
            $options['labels']['user_ids'] = $lbl_users;
            $options['spec']['user_ids'] =  array('type' => 'multienum', 'options' => get_user_array(false));
            $options['values']['user_ids'] = $this->user_ids;
        }

		return $options;
	}
	

	function get_footer_text() {
		return translate('LBL_SALES_STAGE_FORM_DESC', 'Charts');
	}

	function gen_data()
	{
		global $db, $app_strings, $barChartColors, $current_user, $timedate;
		$cache_file_name = $this->get_cache_file();

		$vars = array(
			'datax',
			'stageArr',
			'symbol',
			'new_ids',
			'dateStartDisplay', 
			'dateEndDisplay',
			'total',
		);

		require_once('modules/Currencies/Currency.php');
		$user_id = $this->user_ids ? $this->get_selected_values($this->user_ids, get_user_array(false)) : array();
		$datax = $this->get_selected_values($this->sales_stages, 'sales_stage_dom');


		$lq = new ListQuery('Opportunity');
		$lq->primary_added = true;
		$lq->addField('sales_stage');
		$lq->addField('assigned_user');
		$lq->addField('assigned_user_id');
		$lq->addFieldLiteral('opp_count', 'count(*)', 'int');
		$lq->addFieldLiteral('total', 'sum(amount_usdollar/1000)', 'double');
		if(count($datax))
			$lq->addSimpleFilter('sales_stage', array_keys($datax));
		if(count($user_id))
			$lq->addSimpleFilter('assigned_user_id', array_keys($user_id));
		$lq->addFilterClause(array(
			'field' => 'date_closed',
			'operator' => 'between',
			'value' => $this->date_start,
			'end' => $this->date_end,
		));
		$lq->addAclFilters('report');
		$lq->setGroupBy('sales_stage, assigned_user_id');
		$lq->setOrderBy('total');
		$query = $lq->getSql();
		
		
		$result = $db->query($query)
		or sugar_die("Error selecting sugarbean: ".mysql_error());
		//build pipeline by sales stage data
		$total = 0;
		$div = 1;
		$symbol = AppConfig::setting('locale.base_currency.symbol');
		global $current_user;
		if($current_user->getPreference('currency') ){
			$currency = new Currency();
			$currency->retrieve($current_user->getPreference('currency'));
			$div = $currency->conversion_rate;
			$symbol = $currency->symbol;
		}
		// cn: adding user-pref date handling
		$dateStartDisplay = $timedate->to_display_date($this->date_start, false);
		$dateEndDisplay = $timedate->to_display_date($this->date_end, false);
		
		$stageArr = array();
		$usernameArr = array();
		$rowTotalArr = array();
		$rowTotalArr[] = 0;
		while($row = $db->fetchByAssoc($result, -1, false))
		{
			if($row['total']*$div<=100){
				$sum = round($row['total']*$div, 2);
			} else {
				$sum = round($row['total']*$div);
			}

			if($lq->checkUseRealNames()) {
				$uname = get_assigned_user_name($row['assigned_user_id']);
			} else {
				$uname = get_user_name($row['assigned_user_id']);
			}

			if(!isset($stageArr[$row['sales_stage']]['row_total'])) {$stageArr[$row['sales_stage']]['row_total']=0;}
			$stageArr[$row['sales_stage']][$row['assigned_user_id']]['opp_count'] = $row['opp_count'];
			$stageArr[$row['sales_stage']][$row['assigned_user_id']]['total'] = $sum;
			$stageArr[$row['sales_stage']]['people'][$row['assigned_user_id']] = $uname;
			$stageArr[$row['sales_stage']]['row_total'] += $sum;

			$usernameArr[$row['assigned_user_id']] = $uname;
			$total += $sum;
		}

		return compact($vars);

	}


	/**
	* Creates opportunity pipeline image as a HORIZONTAL accumlated BAR GRAPH for multiple users.
	* Portions created by SugarCRM are Copyright (C) SugarCRM, Inc..
	* All Rights Reserved..
	* Contributor(s): ______________________________________..
	*/
	function gen_xml()
	{
		global $app_strings, $barChartColors, $current_user, $timedate;
		
		$cache_file_name = $this->get_cache_file();
		if(! file_exists($cache_file_name) || $this->refresh) {
			
			extract($this->gen_data());
			$fileContents = '     <yData defaultAltText="'.translate('LBL_ROLLOVER_DETAILS', 'Charts').'">'."\n";
			$kDelim = $current_user->getPreference('num_grp_sep');
			$rowTotalArr = array(0);
			foreach ($datax as $key=>$translation) {
				if(isset($stageArr[$key]['row_total'])){$rowTotalArr[]=$stageArr[$key]['row_total'];}
				if(isset($stageArr[$key]['row_total']) && $stageArr[$key]['row_total']>100) {
					$stageArr[$key]['row_total'] = round($stageArr[$key]['row_total']);
				}
				$fileContents .= '     <dataRow title="'.$translation.'" endLabel="';
				if(isset($stageArr[$key]['row_total'])){$fileContents .= currency_format_number($stageArr[$key]['row_total'], array('currency_symbol' => false));}
				$fileContents .= '">'."\n";
				if(isset($stageArr[$key]['people'])){
					asort($stageArr[$key]['people']);
					reset($stageArr[$key]['people']);
					foreach ($stageArr[$key]['people'] as $nameKey=>$nameValue) {
						$fileContents .= '          <bar id="'.$nameKey.'" totalSize="'.$stageArr[$key][$nameKey]['total'].'" altText="'.$nameValue.': '.format_number($stageArr[$key][$nameKey]['opp_count'], 0, 0).' '.translate('LBL_OPPS_WORTH', 'Charts').' '.currency_format_number($stageArr[$key][$nameKey]['total'], array('currency_symbol'=>false)).translate('LBL_OPP_THOUSANDS', 'Charts').' '.translate('LBL_OPPS_IN_STAGE', 'Charts').' '.$translation.'" url="index.php?module=Opportunities&action=index&assigned_user_id[]='.$nameKey.'&sales_stage='.urlencode($key).'&from_date_start='.$this->date_start.'&to_date_start='.$this->date_end.'&query=true&searchFormTab=advanced_search"/>'."\n";
					}
				}
				$fileContents .= '     </dataRow>'."\n";
			}
			$fileContents .= '     </yData>'."\n";
			
			// longreach - added $chart_size param
			$chart_size = $this->get_chart_info();
			$max = $this->get_useful_max($rowTotalArr, $chart_size[0]);			
			$length = $this->is_sidebar ? 4 : 10;

			$fileContents .= '     <xData min="0" max="'.$max.'" length="'.$length.'" prefix="'.$symbol.'" suffix="" kDelim="'.$kDelim.'" />'."\n";
			$fileContents .= '     <colorLegend status="on">'."\n";
			$i=0;
			asort($new_ids);
			foreach ($new_ids as $key=>$value) {
			$color = $this->generate_graphcolor($key,$i);
			$fileContents .= '          <mapping id="'.$key.'" name="'.$value.'" color="'.$color.'"/>'."\n";
			$i++;
			}
			$fileContents .= '     </colorLegend>'."\n";
			$fileContents .= '     <graphInfo>'."\n";
			$fileContents .= '          <![CDATA['.translate('LBL_DATE_RANGE', 'Charts').' '.$dateStartDisplay.' '.translate('LBL_DATE_RANGE_TO', 'Charts').' '.$dateEndDisplay.'<BR/>'.translate('LBL_OPP_SIZE', 'Charts').' '.$symbol.'1'.translate('LBL_OPP_THOUSANDS', 'Charts').']]>'."\n";
			$fileContents .= '     </graphInfo>'."\n";
			$fileContents .= '     <chartColors ';
			foreach ($barChartColors as $key => $value) {
				$fileContents .= ' '.$key.'='.'"'.$value.'" ';
			}
			$fileContents .= ' />'."\n";
			$fileContents .= '</graphData>'."\n";
			$total = $total;

			$title = '<graphData title="'.translate('LBL_TOTAL_PIPELINE', 'Charts').currency_format_number($total, array('currency_symbol' => true)).$app_strings['LBL_THOUSANDS_SYMBOL'].'">'."\n";
			$fileContents = $title.$fileContents;
			$this->save_xml_file($cache_file_name, $fileContents);
			
		}
		return $cache_file_name;
	}
	
	function gen_svg()
	{
		global $app_strings, $barChartColors, $current_user, $timedate;
		
		$cache_file_name = $this->get_cache_file();
		if(! file_exists($cache_file_name) || $this->refresh) {
			extract($this->gen_data());

			require_once 'include/SVGCharts/Bar.php';
			require_once('include/SVGCharts/impl/SVGChartData.php');
			$data = new SVGChartData;
			$size = $this->get_chart_info();
			$w = $size[1];
			$chart = new SVGChartBar($w, $w/2);
			$chart->setHorizontal();
			$labels = array();
			$series = $seriesVars = array();


			$kDelim = $current_user->getPreference('num_grp_sep');
			foreach ($datax as $key=>$translation) {
				$labels[$key]= $translation;
				if(isset($stageArr[$key]['row_total'])){$rowTotalArr[]=$stageArr[$key]['row_total'];}
				if(isset($stageArr[$key]['row_total']) && $stageArr[$key]['row_total']>100) {
					$stageArr[$key]['row_total'] = round($stageArr[$key]['row_total']);
				}
				if(isset($stageArr[$key]['people'])){
					asort($stageArr[$key]['people']);
					reset($stageArr[$key]['people']);
					foreach ($stageArr[$key]['people'] as $nameKey=>$nameValue) {
						$series[$nameValue]['points'][$key] = array(
							'n' => $stageArr[$key][$nameKey]['total'],
							'c' => $stageArr[$key][$nameKey]['opp_count'],
							's' => currency_format_number($stageArr[$key][$nameKey]['total'], array('currency_symbol'=>true, 'symbol_space' => '')).translate('LBL_OPP_THOUSANDS', 'Charts'),
						);
						$seriesVars[$key][$nameValue] = array(
							'sales_stage' => $key,
							'user_id' => $nameKey,
						);
					}
				}
			}
			
			$title = translate('LBL_TOTAL_PIPELINE', 'Charts'). currency_format_number($total, array('currency_symbol' => true, 'symbol_space'=>'')).$app_strings['LBL_THOUSANDS_SYMBOL'];

			$chart->setTitle($title);
			$chart->setData($data);
			foreach ($series as $k => $s) {
				$data->addSeries($k, $s);
			}
			$data->setLabels($labels);
			$data->setSeriesVars($seriesVars);
			$chart->setStacked(true);
			$chart->setAdditiveStatus(true);
			if($this->user_ids == array(AppConfig::current_user_id()))
				$chart->removeLegend();

			$valueRollover = '$group2$: $count$ '.translate('LBL_OPPS_WORTH', 'Charts'). ' $value$' . translate('LBL_OPP_THOUSANDS', 'Charts') . ' ' . translate('LBL_OPPS_IN_STAGE', 'Charts').' $group1$';
			$chart->setStatus(translate('LBL_ROLLOVER_DETAILS', 'Charts'), $valueRollover, $valueRollover);

			$url = 
				  AppConfig::site_url()
				. '/index.php?module=Opportunities&action=ListView'
				. '&filter_owner=$user_id$&sales_stage=$sales_stage$'
				. '&date_closed-operator=between_dates&date_closed=' . $this->date_start . '&date_closed-end=' . $this->date_end
				. '&query=true&view_closed=1&layout=Standard'
				;
			$chart->setLinkTemplate($url);


			$fileContents = $chart->render();
			$this->save_xml_file($cache_file_name, $fileContents);
			
		}
		return $cache_file_name;
	}

}

?>
