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

require_once 'modules/UWizard/UWizard.php';

class BackupStudioWizard
{
	private $errors = array();
	
	static private $messages = array(
		UPLOAD_ERR_INI_SIZE => 'UPLOAD_ERR_INI_SIZE',
		UPLOAD_ERR_FORM_SIZE => 'UPLOAD_ERR_FORM_SIZE',
		UPLOAD_ERR_PARTIAL => 'UPLOAD_ERR_PARTIAL',
		UPLOAD_ERR_NO_FILE => 'UPLOAD_ERR_NO_FILE',
		UPLOAD_ERR_NO_TMP_DIR => 'UPLOAD_ERR_NO_TMP_DIR',
		UPLOAD_ERR_CANT_WRITE => 'UPLOAD_ERR_CANT_WRITE',
		UPLOAD_ERR_EXTENSION => 'UPLOAD_ERR_EXTENSION',
	);
	
	var $params;

	public function __construct($params)
	{
		$this->params = $params;
	}


	public function process()
	{
		if (!empty($this->params['do_backup'])) $this->backup();
	}


	private function extractFiles($zip, $extractPatterns, $deletePatterns)
	{
		global $mod_strings;
		$entries = array();
		for ($i = 0; $i < $zip->numFiles; $i++) {
			$info = $zip->statIndex($i);
			foreach ($extractPatterns as $pattern) {
				if (preg_match($pattern, $info['name'])) {
					$entries[] = $info['name'];
				}
			}
		}
		$existing = array();
		foreach ($deletePatterns as $pattern) {
			$existing = array_merge($existing, glob_unsorted($pattern));
		}
		foreach ($existing as $file) {
			if (!@unlink($file)) {
				$this->errors[] = $mod_strings['ERR_CANT_DELETE'] . ' ' . $file;
				return false;
			}
		}
		if (!empty($entries)) {
			$zip->extractTo(AppConfig::custom_dir(), $entries);
		}
		return true;
	}

	private function backup()
	{
		require_once 'include/database/ListQuery.php';
		require_once 'include/config/format/ConfigWriter.php';
		$export_strings = false;
		$export_lists = false;
		$cw = new ConfigWriter;
		$zip_dir = CacheManager::get_location('temp/');
		
		$date = gmdate('Y-m-d-H-i-s');
		$zipName = "PersonalityPack_" . $date . '.zip';
		$zip = new ZipArchive;
		$zip->open($zip_dir . '/' . $zipName, ZipArchive::OVERWRITE | ZipArchive::CREATE);
	
		$manifest = array(
			'type' => 'pers_pack',
			'name' => 'Personality pack',
			'id' => 'PersPack',
			'version' => $date,
			'author' => 'Long Reach Corporation',
			'published_date' => $date,
			'conditions' => array(),
		);

		if (!empty($this->params['backup_custom'])) {
			$lq = new ListQuery('DynField');
			$result = $lq->fetchAll();
			$manifest['custom']  = $result->getRows();
			foreach ($manifest['custom'] as &$f) {
				unset($f['id']);
				unset($f['date_entered']);
				unset($f['date_modified']);
				unset($f['deleted']);
			}
			$export_strings = true;
		}

		
		if (!empty($this->params['backup_modules'])) {
			$have_modules = false;

			$path = AppConfig::custom_dir() . 'modules/';
			$files = glob_unsorted($path . '*/models/bean.*.php');
			$files = array_merge($files, glob_unsorted($path . '*/metadata/module_info.php'));
			foreach ($files as $file) {
				$have_modules = true;
				$this->addFile($zip, $file, '');
				$manifest['copy_conditional']['modules'][] = array(
					'from' => $file,
					'to' => $file
				);
			}

			$beans = AppConfig::setting('modinfo.primary_beans');
			foreach ($beans as $module => $model) {
				if (AppConfig::setting("modinfo.by_name.$module.detail.created_by_module_designer")) {
					$have_modules = true;
					$dir = "modules/$module";
					$this->addDir($zip, $dir);
					$manifest['copy_conditional']['modules'][] = array(
						'from' => $dir . '/',
						'to' => $dir . '/',
					);
				}
			}
			
			$files = glob_unsorted("modules/*/models/link.mb_*_links.php");
			foreach ($files as $file) {
				$have_modules = true;
				$this->addFile($zip, $file, '');
				$manifest['copy_conditional']['modules'][] = array(
					'from' => $file,
					'to' => $file
				);
			}
			if ($have_modules) {
				$manifest['conditions']['modules'] = true;
			}
		}

		
		//$files = glob_unsorted(AppConfig::custom_dir() . "/modules/*/views/view.Standard.php");
		/*
		foreach ($files as $file) {
			$this->addFile($zip, $file, '');
			$manifest['copy'][] = array(
				'from' => $file,
				'to' => $file
			);
		}
		 */


		if (!empty($this->params['backup_dropdowns'])) {
			$export_lists = true;
		}

		if (!empty($this->params['backup_layout'])) {
			$viewPath = AppConfig::custom_dir() . 'modules/';
			$files = array_merge(
				glob_unsorted($viewPath . '*/views/*.php'),
				glob_unsorted($viewPath . '*/new_views/*.php')
			);
			foreach ($files as $file) {
				$manifest['conditions']['layout'] = true;
				$this->addFile($zip, $file, '');
				$manifest['copy_conditional']['layout'][] = array(
					'from' => $file,
					'to' => $file
				);
			}
		}

		$lang_types = array();
		if ($export_strings) $lang_types[] = 'strings';
		if ($export_lists) $lang_types[] = 'lists';
		foreach ($lang_types as $lt) {
			$langPath = AppConfig::custom_dir() . 'include/language/';
			$files = glob_unsorted($langPath . "lang.*.$lt.php");
			foreach ($files as $file) {
				if ($lt == 'lists') $manifest['conditions']['dropdowns'] = true;
				$this->addFile($zip, $file, $langPath, dirname($file));
				if ($lt == 'lists') {
					$manifest['copy_conditional']['dropdowns'][] = array(
						'from' => $file,
						'to' => $file
					);
				} else {
					$manifest['copy'][] = array(
						'from' => $file,
						'to' => $file
					);
				}
			}

			$langPath = AppConfig::custom_dir() . 'modules/';
			$files = glob_unsorted($langPath . "*/language/lang.*.$lt.php");
			foreach ($files as $file) {
				$m = array();
				if ($lt == 'lists') $manifest['conditions']['dropdowns'] = true;
				$this->addFile($zip, $file, '');
				if ($lt == 'lists') {
					$manifest['copy_conditional']['dropdowns'][] = array(
						'from' => $file,
						'to' => $file
					);
				} else {
					$manifest['copy_conditional']['modules'][] = array(
						'from' => $file,
						'to' => $file
					);
					$manifest['conditions']['modules'] = true;
				}
			}
		}

		$packages = array_get_default($this->params, 'packages', array());
		foreach ($packages as $pid) {
			$package = ListQuery::quick_fetch_row('UpgradeHistory', $pid);
			if (is_file($package['filename'])) {
				$zip->addFile($package['filename'], basename($package['filename']));
				$manifest['included_packages'][] = basename($package['filename']);
			}
		}
		
		$zip->addFromString('manifest.php', "<?php /*  */?>\n\n" . $cw->encode($manifest, null, null, true));

		$zip->close();
		$data = file_get_contents($zip_dir . '/' . $zipName); 
		@unlink($zip_dir . '/' . $zipName);
		
		header("Content-Disposition: attachment; filename=$zipName");
		header("Content-Type: application/octet-stream;");
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" );
		header("Cache-Control: max-age=0");
		header("Pragma: public");
		header("Content-Length: ".strlen($data));
		print $data;
		exit;
	}


	private function addDir($zip, $dir)
	{
		$files = glob($dir . '/*');
		foreach ($files as $file) {
			if (is_dir($file))
				$this->addDir($zip, $file);
			else
				$this->addFile($zip, $file, '');
		}
	}

	private function addFile($zip, $file, $prefix = '', $path = '')
	{
		if (strlen($prefix) && substr($prefix, -1) == '/')
			$prefix = substr($prefix, 0, -1);
		$relative = $file;
		if (strlen($prefix) && strpos($file, $prefix . '/') === 0)
			$relative = substr($relative, strlen($prefix) + 1);
		if ($relative == '.')
			$relative = '';
		$relative = str_replace('./', '', $relative);
		if (strlen($relative)) {
			$rel = dirname($relative);
			if ($rel != '.') {
				if (strlen($path)) $path .= '/';
				$path .= dirname($relative);
			}
		}
		if ($path == '.')
			$path = '';
		$parts = explode('/', $path);
		$path = '';
		foreach ($parts as $part) {
			if (!strlen($part)) continue;
			if (strlen($path)) $path .= '/';
			$path .= $part;
			if ($zip->locateName($path) === false) {
				$zip->addEmptyDir($path);
			}
		}
		if (strlen($path)) $path .= '/';
		$path .= basename($file);
		$zip->addFile($file, $path);
	}

	public function render()
	{
		global $mod_strings;
		echo get_module_title('Administration', to_html($mod_strings['LBL_MODULE_TITLE'] . translate('LBL_SEPARATOR', 'app') . $mod_strings['LBL_CREATE_PPACK']), false);
		echo '<table>';
		echo '<tr><td valign="top">';
		$this->renderBackup();
		echo '</td></tr>';
		echo '</table>';
	}

	private function renderBackup()
	{
		global $mod_strings;
		$installed = UWizard::listInstalledPackages();
		$available = array();
		foreach ($installed as $package) {
			$type = $package['type'];
			$id = $package['id_name'];
			$version = $package['version'];
			if ($type == 'patch') continue;
			if ($type == 'pers_pack') continue;
			$type_id = $type . '_' . $id;
			if (!isset($available[$type_id]))
				$available[$type_id] = $package;
			else {
				if (version_compare($available[$type_id]['version'], $version) < 0)
					$available[$type_id] = $package;
			}
		}
		$packages = '';
		if (!empty($available)) {
			$packages =  "<fieldset><legend>{$mod_strings['LBL_INCLUDE_PACKAGES']}</legend>";
			foreach ($available as $package) {
				$packages .= '<input type="checkbox" name="packages[]" value="' . $package['id'] . '">&nbsp;';
				$packages .= $package['name'] . ' ' . $package['version'];
				$packages .= '<br />';
			}
			$packages .= '</fieldset>';
		}
		echo <<<HTML
<form method="POST" action="index.php">
<input type="hidden" name="module" value="UWizard">
<input type="hidden" name="action" value="createPPack">
<input type="hidden" name="do_backup" value="1">
<table class="tabForm" width="350">
<tr>
	<th class="dataLabel">
		<h4 class="dataLabel">{$mod_strings['LBL_SELECT_CUSTOMIZATIONS']}</h4>
	</th>
</tr>
<tr>
	<td class="dataLabel">
		<input checked="checked" type="checkbox" name="backup_modules" value="1" id="backup_modules"> <label for="backup_modules">{$mod_strings['LBL_BACKUP_MODULES']}</label>
	</td>
</tr>
<tr>
	<td class="dataLabel">
		<input checked="checked" type="checkbox" name="backup_layout" value="1" id="backup_layout"> <label for="backup_layout">{$mod_strings['LBL_BACKUP_LAYOUTS']}</label>
	</td>
</tr>
<tr>
	<td class="dataLabel">
		<input checked="checked" type="checkbox" name="backup_custom" value="1" id="backup_custom"> <label for="backup_custom">{$mod_strings['LBL_BACKUP_CUSTOM']}</label>
	</td>
</tr>
<tr>
	<td class="dataLabel">
		<input checked="checked" type="checkbox" name="backup_dropdowns" value="1" id="backup_dropdowns"> <label for="backup_dropdowns">{$mod_strings['LBL_BACKUP_DROPDOWNS']}</label>
	</td>
</tr>
<tr>
	<td class="dataLabel">
$packages
	</td>
</tr>
<tr>
	<td class="dataLabel">
		<button type="submit" class="input-button input-outer"><div class="input-icon icon-accept left"></div><span class="input-label">{$mod_strings['LBL_CREATE_PPACK_BUTTON']}</button>
	</td>
</tr>
</table>
</form>
HTML;
	}
}

$wizard = new BackupStudioWizard($_POST);
$wizard->process();
$wizard->render();

