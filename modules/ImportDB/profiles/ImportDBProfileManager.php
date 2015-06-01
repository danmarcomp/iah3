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
require_once('modules/ImportDB/ImportDBProfile.php');
require_once('modules/ImportDB/ImportDBModule.php');
require_once('modules/ImportDB/ImportDBHistory.php');

class ImportDBProfileManager {
	/**
	 * Available profiles list
	 *
	 * @var array
	 */
	private static $profiles = array();
	/**
	 * List of modules that we need to emulate logic hooks for,
	 *
	 * @var array
	 */
	private static $logic_hook_modules = array(
		'Users', 'Accounts', 'Opportunities', 'Contacts', 'Leads', 'Cases', 'Calls', 'Meetings', 'Tasks', 'Notes',
	);

	private static $initialized = false;

	/**
	 * Inits import profiles
	 *
	 * @static
	 * @return void
	 */
	public static function init() {
		if (self::$initialized)
			return;
		$cwd = dirname(realpath(__FILE__));
		require_once($cwd . '/ImportDBImportProfile.php');
		require_once($cwd . '/ImportDBSugarGenericProfile.php');
		require_once($cwd . '/ImportDBSugar60Profile.php');
		foreach (glob($cwd . '/*.php') as $filename) {
			require_once($filename);

			$parts = explode(DIRECTORY_SEPARATOR, $filename);
			$file = $parts[count($parts) - 1];
			$file = substr($file, 0, strrpos($file, '.'));
            $file = str_replace('profiles/', '', $file);

			if (preg_match('#^ImportDB.+\d+Profile$#', $file)) {
				self::register(new $file());
			}
		}
		self::$initialized = true;
	}

	/**
	 * Register new import profile
	 *
	 * @static
	 * @param ImportDBImportProfile $instance
	 * @return void
	 */
	public static function register(ImportDBImportProfile $instance) {
		self::$profiles[$instance->getName()] = $instance;
	}

	/**
	 * Returns registered profiles array where key is profile class name and value is
	 * profile title
	 *
	 * @static
	 * @param string $module return profiles that support the module, when
	 * @return array
	 */
	public static function getProfilesList($module = null) {
		$out = array();
		foreach (self::$profiles as $name => &$instance) {
			/** @var $instance ImportDBImportProfile */
			if ($instance->getAvailableModules($module)) {
				$out[$name] = $instance->getTitle();
			}
		}
		return $out;
	}

	/**
	 * Returns import profile insance by class name
	 *
	 * @static
	 * @throws OutOfBoundsException
	 * @param  $className
	 * @return ImportDBImportProfile
	 */
	public static function &getProfile($className) {
		if (!empty(self::$profiles[$className])) return self::$profiles[$className];
		throw new OutOfBoundsException(sprintf(
			translate('MSG_PROFILE_NOT_FOUND', 'ImportDB'), $className));
	}

	/**
	 * Adds ImportDB logic hooks for required modules
	 *
	 * @static
	 * @param string $module
	 * @param array $hook_array
	 * @return void
	 */
	public static function installLogicHook($module, array &$hook_array) {
		$modules = array_flip(self::$logic_hook_modules);
		if (isset($modules[$module])) {
			$hook_array['after_delete'][] = array(
				1,
				$module . ' ImportDB after delete',
				'modules/ImportDB/profiles/ImportDBProfileManager.php',
				'ImportDBProfileManager',
				'dispatchLogicHook',
			);
			$hook_array['after_restore'][] = array(
				1,
				$module . ' ImportDB after restore',
				'modules/ImportDB/profiles/ImportDBProfileManager.php',
				'ImportDBProfileManager',
				'dispatchLogicHook',
			);

			// we need to add hooks only once, so unset module to skip addition next time if any
			unset(self::$logic_hook_modules[$modules[$module]]);
		}
	}

	/**
	 * Dispatch Logic hooks
	 *
	 * @param SugarBean $bean
	 * @param string $event
	 * @param array $arguments
	 * @return void
	 */
	public function dispatchLogicHook(SugarBean &$bean, $event, array $arguments) {
		self::init();

		$bean_class = get_class($bean);

		foreach (self::$profiles as $name => &$profile) {
			/** @var $profile ImportDBImportProfile */
			$modules = $profile->getAvailableModules(null, null);
			foreach ($modules as $module => $data) {
				if ($bean_class == AppConfig::module_primary_bean($module)) {
					$import_module = $profile->getImportDBModule($module);

					$lq = new ListQuery('ImportDBHistory', null, array('filter_deleted' => false));
					$lq->addFilterClauses(
						array(
							array('field' => 'generated_id', 'value' => $arguments['id']),
							array('field' => 'module_id', 'value' => $import_module->getField('id')),
						)
					);
					$row = $lq->runQuerySingle();

					if ($row->getResultCount()) {
						$upd = new RowUpdate($row);
						$upd->markDeleted($event == 'after_delete' ? true : false);
					}
				}
			}
		}
	}
}
