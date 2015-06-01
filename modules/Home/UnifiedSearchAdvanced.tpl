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


<form name='UnifiedSearchAdvanced' action='index.php' method='get'>
<input type='hidden' name='module' value='Home'>
<input type='hidden' name='query_string' value=''>
<input type='hidden' name='advanced' value='true'>
<input type='hidden' name='action' value='UnifiedSearch'>
<input type='hidden' name='search_form' value='false'>
	<table width='300' class='tabForm' border='0' cellpadding='0' cellspacing='1'>
	<tr>
		<!--longreach - modified - localization-->
		<th align='left' colspan='3'>{$MOD.LBL_MODULES_TO_SEARCH}</th>
		<th align='right'>
			<img onclick='SUGAR.unifiedSearchAdvanced.get_content()' src='{$IMAGE_PATH}close.gif' border='0'>
		</th>
	</tr>
	{foreach from=$MODULES_TO_SEARCH name=m key=module item=info}
	{if $smarty.foreach.m.first}
		<tr style='padding: 0px 0px 0px 0px; margin: 0px 0px 0px 0px'>
	{/if}
		<td width='1' style='border: none; padding: 0px 10px 0px 0px; margin: 0px 0px 0px 0px'>
	{$smarty.foreach.m.index} 
			<input class='checkbox' id='cb_{$module}' type='checkbox' name='search_mod_{$module}' value='true' {if $info.checked}checked{/if}>
		</td>
		<td style='border: none; padding: 0px 0px 0px 0px; margin: 0px 0px 0px 0px; cursor: hand; cursor: pointer' onclick="document.getElementById('cb_{$module}').checked = !document.getElementById('cb_{$module}').checked;">
			{$info.translated}
		</td>
	{if $smarty.foreach.m.index % 2 == 1} 
		</tr><tr style='padding: 0px 0px 0px 0px; margin: 0px 0px 0px 0px'>
	{/if}
	{/foreach}
	</tr>
	</table>
</form>
