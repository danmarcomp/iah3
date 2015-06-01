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
// vim: set foldmethod=marker :
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');


require_once("include/charts/BaseChart.php");


class Chart_pipeline_by_month extends BaseChart
{
	var $is_sidebar = false;
	var $modules = array('Opportunities');
	var $default_title = 'LBL_PIPELINE_MONTHS_TITLE_FULL';
	var $chart_type = 'hBar';
	var $dashletIcon = 'Forecasts';

	var $mode = 'open'; // one of all, open, closed
	var $user_ids;
	var $year_start;
	var $month_start;
	var $month_count;

	var $chart_select_options = array('user_ids');

	function init_chart_options($options) {
		global $current_user;

		if($this->is_sidebar)
			$this->user_ids = array($current_user->id);
		else
			$this->user_ids = array_get_default($options, 'user_ids');

		if($this->mode == 'closed') {
			$now = localtime(time(), 1);
			$current_year = $now['tm_year'] + 1900;
			$this->month_start = fiscal_year_start_month();
			$this->year_start = $current_year;
			if($now['tm_mon'] + 1 < $this->month_start)
				$this->year_start --;
			$this->month_count = '12';
		}
		else {
			$this->year_start = date('Y');
			$this->month_start = date('m')-1;
			if($this->month_start < 1) {
				$this->month_start = '12';
				$this->year_start --;
			}
			$this->month_count = '13';
		}

		$entropy = $this->create_options_hash($options);
		$this->cache_filename = filename_safe_string(implode('_', array(
			$entropy, $this->mode, 'pipeline_by_month',
			$this->year_start.'-'.$this->month_start,
			$this->month_count, $this->is_sidebar,
		)));
		$svg = $this->svg_charts_enabled();
		$this->cache_filename .= ($svg ? '.svg' : '.xml');
		$GLOBALS['log']->debug("cache file name is: $this->cache_filename");
	}


	function chart_display_options() {
		if($this->is_sidebar)
			return '';

		$lbl_users = translate('LBL_USERS', 'Charts');

        return array(
            'labels' => array(
                'user_ids' => $lbl_users
            ),
            'spec' => array(
                'user_ids' => array('type' => 'multienum', 'options' => get_user_array(false))
            ),
            'values' => array(
                'user_ids' => $this->user_ids
            )
        );
	}

	function get_footer_text() {
		global $current_user;
		if(!$this->is_sidebar)
			$users_text = translate('LBL_SELECTED_USERS', 'Charts');
		//else if($current_user->id == 1)
		//	$users_text = translate('LBL_ALL_USERS', 'Charts');
		else
			$users_text = translate('LBL_THE_CURRENT_USER', 'Charts');
		$footer_text = translate('LBL_PIPELINE_MONTHS_DESC_'.strtoupper($this->mode), 'Charts');
		$footer_text = str_replace('USER', $users_text, $footer_text);
		return $footer_text;
	}


	function gen_data()
	{
		global $db, $app_strings, $app_list_strings, $log, $barChartColors, $current_user;
		$vars = array(
			'closed_won_status',
			'closed_lost_status',
			'chart_size',
			'filter',
			'user_id',
			'max_value',
			'symbol',
			'won_translation',
			'total',
			'weighted',
			'months',
			'weighted_translation',
			'total_translation',
		);

		$cache_file_name = $this->get_cache_file();		

		$closed_won_status = 'Closed Won';
		$closed_lost_status = 'Closed Lost';
		$chart_size = $this->get_chart_info();
		$filter = $this->mode;
		$user_id = $this->get_selected_values($this->user_ids, get_user_array(false));


		// set date range
		if($this->year_start == '')
			$this->year_start = date('Y');
		if($this->month_start == '')
			$this->month_start = date('m');
		$months = array();
		$first_month = sprintf('%04d-%02d', $this->year_start, $this->month_start);
		$y = $this->year_start;
		$m = $this->month_start;
		for($i = 0; $i < $this->month_count; $i++) {
			$months[sprintf('%04d-%02d', $y, $m)] = array('total'=>0, 'weighted'=>0, 'total_won'=>0, 'won_count'=>0, 'gross_count'=>0, 'open_count'=>0);
			$m++;
			if($m > 12) { $m = 1; $y ++; }
		}
		$end_month = sprintf('%04d-%02d', $y, $m);
		$date_start = $first_month . '-01 00:00';
		$date_end = $end_month . '-01 00:00';


		$lq = new ListQuery('Opportunity');
		$lq->primary_added = true;
		$lq->addFieldLiteral('m', "date_format(opportunities.date_closed,'%Y-%m')");
		$lq->addFieldLiteral('total', 'sum(amount_usdollar/1000)', 'double');
		$lq->addFieldLiteral('weighted',
			"sum(CASE ".
				"WHEN sales_stage LIKE '".$closed_won_status."%' THEN amount_usdollar/1000 ".
				"WHEN sales_stage LIKE '".$closed_lost_status."%' THEN 0 ".
				"ELSE amount_usdollar*probability/100000 END)", 'double');
		$lq->addFieldLiteral('total_won', "sum(amount_usdollar/1000 * if(sales_stage LIKE '".$closed_won_status."%', 1, 0))", 'double');
		$lq->addFieldLiteral('won_count', "sum(if(sales_stage LIKE '".$closed_won_status."%', 1, 0))", 'int');
		$lq->addFieldLiteral('lost_count', "sum(if(sales_stage LIKE '".$closed_lost_status."%', 1, 0))", 'int');
		$lq->addFieldLiteral('opp_count', 'count(*)', 'int');
		if(count($user_id))
			$lq->addSimpleFilter('assigned_user_id', array_keys($user_id));
		if($filter == 'open') {
			$lq->addSimpleFilter('sales_stage', $closed_won_status, 'not_like', null, 'suffix');
			$lq->addSimpleFilter('sales_stage', $closed_lost_status, 'not_like', null, 'suffix');
		}
		if($filter == 'closed') {
			$lq->addSimpleFilter('sales_stage', $closed_won_status, 'like', 'suffix');
		}
		$lq->addFilterClause(array(
			'field' => 'date_closed',
			'operator' => 'between',
			'value' => $date_start,
			'end' => $date_end,
		));
		$lq->addAclFilters('report');
		$lq->setGroupBy('m');
		$lq->setOrderBy('m');
		$query = $lq->getSql();


		$result = $db->query($query) or sugar_die("Error selecting sugarbean: ".mysql_error());
		//build pipeline by sales stage data
		$total = 0;
		$weighted = 0;
		$won = 0;

		$div = 1;
		$symbol = AppConfig::setting('locale.base_currency.symbol');
		global $current_user;
		if($current_user->getPreference('currency') ){
			require_once('modules/Currencies/Currency.php');
			$currency = new Currency();
			$currency->retrieve($current_user->getPreference('currency'));
			$div = $currency->conversion_rate;
			$symbol = $currency->symbol;
		}

		//$max_value = 100;
		$row_totals = array(0);

		while($row = $db->fetchByAssoc($result, -1, false))
		{
			if($row['total']*$div <= 100){
				$sum = round($row['total']*$div, 2);
			} else {
				$sum = round($row['total']*$div);
			}
			if($row['weighted']*$div <= 100){
				$wsum = round($row['weighted']*$div, 2);
			} else {
				$wsum = round($row['weighted']*$div);
			}
			if($row['total_won']*$div <= 100){
				$wonsum = round($row['total_won']*$div, 2);
			} else {
				$wonsum = round($row['total_won']*$div);
			}

			$m = $row['m'];
			$months[$m]['total'] = $sum;
			$months[$m]['weighted'] = $wsum;
			$months[$m]['total_won'] = $wonsum;
			//if($max_value < $sum || $max_value < $wsum || $max_value < $wonsum)
			//	$max_value = max($sum, $wsum, $wonsum);
			//$months[$m]['opp_count'] = $row['opp_count'];
			$months[$m]['won_count'] = $row['won_count'];
			//$months[$m]['lost_count'] = $row['lost_count'];
			$months[$m]['gross_count'] = $row['opp_count'] - $row['lost_count'];
			$months[$m]['open_count'] = $months[$m]['gross_count'] - $row['won_count'];

			$row_totals[] = $sum;
			$total += $sum;
			$weighted += $wsum;
			$won += $wonsum;
		}

		$max_value = $this->get_useful_max($row_totals, $chart_size[0]);

		$won_translation = translate('LBL_WON_TOTAL', 'Charts');
		$total_translation = translate('LBL_GROSS_TOTAL', 'Charts');
		$weighted_translation = translate('LBL_WEIGHTED_TOTAL', 'Charts');

		return compact($vars);
	}

	function gen_xml() {/*{{{*/
		global $app_strings, $app_list_strings, $log, $barChartColors, $current_user;

		$cache_file_name = $this->get_cache_file();		

		if(! file_exists($cache_file_name) || $this->refresh) {
			extract($this->gen_data());
			$fileContents = '     <yData defaultAltText="'.translate('LBL_ROLLOVER_DETAILS', 'Charts').'">'."\n";
			if (!empty($months)) {
				foreach ($months as $month => $row){
					// longreach - start added
					$month_end =  $month . date('-t', strtotime($month . '-01'));
					// longreach - end added
					$user_args = '';
					if(is_array($user_id))
						foreach($user_id as $uid => $dummy)
							$user_args .= '&assigned_user_id[]='.$uid;
					else
						$user_args = '&assigned_user_id='.$user_id;
					$user_args .= '&searchFormTab=advanced_search&view_closed_items=1';
					$url = htmlspecialchars('index.php?module=Opportunities&action=ListView&from_date_closed='.$month . '-01&to_date_closed='.$month_end.$user_args.'&query=true');

					$label = currency_format_number($row['total'], array('currency_symbol' => false));
-					$fileContents .= '          <dataRow title="'.$month.'" endLabel="'.$label.'">'."\n";
					if($filter != 'open')
						$fileContents .= '               <bar id="won" totalSize="'.$row['total_won'].'" altText="'.$month.': '.$row['won_count'].' '.translate('LBL_OPPS_WORTH', 'Charts').' '.$row['total_won'].translate('LBL_OPP_THOUSANDS', 'Charts').' '.translate('LBL_WON', 'Charts').'" url="'.$url.'&amp;sales_stage='.$closed_won_status.'"/>'."\n";
					$added_total = $row['weighted'] - $row['total_won'];
					if($filter != 'closed')
						$fileContents .= '               <bar id="weighted" totalSize="'.$added_total.'" altText="'.$month.': '.$row['open_count'].' '.translate('LBL_OPPS_WORTH', 'Charts').' '.$row['weighted'].translate('LBL_OPP_THOUSANDS', 'Charts').' '.translate('LBL_WEIGHTED', 'Charts').'" url="'.$url.'"/>'."\n";
					$added_total = $row['total'] - $row['weighted'] - $row['total_won'];
					if($filter != 'closed')
						$fileContents .= '               <bar id="total" totalSize="'.$added_total.'" altText="'.$month.': '.$row['gross_count'].' '.translate('LBL_OPPS_WORTH', 'Charts').' '.$row['total'].translate('LBL_OPP_THOUSANDS', 'Charts').' '.translate('LBL_GROSS', 'Charts').'" url="'.$url.'"/>'."\n";
					$fileContents .= '          </dataRow>'."\n";

					/*$fileContents .= '          <dataRow title="" endLabel="'.$row['weighted'].'">'."\n";
					$fileContents .= '               <bar id="weighted" totalSize="'.$row['weighted'].'" altText="'.$month.': '.$row['opp_count'].' '.translate('LBL_OPPS_WORTH', 'Charts').' '.$row['weighted'].translate('LBL_OPP_THOUSANDS', 'Charts').'" url="'.$url.'"/>'."\n";
					$fileContents .= '          </dataRow>'."\n";*/
				}
			} else {
				$fileContents .= '          <dataRow title="" endLabel="">'."\n";
				$fileContents .= '               <bar id="" totalSize="0" altText="" url=""/>'."\n";
				$fileContents .= '          </dataRow>'."\n";
			}
			$fileContents .= '     </yData>'."\n";

			$length = ($this->is_sidebar ? 4 : 10);

			$fileContents .= '     <xData min="0" max="'.$max_value.'" length="'.$length.'" prefix="'.$symbol.'" suffix="" defaultAltText="'.translate('LBL_ROLLOVER_DETAILS', 'Charts').'"/>'."\n";

			$legend_status = ($this->is_sidebar ? 'off' : 'on');
			$fileContents .= '     <colorLegend status="'.$legend_status.'">'."\n";
			if($filter != 'open')
				$fileContents .= '          <mapping id="won" name="'.$won_translation.'" color="0x00FF00"/>'."\n";
			if($filter != 'closed')
				$fileContents .= '          <mapping id="weighted" name="'.$weighted_translation.'" color="0xFF0000"/>'."\n";
			if($filter != 'closed')
				$fileContents .= '          <mapping id="total" name="'.$total_translation.'" color="0x0000FF"/>'."\n";
			$fileContents .= '     </colorLegend>'."\n";
			$fileContents .= '     <graphInfo>'."\n";
			//$fileContents .= '          <![CDATA['.translate('LBL_DATE_RANGE', 'Charts')." ".$date_start." ".translate('LBL_DATE_RANGE_TO', 'Charts')." ".$date_end."<br/>".translate('LBL_OPP_SIZE', 'Charts').' '.$symbol.'1'.translate('LBL_OPP_THOUSANDS', 'Charts').']]>'."\n";
			$fileContents .= '     </graphInfo>'."\n";
			$fileContents .= '     <chartColors ';
			foreach ($barChartColors as $key => $value) {
				$fileContents .= ' '.$key.'='.'"'.$value.'" ';
			}
			$fileContents .= ' />'."\n";
			$fileContents .= '</graphData>'."\n";
			$total = currency_format_number($total);
			$weighted = currency_format_number($weighted, array('decimals' => 0, 'use_currency_decimals' => false));
			$won = currency_format_number($weighted, array('round' => 0, 'use_currency_decimals' => false));
			$title = '<graphData title="';
			if($filter == 'closed')
				$title .= translate('LBL_TOTAL_SALES', 'Charts');
			else
				$title .= translate('LBL_TOTAL_PIPELINE', 'Charts');
			$title .= $total.$app_strings['LBL_THOUSANDS_SYMBOL'];
			if($filter == 'all' || $filter == 'open') {
				$title .= ' (';
				if($filter == 'all')
					$title .= $won.$app_strings['LBL_THOUSANDS_SYMBOL'].' '.translate('LBL_WON', 'Charts').', ';
				$title .= $weighted.$app_strings['LBL_THOUSANDS_SYMBOL'].' '.translate('LBL_WEIGHTED', 'Charts').')';
			}
			$title .= '">'."\n";
			$fileContents = $title.$fileContents;

			//echo $fileContents;
			$this->save_xml_file($cache_file_name, $fileContents);
		}
		return $cache_file_name;
	}/*}}}*/

	function gen_svg() {
		global $app_strings, $app_list_strings, $log, $barChartColors, $current_user;

		$cache_file_name = $this->get_cache_file();
		if(! file_exists($cache_file_name) || $this->refresh) {
			require_once 'include/SVGCharts/Bar.php';
			require_once('include/SVGCharts/impl/SVGChartData.php');
			$data = new SVGChartData;
			$size = $this->get_chart_info();
			$w = $size[1];
			$chart = new SVGChartBar($w, $w/2);
			$chart->setHorizontal();
			$seriesVars = array();
			extract($this->gen_data());
			if (!empty($months)) {
				$labels = array();
				$series = array();
				foreach ($months as $month => $row){
					$user_args = '';
					$month_end =  $month . date('-t', strtotime($month . '-01'));

					$seriesVars[$month] = array(
						'month_start' => $month . '-01',
						'month_end' => $month_end,
					);

					// longreach - end added
					if(is_array($user_id))
						foreach($user_id as $uid => $dummy)
							$user_args .= '&assigned_user_id[]='.$uid;
					else
						$user_args = '&filter_owner='.$user_id;
					$user_args .= '&searchFormTab=advanced_search&view_closed_items=1';
					$labels[$month]= $month;

					if($filter != 'open') {
						//$fileContents .= '               <bar id="won" totalSize="'.$row['total_won'].'" altText="'.$month.': '.$row['won_count'].' '.translate('LBL_OPPS_WORTH', 'Charts').' '.$row['total_won'].translate('LBL_OPP_THOUSANDS', 'Charts').' '.translate('LBL_WON', 'Charts').'" url="'.$url.'&amp;sales_stage='.$closed_won_status.'"/>'."\n";
						$name = translate('LBL_WON_TOTAL', 'Charts');
						if ($row['total_won']) {
							$series[$name]['points'][$month] = array('n' => $row['total_won'], 'c' => $row['won_count']);
							$seriesVars[$month][$name] = array(
								'sales_stage' => 'Closed Won',
							);
						}
					}

					$added_total = $row['total'] - $row['weighted'] - $row['total_won'];
					if($filter != 'closed') {
						//$fileContents .= '               <bar id="total" totalSize="'.$added_total.'" altText="'.$month.': '.$row['gross_count'].' '.translate('LBL_OPPS_WORTH', 'Charts').' '.$row['total'].translate('LBL_OPP_THOUSANDS', 'Charts').' '.translate('LBL_GROSS', 'Charts').'" url="'.$url.'"/>'."\n";
						$name = translate('LBL_GROSS_TOTAL', 'Charts');
						if ($added_total) {
							$series[$name]['points'][$month] = array('n' => $added_total, 'c' => $row['gross_count']);
							$seriesVars[$month][$name] = array(
								'sales_stage' => '',
							);
						}
					}

					$added_total = $row['weighted'] - $row['total_won'];
					if($filter != 'closed') {
						//$fileContents .= '               <bar id="weighted" totalSize="'.$added_total.'" altText="'.$month.': '.$row['open_count'].' '.translate('LBL_OPPS_WORTH', 'Charts').' '.$row['weighted'].translate('LBL_OPP_THOUSANDS', 'Charts').' '.translate('LBL_WEIGHTED', 'Charts').'" url="'.$url.'"/>'."\n";
						$name = translate('LBL_WEIGHTED_TOTAL', 'Charts');
						if ($added_total) {
							$series[$name]['points'][$month] = array('n' => $added_total, 'c' => $row['open_count']);
						}
					}
				}
			}

			$total = currency_format_number($total, array('symbol_space' => ''));
			$weighted = currency_format_number($weighted, array('decimals' => 0, 'use_currency_decimals' => false, 'symbol_space' => ''));
			$won = currency_format_number($weighted, array('round' => 0, 'use_currency_decimals' => false, 'symbol_space' => ''));
			if($filter == 'closed')
				$title = translate('LBL_TOTAL_SALES', 'Charts');
			else
				$title = translate('LBL_TOTAL_PIPELINE', 'Charts');
			$title .= $total.$app_strings['LBL_THOUSANDS_SYMBOL'];
			if($filter == 'all' || $filter == 'open') {
				$title .= ' (';
				if($filter == 'all')
					$title .= $won.$app_strings['LBL_THOUSANDS_SYMBOL'].' '.translate('LBL_WON', 'Charts').', ';
				$title .= $weighted.$app_strings['LBL_THOUSANDS_SYMBOL'].' '.translate('LBL_WEIGHTED', 'Charts').')';
			}
			$chart->setTitle($title);
			$chart->setData($data);
			foreach ($series as $k => $s) {
				$data->addSeries($k, $s);
			}
			$data->setLabels($labels);
			$data->setSeriesVars($seriesVars);
			$chart->setStacked(true);
			$chart->setAdditiveStatus(true);
			$valueRollover = '$group1$: $count$ '.translate('LBL_OPPS_WORTH', 'Charts'). ' $value$' . translate('LBL_OPP_THOUSANDS', 'Charts').' $group2$';
			$chart->setStatus(translate('LBL_ROLLOVER_DETAILS', 'Charts'), $valueRollover, $valueRollover);
			$url = 
				  AppConfig::site_url()
				. '/index.php?module=Opportunities&action=ListView'
				. '&date_closed-operator=between_dates&date_closed=$month_start$&date_closed-end=$month_end$'
				. '&sales_stage=$sales_stage$'
				. $user_args
				. '&layout=Standard&view_closed=true&query=true'
				;
			$chart->setLinkTemplate($url);

			$fileContents = $chart->render();

			$this->save_xml_file($cache_file_name, $fileContents);
		}
		return $cache_file_name;
	}
}


?>
