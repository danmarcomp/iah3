{*

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



*}


<form name="EditView" id='EditView' method="POST" action="index.php">
<input type="hidden" name="module" value="Administration">
<input type="hidden" name="action">
<input type="hidden" name="return_module" value="{$RETURN_MODULE}">
<input type="hidden" name="return_action" value="{$RETURN_ACTION}">
<table width="100%" cellpadding="0" cellspacing="0" border="0">
<tr>
			

		<td style="padding-bottom: 2px;"><input title="{$APP.LBL_SAVE_BUTTON_TITLE}" accessKey="{$APP.LBL_SAVE_BUTTON_KEY}" class="button" onclick="studiotabs.generateGroupForm('EditView');this.form.action.value='SaveTabs'; " type="submit" name="button" value="  {$APP.LBL_SAVE_BUTTON_LABEL}  " > <input title="{$APP.LBL_CANCEL_BUTTON_TITLE}" accessKey="{$APP.LBL_CANCEL_BUTTON_KEY}" class="button" onclick="this.form.action.value='{$RETURN_ACTION}'; this.form.module.value='{$RETURN_MODULE}';" type="submit" name="button" value="  {$APP.LBL_CANCEL_BUTTON_LABEL}  "></td>
</tr><tr>		
		<td style="padding-bottom: 2px;" valign='top'><input type='checkbox' name='user_edit_tabs' value=1 class='checkbox' {if !empty($user_can_edit)}CHECKED{/if}>&nbsp;<b onclick='document.EditView.user_edit_tabs.checked= !document.EditView.user_edit_tabs.checked' style='cursor:default'>{$MOD.LBL_ALLOW_USER_TABS}</b>
	
</tr>
</table>
