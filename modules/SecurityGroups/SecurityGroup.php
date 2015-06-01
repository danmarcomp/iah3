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

require_once('modules/SecurityGroups/SecurityGroup_sugar.php');
class SecurityGroup extends SecurityGroup_sugar {
	
    
	function SecurityGroup(){	
		parent::SecurityGroup_sugar();
	}

	var $last_run = array('module' => '', 'record' => '', 'action' => '', 'response' => '');

	
	/**
	 * Gets the join statement used for returning all rows in a list view that a user has group rights to.
	 * Make sure any use of this also return records that the user has owner access to. 
	 * (e.g. caller uses getOwnerWhere as well)
	 *
	 * @param GUID $user_id
	 * @return STRING
	 */
	function getGroupWhere($table_name,$module,$user_id)
	{

		//need a different query if doing a securitygroups check
		if($module == "SecurityGroups") {
			return " $table_name.id in (
				select secg.id from securitygroups secg
				inner join securitygroups_users secu on secg.id = secu.securitygroup_id and secu.deleted = 0 
					and secu.user_id = '$user_id'
				where secg.deleted = 0  
			)";
		} else {
			return " $table_name.id in (
				select secr.record_id from securitygroups secg
				inner join securitygroups_users secu on secg.id = secu.securitygroup_id and secu.deleted = 0 
					and secu.user_id = '$user_id'
				inner join securitygroups_records secr on secg.id = secr.securitygroup_id and secr.deleted = 0 
					and secr.module = '$module' 
				where secg.deleted = 0  
			)";

			//and secr.record_id = $table_name.id //not needed as the in clause takes care of this check
		}
	}
	
	/**
	 * @returns true if group is assigned to the record
	 */
	function groupHasAccess($module,$id)
	{
		if(!isset($id) || $id == '[SELECT_ID_LIST]')
		{
			return true; //means that this is a listview and everybody is an owner of the listview
		}
		$security_modules = SecurityGroup::getSecurityModules();
		if(empty($security_modules[$module])) {
			return false;
		}
		
		global $current_user;
		$cache_key = "SecurityGroups.{$current_user->id}.$module";
		$cache_entry = sugar_cache_get($cache_key, $found);
		if($found && isset($cache_entry[$id]))
			return $cache_entry[$id];
		if(! is_array($cache_entry))
			$cache_entry = array();

		global $db;
		$query = "select count(securitygroups.id) as results from securitygroups "
				."inner join securitygroups_users on securitygroups.id = securitygroups_users.securitygroup_id and securitygroups_users.deleted = 0 "
				."  and securitygroups_users.user_id = '$current_user->id' "
				."inner join securitygroups_records on securitygroups.id = securitygroups_records.securitygroup_id and securitygroups_records.deleted = 0 "
				."	and securitygroups_records.record_id = '$id' "
				."	and securitygroups_records.module = '$module' "
				."where securitygroups.deleted = 0  ";
		$GLOBALS['log']->debug("SecuritySuite: groupHasAccess $query");
		$result = $db->query($query, true);
		$row = $db->fetchByAssoc($result);
		$cache_entry[$id] = (isset($row) && $row['results']>0);
		sugar_cache_put($cache_key, $cache_entry, false, true);
		
		return $cache_entry[$id];
	}
	
	// quickly retrieve access for multiple entries
	function groupHasAccessList($module, $id_list) {
		if(! count($id_list))
			return true;
		$security_modules = SecurityGroup::getSecurityModules();
		if(empty($security_modules[$module])) {
			return false;
		}
		
		global $current_user;
		$cache_key = "SecurityGroups.{$current_user->id}.$module";
		$cache_entry = sugar_cache_get($cache_key, $found);
		if(! $found || ! is_array($cache_entry))
			$cache_entry = array();
		$missing = array();
		foreach($id_list as $id) {
			if(! isset($cache_entry[$id]))
				$missing[] = $id;
		}
		if(! count($missing))
			return;
		
		global $db;
		$query = "SELECT DISTINCT securitygroups_records.record_id FROM securitygroups_records "
				."LEFT JOIN securitygroups ON securitygroups.id = securitygroups_records.securitygroup_id AND securitygroups.deleted = 0 "
				."LEFT JOIN securitygroups_users ON securitygroups.id = securitygroups_users.securitygroup_id AND securitygroups_users.deleted = 0 "
					."  AND securitygroups_users.user_id = '{$current_user->id}' "
				."WHERE securitygroups_records.module = '$module' "
				."AND securitygroups_records.record_id IN ('".implode("','", $missing)."') "
				."AND securitygroups_users.securitygroup_id IS NOT NULL";
		$GLOBALS['log']->debug("SecuritySuite: groupHasAccessList $query");
		$result = $db->query($query, true);
		$ok = array();
		while($row = $db->fetchByAssoc($result, -1, false))
			$ok[$row['record_id']] = 1;
		foreach($missing as $id)
			$cache_entry[$id] = ! empty($ok[$id]);
		sugar_cache_put($cache_key, $cache_entry, false, true);
	}
	
	function inherit(&$focus,$isUpdate)
	{
		SecurityGroup::assign_default_groups($focus,$isUpdate); //this must be first because it does not check for dups
		//don't do inheritance if popup selector method is chosen and a user is making the request...
		$config = SecurityGroup::getConfig();
		if(
			($config['popup_select'] == true
			 && isset($_REQUEST['action']) && $_REQUEST['action'] == 'Save')
		) {
			return; 
			
		}
		SecurityGroup::inherit_creator($focus,$isUpdate);
		SecurityGroup::inherit_assigned($focus,$isUpdate);
		SecurityGroup::inherit_parent($focus,$isUpdate);
		
	}
	
	function assign_default_groups(&$focus,$isUpdate)
	{
		global $db;
		global $current_user;
		if(!$isUpdate) {
			$defaultGroups = SecurityGroup::retrieveDefaultGroups();
			foreach($defaultGroups as $default_id => $defaultGroup) {

				if($defaultGroup['module'] == "All" || $defaultGroup['module'] == $focus->module_dir) {
					$query = "insert into securitygroups_records(securitygroup_id,record_id,module,date_modified,deleted) "					
							."select distinct g.id,'$focus->id','$focus->module_dir',".db_convert('','today').",0 "
							."from securitygroups g "
							."left join securitygroups_records d on d.securitygroup_id = g.id and d.record_id = '$focus->id' and d.module = '$focus->module_dir' and d.deleted = 0 "
							."where d.securitygroup_id is null and g.id = '".$defaultGroup['securitygroup_id']."' and g.deleted = 0 ";
					$GLOBALS['log']->debug("SecuritySuite: Assign Default Groups: $query");
					$db->query($query,true); 
				}
			} //end foreach default group
		}
		
	} 
	
	function inherit_creator(&$focus,$isUpdate)
	{
		global $db;
		global $current_user;
		$config = SecurityGroup::getConfig();
		if(!$isUpdate && $config['inherit_creator'] == true) {

			if(isset($_SESSION['portal_id']) && isset($_SESSION['user_id'])) {
				return; //don't inherit if from portal
			}
			
			//inherit only those that support Security Groups
			$security_modules = SecurityGroup::getSecurityModules();
			if(in_array($focus->module_dir,$security_modules)) {
					
				//test to see if works for creating a note for a case from the portal...this may need to be handled slightly differently
				//inherits portal users groups? Could be an interesting twist...
				$query = "insert into securitygroups_records(securitygroup_id,record_id,module,date_modified,deleted) "
						."select distinct u.securitygroup_id,'$focus->id','$focus->module_dir',".db_convert('','today').",0 "
						."from securitygroups_users u "
						."inner join securitygroups g on u.securitygroup_id = g.id and g.deleted = 0 and (g.noninheritable is null or g.noninheritable <> 1) "
						."left join securitygroups_records d on d.securitygroup_id = u.securitygroup_id and d.record_id = '$focus->id' and d.module = '$focus->module_dir' and d.deleted = 0 "
						."where d.securitygroup_id is null and u.user_id = '$current_user->id' and u.deleted = 0 and (u.noninheritable is null or u.noninheritable <> 1)";
				$GLOBALS['log']->debug("SecuritySuite: Inherit from Creator: $query");
				$db->query($query,true); 
			}
		}
		
	} 
	
	function inherit_assigned(&$focus,$isUpdate)
	{
		global $db;
		global $current_user;
		$config = SecurityGroup::getConfig();
		if($config['inherit_assigned'] == true) {
	
			$assigned_user_id = isset($focus->assigned_user_id) ? $focus->assigned_user_id : null;
			if(!empty($assigned_user_id)) {
				//inherit only those that support Security Groups
				$security_modules = SecurityGroup::getSecurityModules();
				if(in_array($focus->module_dir,$security_modules)) {

					//test to see if works for creating a note for a case from the portal...this may need to be handled slightly differently
					//inherits portal users groups? Could be an interesting twist...
					$query = "insert into securitygroups_records(securitygroup_id,record_id,module,date_modified,deleted) "
							."select distinct u.securitygroup_id,'$focus->id','$focus->module_dir',".db_convert('','today').",0 "
							."from securitygroups_users u "
							."inner join securitygroups g on u.securitygroup_id = g.id and g.deleted = 0 and (g.noninheritable is null or g.noninheritable <> 1) "
							."left join securitygroups_records d on d.securitygroup_id = u.securitygroup_id and d.record_id = '$focus->id' and d.module = '$focus->module_dir' and d.deleted = 0 "
							."where d.securitygroup_id is null and u.user_id = '$assigned_user_id' and u.deleted = 0  and (u.noninheritable is null or u.noninheritable <> 1)";
					$GLOBALS['log']->debug("SecuritySuite: Inherit from Assigned: $query");
					$db->query($query,true); 
				}
			} //if !empty assigned_user_id
		}
		
	} 
	
	function inherit_parent(&$focus,$isUpdate)
	{
		global $db;
		$config = SecurityGroup::getConfig();
		//new record or if update from soap api for cases or bugs
		//TEST FOR PORTAL NOTES
		//if((!$isUpdate || ($isUpdate && !empty($focus->note_id) && ($focus->object_name == "Case" || $focus->object_name == "Bug")))
		if(!$isUpdate
			&& $config['inherit_parent'] == true) {
    
    		$parent_type = @$_REQUEST['relate_to'];
    		$parent_id = @$_REQUEST['relate_id'];
			$focus_module_dir = $focus->module_dir;
			$focus_id = $focus->id;
			
			if(isset($_SESSION['portal_id'])) {
				$parent_id = $_SESSION['user_id']; //soap stores contact id in user_id field
				$parent_type = "Contacts";
			}
			
			if(empty($parent_type)) {
				$parent_type = @$_REQUEST['return_module'];
				$parent_id = @$_REQUEST['return_id'];
			}

		
			//inherit only those that support Security Groups
			$security_modules = SecurityGroup::getSecurityModules();
			
			if(!empty($parent_type) && !empty($parent_id) && in_array($focus_module_dir,$security_modules)) { // && $parent_type != "Emails" && $parent_type != "Meetings") {
			
				/** can speed this up by doing one query */
				//should be just one query but need a unique guid for each insert
				//WE NEED A UNIQUE GUID SO USE THE BUILT IN SQL GUID METHOD
				$query = "insert into securitygroups_records(securitygroup_id,record_id,module,date_modified,deleted) "
						."select distinct r.securitygroup_id,'$focus_id','$focus_module_dir',".db_convert('','today').",0 "
						."from securitygroups_records r "
						."inner join securitygroups g on r.securitygroup_id = g.id and g.deleted = 0 and (g.noninheritable is null or g.noninheritable <> 1) "
						."left join securitygroups_records d on d.securitygroup_id = r.securitygroup_id and d.record_id = '$focus_id' and d.module = '$focus_module_dir' and d.deleted = 0 "
						."where d.securitygroup_id is null and r.module = '$parent_type' "
						."and r.record_id = '$parent_id' "
						."and r.deleted = 0 ";
						//using left join instead
						//and not exists (select top 1 s.id from securitygroups_records s where s.deleted = 0 and s.record_id = '$focus_id' and s.securitygroup_id = r.securitygroup_id and s.module = '$focus_module_dir') ";
				$GLOBALS['log']->debug("SecuritySuite: Inherit from Parent: $query");
				$db->query($query,true);
			
			} //end if parent type/id
		} //end if new record
	}
	
	/**
	 * If user is a member of just one group inherit group for new record
	 * returns true if inherit just one else false
	 */
	function inheritOne($user_id, $record_id, $module) {
		//check to see if in just one group...if so, inherit that group and return true
		global $db; 
		
		$query = "select count(securitygroups.id) as results from securitygroups "
				."inner join securitygroups_users on securitygroups.id = securitygroups_users.securitygroup_id "
				." and securitygroups_users.deleted = 0 "
				." where securitygroups.deleted = 0 and securitygroups_users.user_id = '$user_id' "
				."  and (securitygroups.noninheritable is null or securitygroups.noninheritable <> 1) "
				."  and (securitygroups_users.noninheritable is null or securitygroups_users.noninheritable <> 1) ";
		$GLOBALS['log']->debug("SecuritySuite: Inherit One Pre-Check Qualifier: $query");
		$result = $db->query($query);
		$row = $db->fetchByAssoc($result);
		if(isset($row) && $row['results'] == 1) {
				
			$query = "insert into securitygroups_records(securitygroup_id,record_id,module,date_modified,deleted) "
					."select distinct u.securitygroup_id,'$record_id','$module',".db_convert('','today').",0 "
					."from securitygroups_users u "
					."inner join securitygroups g on u.securitygroup_id = g.id and g.deleted = 0 and (g.noninheritable is null or g.noninheritable <> 1) "
					."left join securitygroups_records d on d.securitygroup_id = u.securitygroup_id and d.record_id = '$record_id' and d.module = '$module' and d.deleted = 0 "
					."where d.securitygroup_id is null and u.user_id = '$user_id' and u.deleted = 0 and (u.noninheritable is null or u.noninheritable <> 1)";
			$GLOBALS['log']->debug("SecuritySuite: Inherit One: $query");
			$db->query($query,true); 		
			return true;
		}
		
		return false;
	}

	/**
	 * returns # of groups a user is a member of that are inheritable
	 *
	 * TODO: cache this value in the session var
	 */
	function getMembershipCount($user_id) {
		global $db; 
		
		if(!isset($_SESSION['securitygroup_count'])) {
			$query = "select count(securitygroups.id) as results from securitygroups "
					."inner join securitygroups_users on securitygroups.id = securitygroups_users.securitygroup_id "
					." and securitygroups_users.deleted = 0 "
					." where securitygroups.deleted = 0 and securitygroups_users.user_id = '$user_id' "
					."  and (securitygroups.noninheritable is null or securitygroups.noninheritable <> 1) "
					."  and (securitygroups_users.noninheritable is null or securitygroups_users.noninheritable <> 1) ";
			$GLOBALS['log']->debug("SecuritySuite: Inherit One Pre-Check Qualifier: $query");
			$result = $db->query($query);
			$row = $db->fetchByAssoc($result);
			if(isset($row)) {
				$_SESSION['securitygroup_count'] = $row['results'];
			}
		}
		
		return $_SESSION['securitygroup_count'];
	}
	
	function retrieveDefaultGroups() {
		global $db;
		$default_groups = array();
		$query = "select securitygroups.name, securitygroups_default.module, securitygroups_default.securitygroup_id "
				."from securitygroups_default "
				."inner join securitygroups on securitygroups_default.securitygroup_id = securitygroups.id "
				."where securitygroups_default.deleted = 0 and securitygroups.deleted = 0";
		$GLOBALS['log']->debug("SecuritySuite: Retrieve Default Groups: $query");
		$result = $db->query($query);
		while(($row = $db->fetchByAssoc($result)) != null) {
			$default_groups[] = array('group'=>$row['name'],'module'=>$row['module'],'securitygroup_id'=>$row['securitygroup_id']);
		}
		
		return $default_groups;
	}

    function saveDefaultGroup($group_id, $module) {
    	global $db;
    	$query = "INSERT INTO securitygroups_default (securitygroup_id, module, date_modified, deleted) "
    			."VALUES ( ";
		$query .= "'$group_id', '$module',".db_convert('','today').",0 )";
		$GLOBALS['log']->debug("SecuritySuite: Save Default Group: $query");
        $db->query($query);
    }
    
    function removeDefaultGroup($group_id, $module) {
    	global $db;
		$query = "delete from securitygroups_default where securitygroup_id='$group_id' and module='$module' ";
        $db->query($query);
    }
    
    /**
     * Used to get the modules that are tied to security groups. 
     * There should be a relationship of some sort in order to tie the two together.
     *
     * This will be used for things such as default groups for modules, etc.
     */
    function getSecurityModules() {
    	global $db;
    	$cache_key = "SecurityGroups.security_modules";
    	$cache_entry = sugar_cache_get($cache_key, $found);
		if($found)
			return $cache_entry;
    	
    	// ajrw - securitygroups still implemented at module level
    	// should probably be moved to bean level
    	$security_modules = array();
    	$beans = AppConfig::setting('model.index.securitygroups', array());
    	foreach($beans as $b) {
    		$module = AppConfig::setting("model.index.bean_module.$b");
    		if($module)
				$security_modules[$module] = $module;
		}
		sugar_cache_put($cache_key, $security_modules);
		
		return $security_modules;
    }

	function getConfig()
	{
		return AppConfig::setting('aclconfig');
	}

    /** To get the link name used to call load_relationship */
    function getLinkName($this_module, $rel_module) {
    	return 'securitygroups';
    }
    
    /**
     * Add a Security Group to a record
     */
    function addGroupToRecord($module, $record_id, $securitygroup_id) {
    	if(empty($module) || empty($record_id) || empty($securitygroup_id)) {
    		return; //missing data
    	}
		global $db; 
		$query = "insert into securitygroups_records(id,securitygroup_id,record_id,module,date_modified,deleted) "
				."values( '".create_guid()."','".$securitygroup_id."','$record_id','$module',".db_convert('','today').",0) ";
		$GLOBALS['log']->debug("SecuritySuite: addGroupToRecord: $query");
		$db->query($query,true); 
    }
    
    function getUserSecurityGroups($user_id) {
    	return self::get_user_security_groups($user_id);
    }
    
	/**
	 * Return a list of groups that this user belongs to.
	 */
	static function get_user_security_groups($user_id)
	{	
		global $db;
		$query = "select securitygroups.id, securitygroups.name from securitygroups_users "
				."inner join securitygroups on securitygroups_users.securitygroup_id = securitygroups.id "
				."		and securitygroups.deleted = 0 "
				."where securitygroups_users.user_id='$user_id' and securitygroups_users.deleted = 0 ";
		$result = $db->query($query,true,"Error finding the full membership list for a user: ");
		
		$group_array = Array();
		$result = $db->query($query);
		while(($row=$db->fetchByAssoc($result)) != null) {			
			$group_array[$row['id']] = $row;
		}

		return $group_array;
	}

	/**
	 * Return a list of all groups
	 */
	function getAllSecurityGroups()
	{	
		global $db;
		$query = "select id, name from securitygroups "
				."where securitygroups.deleted = 0 "
				."order by name";
		$result = $db->query($query,true,"Error finding the full membership list for a user: ");
		
		$group_array = Array();
		$result = $db->query($query);
		while(($row=$db->fetchByAssoc($result)) != null) {			
			$group_array[$row['id']] = $row;
		}

		return $group_array;
	}
	
	
	static function after_save(RowUpdate $upd) {
		AppConfig::invalidate_cache('acl');
	}
	
	static function update_acl(RowUpdate &$update, $link_name) {
		$model = $update->getModelName();
		if($model == 'SecurityGroup' && ($link_name == 'users' || $link_name == 'aclroles')) {
			AppConfig::invalidate_cache('acl');
		}
	}

}
?>
