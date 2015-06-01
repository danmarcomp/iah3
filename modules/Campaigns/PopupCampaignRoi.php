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

 * Description:  TODO: To be written.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/

require_once('XTemplate/xtpl.php');
require_once('data/Tracker.php');
require_once('modules/Campaigns/Campaign.php');
require_once('modules/Campaigns/Forms.php');
require_once('include/DetailView/DetailView.php');
require_once('modules/Campaigns/Charts.php');


global $mod_strings;
global $app_strings;
global $app_list_strings;

/*
echo "\n<p>\n";
echo get_module_title($mod_strings['LBL_MODULE_NAME'], $mod_strings['LBL_NEWSLETTER'].": ".$focus->name, true);
echo "\n</p>\n";
*/
global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";
require_once($theme_path.'layout_utils.php');


$GLOBALS['log']->info("Campaign detail view");

$xtpl=new XTemplate ('modules/Campaigns/PopupCampaignRoi.html');

//_pp($_REQUEST['id']);
$campaign_id=$_REQUEST['id'];
$campaign = new Campaign();
$opp_query1  = "select camp.name, camp.actual_cost,camp.budget,camp.expected_revenue,count(*) opp_count,SUM(opp.amount) as Revenue, SUM(camp.actual_cost) as Investment, 
                            ROUND((SUM(opp.amount) - SUM(camp.actual_cost))/(SUM(camp.actual_cost)), 2)*100 as ROI";	           
            $opp_query1 .= " from opportunities opp";
            $opp_query1 .= " right join campaigns camp on camp.id = opp.campaign_id";
            $opp_query1 .= " where opp.sales_stage = 'Closed Won' and camp.id='$campaign_id'";
            $opp_query1 .= " group by camp.name";
            //$opp_query1 .= " and deleted=0";                                  
            $opp_result1=$campaign->db->query($opp_query1);              
            $opp_data1=$campaign->db->fetchByAssoc($opp_result1);
 //get the click-throughs
 $query_click = "SELECT count(*) hits ";
			$query_click.= " FROM campaign_log ";
			$query_click.= " WHERE campaign_id = '$campaign_id' AND activity_type='link' AND related_type='CampaignTrackers' AND archived=0 AND deleted=0";

            //if $marketing id is specified, then lets filter the chart by the value
            if (!empty($marketing_id)){
                $query_click.= " AND marketing_id ='$marketing_id'"; 
            }            

			$query_click.= " GROUP BY  activity_type, target_type";
			$query_click.= " ORDER BY  activity_type, target_type";
			$result = $campaign->db->query($query_click);
            
            
  $xtpl->assign("OPP_COUNT", $opp_data1['opp_count']);    
  $xtpl->assign("ACTUAL_COST",$opp_data1['actual_cost']);
  $xtpl->assign("PLANNED_BUDGET",$opp_data1['budget']);
  $xtpl->assign("EXPECTED_REVENUE",$opp_data1['expected_revenue']);
         
           

require_once('modules/Currencies/Currency.php');
	$currency  = new Currency();
if(isset($focus->currency_id) && !empty($focus->currency_id))
{
	$currency->retrieve($focus->currency_id);
	if( $currency->deleted != 1){
		$xtpl->assign("CURRENCY", $currency->iso4217 .' '.$currency->symbol );
	}else $xtpl->assign("CURRENCY", $currency->getDefaultISO4217() .' '.$currency->getDefaultCurrencySymbol() );
}else{

	$xtpl->assign("CURRENCY", $currency->getDefaultISO4217() .' '.$currency->getDefaultCurrencySymbol() );

}

global $current_user;
if(is_admin($current_user) && $_REQUEST['module'] != 'DynamicLayout' && !empty($_SESSION['editinplace'])){

	$xtpl->assign("ADMIN_EDIT","<a href='index.php?action=index&module=DynamicLayout&from_action=".$_REQUEST['action'] ."&from_module=".$_REQUEST['module'] ."&record=".$_REQUEST['record']. "'>".get_image($image_path."EditLayout","border='0' alt='Edit Layout' align='bottom'")."</a>");
}

//$detailView->processListNavigation($xtpl, "CAMPAIGN", $offset, $focus->is_AuditEnabled());
// adding custom fields:
//require_once('modules/DynamicFields/templates/Files/DetailView.php');





/* we need to build the dropdown of related marketing values
    $latest_marketing_id = '';
    $selected_marketing_id = '';
    if(isset($_REQUEST['mkt_id'])) $selected_marketing_id = $_REQUEST['mkt_id'];
    $options_str = '<option value="all">--None--</option>';
    //query for all email marketing records related to this campaign
    $latest_marketing_query = "select id, name, date_modified from email_marketing where campaign_id = '$focus->id' order by date_modified desc";
    
    //build string with value(s) retrieved
    $result =$campaign->db->query($latest_marketing_query);
    if ($row = $campaign->db->fetchByAssoc($result)){
        //first, populated the latest marketing id variable, as this
        // variable will be used to build chart and subpanels
        $latest_marketing_id = $row['id'];
        //fill in first option value
        $options_str .= '<option value="'. $row['id'] .'"';
        // if the marketing id is same as selected marketing id, set this option to render as "selected"
        if (!empty($selected_marketing_id) && $selected_marketing_id == $row['id']) {
            $options_str .=' selected>'. $row['name'] .'</option>';
        // if the marketing id is empty then set this first option to render as "selected"
        }elseif(empty($selected_marketing_id)){
            $options_str .=' selected>'. $row['name'] .'</option>';
        // if the marketing is not empty, but not same as selected marketing id, then..
        //.. do not set this option to render as "selected"            
        }else{
            $options_str .='>'. $row['name'] .'</option>';
        }
    }
    //process rest of records, if they exist
    while ($row = $campaign->db->fetchByAssoc($result)){
        //add to list of option values
        $options_str .= '<option value="'. $row['id'] .'"';
        //if the marketing id is same as selected marketing id, then set this option to render as "selected"
        if (!empty($selected_marketing_id) && $selected_marketing_id == $row['id']) {
            $options_str .=' selected>'. $row['name'] .'</option>';
        }else{
            $options_str .=' >'. $row['name'] .'</option>';
        }    
     }
    //populate the dropdown    
    $xtpl->assign("MKT_DROP_DOWN",$options_str);
    
  */  

//add chart
$seps				= array("-", "/");
$dates				= array(date('Y-m-d'), date('Y-m-d'));
$dateFileNameSafe	= str_replace($seps, "_", $dates);
$cache_file_name_roi	= $current_user->getUserPrivGuid()."_campaign_response_by_roi_".$dateFileNameSafe[0]."_".$dateFileNameSafe[1].".xml";
$chart= new charts();

//ob_start();

    //if marketing id has been selected, then set "latest_marketing_id" to the selected value
    //latest marketing id will be passed in to filter the charts and subpanels 
    
    if(!empty($selected_marketing_id)){$latest_marketing_id = $selected_marketing_id;}
    if(empty($latest_marketing_id) ||  $latest_marketing_id === 'all'){        
        $xtpl->assign("MY_CHART_ROI", $chart->campaign_response_roi_popup($app_list_strings['roi_type_dom'],$app_list_strings['roi_type_dom'],$campaign_id,AppConfig::setting('site.paths.xml_dir').$cache_file_name_roi,true));
    }else{
    	
    $xtpl->assign("MY_CHART_ROI", $chart->campaign_response_roi_popup($app_list_strings['roi_type_dom'],$app_list_strings['roi_type_dom'],$campaign_id,AppConfig::setting('site.paths.xml_dir').$cache_file_name_roi,true));                
    }
    
//$output_html .= ob_get_contents();
//ob_end_clean();


//_ppd($xtpl);
//end chart

$xtpl->parse("main");
$xtpl->out("main");

?>
