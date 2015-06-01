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

action_restricted_for('demo');

global $current_user, $db;

if ($db->dbType == 'oci8') {
	echo "<BR>";
	echo "<p>".$mod_strings['ERR_NOT_FOR_ORACLE']."</p>";
	echo "<BR>";
	sugar_die('');	
}
if ($db->dbType == 'mssql') {
    echo "<BR>";
    echo "<p>".$mod_strings['ERR_NOT_FOR_MSSQL']."</p>";
    echo "<BR>";
    sugar_die('');  
}

if(! is_admin($current_user)) {
	ACLController::displayNoAccess();
}


$perform = '';
$execute = false;

if(isset($_REQUEST['do_action'])){
	switch($_REQUEST['do_action']){
		case 'display':
		case 'do_display':
			$perform = $_REQUEST['do_action'];
			if(! empty($_REQUEST['execute']))
				$execute = true;
			break;
		case 'export':
			header('Location: async.php?module=Administration&action=RepairSchema&do_action=do_export');
			return;
		case 'do_export':
			$perform = 'export';
			break;
		default:
			$perform = 'menu';
	}
} else {
	$perform = 'menu';
}


if(($perform == 'display' || $perform == 'menu') && ! defined('ASYNC_ENTRY')) {
	echo get_module_title('Repair', $mod_strings['LBL_REPAIR_DATABASE'], true);
}

if($perform == 'menu') {
	echo <<<EOH
	<script type="text/javascript">
		function toggleExec() {
			$('execute_opt').disabled = ! $('display_opt').checked;
		}
		function toggleRepair() {
			$('repair_opt').disabled = ! $('check_opt').checked;
		}
		function toggleIndices() {
			$('indices_opt').disabled = ! $('columns_opt').checked;
		}
		function toggleSendForm() {
			$('sendform').disabled = true;
		}
	</script>
	
	<form id="repairdb" name="repairdb" method="post" autocomplete="off" onsubmit="toggleSendForm()">
		<input type='hidden' name='action' value='RepairSchema'>
		<input type='hidden' name='module' value='Administration'>
	<table class='tabForm' cellpadding="0" cellspacing="0" width="500">
	<tr><th class="dataLabel" colspan="2">
		<h4 class="dataLabel">{$mod_strings['LBL_REPAIR_ACTION']}</h4>
	</th></tr>
	<tr><td>
		<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 0.5em 0">		
		<tr><td class="dataLabel" colspan="2">
			<p><label><input type="radio" id="display_opt" name="do_action" value="display" checked="checked" onclick="toggleExec()">
				<b>{$mod_strings['LBL_REPAIR_DISPLAYSQL']}</b></p>
		</td></tr>
		<tr><td class="dataLabel" width="10">&nbsp;</td>
			<td class="dataLabel">
				<p><label><input type="checkbox" id="execute_opt" name="execute" value="1">
					<b>{$mod_strings['LBL_REPAIR_EXECUTESQL']}</b></p>
			</td>
		</tr>
		<tr><td class="dataLabel" colspan="2">
			<p><label><input type="radio" name="do_action" value="export" onclick="toggleExec()">
				<b>{$mod_strings['LBL_REPAIR_EXPORTSQL']}</b></p>
		</td></tr>
		</table>
	</td></tr>
	<tr><th class="dataLabel" colspan="2">
		<h4 class="dataLabel">{$mod_strings['LBL_REPAIR_STEPS']}</h4>
	</th></tr>
	<tr><td>
		<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 0.5em 0">		
		<tr><td class="dataLabel" colspan="2">
			<p><label><input type="checkbox" id="columns_opt" name="update_columns" value="1" checked="checked" onclick="toggleIndices()">
				<b>{$mod_strings['LBL_REPAIR_UPDATE_COLUMNS']}</b></p>
		</td></tr>
		<tr><td class="dataLabel" width="10">&nbsp;</td>
			<td class="dataLabel">
			<p><label><input type="checkbox" id="indices_opt" name="update_indices" value="1" checked="checked">
				<b>{$mod_strings['LBL_REPAIR_UPDATE_INDICES']}</b></p>
			</td>
		</tr>
		<tr><td class="dataLabel" colspan="2">
			<p><label><input type="checkbox" id="audit_opt" name="update_audit" value="1" checked="checked">
				<b>{$mod_strings['LBL_REPAIR_UPDATE_AUDIT']}</b></p>
		</td></tr>
		<tr><td class="dataLabel" colspan="2">
			<p><label><input type="checkbox" id="check_opt" name="check_tables" value="1" onclick="toggleRepair()">
				<b>{$mod_strings['LBL_REPAIR_CHECK_TABLES']}</b></p>
		</td></tr>
		<tr><td class="dataLabel" width="10">&nbsp;</td>
			<td class="dataLabel">
				<p><label><input type="checkbox" id="repair_opt" name="repair_tables" value="1" disabled="disabled">
					<b>{$mod_strings['LBL_REPAIR_REPAIR_TABLES']}</b></p>
			</td>
		</tr>
		
		<tr><td class="dataLabel" colspan="2">
			<button type="submit" class="input-button input-outer" id="sendform"><div class="input-icon icon-accept left"></div><span class="input-label"> {$mod_strings['LBL_GO']} </span></button>
		</td></tr>
		<tr><td class="dataLabel" colspan="2">
			<br>
			<p>{$mod_strings['LBL_REPAIR_DATABASE_TEXT']}</p>
		</td></tr>
		</table>
	</td></tr>
	</table>
	</form>
EOH;
	return;
}

set_time_limit(3600);

require_once('include/database/DBChecker.php');
$checker = new DBChecker;

$checker->reloadModels();

$actions = array(
	'columns' => ! empty($_REQUEST['update_columns']),
	'audit' => ! empty($_REQUEST['update_audit']),
	'indices' => ! empty($_REQUEST['update_indices']),
	'execute' => $execute,
	'check' => ! empty($_REQUEST['check_tables']),
	'repair' => ! empty($_REQUEST['repair_tables']),
);

$sql = $checker->checkTables($actions, $perform != 'export');

if($perform == 'export') {
	if(is_array($sql))
		$sql = implode("\n", $sql);
	header("Content-Disposition: attachment; filename=repairDB.sql");
	header("Content-Type: text/sql; charset=".AppConfig::charset());
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" );
	header("Cache-Control: post-check=0, pre-check=0", false);
	if(! AppConfig::get_server_compression())
		header("Content-Length: ".strlen($sql));
	echo $sql;
	exit;
}

if(! $sql) {
	echo $mod_strings['LBL_REPAIR_NO_CHANGE'];
}

/*if($perform == 'display' && ! $execute) {
	echo "
	<p>
	<form name='repairdb'>
		<input type='hidden' name='action' value='RepairSchema'>
		<input type='hidden' name='module' value='Administration'>
		<input type='hidden' name='do_action' value='display'>
		<input type='hidden' name='execute' value='1'>
		<input type='submit' class='button' value='{$mod_strings['LBL_REPAIR_EXECUTESQL']}'>
	</form>";
}*/

?>
