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
require_once('modules/ImportDB/ImportDBxtpl.php');

require_once('modules/ImportDB/profiles/ImportDBProfileManager.php');
require_once('modules/ImportDB/functions.php');

require_once('include/ProgressIndicator.php');
require_once('include/JSON.php');

checkAdmin();

$module = array_get_default($_REQUEST, 'module', 'index');
$step = array_get_default($_REQUEST, 'step', 'index');

$strings = return_module_language('', 'ImportDB');

$bean_name = AppConfig::module_primary_bean($module);
$detail = AppConfig::setting("model.detail.$bean_name");
$module_title = array_get_default($detail, 'importable', false);
if (!$module_title) {
	$step = 'error';
	$module_title = $strings['ERR_MODULE_NOT_IMPORTABLE'];
} else {
	$steps = array(
		'index' => 'LBL_MODULE_ACT_HOME',
		'import' => 'LBL_MODULE_ACT_IMPORT',
		'prepare' => 'LBL_MODULE_ACT_PREPARE',
		'execute' => 'LBL_MODULE_ACT_EXECUTE',
	);
	$module_title = translate($module_title) . ': ';
	$module_title .= translate('LBL_IMPORT_DB_TITLE', 'Administration');
	if (!empty($steps[$step])) $module_title .= ' / ' . $strings[$steps[$step]];
}

ImportDBProfileManager::init();

switch ($step) {
	case 'index':
		if (empty($_REQUEST['op'])) {
			$xtpl = new ImportDBxtpl('modules/ImportDB/tpl/index.html', $module);
			$xtpl->assign('PROFILE_SELECT', importDBSelectControl(
				$strings['LBL_IMPORT_DATA_FROM'],
				'profile',
				'profile-select',
				array_reverse(ImportDBProfileManager::getProfilesList($module), true),
				null,
				true
			));
		} else {
			redirect(
				array(
					'module' => $module,
					'action' => 'ImportDB',
					'step' => 'import',
					'profile' => getImportDBProfile($module)->getName(),
				)
			);
		}
		break;

	case 'import':
		$profile = getImportDBProfile($module);
		$data_manager = new ImportDBDataManager($profile);

		if (empty($_REQUEST['op'])) {
			$xtpl = new ImportDBxtpl('modules/ImportDB/tpl/import.html', $module);
			$ent_list = array();
			foreach ($profile->getAvailableModules() as $name => $title) {
				$importdb_module = $profile->getImportDBModule($name, false);

				$imported = (empty($importdb_module) || !$data_manager->fetchList($name, true)) ? false : true;
				$xtpl->assign('MOD', array('name' => $name, 'title' => $imported ? ('<strong>' . $title . '</strong>') : $title));
				$xtpl->assign('PROFILE_FIELDS', $profile->uploadFormFields($module));
				$xtpl->parse('main.ordered_module');
			}
		} else {
			if (!empty($_FILES['data_file']['size']) && is_file($_FILES['data_file']['tmp_name'])) {
				$upload_fname = $_FILES['data_file']['name'];
				if (preg_match('/\.csv(\.txt)?$/i', $upload_fname)) {
					if (!move_uploaded_file($_FILES['data_file']['tmp_name'], IMPORTDB_DATA_FILE)) {
						redirect(
							array(
								'module' => $module,
								'action' => 'ImportDB',
								'step' => 'import',
								'profile' => $profile->getName(),
								'msg_type' => 'error',
								'msg_text' => $strings['MSG_UNABLE_TO_MOVE_FILE'],
							)
						);
					}

					$profile->processFormFields($module);
					redirect(
						array(
							'module' => $module,
							'action' => 'ImportDB',
							'step' => 'prepare',
							'profile' => $profile->getName(),
						)
					);
				} else {
					redirect(
						array(
							'module' => $module,
							'action' => 'ImportDB',
							'step' => 'import',
							'profile' => $profile->getName(),
							'msg_type' => 'error',
							'msg_text' => $strings['MSG_FILE_MUST_BE_CSV'],
						)
					);
				}
			}
			redirect(
				array(
					'module' => $module,
					'action' => 'ImportDB',
					'step' => 'import',
					'profile' => $profile->getName(),
					'msg_type' => 'error',
					'msg_text' => $strings['MSG_INVALID_FILE'],
				)
			);
		}
		break;

	case 'prepare':
		$profile = getImportDBProfile($module);
		$mod_title = getImportDBModuleTitle($profile, $module);
		checkImportDBFile($profile, $module);

		if (empty($_REQUEST['op'])) {
			normalizeImportCSV();

			$csv_handle = fopen(IMPORTDB_DATA_FILE, 'r');
			try {
				$prepare = $profile->prepareImport($module, $csv_handle);
			} catch (BadMethodCallException $e) {
				// there is no need for preparations
				fclose($csv_handle);
				redirect(
					array(
						'module' => $module,
						'action' => 'ImportDB',
						'step' => 'pre_execute',
						'profile' => $profile->getName(),
					)
				);
			} catch (Exception $e) {
				fclose($csv_handle);
				redirect(
					array(
						'module' => $module,
						'action' => 'ImportDB',
						'step' => 'import',
						'profile' => $profile->getName(),
						'msg_type' => 'error',
						'msg_text' => $e->getMessage(),
					)
				);
			}
			fclose($csv_handle);

			if (!empty($prepare)) {
				$str = array(
					'MSG_PREPARE_TO_IMPORT' => sprintf($strings['MSG_PREPARE_TO_IMPORT'], $mod_title, $profile->getTitle()),
				);

				$xtpl = new ImportDBxtpl('modules/ImportDB/tpl/prepare.html', $module, true, $str);

				$xtpl->assign('PREPARE', $prepare);
			} else {
				// there is no need for preparations
				redirect(
					array(
						'module' => $module,
						'action' => 'ImportDB',
						'step' => 'pre_execute',
						'profile' => $profile->getName(),
					)
				);
			}
		} else {
			try {
				$profile->processPrepareImport($module);
			} catch (BadMethodCallException $e) {
				// there is no need for preparations (strange...)
			} catch (Exception $e) {
				redirect(
					array(
						'module' => $module,
						'action' => 'ImportDB',
						'step' => 'import',
						'profile' => $profile->getName(),
						'msg_type' => 'error',
						'msg_text' => $e->getMessage(),
					)
				);
			}

			redirect(
				array(
					'module' => $module,
					'action' => 'ImportDB',
					'step' => 'pre_execute',
					'profile' => $profile->getName(),
				)
			);
		}
		break;

	case 'pre_execute':
		$profile = getImportDBProfile($module);
		$mod_title = getImportDBModuleTitle($profile, $module);
		checkImportDBFile($profile, $module);
		if (empty($_REQUEST['op'])) {
			try {
				$content = $profile->preExecute($module);
			} catch (BadMethodCallException $e) {
				redirect(
					array(
						'module' => $module,
						'action' => 'ImportDB',
						'step' => 'execute',
						'profile' => $profile->getName(),
					)
				);
			} catch (Exception $e) {
				redirect(
					array(
						'module' => $module,
						'action' => 'ImportDB',
						'step' => 'import',
						'profile' => $profile->getName(),
						'msg_type' => 'error',
						'msg_text' => $e->getMessage(),
					)
				);
			}
			if (!empty($content)) {
				$str = array(
					'MSG_PREPARE_TO_IMPORT' => sprintf($strings['MSG_PREPARE_TO_IMPORT'], $mod_title, $profile->getTitle()),
				);

				$xtpl = new ImportDBxtpl('modules/ImportDB/tpl/pre_execute.html', $module, true, $str);

				$xtpl->assign('PREPARE', $content);
			} else {
				// there is no need for preparations
				redirect(
					array(
						'module' => $module,
						'action' => 'ImportDB',
						'step' => 'execute',
						'profile' => $profile->getName(),
					)
				);
			}
		} else {
			try {
				$profile->processPreExecute($module);
			} catch (BadMethodCallException $e) {
			} catch (Exception $e) {
				redirect(
					array(
						'module' => $module,
						'action' => 'ImportDB',
						'step' => 'import',
						'profile' => $profile->getName(),
						'msg_type' => 'error',
						'msg_text' => $e->getMessage(),
					)
				);
			}

			redirect(
				array(
					'module' => $module,
					'action' => 'ImportDB',
					'step' => 'execute',
					'profile' => $profile->getName(),
				)
			);
		}

		break;
	case 'execute':
		$profile = getImportDBProfile($module);
		$mod_title = getImportDBModuleTitle($profile, $module);
		checkImportDBFile($profile, $module);

		$csv_handle = fopen(IMPORTDB_DATA_FILE, 'r');
		$total_rows = $profile->getTotalRowsCount($csv_handle);
		$total_steps = ceil($total_rows / IMPORTDB_CHUNK_SIZE);
		fclose($csv_handle);

		$str = array(
			'MSG_EXECUTE_PREDICTION' => sprintf($strings['MSG_EXECUTE_PREDICTION'], $total_rows, $total_steps),
		);

		$xtpl = new ImportDBxtpl('modules/ImportDB/tpl/execute.html', $module, true, $str);
		$allModules = $profile->getAvailableModules();
		$allModulesNames = array_keys($allModules);
		$xtpl->assign('LBL_CONTINUE_SAME', sprintf($strings['LBL_CONTINUE_SAME'], $allModules[$module]));
		$xtpl->assign('PROFILE_NAME', $profile->getName());


		if ($idx < count($allModulesNames) - 1) {
			$idx = array_search($module, $allModulesNames);
			$xtpl->assign('LBL_CONTINUE_NEXT', sprintf($strings['LBL_CONTINUE_NEXT'], $allModules[$allModulesNames[$idx+1]]));
			$xtpl->assign('NEXT_MODULE', $allModulesNames[$idx+1]);
			$xtpl->parse('main.next_module');
		}

		$xtpl->assign('IMPORT_STEPS', $total_steps);
		break;

	case 'asyncImport':
		set_time_limit(3600);
		// flush after each output so the user can see the progress in real-time
		ob_implicit_flush();

		$profile = getImportDBProfile($module);
		$mod_title = getImportDBModuleTitle($profile, $module);
		checkImportDBFile($profile, $module);

		$iter = array_get_default($_REQUEST, 'iter');
		if (is_null($iter)) {
			$limit = PHP_INT_MAX;
			$offset = 0;
		} else {
			$limit = IMPORTDB_CHUNK_SIZE;
			$offset = intval($iter) * $limit;
		}

		if ($offset == 0) {
			importSessionSet('__ImportDB.imported_total',  0);
		}

		$csv_handle = fopen(IMPORTDB_DATA_FILE, 'r');
		try {
			importSessionClose();
			$count = $profile->executeImport($module, $csv_handle, IMPORTDB_PROGRESS_FILE, $offset, $limit);
			importSessionResume();

			importSessionSet('__ImportDB.imported_total', $_SESSION['__ImportDB']['imported_total'] + $count);
			if ($_SESSION['__ImportDB']['imported_total'] == 1) {
				$msg = sprintf($strings['MSG_ITEM_IMPORTED_SINGLE'], $profile->getAvailableModules($module));
			} else {
				$msg = sprintf($strings['MSG_ITEM_IMPORTED_PLURAL'], $_SESSION['__ImportDB']['imported_total'],
					$profile->getAvailableModules($module));
			}

			importSessionSet('__ImportDB.msg_text', $msg);
			$error = false;
		} catch (Exception $e) {
			importSessionResume();
			unlink(IMPORTDB_DATA_FILE);
			importSessionSet('__ImportDB.msg_text', $e->getMessage());
			importSessionSet('__ImportDB.msg_type', 'error');
			$error = true;
		}

		$json = new JSON(JSON_LOOSE_TYPE);
		die($json->encode(array('error' => $error)));
		break;

	case 'asyncProgress':
		if (!isset($_REQUEST['result_message'])) {
			$progressIndicator = new ProgressIndicator(IMPORTDB_PROGRESS_FILE);

			$data = $progressIndicator->requestHistory();
			$json = new JSON(JSON_LOOSE_TYPE);
			$out = $json->encode($data);
		} else {
			$xtpl = new ImportDBxtpl('modules/ImportDB/tpl/index.html', $module);
			$out = $xtpl->text('main.message');

			importSessionSet('__ImportDB.imported_total', 0);
			unlink(IMPORTDB_DATA_FILE);
		}
		die($out);
		break;

	case 'error':
	default:
		print $strings['ERR_NOT_BE_HERE'];
}

if (!empty($xtpl)) {
	echo get_module_title($module, $module_title, true);

	$xtpl->parse('main');
	$xtpl->out('main');
}
