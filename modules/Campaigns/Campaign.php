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

 * Description:
 ********************************************************************************/




require_once('data/SugarBean.php');


class Campaign extends SugarBean {
	var $field_name_map;
	
	// Stored fields
	var $id;
	var $date_entered;
	var $date_modified;
	var $modified_user_id;
	var $assigned_user_id;
	var $created_by;
	var $created_by_name;
	var $modified_by_name;




	var $name;
	var $start_date;
	var $end_date;
	var $status;
	var $expected_cost;
	var $budget;
	var $actual_cost;
	var $expected_revenue;
	var $campaign_type;
	var $objective;
	var $content;
	var $tracker_key;
	var $tracker_text;
	var $tracker_count;
	var $refer_url;
	var $impressions;
	
	// These are related
	var $assigned_user_name;

	// module name definitions and table relations
	var $table_name = "campaigns";
	var $rel_prospect_list_table = "prospect_list_campaigns";
	var $object_name = "Campaign";
	var $module_dir = 'Campaigns';

    
	var $new_schema = true;

	
	function update_currency_id($fromid, $toid){
	}


	function set_notification_body($xtpl, $camp)
	{
		$xtpl->assign("CAMPAIGN_NAME", $camp->name);
		$xtpl->assign("CAMPAIGN_AMOUNT", $camp->budget);
		$xtpl->assign("CAMPAIGN_CLOSEDATE", $camp->end_date);
		$xtpl->assign("CAMPAIGN_STATUS", $camp->status);
		$xtpl->assign("CAMPAIGN_DESCRIPTION", $camp->content);

		return $xtpl;
	}

	function track_log_entries($type=array()) {
        //get arguments being passed in
        $args = func_get_args();
        $mkt_id ='';
        //if one of the arguments is marketing ID, then we need to filter by it
        foreach($args as $arg){
            if(isset($arg['EMAIL_MARKETING_ID_VALUE'])){
                $mkt_id = $arg['EMAIL_MARKETING_ID_VALUE'];
            }
        }
		if (empty($type)) 
			$type[0]='targeted';
		$this->load_relationship('log_entries');
		$query_array = $this->log_entries->getQuery(true);
		$query_array['select'] ="SELECT campaign_log.* ";
		$query_array['where'] = $query_array['where']. " AND activity_type='{$type[0]}' AND archived=0";
        //add filtering by marketing id, if it exists
        if (!empty($mkt_id)) $query_array['where'] = $query_array['where']. " AND marketing_id ='$mkt_id' ";
		return (implode(" ",$query_array));
	}
	function get_queue_items() {
        //get arguments being passed in
        $args = func_get_args();
        $mkt_id ='';
        //if one of the arguments is marketing ID, then we need to filter by it
        foreach($args as $arg){
            if(isset($arg['EMAIL_MARKETING_ID_VALUE'])){
                $mkt_id = $arg['EMAIL_MARKETING_ID_VALUE'];
            }
        }

		$this->load_relationship('queueitems');
		$query_array = $this->queueitems->getQuery(true);
        //add filtering by marketing id, if it exists, and if where key is not empty
        if (!empty($mkt_id) && !empty($query_array['where'])){
             $query_array['where'] = $query_array['where']. " AND marketing_id ='$mkt_id' ";
        }
		//get select query from email man.
		require_once('modules/EmailMan/EmailMan.php');
		$man = new EmailMan();
		$listquery= $man->create_list_query('',str_replace(array("WHERE","where"),"",$query_array['where']));	
		return ($listquery);
		
	}


    static function get_opportunities_won($spec) {
        $won = 0;

        if (isset($spec['raw_values']['id'])) {
            global $db;

            $opp_query  = "SELECT camp.name, COUNT(*) opp_count, SUM(opp.amount) AS Revenue, SUM(camp.actual_cost) AS Investment,
                ROUND((SUM(opp.amount) - SUM(camp.actual_cost)) / (SUM(camp.actual_cost)), 2) * 100 AS ROI
                FROM opportunities opp
                RIGHT JOIN campaigns camp ON camp.id = opp.campaign_id
                WHERE opp.sales_stage = 'Closed Won' AND camp.id = '" .$spec['raw_values']['id']. "'
                AND opp.deleted=0
                GROUP BY camp.name";

            $opp_result = $db->query($opp_query);
            $opp_data = $db->fetchByAssoc($opp_result);
            if (! empty($opp_data['opp_count']))
                $won = $opp_data['opp_count'];
        }

        return $won;
    }

    static function get_cost_per_impression($spec) {
        $cost = 0;

        if (isset($spec['raw_values']['impressions']) && $spec['raw_values']['impressions'] > 0) {
            $actual_cost = 0;

            if (! empty($spec['raw_values']['actual_cost']))
                $actual_cost = $spec['raw_values']['actual_cost'];

            $cost = $actual_cost / $spec['raw_values']['impressions'];
        }

        $cost = format_number($cost);
        
        return $cost;
    }

    static function get_cost_per_click($spec) {
        $cost = 0;

        if (isset($spec['raw_values']['id'])) {
            global $db;

            $log_query  = "SELECT camp.name, COUNT(*) click_thru_link
                FROM campaign_log camp_log
                RIGHT JOIN campaigns camp ON camp.id = camp_log.campaign_id
                WHERE camp_log.activity_type = 'link' AND camp.id='" .$spec['raw_values']['id']. "'
                AND camp.deleted = 0
                GROUP BY camp.name";

            $log_result = $db->query($log_query);
            $log_data = $db->fetchByAssoc($log_result);

            $clicks = 0;
            if (! empty($log_data['click_thru_link']))
                $clicks = $log_data['click_thru_link'];

            if ($clicks > 0) {
                $actual_cost = 0;

                if (! empty($spec['raw_values']['actual_cost']))
                    $actual_cost = $spec['raw_values']['actual_cost'];

                $cost = $actual_cost / $clicks;
            }

        }
        
        $cost = format_number($cost);

        return $cost;
    }

    static function send_notification(RowUpdate $upd) {
        $vars = array(
            'CAMPAIGN_NAME' => array('field' => 'name', 'in_subject' => true),
            'CAMPAIGN_AMOUNT' => array('field' => 'budget'),
            'CAMPAIGN_CLOSEDATE' => array('field' => 'end_date'),
            'CAMPAIGN_STATUS' => array('field' => 'status'),
            'CAMPAIGN_DESCRIPTION' => array('field' => 'content')
        );

        $manager = new NotificationManager($upd, 'CampaignAssigned', $vars);

        if ($manager->wasRecordReassigned())
            $manager->sendMails();
    }

	static function get_campaign_mailboxes(&$emails)
	{
		$return_array = array();
		require_once 'include/database/ListQuery.php';
		$lq = new ListQuery('EmailPOP3');
		$lq->addFilterClause(array('field' => 'user_id', 'value' => '-1'));
		$lq->addFilterClause(array('field' => 'mailbox_type', 'value' => 'bounce'));
		$result = $lq->runQuery();
		foreach($result->getRowIndexes() as $idx) {
			$row = $result->getRowResult($idx);
			$return_array[$row->getField('id')] = $row->getField('name');
			$emails[$row->getField('id')] = $row->getField('email');
		}
		return $return_array;
	}

	static function listupdate_perform($mu, $perform, &$listFmt, &$list_result, $uids)
	{
		if ($perform == 'QuickCampaign') {

			global $current_user;

			$date_now = gmdate("Y-m-d H:i:s");
			$dateAndTime = explode(' ', $date_now);
			$template_id = $_REQUEST['template_id'];
			$mailbox_id = $_REQUEST['mailbox_id'];
			require_bean('EmailTemplate');
			$tpl = new EmailTemplate;
			$tpl->retrieve($template_id);
			$template_name =  $tpl->name;	

			require_once 'include/database/RowUpdate.php';
			// create prospects list
			$list = new RowUpdate('ProspectList');
			$list->set(array(
				'name' => '*** ' . $template_name . ' ' .$dateAndTime[0],
				'assigned_user_id' => AppConfig::current_user_id(),
				'list_type' => 'default',
				'status' => 'Complete',
				'end_date' => $date_now,
			));
			$list->insertRow();
			$params = array();
			$uids = array();
			while(! $list_result->failed) {
				foreach($list_result->getRowIndexes() as $idx) {
					$row = $list_result->getRowResult($idx);
					$id = $row->getField('id');
					$params[$id] = array('name' => 'related_type', 'value' => $list_result->module_dirs[0]);
					$uids[] = $id;
				}
				if($list_result->page_finished)
					break;
				$listFmt->pageResult($list_result, true);
			}
			$list->addUpdateLinks('prospects', $uids, $params);
			$listId = $list->getPrimaryKeyValue();

			$campaign = new RowUpdate('Campaign');
			$campaign->set(array(
				'name' => '*** ' . $template_name . ' ' .$dateAndTime[0],
				'assigned_user_id' => AppConfig::current_user_id(),
				'campaign_type' => 'Email',
				'status' => 'Complete',
				'start_date' => $date_now,
				'end_date' => $date_now,
			));
			 
			$campaign->insertRow();
			$campaignId = $campaign->getPrimaryKeyValue();
			$campaign->addUpdateLink('prospectlists', $listId);

			$marketing = new RowUpdate('EmailMarketing');
			$marketing->set(array(
				'name' => '*** ' . $template_name . ' ' .$dateAndTime[0],
				'from_name' => $current_user->name,
				'template_id' => $template_id,
				'inbound_email_id' => $mailbox_id,
				'date_start' => $dateAndTime[0],
				'time_start' => $dateAndTime[1],
				'status' => 'active',
				'campaign_id' => $campaignId,
				'all_prospect_lists' => 1,
			));
			$marketing->insertRow();
			$marketingId = $marketing->getPrimaryKeyValue();

			$data = array(
				'module' => 'Campaigns',
				'action' => 'QueueCampaign',
				'record' => $campaignId,
				'mass' => $marketingId,
			);
			 
			return array('perform', $data);
		}
	}

}
?>
