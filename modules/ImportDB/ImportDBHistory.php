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
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');


require_once('data/SugarBean.php');

class ImportDBHistory extends SugarBean {
	var $id;
	var $module_id;
	var $source_id;
	var $generated_id;
	var $date_modified;
	var $deleted;

	// static
	var $table_name = 'importdb_history';
	var $object_name = 'ImportDBHistory';
	var $new_schema = true;
	var $module_dir = 'ImportDB';

	function ImportDBHistory() {
		parent::SugarBean();

		$this->date_added = date('Y-m-d H:i:s');
	}

	function getModuleOptions()
	{
		require_once 'include/database/ListQuery.php';
		$lq = new ListQuery('ImportDBHistory', array('module', 'profile_name'));
		$lq->setGroupBy('module_id');
		$rows = $lq->fetchAllRows('module');
		$ret = array();
		foreach ($rows as $row) {
		$name = $row['module'];
		$spec = array('raw_values' => $row);
		$ret[$row['module_id']] = array(
			'name' => array_get_default($GLOBALS['app_list_strings']['moduleListSingular'], $name, $name) . ' / ' . self::moduleProfileName($spec),
			'icon' => 'theme-icon active module-' . $name, 
		);

		}
		return $ret;
	}
	
	function getSourceOptions()
	{
		require_once 'include/database/ListQuery.php';
		$lq = new ListQuery('ImportDBProfile');
		$rows = $lq->fetchAllRows('name');
		$ret = array();
		foreach ($rows as $row) {
			$ret[$row['id']] = $row['name'];

		}
		return $ret;
	}
	
	static function moduleProfileName($spec) {
		static $map = array();
		if (!isset($spec['raw_values']['module_id']))
			return '';
		$id = $spec['raw_values']['module_id'];
		if (!isset($map[$id])) {
			require_once 'modules/ImportDB/profiles/ImportDBProfileManager.php';
			$name = '';
			require_once 'include/database/ListQuery.php';
			$lq = new ListQuery('ImportDBModule');
			$lq->addField('profile');
			$lq->addFilterPrimaryKey($id);
			$row = $lq->runQuerySingle();
			if ($row) {
				$class = $row->getField('profile');
				ImportDBProfileManager::init();
				try {
					$profile = ImportDBProfileManager::getProfile($class);
					$name = $profile->getTitle();
				} catch (OutOfBoundsException $e) {
					$name = '???';
				}
			}
			$map[$id] = $name;
		}
		return $map[$id];
	}
	
	static function listupdate_perform($mu, $perform, &$listFmt, &$list_result, $uids) {
		if ($perform != 'DeleteWithRelated')
			return;
		$modelMap = array();;
		while(! $list_result->failed) {
			$upd = new RowUpdate($list_result->base_model);
			foreach($list_result->getRowIndexes() as $idx) {
				$row = $list_result->getRowResult($idx);
				$module_id = $row->getField('module_id');
				if (!isset($modelMap[$module_id])) {
					$mr = ListQuery::quick_fetch_row('ImportDBModule', $row->getField('module_id'), array('name'));
					if ($mr)
						$modelMap[$module_id] = AppConfig::module_primary_bean($mr['name'], false);
					else
						$modelMap[$module_id] = false;
				}
				if ($modelMap[$module_id]) {
					$relUpdate = new RowUpdate($modelMap[$module_id]);
					$relUpdate->new_record = false;
					if ($relUpdate->setOriginal(array('deleted' => 0, 'id' => $row->getField('generated_id')))) {
						$relUpdate->markDeleted(1);
					}
				}
				if($upd->setOriginal($row)) {
					$upd->markDeleted(1);
				}
			}
		
			if($list_result->page_finished)
				break;
			$listFmt->pageResult($list_result, true);
		}
	}
}
