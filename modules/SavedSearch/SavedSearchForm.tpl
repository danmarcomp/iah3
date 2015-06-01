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


<table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm" style="border-top: 0px none; margin-bottom: 4px" >
<tr>
<tr valign='top'>
	<td align='left' rowspan='3' colspan='2'>
		<input id='displayColumnsDef' type='hidden' name='displayColumns'>
		<input id='hideTabsDef' type='hidden' name='hideTabs'>
		{$columnChooser}
	</td>
	<td class='dataLabel' nowrap align='left' width='1%'>
		{$MOD.LBL_ORDER_BY_COLUMNS}
	</td>
	<td class='dataField'>
		<select name='orderBy' id='orderBySelect' style="width: 14em">
		</select>
	</td>
</tr>
<tr valign='top'>
	<td nowrap class='dataLabel'>
		{$MOD.LBL_DIRECTION}
	</td>
	<td class='dataField'>
		<span style="white-space: nowrap"><input id='sort_order_asc_radio' type='radio' class='radio' name='sortOrder' value='ASC' {if $selectedSortOrder == 'ASC'}checked{/if}> <span onclick='document.getElementById("sort_order_asc_radio").checked = true' style="cursor: pointer">{$MOD.LBL_ASCENDING}</span></span>
		&nbsp;<span style="white-space: nowrap"><input id='sort_order_desc_radio' type='radio' class='radio' name='sortOrder' value='DESC' {if $selectedSortOrder == 'DESC'}checked{/if}> <span onclick='document.getElementById("sort_order_desc_radio").checked = true' style="cursor: pointer">{$MOD.LBL_DESCENDING}</span></span>
	</td>
</tr>
<tr valign="top">
	<td class='dataLabel' nowrap>
		{$MOD.LBL_SAVE_SEARCH_AS} <img border='0' src='{$imagePath}help.gif' onmouseover="return SUGAR.popups.tooltip('{$MOD.LBL_SAVE_SEARCH_AS_HELP}', this);">
	</td>
	<td class='dataField'>
		<input type='text' name='saved_search_name'>
		<input value='{$SAVE}' title='{$MOD.LBL_SAVE_BUTTON_TITLE}' class='button' type='button' name='saved_search_submit' onclick='SUGAR.savedViews.setChooser(); return SUGAR.savedViews.saved_search_action("save");'>
	</td>
</tr>
</table>
<script type="text/javascript">
	SUGAR.savedViews.columnsMeta = {$columnsMeta};
	saved_search_select = "{$SAVED_SEARCH_SELECT}";
	SUGAR.savedViews.selectedSortOrder = "{$selectedSortOrder|default:'DESC'}";
	SUGAR.savedViews.selectedOrderBy = "{$selectedOrderBy}";
</script>
