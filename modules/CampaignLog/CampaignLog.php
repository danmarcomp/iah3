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


class CampaignLog extends SugarBean {

	var $table_name = 'campaign_log';
	var $object_name = 'CampaignLog';
	var $module_dir = 'CampaignLog';
	
	var $new_schema = true;
		
	var $campaign_id;
	// longreach - added
	var $marketing_id;
	var $target_tracker_key;
	var $target_id;
	var $target_type;
	var $activity_type;
	var $activity_date;
	var $related_id;
	var $related_type;
	var $deleted;
	var $list_id;
	var $hits;
	var $more_information;


	//this function is called statically by the campaing_log subpanel.
	 function get_related_name($related_id, $related_type) {
	 	$db= & PearDatabase::getInstance();
	 	if ($related_type == 'Emails') {
	 		$query="SELECT name from emails where id='$related_id'";
	 		$result=$db->query($query);
	 		$row=$db->fetchByAssoc($result);
	 		if ($row != null) {
	 			return $row['name'];
	 		}
	 	}
	 	if ($related_type == 'Contacts') {
	 		$query="SELECT first_name, last_name from contacts where id='$related_id'";
	 		$result=$db->query($query);
	 		$row=$db->fetchByAssoc($result);
	 		if ($row != null) {
	 			return $row['first_name'] . ' ' . $row['last_name'];
	 		}
	 	}
	 	if ($related_type == 'CampaignTrackers') {
	 		$query="SELECT tracker_url from campaign_trkrs where id='$related_id'";
	 		$result=$db->query($query);
	 		$row=$db->fetchByAssoc($result);
	 		if ($row != null) {
	 			return $row['tracker_url'] ;
	 		}
	 	}

		return $related_id.$related_type;
	}
	
}

?>
