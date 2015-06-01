<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/*********************************************************************************
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
 ********************************************************************************/
require_once('include/dir_inc.php');

function runBackup() {
	ignore_user_abort(true);
	set_time_limit(0);
	ini_set('memory_limit', '-1');

	$now = date("Ymd_His");
	$backupZip = "backup_{$now}.zip";
	$backupDir = rtrim(AppConfig::setting('site.paths.backup_dir'), '/');
	
	$rsyncTargetDir = AppConfig::setting("backup.rsync_target_dir");
	$fileFilter = AppConfig::setting("backup.zip_files_filter");
	$doRsyncFilesDir = false;
	$doDatabaseDump = false;
	if (isRsyncSupported() && AppConfig::setting('backup.rsync_enabled') && $rsyncTargetDir)
		$doRsyncFilesDir = true;
	if($doRsyncFilesDir && $fileFilter == 'complete')
		$fileFilter = 'maximal';
	if (isDumpSupported() && AppConfig::setting('backup.dump_enabled'))
		$doDatabaseDump = true;
	
    $zip = new ZipArchive();
    $zipPath = $backupDir .'/'. $backupZip;
    if (!$zip->open($zipPath, ZipArchive::CREATE)) {
        $GLOBALS['log']->fatal("Error creating backup zip file: {$zip->getStatusString()}");
        $zipFailed = true;
    } else {
		if($doDatabaseDump && backupDatabase($zip, "iahdb_{$now}.sql", $sqlFile)) {
			// close and reopen the zip so we have the SQL backup in case the next step fails
			if(! $zip->close()) {
				$GLOBALS['log']->fatal("Error saving database SQL to zip: {$zip->getStatusString()}");
				$zipFailed = true;
			} else if(! $zip->open($zipPath)) {
				$GLOBALS['log']->fatal("Error reopening backup zip: {$zip->getStatusString()}");
				$zipFailed = true;
			}
			@unlink($sqlFile);
		}
		
		if(empty($zipFailed)) {
			if($fileFilter && $fileFilter != 'none') {
				backupApplication($zip, $fileFilter);
			}
			if(! $zip->close()) {
				$GLOBALS['log']->fatal("Error closing backup zip: {$zip->getStatusString()}");
				$zipFailed = true;
			}
		}
	}
	
	if($doRsyncFilesDir) {
		rsyncBackupFiles(AppConfig::files_dir(), $rsyncTargetDir);
	}
	
	return empty($zipFailed);
}


function backupDatabase(ZipArchive $zip, $sqlName, &$sqlFile) {
	$dbOpts = AppConfig::setting('database.primary');
	if(! $dbOpts) return false;
	$dbhost = escapeshellarg($dbOpts['host_name']);
	$dbuser = escapeshellarg($dbOpts['user_name']);
	$dbpass = escapeshellarg($dbOpts['password']);
	$dbname = escapeshellarg($dbOpts['name']);

	if(empty($sqlFile))
		$sqlFile = createBackupTempFile($sqlName);
	if(! $sqlFile) {
		$GLOBALS['log']->fatal("Error creating temp file for database backup: no target filename for export");
	} else {
		$non_admin_options = "--skip-lock-tables";
		$sqlEscaped = escapeshellarg($sqlFile);
		$execPath = escapeshellarg(AppConfig::setting('backup.mysqldump_path'));
		$command = "$execPath $non_admin_options -h $dbhost -u $dbuser --password=$dbpass $dbname > $sqlEscaped";
		$result = system($command);
		$zip->addFile($sqlFile, $sqlName);
		return true;
	}
	return false;
}


function createBackupTempFile($filename) {
	$tmpdir = sys_get_temp_dir();
	if($tmpdir) {
		if(substr($tmpdir, -1) != DIRECTORY_SEPARATOR) $tmpdir .= DIRECTORY_SEPARATOR;
		$path = $tmpdir . $filename;
		if(@touch($path)) return $path;
	}
	$path = CacheManager::get_location('temp/') . $filename;
	if(@touch($path)) return $path;
	return false;
}


function rsyncBackupFiles($filesDir, $targetDir) {	
	if (!file_exists($targetDir)) {
		if(! mkdir_recursive($targetDir)) {
			$GLOBALS['log']->fatal("Error creating backup directory for rsync backup: $targetDir");
			return false;
		}
	}
	if (!is_dir($targetDir)) {
		$GLOBALS['log']->fatal("Backup directory path is an existing file: $targetDir");
		return false;
	}
	
	$filesDir = escapeshellcmd($filesDir);
	$targetDir = escapeshellcmd($targetDir);
	$execPath = escapeshellarg(AppConfig::setting('backup.rsync_path'));
	$command = "$execPath --recursive --delete --exclude='files/backups/*.zip' ".$filesDir." ".$targetDir;
	system($command);
}


function backupApplication(ZipArchive $zip, $fileFilter=true) {
	if($fileFilter == 'minimal') {
		$cfgPath = AppConfig::local_config_path();
		if(file_exists($cfgPath)) $zip->addFile($cfgPath);
		zipAddDir($zip, 'custom');
		zipAddDir($zip, 'modules');
	} else {
		$systemCacheDir = rtrim(AppConfig::cache_dir(), '/');
		$systemFilesDir = rtrim(AppConfig::files_dir(), '/');
		$except = array($systemCacheDir, $systemFilesDir);
		
		zipAddDir($zip, '.', $except);
		
		if ($fileFilter != 'complete') {
			zipAddDirStructure($zip, $systemFilesDir, true);
		} else {
			zipAddDir($zip, $systemFilesDir, array('backups'));
		}

		zipAddDirStructure($zip, $systemCacheDir, true);
	}
}


function zipAddDir(ZipArchive $zip, $rootPath, $addExceptions=null) {
	$exceptions = array(".", "..", ".svn", ".git", ".gitattributes", ".gitignore", ".idea");
	if($addExceptions) array_extend($exceptions, $addExceptions);
	if(! strlen($rootPath)) $rootPath = '.';
	$stack = array();
	$prefix = $rootPath == '.' ? '' : $rootPath . '/';
	$h = opendir($rootPath);
	
	while($h !== false) {
		while ($file = readdir($h)) {
			if (in_array($file, $exceptions)) {
				continue;
			}
			$path = $prefix . $file;
			if(is_dir($path)) {
				$stack[] = array($h, $prefix);
				$h = opendir($path);
				$prefix .= $file . '/';
				continue;
			}
			$zip->addFile($path);
		}
		closedir($h);
		if($stack) {
			list($h, $prefix) = array_pop($stack);
		} else
			$h = false;
	}
}


function zipAddDirStructure(ZipArchive $zip, $rootPath, $createIndexHtml=false) {
	$exceptions = array(".", "..", ".svn", ".git");
	if(! strlen($rootPath)) $rootPath = '.';
	$stack = array();
	$prefix = $rootPath == '.' ? '' : $rootPath . '/';
	$h = opendir($rootPath);
	
	while($h !== false) {
		while ($file = readdir($h)) {
			if (in_array($file, $exceptions)) {
				continue;
			}
			$path = $prefix . $file;
			if(is_dir($path)) {
				$indexPath = $path . '/index.html';
				$found = file_exists($indexPath);
				if(! $found && $createIndexHtml) {
					if(touch($indexPath)) $found = true;
				}
				if($found)
					$zip->addFile($indexPath);
				else if(method_exists($zip, 'addEmptyDir'))
					$zip->addEmptyDir($path);
				
				$stack[] = array($h, $prefix);
				$h = opendir($path);
				$prefix .= $file . '/';
				continue;
			}
		}
		closedir($h);
		if($stack) {
			list($h, $prefix) = array_pop($stack);
		} else
			$h = false;
	}
}


function getBackupsList() {
    $backupFiles = array();
	$backupDir = rtrim(AppConfig::setting('site.paths.backup_dir'), '/');
    
	$h = opendir($backupDir);
	
	if ($h !== false) {
		while ($file = readdir($h)) {
			$path = $backupDir . '/' . $file;
			if ($file !== false && is_file($path) && substr($file, 0, 7) == 'backup_') {
				$modified = filemtime($path);
				$backupFiles[] = array (
					"name" => $path,
					"size" => filesize($path),
					"modified" => $modified,
				);
			} 
		}
		
		closedir($h);
	}

	SortByDate($backupFiles);	
	
	return $backupFiles;
}

function comparar($a, $b) {
	//reversed to order from newest to oldest, change to ($a["1"], $b["1"]) to do oldest to newest
	return strnatcasecmp($b['modified'], $a['modified']); 
}

function SortByDate(&$Files) {
  usort($Files, 'comparar');
}

function isRsyncSupported($path=null) {
	if(empty($path)) $path = AppConfig::setting('backup.rsync_path');
	if(! preg_match('~rsync(.exe)?$~', $path)) return false;
	$path = escapeshellarg($path);
	if($path)
		$out = @shell_exec("$path --help");

	if (! empty($out) && strpos($out, "rsync") !== false) {
		return true;
	} else {
		return false;
	}
}

function isDumpSupported($path=null) {
	if(empty($path)) $path = AppConfig::setting('backup.mysqldump_path');
	if(! preg_match('~mysqldump(.exe)?$~', $path)) return false;
	$path = escapeshellarg($path);
	if($path)
		$out = @shell_exec("$path --help");

	if (! empty($out) && strpos($out, "mysqldump") !== false) {
		return true;
	} else {
		return false;
	}
}


?>
