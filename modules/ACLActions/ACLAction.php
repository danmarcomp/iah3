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

require_once('modules/ACLActions/actiondefs.php');
require_once('modules/SecurityGroups/SecurityGroup.php');

class ACLAction  extends SugarBean{
	var $module_dir = 'ACLActions';
	var $object_name = 'ACLAction';
	var $table_name = 'acl_actions';
	var $new_schema = true;

	function ACLAction(){
		parent::SugarBean();
	}

	
	/**
	 * static addActions($category, $type='module')
	 * Adds all default actions for a category/type
	 *
	 * @param STRING $category - the category (e.g module name - Accounts, Contacts)
	 * @param STRING $type - the type (e.g. 'module', 'field')
	 */
	function addActions($category, $type='module'){
		global $ACLActions, $db;
		if(isset($ACLActions[$type])){
			$query = "SELECT name FROM acl_actions WHERE category = '$category' AND acltype='$type' AND NOT deleted";
			$result = $db->query($query);
			$defined = array();
			while($row = $db->fetchByAssoc($result)) {
				$defined[$row['name']] = 1;
			}
			foreach($ACLActions[$type]['actions'] as $action_name =>$action_def){
				if(! empty($defined[$action_name]))
					continue;
				$action = new ACLAction();
				$action->name = $action_name;
				$action->category = $category;
				$action->aclaccess = $action_def['default'];
				$action->acltype = $type;
				$action->modified_user_id = 1;
				$action->created_by = 1;
				$action->save();
				$action->cleanup();
			}
			// may wish to delete extra defined actions here
		}else{
			sugar_die("FAILED TO ADD: $category : $name - TYPE $type NOT DEFINED IN modules/ACLActions/actiondefs.php");
		}
		
	}
	
	function removeActions($category, $where='') {
		global $db;
		$query = "UPDATE acl_actions SET deleted=1 WHERE category='$category'";
		if($where) $query .= " AND ($where)";
		$result = $db->query($query);
	}
	
	/**
	 * static AccessColor($access)
	 *
	 * returns the color associated with an access level 
	 * these colors exist in the definitions in modules/ACLActions/actiondefs.php
	 * @param INT $access - the access level you want the color for
	 * @return the color either name or hex representation or false if the level does not exist
	 */
	static function AccessColor($access){
		global $ACLActionAccessLevels;
		if(isset($ACLActionAccessLevels[$access])){
			
			return $ACLActionAccessLevels[$access]['color'];
		}
		return false;	
	}
	static function AccessClass($access) {
		global $ACLStyleClassNames;
		if(isset($ACLStyleClassNames[$access])) {
			return $ACLStyleClassNames[$access];
		}
		return '';
	}
	
	/**
	 * static AccessName($access)
	 *
	 * returns the translated name  associated with an access level 
	 * these label definitions  exist in the definitions in modules/ACLActions/actiondefs.php
	 * @param INT $access - the access level you want the color for
	 * @return the translated access level name or false if the level does not exist
	 */
	static function AccessName($access){
		global $ACLActionAccessLevels;
		if(isset($ACLActionAccessLevels[$access])){
			return translate($ACLActionAccessLevels[$access]['label'], 'ACLActions');
		}
		return false;
		
	}
	
	/**
	 * static getAccessOptions()
	 * this is used for building select boxes 
	 * @return array containg access levels (ints) as keys and access names as values
	 */
	static function getAccessOptions($category, $action, $type='module', $no_disable_access=false){
		global $ACLActions;
		static $team_mods;
		if(! isset($team_mods)) $team_mods = SecurityGroup::getSecurityModules();
		
		$options = array();
		if(! empty($ACLActions[$type]['actions'][$action]['aclaccess']))
		foreach($ACLActions[$type]['actions'][$action]['aclaccess'] as $opt){
			if($opt == ACL_ALLOW_GROUP && ($type != 'module' || empty($team_mods[$category])))
				continue;
			if($no_disable_access && $action == 'access' && $opt == ACL_ALLOW_DISABLED)
				continue;
			$options[] = array('value' => $opt, 'label' => ACLAction::AccessName($opt), 'className' => ACLAction::AccessClass($opt));
		}
		return $options;
		
	}
	
	/**
	 * function static getDefaultActions()
	 * This function will return a list of acl actions with their default access levels
	 *
	 *
	 */
	static function getDefaultActions($type='module', $action=''){
		$ret = array();
		$by_model = AppConfig::setting("acl.default_role", null, true);
		// convert config result into expected format
		foreach($by_model as $model => $by_type) {
			foreach($by_type as $t => $actions) {
				if(! isset($type) || $t == $type) {
					foreach($actions as $k => $v) {
						if(! isset($action) || $k == $action) {
							$ret[$model][] = array(
								'type' => $t,
								'action' => $k,
								'value' => $v,
							);
						}
					}
				}
			}
		}
		return $ret;
	}
	
	
	/**
	 * static getUserActions($user_id,$refresh=false, $category='', $action='')
	 * returns a list of user actions
	 * @param GUID $user_id
	 * @param BOOLEAN $refresh (ignored)
	 * @param STRING $category
	 * @param STRING $action
	 * @return ARRAY of ACLActionsArray
	 */
	
	static function getUserActions($user_id, $refresh=false, $category='', $type='module', $action=''){
		$src = "acl.by_user.$user_id.$type";
		if(! empty($category)) {
			$src .= ".$category";
			if(! empty($action))
				$src .= ".actions.$action";
		}

        $rows = AppConfig::setting($src);
        $ret = array();
        if($rows) {
        	if(! empty($category)) {
				if(! empty($action)) $rows = array('actions' => array($action => $rows));
        		$rows = array($category => $rows);
        	}
            foreach($rows as $mod => $info) {
                foreach($info['actions'] as $name => $raction) {
                    $ret[$mod][$type][$name] = array(
                        'id' => $raction['id'],
                        'acltype' => $type, 'module' => $mod,
                        'aclaccess' => $raction['aclaccess'],
                    );
                }
            }
            if(! empty($category))
            	$ret = $ret[$category];
            if(! empty($action))
            	$ret = $ret[$type][$action];
        }

		return $ret;
	}
	
	
	/**
	 * static getSecurityGroupActions($user_id,$refresh=false, $category='', $action='')
	 * returns a list of user actions
	 * @param GUID $team_id
	 * @param BOOLEAN $refresh (ignored)
	 * @param STRING $category
	 * @param STRING $action
	 * @return ARRAY of ACLActionsArray
	 */
	
	static function getSecurityGroupActions($team_id, $refresh=false, $category='', $type='module', $action=''){
		$src = "acl.by_team.$team_id.$type";
		if(! empty($category)) {
			$src .= ".$category";
			if(! empty($action))
				$src .= ".actions.$action";
		}
		return AppConfig::setting($src);
	}

	
	/**
	 * function hasAccess($is_owner= false , $access = 0)
	 * checks if a user has access to this acl if the user is an owner it will check if owners have access
	 *
	 * @param boolean $is_owner
	 * @param boolean $in_group
	 * @param int $access
	 * @return true or false
	 */
	/* longreach - modified parameters
	function hasAccess($is_owner=false, $access = 0){
	*/
	
	static function has_access($user_id, $is_owner, $in_group=false, $access=0) {
        if(! isset($access))
        	return null;
        global $current_user;
        if($access != ACL_ALLOW_DISABLED && $user_id == $current_user->id && is_admin($current_user))
        	return true;

		$allowed_users = null;
        if (ACLAction::accessLevelRequiresOwnership($access)) {
            $allowed_users = ACLAction::getAllowedUsers($user_id, $access);
        }        

        /* eggsurplus - SECURITYGROUPS */
		if (
            $access != 0 && ($access == ACL_ALLOW_ALL 
			|| (($is_owner == $user_id) && ($access == ACL_ALLOW_OWNER || $access == ACL_ALLOW_GROUP) )  //if owner that's better than in group so count it...better way to clean this up?
			|| ($in_group && $access == ACL_ALLOW_GROUP) //need to pass if in group with access somehow
            || (isset($allowed_users) && in_array($is_owner, $allowed_users)))
        ) return true;
        /* end eggsurplus - SECURITYGROUPS */
        
		return false;
	}
	
	// longreach - now calls static method
	function hasAccess($user_id, $is_owner, $in_group=false, $access=null) {
		if(! isset($access))
			$access = $this->aclaccess;
		return self::has_access($user_id, $is_owner, $in_group, $access);
	}


	/**
	 * STATIC function userNeedsSecurityGroup($user_id, $category, $action,$type='module')
	 * checks if a user should have ownership to do an action
	 *
	 * @param GUID $user_id
	 * @param STRING $category
	 * @param STRING $action
	 * @param STRING $type
	 * @return boolean
	 */
	static function userNeedsSecurityGroup($user_id, $category, $action,$type='module'){
		$level = self::getUserAccessLevel($user_id, $category, $action, $type, 'aclaccess');
		return (isset($level) && $level == ACL_ALLOW_GROUP);
	}	


	static function userNeedsSecurityAdmin($user_id, $category, $action,$type='module'){
		$level = self::getUserAccessLevel($user_id, $category, $action, $type, 'aclaccess');
		return (isset($level) && $level == ACL_ALLOW_ADMIN);
	}	
	
	
	
	/**
	 * static function userHasAccess($user_id, $category, $action, $is_owner = false)
	 *
	 * @param GUID $user_id the user id who you want to check access for
	 * @param STRING $category the category you would like to check access for
	 * @param STRING $action the action of that category you would like to check access for
	 * @param BOOLEAN OPTIONAL $is_owner if the object is owned by the user you are checking access for
	 */
	/* eggsurplus - SECURITYGROUPS - added $in_group */
	static function userHasAccess($user_id, $category, $action,$type='module', $is_owner = false, $in_group = false){
		$lvl = self::getUserAccessLevel($user_id, $category, 'access');
		if($lvl == ACL_ALLOW_ADMIN || $lvl < ACL_ALLOW_LIMITED)
			return false;
		if($action == 'access')
			return true;
		$aclAccess = self::getUserAccessLevel($user_id, $category, $action, $type, 'aclaccess');
		return self::has_access($user_id, $is_owner, $in_group, $aclAccess);
	}
	
	/**
	 * function getUserAccessLevel($user_id, $category, $action,$type='module')
	 * returns the access level for a given category and action
	 *
	 * @param GUID  $user_id
	 * @param STRING $category
	 * @param STRING $action
	 * @param STRING $type
	 * @return INT (ACCESS LEVEL)
	 */
	static function getUserAccessLevel($user_id, $category, $action, $type='module', $attrib='aclaccess'){
		if(! isset($user_id)) $user_id = AppConfig::current_user_id();
		$key = "acl.by_user.$user_id.$type.$category.actions.$action";
		if(isset($attrib)) $key .= ".$attrib";
		$val = AppConfig::setting($key);
		return $val;
	}
	
	static function levelStringToInt($val) {
		global $ACLActionLevelString;
		return array_get_default($ACLActionLevelString, $val);
	}
	
	static function levelIntToString($val) {
		global $ACLActionLevelString;
		$idx = array_search($val, $ACLActionLevelString);
		if(! isset($idx)) $idx = $val;
		return $idx;
	}
	
	
	/**
	 * STATIC function userNeedsOwnership($user_id, $category, $action,$type='module')
	 * checks if a user should have ownership to do an action
	 *
	 * @param GUID $user_id
	 * @param STRING $category
	 * @param STRING $action
	 * @param STRING $type
	 * @return boolean
	 */
	static function userNeedsOwnership($user_id, $category, $action,$type='module'){
		$aclAccess = self::getUserAccessLevel($user_id, $category, $action, $type, 'aclaccess');		
		return ACLAction::accessLevelRequiresOwnership($aclAccess);
	}
	
	
	/**
	 * 
	 * static pass by ref setupCategoriesMatrix(&$categories)
	 * takes in an array of categories and modifes them adding display information
	 *
	 * @param unknown_type $categories
	 */
	function setupCategoriesMatrix(&$categories){
		global $ACLActions, $current_user;
		$names = array();
		$disabled = array();
		foreach($categories as $cat_name=>$category){
			$no_disable_access = AppConfig::setting("acl.default_role.module.$cat_name.no_disable_access", false);
	foreach($category as $type_name=>$type){
		$setup_names = false;
		if(empty($names)){
			$names = array();
		}
	foreach($type as $act_name=>$action){
		
		if(! isset($names[$act_name])){
			$names[$act_name] = @translate($ACLActions[$type_name]['actions'][$act_name]['label'], 'ACLActions');
		}
		
		//$categories[$cat_name][$type_name][$act_name]['accessColor'] = ACLAction::AccessColor($action['aclaccess']);	
		$categories[$cat_name][$type_name][$act_name]['cssclass'] = ACLAction::AccessClass($action['aclaccess']);
		
		if($type_name== 'module'){

			// longreach - modified - silence $categories
			if($act_name != 'access' && @$categories[$cat_name]['module']['access']['aclaccess'] == ACL_ALLOW_DISABLED){
				$categories[$cat_name][$type_name][$act_name]['inactive'] = 1;
				$disabled[] = $cat_name;
			}
			
		}
		
		$categories[$cat_name][$type_name][$act_name]['accessName'] = ACLAction::AccessName($action['aclaccess']);
		$categories[$cat_name][$type_name][$act_name]['accessOptions'] =  ACLAction::getAccessOptions($cat_name, $act_name, $type_name, $no_disable_access);
		
	}
	}
	

	
}
if(!is_admin($current_user)){
	foreach($disabled as $cat_name){
		unset($categories[$cat_name]);
	}
}
	return $names;
		
	}
	
	
	function groupCategories(&$categories) {
		global $app_list_strings;
		require_once('include/GroupedTabs/GroupedTabStructure.php');
		
		$grouped = array();
		$mods = array_keys($categories);
		$groupedTabsClass = new GroupedTabStructure();
		$group_defs = $groupedTabsClass->get_tab_structure($mods, '', true, true);
		$mod_names = array_merge($app_list_strings['moduleList'], $app_list_strings['ACLmoduleList']);
		
		foreach($group_defs as $gname => $gdef) {
			$mods = array();
			$ord = array();
			foreach($gdef['modules'] as $m)
				if(isset($categories[$m]))
					$mods[array_get_default($mod_names, $m, $m)] = $m;
			ksort($mods);
			if(count($mods))
				$grouped[] = array('name' => $gname, 'label' => translate($gname), 'modules' => $mods);
		}
		
		return $grouped;
	}
	
	
	/**
	 * function toArray()
	 * returns this acl as an array
	 *
	 * @return array of fields with id, name, access and category
	 */
	function toArray(){
		$array_fields = array('id', 'aclaccess');
		$arr = array();
		foreach($array_fields as $field){
			$arr[$field] = $this->$field;
		}
		return $arr;
	}
	
	/**
	 * function fromArray($arr)
	 * converts an array into an acl mapping name value pairs into files
	 *
	 * @param Array $arr
	 */
	function fromArray($arr){
		foreach($arr as $name=>$value){
			$this->$name = $value;
		}
	}
	
	/**
	 * function clearSessionCache()
	 * clears the session variable storing the cache information for acls
	 *
	 */
	function clearSessionCache(){
		/*unset($_SESSION['ACL']);
		// longreach - start added
		unset($_SESSION['ACLUSERS']);
        $GLOBALS['log']->debug('ACL cache cleared');*/
		// longreach - end added
	}
	

    static function accessLevelRequiresOwnership($level)
    {
        static $levels = array(
            ACL_ALLOW_OWNER,
            ACL_ALLOW_ROLE,
        );
        return in_array($level, $levels);
    }
    
    static function accessLevelRequiresTeam($level)
    {
        return $level == ACL_ALLOW_GROUP;
    }

    static function getAllowedUsers($user_id, $level)
    {
    	return AppConfig::setting("acl.allowed_users.$user_id.$level");
    }
    
    // ajrw - this seems a bit problematic when there are many users?
    static function loadAllowedUsers($user_id) {
    	global $db;
		$ret = array();
		$users = array();

		/*
		 * Reporting line inheritance disabled 
		 */

		/*
		$query = "SELECT id, reports_to_id FROM users WHERE !deleted AND (id='$user_id' OR (reports_to_id IS NOT NULL AND reports_to_id != ''))";
		$res = $db->query($query);
		while ($row = $db->fetchByAssoc($res)) {
			if (!empty($row['reports_to_id'])) $users[$row['reports_to_id']][] = $row['id'];
		}
		 */

		$current_reports = array($user_id);
		while (!empty($current_reports)) {
			$new_reports = array();
			foreach ($current_reports as $parent_id) {
				if (isset($users[$parent_id])) {
					foreach ($users[$parent_id] as $uid) {
						$ret[ACL_ALLOW_OWNER][] = $uid;
						$ret[ACL_ALLOW_ROLE][] = $uid;
						$new_reports[] = $uid;
					}
				}
			}
			$current_reports = $new_reports;
		}
		$ret[ACL_ALLOW_OWNER][] = $user_id;
		$ret[ACL_ALLOW_ROLE][] = $user_id;
		// added for SecurityGroups
		$ret[ACL_ALLOW_GROUP][] = $user_id;

		$query = "SELECT others.user_id FROM acl_roles_users mine LEFT JOIN acl_roles_users others ON others.role_id = mine.role_id WHERE !mine.deleted AND !others.deleted AND mine.user_id = '$user_id'";
		$res = $db->query($query);
		while ($row = $db->fetchByAssoc($res)) {
			$ret[ACL_ALLOW_ROLE][] = $row['user_id'];
		}
	  
		if (isset($ret[ACL_ALLOW_OWNER])) $ret[ACL_ALLOW_OWNER] = array_unique($ret[ACL_ALLOW_OWNER]);
		if (isset($ret[ACL_ALLOW_ROLE])) $ret[ACL_ALLOW_ROLE] = array_unique($ret[ACL_ALLOW_ROLE]);
		return $ret;
    }
	// longreach - end added
	
	
	
	

}


?>
