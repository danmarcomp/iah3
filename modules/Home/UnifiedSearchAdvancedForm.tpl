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


<form name='UnifiedSearchAdvancedMain' action='index.php' method='post'>
<input type='hidden' name='module' value='Home'>
<input type='hidden' name='advanced' value='true'>
<input type='hidden' name='action' value='UnifiedSearch'>
<input type='hidden' name='search_form' value='false'>
	<table width='100%' class='tabForm' border='0' cellspacing='1'>
	<tr style='padding-bottom: 10px'>
		<td colspan='{$cb_columns}' align="center" nowrap>
			{*<input id='searchFieldMain' class='searchField' type='text' size='70' name='query_string' value='{$query_string}'>*}
			{$search_input}
		</td>
	</tr>
	<tr height='5'><td></td></tr>
	<tr>
	{foreach from=$MODULES_TO_SEARCH name=m key=module item=info}
		<td width='150' style='padding: 5px 3px 0px 0px;' >
			<label>{$info.checkbox}&nbsp;&nbsp;&nbsp;{$info.translated}</label>
		</td>
	{if $smarty.foreach.m.index % $cb_columns  == $cb_columns - 1} 
		</tr><tr>
	{/if}
	{/foreach}
	</tr>
	<tr>
		<td colspan="{$cb_columns}" style="padding-top: 4px">
			<a class='listViewCheckLink' href='javascript:search_check_all(true);'>{$APP.LBL_CHECKALL}</a> &middot;
			<a class='listViewCheckLink' href='javascript:search_check_all(false);'>{$APP.LBL_CLEARALL}</a> &middot;
			<a class='listViewCheckLink' href='javascript:search_reset();'>{$MOD.LBL_DEFAULTS}</a>
		</td>
	</tr>
	<tr>
		<td colspan="{$cb_columns}" style="padding-top: 4px">
			<label>{$PREFIX_MATCH}&nbsp;&nbsp;&nbsp;{$MOD.LBL_MATCH_PREFIX_ONLY}</label>
			<button type="submit" class="input-button input-outer" style="float: right"><div class="input-icon icon-accept left"></div><span class="input-label">{$APP.LBL_SEARCH}</span></button>
		</td>
	</tr>
	</table>
</form>
<br />

<script type="text/javascript">
	var search_defaults = {$default_mods};
{literal}
	function search_reset(state) {
		var form = SUGAR.ui.getForm('UnifiedSearchAdvancedMain'),
			names = SUGAR.ui.getFormInputNames(form);
		for(var idx = 0; idx < names.length; idx++) {
			var m = names[idx].match(/^search_mod_(.*)/);
			if(m)
				SUGAR.ui.getFormInput(form, names[idx]).setValue(search_defaults[m[1]] ? 1 : 0);
		}
		$('prefix_match_only').checked = false;
		$('searchFieldMain').value = '';
	}
	function search_check_all(state) {
		var form = SUGAR.ui.getForm('UnifiedSearchAdvancedMain'),
			names = SUGAR.ui.getFormInputNames(form);
		for(var idx = 0; idx < names.length; idx++) {
			console.log(names[idx]);
			if(names[idx].match(/^search_mod_*/))
				SUGAR.ui.getFormInput(form, names[idx]).setValue(state ? 1 : 0);
		}
	}
{/literal}
</script>
