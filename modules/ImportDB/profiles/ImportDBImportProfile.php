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
abstract class ImportDBImportProfile {
	/**
	 * @var RowResult
	 */
	private $importdb_profile = null;
	/**
	 * @var RowResult[]
	 */
	private $importdb_modules = array();
	/**
	 * @var array
	 */
	protected $mod_strings = array();
	/**
	 * @var array
	 */
	protected $app_strings = array();

	public function __construct() {
		$this->mod_strings = return_module_language('', 'ImportDB');
		$this->app_strings = return_application_language('');
	}

	/**
	 * Returns import profile title
	 *
	 * @abstract
	 * @return string
	 */
	abstract public function getTitle();

	/**
	 * Gets full modules list available for import profile
	 *
	 * @abstract
	 * @return array
	 */
	abstract protected function getModulesList();

	/**
	 * Returns array of modules available for import.
	 *
	 * @var string $module
	 * @var string $value
	 * @return array|string
	 */
	public function getAvailableModules($module = null, $value = 'title') {
		$modules = $this->getModulesList();

		if (is_null($module)) {
			if (is_null($value)) {
				return $modules;
			} else {
				$out = array();
				foreach ($modules as $name => $data) {
					$out[$name] = isset($data[$value]) ? $data[$value] : null;
				}
				return $out;
			}
		} else {
			if (isset($modules[$module])) {
				return (is_null($value) ? $modules[$module] : $modules[$module][$value]);
			}
		}
	}

	/**
	 * Returns import profile name
	 *
	 * @return string
	 */
	public function getName() {
		return get_class($this);
	}

	public function uploadFormFields($module) {
		return '';
	}

	public function processFormFields($module) {
	}

	protected function csvLine($csv_file_handle) {
		return fgetcsv($csv_file_handle);
	}


	protected function filterExpectedColumns(&$cols)
	{
	}

	/**
	 * Prepare import action.
	 * Returns html (form) if prepare step is required.
	 *
	 * @throws BadMethodCallException
     * @throws LogicException
	 * @param string $module
	 * @param resource $csv_file_handle
	 * @return string
	 */
	public function prepareImport($module, $csv_file_handle) {
		$header_idx = $this->getHeaderIdx($csv_file_handle, $module);
		$header = $header_idx ? array_flip($header_idx) : array();
		$expected_columns = $this->getAvailableModules($module, 'columns');
		$this->filterExpectedColumns($expected_columns);

		$missed_columns = array();
		foreach ($expected_columns as $column => $data) {
			if (!isset($header_idx[$column])) $missed_columns[] = $column;
		}
		if (!empty($missed_columns)) {
			throw new LogicException($this->mod_strings['MSG_MISSING_COLUMNS'] . ': ' . implode(', ', $missed_columns));
		}

		$method_name = $this->moduleName2MethodName($module, 'prepare');
		if (method_exists($this, $method_name)) {
			return call_user_func_array(array($this, $method_name), array($csv_file_handle, $header, $header_idx));
		} else {
			throw new BadMethodCallException(sprintf($this->mod_strings['MSG_PREPARE_NOT_IMPLEMENTED'], $module));
		}
	}

	/**
	 * Processes prepare import action.
	 * Returns errors array if any.
	 *
	 * @throws BadMethodCallException
	 * @param string $module
	 * @return array
	 */
	public function processPrepareImport($module) {
		$method_name = $this->moduleName2MethodName($module, 'process');
		if (method_exists($this, $method_name)) return call_user_func_array(array($this, $method_name), array());
		else throw new BadMethodCallException(sprintf($this->mod_strings['MSG_PROCESS_NOT_IMPLEMENTED'], $module));
	}

	protected function getHeaderIdx($csv_file_handle, $module = null) {
		$header = $this->csvLine($csv_file_handle);
		$header_idx = array_flip($header);

		return $header_idx;
	}
	
	public function preExecute($module)
	{
		$method_name = $this->moduleName2MethodName($module, 'preExecute');
		if (method_exists($this, $method_name)) return call_user_func_array(array($this, $method_name), array());
		else throw new BadMethodCallException(sprintf($this->mod_strings['MSG_PROCESS_NOT_IMPLEMENTED'], $module));
	}

	public function processPreExecute($module)
	{
		$method_name = $this->moduleName2MethodName($module, 'processPreExecute');
		if (method_exists($this, $method_name)) return call_user_func_array(array($this, $method_name), array());
		else throw new BadMethodCallException(sprintf($this->mod_strings['MSG_PROCESS_NOT_IMPLEMENTED'], $module));
	}

	/**
	 * Execute import action
	 *
	 * @throws BadMethodCallException
	 * @param string $module
	 * @param resource $csv_file_handle
	 * @param string $progress_filename
	 * @param int $offset
	 * @param int $limit
	 * @return int imported entities count
	 */
	public function executeImport($module, $csv_file_handle, $progress_filename, $offset, $limit) {
		$total_rows = $this->getTotalRowsCount($csv_file_handle);
		if ($total_rows < $offset) {
			return 0;
		}

		$total2read = $total_rows - $offset;
		if ($total2read > $limit) {
			$total2read = $limit;
		}

		require_once 'include/ProgressIndicator.php';
		$progress_process = $this->mod_strings['LBL_MODULE_TITLE'];
		$progressIndicator = new ProgressIndicator($progress_filename, true);
		$this->updateProgress($progressIndicator, $total2read, 0, $progress_process);

		$ent_data = $this->getAvailableModules($module, null);
		$header_idx = $this->getHeaderIdx($csv_file_handle, $module);
		$import_mod_id = $this->getImportDBModule($module)->getField('id');

		$count = 0;
		$processed = -$offset;
		$line_number = 0;
		while (($data = $this->csvLine($csv_file_handle)) !== false) {
			$line_number++;
			$processed++;
			$offset--;
			if ($offset >= 0 || isDataEmpty($data)) {
				$this->updateProgress($progressIndicator, $total2read, $processed, $progress_process);
				continue;
			}

			if(! empty($header_idx['id']) && ! empty($data[$header_idx['id']])) {
				$lq = new ListQuery('ImportDBHistory');
				$lq->addFilterClauses(
					array(
						array('field' => 'module_id', 'value' => $import_mod_id),
						array('field' => 'source_id', 'value' => $data[$header_idx['id']]),
					)
				);
				$history_row = $lq->runQuerySingle();
				if ($history_row->getResultCount()) {
					$this->updateProgress($progressIndicator, $total2read, $processed, $progress_process);
					continue;
				}
			}

			$import_bean_name = AppConfig::module_primary_bean($module);
			$import_row = RowUpdate::blank_for_model($import_bean_name);
			$updates = array();

			foreach ($ent_data['columns'] as $column => $action) {
				$bean_field = empty($action['params']['bean_field']) ? $column : $action['params']['bean_field'];
				if ( ($pos = strpos($bean_field, '/name')) !== false) {
					$id_field = substr($bean_field, 0, $pos);
					$def = $import_row->model->getFieldDefinition($id_field);
					$ref_field = array_get_default($def, 'for_ref');
					if ($ref_field) {
						$ref_def = $import_row->model->getFieldDefinition($ref_field);
						$action['action'] = 'get_id_from_name';
						if (isset($ref_def['dynamic_module'])) {
							$action['params']['dynamic_module'] =  $ref_def['dynamic_module'];
						} else {
							$action['params']['bean_name'] =  $ref_def['bean_name'];
						}
						$action['params']['id_field'] =  $bean_field;
						$bean_field = $id_field;
					}
				}
				
				if (empty($action['action'])) $action['action'] = 'copy';

				switch ($action['action']) {
					case 'skip':
						break;
					case 'const':
						$updates[$bean_field] = $action['params']['value'];
						break;
					case 'get_from_imported':
						$source_id = trim($data[$header_idx[$action['params']['id_field']]]);
						if (strlen($source_id)) {
							$val = $this->actionGetFromImported($action, $data, $header_idx);
							if (strlen($val)) $updates[$bean_field] = $val;
						}
						break;
					case 'get_id_from_name':
						$source_id = trim($data[$header_idx[$action['params']['id_field']]]);
						if (strlen($source_id)) {
							$val = $this->actionGetIdFromName($action, $data, $header_idx);
							if (strlen($val)) $updates[$bean_field] = $val;
						}
						break;
					case 'custom':
						$ret = call_user_func_array(
							array($this, $action['params']['method']),
							array($column, &$import_row, $header_idx, $data)
						);
						if (!empty($ret)) $updates[$bean_field] = $ret;
						break;
					case 'default_on_empty':
						$value = trim($data[$header_idx[$column]]);
						$updates[$bean_field] = (empty($value) ? $action['params']['default'] : $value);
						break;
					case 'copy':
					default:
						$val = $data[$header_idx[$column]];
						$updates[$bean_field] = $val;
				}
			}

			if (!empty($ent_data['bean_extra_fields'])) {
				foreach ($ent_data['bean_extra_fields'] as $bean_field => $action) {
					if (isset($updates[$bean_field])) continue;
					switch ($action['action']) {
						case 'get_from_imported':
							$val = $this->actionGetFromImported($action, $data, $header_idx);
							$updates[$bean_field] = $val;
							break;
						case 'custom':
							$ret = call_user_func_array(
								array($this, $action['params']['method']),
								array($bean_field, &$import_row, $header_idx, $data)
							);
							if (!empty($ret)) $updates[$bean_field] = $ret;
							break;
						case 'copy_exists':
							$val = (empty($header_idx[$action['params']['source']]) ? $action['params']['default'] : $data[$header_idx[$action['params']['source']]]);
							$updates[$bean_field] = $val;
							break;
					}
				}
			}

			// map translated enums
			global $app_list_strings;
			foreach ($updates as $f => $v) {
				$def = $import_row->model->getFieldDefinition($f);
				if (!is_array($def))
					continue;
				if ($def['type'] != 'enum')
					continue;
				$opts = array_get_default($def, 'options');
				if ($opts) {
					$opts = array_get_default($app_list_strings, $opts);
					if (is_array($opts)) {
						$opts = array_flip(array_map('mb_strtolower', $opts));
						$v = mb_strtolower($v);
						if (isset($opts[$v]))
							$updates[$f] = $opts[$v];
					}
				}
			}

			$import_row->loadInput($updates, true);
			$messages = importCheckEncoding($updates);

			if (empty($messages))
				$messages = ImportDBBadCases::isBadCase(array('row' => $import_row));

			if (empty($messages)) {
				$import_row->save();
				
				$source_id = array_get_default($data, array_get_default($header_idx, 'id'));
				//if ($source_id) {
					$bean_id = $import_row->getPrimaryKeyValue();
	
					$history_row = RowUpdate::blank_for_model('ImportDBHistory');
					$history_row->set('module_id', $import_mod_id);
					$history_row->set('source_id', $source_id);
					$history_row->set('generated_id', $bean_id);
					$history_row->save();
				//}

				if (!empty($ent_data['postsave'])) {
					foreach ($ent_data['postsave'] as $funcname) {
						call_user_func_array(array($this, $funcname), array($bean_id, &$import_row, $header_idx, $data));
					}
				}
				$count++;
			} else {
				$tpl = translate('MSG_SKIPPED_ROW_TEMPLATE', 'ImportDB');
				foreach ($messages as $msg) {
					importAddWarning(sprintf($tpl, $line_number, $msg));
				}
			}

			$this->updateProgress($progressIndicator, $total2read, $processed, $progress_process);

			if ($processed >= $total2read) {
				break;
			}
		}
		$progressIndicator->finish();
		return $count;
	}

	/**
	 * Gets total data rows count.
	 *
	 * @param resource $csv_file_handle
	 * @return int
	 */
	public function getTotalRowsCount($csv_file_handle) {
		$current_offset = ftell($csv_file_handle);
		fseek($csv_file_handle, 0);
		$count = 0;
		while (($data = $this->csvLine($csv_file_handle)) !== false && !isDataEmpty($data)) $count++;
		fseek($csv_file_handle, $current_offset);
		--$count;
		return $count;
	}

	/**
	 * @return RowResult
	 */
	public function &getImportDBProfile() {
		if (is_null($this->importdb_profile)) {
			$lq = new ListQuery('ImportDBProfile', array('id', 'name'));
			$lq->addFilterClause(array('field' => 'name', 'value' => $this->getName()));
			$this->importdb_profile = $lq->runQuerySingle();

			if (!$this->importdb_profile->getResultCount()) {
				$upd = new RowUpdate('ImportDBProfile');
				$upd->new_record = true;
				$upd->set('name', $this->getName());
				$upd->save();

				$this->importdb_profile = $lq->runQuerySingle();
			}
		}
		return $this->importdb_profile;
	}

	/**
	 * @param string $module
	 * @param bool $force_create create module if not found
	 * @return RowResult
	 */
	public function &getImportDBModule($module, $force_create = true) {
		if (empty($this->importdb_modules[$module])) {
			$lq = new ListQuery('ImportDBModule', array('id', 'profile_id', 'name'));
			$lq->addFilterClauses(
				array(
					array('field' => 'profile_id', 'value' => $this->getImportDBProfile()->getField('id')),
					array('field' => 'name', 'value' => $module),
				)
			);
			$row = $lq->runQuerySingle();

			if ($row->getResultCount()) {
				$this->importdb_modules[$module] = $row;
			} elseif ($force_create) {
				$upd = new RowUpdate('ImportDBModule');
				$upd->new_record = true;
				$upd->set(
					array(
						'profile_id' => $this->getImportDBProfile()->getField('id'),
						'name' => $module,
					)
				);
				$upd->save();

				$this->importdb_modules[$module] = $lq->runQuerySingle();
			}
		}

		$out = (empty($this->importdb_modules[$module]) ? null : $this->importdb_modules[$module]);
		return $out;
	}

	/**
	 * Removes data by history ID
	 *
	 * @param string $module
	 * @param array $history_ids
	 * @return void
	 */
	public function removeData($module, array $history_ids) {
		$beans = $this->getAvailableModules($module, 'beans');
		$module_bean = $beans[0];
		include_bean($module_bean, true);

		foreach ($history_ids as $id) {
			$h = new ImportDBHistory();
			$h->retrieve($id);

			if ($module_bean == 'User' && $h->source_id == '1') {
				$query = sprintf("DELETE FROM %s WHERE id = '%s'", $h->table_name, $id);
				DBManagerFactory::getInstance()->query($query);
			} else {
				/** @var $bean SugarBean */
				$bean = new $module_bean();
				$bean->mark_deleted($h->generated_id);
			}
		}
	}

	private function updateProgress(ProgressIndicator &$progressIndicator, $total_rows, $processed, $progress_process) {
		$percent = 100 * $processed / $total_rows;
		$overall = $percent;

		if ($percent > 100) {
			$percent = $overall = 100;
		}

		$progressIndicator->logProgress($progress_process, $percent, $overall);
	}

	/**
	 * Gets import handler by module name
	 *
	 * @param string $module
     * @param string $action
	 * @return string
	 */
	protected function moduleName2MethodName($module, $action) {
		// check for module existance and include all required files
		$this->getAvailableModules($module);
		return $action . str_replace(array(' '), array(''), $module);
	}

	/**
	 * Converts imported date to required format
	 *
	 * @param string $imported_date
	 * @param string $format
	 * @return string
	 */
	protected function getDate($imported_date, $format) {
        if (empty($imported_date)) return null;
		$formats = array('datetime' => 'Y-m-d H:i:s', 'date' => 'Y-m-d', 'time' => 'H:i:s');
		$date_format = $formats[$format];

		$tst = strtotime($imported_date);
		if (!$tst) {
			$tst = strtotime(str_replace('/', '.', $imported_date));
		}
		return gmdate($date_format, $tst);
	}
	
	/**
	 * Import action 'get_from_imported' implementation
	 *
	 * @param array $action
	 * @param array $data
	 * @param array $header_idx
	 * @return string
	 */
	protected function actionGetFromImported(array $action, array $data, array $header_idx) {
		static $cache = array();

		// large cache size causes memory overhead error
		if (count($cache) > 100) {
			unset($cache);
			$cache = array();
		}


		if (isset($action['params']['dynamic_module'])) {
			$module = trim($data[$header_idx[$action['params']['dynamic_module']]]);
		} else {
			$module = $action['params']['module'];
		}

		if (empty($module)) return null;
		$source_id = trim($data[$header_idx[$action['params']['id_field']]]);
		if (empty($source_id)) return null;

		if (!empty($action['params']['id_modifier'])) {
			$source_id = call_user_func($action['params']['id_modifier'], $source_id);
		}
		
		if (!isset($cache[$action['params']['module']][$source_id])) {
			$module_id = $this->getImportDBModule($action['params']['module'])->getField('id');

			$lq = new ListQuery('ImportDBHistory', array('id', 'generated_id'));
			$lq->addFilterClauses(
				array(
					array('field' => 'module_id', 'value' => $module_id),
					array('field' => 'source_id', 'value' => $source_id),
				)
			);
			$history_row = $lq->runQuerySingle();

			if ($history_row->getResultCount()) {
				$bean_name = AppConfig::module_primary_bean($action['params']['module']);
				if (!$bean_name) $bean_name = $action['params']['bean'];

				$lq = new ListQuery($bean_name, array($action['params']['field']));
				$bean_row = $lq->queryRecord($history_row->getField('generated_id'));
				$cache[$action['params']['module']][$source_id] = $bean_row;
			} else {
				$cache[$action['params']['module']][$source_id] = null;
			}
		}

		$out = null;
		if (!empty($cache[$action['params']['module']][$source_id])) {
			$out = $cache[$action['params']['module']][$source_id]->getField($action['params']['field']);
		}
		return $out;
	}

	protected function actionGetIdFromName(array $action, array $data, array $header_idx)
	{
		static $cache = array();
		$bean_name = null;
		if (isset($action['params']['dynamic_module'])) {
			$dynamic_module = trim($data[$header_idx[$action['params']['dynamic_module']]]);
			$bean_name = AppConfig::module_primary_bean($dynamic_module);
		} else  {
			$bean_name = $action['params']['bean_name'];
		}
		if (empty($bean_name))
			return null;
		$name = trim($data[$header_idx[$action['params']['id_field']]]);
		$hash = md5($name);
		if (isset($cache[$bean_name][$hash]))
			return $cache[$bean_name][$hash];
		

		switch ($bean_name) {
			case 'User':
				$name_fields = array('user_name');
				break;
			case 'Currency':
				$name_fields = array('iso4217');
				break;
			default:
				$model = new ModelDef($action['params']['bean_name']);
				if ($model == 'Contact' || $bean_name == 'Lead' || $bean_name == 'Prospect') {
					$name_fields = array('first_name', 'last_name');
				} else {
					$name_fields = array('name');
				}
				break;
		}
		$lq = new ListQuery($bean_name);
		$literal = false;
		if (count($name_fields) > 1) {
			$f = 'CONCAT(' . join(', ', $name_fields) . ') = \'' . PearDatabase::quote($name) . '\'';
			$literal = true;
		} else {
			$f = $name_fields[0];
		}
		if ($literal) {
			$lq->addFilterClause(array('value' => $f, 'op' => 'literal'));
		} else {
			$lq->addFilterClause(array('field' => $f, 'value' => $name));
		}
		$lq->addField('id');
		$result = $lq->runQuerySingle();
		if ($result) {
			$id = $result->getField('id');
		} else {
			$id = '';
		}
		return $cache[$bean_name][$hash] = $id;
	}

	public function processPreExecuteContacts()
	{
		return $this->processPreExecuteProspects();
	}

	public function processPreExecuteLead()
	{
		return $this->processPreExecuteProspects();
	}

	public function processPreExecuteProspects()
	{
		$action = array_get_default($_POST, 'import_create_new');
		switch ($action) {
			case '1':
				importSessionSet('__ImportDB.PROSPECTLIST', array(
					'action' => 'create',
					'name' => $_POST['import_new_name'],
					'type' => $_POST['import_new_type'],
				));
				break;
			case '2':
				importSessionSet('__ImportDB.PROSPECTLIST', array(
					'action' => 'add',
					'id' => $_POST['import_selected_list_id'],
				));
				break;
			default:
				importSessionSet('__ImportDB.PROSPECTLIST', null);
				break;
		}
		$check = array_get_default($_POST, 'import_duplicate_check');
		importSessionSet('__ImportDB.DUP_CHECK', $check);
	}

	public function addToProspectsList($bean_id, &$import_row, $header_idx, $data)
	{
		if (!isset($_SESSION['__ImportDB']['PROSPECTLIST'])) {
			return;
		}
		$action = array_get_default($_SESSION['__ImportDB']['PROSPECTLIST'], 'action');
		if (!isset($this->prospectList)) {
			switch ($action) {
				case 'create':
					$name = (string)array_get_default($_SESSION['__ImportDB']['PROSPECTLIST'], 'name');
					$type = array_get_default($_SESSION['__ImportDB']['PROSPECTLIST'], 'type');
					if ($name !== '') {
						$pl = RowUpdate::blank_for_model('ProspectList');
						$pl->set('name', $name);
						$pl->set('list_type', $type);
						$pl->set('assigned_user_id', AppConfig::current_user_id());
						$pl->save();
						$id = $pl->getPrimaryKeyValue();
						importSessionSet('__ImportDB.PROSPECTLIST.action', 'add');
						importSessionSet('__ImportDB.PROSPECTLIST.id', $id);
						$this->prospectList = $pl;
					}
					break;
				case 'add':
					$id = array_get_default($_SESSION['__ImportDB']['PROSPECTLIST'], 'id');
					if ($id) {
						$plr = ListQuery::quick_fetch('ProspectList', $id);
						if ($plr) {
							$this->prospectList = RowUpdate::for_result($plr);
						}
					}
			}
		}
		if (!$this->prospectList) {	
			importSessionSet('__ImportDB.PROSPECTLIST', null);
			return;
		}
		$link = strtolower($import_row->model->getModuleDir());
		$this->prospectList->addUpdateLink($link, $import_row->getPrimaryKeyValue());
	}

	public function preExecuteContacts()
	{
		return $this->preExecuteProspects();
	}
	
	public function preExecuteLeads()
	{
		return $this->preExecuteProspects();
	}

	public function preExecuteProspects()
	{
		global $app_list_strings;
		$pl_lang = return_module_language('','ProspectLists');;
		$options = get_select_options_with_id($app_list_strings['prospect_list_type_dom'], 'default');
		return <<<HTML
<h3>{$this->mod_strings['LBL_PROCESS_IMPORTED_PROSPECTS']}</h3>
<table>
<tbody>
<tr>
	<td colspan="3">
		<select name="import_create_new" onchange="$('row_type').style.display = $('row_create_new').style.display = this.value=='1'?'':'none'; $('row_use_existing').style.display=this.value=='2'?'':'none'">
			<option value="0" selected="selected">{$this->mod_strings['LBL_CREATE_NO_PLIST']}</option>
			<option value="1" >{$pl_lang['LBL_CONVERT_NEW']}</option>
			<option value="2" >{$pl_lang['LBL_CONVERT_EXISTING']}</option>
		</select>
	</td>
</tr>
<tr>
	<td colspan="3"><hr /></td>
</tr>
</tbody>
<tbody>
<tr id="row_create_new" style="display:none">
	<td>{$pl_lang['LBL_CONVERT_NEW_NAME']}</td>
	<td><input type="text" name="import_new_name" id="new_name"/></td>
</tr>
<tr id="row_type" style="display:none">
	<td>{$pl_lang['LBL_LIST_TYPE']}</td>
	<td><select name="import_new_type" >$options</select></td>
</tr>
<tr id="row_use_existing" style="display:none">
	<td>{$pl_lang['LBL_CONVERT_SELECT']}</td>
	<td id="list_select"></td>
</tr>
</tbody>
</table>
<h3>{$this->mod_strings['LBL_DUPLICATE_CHECK']}</h3>
<table>
<tr>
	<td colspan="3">
		<select name="import_duplicate_check">
			<option value="name_or_email" selected="selected">{$this->mod_strings['LBL_DUP_NAME_OR_EMAIL']}</option>
			<option value="name_and_email">{$this->mod_strings['LBL_DUP_NAME_AND_EMAIL']}</option>
			<option value="name_only">{$this->mod_strings['LBL_DUP_NAME']}</option>
			<option value="email_only">{$this->mod_strings['LBL_DUP_EMAIL']}</option>
			<option value="">{$this->mod_strings['LBL_DUP_NONE']}</option>
		</select>
	</td>
</tr>
</table>
<script type="text/javascript">
	var form = $('importdb-preexecute-form');
	var attrs = {module: 'ProspectLists'};
    attrs.init_key = '';
	attrs.init_value = '';
	attrs.form = form;
	attrs.key_name = 'import_selected_list_id';
	attrs.key_id = 'import_selected_list_id';
    var input = new SUGAR.ui.RefInput('import_selected_list', attrs);
    SUGAR.ui.registerInput(form, input);
	var el = input.render();
	$('list_select').appendChild(el);
</script>

HTML;
	}

}

