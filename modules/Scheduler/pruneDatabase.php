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

if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
if (!defined('inScheduler')) die('Unauthorized access');

require_once('include/dir_inc.php');
require_once('include/utils/file_utils.php');

define('PRUNE_DB_BATCH_SIZE', 200);

function pruneDatabase() {
	global $db;
	$GLOBALS['log']->info('----->Scheduler fired job of type pruneDatabase()');
	$backupDir	= CacheManager::get_location('backups', true);
	$backupFile	= 'backup-pruneDatabase-GMT0_'.gmdate('Y_m_d-H_i_s', strtotime('now')).'.php';
	
	$models = array_merge(
		AppConfig::setting('model.index.by_type.bean'),
		AppConfig::setting('model.index.by_type.link')
	);
	
	if($models) {
		foreach($models as $modelName) {			
			$table = AppConfig::setting("model.detail.$modelName.table_name");
			if(! $table)
				continue;
			$table = $db->quoteField($table);
			if(! AppConfig::setting("model.fields.$modelName.id"))
				continue;
			if(! AppConfig::setting("model.fields.$modelName.deleted"))
				continue;
			
			// build column names
			$rCols = $db->query('SHOW COLUMNS FROM '.$table);
			$colNames = array();
			while($aCols = $db->fetchByAssoc($rCols))
				$colNames[] = $aCols['Field']; 
			if(! $colNames)
				continue;
			$colNamesEsc = array_map(array(&$db, 'quoteField'), $colNames);
			
			$qtpl = 'INSERT INTO '.$table.' (';
			$qtpl .= implode(', ', $colNamesEsc);
			$qtpl .= ') VALUES ';
			
			do {
				$qDel = 'SELECT * FROM '.$table.' WHERE deleted = 1 LIMIT ' . constant('PRUNE_DB_BATCH_SIZE');
				$rDel = $db->query($qDel);
				if(! $rDel || ! $db->getRowCount($rDel))
					break;
				
				$ids = array();
				$rows = array();
				while($aDel = $db->fetchByAssoc($rDel)) {
					$vals = array();
					foreach($colNames as $column)
						$vals[] = '"'.$db->quote($aDel[$column]).'"';
					$rows[] = '(' . implode(', ', $vals) . ')';
					$ids[] = '"' . $db->quote($aDel['id']) . '"';
				}
				
				if($rows) {
					$queryString[] = $qtpl . implode(', ', $rows) . ';';
					$query = $db->query('DELETE FROM '.$table.' WHERE id IN (' . implode(',', $ids) . ')');
					if(! $db->query($qDel))
						break;
				} else
					break;
			} while(1);
		}
		
		if(!file_exists($backupDir) || !file_exists($backupDir.'/'.$backupFile)) {
			// create directory if not existent
			mkdir_recursive($backupDir, false);
		}
		// write cache file
		
		write_array_to_file('pruneDatabase', $queryString, $backupDir.'/'.$backupFile);
		return true;
	}
	return false;
}

pruneDatabase();

