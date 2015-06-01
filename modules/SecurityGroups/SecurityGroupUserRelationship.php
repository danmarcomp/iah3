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
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');


require_once('data/SugarBean.php');

// Contact is used to store customer information.
class SecurityGroupUserRelationship extends SugarBean {
	// Stored fields
	var $id;
	var $securitygroup_id;
	var $securitygroup_noninheritable;
	var $user_id;
	var $noninheritable;
	
	// Related fields
	var $securitygroup_name;
	var $user_name;

	var $table_name = "securitygroups_users";
	var $object_name = "SecurityGroupUserRelationship";
	var $column_fields = Array("id"
		,"securitygroup_id"
		,"user_id"
		,"noninheritable"
		,'date_modified'
		);

	var $new_schema = true;
	
	var $additional_column_fields = Array();
		var $field_defs = array (
       'id'=>array('name' =>'id', 'type' =>'char', 'len'=>'36', 'default'=>'')
      , 'securitygroup_id'=>array('name' =>'securitygroup_id', 'type' =>'char', 'len'=>'36', )
      , 'user_id'=>array('name' =>'user_id', 'type' =>'char', 'len'=>'36',)
      , 'noninheritable'=>array('name' =>'noninheritable', 'type' =>'bool', 'len'=>'1')
      , 'date_modified'=>array ('name' => 'date_modified','type' => 'datetime')
      , 'deleted'=>array('name' =>'deleted', 'type' =>'bool', 'len'=>'1', 'default'=>'0', 'required'=>true)
      );
	function SecurityGroupUserRelationship() {
		parent::SugarBean();

		$this->disable_row_level_security =true;

		}

	function fill_in_additional_detail_fields()
	{
		if(isset($this->securitygroup_id) && $this->securitygroup_id != "")
		{
			$query = "SELECT name from securitygroups where id='$this->securitygroup_id' AND deleted=0";
			$result =$this->db->query($query,true," Error filling in additional detail fields: ");
			// Get the id and the name.
			$row = $this->db->fetchByAssoc($result);

			if($row != null)
			{
				$this->securitygroup_name = $row['name'];
			}
		}

		if(isset($this->user_id) && $this->user_id != "")
		{
			$query = "SELECT user_name from users where id='$this->user_id' AND deleted=0";
			$result =$this->db->query($query,true," Error filling in additional detail fields: ");
			// Get the id and the name.
			$row = $this->db->fetchByAssoc($result);

			if($row != null)
			{
				$this->user_name = $row['user_name'];
			}
		}

	}

	function create_list_query(&$order_by, &$where)
	{
		$query = "SELECT id, first_name, last_name, user_name FROM users ";
		$where_auto = "deleted=0";

		if($where != "")
			$query .= "where $where AND ".$where_auto;
		else
			$query .= "where ".$where_auto;

		$query .= " ORDER BY last_name, first_name";

		return $query;
	}
}



?>
