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
class ImportDBCustomCSV00Profile extends ImportDBSugar60Profile {
	protected $csv_data = array();
	protected $modules_list;
	protected $header_idx;

	public function __construct() {
		parent::__construct();

		$this->modules_list = $this->getModulesListFrame();
		
		if(! empty($_SESSION['__ImportDB']['customcsv']))
			$this->csv_data = $_SESSION['__ImportDB']['customcsv'];

		if (!empty($this->csv_data['csv_saved_mapping']) ||
		    !empty($this->csv_data['csv_mapping'])
		) {

			if (!empty($this->csv_data['csv_saved_mapping'])) {
				$lq = new ListQuery('ImportDBMap', array('mapping', 'has_header', 'delimiter', 'name'));
				$row = $lq->queryRecord($_SESSION['__ImportDB']['customcsv']['csv_saved_mapping']);

				$this->csv_data['csv_mapping'] = unserialize($row->getField('mapping'));
				//$this->csv_data['csv_has_header'] = $row->getField('has_header');
				//$this->csv_data['csv_delimiter'] = $row->getField('delimiter');
				$this->csv_data['csv_mapping_name'] = $row->getField('name');
			}

			$module = $this->csv_data['module'];
            //$this->addAllFields($module, &$plist);
            //$this->addCustomFields($module, &$plist);

			$import_cols = $this->csv_data['csv_mapping'];
			$this->header_idx = array_flip($import_cols);

			$mlist = $this->modules_list;
			foreach (array_keys($this->modules_list) as $m)
				$this->modules_list[$m]['columns'] = array();
			foreach ($this->csv_data['csv_mapping'] as $import_col => $parent_col) {
				if (!empty($parent_col)) {
					//$this->modules_list[$module]['columns'][$import_col] = $plist[$module]['columns'][$parent_col];
					$this->modules_list[$module]['columns'][$parent_col] = array_get_default($mlist[$module]['columns'], $parent_col);
				}
			}
		}
	}

	public function getTitle() {
		return 'Custom CSV';
	}

	protected function getModulesListFrame() {
		$ret = array(
			'Users' => array(
				'title' => $this->app_strings['LBL_USERS'],
				'module' => 'Users',
				'title_field' => 'user_name',
				'columns' => array(),
				'bean_extra_fields' => array(
					'user_hash' => array(
						'action' => 'custom',
						'params' => array(
							'method' => 'userHash',
						),
					),
				),
			),

			'Accounts' => array(
				'title' => $this->app_strings['LBL_ACCOUNTS'],
				'module' => 'Accounts',
				'title_field' => 'name',
				'columns' => array(),
			),

			'Opportunities' => array(
				'title' => $this->app_strings['LBL_OPPORTUNITIES'],
				'module' => 'Opportunities',
				'title_field' => 'name',
				'columns' => array(),
				'bean_extra_fields' => array(
					'account_id' => array(
						'action' => 'get_from_imported',
						'params' => array(
							'module' => 'AccountsNameMap',
							'bean' => 'Account',
							'field' => 'id',
							'id_field' => 'account_name',
							'id_modifier' => 'md5',
						),
					),
				),
				'postsave' => array('opportunityAccName'),
			),

			'Contacts' => array(
				'title' => $this->app_strings['LBL_CONTACTS'],
				'module' => 'Contacts',
				'title_field' => array('first_name', 'last_name'),
				'columns' => array(),
				'bean_extra_fields' => array(
					'account_id' => array(
						'action' => 'get_from_imported',
						'params' => array(
							'module' => 'AccountsNameMap',
							'bean' => 'Account',
							'field' => 'id',
							'id_field' => 'account_name',
							'id_modifier' => 'md5',
						),
					),
				),
				'postsave' => array('contactAccName', 'addToProspectsList'),
			),

			'Leads' => array(
				'title' => $this->app_strings['LBL_LEADS'],
				'module' => 'Leads',
				'title_field' => array('first_name', 'last_name'),
				'columns' => array(),
				'postsave' => array('addToProspectsList'),
			),

			'Cases' => array(
				'title' => $this->app_strings['LBL_CASES'],
				'module' => 'Cases',
				'title_field' => 'name',
				'columns' => array(),
			),

			'ProductCategories' => array(
				'title' => $this->app_strings['LBL_PRODUCT_CATEGORIES'],
				'module' => 'ProductCategories',
				'title_field' => 'name',
				'columns' => array(),
			),
			'ProductCatalog' => array(
				'title' => $this->app_strings['LBL_PRODUCTS'],
				'module' => 'ProductCatalog',
				'title_field' => 'name',
				'columns' => array(),
			),

			'Calls' => array(
				'title' => $this->app_strings['LBL_CALLS'],
				'module' => 'Calls',
				'title_field' => 'name',
				'columns' => array(),
			),

			'Meetings' => array(
				'title' => $this->app_strings['LBL_MEETINGS'],
				'module' => 'Meetings',
				'title_field' => 'name',
				'columns' => array(),
			),

			'Tasks' => array(
				'title' => $this->app_strings['LBL_TASKS'],
				'module' => 'Tasks',
				'title_field' => 'name',
				'columns' => array(),
			),

			'Notes' => array(
				'title' => $this->app_strings['LBL_NOTES'],
				'module' => 'Notes',
				'title_field' => 'name',
				'columns' => array(),
				'bean_extra_fields' => array(
					'assigned_user_id' => array(
						'action' => 'custom',
						'params' => array(
							'method' => 'noteAssignedTo',
						),
					),
				),
			),
			
			'Prospects' => array(
				'title' => $this->app_strings['LBL_PROSPECTS'],
				'module' => 'Prospects',
				'title_field' => array('first_name', 'last_name'),
				'columns' => array(),
				'postsave' => array('addToProspectsList'),
			),
		);
		$mods = AppConfig::setting('modinfo.index.normal');
		$mods = array_merge($mods, AppConfig::setting('modinfo.index.manual'));
		foreach ($mods as $m) {
			if (!isset($ret[$m])) {
				$bean_name = AppConfig::module_primary_bean($m);
				$importable = AppConfig::setting("model.detail.{$bean_name}.importable");
				if ($importable) {
					$ret[$m] = array(
						'title' => $this->app_strings[$importable],
						'module' => $m,
						'columns' => array(),
					);
				}
			}
		}
		foreach (array_keys($ret) as $m) {
			$this->addAllFields($m, $ret);
			$this->addCustomFields($m, $ret);
		}
		return $ret;
	}

	protected function getModulesList() {
		return $this->modules_list;
	}

	protected function csvLine($csv_file_handle) {
		if (!empty($this->csv_data['csv_delimiter'])) {
			$delim = ',';
			if ($this->csv_data['csv_delimiter'] == 'tab') $delim = "\t";
			elseif ($this->csv_data['csv_delimiter'] == 'semicolon') $delim = ";";
			return fgetcsv($csv_file_handle, null, $delim);
		}
		return parent::csvLine($csv_file_handle);
	}

	protected function getHeaderIdx($csv_file_handle, $module = null) {
		if ($this->csv_data['csv_has_header']) $this->csvLine($csv_file_handle);
		return $this->header_idx;
	}

	public function uploadFormFields($module) {

		$import_module = $this->getImportDBModule($module);

		$xtpl = new ImportDBxtpl('modules/ImportDB/tpl/customcsv_fields.html', $module, false);

		$lq = new ListQuery('ImportDBMap');
		$lq->addFilterClause(array('field' => 'module', 'value' => $import_module->getField('id')));
		if (!AppConfig::is_admin()) {
			$lq->addFilterClause(array('field' => 'assigned_user_id', 'value' => AppConfig::current_user_id()));
		}

		$mapping_params = array();
		$list = $lq->runQuery(0, null, false, 'name');
		foreach ($list->getRowIndexes() as $idx) {
			$row = $list->getRowResult($idx);
			$xtpl->assign('MAPPING', array('id' => $row->getField('id'), 'name' => $row->getField('name')));
			$xtpl->parse('main.mapping');
			$mapping_params[$row->getField('id')] = array(
				'has_header' => $row->getField('has_header'),
				'delimiter' => $row->getField('delimiter'),
			);
		}
		$json = getJSONObj();
		$xtpl->assign('mappings', $json->encode($mapping_params));

		$xtpl->parse('main');
		return $xtpl->text('main');
	}

	public function processFormFields($module) {
		importSessionSet('__ImportDB.customcsv',  $_REQUEST);
	}

	protected function prepareModule($module, $csv_file_handle) {
		$saved_mapping = array_get_default($this->csv_data, 'csv_mapping', array());
		fseek($csv_file_handle, 0, SEEK_SET);

		$data = $_SESSION['__ImportDB']['customcsv'];
		$modules_list = array(); //parent::getModulesList();

		$mod_strings = return_module_language('', $module);
		$app_strings = return_application_language('');

		$bean_name = AppConfig::module_primary_bean($module);
		$model_def = new ModelDef($bean_name);

		$this->addCustomFields($module, $modules_list);
		$this->addAllFields($module, $modules_list);

		$options = array('' => sprintf('-- %s --', $this->mod_strings['LBL_DO_NOT_MAP_FIELD']));
		foreach ($modules_list[$module]['columns'] as $column => $action) {
			if ($column != 'id' && !empty($action['action']) && $action['action'] == 'skip') continue;
			$ref_def = null;

			$field_def = $model_def->getFieldDefinition($column);
			$vname = $field_def['vname'];
			if ($field_def['type'] == 'id' && !empty($field_def['for_ref'])) {
				$ref_def = $model_def->getFieldDefinition($field_def['for_ref']);
			}
			$title = array_get_default($mod_strings, $vname, array_get_default($app_strings, $vname, $column));
			$suffix = '';
			if ($ref_def) {
				$suffix = ' [ID]';
			}
			$options[$column] = $title . $suffix;
			if ($ref_def) {
				$suffix = $ref_def['bean_name'] == 'User' ? ' [User Name]' : ' [Name]';
				$options[$column . '/name'] = $title . $suffix;
			}
		}

		$delim = ',';
		if ($data['csv_delimiter'] == 'tab') $delim = "\t";
		if ($data['csv_delimiter'] == 'semicolon') $delim = ";";

		$xtpl = new ImportDBxtpl('modules/ImportDB/tpl/prepare_customcsv.html', $module, false);

		if (!empty($data['csv_has_header'])) {
			$xtpl->parse('main.header_row');

			$header = fgetcsv($csv_file_handle, null, $delim);
			$row1 = fgetcsv($csv_file_handle, null, $delim);
			$row2 = fgetcsv($csv_file_handle, null, $delim);

			foreach ($header as $idx => $column) {
				$row_data = array(
					'DB_FIELD' => importDBSelectControl(
						null,
						'column_map[' . $idx . ']',
						'column_map-' . $idx,
						$options,
						array_get_default($saved_mapping, $idx, $column)),
					'ROW1' => $column,
					'ROW2' => $row1[$idx],
					'ROW3' => empty($row2[$idx]) ? '&nbsp' : $row2[$idx],
				);
				$xtpl->assign('ROW', $row_data);
				$xtpl->parse('main.map_row');
			}
		} else {
			$xtpl->parse('main.row3');

			$row1 = fgetcsv($csv_file_handle, null, $delim);
			$row2 = fgetcsv($csv_file_handle, null, $delim);
			$row3 = fgetcsv($csv_file_handle, null, $delim);

			foreach ($row1 as $idx => $value) {
				$row_data = array(
					'DB_FIELD' => importDBSelectControl(
						null,
						'column_map[' . $idx . ']',
						'column_map-' . $idx,
						$options,
						array_get_default($saved_mapping, $idx, null)
					),
					'ROW1' => $value,
					'ROW2' => empty($row2[$idx]) ? '&nbsp' : $row2[$idx],
					'ROW3' => empty($row3[$idx]) ? '&nbsp' : $row3[$idx],
				);
				$xtpl->assign('ROW', $row_data);
				$xtpl->parse('main.map_row');
			}
		}

		$required_fields = $model_def->getRequiredFields();
		$fields_to_skip = array(
			'modified_user_id', 'modified_user', 'assigned_user', 'portal_active', 'email_opt_out', 'invalid_email',
			'recurrence_index', 'email_attachment', 'id_c', 'date_entered', 'date_modified', 'id',
		);
		if (!empty($required_fields)) {
			$titles = array();

			foreach ($required_fields as $field) {
				if (in_array($field, $fields_to_skip)) continue;

				$field_def = $model_def->getFieldDefinition($field);
				if ($field_def['type'] == 'ref') continue;
				if ($field_def['type'] == 'base_currency') continue;
				if (! empty($field_def['auto_increment'])) continue;
				if (isset($field_def['default']) && $field_def['default'] !== '') {
					continue;
				}
				$vname = $field_def['vname'];
				$title = array_get_default($mod_strings, $vname, array_get_default($app_strings, $vname, $field));
				$titles[$field] = $title;
			}

			if (!empty($titles)) {
				foreach ($titles as $title) {
					$xtpl->assign('FIELD_TITLE', $title);
					$xtpl->parse('main.required_fields.field');
				}
				$xtpl->assign('REQUIRED_MAPPINGS', json_encode($titles));
				$xtpl->parse('main.required_fields');
			}
		}


		if (!empty($this->csv_data['csv_saved_mapping'])) {
			$xtpl->assign('CSV_MAPPING_NAME', sprintf(
				$this->mod_strings['LBL_UPDATE_MAPPING'],
				array_get_default($this->csv_data, 'csv_mapping_name')
			));
			$xtpl->parse('main.update_mapping');
		}

		$xtpl->parse('main');
		return $xtpl->text('main');
		return null;
	}

	protected function prepareProductCategories($csv_file_handle, $header, $header_idx) {
		return $this->prepareModule(str_replace('prepare', '', __FUNCTION__), $csv_file_handle);
	}

	protected function prepareProductCatalog($csv_file_handle, $header, $header_idx) {
		return $this->prepareModule(str_replace('prepare', '', __FUNCTION__), $csv_file_handle);
	}

	protected function prepareUsers($csv_file_handle, $header, $header_idx) {
		return $this->prepareModule(str_replace('prepare', '', __FUNCTION__), $csv_file_handle);
	}

	protected function prepareAccounts($csv_file_handle, $header, $header_idx) {
		return $this->prepareModule(str_replace('prepare', '', __FUNCTION__), $csv_file_handle);
	}

	protected function prepareOpportunities($csv_file_handle, $header, $header_idx) {
		return $this->prepareModule(str_replace('prepare', '', __FUNCTION__), $csv_file_handle);
	}

	protected function prepareContacts($csv_file_handle, $header, $header_idx) {
		return $this->prepareModule(str_replace('prepare', '', __FUNCTION__), $csv_file_handle);
	}

	protected function prepareLeads($csv_file_handle, $header, $header_idx) {
		return $this->prepareModule(str_replace('prepare', '', __FUNCTION__), $csv_file_handle);
	}

	protected function prepareCases($csv_file_handle, $header, $header_idx) {
		return $this->prepareModule(str_replace('prepare', '', __FUNCTION__), $csv_file_handle);
	}

	protected function prepareCalls($csv_file_handle, $header, $header_idx) {
		return $this->prepareModule(str_replace('prepare', '', __FUNCTION__), $csv_file_handle);
	}

	protected function prepareMeetings($csv_file_handle, $header, $header_idx) {
		return $this->prepareModule(str_replace('prepare', '', __FUNCTION__), $csv_file_handle);
	}

	protected function prepareTasks($csv_file_handle, $header, $header_idx) {
		return $this->prepareModule(str_replace('prepare', '', __FUNCTION__), $csv_file_handle);
	}

	protected function prepareNotes($csv_file_handle, $header, $header_idx) {
		return $this->prepareModule(str_replace('prepare', '', __FUNCTION__), $csv_file_handle);
	}
	
	protected function prepareProspects($csv_file_handle, $header, $header_idx) {
		return $this->prepareModule(str_replace('prepare', '', __FUNCTION__), $csv_file_handle);
	}

	protected function processModule($module) {
		if (!empty($_REQUEST['save_map'])) {
			$module_row = $this->getImportDBModule($module);
			$action = $_REQUEST['save_map'];
			$update = null;
			
			if ($action == 'create' && !empty($_REQUEST['save_map_as'])) {
				$update = array(
					'assigned_user_id' => AppConfig::current_user_id(),
					'name' => $_REQUEST['save_map_as'],
					'module' => $module_row->getField('id'),
					'mapping' => serialize($_REQUEST['column_map']),
					'has_header' => (int)($_SESSION['__ImportDB']['customcsv']['csv_has_header']),
					'delimiter' => $_SESSION['__ImportDB']['customcsv']['csv_delimiter'],
				);
				$upd = RowUpdate::blank_for_model('ImportDBMap');
			} elseif ($action == 'update') {
				$map = ListQuery::quick_fetch('ImportDBMap', $_SESSION['__ImportDB']['customcsv']['csv_saved_mapping']);
				if ($map) {
					$upd = RowUpdate::for_result($map);
					$update = array(
						'mapping' => serialize($_REQUEST['column_map']),
						'has_header' => (int)($_SESSION['__ImportDB']['customcsv']['csv_has_header']),
						'delimiter' => $_SESSION['__ImportDB']['customcsv']['csv_delimiter'],
					);
				}
			}

			if ($update) {
				$upd->set($update);
				$upd->save();
				importSessionSet('__ImportDB.customcsv.csv_saved_mapping', $upd->getPrimaryKeyValue());
			}

		} else {
			importSessionSet('__ImportDB.customcsv.csv_mapping', $_REQUEST['column_map']);
		}
	}

	protected function processProductCategories() {
		$this->processModule(str_replace('process', '', __FUNCTION__));
	}

	protected function processProductCatalog() {
		$this->processModule(str_replace('process', '', __FUNCTION__));
	}

	protected function processUsers() {
		$this->processModule(str_replace('process', '', __FUNCTION__));
	}

	protected function processAccounts() {
		$this->processModule(str_replace('process', '', __FUNCTION__));
	}

	protected function processOpportunities() {
		$this->processModule(str_replace('process', '', __FUNCTION__));
	}

	protected function processContacts() {
		$this->processModule(str_replace('process', '', __FUNCTION__));
	}

	protected function processLeads() {
		$this->processModule(str_replace('process', '', __FUNCTION__));
	}

	protected function processCases() {
		$this->processModule(str_replace('process', '', __FUNCTION__));
	}

	protected function processCalls() {
		$this->processModule(str_replace('process', '', __FUNCTION__));
	}

	protected function processMeetings() {
		$this->processModule(str_replace('process', '', __FUNCTION__));
	}

	protected function processTasks() {
		$this->processModule(str_replace('process', '', __FUNCTION__));
	}

	protected function processNotes() {
		$this->processModule(str_replace('process', '', __FUNCTION__));
	}
	
	protected function processProspects() {
		$this->processModule(str_replace('process', '', __FUNCTION__));
	}

    private function addCustomFields($module, &$modules_list) {
        $bean_name = AppConfig::module_primary_bean($module);
        $model_def = new ModelDef($bean_name);
        $custom_fields = $model_def->getCustomFields();

        for ($i = 0; $i < sizeof($custom_fields); $i++) {
            if ($custom_fields[$i] !== 'id_c')
                $modules_list[$module]['columns'][$custom_fields[$i]] = array();
        }
    }
	
    private function addAllFields($module, &$modules_list) {
        $bean_name = AppConfig::module_primary_bean($module);
        $model_def = new ModelDef($bean_name);
		$fields = $model_def->getFieldDefinitions();
		foreach ($fields as $f => $def) {
			$col = array();
			$add = true;
			if ($def['type'] == 'base_currency') $add = false;
			if ($def['type'] == 'ref') $add = false;
			if ($def['source']['type'] != 'db') $add = false;
			if ($f != 'id' && !array_get_default($def, 'editable', true)) $add = false;
			if ($f == 'id') {
				$col['action'] = 'skip';
			}
			if ($def['type'] == 'id' && ($for_ref = array_get_default($def, 'for_ref'))) {
				$ref_def = $fields[$for_ref];
				if (isset($ref_def['bean_name'])) {
					if($ref_def['bean_name'] == 'Currency') {
						$importable = 'LBL_CURRENCY';
						$col['action'] = 'copy';
					} else {
						$importable = AppConfig::setting("model.detail.{$ref_def['bean_name']}.importable");
						if ($importable) {
							$col['action'] = 'get_from_imported';
							$col['params'] = array(
								'id_field' => $f,
								'module' => AppConfig::module_for_model($ref_def['bean_name']),
							);
						}
					}
				} else if (isset($ref_def['dynamic_module'])) {
					$importable = true;
					$col['action'] = 'get_from_imported';
					$col['params'] = array(
						'id_field' => $f,
						'dynamic_module' => $ref_def['dynamic_module'],
					);
				} else
					$importable = false;
				if (!$importable)
					$add = false;
			}
			$method_name = $this->moduleName2MethodName($module, 'specialField');
			if (method_exists($this, $method_name)) {
				$add |= call_user_func_array(array($this, $method_name), array($f, &$col));
			}
			if ($add)
	            $modules_list[$module]['columns'][$f] = $col;
		}
    }
	
	public function getTotalRowsCount($csv_file_handle) {
		$count = parent::getTotalRowsCount($csv_file_handle);
		if (!$this->csv_data['csv_has_header'])
			$count++;
		return $count;
	}

	private function specialFieldProductCatalog($f, &$col)
	{
	}
	
	protected function filterExpectedColumns(&$cols)
	{
		$cols = array();
	}
}
