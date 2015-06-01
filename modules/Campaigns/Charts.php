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

* Description:  Includes the functions for Customer module specific charts.
********************************************************************************/

require_once('modules/Campaigns/Campaign.php');
require_once('include/charts/Charts.php');
require_once('include/utils.php');


class charts {
	/**
	* Creates opportunity pipeline image as a VERTICAL accumlated bar graph for multiple users.
	* param $datax- the month data to display in the x-axis
	* Portions created by SugarCRM are Copyright (C) SugarCRM, Inc..
	* All Rights Reserved..
	* Contributor(s): ______________________________________..
	*/	
	function campaign_response_by_activity_type($datay= array(),$targets=array(),$campaign_id, $cache_file_name='a_file', $refresh=false, $marketing_id='') {
		global $app_strings, $mod_strings, $charset, $lang, $barChartColors,$app_list_strings;
	
		if (!file_exists($cache_file_name) || $refresh == true) {
			$GLOBALS['log']->debug("datay is:");
			$GLOBALS['log']->debug($datay);
			$GLOBALS['log']->debug("user_id is: ");
			$GLOBALS['log']->debug("cache_file_name is: $cache_file_name");

			$focus = new Campaign();
					
			$query = "SELECT activity_type,target_type, count(*) hits ";
			$query.= " FROM campaign_log ";
			$query.= " WHERE campaign_id = '$campaign_id' AND archived=0 AND deleted=0";
            //if $marketing id is specified, then lets filter the chart by the value
            if (!empty($marketing_id)){
                $query.= " AND marketing_id ='$marketing_id'"; 
            }            

			$query.= " GROUP BY  activity_type, target_type";
			$query.= " ORDER BY  activity_type, target_type";
			
			$result = $focus->db->query($query);

			$leadSourceArr = array();
			$total=0;
			$total_targeted=0;
			while($row = $focus->db->fetchByAssoc($result, -1, false))
			{
				if(!isset($leadSourceArr[$row['activity_type']]['row_total'])) {
					$leadSourceArr[$row['activity_type']]['row_total']=0;
				}
				
				$leadSourceArr[$row['activity_type']][$row['target_type']]['hits'][] = $row['hits'];
				$leadSourceArr[$row['activity_type']][$row['target_type']]['total'][] = $row['hits'];
				$leadSourceArr[$row['activity_type']]['outcome'][$row['target_type']]=$row['target_type'];
				$leadSourceArr[$row['activity_type']]['row_total'] += $row['hits'];

				if (!isset($leadSourceArr['all_activities'][$row['target_type']])) {
					$leadSourceArr['all_activities'][$row['target_type']]=array('total'=>0);
				}
				
				$leadSourceArr['all_activities'][$row['target_type']]['total'] += $row['hits'];

				$total += $row['hits'];
				if ($row['activity_type'] =='targeted') {
					$targeted[$row['target_type']]=$row['hits'];
					$total_targeted+=$row['hits'];
				}
			}
			$fileContents = '     <yData defaultAltText="'.$mod_strings['LBL_ROLLOVER_VIEW'].'">'."\n";
			foreach ($datay as $key=>$translation) {
				if ($key == '') {
				
					/* longreach - removed - don't use translated value for key
					$key = $mod_strings['NTC_NO_LEGENDS'];
					*/
					
					$translation = $mod_strings['NTC_NO_LEGENDS'];
				}
				if(!isset($leadSourceArr[$key])){
					$leadSourceArr[$key] = $key;
					
					// longreach - added
					$leadSourceArr[$key] = array('row_total' => 0, 'outcome' => array());
				}
				if(isset($leadSourceArr[$key]['row_total'])){$rowTotalArr[]=$leadSourceArr[$key]['row_total'];}
				if(isset($leadSourceArr[$key]['row_total']) && $leadSourceArr[$key]['row_total']>100){
					$leadSourceArr[$key]['row_total'] = round($leadSourceArr[$key]['row_total']);
				}
				$fileContents .= '          <dataRow title="'.$translation.'" endLabel="'.$leadSourceArr[$key]['row_total'].'">'."\n";
				if(is_array($leadSourceArr[$key]['outcome'])){
					foreach ($leadSourceArr[$key]['outcome'] as $outcome=>$outcome_translation){
						//create alternate text.
                        $alttext = ' ';
                        if(isset($targeted) && !empty($targeted)){
							$alttext=$targets[$outcome].': '.$mod_strings['LBL_TARGETED'].' '.$targeted[$outcome]. ', '.$mod_strings['LBL_TOTAL_TARGETED'].' '. $total_targeted. ".";
						}
						if ($key != 'targeted'){
							$alttext.=" $translation ". array_sum($leadSourceArr[$key][$outcome]['hits']);
						}
						$fileContents .= '               <bar id="'.$outcome.'" totalSize="'.array_sum($leadSourceArr[$key][$outcome]['total']).'" altText="'.$alttext.'" url="#'.$key.'"/>'."\n";
					}
				}
				$fileContents .= '          </dataRow>'."\n";
			}
			$fileContents .= '     </yData>'."\n";
			$max = get_max($rowTotalArr);
			$fileContents .= '     <xData min="0" max="'.$max.'" length="10" prefix="'.''.'" suffix=""/>'."\n";
			$fileContents .= '     <colorLegend status="on">'."\n";
			$i=0;

			foreach ($targets as $outcome=>$outcome_translation) {
				$color = generate_graphcolor($outcome,$i);
				$fileContents .= '          <mapping id="'.$outcome.'" name="'.$outcome_translation.'" color="'.$color.'"/>'."\n";
				$i++;
			}
			$fileContents .= '     </colorLegend>'."\n";
			$fileContents .= '     <graphInfo>'."\n";
			$fileContents .= '          <![CDATA['.' '.']]>'."\n";
			$fileContents .= '     </graphInfo>'."\n";
			$fileContents .= '     <chartColors ';
			foreach ($barChartColors as $key => $value) {
				$fileContents .= ' '.$key.'='.'"'.$value.'" ';
			}
			$fileContents .= ' />'."\n";
			$fileContents .= '</graphData>'."\n";
			$total = round($total, 2);
			$title = '<graphData title="'.$mod_strings['LBL_CAMPAIGN_RESPONSE_BY_RECIPIENT_ACTIVITY'].'">'."\n";
			$fileContents = $title.$fileContents;

			save_xml_file($cache_file_name, $fileContents);
		}
		$return = create_chart('hBarF',$cache_file_name);
		return $return;
	}
	//campaign roi compputations
function campaign_response_roi($datay= array(),$targets=array(),$campaign_id, $cache_file_name='a_file', $refresh=false,$marketing_id='') {
		global $app_strings,$mod_strings, $current_module_strings, $charset, $lang, $barChartColors,$app_list_strings;
		if (!file_exists($cache_file_name) || $refresh == true) {
			$GLOBALS['log']->debug("datay is:");
			$GLOBALS['log']->debug($datay);
			$GLOBALS['log']->debug("user_id is: ");
			$GLOBALS['log']->debug("cache_file_name is: $cache_file_name");
            
			$focus = new Campaign();
			$opp_count=0;
			$opp_query  = "select count(*) opp_count,sum(" . db_convert("amount_usdollar","IFNULL",array(0)).")  total_value";	           
            $opp_query .= " from opportunities";
            $opp_query .= " where campaign_id='$campaign_id'";
            $opp_query .= " and sales_stage='Prospecting'";
            $opp_query .= " and deleted=0";
                                    
            $opp_result=$focus->db->query($opp_query);
            $opp_data=$focus->db->fetchByAssoc($opp_result);
            if (empty($opp_data['opp_count'])) $opp_data['opp_count']=0;
            if (empty($opp_data['total_value'])) $opp_data['total_value']=0;
            
            //report query
			$opp_query1  = "select camp.name, count(*) opp_count,SUM(opp.amount) as Revenue";	           
            $opp_query1 .= " from opportunities opp";
            $opp_query1 .= " right join campaigns camp on camp.id = opp.campaign_id";
            $opp_query1 .= " where opp.sales_stage = 'Closed Won'and camp.id='$campaign_id' and opp.deleted=0";
            $opp_query1 .= " group by camp.name";

            $opp_result1=$focus->db->query($opp_query1);              
            $opp_data1=$focus->db->fetchByAssoc($opp_result1);

            if (empty($opp_data1['opp_count'])) $opp_data1['opp_count']= 0 ;
			if (empty($opp_data1['Revenue'])) $opp_data1['Revenue']= 0 ;

			$camp_query1  = "select camp.name, SUM(camp.actual_cost) as investment,SUM(camp.budget) as budget,SUM(camp.expected_revenue) as expected_revenue";                             	           
            $camp_query1 .= " from campaigns camp";            
            $camp_query1 .= " where camp.id='$campaign_id'";
            $camp_query1 .= " group by camp.name";
            
            $camp_result1=$focus->db->query($camp_query1);              
            $camp_data1=$focus->db->fetchByAssoc($camp_result1);
            
			if (empty($camp_data1['investment'])) $camp_data1['investment']= 0 ;
			if (empty($camp_data1['budget'])) $camp_data1['budget']= 0 ;
            if (empty($camp_data1['expected_revenue'])) $camp_data1['expected_revenue']= 0 ;
             
            $opp_data1['Investment']=$camp_data1['investment'];
	        $opp_data1['Budget']=$camp_data1['budget'];
	        $opp_data1['Expected_Revenue']=$camp_data1['expected_revenue'];	
            
			$query = "SELECT activity_type,target_type, count(*) hits ";
			$query.= " FROM campaign_log ";
			$query.= " WHERE campaign_id = '$campaign_id' AND archived=0 AND deleted=0";
            //if $marketing id is specified, then lets filter the chart by the value
            if (!empty($marketing_id)){
                $query.= " AND marketing_id ='$marketing_id'"; 
            }            
			$query.= " GROUP BY  activity_type, target_type";
			$query.= " ORDER BY  activity_type, target_type";
			$result = $focus->db->query($query);

			$leadSourceArr = array();
			$total=0;
			$total_targeted=0;
			
			while($row = $focus->db->fetchByAssoc($result, -1, false))
			{
				if(!isset($leadSourceArr[$row['activity_type']]['row_total'])) {
					$leadSourceArr[$row['activity_type']]['row_total']=0;
				}
				
				$leadSourceArr[$row['activity_type']][$row['target_type']]['hits'][] = $row['hits'];
				$leadSourceArr[$row['activity_type']][$row['target_type']]['total'][] = $row['hits'];
				$leadSourceArr[$row['activity_type']]['outcome'][$row['target_type']]=$row['target_type'];
				$leadSourceArr[$row['activity_type']]['row_total'] += $row['hits'];

				if (!isset($leadSourceArr['all_activities'][$row['target_type']])) {
					$leadSourceArr['all_activities'][$row['target_type']]=array('total'=>0);
				}
				
				$leadSourceArr['all_activities'][$row['target_type']]['total'] += $row['hits'];

				$total += $row['hits'];
				if ($row['activity_type'] =='targeted') {
					$targeted[$row['target_type']]=$row['hits'];
					$total_targeted+=$row['hits'];
				}
			}			
			
			$rev = $opp_data1['Revenue'];
			$inv = $opp_data1['Investment'];
			$bud = $opp_data1['Budget'];
			$exp_rev = $opp_data1['Expected_Revenue'];
			$scale=0;
			if(($rev > 0 && $rev > $inv) or $rev=$inv){
				$scale = $rev;
			}
			else if($rev<$inv){
				$scale= $inv;
			}
			if($bud >0 && $bud>$scale){
				$scale =$bud;
			}
			if($exp_rev >0 && $exp_rev > $scale){
			 $scale = $exp_rev; 	
			}
			
			$max= 80;
			
			if($scale >0){
			 $max = $scale+$scale*0.2;
		    }
			
			
		    $maxy = 10; 
			
			$fileContents = '     <yData min="0" max="'.$maxy.'" length="10" defaultAltText="'.'Rollover a bar to view details.'.'">'."\n";			
			foreach ($datay as $key=>$translation) {
				if ($key == '') {
					$key = $current_module_strings['NTC_NO_LEGENDS'];
					$translation = $current_module_strings['NTC_NO_LEGENDS'];
				}
				if(!isset($leadSourceArr[$key])){
					$leadSourceArr[$key] = $key;
				}
				
				if(isset($leadSourceArr[$key]['row_total'])){$rowTotalArr[]=$leadSourceArr[$key]['row_total'];}
				if(isset($leadSourceArr[$key]['row_total']) && $leadSourceArr[$key]['row_total']>100){
					$leadSourceArr[$key]['row_total'] = round($leadSourceArr[$key]['row_total']);
				}
				$fileContents .= '          <dataRow title="'.$translation.'" endLabel="'.$leadSourceArr[$key]['row_total'].'">'."\n";
				
				//_ppd(is_array($leadSourceArr[$key]['outcome']));
				if(is_array($leadSourceArr[$key]['outcome'])){
					foreach ($leadSourceArr[$key]['outcome'] as $outcome=>$outcome_translation){								
						//create alternate text.
						/*
						$alttext=$targets[$outcome].': Targeted '.$targeted[$outcome]. ', Total Targeted '. $total_targeted. ".";
						if ($key != 'targeted'){
							$alttext.=" $translation ". $opp_data[$leadSourceArr[$key]];
						}
						*/									
						$fileContents .= '               <bar id="'.$outcome.'" totalSize="'.$opp_data1[$leadSourceArr[$key]].'" altText="'.$opp_data[$leadSourceArr[$key]].'" url="#'.$key.'"/>'."\n";
					}
				}
				
				$fileContents .= '               <bar id="'.$leadSourceArr[$key].'" totalSize="'.$opp_data1[$leadSourceArr[$key]].'" altText="'.$opp_data1[$leadSourceArr[$key]].'" url="#'.$key.'"/>'."\n";
				$fileContents .= '          </dataRow>'."\n";
			}
			
			
			$fileContents .= '     </yData>'."\n";
			//$max = get_max($rowTotalArr);
			$fileContents .= '     <xData min="0" max="'.$max.'" length="10" prefix="'.''.'" suffix=""/>'."\n";
					
			$fileContents .= '     <colorLegend status="on">'."\n";
			$i=0;
          
			foreach ($targets as $outcome=>$outcome_translation) {
				$color = generate_graphcolor($outcome,$i);
				$fileContents .= '          <mapping id="'.$outcome.'" name="'.$outcome_translation.'" color="'.$color.'"/>'."\n";
				$i++;
			}
			
			$fileContents .= '     </colorLegend>'."\n";
			
			$fileContents .= '     <graphInfo>'."\n";
			$fileContents .= '          <![CDATA['.' '.']]>'."\n";
			$fileContents .= '     </graphInfo>'."\n";
			$fileContents .= '     <chartColors ';
			
			foreach ($barChartColors as $key => $value) {
				$fileContents .= ' '.$key.'='.'"'.$value.'" ';
			}
			
			$fileContents .= ' />'."\n";
			$fileContents .= '</graphData>'."\n";
			
			
			$total = round($total, 2);
			$title = '<graphData title="'.$mod_strings['LBL_CAMPAIGN_RETURN_ON_INVESTMENT'].'                                                                                                            ">'."\n";			
			$fileContents = $title.$fileContents;
                   
			save_xml_file($cache_file_name, $fileContents);
		}
		
            $width = "900";
            $height = "400";                        
		$return = create_chart('vBarF',$cache_file_name,$width,$height);
		return $return;
	}
}// end charts class
?>
