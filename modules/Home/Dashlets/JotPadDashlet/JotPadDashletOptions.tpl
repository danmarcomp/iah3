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


<div style='width: 500px'>
<form name='configure_{$id}' action="index.php" method="post" onSubmit='return SUGAR.sugarHome.postForm("configure_{$id}", SUGAR.sugarHome.uncoverPage);' autocomplete="off">
<input type='hidden' name='id' value='{$id}'>
<input type='hidden' name='module' value='Home'>
<input type='hidden' name='action' value='ConfigureDashlet'>
<input type='hidden' name='to_pdf' value='true'>
<input type='hidden' name='configure' value='true'>
<table width="100%" cellpadding="0" cellspacing="0" border="0" class="tabForm" align="center">
<tr>
    <td valign='top' width="30%" nowrap class='dataLabel'>{$titleLbl}</td>
    <td valign='top' class='dataField'>
    	<input class="input-text" name="title" size='20' value='{$title}'>
    </td>
</tr>
<tr>
    <td valign='top' nowrap class='dataLabel'>{$heightLbl}</td>
    <td valign='top' class='dataField'>
    	<input class="input-text" name="height" size='5' value='{$height}'>
    </td>
</tr>
<tr>
    <td align="right" colspan="2">
		<input type='hidden' name='resetDashlet' value=''>
		<button type='button' class='input-button input-outer' onclick="SUGAR.sugarHome.hideConfigure();"><div class="input-icon icon-cancel left"></div><span class="input-label">{$cancelLbl}</span></button>
		<button type='button' class='input-button input-outer' onclick="this.form.resetDashlet.value='1'; this.form.onsubmit();"><div class="input-icon icon-delete left"></div><span class="input-label">{$resetLbl}</span></button>
		<button type='submit' class='input-button input-outer'><div class="input-icon icon-accept left"></div><span class="input-label">{$saveLbl}</span></button>
   	</td>
</tr>
</table>
</form>
</div>
