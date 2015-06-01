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
require_once('include/utils.php');


class EmailMarketing extends SugarBean {

	var $field_name_map;
	
	var $id;
	var $deleted;
	var $date_entered;
	var $date_modified;
	var $modified_user_id;
	var $created_by;
	var $name;
	var $from_addr;
	var $from_name;
	var $date_start;
	var $template_id;
	var $campaign_id;
	var $all_prospect_lists;
	var $status;
	var $inbound_email_id;
	
	var $table_name = 'email_marketing';
	var $object_name = 'EmailMarketing';
	var $module_dir = 'EmailMarketing';
	
	var $new_schema = true;

	function EmailMarketing() {
		parent::SugarBean();
	}
	
	function get_summary_text()
	{
		return $this->name;
	}
	
	function create_list_query($order_by, $where, $show_deleted = 0)
	{
		$query = "SELECT ";
		$query .= "email_marketing.* FROM email_marketing ";
		$where_auto = " 1=1";
		if($show_deleted == 0){
			$where_auto = " email_marketing.deleted=0";
		}else if($show_deleted == 1){
			$where_auto = " email_marketing.deleted=1";
		}	
		
		if($where != "")
			$query .= " WHERE $where AND " . $where_auto;
		else
			$query .= " WHERE " . $where_auto;
		
		if($order_by != "")
			$query .= " ORDER BY $order_by ";
		else
			$query .= " ORDER BY email_marketing.name ";

		return $query;		
	}

	function create_export_query($order_by, $where)
	{
		return $this->create_list_query($order_by, $where);
	}	
	
	function get_list_view_data(){

		$temp_array = $this->get_list_view_array();
		
		$id = $temp_array['ID'];
		$template_id = $temp_array['TEMPLATE_ID'];
		
		//mode is set by schedule.php from campaigns module.
		if (!isset($this->mode) or empty($this->mode) or $this->mode!='test') {
			$this->mode='rest';
		}
		
		if ($temp_array['ALL_PROSPECT_LISTS']==1) {
			$query="SELECT name from prospect_lists ";
			$query.=" INNER JOIN prospect_list_campaigns plc ON plc.prospect_list_id = prospect_lists.id";
			$query.=" WHERE plc.campaign_id='{$temp_array['CAMPAIGN_ID']}'"; 
			$query.=" AND prospect_lists.deleted=0";
			$query.=" AND plc.deleted=0";
			if ($this->mode=='test') {
				$query.=" AND prospect_lists.list_type='test'";			
			} else {
				$query.=" AND prospect_lists.list_type!='test'";			
			}
		} else {
			$query="SELECT name from prospect_lists ";
			$query.=" INNER JOIN email_marketing_prospect_lists empl ON empl.prospect_list_id = prospect_lists.id";
			$query.=" WHERE empl.email_marketing_id='{$id}'"; 
			$query.=" AND prospect_lists.deleted=0";
			$query.=" AND empl.deleted=0";
			if ($this->mode=='test') {
				$query.=" AND prospect_lists.list_type='test'";			
			} else {
				$query.=" AND prospect_lists.list_type!='test'";			
			}
		}
		$res = $this->db->query($query);
		while (($row = $this->db->fetchByAssoc($res)) != null) {
			if (!empty($temp_array['PROSPECT_LIST_NAME'])) {
				$temp_array['PROSPECT_LIST_NAME'].="<BR>";
			}
			$temp_array['PROSPECT_LIST_NAME'].=$row['name'];
		}
		return $temp_array;
	}	

	function bean_implements($interface){
		switch($interface){
			case 'ACL':return true;
		}
		return false;
	}

	function get_all_prospect_lists() {
		
		$query="select prospect_lists.* from prospect_lists ";
		$query.=" left join prospect_list_campaigns on prospect_list_campaigns.prospect_list_id=prospect_lists.id";
		$query.=" where prospect_list_campaigns.deleted=0";
		$query.=" and prospect_list_campaigns.campaign_id='$this->campaign_id'";
		$query.=" and prospect_lists.deleted=0";
		$query.=" and prospect_lists.list_type not like 'exempt%'";
		
		return $query;
	}
	
	static function get_entry_count($id, $campaign_id, $all_prospect_lists)
	{
		if ($all_prospect_lists) {
			$lq = new ListQuery('Campaign', array('id', 'dynamic'), array('link_name' => 'prospectlists'));
			$lq->setParentKey($campaign_id);
		} else {
			$lq = new ListQuery('EmailMarketing', array('id','dynamic'), array('link_name' => 'prospectlists'));
			$lq->setParentKey($id);
		}
		$rows = $lq->fetchAllRows();
		require_bean('ProspectList');
		$count = 0;
		foreach ($rows as $row) {
			$count += ProspectList::get_entry_count($row['id'], $row['dynamic']);
		}
		return $count;
	}

}
?>
