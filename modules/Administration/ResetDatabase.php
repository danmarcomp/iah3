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


action_restricted_for('demo');

require_once('include/Sugar_Smarty.php');

global $mod_strings;
global $app_list_strings;
global $app_strings;
global $current_user;

if (!is_admin($current_user)) sugar_die("Unauthorized access to administration.");

echo get_module_title('Repair', $mod_strings['LBL_RESET_DATABASE_TITLE'], true);


// should be in Forms.php, but easier to maintain here
function get_resetdatabase_js(&$module_info) {
	$names = array_keys($module_info);
	$set_names = '';
	foreach($names as $k=>$n)
		$set_names .= "names[$k] = \"$n\";\n";
	return <<<EOQ
<script type="text/javascript" language="Javascript">
<!--  to hide script contents from old browsers
	var names = new Array();
	$set_names
		
	function checkAll(targ) {
		for(i in names) {
			name = names[i]
			document.getElementById("check_"+name).checked = targ.checked;
		}
	}
// end hiding contents from old browsers  -->
</script>
EOQ;
}

global $db;

$by_type = AppConfig::setting('model.index.by_type');
$models = array('table' => array(), 'bean' => array(), 'link' => array());
foreach($by_type as $type => $mods) {
	if($type == 'template' || $type == 'custom' || $type == 'union')
		continue;
	foreach($mods as $m) {
		$detail = AppConfig::setting("model.detail.$m");
		if(! isset($detail['table_name']))
			continue;
		$mod_dir = array_get_default($detail, 'module_dir');
		if($mod_dir)
			$m = array_get_default($app_list_strings['moduleList'], $mod_dir, $mod_dir);
		else
			$m = 'application'; // needs translating
		$bean = array_get_default($detail, 'bean_name');
		$models[$type][$m] = array(
			'module' => $mod_dir, 'table' => $detail['table_name'],
			'bean' => $bean,
		);
	}
	if(isset($models[$type]))
		ksort($models[$type]);
}

/*$tpl = new Sugar_Smarty();

$tpl->display('modules/Administration/Reset.tpl');
*/



if(isset($_REQUEST['clear_modules']) && is_array($_REQUEST['clear_modules'])) {
	$clear_modules = $_REQUEST['clear_modules'];
	$done = 0;
	
	if(isset($_REQUEST['confirm']) && !empty($_REQUEST['confirm'])
			&& isset($_REQUEST['passwd'])) {
		$passwd = $_REQUEST['passwd'];
		$enc_pwd = md5($passwd);
		$query = "SELECT id from $current_user->table_name where id='$current_user->id' AND user_hash='$enc_pwd'";
		$result = $db->limitQuery($query,0,1, false);
		if(!empty($result)) {
			
			foreach(array_keys($clear_modules) as $mod) {
				if(isset($module_info[$mod]))
					$module_info[$mod]->clear_module();
			}
			
			header("Location: index.php?module=Administration&action=index");
			$done = 1;
		}
		else
			$error_string = $mod_strings['LBL_INCORRECT_PASSWORD'];
	}
	
	if(!$done) {
		$short_list = array();
		foreach(array_keys($clear_modules) as $mod) {
			if(isset($module_info[$mod]))
				$short_list[$mod] = $module_info[$mod];
		}
		ksort($short_list);
		$listView = new ListView();
		$listView->initNewXTemplate("modules/Administration/ResetConfirm.html", $mod_strings);
		$listView->createXTemplate();
		if(isset($error_string))
			$listView->xTemplateAssign("ERROR_STRING", "<span class='error'>$error_string</span><p>");
		$listView->processListRows($short_list, "main", "INFO");
		$listView->xTemplate->out("main");
	}
}
else {
	/*echo get_resetdatabase_js($module_info);
	
	$listView = new ListView();
	$listView->initNewXTemplate("modules/Administration/ResetListView.html", $mod_strings);
	$listView->createXTemplate();
	$listView->processListRows($module_info, "main", "INFO");
	$listView->xTemplate->out("main");*/

}

?>
