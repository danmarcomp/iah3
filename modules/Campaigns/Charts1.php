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
//todo: experimental class for chart data handling..not used in the application at this time.

require_once('modules/Campaigns/Campaign.php');
require_once('include/charts/Charts.php');
require_once('include/utils.php');
require_once('XTemplate/xtpl.php');


class charts {
    
    /* @function: 
     * 
     * @param array targets: translated list of all activity types, targeted, bounced etc..
     * @param string campaign_id: chart for this campaign.
     */
    function campaign_response_chart($targets,$campaign_id) {

        $focus = new Campaign();
        $leadSourceArr = array();
                    
        $query = "SELECT activity_type,target_type, count(*) hits ";
        $query.= " FROM campaign_log ";
        $query.= " WHERE campaign_id = '$campaign_id' AND archived=0 AND deleted=0";
        $query.= " GROUP BY  activity_type, target_type";
        $query.= " ORDER BY  activity_type, target_type";
            
        $result = $focus->db->query($query);
        while($row = $focus->db->fetchByAssoc($result, -1, false)) {
            
            if (isset($leadSourceArr[$row['activity_type']]['value'])) {
                $leadSourceArr[$row['activity_type']]['value']=0;
            }
            
            $leadSourceArr[$row['activity_type']]['value']=  $leadSourceArr[$row['activity_type']]['value'] + $row['hits'];
            
            if (!empty($row['target_type'])) {
                $leadSourceArr[$row['activity_type']]['bars'][$row['target_type']]['value']=$row['hits'];
            }
        }

        foreach ($targets as $key=>$value) {
            if (!isset($leadSourceArr[$key])) {
                $leadSourceArr[$key]['value']=0;
            }
        }
        
        //use the new template.
        $xtpl=new XTemplate ('modules/Campaigns/chart.tpl');
        $xtpl->assign("GRAPHTITLE",'Campaign Response by Recipient Activity');
        $xtpl->assign("Y_DEFAULT_ALT_TEXT",'Rollover a bar to view details.');
    
        //process rows
        foreach ($leadSourceArr as $key=>$values) {
            if (isset($values['bars'])) {
                foreach ($values['bars'] as $bar_id=>$bar_value) {
                    $xpl->assign("Y_BAR_ID",$bar_id);
                }   
            }
            
        }
    }
    }// end charts class
?>
    
