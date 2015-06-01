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

// longreach - added
define ('DEFAULT_ROLE_ID', 'default_-role-0000-0000-000000000001');

require_once('data/SugarBean.php');
class ACLRole extends SugarBean{
	// longreach - start added
	var $id;
	var $name;
	var $description;
	// longreach - end added
	
	var $module_dir = 'ACLRoles';
	var $object_name = 'ACLRole';
	var $table_name = 'acl_roles';
	var $new_schema = true;
	var $disable_row_level_security = true;
	var $relationship_fields = array(
									'user_id'=>'users'
								);
	

	function get_default_list_row($id) {
		$ret = array(
			'id' => $id,
			'name' => translate('LBL_DEFAULT_ROLE', 'ACLRoles'),
			'description' => '',
		);
		return $ret;
	}
	
	static function before_save(RowUpdate &$update) {
		if($update->getField('id') == DEFAULT_ROLE_ID) {
			$fix = array(
				'name' => translate('LBL_DEFAULT_ROLE', 'ACLRoles'),
			);
			$update->set($fix);
		}
	}
	
	static function after_save(RowUpdate &$update) {
		AppConfig::invalidate_cache('acl');
	}
	
	static function update_acl(RowUpdate &$update, $link_name) {
		$model = $update->getModelName();
		if($model == 'ACLRole' && ($link_name == 'users' || $link_name == 'securitygroups')) {
			AppConfig::invalidate_cache('acl');
		}
	}


/**
 * function setAction($role_id, $action_id, $access)
 * 
 * Sets the relationship between a role and an action and sets the access level of that relationship
 *
 * ajrw - updated for 7.0, but should probably move into ACLManager::setLocal
 */
static function setAction($role_id, $category, $name, $access, $type='module'){
	$old = AppConfig::setting("acl.by_role.$role_id.$type.$category.actions.$name");
	if(! $old)
		return false;
	$prev_level = $old['aclaccess'];
    $access = (int)$access;
	if($prev_level != $access) {
		global $db, $timedate, $current_user;
		$q_access = $db->quote($access);
		$now = $timedate->get_gmt_db_datetime();
		$uid = $current_user->id;
		if($old['id']) {
			$q = "UPDATE acl_actions SET aclaccess='$q_access', date_modified='$now', modified_user_id='$uid' WHERE id='{$old['id']}'";
		} else {
			$q_cat = $db->quote($category);
			$q_name = $db->quote($name);
			$guid = create_guid();
			$q = "INSERT INTO acl_actions (id, role_id, acltype, category, name, aclaccess, date_entered, date_modified, created_by, modified_user_id) "
				. "VALUES('$guid', '$role_id', '$type', '$q_cat', '$q_name', '$q_access', '$now', '$now', '$uid', '$uid')";
		}
		$db->query($q, false);
	}
	return true;
}


/**
 * static getRoleActions($role_id)
 * 
 * gets the actions of a given role
 *
 * @param GUID $role_id
 * @return array of actions 
 */
static function getRoleActions($role_id, $type='module', $editable_only=false){
	$rows = AppConfig::setting("acl.by_role.$role_id.$type");
	$ret = array();
	if($rows) {
		foreach($rows as $mod => $info) {
			if($editable_only && ! empty($info['no_edit']))
				continue;
			foreach($info['actions'] as $name => $action) {
				$ret[$mod][$type][$name] = array(
					'id' => $action['id'],
					'acltype' => $type, 'module' => $mod,
					'aclaccess' => $action['aclaccess'],
				);
			}
		}
	}
	return $ret;
}


/**
 *  toArray()
	 * returns this role as an array
	 *
	 * @return array of fields with id, name, description
	 */
	function toArray(){
		$array_fields = array('id', 'name', 'description');
		$arr = array();
		foreach($array_fields as $field){
			if(isset($this->$field)){
				$arr[$field] = $this->$field;
			}else{
				$arr[$field] = '';
			}
		}
		return $arr;
	}
	
	/**
	 * fromArray($arr)
	 * converts an array into an role mapping name value pairs into files
	 *
	 * @param Array $arr
	 */
	function fromArray($arr){
		foreach($arr as $name=>$value){
			$this->$name = $value;
		}
	}

 	// longreach - start added
	function retrieve($id)
    {
        $ret = parent::retrieve($id);
        if ($id == DEFAULT_ROLE_ID && empty($ret->name)) {
            $this->name = $ret->name = translate('LBL_DEFAULT_ROLE', 'ACLRoles');
        }
        return $ret;
    }


    static function removeUnusedActions($actions)
	{
		return $actions;
        static $remove = array(
            array('Calendar', 'module', '*'),
            array('Dashboard', 'module', '*'),
            array('Forecasts', 'module', '*'),
            array('SecurityGroups', 'module', '*'),
            array('ReportData', 'module', 'access'),
            array('ReportData', 'module', 'edit'),
            array('ReportData', 'module', 'import'),
            array('ReportData', 'module', 'report'),
            array('HR', 'module', 'delete'),
		);

		static $restricted = array(
			'approve' => array('Quotes', 'Timesheets', 'Vacations', 'ExpenseReports'),
		);

        foreach ($remove as $r) {
            if (isset($actions[$r[0]][$r[1]][$r[2]])) $actions[$r[0]][$r[1]][$r[2]] = null;
            if($r[2] == '*' && isset($actions[$r[0]][$r[1]])) {
            	foreach($actions[$r[0]][$r[1]] as $k => $v)
            		if($k != 'access' && (! isset($restricted[$k]) || ! in_array($r[0], $restricted[$k])))
            			$actions[$r[0]][$r[1]][$k] = null;
            }
		}

		foreach ($restricted as $raction => $rmods) {
			foreach ($actions as $module => $action) {
				if (!in_array($module, $rmods)) {
					if (isset($actions[$module]['module'][$raction])) $actions[$module]['module'][$raction] = null;
				}
			}
		}
        return $actions;
    }

	// longreach - end added

}

?>
