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


{if $SAVED_SEARCHES_OPTIONS != null}
<select style="width: 150px" name='side_saved_search_select' id='global_saved_search_select' onChange='SUGAR.savedViews.shortcut_select(this, "{$SEARCH_MODULE}");'>
	{$SAVED_SEARCHES_OPTIONS}
</select>
{if $selectedSearch}
&nbsp;<input class='button' onclick='SUGAR.savedViews.setChooser(); return SUGAR.savedViews.saved_search_action("update")' value='{$APP.LBL_UPDATE}' title='{$MOD.LBL_UPDATE_BUTTON_TITLE}' name='ss_update' type='button'>&nbsp;
<input class='button' onclick='return SUGAR.savedViews.saved_search_action("delete", "{$MOD.LBL_DELETE_CONFIRM}")' value='{$APP.LBL_DELETE_BUTTON_LABEL}' title='{$MOD.LBL_DELETE_BUTTON_TITLE}' name='ss_delete' type='button'>
{/if}
{/if}
