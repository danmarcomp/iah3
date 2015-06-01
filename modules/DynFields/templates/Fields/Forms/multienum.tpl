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


{include file="modules/DynFields/templates/Fields/Forms/coreTop.tpl"}
<tr><td nowrap="nowrap">{$MOD.LBL_DROP_DOWN_LIST}:</td><td>{html_options name="ext1" id="ext1" selected=$cf->ext1 values=$dropdowns output=$dropdowns  onChange="dropdownChanged(this.value);"}</td></tr>
<tr><td nowrap="nowrap">{$MOD.COLUMN_TITLE_DEFAULT_VALUE}:</td><td>{html_options name="default_value" id="default_value" selected=$cf->default_value options=$selected_dropdown }</td></tr>
<tr><td nowrap="nowrap">{$MOD.COLUMN_TITLE_DISPLAYED_ITEM_COUNT}:</td><td><input type='text' name='ext2' id='ext2' value='{$cf->ext2|default:5}'>
<script>
addToValidate('popup_form', 'ext2', 'int', false,'{$MOD.COLUMN_TITLE_DISPLAYED_ITEM_COUNT}' );
</script>
</td></tr>
<tr><td nowrap="nowrap">{$MOD.COLUMN_TITLE_MASS_UPDATE}:</td><td><input type="checkbox" name="mass_update" value="1" {if !empty($cf->mass_update)}checked{/if}/></td></tr>

{include file="modules/DynFields/templates/Fields/Forms/coreBottom.tpl"}
<script>dropdownChanged(document.getElementById('ext1').options[document.getElementById('ext1').options.selectedIndex]);</script>

