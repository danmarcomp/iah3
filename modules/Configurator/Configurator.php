<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/**
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
 */



require_once ('include/utils/file_utils.php');

class Configurator {
	var $settings = array();
	var $errors = array('main' => '');
	
	function loadConfig($standard=false, $flat=true) {
		$cfg = AppConfig::extract_settings(array_values($this->settings), $standard);
		$ret = array();
		if(isset($cfg['backup'])) {
			require_once('modules/Administration/backup_utils.php');
			if(isset($_REQUEST['backup_rsync_path']))
				$cfg['backup']['rsync_path'] = $_REQUEST['backup_rsync_path'];
			if(isset($_REQUEST['backup_mysqldump_path']))
				$cfg['backup']['mysqldump_path'] = $_REQUEST['backup_mysqldump_path'];
			$cfg['backup']['rsync_available'] = isRsyncSupported($cfg['backup']['rsync_path']) ? 1 : 0;
			$cfg['backup']['dump_available'] = isDumpSupported($cfg['backup']['mysqldump_path']) ? 1 : 0;
			if($flat) $ret['backup_rsync_available'] = $cfg['backup']['rsync_available'];
			if($flat) $ret['backup_dump_available'] = $cfg['backup']['dump_available'];
		}
		if($flat) {
			$this->flattenConfig($cfg, $cfg2);
			foreach($this->settings as $k => $v)
				$ret[$k] = array_get_default($cfg2, $v);
			return $ret;
		}
		return $cfg;
	}

	function populateDefaults() {
		$cfg = $this->loadConfig(true, true); // load standard, flat	
		$upd = array();
		foreach($this->settings as $k => $f) {
			$upd[$f] = array_get_default($cfg, $k);
		}
		return $upd;
	}
	
	function populateFromPost() {
		$cfg = $this->loadConfig(true, true); // load standard, flat
		
		$upd = array();
		foreach($this->settings as $k => $f) {
			$fk = $k;
			$default = array_get_default($cfg, $k);
			if(isset($_POST[$fk])) {
				if($f == 'inspector.new_password') {
					if(strlen($_POST[$fk]))
						$upd['inspector.password'] = md5(AppConfig::unique_key('i@h').$_POST[$fk]);
				}
				else if($k == 'company_logo_file' || $k == 'site_top_logo_primary')
					continue;
				else if(is_bool($default))
					$upd[$f] = ! empty($_POST[$fk]) ? true : false;
				else if(is_int($default))
					$upd[$f] = (int)$_POST[$fk];
				else
					$upd[$f] = $_POST[$fk];
			}
		}
		
		return $upd;
	}

	function saveConfig() {
		$upd = $this->populateFromPost();
		$coll = AppConfig::setting('database.primary.collation');
		$fc_period = AppConfig::setting('company.forecast_period');
		$fy_start = AppConfig::setting('company.fiscal_year_start');

		$this->saveImages($upd);
				
		AppConfig::update_local($upd, true);
		AppConfig::save_local();
		
		if($coll != AppConfig::setting('database.primary.collation')) {
			// reconnect to use new collation setting (managed on Locale page)
			DBManager::disconnectInstance();
			$GLOBALS['db'] =& DBManager::getInstance();
		}
		if($fc_period != AppConfig::setting('company.forecast_period')
		|| $fy_start != AppConfig::setting('company.fiscal_year_start')) {
			require_once('modules/Forecasts/Forecast.php');
			$forecast = new Forecast;
			$forecast->recreatePull();
		}
	}

	function restoreConfig() {
		$orig = $this->populateDefaults();
		AppConfig::update_local($orig, true);
		AppConfig::save_local();
	}

	function flattenConfig($cfg, &$ret, $prefix='') {
		if(! isset($ret))
			$ret = array();
		foreach($cfg as $cat => $vals) {
			if(! is_array($vals))
				$ret[$prefix.$cat] = $vals;
			else
				$this->flattenConfig($vals, $ret, $prefix.$cat.'.');
		}
	}

	function saveImages(&$updates) {
		require_once('include/upload_file.php');
		$upload_dir = CacheManager::get_location('images/logos/', true);

		if(! empty($_REQUEST['company_logo_file_clear'])) {
			$updates['company.logo_file'] = AppConfig::setting('company.logo_file', '', true);
		} else {
			$upload_logo = new UploadFile('company_logo_file');
			$upload_logo->set_upload_dir($upload_dir);
			if ($upload_logo->confirm_upload(true)) {
				//$upload_logo->unlink_file('', basename($focus->getLogoPath()));
				$upload_logo->final_move('');
				$updates['company.logo_file'] = $upload_logo->get_upload_path();
			}
		}
		
		if(! empty($_REQUEST['site_top_logo_primary_clear'])) {
			$updates['site.top_logo.primary'] = AppConfig::setting('site.top_logo.primary', '', true);
		} else {
			$upload_logo = new UploadFile('site_top_logo_primary');
			$upload_logo->set_upload_dir($upload_dir);
			if ($upload_logo->confirm_upload(true)) {
				//$upload_logo->unlink_file('', basename($focus->getLogoPath()));
				$upload_logo->final_move('');
				$updates['site.top_logo.primary'] = $upload_logo->get_upload_path();
			}
		}
	}

}


?>
