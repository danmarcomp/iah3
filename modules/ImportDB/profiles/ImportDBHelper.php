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
class ImportDBHelper {
	/**
	 * @var ImportDBImportProfile
	 */
	private $profile;
	/**
	 * This is DBManager instance actually, but for better code completion we assume that this is
	 * concrete DBManager instance.
	 *
	 * @var MysqlManager
	 */
	private $db;
	/**
	 * @var array
	 */
	private $mod_strings = array();

	/**
	 * Constructor
	 *
	 * @param ImportDBImportProfile $profile
	 * @return void
	 */
	public function __construct(ImportDBImportProfile &$profile) {
		$this->profile = $profile;
		$this->db = DBManagerFactory::getInstance();
		$this->mod_strings = return_module_language('', 'ImportDB');
	}

	/**
	 * Return HTML form for users mapping
	 *
	 * @param resource $csv_file_handle
	 * @param array $header_idx
	 * @return string
	 */
	public function getUsersMappingForm($csv_file_handle, array $header_idx) {
		$missed_users = array();

		// lets do direct users search first
		$module_id = $this->profile->getImportDBModule('Users')->getField('id');
		while (($data = fgetcsv($csv_file_handle)) !== false) {
			if (isDataEmpty($data)) continue;

			$user_id = $data[$header_idx['assigned_user_id']];
			$user_name = $data[$header_idx[(empty($header_idx['assigned_user_name']) ?
					'assigned_user_id' : 'assigned_user_name')]];

			$lq = new ListQuery('ImportDBHistory');
			$lq->addFilterClauses(
				array(
					array('field' => 'module_id', 'value' => $module_id),
					array('field' => 'source_id', 'value' => $user_id),
				)
			);
			$history_row = $lq->runQuerySingle();

			if (!$history_row->getResultCount()) {
				$missed_users[$user_id] = $user_name;
			}
		}

		// now check some known issues
		if (!empty($missed_users)) {
			// empty value will be processed on import execution, so just skip this for now
			unset($missed_users['']);

			// imported admin may have id different from 1
			// but we merged it into our admin record, so now he is 1 for sure
			if (isset($missed_users['1'])) {
				$upd = new RowUpdate('ImportDBHistory');
				$upd->new_record = true;
				$upd->set(array(
					'module_id' => $module_id,
					'source_id' => 1,
					'generated_id' => 1,
				));
				$upd->save();
				unset($missed_users['1']);
			}

			// user id may be equal to user name for some reasons
			// let's check this case
			foreach ($missed_users as $user_name => $value) {
				$lq = new ListQuery('User');
				$lq->addFilterClause(array('field' => 'user_name', 'value' => $user_name));
				$row = $lq->runQuerySingle();

				if ($row->getResultCount()) {
					$upd = new RowUpdate('ImportDBHistory');
					$upd->new_record = true;
					$upd->set(array(
						'module_id' => $module_id,
						'source_id' => $user_name,
						'generated_id' => $row->row['id'],
					));
					$upd->save();
					unset($missed_users[$user_name]);
				}
			}

			// and finally, we seem to have lost users
			// let's link them to admin user
			if (!empty($missed_users)) {
				foreach ($missed_users as $id => $name) {
					$upd = new RowUpdate('ImportDBHistory');
					$upd->new_record = true;
					$upd->set(array(
						'module_id' => $module_id,
						'source_id' => $id,
						'generated_id' => 1,
					));
					$upd->save();
					unset($missed_users[$id]);
				}
				importSessionSet('__ImportDB.msg_text', $this->mod_strings['MSG_LOST_USERS']);
			}
		}

		// actually this is redundant and came from legacy version with manual mapping
		// but let's support this for a while if any
		if (!empty($missed_users)) {
			$lq = new ListQuery('User', array('id', 'user_name'));
			$all_users = $lq->fetchAll();

			$xtpl_users_options = new XTemplate('modules/ImportDB/tpl/PrepareUsersOptions.html');
			foreach ($all_users->getRowIndexes() as $idx) {
				$user = $all_users->getRowResult($idx);
				$xtpl_users_options->assign('USER', array('id' => $user->row['id'], 'name' => $user->row['user_name']));
				$xtpl_users_options->parse('main.user');
			}
			$xtpl_users_options->parse('main');
			$options = $xtpl_users_options->text('main');

			$xtpl_user_mapping = new XTemplate('modules/ImportDB/tpl/PrepareUsersMapping.html');
			$xtpl_user_mapping->assign('LABEL_IMPORT_NAME', $this->mod_strings['LBL_IMPORT_UNAME']);
			$xtpl_user_mapping->assign('LABEL_INTERNAL_NAME', $this->mod_strings['LBL_INTERNAL_UNAME']);
			$xtpl_user_mapping->assign('FORM_ELEMENT_NAME', 'import_users');
			$xtpl_user_mapping->assign('MOD_STRINGS', $this->mod_strings);
			foreach ($missed_users as $id => $name) {
				if (empty($name)) $name = $id;
				$xtpl_user_mapping->assign('USER', array('id' => $id, 'name' => $name, 'options' => $options));
				$xtpl_user_mapping->parse('main.user');
			}
			$xtpl_user_mapping->parse('main');
			return $xtpl_user_mapping->text('main');
		}

		return '';
	}

	/**
	 * Process user mapping form submission
	 *
	 * @return void
	 */
	public function processUsersMapping() {
		$this->processModuleMapping('Users', 'import_users');
	}

	/**
	 * Returns HTML for accounts by name mapping
	 *
	 * @param resource $csv_file_handle
	 * @param array $header_idx
	 * @return string
	 */
	public function getAccountsByNameMappingForm($csv_file_handle, array $header_idx) {
		$amap_mod_id = $this->profile->getImportDBModule('AccountsNameMap')->getField('id');
		$aimp_mod_id = $this->profile->getImportDBModule('Accounts')->getField('id');
		$aipm_quoted = PearDatabase::quote($aimp_mod_id, false);

		$model = new ModelDef('Account');
		$a_table = $model->table_name;

		$accouns_mapping = array();
		$matched_names = array();
		while (($data = fgetcsv($csv_file_handle)) !== false) {
			if (isDataEmpty($data)) continue;

			$account_name = $data[$header_idx['account_name']];
			$source_id = md5($account_name);

			$lq = new ListQuery('ImportDBHistory');
			$lq->addFilterClauses(
				array(
					array('field' => 'module_id', 'value' => $amap_mod_id),
					array('field' => 'source_id', 'value' => $source_id),
				)
			);
			$history_row = $lq->runQuerySingle();

			if (!$history_row->getResultCount()) {
				$h_table = $lq->base_model->table_name;

				$name_quoted = PearDatabase::quote($account_name, false);

				$query = "SELECT a.id
							FROM {$a_table} a
							INNER JOIN {$h_table} h ON h.generated_id = a.id AND h.module_id = '{$aipm_quoted}'
							WHERE a.name = '{$name_quoted}'
							LIMIT 1";
				$acc_id = $this->db->getOne($query);
				if (!empty($acc_id)) {
					$matched_names[$source_id] = $acc_id;
				} else {
					$accouns_mapping[$account_name] = $source_id;
				}
			}
		}

		if (!empty($matched_names)) {
			$request_key = '__matched_names';
			$_REQUEST[$request_key] = $matched_names;
			$this->processModuleMapping('AccountsNameMap', $request_key);
		}

		// check some known issues
		if (!empty($accouns_mapping)) {
			// empty value will be processed on import execution, so just skip this for now
			unset($accouns_mapping['']);

			// drop all unknown names for now
			// maybe we will do something with them in the future
			$accouns_mapping = array();
		}

		if (!empty($accouns_mapping)) {
			$xtpl_acc_mapping = new XTemplate('modules/ImportDB/tpl/PrepareAccountsMapping.html');
			$xtpl_acc_mapping->assign('MOD_STRINGS', $this->mod_strings);
			foreach ($accouns_mapping as $account_name => $source_id) {
				$xtpl_acc_mapping->assign('ACC', array('id' => $source_id, 'name' => $account_name, 'options' => $this->getAccOptions()));
				$xtpl_acc_mapping->parse('main.acc');
			}
			$xtpl_acc_mapping->parse('main');
			return $xtpl_acc_mapping->text('main');
		}

		return '';
	}

	protected function getAccOptions() {
		static $cache = '';
		static $all_accounts = array();

		if (empty($all_accounts)) {
			$lq = new ListQuery('Account', array('id', 'name'));
			$all_accounts = $lq->fetchAll();
		}

		if (empty($cache)) {
			$xtpl_acc_options = new XTemplate('modules/ImportDB/tpl/PrepareAccountsOptions.html');
			foreach ($all_accounts->getRowIndexes() as $idx) {
				$acc = $all_accounts->getRowResult($idx);
				$xtpl_acc_options->assign('ACC', array('id' => $acc->row['id'], 'name' => $acc->row['name']));
				$xtpl_acc_options->parse('main.acc');
			}
			$xtpl_acc_options->parse('main');
			$cache = $xtpl_acc_options->text('main');
		}

		return $cache;
	}

	/**
	 * Process user mapping form submission
	 *
	 * @return void
	 */
	public function processAccountsByNameMapping() {
		$this->processModuleMapping('AccountsNameMap', 'import_accounts_name');
	}

	/**
	 * Return HTML form for accounts mapping
	 *
	 * @param resource $csv_file_handle
	 * @param array $header_idx
	 * @return string
	 */
	public function getAccountsMappingForm($csv_file_handle, array $header_idx) {
		return $this->getModuleMappingForm($csv_file_handle, $header_idx, 'Accounts', 'account_id', 'account_name',
			'Account', 'LBL_IMPORT_ACCOUNT', 'LBL_INTERNAL_ACCOUNT', 'import_accounts');
	}

	/**
	 * Process accounts mapping form submission
	 *
	 * @return void
	 */
	public function processAccountsMapping() {
		$this->processModuleMapping('Accounts', 'import_accounts');
	}

	/**
	 * Return HTML form for accounts mapping
	 *
	 * @param resource $csv_file_handle
	 * @param array $header_idx
	 * @return string
	 */
	public function getContactsMappingForm($csv_file_handle, array $header_idx) {
		return $this->getModuleMappingForm($csv_file_handle, $header_idx, 'Contacts', 'contact_id', 'contact_id',
			'Contact', 'LBL_IMPORT_CONTACT', 'LBL_INTERNAL_CONTACT', 'import_contacts');
	}

	/**
	 * Process accounts mapping form submission
	 *
	 * @return void
	 */
	public function processContactsMapping() {
		$this->processModuleMapping('Contacts', 'import_contacts');
	}

	/**
	 * Generic method for mapping form generation
	 *
	 * @param resource $csv_file_handle
	 * @param array $header_idx
	 * @param string $module
	 * @param string $id_field
	 * @param string $name_field
	 * @param string $bean_name
	 * @param string $lbl_import_idx
	 * @param string $lbl_internal_idx
	 * @param string $request_key
	 * @return string
	 */
	protected function getModuleMappingForm($csv_file_handle, array $header_idx, $module, $id_field, $name_field,
		$bean_name, $lbl_import_idx, $lbl_internal_idx, $request_key) {
		$module_id = $this->profile->getImportDBModule($module)->getField('id');
		$missed_users = array();
		while (($data = fgetcsv($csv_file_handle)) !== false) {
			if (isDataEmpty($data)) continue;

			$user_id = $data[$header_idx[$id_field]];
			$user_name = $data[$header_idx[$name_field]];

			$lq = new ListQuery('ImportDBHistory');
			$lq->addFilterClauses(
				array(
					array('field' => 'module_id', 'value' => $module_id),
					array('field' => 'source_id', 'value' => $user_id),
				)
			);
			$history_row = $lq->runQuerySingle();

			if (!empty($user_id) && !$history_row->getResultCount()) {
				$missed_users[$user_id] = $user_name;
			}
		}

		if (!empty($missed_users)) {
			$lq = new ListQuery($bean_name, array('id', 'name'));
			$all_records = $lq->fetchAll();

			$xtpl_users_options = new XTemplate('modules/ImportDB/tpl/PrepareAccountsOptions.html');
			foreach ($all_records->getRowIndexes() as $idx) {
				$record = $all_records->getRowResult($idx);
				$xtpl_users_options->assign('USER', array('id' => $record->row['id'], 'name' => $record->row['name']));
				$xtpl_users_options->parse('main.acc');
			}
			$xtpl_users_options->parse('main');
			$options = $xtpl_users_options->text('main');

			$xtpl_user_mapping = new XTemplate('modules/ImportDB/tpl/PrepareUsersMapping.html');
			$xtpl_user_mapping->assign('LABEL_IMPORT_NAME', $this->mod_strings[$lbl_import_idx]);
			$xtpl_user_mapping->assign('LABEL_INTERNAL_NAME', $this->mod_strings[$lbl_internal_idx]);
			$xtpl_user_mapping->assign('FORM_ELEMENT_NAME', $request_key);
			$xtpl_user_mapping->assign('MOD_STRINGS', $this->mod_strings);
			foreach ($missed_users as $id => $name) {
				$xtpl_user_mapping->assign('USER', array('id' => $id, 'name' => $name, 'options' => $options));
				$xtpl_user_mapping->parse('main.user');
			}
			$xtpl_user_mapping->parse('main');
			return $xtpl_user_mapping->text('main');
		}

		return '';
	}

	/**
	 * Generic method for processing entity mapping
	 *
	 * @param string $module
	 * @param string $request_key
	 * @return void
	 */
	protected function processModuleMapping($module, $request_key) {
		if (!empty($_REQUEST[$request_key])) {
			$module_id = $this->profile->getImportDBModule($module)->getField('id');
			foreach ($_REQUEST[$request_key] as $import_id => $internal_id) {
				$upd = new RowUpdate('ImportDBHistory');
				$upd->new_record = true;
				$upd->set(
					array(
						'module_id' => $module_id,
						'source_id' => $import_id,
						'generated_id' => $internal_id,
					)
				);
				$upd->save();
			}
		}
	}
}
