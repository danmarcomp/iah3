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
abstract class ImportDBSugarGenericProfile extends ImportDBImportProfile {
	/**
	 * @var array
	 */
	protected $std_bean = array();

	public function __construct() {
		parent::__construct();

		$this->std_bean = array(
			'id' => array('action' => 'skip'),
			'date_entered' => array('action' => 'copy_date'),
			'date_modified' => array(
				'action' => 'custom',
				'params' => array(
					'method' => 'dateModified',
				),
			),
			'modified_user_id' => array(
				'action' => 'const',
				'params' => array(
					'value' => 1,
				),
			),
			'created_by' => array(
				'action' => 'const',
				'params' => array(
					'value' => 1,
				),
			),
			'description' => array(),
			'deleted' => array(),
		);
	}

	/**
	 * Custom mapping method for date_modified field.
	 *
	 * @param string $column
	 * @param RowUpdate $row
	 * @param array $header_idx
	 * @param array $data
	 * @return void
	 */
	protected function dateModified($column, RowUpdate &$row, array $header_idx, array $data) {
		// we are going to get date modified field from imported data,
		// so let's disable automatic update date assignment
		$row->set('update_date_modified', false);
		return $this->getDate($data[$header_idx[$column]], 'Y-m-d H:i:s');
	}

	/**
	 * Custom mapping method for User ID.
	 * We need this to merge admin (id=1) accounts instead of creating new one.
	 * We'll replace empty bean with current admin and fill all data from imported later.
	 *
	 * @param string $column
	 * @param RowUpdate $row
	 * @param array $header_idx
	 * @param array $data
	 * @return void
	 */
	protected function userId($column, RowUpdate &$row, array $header_idx, array $data) {
		if ($data[$header_idx['id']] == '1' || $data[$header_idx['user_name']] == 'admin') {
			$lq = new ListQuery('User');
			$user_row = $lq->queryRecord(1);
			$row = new RowUpdate($user_row);
		}
	}

	/**
	 * Custom mapping method for User Hash.
	 *
	 * @param string $column
	 * @param RowUpdate $row
	 * @param array $header_idx
	 * @param array $data
	 * @return void
	 */
	protected function userHash($column, RowUpdate &$row, array $header_idx, array $data) {
		return (($data[$header_idx['id']] == '1' || $data[$header_idx['user_name']] == 'admin') ?
				null : md5(str_replace('.', '', uniqid('', true))));
	}

	/**
	 * Post-save method for Opportunity account_name.
	 *
	 * @param string $bean_id
	 * @param RowUpdate $row
	 * @param array $header_idx
	 * @param array $data
	 * @return void
	 */
	protected function opportunityAccName($bean_id, RowUpdate &$row, array $header_idx, array $data) {
		$action = array(
			'action' => 'get_from_imported',
			'params' => array(
				'module' => 'AccountsNameMap',
				'bean' => 'Account',
				'field' => 'id',
				'id_field' => 'account_name',
			),
		);
		$acc_id = $this->actionGetFromImported($action, $data, $header_idx);

		if (!empty($acc_id)) {
			$row = ListQuery::quick_fetch('Account', $acc_id);
			$upd = new RowUpdate($row);
			$upd->addUpdateLink('opportunities', $bean_id);
		}
	}

	/**
	 * Post-save method for Contact account_name.
	 *
	 * @param string $bean_id
	 * @param RowUpdate $row
	 * @param array $header_idx
	 * @param array $data
	 * @return void
	 */
	protected function contactAccName($bean_id, RowUpdate &$row, array $header_idx, array $data) {
		$action = array(
			'action' => 'get_from_imported',
			'params' => array(
				'module' => 'AccountsNameMap',
				'bean' => 'Account',
				'field' => 'id',
				'id_field' => 'account_name',
			),
		);
		$acc_id = $this->actionGetFromImported($action, $data, $header_idx);

		if (!empty($acc_id)) {
			$row = ListQuery::quick_fetch('Account', $acc_id);
			$upd = new RowUpdate($row);
			$upd->addUpdateLink('contacts', $bean_id);
		}
	}

	/**
	 * Custom mapping method for Calls parent
	 *
	 * @param string $column
	 * @param RowUpdate $row
	 * @param array $header_idx
	 * @param array $data
	 * @return void
	 */
	protected function calendarParentId($column, RowUpdate &$row, array $header_idx, array $data) {
		static $cache = array();

		$parent_type = $data[$header_idx['parent_type']];
		$parent_id = $data[$header_idx['parent_id']];

		$parents_map = array(
			'Accounts' => 'Accounts',
			'Opportunities' => 'Opportunities',
			'Cases' => 'Cases',
			'Leads' => 'Leads',
			'Contacts' => 'Contacts',
		);

		if (!empty($parent_type) && !empty($parent_id) && !empty($parents_map[$parent_type])) {
			if (!isset($cache[$parent_type][$parent_id])) {
				$module_id = $this->getImportDBModule($parents_map[$parent_type])->getField('id');

				$lq = new ListQuery('ImportDBHistory', array('id', 'generated_id'));
				$lq->addFilterClauses(
					array(
						array('field' => 'module_id', 'value' => $module_id),
						array('field' => 'source_id', 'value' => $parent_id),
					)
				);
				$history_row = $lq->runQuerySingle();

				if ($history_row->getResultCount()) {
					$cache[$parent_type][$parent_id] = $history_row->getField('generated_id');
				} else {
					$cache[$parent_type][$parent_id] = null;
				}
			}

			if (!empty($cache[$parent_type][$parent_id])) {
				return $cache[$parent_type][$parent_id];
			}
		}

		$row->set('parent_type', null);
	}

	/**
	 * Custom mapping method for Notes assigned to
	 *
	 * @param string $column
	 * @param RowUpdate $row
	 * @param array $header_idx
	 * @param array $data
	 * @return void
	 */
	protected function noteAssignedTo($column, RowUpdate &$row, array $header_idx, array $data) {
		$parent_type = $row->getField('parent_type');
		$parent_id = $row->getField('parent_id');
		if (!empty($parent_type) && !empty($parent_id)) {
			$parent_bean = AppConfig::module_primary_bean($parent_type);

			$lq = new ListQuery($parent_bean, array('assigned_user_id', 'assigned_user_name'));
			$parent_row = $lq->queryRecord($parent_id);

			$assigned_user_id = $parent_row->getField('assigned_user_id');
			if (!empty($assigned_user_id)) {
				$row->set('assigned_user_id', $assigned_user_id);

				$assigned_user_name = $parent_row->getField('assigned_user_name');
				if (!empty($assigned_user_name)) {
					$row->set('assigned_user_name', $assigned_user_name);
				} else {
					$lq = new ListQuery('User', array('user_name'));
					$user_row = $lq->queryRecord($assigned_user_id);
					$row->set('assigned_user_name', $user_row->getField('user_name'));
				}
			}
		}
	}

	/**
	 * Custom mapping method for Tasks priority
	 *
	 * @param string $column
	 * @param RowUpdate $row
	 * @param array $header_idx
	 * @param array $data
	 * @return void
	 */
	protected function tasksPriority($column, RowUpdate &$row, array $header_idx, array $data) {
		global $app_list_strings;

		$priorities = array_flip($app_list_strings['task_priority_dom']);
		return (isset($priorities[$data[$header_idx['priority']]]) ? $priorities[$data[$header_idx['priority']]] : 'P1');
	}

	protected function prepareAccounts($csv_file_handle, $header, $header_idx) {
		$import_helper = new ImportDBHelper($this);
		$out = $import_helper->getUsersMappingForm($csv_file_handle, $header_idx);
		return $out;
	}

	protected function processAccounts() {
		$import_helper = new ImportDBHelper($this);
		$import_helper->processUsersMapping();
	}

	protected function prepareOpportunities($csv_file_handle, $header, $header_idx) {
		$import_helper = new ImportDBHelper($this);

		$first_row_pos = ftell($csv_file_handle);
		$out = $import_helper->getUsersMappingForm($csv_file_handle, $header_idx);

		fseek($csv_file_handle, $first_row_pos);
		$out .= $import_helper->getAccountsByNameMappingForm($csv_file_handle, $header_idx);

		return $out;
	}

	protected function processOpportunities() {
		$import_helper = new ImportDBHelper($this);
		$import_helper->processUsersMapping();
		$import_helper->processAccountsByNameMapping();
	}

	protected function prepareContacts($csv_file_handle, $header, $header_idx) {
		$import_helper = new ImportDBHelper($this);

		$first_row_pos = ftell($csv_file_handle);
		$out = $import_helper->getUsersMappingForm($csv_file_handle, $header_idx);

		fseek($csv_file_handle, $first_row_pos);
		$out .= $import_helper->getAccountsByNameMappingForm($csv_file_handle, $header_idx);

		return $out;
	}

	protected function processContacts() {
		$import_helper = new ImportDBHelper($this);
		$import_helper->processUsersMapping();
		$import_helper->processAccountsByNameMapping();
	}

	protected function prepareLeads($csv_file_handle, $header, $header_idx) {
		$import_helper = new ImportDBHelper($this);

		$first_row_pos = ftell($csv_file_handle);
		$out = $import_helper->getUsersMappingForm($csv_file_handle, $header_idx);

		fseek($csv_file_handle, $first_row_pos);
		$out .= $import_helper->getContactsMappingForm($csv_file_handle, $header_idx);

		return $out;
	}

	protected function processLeads() {
		$import_helper = new ImportDBHelper($this);
		$import_helper->processUsersMapping();
		$import_helper->processContactsMapping();
	}

	protected function prepareCases($csv_file_handle, $header, $header_idx) {
		$import_helper = new ImportDBHelper($this);

		$first_row_pos = ftell($csv_file_handle);
		$out = $import_helper->getUsersMappingForm($csv_file_handle, $header_idx);

		fseek($csv_file_handle, $first_row_pos);
		$out .= $import_helper->getAccountsMappingForm($csv_file_handle, $header_idx);

		return $out;
	}

	protected function processCases() {
		$import_helper = new ImportDBHelper($this);
		$import_helper->processUsersMapping();
		$import_helper->processAccountsMapping();
	}

	protected function prepareCalls($csv_file_handle, $header, $header_idx) {
		$import_helper = new ImportDBHelper($this);
		$out = $import_helper->getUsersMappingForm($csv_file_handle, $header_idx);
		return $out;
	}

	protected function processCalls() {
		$import_helper = new ImportDBHelper($this);
		$import_helper->processUsersMapping();
	}

	protected function prepareMeetings($csv_file_handle, $header, $header_idx) {
		$import_helper = new ImportDBHelper($this);
		$out = $import_helper->getUsersMappingForm($csv_file_handle, $header_idx);
		return $out;
	}

	protected function processMeetings() {
		$import_helper = new ImportDBHelper($this);
		$import_helper->processUsersMapping();
	}

	protected function prepareTasks($csv_file_handle, $header, $header_idx) {
		$import_helper = new ImportDBHelper($this);
		$out = $import_helper->getUsersMappingForm($csv_file_handle, $header_idx);
		return $out;
	}

	protected function processTasks() {
		$import_helper = new ImportDBHelper($this);
		$import_helper->processUsersMapping();
	}
	
	protected function prepareProspects($csv_file_handle, $header, $header_idx) {
		$import_helper = new ImportDBHelper($this);

		$first_row_pos = ftell($csv_file_handle);
		$out = $import_helper->getUsersMappingForm($csv_file_handle, $header_idx);

		fseek($csv_file_handle, $first_row_pos);
		$out .= $import_helper->getContactsMappingForm($csv_file_handle, $header_idx);

		return $out;
	}

	protected function processProspects() {
		$import_helper = new ImportDBHelper($this);
		$import_helper->processUsersMapping();
		$import_helper->processContactsMapping();
	}

}
