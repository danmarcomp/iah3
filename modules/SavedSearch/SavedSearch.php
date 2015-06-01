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

require_once('data/SugarBean.php');
        
class SavedSearch extends SugarBean {
	// Stored fields
	var $id;
	var $date_entered;
	var $date_modified;
	var $assigned_user_id;
	var $name;
	var $description;
	var $search_module;
    var $columns;
    var $filters;
    var $filter_values;
    var $filter_name;
    var $layout_name;
	
	var $object_name = 'SavedSearch';
	var $table_name = 'saved_search';
	var $module_dir = 'SavedSearch';

    const OWNER_DEFAULT = 'mine';

	static function get_index($module, $user_id=null) {
		$lq = new ListQuery('SavedSearch', array('id', 'name', 'description'));
		$lq->addSimpleFilter('search_module', $module);
		if(! isset($user_id)) $user_id = AppConfig::current_user_id();
		$lq->setAclUserId($user_id);
		$lq->addAclFilter('list');
		$lq->acl_admin_except = false;
		$lq->setOrderBy('date_entered');
		$ret = $lq->runQuery();
		if(! $ret || $ret->failed)
			return null;
		return $ret->rows;
	}

    static function load_search($module, $id, $user_id=null, $as_result=false) {
		$lq = new ListQuery('SavedSearch');
		$lq->addSimpleFilter('search_module', $module);
		if(isset($user_id))
            $lq->setAclUserId($user_id);
        $lq->addAclFilter('view');
		$ret = $lq->queryRecord($id);
		if (! $ret || $ret->failed)
			return null;
		if($as_result)
			return $ret;
		return $ret->row;
	}
	
	static function save_search($module, $id, $values, $user_id=null) {
		if($id)
			$base = self::load_search($module, $id, $user_id, true);
		if(empty($base))
			$ru = RowUpdate::blank_for_model('SavedSearch');
		else
			$ru = RowUpdate::for_result($base);

		$ru->set('search_module', $module);
		if(! $ru->getField('assigned_user_id')) {
			if(! isset($user_id)) $user_id = AppConfig::current_user_id();
			$ru->set('assigned_user_id', $user_id);
		}

		if($values)
			$ru->set($values);
		if($ru->save())
			return $ru;
		return false;
	}

	static function delete_search($module, $id, $user_id=null) {
		$base = self::load_search($module, $id, $user_id, true);
		if(! $base)
			return false;
		$ru = RowUpdate::for_result($base);
		return $ru->markDeleted();
	}

    static function get_owner_clause($owner_id) {
        $owners = array($owner_id, self::OWNER_ALL_VALUE);
        $owner_groups = AppConfig::setting("acl.user_teams.".$owner_id, array());

        if (! empty($owner_groups))
            $owners = array_merge($owners, array_keys($owner_groups));

        $clause = array(
            'field' => 'assigned_user_id',
            'operator' => 'eq',
            'value' => $owners
        );

        return $clause;
    }

    static function get_search_module($id) {
        $layout = ListQuery::quick_fetch_row('SavedSearch', $id, array('search_module'));
        $module = '';
        if (! empty($layout['search_module']))
            $module = $layout['search_module'];
        return $module;
    }
}
?>