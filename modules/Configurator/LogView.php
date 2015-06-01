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

action_restricted_for('demo');

if(!is_admin($current_user)){
	sugar_die('Admin Only');	
}

echo get_module_title('Log', $mod_strings['LBL_VIEW_LOGS_TITLE'], true);

$filter = '';
if(!empty($_REQUEST['filter'])){
	$filter = 	$_REQUEST['filter'];
}
$ignore_self = false;
if(!empty($_REQUEST['ignore_self'])){
	$ignore_self = 'checked';	
}
$reg_ex = false;
if(!empty($_REQUEST['reg_ex'])){
	$reg_ex = 'checked';	
}
set_time_limit(180);
echo <<<EOQ
<form action='index.php' name='logview'>
<input type='hidden' name='action' value='LogView'>
<input type='hidden' name='module' value='Configurator'>
<input type='hidden' name='doaction' value=''>

<div class="form-top form-buttons opaque" style="padding: 2px">
<button type="button" class="input-button input-outer" onclick='document.logview.doaction.value="all";document.logview.submit()' name='all'><span class="input-label">All</span></button>
<button type="button" class="input-button input-outer" onclick='document.logview.doaction.value="mark";document.logview.submit()' name='mark'><span class="input-label">Mark Point</span></button>
<button type="submit" class="input-button input-outer" name='display'><span class="input-label">Refresh From Mark</span></button>
<button type="button" class="input-button input-outer" onclick='document.logview.doaction.value="next";document.logview.submit()' name='next'><span class="input-label">Next &gt;&gt;</span></button>
</div>

<div class="form-bottom opaque" style="padding: 5px">
Search: <input type='text' name='filter' value='$filter'>&nbsp;Reg Exp: <input type='checkbox' name='reg_ex' $reg_ex> 
<br>
Ignore Self: <input type='checkbox' name='ignore_self' $ignore_self>
</div>

</form>
EOQ;

define('PROCESS_ID', 1);
define('LOG_LEVEL', 2);
define('LOG_NAME', 3);
define('LOG_DATA', 4);
$logFile = AppConfig::setting('site.log.dir').'/'.AppConfig::setting('site.log.file');

if (!file_exists($logFile)) {
	die('No Log File');
}
$lastMatch = false;
$doaction =(!empty($_REQUEST['doaction']))?$_REQUEST['doaction']:'';

switch($doaction){
	case 'mark':
		echo '<h3>Marking Where To Start Logging From</h3><br>';
		$_SESSION['log_file_size'] = filesize($logFile);
		break;
	case 'next':
		if(!empty($_SESSION['last_log_file_size'])){
			$_SESSION['log_file_size'] = $_SESSION['last_log_file_size'];	
		}else{
			$_SESSION['log_file_size'] = 0;	
		}	
		$_REQUEST['display'] = true;
		break;
	case 'all':
		$_SESSION['log_file_size'] = 0;	
		$_REQUEST['display'] = true;
		break;
}
		

if (!empty ($_REQUEST['display'])) {
	echo '<h3>Displaying Log</h3>';
	$process_id =  getmypid();
	
	echo 'Your process id [' . $process_id. ']';
	echo '<br>Your IP Address is ' . query_client_ip();
	if($ignore_self){
		echo ' it will be ignored ';	
	}
	echo '<br>';
	if (empty ($_SESSION['log_file_size'])) {
		$_SESSION['log_file_size'] = 0;
	}
	$cur_size = filesize($logFile);
	$_SESSION['last_log_file_size'] = $cur_size;
	$pos = 0;
	if ($cur_size >= $_SESSION['log_file_size']) {
		$pos = $_SESSION['log_file_size'] - $cur_size;
	}
	if($_SESSION['log_file_size'] == $cur_size){
		echo 'log has not changed<br>';	
	}else{
		$fp = fopen($logFile, 'r');
		fseek($fp, $pos , SEEK_END);
		echo '<pre>';
		while($line = fgets($fp)){
			$found = preg_match('~\[([0-9]*)\] ([a-zA-Z]+) ([\[\]a-zA-Z0-9\.]+) - (.*)$~', $line, $result);
			//ob_flush();
			//flush();
			if(! $found) {
				if($lastMatch)
					echo $line;
			}else{
				$lastMatch = false;
				$ip = query_client_ip();
				if(empty($result) || ($ignore_self && $result[LOG_NAME] == $ip )){
					
				}else{
					if(empty($filter) || (!$reg_ex && substr_count($line, $filter) > 0) || ($reg_ex && preg_match($filter, $line))){
						$lastMatch = true;
						echo $line;	
					}	
				}
			}	
		}
		echo '</pre>';
		fclose($fp);
	}
}
?>
