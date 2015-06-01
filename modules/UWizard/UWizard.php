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

require_once 'modules/UWizard/IAHManifest.php';
require_once 'include/utils/zip_utils.php';
require_once 'include/database/ListQuery.php';
require_once 'include/database/RowUpdate.php';
require_once 'include/Sugar_Smarty.php';

class UWizardError extends IAHError {}

class UWizard
{
	const THIS_VERSION = 1;
	const NEWER_VERSION = 2;

	protected $manifest;
	protected $zipfile;
	protected $action;
	protected $confirm;
	
	protected static $packageTypes = array(
		'pers_pack', 'patch', 'module', 'holidays', 'langpack', 'theme',
	);
	
	public static $colors = array(
		'info' => '#0c0',
		'success' => '#0c0',
		'warning' => '#009',
		'error' => '#c00',
	);
	
	public static $leds = array(
		'success' => 'icon-ledgreen',
		'warning' => 'icon-ledyellow',
		'error' => 'icon-ledred',
	);
	

	public static function create($zipfile, $action, $confirm=null)
	{
		$manifest = self::readManifest($zipfile);
		if ($manifest->getType() == 'pers_pack') {
			require_once 'modules/UWizard/UWizardPP.php';
			return new UWizardPP($zipfile, $action);
		}
		return new UWizard($zipfile, $action, $confirm);
	}

	protected function __construct($zipfile, $action, $confirm=null)
	{
		$this->zipfile = $zipfile;
		$this->manifest = self::readManifest($zipfile);
		$this->action = $action;
		if(isset($confirm)) {
			if(! is_array($confirm))
				$confirm = array_filter(explode(',', $confirm));
			$this->confirm = $confirm;
		} else
			$this->confirm = array();
	}

	public function getManifest()
	{
		return $this->manifest;
	}

	public static function getUpgradeStatus() {
		return AppConfig::setting('site.upgrade_in_progress');
	}
	
	public static function saveUpgradeStatus($status) {
		AppConfig::set_local('site.upgrade_in_progress', $status);
		AppConfig::save_local();
	}

	public static function upload($inputName, $allowedType = null)
	{
		if (!isset($_FILES[$inputName])) return 'UPLOAD_ERR_NO_FILE';
		$error = array_get_default($_FILES[$inputName], 'error');
		switch ($error) {
			case UPLOAD_ERR_OK: break;
			case UPLOAD_ERR_INI_SIZE: return 'UPLOAD_ERR_INI_SIZE';
			case UPLOAD_ERR_FORM_SIZE: return 'UPLOAD_ERR_FORM_SIZE';
			case UPLOAD_ERR_PARTIAL: return 'UPLOAD_ERR_PARTIAL';
			case UPLOAD_ERR_NO_FILE: return 'UPLOAD_ERR_NO_FILE';
			case UPLOAD_ERR_NO_TMP_DIR: return 'UPLOAD_ERR_NO_TMP_DIR';
			case UPLOAD_ERR_CANT_WRITE: return 'UPLOAD_ERR_CANT_WRITE';
			case UPLOAD_ERR_EXTENSION: return 'UPLOAD_ERR_EXTENSION';
			default: return 'UPLOAD_ERR_NO_FILE';
		}
		$fname = basename(clean_path($_FILES[$inputName]['name']));
		if (substr(strtolower($fname), -4) != '.zip')
			return 'ERR_NOT_ZIP';
		$manifest = self::readManifest($_FILES[$inputName]['tmp_name']);
		$error = $manifest->validate();
		if ($error)
			return $error;

		$type = $manifest->getType();
		
		if ($allowedType && $type != $allowedType) {
			return 'ERR_INVALID_PACKAGE_TYPE';
		}

		$path = self::getCacheDir('upgrades') . "$type/";
		if (file_exists($path . $fname)) {
			return 'ERR_DUPLICATE_UPLOAD';
		}
		mkdir_recursive($path);
		if (!move_uploaded_file($_FILES[$inputName]['tmp_name'], $path . $fname)) {
			return 'ERR_CANT_MOVE';
		}

		return '';
	}

	public static function deletePackage($filename)
	{
		if (self::isValidPackageArchive($filename))
			@unlink($filename);
	}

	public static function listUploadedPackages($type_filter = null)
	{
		$packages = array();
		$types = IAHManifest::allowedTypes();
		if ($type_filter) {
			if (in_array($type_filter, $types))
				$types = array($type_filter);
			else
				$types = array();
		}
		foreach ($types as $type) {
			$path = self::getCacheDir('upgrades') . "$type/*.{z,Z}{i,I}{p,P}";
			$files = glob($path, GLOB_NOSORT | GLOB_BRACE);
			foreach ($files as $file) {
				$manifest = self::readManifest($file);
				$package = array(
					'name' => $manifest->getName(),
					'id' => $manifest->getId(),
					'type' => $manifest->getType(),
					'date' => $manifest->getDate(),
					'description' => $manifest->getDescription(),
					'author' => $manifest->getAuthor(),
					'version' => $manifest->getVersion(),
					'status' => $manifest->validate(),
					'source' => $file,
					'installable' => true,
					'conditions' => array(),
				);
				if (empty($package['status'])) {
					$version_check = self::isInstallable($package['type'], $package['id'], $package['version']);
					if ($version_check & self::THIS_VERSION) {
						continue;
					}
					if ($version_check & self::NEWER_VERSION) {
						$package['status'] = 'ERR_OUTDATED';
					}
					if ($package['type'] == 'pers_pack') {
						$subPackages = self::persPackPackages($manifest, $package['source']);
						foreach ($subPackages as $sp) {
							$version_check = self::isInstallable($sp->getType(), $sp->getId(), $sp->getVersion());
							if ($version_check) {
								$package['status'] = 'ERR_OUTDATED_PPACK';
								break;
							}
						}
					}
				}
				$conditions = $manifest->path('conditions',array());
				$test_conditions = array(
					'modules' => 'LBL_INSTALL_MODULES',
					'dropdowns' => 'LBL_INSTALL_DROPDOWNS',
					'layout' => 'LBL_INSTALL_LAYOUT',
				);
				foreach ($test_conditions as $c => $lbl) {
					if (!empty($conditions[$c]))
						$package['conditions'][$c] = translate($lbl, 'UWizard');
				}
				$custom = $manifest->path('custom');
				if (!empty($custom))
					$package['conditions']['custom'] = translate('LBL_INSTALL_CUSTOM', 'UWizard');
				if ($package['type'] == 'pers_pack') {
					foreach ($subPackages as $sp) {
						$package['conditions']['package_' . $sp->getType() . '_' . $sp->getId() . '_' . $sp->getVersion()] = $sp->getName();
					}
				}
				if (empty($package['status'])) {
					$package['status'] = 'STATUS_READY';
					$package['icon'] = '<div class="input-icon ' . self::$leds['success'] . '"></div>';
				} else {
					$package['icon'] = '<div class="input-icon ' . self::$leds['error'] . '"></div>';
					$package['class'] = 'error';
					$package['installable'] = false;
				}
				$packages[] = $package;
			}
		}
		return $packages;
	}

	public static function listInstalledPackages($type_filter = null)
	{
		$no_db = defined('IAH_IN_INSTALLER');
		$idx = array();
		if (!$no_db) {
			$lq = new ListQuery('UpgradeHistory');
			$lq->setOrderBy('date_entered DESC');
			$result = $lq->runQuery();
			$packages = $result->getRows();
		} else {
			$packages = array_get_default($_SESSION, 'UWizard_UpgradeHistory', array());
		}
		foreach ($packages as  &$package) {
			try {
				if ($package['from_ppack']) continue;
				$manifest = self::readManifest($package['filename']);
				if ($type_filter && $type_filter != $manifest->getType()) continue;
				$package['icon'] = '<div class="input-icon icon-ledgrey"></div>';
				if ($manifest->getType() == 'patch') continue;
				$is_uninstallable = $manifest->path('is_uninstallable');
				if ($manifest->getType() == 'pers_pack') {
					$is_uninstallable = true;
					$subPackages = self::persPackPackages($manifest, $package['filename']);
					foreach ($subPackages as $sp) {
						if (!$no_db) {
							$lq = new ListQuery('UpgradeHistory');
							$lq->addSimpleFilter('id_name', $sp->getId());
							$lq->addSimpleFilter('type', $sp->getType());
							$result = $lq->runQuery();
							$rows = $result->getRows();
						} else {
							$all = array_get_default($_SESSION, 'UWizard_UpgradeHistory', array());
							$rows = array();
							foreach ($all as $p) {
								if (array_get_default($p, 'id_name') == $sp->getId() && array_get_default($p, 'type') == $sp->getType()) {
									$rows[] = $row;
								}
							}
						}
						foreach ($rows as $row) {
							if (version_compare($row['version'], $sp->getVersion()) > 0) {
								$is_uninstallable = false;
								break;
							}
						}
					}
				}
				if (!isset($idx[$manifest->getType()][$manifest->getId()]) && $is_uninstallable) {
					$package['uninstallable'] = true;
					$idx[$manifest->getType()][$manifest->getId()] = true;
				}
			} catch (Exception $e) {
			}
		}
		return $packages;
	}

	protected static function persPackPackages($manifest, $filename)
	{
		$ret = array();
		$files = $manifest->path('included_packages', array());
		if (!empty($files)) {
			$destDir = self::getUnzipDir($manifest);
			if (!@mkdir_recursive($destDir))
				throw new UWizardError(array('ERR_CANT_CREATE_UNZUP_DIR', 'UWizard'));
			foreach (glob($destDir . '*', GLOB_NOSORT) as $file) {
				if (is_dir($file))
					$result = @rmdir_recursive($file);
				else
					$result = @unlink($file);
				if (!$result)
					throw new UWizardError(array('ERR_CANT_CLEAN_UNZUP_PACKAGE', 'UWizard'));
			}
			if (!@unzip($filename, $destDir, false))
				throw new UWizardError(array('ERR_CANT_UNZUP_PACKAGE', 'UWizard'));
		}
		foreach ($files as $file) {
			try {
				$ret[] = self::readManifest($destDir . $file);
			} catch (Exception $e) {
				pr2($e->getMessage());
			}
		}
		return $ret;
	}

	public static function checkCanRemove($from, $to)
	{
		$from = clean_path($from);
		$to = clean_path($to);
		if (!file_exists($from)) {
			return '';
		}
		if (is_file($from) && is_dir($to))
			$to = $to . '/' . basename($from);
		if (is_file($from) && is_file($to)) {
			if (is_os_writable($to))
				return '';
			return 'ERR_NOT_WRITABLE';
		}
		if (!is_dir($from))
			return '';
		$files = glob($from . '/*', GLOB_NOSORT);
		$errors = array();
		if($files)
		foreach ($files as $file) {
			$result = self::checkCanRemove($file, $to . '/' . basename($file));
			if ($result)
				$errors[] = $result;
		}
		return $errors;
	}

	public static function checkCanCopy($from, $to, $parent_checked=false)
	{
		// here we check if $from can be copied to $to
		// if $from is a dir, $to must be existing writable dir,
		// or we have enough permissions to create it
		//
		// if $from is a file, decicion about $to is taken. If $to
		// ends with a slash, it is supposed to be be existing writeable dir, 
		// or we have enough permissions to create it. Otherwise, $to
		// is a file. If it exists, it must be wrateable. Otherwise, its
		// directory must be existing writeable dir, 
		// or we have enough permissions to create it.

		$from = clean_path($from);
		$to = clean_path($to);
		if (!file_exists($from))
			return array('ERR_NO_SOURCE_FILE', $from);
		$check_dir = $check_file = '';
		if (is_dir($from)) {
			$files = glob($from . '/*', GLOB_NOSORT);
			if($files)
			foreach ($files as $file) {
				$check_to = rtrim($to, '/') . '/' . basename($file);
				if (is_dir($file))
					$check_to .= '/';
				$result = self::checkCanCopy($file, $check_to, true);
				if ($result)
					return $result;
			}
			$check_dir = $to;
		} else {
			if (substr($to, -1) == '/')
				$check_file = $to . basename($from);
			else
				$check_file = $to;
		}
		if ($check_file) {
			if (is_dir($check_file))
				return array('ERR_CANT_COPY_FILE_DIR', $check_file);
			if (is_file($check_file)) {
				if (!is_os_writable($check_file))
					return array('ERR_NOT_WRITABLE', $check_file);
				return 0;
			}
			if(! $parent_checked)
				$check_dir = dirname($check_file);
		}
		$parent_writable = true;
		if ($check_dir) {
			$parts = explode('/', $check_dir);
			$path = '';
			foreach ($parts as $part) {
				$path .= $part . '/';
				if ($part === '') continue;
				$noslash = substr($path, 0, -1);
				if (!file_exists($noslash)) {
					if ($parent_writable)
						return 0;
					else
						return array('ERR_NOT_WRITABLE', $path);
				}
				if (!is_dir($noslash))
					return array('ERR_DESTINATION_IS_NOT_DIR', $path);
				$parent_writable = is_os_writable($noslash);
			}
			if (!is_os_writable($check_dir))
				return array('ERR_NOT_WRITABLE', $check_dir);
		}
		return 0;
	}
	
	public static function getUnzipDir(IAHManifest $manifest)
	{
		if (! $manifest->checkVitalInfo($err))
			throw new IAHManifestError(translate($err, 'UWizard'));

		return self::getCacheDir('upgrades/temp')
			. $manifest->getType() . '/' . $manifest->getId()
			. '/' . $manifest->getVersion() . '/';
	}

	public static function getBackupDir(IAHManifest $manifest)
	{
		if (! $manifest->checkVitalInfo($err))
			throw new IAHManifestError(translate($err, 'UWizard'));

		return self::getCacheDir('upgrades')
			. $manifest->getType() . '/' . $manifest->getId()
			. '/' . $manifest->getVersion() . '/backup/';
	}

	protected function unzipPackage()
	{
		$cleanBackup = ($this->action == 'prepare_install');
		if ($cleanBackup) {
			$destDir = self::getBackupDir($this->manifest);
			if (!@mkdir_recursive($destDir))
				throw new UWizardError(array('ERR_CANT_CREATE_BACKUP_DIR', 'UWizard'));
			$files = glob($destDir . '*', GLOB_NOSORT);
			if($files)
			foreach ($files as $file) {
				if (is_dir($file))
					$result = @rmdir_recursive($file);
				else
					$result = @unlink($file);
				if (!$result)
					throw new UWizardError(array('ERR_CANT_CLEAN_BACKUP_DIR', 'UWizard'));
			}
		}
		
		$destDir = self::getUnzipDir($this->manifest);
		if (!@mkdir_recursive($destDir))
			throw new UWizardError(array('ERR_CANT_CREATE_UNZUP_DIR', 'UWizard'));
		$files = glob($destDir . '*', GLOB_NOSORT);
		if($files)
		foreach ($files as $file) {
			if (is_dir($file))
				$result = @rmdir_recursive($file);
			else
				$result = @unlink($file);
			if (!$result)
				throw new UWizardError(array('ERR_CANT_CLEAN_UNZUP_PACKAGE', 'UWizard'));
		}
		if (!@unzip($this->zipfile, $destDir, false))
			throw new UWizardError(array('ERR_CANT_UNZUP_PACKAGE', 'UWizard'));
		return array(
			'status' => 'success',
			'message' => translate('LBL_UNZIPPED_SUCCESS', 'UWizard'),
		);
	}


	public static function isValidPackageArchive($filename)
	{
		$filename = clean_path($filename);
		if (!is_readable($filename) || is_dir($filename))
			return false;
		$pattern = '/^' . preg_quote(self::getCacheDir('upgrades'), '/') . '(';
		$pattern .= join('|', IAHManifest::allowedTypes()) . ')\\/';
		$pattern .= '[^\\/]+\.[Zz][iI][pP]$/';
		return preg_match($pattern , $filename);
	}
	
	public function getPrepareSteps($manifest, $confirm) {
		$type = $manifest->getType();
		if(! $type || ! in_array($type, self::$packageTypes))
			return false;
		$steps = array();
		$steps[] = 'unzipPackage';
		if($type == 'patch' && $this->manifest->path('precopy') && ! in_array('precopy', $confirm)) {
			$steps[] = 'precopy';
		}
		if($type == 'pers_pack' || $type == 'patch' || $type == 'module') {
			$steps[] = 'checkSystem';
		}
		$steps[] = 'checkPermissions';
		return $steps;
	}
	
	public static function prepareInstall($zipfile, $render = true, $confirm=null) {

		$conditions = explode('|', array_get_default($_POST, 'conditions', ''));
		$_SESSION['UWizard_conditions'] = array();
		foreach ($conditions as $c)
			if (!empty($c))
				$_SESSION['UWizard_conditions'][$c] = true;
		
		$result = array('status' => 'success');
		$confirm = array_get_default($_POST, 'confirm', $confirm);
		$performed = array();
	
		try {
			$wizard = UWizard::create($zipfile, 'prepare_install', $confirm);
			$check = self::isInstallable($wizard->manifest->getType(), $wizard->manifest->getId(), $wizard->manifest->getVersion());
			if ($check)
				throw new UWizardError(array('ERR_OUTDATED', 'UWizard'));

			$type = $wizard->manifest->getType();
			$steps = $wizard->getPrepareSteps($wizard->manifest, $wizard->confirm);
			if (! $steps)
				throw new UWizardError(array('ERR_PACKAGE_TYPE_NOT_SUPPORTED', 'UWizard'));
			
			foreach($steps as $step) {
				try {
					$result = $wizard->$step();
				} catch(UWizardError $e) {
					$result = array('status' => 'error', 'message' => $e->getMessage());
				}
				if(! is_array($result))
					throw new UWizardError("Error running step $step, bad return value");
				$result['step'] = $step;
				if(empty($result['label']))
					$result['label'] = translate('LBL_STEP_' . strtoupper($step), 'UWizard');
				
				if($result['status'] == 'warning') {
					if(! isset($result['retry']))
						$result['retry'] = true;
					if(in_array($step, $wizard->confirm))
						$_SESSION['UWizard_result'][$zipfile][$step]['confirm'] = 1;
					if(! empty($_SESSION['UWizard_result'][$zipfile][$step]['confirm']))
						$result['status'] = 'success';
				}
				
				$result['color'] = array_get_default(self::$colors, $result['status'], '#000');
				$result['led'] = '<div class="input-icon ' . array_get_default(self::$leds, $result['status'], 'icon-ledgrey') . '"></div>';
				
				$performed[] = $result;
				if($result['status'] != 'success')
					break;
			}			
		} catch (Exception $e) {
			throw new UWizardError($e->getMessage());
		}
		
		if($render)
			echo $wizard->renderPrepareSteps($performed);
		
		return $performed;
	}
	
	public static function beginInstall($zipfile, $render=true) {
		$wizard = UWizard::create($zipfile, 'install');
		$type = $wizard->manifest->getType();
		$status = array(
			'perform' => 'install',
			'source' => $zipfile,
			'name' => $wizard->manifest->getName(),
			'id' => $wizard->manifest->getId(),
			'version' => $wizard->manifest->getVersion(),
			'type' => $wizard->manifest->getType(),
			'user_id' => AppConfig::current_user_id(),
			'start_time' => gmdate('Y-m-d H:i:s'),
			'start_version' => AppConfig::version(),
		);
		self::saveUpgradeStatus($status);
		if($render) {
			echo translate('LBL_UPGRADE_REDIRECT', 'UWizard') . "<script>document.location.href='upgrade.php';</script>";
		}
	}
	
	public static function runCommitStep() {
		$upgrade = self::getUpgradeStatus();
		if($upgrade && ! empty($upgrade['source'])) {
			$wizard = UWizard::create($upgrade['source'], $upgrade['perform']);
			if($upgrade['perform'] == 'install')
				$commit = 'commit';
			else
				$commit = 'remove';
			if(empty($upgrade['committed'])) {
				$upgrade['begun'] = true;
				$wizard->saveUpgradeStatus($upgrade);
				$wizard->runHook($commit, 'pre');
				$result = $wizard->$commit();
				if($result['status'] == 'success') {
					$upgrade['committed'] = true;
					$wizard->saveUpgradeStatus($upgrade);
				}
				return $result;
			} else {
				$add_steps = $wizard->manifest->path("hooks.$commit");
				if(is_array($add_steps)) {
					foreach($add_steps as $step => $step_info) {
						if(empty($upgrade['commit_steps'][$step]['complete'])) {
							$result = $wizard->runHook($step);
							if(! is_array($result))
								$result = array();
							$result['step'] = $step;
							if(! isset($result['status']))
								$result['status'] = 'success';
							if($result['status'] == 'success') {
								$upgrade['commit_steps'][$step]['complete'] = true;
								$wizard->saveUpgradeStatus($upgrade);
							}
							return $result;
						}
					}
				}
				
				$result = $wizard->runHook($commit, 'post');
				if($result) {
					$result['step'] = 'post' . $commit;
					if(! isset($result['status']))
						$result['status'] = 'success';
					return $result;
				}
				
				$result = $wizard->cleanup(false, $upgrade['perform'] == 'uninstall');
				return $result;
			}
		}
		
		return false;
	}
	
	public static function quickCheckInstall($zipfile) {
		$wizard = UWizard::create($zipfile, 'prepare_install');
		$result = $wizard->unzipPackage();
		if($result['status'] != 'success') return $result;
		$result = $wizard->checkPermissions('copy');
		return $result;
	}
		
	public static function quickInstall($zipfile) {
		$result = self::quickCheckInstall($zipfile);
		if($result['status'] != 'success') return $result;
		$wizard = UWizard::create($zipfile, 'install');
		try {
			$result = $wizard->commit();
		} catch(UWizardError $e) {
			return array('status' => 'error', 'message' => $e->getMessage());
		}
		if($result['status'] != 'success') return $result;
		$result = array('status' => 'success');
		$wizard->unlinkTemp($result);
		return $result;
	}
	
	public static function quickUninstall($zipfile) {
		$wizard = UWizard::create($zipfile, 'prepare_uninstall');
		$result = $wizard->unzipPackage();
		if($result['status'] != 'success') return $result;
		$result = $wizard->checkRemovePermissions();
		if($result['status'] != 'success') return $result;
		$result = $wizard->remove();
		if($result['status'] != 'success') return $result;
		$result = array('status' => 'success');
		$wizard->unlinkTemp($result);
		return $result;
	}
	
	
	public static function prepareUninstall($zipfile, $render = true, $confirm=null) {
		$result = array('status' => 'success');
		$confirm = array_get_default($_POST, 'confirm', $confirm);
		$performed = array();
	
		try {
			$wizard = UWizard::create($zipfile, 'prepare_uninstall', $confirm);
			$steps = array('unzipPackage', 'checkRemovePermissions');
			
			foreach($steps as $step) {
				try {
					$result = $wizard->$step();
				} catch(UWizardError $e) {
					$result = array('status' => 'error', 'message' => $e->getMessage());
				}
				if(! is_array($result))
					throw new UWizardError("Error running step $step, bad return value");
				if(empty($result['label']))
					$result['label'] = translate('LBL_STEP_' . strtoupper($step), 'UWizard');
			
				if($result['status'] == 'warning') {
					if(! isset($result['retry']))
						$result['retry'] = true;
					if(in_array($step, $wizard->confirm))
						$_SESSION['UWizard_result'][$zipfile][$step]['confirm'] = 1;
					if(! empty($_SESSION['UWizard_result'][$zipfile][$step]['confirm']))
						$result['status'] = 'success';
				}
			
				$result['color'] = array_get_default(self::$colors, $result['status'], '#000');
				$result['led'] = '<div class="input-icon ' . array_get_default(self::$leds, $result['status'], 'icon-ledgrey') . '"></div>';
			
				$performed[] = $result;
				if($result['status'] != 'success')
					break;
			}
		} catch (Exception $e) {
			throw new UWizardError($e->getMessage());
		}
		
		if($render)
			echo $wizard->renderPrepareSteps($performed, true);
		
		return $performed;
	}
	
	
	public static function beginUninstall($zipfile, $render=true) {
		$wizard = UWizard::create($zipfile, 'uninstall');
		$type = $wizard->manifest->getType();
		$status = array(
			'perform' => 'uninstall',
			'source' => $zipfile,
			'name' => $wizard->manifest->getName(),
			'id' => $wizard->manifest->getId(),
			'version' => $wizard->manifest->getVersion(),
			'type' => $wizard->manifest->getType(),
			'user_id' => AppConfig::current_user_id(),
			'start_time' => gmdate('Y-m-d H:i:s'),
		);
		self::saveUpgradeStatus($status);
		if($render) {
			echo translate('LBL_UPGRADE_REDIRECT', 'UWizard') . "<script>document.location.href='upgrade.php';</script>";
		}
	}
	
	
	public static function cancelUpgrade() {
		$upgrade = self::getUpgradeStatus();
		if($upgrade && ! empty($upgrade['source'])) {
			$wizard = UWizard::create($upgrade['source'], $upgrade['perform']);
			self::saveUpgradeStatus(null);
			$result = array();
			$wizard->unlinkTemp($result);
			$result['messages'][] = translate('LBL_RESTORED_ACCESS', 'UWizard');
			return $result;
		}
	}
	
	
	protected function checkRemovePermissions()
	{
		$src = self::getUnzipDir($this->manifest);
		$dest = clean_path(getcwd()) . '/';
		$paths = array('copy');
		foreach ($this->manifest->path('copy_conditional', array()) as $c => $unused) {
			$GLOBALS['log']->debug("Checking condition $c for remove permissions ......");
			if (!empty($_SESSION['UWizard_conditions'][$c])) {
				$paths[] = 'copy_conditional.' . $c;
				$GLOBALS['log']->debug("true");
			} else {
				$GLOBALS['log']->debug("false");
			}
		}
		$errors = array();
		foreach ($paths as $path) {
			$defs = $this->manifest->path($path);
			if (is_array($defs)) {
				foreach ($defs as $x) {
					$result = self::checkCanRemove($src . $x['from'], $dest . $x['to']);
					if (!empty($result)) {
						if (!is_array($result))
							$result = array($result);
						foreach ($result as $err)
							$errors[] = array('&lt;base&gt;/' . $x['from'], $dest . $x['to'], $err);
					}
				}
			}
		}
		if (!empty($errors)) {
			$template = translate('LBL_REMOVE_PERMISSION_PROBLEM', 'UWizard');
			$ret = array(
				'status' => 'error',
			);
			foreach ($errors as $error) {
				$message = str_replace('{FROM}', $error[0], $template);
				$message = str_replace('{TO}', $error[1], $message);
				$message = str_replace('{MESSAGE}', translate($error[2], 'UWizard'), $message);
				$ret['errors'][] = $message;
			}
		} else {
			$ret = array(
				'status' => 'success',
				'label' => translate('LBL_STEP_CHECKPERMISSIONS', 'UWizard'),
				'message' => translate('LBL_PERMISSIONS_OK', 'UWizard'),
			);
		}
		return $ret;
	}

	protected function remove($from_ppack = false)
	{
		$result = array();
		$this->restoreBackup($result);
		$this->removeVersion($result);
		$this->finalActions($result, $from_ppack);
		$result['status'] = 'success';
		$result['final'] = 'true';
		return $result;
	}

	protected function removeVersion(&$result)
	{
		$no_db = defined('IAH_IN_INSTALLER');
		if (!$no_db) {
			$lq = new ListQuery('UpgradeHistory');
			$lq->addSimpleFilter('type', $this->manifest->getType());
			$lq->addSimpleFilter('id_name', $this->manifest->getId());
			$lq->addSimpleFilter('version', $this->manifest->getVersion());
			$rows = $lq->runQuery();
			foreach ($rows->getRowIndexes() as $idx) {
				$row = $rows->getRowResult($idx);
				$ru = RowUpdate::for_result($row);
				$ru->deleteRow();
				break;
			}
		} else {
			$all = array_get_default($_SESSION, 'UWizard_UpgradeHistory', array());
			$rows = array();
			foreach ($all as $idx => $p) {
				if (
					array_get_default($p, 'type') == $this->manifest->getType() &&
					array_get_default($p, 'id_name') == $this->manifest->getId() &&
					array_get_default($p, 'version') == $this->manifest->getVersion()
				) {
					unset($_SESSION['UWizard_UpgradeHistory'][$idx]);
				}
			}
		}
		$result['messages'][] = translate('LBL_REMOVED_HISTORY', 'UWizard');
	}
	
	protected function restoreFile($from, $to, $zipDir, $dest, $backupBase)
	{
		$from = clean_path($from);
		$to = clean_path($to);
		$zipDir = clean_path($zipDir);
		$dest = clean_path($dest);
		$backupBase = clean_path($backupBase);
		if (file_exists($zipDir . $from)) {
			if (is_dir($zipDir . $from)) {
				$files = glob($zipDir . $from . '/*', GLOB_NOSORT);
				if($files)
				foreach ($files as $file) {
					$file = basename($file);
					$this->restoreFile($from . '/' . $file, $to . '/' . $file, $zipDir, $dest, $backupBase);
				}
			} else {
				if (is_dir($dest . '/' . $to)) {
					$to = $to . '/' . basename($from);
				}
				if (is_file($backupBase . $to)) {
					@copy($backupBase . $to, $dest . '/' . $to);
				} else {
					@unlink($dest . '/' . $to);
				}
			}
		}
	}

	protected function restoreBackup(&$result)
	{
		$zipDir = self::getUnzipDir($this->manifest);
		$backupBase = self::getBackupDir($this->manifest);
		$dest = clean_path(getcwd()) . '/';
		$errors = array();
		$defs = $this->manifest->path('copy', array());

		foreach ($this->manifest->path('copy_conditional', array()) as $c => $d) {
			$GLOBALS['log']->debug("Checking condition $c for backup restore ..... ");
			if (!empty($_SESSION['UWizard_conditions'][$c])) {
				$defs = array_merge($defs, $d);
				$GLOBALS['log']->debug("true");
			} else {
				$GLOBALS['log']->debug("false");
			}
		}

		if (is_array($defs)) {
			foreach ($defs as $x) {
				$this->restoreFile($x['from'], $x['to'], $zipDir, $dest, $backupBase);
			}
		}
		$result['messages'][] = translate('LBL_RESTORED_FILES_BACKUP', 'UWizard');
	}

	protected function checkPermissions($path = 'copy')
	{
		$errors = array();
		$src = self::getUnzipDir($this->manifest);
		$dest = clean_path(getcwd()) . '/';
		$defs = $this->manifest->path($path, array());

		if ($path == 'copy')
			foreach ($this->manifest->path('copy_conditional', array()) as $c => $d) {
				$GLOBALS['log']->debug("Checking condition $c for install permissions ..... ");
				if (!empty($_SESSION['UWizard_conditions'][$c])) {
					$defs = array_merge($defs, $d);
					$GLOBALS['log']->debug("true");
				} else {
					$GLOBALS['log']->debug("false");
				}
			}

		if (is_array($defs)) {
			foreach ($defs as $x) {
				$result = self::checkCanCopy($src . $x['from'], $dest . $x['to']);
				if ($result) {
					$errors[] = array('&lt;base&gt;/' . $x['from'], $dest . $x['to'], $result[0], $result[1]);
				}
			}
		}
		if ($path == 'copy') {
			if ($this->manifest->getType() == 'patch') {
				if (!is_os_writable($dest . 'sugar_version.php'))
					$errors[] = array('', $dest . 'sugar_version.php', 'LBL_SUGAR_VERSION_NOT_WRITEBLE');
			}
		}
		if (!empty($errors)) {
			$template = translate('LBL_PERMISSION_PROBLEM', 'UWizard');
			$ret = array(
				'status' => 'error',
			);
			foreach ($errors as $error) {
				if(empty($error[2]))
					$message = $template;
				else
					$message = translate($error[2], 'UWizard');
				$message = str_replace('{FROM}', $error[0], $message);
				$message = str_replace('{TO}', $error[1], $message);
				if(isset($error[3])) $message .= ' ('.$error[3].')';
				$ret['errors'][] = $message;
			}
		} else {
			$ret = array(
				'status' => 'success',
				'message' => translate('LBL_PERMISSIONS_OK', 'UWizard'),
			);
		}
		return $ret;
	}

	protected function commit($from_ppack = false)
	{
		$result = array('status' => 'success', 'step' => 'commit', 'label' => translate('LBL_STEP_COMMIT', 'UWizard'));
		$this->copyFiles($result);
		$this->finalActions($result, $from_ppack);
		$this->recordHistory($result, $from_ppack);
		if ($this->manifest->getType() == 'patch' && $this->manifest->getId() == 'InfoAtHand') {
			$this->updateVersion($result);
		}
		return $result;
	}
	
	protected function cleanup($from_ppack = false, $uninstall=false) {
		$result = array('status' => 'success', 'step' => 'cleanup', 'label' => translate('LBL_STEP_CLEANUP', 'UWizard'));
		$this->unlinkTemp($result);
		if(! $uninstall) {
			$manifest = self::readManifest($this->zipfile);
			if($manifest->getType() != 'pers_pack' && ! $manifest->path('is_uninstallable')) {
				$this->deletePackage($this->zipfile);
				$result['messages'][] = translate('LBL_REMOVED_PACKAGE', 'UWizard');
			}
		}
		$this->saveUpgradeStatus(null);
		$result['messages'][] = translate('LBL_RESTORED_ACCESS', 'UWizard');
		return $result;
	}

	protected function recordHistory(&$result, $from_ppack)
	{
		$no_db = defined('IAH_IN_INSTALLER');
		$update = array(
			'filename' => $this->zipfile,
			'type' => $this->manifest->getType(),
			'status' => 'installed',
			'version' => $this->manifest->getVersion(),
			'name' => $this->manifest->getName(),
			'description' => $this->manifest->getDescription(),
			'id_name' => $this->manifest->getId(),
			'from_ppack' =>  (int)$from_ppack
		);
		if (!$from_ppack) {
			$update['ppack_conditions'] = serialize(array_get_default($_SESSION, 'UWizard_conditions'));
			unset($_SESSION['UWizard_conditions']);
		}
		if (!$no_db) {
			$uh = RowUpdate::blank_for_model('UpgradeHistory');
			$uh->set($update);
			$uh->save();
		} else {
			$update['date_entered'] = gmdate('Y-m-d H:i:s');
			if (!isset($_SESSION['UWizard_UpgradeHistory']))
				$_SESSION['UWizard_UpgradeHistory'] = array();
			array_unshift($_SESSION['UWizard_UpgradeHistory'], $update);
		}
		$result['messages'][] = translate('LBL_RECORDED_HISTORY', 'UWizard');
	}

	protected function updateVersion(&$result)
	{
		AppConfig::set_local('info.sugar_version', $this->manifest->getVersion());
		AppConfig::save_local();
		AppConfig::update_sugar_version($this->manifest->getVersion());
		$result['messages'][] = translate('LBL_UPDATED_VERSION', 'UWizard');
	}

	protected function resetCache(&$result)
	{
		AppConfig::cache_reset();
		AppConfig::invalidate_cache('ext');
		AppConfig::invalidate_cache('model');
		AppConfig::invalidate_cache('modinfo');
		AppConfig::invalidate_cache('acl');
		AppConfig::invalidate_cache('views');
		AppConfig::invalidate_cache('display');
		AppConfig::invalidate_cache('notification');
		$result['messages'][] = translate('LBL_RESET_CACHE', 'UWizard');
		$this->resetLanguage($result);
	}

	protected function resetLanguage(&$result)
	{
		AppConfig::invalidate_cache('ext');
		AppConfig::invalidate_cache('lang');
		LanguageManager::cleanJSCache();
		$result['messages'][] = translate('LBL_RESET_LANG', 'UWizard');
	}

	protected function finalActions(&$result, $from_ppack)
	{
		if($from_ppack) return;
		$type = $this->manifest->getType();
		if($type == 'langpack') {
			$this->resetLanguage($result);
		}
		else {
			$this->resetCache($result);
			if($type == 'module' || $type == 'patch' || $type == 'pers_pack')
				$this->repairDB($result);
		}
	}

	protected function repairDB(&$result)
	{	
		require_once('include/database/DBChecker.php');
		$checker = new DBChecker;

		$checker->reloadModels();

		$actions = array(
			'columns' => true,
			'audit' => true,
			'execute' => true,
			'indices' => true,
		);
		$checker->checkTables($actions, false);
		$result['messages'][] = translate('LBL_REPAIRED_DB', 'UWizard');
	}

	protected function unlinkTemp(&$result)
	{
		@rmdir_recursive(self::getUnzipDir($this->manifest));
		$result['messages'][] = translate('LBL_REMOVED_TEMP', 'UWizard');
	}

	protected function copyFiles(&$result)
	{
		$defs = $this->manifest->path('copy', array());
		foreach ($this->manifest->path('copy_conditional', array()) as $c => $d) {
			$GLOBALS['log']->debug("Checking condition $c for copying ......");
			if (!empty($_SESSION['UWizard_conditions'][$c])) {
				$defs = array_merge($defs, $d);
				$GLOBALS['log']->debug("true");
			} else {
				$GLOBALS['log']->debug("false");
			}
		}
		$backupBase = self::getBackupDir($this->manifest);
		if (is_array($defs)) {
			$src = self::getUnzipDir($this->manifest);
			$dest = clean_path(getcwd()) . '/';
			foreach ($defs as $x) {
				$this->copyFile($x['from'], $x['to'],  $src, $dest, $backupBase . $x['to']);
			}
			$result['messages'][] = translate('LBL_COPIED_FILES', 'UWizard');
		}
	}

	protected function precopy()
	{
		$result = $this->checkPermissions('precopy');
		if ($result['status'] != 'success')
			return $result;
		$defs = $this->manifest->path('precopy');
		if (is_array($defs)) {
			$src = self::getUnzipDir($this->manifest);
			$dest = clean_path(getcwd()) . '/';
			foreach ($defs as $x) {
				$this->copyFile($x['from'], $x['to'],  $src, $dest);
			}
		}
		$result = array(
			'status' => 'warning', // force reload
			'retry' => false,
			'no_default_message' => true,
			'message' => translate('LBL_PRECOPY_OK', 'UWizard'),
		);
		return $result;
	}

	protected function copyFile($from, $to, $zipDir, $dest, $backup = false)
	{
		$error = '';
		$quit = 0;
		$from = clean_path($from);
		$to = clean_path($to);
		$zipDir = clean_path($zipDir);
		$dest = clean_path($dest);
		if($backup) $backup = clean_path($backup);
		for ( ; !$quit; $quit++) {
			if (!file_exists($zipDir . $from)) {
				$error = 'ERR_NO_SOURCE_FILE';
				break;
			}
			if (is_dir($zipDir . $from)) {
				if (substr($from, -1) !== '/')
					$from .= '/';
				$files = glob($zipDir . $from . '*', GLOB_NOSORT);
				if($files)
				foreach ($files as $file) {
					if ($backup)
						$b = $backup . '/' . basename($file);
					else $b = false;
					$this->copyFile($from . basename($file), $to . '/' . basename($file), $zipDir, $dest, $b);
				}
			} else {
				if (substr($to, -1) === '/') {
					if(strlen($to) == 1) $to = '';
					$to .= basename($from);
				}
				if (!@mkdir_recursive(dirname($dest . '/' . $to))) {
					$error = 'ERR_CANT_MAKE_PATH';
					break;
				}

				if ($backup) {
					if(substr($backup, -1) === '/') {
						$backup .= basename($to);
					}
					if (!@mkdir_recursive(dirname($backup))) {
						$error = 'ERR_CANT_MAKE_BACKUP_PATH';
						break;
					}
					if (is_file($dest . '/' . $to) && !@copy($dest . '/' . $to, $backup)) {
						$error = 'ERR_CANT_BACKUP';
						break;
					}
				}

				if (!@copy($zipDir . $from, $dest . '/' . $to)) {
					$error = 'ERR_CANT_COPY';
					break;
				}
			}
		}
		if ($error) {
			$error = translate($error, 'UWizard');
			$error = str_replace('{FROM}', $from, $error);
			$error = str_replace('{TO}', $to, $error);
			throw new UWizardError($error);
		}
	}

	
	protected function renderPrepareSteps($results, $uninstall=false)
	{
		$lang = AppConfig::setting('lang.strings.current.UWizard');
		$lists = AppConfig::setting('lang.lists.current.app');
		$type = $this->manifest->getType();
		$template = 'modules/UWizard/templates/prepareResult.tpl';
		$tpl = new Sugar_Smarty;
		$tpl->assign('LANG', $lang);
		$tpl->assign('LISTS', $lists);
		$tpl->assign('colors', self::$colors);
		$tpl->assign('leds', self::$leds);
		$tpl->assign('package_name', $this->manifest->getName());
		$tpl->assign('package_version', $this->manifest->getVersion());
		$tpl->assign('source', $this->zipfile);
		$tpl->assign('action', $this->action);
		$tpl->assign('uninstall', $uninstall);
		if($results) {
			$results[count($results)-1]['final'] = true;
			if($results[count($results)-1]['status'] == 'success')
				$tpl->assign('finished', 1);
		}
		$tpl->assign('results', $results);
		$confirm = $this->confirm;
		if(! isset($confirm)) $confirm = array();
		$tpl->assign('confirm', implode(',', $confirm));
		$tpl->assign('conditions', implode('|', array_keys(array_get_default($_SESSION, 'UWizard_conditions', array()))));
		return $tpl->fetch($template);
	}

	protected function render($step, $result)
	{
		$lang = AppConfig::setting('lang.strings.current.UWizard');
		$lists = AppConfig::setting('lang.lists.current.app');
		$type = $this->manifest->getType();
		$method = 'render_' . $step;
		$tpl = new Sugar_Smarty;
		$colors = array(
			'info' => '#00cc00',
			'warning' => '#000099',
			'error' => '#cc0000',
		);
		$tpl->assign('colors', $colors);
		$tpl->assign('LANG', $lang);
		$tpl->assign('LISTS', $lists);
		$tpl->assign('package_name', $this->manifest->getName());
		$tpl->assign('package_version', $this->manifest->getVersion());
		$tpl->assign('source', $this->zipfile);
		$tpl->assign('this_step', $step);
		$tpl->assign('next_step', $step + 1);
		$tpl->assign('action', $this->action);
		return $this->$method($tpl, $result);
	}

	protected function render_commit($tpl, $result)
	{
		$tpl->assign('result', $result);
		return $tpl->fetch('modules/UWizard/templates/stepCommit.tpl');
	}

	
	// info, warning, error
	protected function addResult(&$result, $name, $level, $message, $ext=null)
	{
		static $lang;
		if (!isset($lang))
			$lang = AppConfig::get_installer_strings();

		$name = isset($lang[$name]) ? $lang[$name] : $name;

		$msg = array('name' => $name, 'level' => $level, 'message' => $message);
		if(isset($ext)) $msg['ext'] = $ext;
		$result['messages'][] = $msg;
		$l = array_get_default($result, 'error_level');
		switch ($l) {
			case 'error':
				break;
			case 'warning':
				if ($level == 'error')
					$result['error_level'] = 'error';
				break;
			default:
				$result['error_level'] = $level;
		}
	}

	protected function checkExtByFunction(&$result, $function, $name, $okMsg, $failMsg, $failOnError)
	{
		static $lang;
		if (!isset($lang))
			$lang = AppConfig::get_installer_strings();
		
		if(function_exists($function)) {
			$level = 'info';
			$message = $okMsg;
		} else {
			$level = $failOnError ? 'error' : 'warning';
			$message = $failMsg;
		}
		$this->addResult($result, $name, $level, $lang[$message]);
	}

	protected function runHook($method, $pos='')
	{
		$method = $pos . $method;
		$hooksFile = $this->manifest->path('hooks.file');
		$hooksClass = $this->manifest->path('hooks.class');
		if (!empty($hooksFile)) {
			$hooksFile = self::getUnzipDir($this->manifest) . $hooksFile;
			if (is_file($hooksFile)) {
				require_once $hooksFile;
				if (!$hooksClass) {
					if (function_exists($method))
						return $method($this->manifest, self::getUnzipDir($this->manifest));
				} else {
					if (method_exists($hooksClass, $method))
					return $hooksClass::$method($this->manifest, self::getUnzipDir($this->manifest));
				}
			}
			else
				$GLOBALS['log']->error("Upgrade hooks file not found: $hooksFile");
		}
	}

	protected function checkSystem()
	{
		$result = array();
		$lang = AppConfig::get_installer_strings();
		if(! AppConfig::test_session()) {
			$phpIniLocation = get_cfg_var("cfg_file_path");
			$message = $lang['LBL_SESSION_ERR_DESCRIPTION'] . "\n" . htmlspecialchars($phpIniLocation);
			$this->addResult($result, 'LBL_CHECKSYS_SESSION', 'error', $message);
		}

		$php_version = phpversion();
		$check_php_version_result = check_php_version();

		switch($check_php_version_result) {
			case -1:
				$level = 'error';
				$message = 'ERR_CHECKSYS_PHP_UNSUPPORTED_BUG';
				break;
			case 0:
				$level = 'error';
				$message = 'ERR_CHECKSYS_PHP_UNSUPPORTED';
				break;
			case 1:
				$level = 'warning';
				$message = 'ERR_CHECKSYS_PHP_DEPRECATED';
				break;
			case 2:
				$level = 'warning';
				$message = 'ERR_CHECKSYS_PHP_UNTESTED';
				break;
			case 3:
				$level = 'info';
				$message = 'LBL_CHECKSYS_PHP_OK';
				break;
		}
		$this->addResult($result, 'LBL_CHECKSYS_PHPVER', $level, $lang[$message] . ' ' . $php_version . ')');

		$this->checkExtByFunction($result, 'mysql_connect', 'LBL_CHECKSYS_DB_MYSQL', 'LBL_CHECKSYS_OK', 'LBL_CHECKSYS_NOT_AVAILABLE', true);
		$this->checkExtByFunction($result, 'xml_parser_create', 'LBL_CHECKSYS_XML', 'LBL_CHECKSYS_OK', 'LBL_CHECKSYS_NOT_AVAILABLE', true);
		$this->checkExtByFunction($result, 'curl_init', 'LBL_CHECKSYS_CURL', 'LBL_CHECKSYS_OK', 'ERR_CHECKSYS_CURL', false);
		$this->checkExtByFunction($result, 'mb_strlen', 'LBL_CHECKSYS_MBSTRING', 'LBL_CHECKSYS_OK', 'ERR_CHECKSYS_MBSTRING', true);
		$this->checkExtByFunction($result, 'gzclose', 'LBL_CHECKSYS_ZLIB', 'LBL_CHECKSYS_OK', 'ERR_CHECKSYS_ZLIB', false);
		$this->checkExtByFunction($result, 'zip_open', 'LBL_CHECKSYS_ZIP', 'LBL_CHECKSYS_OK', 'ERR_CHECKSYS_ZIP', true);
		$this->checkExtByFunction($result, 'imap_open', 'LBL_CHECKSYS_IMAP', 'LBL_CHECKSYS_OK', 'ERR_CHECKSYS_IMAP', false);
		$this->checkExtByFunction($result, 'is_soap_fault', 'LBL_CHECKSYS_SOAP', 'LBL_CHECKSYS_OK', 'ERR_CHECKSYS_SOAP', false);
		$this->checkExtByFunction($result, 'dom_import_simplexml', 'LBL_CHECKSYS_DOM', 'LBL_CHECKSYS_OK', 'ERR_CHECKSYS_DOM', true);
		
		if(function_exists('gd_info')) {
			$this->checkExtByFunction($result, 'imageftbbox', 'LBL_CHECKSYS_GD', 'LBL_CHECKSYS_OK', 'ERR_CHECKSYS_GD_FT', false);
		} else {
			$this->addResult($result, 'LBL_CHECKSYS_GD', 'warning', $lang['ERR_CHECKSYS_GD']);
		}

		$cfg_path = AppConfig::local_config_path();
		if(is_os_writable($cfg_path)) {
			$level = 'info';
			$message = 'LBL_CHECKSYS_OK';
		} else {
			$level = 'error';
			$message = 'ERR_CHECKSYS_NOT_WRITABLE';
		}
		$this->addResult($result, 'LBL_CHECKSYS_CONFIG', $level, $lang[$message], $cfg_path);

		if(version_compare($php_version, '5.3.0', '<')) {
			if('1' == ini_get('safe_mode')) {
				$level = 'error';
				$message = 'ERR_CHECKSYS_SAFE_MODE';
			} else {
				$level = 'info';
				$message = 'LBL_CHECKSYS_OK';
			}
			$this->addResult($result, 'LBL_CHECKSYS_SAFE_MODE', 'error', $lang[$message]);
		}

		$level = 'info';
		$memory_msg     = "";
		$memory_limit = ini_get('memory_limit');
		if(empty($memory_limit)){
			$memory_limit = "-1";
		}
		$sugarMinMem = AppConfig::setting('install.memory_minimum', 120);
		// logic based on: http://us2.php.net/manual/en/ini.core.php#ini.memory-limit
		if( $memory_limit == "" ){          // memory_limit disabled at compile time, no memory limit
			$memory_msg = $lang['LBL_CHECKSYS_MEM_OK'];
		} elseif( $memory_limit == "-1" ){   // memory_limit enabled, but set to unlimited
			$memory_msg = $lang['LBL_CHECKSYS_MEM_UNLIMITED'];
		} else {
			$mem_display = $memory_limit;
			rtrim($memory_limit, 'M');
			$memory_limit_int = (int) $memory_limit;
			if( $memory_limit_int < $sugarMinMem){
				$memory_msg = $lang['ERR_CHECKSYS_MEM_LIMIT_1'] . ' ' . $sugarMinMem . $lang['ERR_CHECKSYS_MEM_LIMIT_2'];
				$memory_msg = str_replace('$memory_limit', $mem_display, $memory_msg);
				$level = 'error';
			} else {
				$memory_msg = $lang['LBL_CHECKSYS_OK'] . " ($memory_limit)";
			}
		}
		$this->addResult($result, $lang['LBL_CHECKSYS_MEM'] . $sugarMinMem . 'M', $level, $memory_msg);

		// external cache
		$cache_type = sugar_cache_type();
		if($cache_type) {
			$level = 'info';
			$message = $lang['LBL_CHECKSYS_OK'] . " ($cache_type)";
		} else {
			$level = 'warning';
			$message = $lang['ERR_CHECKSYS_EXT_CACHE'];
		}
		$this->addResult($result, $lang['LBL_CHECKSYS_EXT_CACHE'], $level, $message);

		if($result['error_level'] == 'info') {
			$result['error_level'] = 'success';
			$result['message'] = translate('LBL_CHECKSYS_OK', 'UWizard');
		}
		else if($result['error_level'] != 'warning')
			$result['error_level'] = 'error';
		else
			$result['message'] = translate('LBL_CHECKSYS_WARN', 'UWizard');
		$result['status'] = $result['error_level'];
		return $result;
	}

	public static function renderUploaded($packages)
	{
		$tpl = new Sugar_Smarty();
		$lang = AppConfig::setting('lang.strings.current.UWizard');
		$lists = AppConfig::setting('lang.lists.current.app');
		$tpl->assign('LANG', $lang);
		$tpl->assign('LISTS', $lists);
		$tpl->assign('packages', $packages);
		echo $tpl->fetch('modules/UWizard/templates/uploaded.tpl');
	}
	
	public static function renderInstalled($packages)
	{
		$tpl = new Sugar_Smarty();
		$lang = AppConfig::setting('lang.strings.current.UWizard');
		$lists = AppConfig::setting('lang.lists.current.app');
		$tpl->assign('LANG', $lang);
		$tpl->assign('LISTS', $lists);
		$tpl->assign('packages', $packages);
		echo $tpl->fetch('modules/UWizard/templates/installed.tpl');
	}

	public static function readManifest($zipfile)
	{
		$tempBase = self::getCacheDir('upgrades') . 'temp';
		mkdir_recursive($tempBase);
		$tempDir = clean_path(mk_temp_dir( $tempBase ));
		unzip_file( $zipfile, 'manifest.php', $tempDir, false);
		$manifest = new IAHManifest($tempDir . '/manifest.php');
		@unlink($tempDir . '/manifest.php');
		@rmdir($tempDir);
		return $manifest;
	}



	public static function isInstallable($type, $id, $version)
	{
		$no_db = defined('IAH_IN_INSTALLER');
		$check = 0;
		if (!$no_db) {
			$lq = new ListQuery('UpgradeHistory');
			$lq->addSimpleFilter('type', $type);
			$lq->addSimpleFilter('id_name', $id);
			$result = $lq->runQuery();
			$rows = $result->getRows();
		} else {
			$all = array_get_default($_SESSION, 'UWizard_UpgradeHistory', array());
			$rows = array();
			foreach ($all as $p) {
				if ($p['type']  == $type && $p['id_name'] == $id) {
					$rows[] = $p;
				}
			}
		}
		foreach ($rows as $row) {
			$compare = version_compare($row['version'], $version);
			if ($compare == 0)
				$check |= self::THIS_VERSION;
			if ($compare > 0)
				$check |= self::NEWER_VERSION;
		}
		return $check;
	}
	
	// utility method to avoid fatal error during patch install on pre-7.0.11 version
	public static function getCacheDir($path) {
		if(class_exists('CacheManager'))
			return CacheManager::get_location($path);
		return AppConfig::cache_dir() . $path . '/';
	}

	public static function copyInstallationPackages()
	{
		$packages = array_get_default($_SESSION, 'UWizard_UpgradeHistory', array());
		foreach ($packages as $p) {
			$u = RowUpdate::blank_for_model('UpgradeHistory');
			$u->set($p);
			$u->save();
		}
		unset($_SESSION['UWizard_UpgradeHistory']);
	}

	protected static function getPPackConditions($manifest)
	{
		$lq = new ListQuery('UpgradeHistory');
		$lq->addSimpleFilter('type', $manifest->getType());
		$lq->addSimpleFilter('id_name', $manifest->getId());
		$lq->addSimpleFilter('version', $manifest->getVersion());
		$lq->addSimpleFilter('status', 'installed');
		$res = $lq->runQuerySingle();
		return @unserialize($res->getField('ppack_conditions'));
	}

}

