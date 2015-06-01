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
if(!is_admin($current_user)){
	sugar_die('No Access');
}
?>
<form action="index.php" method="post" name="DetailView" id="form">

			<input type="hidden" name="module" value="Users">
			<input type="hidden" name="user_id" value="">
			<input type="hidden" name="record" value="<?php echo $record; ?>">
			<input type="hidden" name="isDuplicate" value=''>
			
			
			<input type="hidden" name="action">
</form>

<?php
$record = '';
if(isset($_REQUEST['record'])) $record = $_REQUEST['record'];
$users = get_user_array(true, "Active", $record);
echo "<form name='Users'>
<input type='hidden' name='action' value='ListRoles'>
<input type='hidden' name='module' value='Users'>
<select name='record' onchange='document.Users.submit();'>";
echo get_select_options_with_id($users, $record);
echo "</select></form>";
if(!empty($record)){
	require_once('modules/ACLRoles/DetailUserRole.php');
	
}


?>
