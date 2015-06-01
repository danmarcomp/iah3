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

class ActivityLog extends SugarBean {

	var $id;
	var $deleted;
    var $date_entered;
    var $assigned_user_id;
    var $module_name;
    var $record_item_id;
    var $primary_account_id;
    var $primary_contact_id;
    var $status;
    var $moved_to;

	var $table_name = "activity_log";

	var $object_name = "ActivityLog";
	var $module_dir = "ActivityLog";

    /** in Days */
    const DEFAULT_CLEAR_INTERVAL = 60;
    /** in Minutes */
    const QUICK_CLEAR_INTERVAL = 15;

    /**
     * @param RowUpdate $record_update
     * @return mixed
     */
    public static function delete_record_activities(RowUpdate $record_update) {
        $result = null;
        if ($record_update->model->activity_log_enabled) {
            /** @var $db DBManager */
            global $db;

            $activity = new ActivityLog();
            $query = "UPDATE `".$activity->table_name."` SET `deleted` = '1' WHERE `module_name` = '" .$record_update->getModuleDir(). "'
                AND `record_item_id` = '" .$record_update->getPrimaryKeyValue(). "' AND `deleted` = '0'";
            $result = $db->query($query);
        }

        return $result;
    }

    /**
     * @param RowUpdate $record_update
     * @param string $status
     */
	public function addActivity(RowUpdate $record_update, $status) {
		$status_array = array();
		if (is_array($status)) {
			$status_array = $status;
			$status = $status['status'];
		}
    	$module = $record_update->getModuleDir();
        $activity = array(
            'assigned_user_id' => AppConfig::current_user_id(),
            'module_name' => $module,
            'record_item_id' => $record_update->getPrimaryKeyValue(),
            'status' => $status,
            'primary_account_id' => null,
            'primary_contact_id' => null,
        );

        $primary_fields = array('account_id', 'contact_id');

        for ($i = 0; $i < sizeof($primary_fields); $i++) {
            $field = $primary_fields[$i];
            $primary_id = null;

            if ($record_update->getField('primary_' . $field)) {
                $primary_id = $record_update->getField('primary_' . $field);
            } elseif ($record_update->getField($field)) {
                $primary_id = $record_update->getField($field);
            } else if($record_update->getField('billing_' . $field)) {
            	$primary_id = $record_update->getField('billing_' . $field);
            }

            if ($primary_id)
                $activity['primary_' . $field] = $primary_id;
        }
        
        if(! $activity['primary_account_id'] && $record_update->getField('parent_type') == 'Accounts') {
        	$activity['primary_account_id'] = $record_update->getField('parent_id');
        }

        if ($status == 'moved') {
            $activity['moved_to'] = $record_update->getField('date_start');
            $this->removeRelatedMovedActivities($activity['module_name'], $activity['record_item_id'], $status);
        } elseif ($status != 'created') {
            $this->removeQuicklyUpdatedActivities($activity['module_name'], $activity['record_item_id']);
        } else {
            $activity['moved_to'] = $record_update->getField('date_start');
		}

		if (isset($status_array['converted_to_id'])) {
			$activity['converted_to_id'] = $status_array['converted_to_id'];
			$activity['converted_to_type'] = $status_array['converted_to_type'];
		}

        $activity_update = RowUpdate::blank_for_model($this->object_name);
        $activity_update->set($activity);
        $activity_update->save();
    }

    /**
     *
     * @return mixed
     */
    public function deleteOldActivities() {
        /** @var $db DBManager */
        global $timedate, $db;

        $interval = AppConfig::setting('site.log.clear_activities_interval', self::DEFAULT_CLEAR_INTERVAL);
        $query = 'DELETE FROM `' .$this->table_name. '` WHERE (`date_entered` <= DATE_SUB("' .$timedate->get_gmt_db_date(). '", INTERVAL ' .$interval. ' DAY)) OR deleted = "1"';
        $result = $db->query($query);

        return $result;
    }

    /**
     * @param string $module
     * @param string $record
     * @param string $status
     * @return mixed
     */
    private function removeRelatedMovedActivities($module, $record, $status) {
        /** @var $db DBManager */
        global $db;

        $query = "UPDATE `".$this->table_name."` SET `deleted` = '1' WHERE `module_name` = '$module' AND `record_item_id` = '$record' AND `status` = '$status'";
        $result = $db->query($query);

        return $result;
    }

    /**
     * @param string $module
     * @param string $record
     * @return mixed
     */
    private function removeQuicklyUpdatedActivities($module, $record) {
        /** @var $db DBManager */
        global $timedate, $db;

        $query = "UPDATE `".$this->table_name."`
            SET `deleted` = '1'
            WHERE `module_name` = '$module' AND `record_item_id` = '$record'
            AND (`status` != 'created' AND `status` != 'moved')
            AND (`date_entered` >= DATE_SUB('" .$timedate->get_gmt_db_datetime(). "', INTERVAL " .self::QUICK_CLEAR_INTERVAL. " MINUTE))";

        $result = $db->query($query);

        return $result;
    }
	
	
	function get_search_module_options()
	{
		$modules = array();
		$beans = AppConfig::setting('modinfo.primary_beans');
		foreach ($beans as $module => $model) {
			if (AppConfig::setting("model.detail.$model.activity_log_enabled")) {
				$modules[$module] = translate('LBL_MODULE_TITLE', $module);
			}
		}
		asort($modules);
		return $modules;
	}
	
	function bean_implements($interface){
		switch($interface){
			case 'ACL':return true;
		}
		return false;
	}
}
?>
