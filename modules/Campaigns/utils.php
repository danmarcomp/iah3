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

require_once 'include/database/ListQuery.php';
require_once 'include/database/RowResult.php';
require_once 'include/database/RowUpdate.php';

class CampaignUtils
{
	/*
	 *returns a list of objects a message can be scoped by, the list contacts the current campaign
	 *name and list of all prospects associated with this campaign..
	 *
	 */
	static public function get_message_scope_dom($campaign_id)
	{
		//find prospect list attached to this campaign..
		$lq = new ListQuery('Campaign', array('id', 'name'), array('link_name' => 'prospectlists'));
		$lq->setParentKey($campaign_id);
		$lq->addSimpleFilter('list_type', 'exempt%', 'NOT LIKE');
		$rows = $lq->fetchAllRows();

		$return_array=array();
		if ($rows) foreach ($rows as $row) {
			$return_array[$row['id']] = $row['name'];
		}
		return $return_array;
	}
	static public function get_user_mailboxes()
	{
		$lq = new ListQuery('EmailPOP3');
		$lq->addSimpleFilter('mailbox_type', 'bounce');
		return $lq->fetchAllRows();
	}

	// AD - this seems to be unused?
	public static function get_campaign_urls($campaign_id)
	{
		$return_array=array();
		if (!empty($campaign_id)) {
			$lq = new ListQuery('CampaignTracker', array('id', 'tracker_url', 'tracker_name'));
			$lq->addSimpleFilter('campaign_id', $campaign_id);
			$rows = $lq->fetchAllRows();
			foreach ($rows as $row) {
				$return_array[$row['id'] . '~' . $row['tracker_url']] = $row['tracker_name'];
			}
		}
		return $return_array;
	}
	
	static private function group_prospect_lists($subs, $ret_ext=false)
	{
		$strings = return_module_language(null, 'Campaigns');
		$all = array();
		foreach($subs as $campaign_id => $lists) {
			foreach($lists as $row) {
				if(! isset($all[$row['id']]))
					$all[$row['id']] = array('name' => $row['name'], 'type' => $row['list_type'], 'campaigns' => array());
				$all[$row['id']]['campaigns'][] = array($campaign_id, $row['campaign_name']);
			}
		}
		if($ret_ext)
			return $all;
		foreach($all as $k => $v) {
			$nm = $v['name'];
			$camps = $v['campaigns'];
			if(count($camps) == 1) {
				if($camps[0][1].' '.$strings['LBL_SUBSCRIPTION_LIST'] == $nm)
					$nm = $camps[0][1];
				else if($camps[0][1].' '.$strings['LBL_TEST_LIST'] != $nm)
					$nm .= ' ('.$camps[0][1].')';
			} else {
				$nm .= str_replace('{N}', count($camps), $strings['LBL_MANAGE_LISTS_CAMPAIGNS_SUFFIX']);
			}
			$all[$k] = $nm;
		}
		return $all;
	}
	
	/*
	 * This function takes in a bean from a lead, propsect, or contact and returns
	 * an array containing all subscription lists that the bean is a part of, and
	 * all the subscriptions that the bean is not a part of.  The array elements
	 * have the key names of "subscribed" and "unsusbscribed".  These elements
	 * contain an array of the corresponding list.  In other words, the "subscribed"
	 * element holds another array that holds the subscription information.
	 *
	 * The subscription information is a concatenated string that holds the prospect
	 * list id and the campaign id, seperated by at "@" character.
	 * To parse these information string into something more usable, use the
	 * "process subscriptions()" function
	 *
	 * */
	public static function get_subscription_lists($focus, $descriptions = false)
	{
		list($subs, $unsub, $optout) = self::get_subscription_lists_ext($focus, 'NewsLetter', '', $descriptions);

		$ret_subs = self::group_prospect_lists($subs);
		$ret_unsub = self::group_prospect_lists($unsub);
		$exempt = array();
		foreach($optout as $campaign_id => $row) {
			if($row['is_exempt'])
				$exempt[$campaign_id] = $row['name'];
		}

		return array('subscribed' => $ret_subs, 'unsubscribed' => $ret_unsub, 'exempt' => $exempt);
	}

	static private function get_sub_lists_query($focus, $campaign_type='', $campaign_id='', $additional_fields=null)
	{
		// AD - TODO - ListQuery should support such things
		$pl_sel = "SELECT DISTINCT pl.id, pl.name, plc.campaign_id, pl.list_type, plp.related_id as rel_id ";
		$pl_join = " FROM prospect_list_campaigns plc "
			. " LEFT JOIN prospect_lists pl ON plc.prospect_list_id=pl.id AND NOT pl.deleted "
			. " LEFT JOIN prospect_lists_prospects plp ON plp.prospect_list_id=pl.id AND plp.related_id='$focus->id'"
			. " AND NOT plp.deleted ";
		$pl_where = " WHERE NOT plc.deleted AND pl.id IS NOT NULL ";
		if($campaign_id)
			$pl_where .= " AND plc.campaign_id='$campaign_id' ";
		else {
			$pl_sel .= ", c.name AS campaign_name ";
			$pl_join .= " LEFT JOIN campaigns c ON c.id=plc.campaign_id ";
			$pl_where .= " AND NOT c.deleted AND (c.status != 'Inactive' AND c.status != 'Complete') ";
			if($campaign_type)
				$pl_where .= " AND c.campaign_type='$campaign_type' ";
		}
		if(is_array($additional_fields) && count($additional_fields))
			$pl_sel .= ', ' . implode(', ', $additional_fields);
		return $pl_sel . $pl_join . $pl_where;
	}

	public static function get_subscription_lists_ext($focus, $campaign_type='', $campaign_id='', $descriptions = false)
	{
		global $db;
		$subs = $unsubs = $optout = array();
		$pl_qry = self::get_sub_lists_query($focus, $campaign_type, $campaign_id, $descriptions);
		$result = $db->query($pl_qry);
		$exempt = array();
		while($row = $db->fetchByAssoc($result)) {
			$campaign_name = array_get_default($row, 'campaign_name', $row['name']);
			if($row['list_type'] == 'exempt') {
				$is_exempt = ! empty($row['rel_id']);
				$optout[$row['campaign_id']] = array(
					'name' => $campaign_name, 'id' => $row['id'], 'is_exempt' => $is_exempt,
				);
				if($is_exempt) $exempt[] = $row['campaign_id'];
			}
			else {
				$entry = array(
					'name' => $row['name'], 'campaign_name' => $campaign_name,
					'id' => $row['id'], 'list_type' => $row['list_type'],
				);
				if($row['rel_id'])
					$subs[$row['campaign_id']][] = $entry;
				else
					$unsubs[$row['campaign_id']][] = $entry;
			}
		}

		foreach($exempt as $campaign_id) {
			if(! empty($subs[$campaign_id]))
				unset($subs[$campaign_id]);
			if(! empty($unsubs[$campaign_id]))
				unset($unsubs[$campaign_id]);
		}

		return array($subs, $unsubs, $optout);
	}

	/*This function is used by the Manage Subscriptions page in order to add the user
	 * to the default prospect lists of the passed in campaign
	 * Takes in campaign and prospect list id's we are subscribing to.
	 * It also takes in a bean of the user (lead,target,prospect) we are subscribing
	 * */
	public static function subscribe_to_list(&$focus, $prospect_list) {
		require_once('modules/ProspectLists/ProspectList.php');
		$relationship = strtolower($focus->getObjectName()).'s';

		$list_result = ListQuery::quick_fetch('ProspectList', $prospect_list);
		$ret = false;
		if($list_result) {
			$list_update = RowUpdate::for_result($list_result);
			$list_update->addUpdateLink($relationship, $focus->id);
			$ret = true;
		}
		return $ret;
	}
	
	
	public static function remove_from_list(&$focus, $prospect_list) {
		require_once('modules/ProspectLists/ProspectList.php');
		$relationship = strtolower($focus->getObjectName()).'s';
		$subscription_list = new ProspectList();
		$ret = false;
		if($subscription_list->retrieve($prospect_list)) {
			$GLOBALS['log']->debug("In Campaigns Util, loading relationship: ".$relationship);
			$subscription_list->load_relationship($relationship);
			$subscription_list->$relationship->delete($prospect_list, $focus->id);
			$ret = true;
		}
		$subscription_list->cleanup();
		return $ret;
	}


	/*This function is used by the Manage Subscriptions page in order to add the user
	 * to the exempt prospect lists of the passed in campaign
	 * Takes in campaign and focus parameters.
	 * This function is also used in removeme.php to support the unsubscribe link in sent emails.
	 * */
	public static function unsubscribe($campaign_id, &$focus) {
		list($subs, $unsubs, $optout) = self::get_subscription_lists_ext($focus, '', $campaign_id);
		$unsub_list = '';
		foreach($optout as $cid => $row) {
			if(! empty($row['is_exempt']))
				return;
			$unsub_list = $row['id'];
		}
		if(! $unsub_list || ! self::subscribe_to_list($focus, $unsub_list)) {
			// missing unsubscription list, so just remove from any and all subscribed lists
			foreach($subs as $cid => $plists) {
				foreach ($plists as $plist) {
					self::remove_from_list($focus, $plist['id']);
				}
			}
		}
	}

    /*
     *This function will return a string to the newsletter wizard if campaign check
     *does not return 100% healthy.
     */
    static public function diagnose()
	{
		global $app_list_strings;
        global $mod_strings;
        global $db;
        $msg = " <table class='tabDetailView' width='100%'><tr><td> ".$mod_strings['LNK_CAMPAIGN_DIGNOSTIC_LINK']."</td></tr>";
        //Start with email components
        //monitored mailbox section

        //run query for mail boxes of type 'bounce'
		$email_health = 0;
		$lq = New ListQuery('EmailPOP3');
		$lq->addSimpleFilter('mailbox_type', 'bounce');
        $mboxes = $lq->fetchResultCount();

		if (empty($mboxes)) {
            $email_health++;
            $msg  .=  "<tr><td ><font color='red'><b>". $mod_strings['LBL_MAILBOX_CHECK1_BAD']."</b></font></td></tr>";
        }


        if (strstr(AppConfig::setting('notify.from_address'), 'example.com')){
            //if "from_address" is the default, then set "bad" message and increment health counter
            $email_health++;
            $msg .= "<tr><td ><font color='red'><b> ".$mod_strings['LBL_MAILBOX_CHECK2_BAD']." </b></font></td></tr>";
        }
        //if health counter is above 1, then show admin link
        if ($email_health) {
            if (AppConfig::is_admin()) {
                $msg.="<tr><td ><a href='index.php?module=Campaigns&action=WizardEmailSetup";
                if(isset($_REQUEST['return_module'])){
                    $msg.="&return_module=".$_REQUEST['return_module'];
                }
                if(isset($_REQUEST['return_action'])){
                    $msg.="&return_action=".$_REQUEST['return_action'];
                }
                $msg.="'>".$mod_strings['LBL_EMAIL_SETUP_WIZ']."</a></td></tr>";
            }else{
                $msg.="<tr><td >".$mod_strings['LBL_NON_ADMIN_ERROR_MSG']."</td></tr>";
            }
        }

        // proceed with scheduler components

		//create and run the scheduler queries
		$lq = new ListQuery('Schedule');
		$lq->addSimpleFilter('type', array('emailmandelivery', 'processbounces'));
		$scheds = $lq->fetchAllRows();

		$sched_health = 0;
		$check_sched1 = 'emailmandelivery';
		$check_sched2 = 'processbounces';

		//build the table rows for scheduler display
		foreach ($scheds as $funct) {
			if($funct['type'] == $check_sched1){
				$check_sched1 = "found";
			}else{
				$check_sched2 = "found";
			}
		}

        //determine if error messages need to be displayed for schedulers
        if($check_sched2 != 'found'){
            $sched_health++;
            $msg.= "<tr><td><font color='red'><b>".$mod_strings['LBL_SCHEDULER_CHECK1_BAD']."</b></font></td></tr>";
        }
        if ($check_sched1 != 'found'){
            $sched_health++;
            $msg.= "<tr><td><font color='red'><b>".$mod_strings['LBL_SCHEDULER_CHECK2_BAD']."</b></font></td></tr>";
        }
        //if health counter is above 1, then show admin link
        if ($sched_health){
            if (AppConfig::is_admin()){
                $msg.="<tr><td ><a href='index.php?module=Scheduler&action=index'>".$mod_strings['LBL_SCHEDULER_LINK']."</a></td></tr>";
            }else{
                $msg.="<tr><td >".$mod_strings['LBL_NON_ADMIN_ERROR_MSG']."</td></tr>";
            }

        }

        //determine whether message should be returned
        if($sched_health + $email_health){
            $msg  .= "</table> ";
        }else{
            $msg = '';
        }
        return $msg;
    }

}

class CampaignActivityTracker
{

	private static function update_hits_counter($log_id, $campaign_id = null)
	{
		// if $campaign_id is given, treat $log_id as target_tracker_key
		// otherwise $log_id is ID in campaign_log table
		global $db;
		$query = "UPDATE campaign_log SET hits = hits + 1 where id='{$log_id}'";
		$result = $db->query($query);
		return $db->getAffectedRowCount($result);
	}

	public static function log_campaign_activity($identifier, $activity, $update=true, $clicked_url_key=null) {
		global $db;
		$return_array = array();

		 //check to see if the identifier has been replaced with Banner string
		if($identifier == 'BANNER' && !empty($clicked_url_key))
		{
			$ip = query_client_ip();
			// create md5 encrypted string using the client ip, this will be used for tracker id purposes
			$enc_id = 'BNR'.md5($ip);

			//default the identifier to ip address
			$identifier = $enc_id;

			//if user has chosen to not use this mode of id generation, then replace identifier with plain guid.
			//difference is that guid will generate a new campaign log for EACH CLICK!!
			//encrypted generation will generate 1 campaign log and update the hit counter for each click
			if(AppConfig::setting('campaigns.banner_id_generation', 'md5')  != 'md5') {
				$identifier = create_guid();
			}

			//retrieve campaign log.
			$lq = new ListQuery('CampaignLog');
			$lq->addSimpleFilter('target_tracker_key', $identifier);
			$lq->addSimpleFilter('related_id', $clicked_url_key);
			$row = null;
			$res = $lq->runQuerySingle();
			if ($res)
				$row = $res->row;

			//if campaign log is not retrieved (this is a new ip address or we have chosen to create
			//unique entries for each click
			if(empty($row)) {
				//retrieve campaign id
				$row = ListQuery::quick_fetch_row('CampaignTracker', $clicked_url_key, array('campaign_id'));

				//create new campaign log with minimal info.  Note that we are creating new unique id
				//as target id, since we do not link banner/web campaigns to any users

				$data = array(
					'target_id' => create_guid(),
					'target_type'  =>  'Prospects',
					'campaign_id' => $row['campaign_id'],
					'target_tracker_key' => $identifier,
					'activity_type' => $activity,
					'activity_date' => gmdate("Y-m-d H:i:s"),
					'hits' => 1,
				);
				if (!empty($clicked_url_key)) {
					$data['related_id'] = $clicked_url_key;
					$data['related_type'] = 'CampaignTrackers';
				}

				//values for return array..
				$return_array['target_id'] = $data['target_id'];
				$return_array['target_type'] = $data['target_type'];

				//create insert query for new campaign log
				$update = RowUpdate::blank_for_model('CampaignLog');
				$update->set($data);
				$update->save();
			} else {
				//campaign log already exists, so just set the return array and update hits column
				$return_array['target_id'] = $row['target_id'];
				$return_array['target_type'] = $row['target_type'];
				self::update_hits_counter($row['id']);
			}
			//return array and exit
			return $return_array;

		}
		
		$lq = new ListQuery('CampaignLog');
		$lq->addSimpleFilter('target_tracker_key', $identifier);
		$lq->addSimpleFilter('activity_type', $activity);
		if (!empty($clicked_url_key)) {
			$lq->addSimpleFilter('related_id', $clicked_url_key);
		}
		$row = null;
		$res = $lq->runQuerySingle();
		if ($res)
			$row = $res->row;

		if (!$row) {
			$lq = new ListQuery('CampaignLog');
			$lq->addSimpleFilter('target_tracker_key', $identifier);
			$lq->addSimpleFilter('activity_type', 'targeted');
			$row = null;
			$res = $lq->runQuerySingle();
			if ($res)
				$row = $res->row;
			//if activity is removed and target type is users, then a user is trying to opt out
			//of emails.  This is not possible as Users Table does not have opt out column.
			if ($row  && (strtolower($row['target_type']) == 'users' &&  $activity == 'removed' )) {
				$return_array['target_id']= $row['target_id'];
				$return_array['target_type']= $row['target_type'];
				return $return_array;
			} elseif ($row) {
				$data = array(
					'campaign_id' => $row['campaign_id'],
					'target_tracker_key' => $identifier,
					'target_id' =>  $row['target_id'],
					'target_type' =>  $row['target_type'],
					'activity_type' =>  $activity,
					'activity_date' => gmdate("Y-m-d H:i:s"),
					'list_id' =>  $row['list_id'],
					'marketing_id' =>  $row['marketing_id'],
					'hits' => 1,
					'more_information' => $row['more_information'],
					'related_id' => $row['related_id'],
					'related_type' => $row['related_type'],
				);
				if (!empty($clicked_url_key)) {
					$data['related_id'] = $clicked_url_key;
					$data['related_type'] ='CampaignTrackers';
				}
				//values for return array..
				$return_array['target_id'] = $row['target_id'];
				$return_array['target_type'] = $row['target_type'];

				$update = RowUpdate::blank_for_model('CampaignLog');
				$update->set($data);
				$update->save();
			}
		} else {
			$return_array['target_id']= $row['target_id'];
			$return_array['target_type']= $row['target_type'];
			self::update_hits_counter($row['id']);
		}

		//check to see if this is a removal action
		if ($row  && $activity == 'removed' ) {
			//retrieve campaign and check it's type, we are looking for newsletter Campaigns
			$c_row = ListQuery::quick_fetch_row('Campaign', $row['campaign_id']);
			if($c_row) {
				//if type is newsletter, then add campaign id to return_array for further processing.
				if(array_get_default($c_row, 'campaign_type') == 'NewsLetter') {
					$return_array['campaign_id'] = $c_row['id'];
				}
			}
		}
		return $return_array;
	}

	public static function campaign_log_lead_entry($campaign_id, $target_id, $rel_id, $rel_type, $email, $activity_type) {
		global $db;
        $tracker_id = create_guid();
		//create new campaign log record.
		$update = RowUpdate::blank_for_model('CampaignLog');
		$data = array(
            'campaign_id' => $campaign_id,
            'target_tracker_key' => $tracker_id,
            'target_id' => $target_id,
            'related_id' => $rel_id,
            'target_type' => $rel_type,
			'activity_type' => $activity_type,
			'more_information' => $email,
		);
		$update->set($data);
        $update->save();
	}

	/**
	 * Handle campaign log entry creation for mail-merge activity. The function will be called by the soap component.
	 *
	 * @param String campaign_id Primary key of the campaign
	 * @param array targets List of keys for entries from prospect_lists_prosects table
	 */
	public static function campaign_log_mail_merge($campaign_id, $targets) {
		$campaign = ListQuery::quick_fetch($campaign_id);
		if (empty($campaign)) {
			$GLOBALS['log']->debug('set_campaign_merge: Invalid campaign id'. $campaign_id);
		} else {
			foreach ($targets as $target_list_id) {
				$plist = ListQuery::quick_fetch_row($target_list_id);
				if (!empty($plist)) {
					self::write_mail_merge_log_entry($campaign_id, $plist);
				}
			}
		}
	}
	
	/**
	 * Function creates a campaign_log entry for campaigns processesed using the mail-merge feature. If any entry
	 * exist the hit counter is updated. target_tracker_key is used to locate duplicate entries.
	 * @param string campaign_id Primary key of the campaign
	 * @param array $pl_row A row of data from prospect_lists_prospects table.
	 */
	private static function write_mail_merge_log_entry($campaign_id, $pl_row)
	{
		global $db;
		//Update the log entry if it exists.
		$count = self::update_hits_counter($pl_row['id'], $campaign_id);

		if (!$count) {
			$data=array(
				'id' => create_guid(),
				'campaign_id' => $campaign_id,
				'target_tracker_key' => $pl_row['id'],
				'target_id' =>  $pl_row['related_id'],
				'target_type' =>  $pl_row['related_type'],
				'activity_type' => "'targeted'",
				'activity_date' => gmdate("Y-m-d H:i:s"),
				'list_id' =>  $pl_row['prospect_list_id'],
				'hits' => 1,
			);
			$update = RowUpdate::blank_for_model('CampaignLog');
			$update->set($data);
			$update->save();
		}
	}

	public static function track_campaign_prospects($focus)
	{
        global $mod_strings;
		$lq = new ListQuery('Campaign', null, array('link_name' => 'prospectlists'));
		$lq->setParentKey($focus->id);
		$lq->addSimpleFilter('list_type', 'default');
		$prospect_lists = $lq->fetchAllRows();

        //list does not exist, send back error message
        if(empty($prospect_lists)) {
            return array(false, $mod_strings['LBL_DEFAULT_LIST_NOT_FOUND']);
        }

        //iterate through each Prospect list and make sure entries exist.
        $entry_count =0;
		foreach($prospect_lists as $list){
			$entry_count += ProspectList::get_entry_count($list['id'], $list['dynamic']);
			if ($entry_count)
				break;
        }
        //if no entries exist, then return error message.
        if(!$entry_count) {
            return array(false, $mod_strings['LBL_DEFAULT_LIST_ENTRIES_NOT_FOUND']);
        }

        //iterate through each member of list and enter campaign log
        foreach($prospect_lists as $list) {
            //process targets/prospects
			self::create_campaign_log_entry($focus->id, $list['id'], 'prospects', 'Prospects');

            //process users
			self::create_campaign_log_entry($focus->id, $list['id'], 'users', 'Users');

            //process contacts
			self::create_campaign_log_entry($focus->id, $list['id'], 'contacts', 'Contacts');

            //process leads
			self::create_campaign_log_entry($focus->id, $default_target_list, 'leads', 'Leads');

        }

		//return success message
        return array(true, $mod_strings['LBL_DEFAULT_LIST_ENTRIES_WERE_PROCESSED']);

    }

	private static function create_campaign_log_entry($campaign_id, $list_id, $rel_name, $rel_type, $target_id = '') {
        global $timedate;
        $target_ids = array();

        //check if this is specified for one target/contact/prospect/lead (from contact/lead detail subpanel)
        if(!empty($target_id)){
            $target_ids[] = $target_id;
        }else{
			//this is specified for all, so load target/prospect relationships (mark as sent button)
			$lq = new ListQuery('ProspectList', null, array('link_name' => $rel_name));
			$lq->setParentKey($list_id);
            $target_ids = array_keys($lq->fetchAllRows());
        }
        if(!empty($target_ids)) {
            foreach($target_ids as $id){
				//perform duplicate check
				$lq = new ListQuery('CampaignLog');
				$lq->addSimpleFilter('campaign_id', $campaign_id);
				$lq->addSimpleFilter('target_id', $id);
				$count = $lq->fetchResultCount();

                //process if this is not a duplicate campaign log entry
                if(!$count) {
                    //create campaign tracker id and retrieve related bio bean
					$tracker_id = create_guid();
					$data = array(
	                    'campaign_id' => $campaign_id,
		                'target_tracker_key' => $tracker_id,
			            'target_id' => $id,
				        'target_type' => $rel_type,
					    'activity_type' => 'targeted',
						'activity_date' => gmdate("Y-m-d H:i:s"),
					);
                    //save the campaign log entry
					$campaign_log = RowUpdate::blank_for_model('CampaignLog');
					$campaign_log->set($data);
                    $campaign_log->save();
                }
            }
        }

    }

}


    /*
     * This function will return an array that has been formatted to work as a Quick Search Object for prospect lists
     */
    function getProspectListQSObjects($source = '', $return_field_name='name', $return_field_id='id' ) {
        global $app_strings;
        //if source has not been specified, then search across all prospect lists
        if(empty($source)){
            $qsProspectList = array('method' => 'query',
                                'modules'=> array('ProspectLists'),
                                'group' => 'and',
                                'field_list' => array('name', 'id'),
                                'populate_list' => array('prospect_list_name', 'prospect_list_id'),
                                'conditions' => array( array('name'=>'name','op'=>'like_custom','end'=>'%','value'=>'') ),
                                'order' => 'name',
                                'limit' => '30',
                                'no_match_text' => $app_strings['ERR_SQS_NO_MATCH']);
        }else{
             //source has been specified use it to tell quicksearch.js which html input to use to get filter value
            $qsProspectList = array('method' => 'query',
                                'modules'=> array('ProspectLists'),
                                'group' => 'and',
                                'field_list' => array('name', 'id'),
                                'populate_list' => array($return_field_name, $return_field_id),
                                'conditions' => array(
                                                    array('name'=>'name','op'=>'like_custom','end'=>'%','value'=>''),
                                                    //this condition has the source parameter defined, meaning the query will take the value specified below
                                                    array('name'=>'list_type', 'op'=>'like_custom', 'end'=>'%','value'=>'', 'source' => $source)
                                ),
                                'order' => 'name',
                                'limit' => '30',
                                'no_match_text' => $app_strings['ERR_SQS_NO_MATCH']);

        }

        return $qsProspectList;
    }



?>
