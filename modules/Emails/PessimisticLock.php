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
global $mod_strings;
$userName = $mod_strings['LBL_UNKNOWN'];

if(isset($_REQUEST['user'])) {
	require_once('modules/Users/User.php');
	$user = new User();
	if ($user->retrieve($_REQUEST['user'])) {
    	$userName = $user->first_name.' '.$user->last_name;
    }
}


// NEXT FREE
if(isset($_REQUEST['next_free']) && $_REQUEST['next_free'] == true) {
	require_once('modules/Emails/Email.php');
	require_once('modules/EmailFolders/EmailFolder.php');
	$next = new Email();
		$qE = 'SELECT count(id) AS c FROM emails WHERE deleted = 0 AND assigned_user_id =-1';
		$rE = $next->db->query($qE);
		$aE = $next->db->fetchByAssoc($rE);

		if($aE['c'] > 0) {
			$qE = 'SELECT id FROM emails WHERE deleted = 0 AND assigned_user_id = -1 LIMIT 1';
			$rE = $next->db->query($qE);
			$aE = $next->db->fetchByAssoc($rE);
			$next->retrieve($aE['id']);
			$next->assigned_user_id = $current_user->id;
			$next->folder = EmailFolder::get_std_folder_id($current_user->id, STD_FOLDER_INBOX);
			$next->save();
			
			header('Location: index.php?module=Emails&action=DetailView&record='.$next->id);
			
		} else {
			// no free items
			header('Location: index.php?module=Emails&action=ListView&type=inbound&group=true');
		}
}
?>
<table width="100%" cellpadding="12" cellspacing="0" border="0">
	<tr>
		<td valign="middle" align="center" colspan="2">
			<?php echo $mod_strings['LBL_LOCK_FAIL_DESC']; ?>
			<br>
			<?php echo $userName.$mod_strings['LBL_LOCK_FAIL_USER']; ?>
		</td>
	</tr>
	<tr>
		<td valign="middle" align="right" width="50%">
			<a href="index.php?module=Emails&action=index&type=inbound&group=true"><?php echo $mod_strings['LBL_BACK_TO_GROUP']; ?></a>
		</td>
		<td valign="middle" align="left">
			<a href="index.php?module=Emails&action=PessimisticLock&next_free=true"><?php echo $mod_strings['LBL_NEXT_EMAIL']; ?></a>
		</td>
	</tr>
</table>
