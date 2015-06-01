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
<tr><td nowrap="nowrap">{$MOD.COLUMN_TITLE_DEFAULT_VALUE}:</td><td>

<input type='text' name='default_value' value='{$cf->default_value}'>
<script>
addToValidate('popup_form', 'default_value', 'float', false,'{$MOD.COLUMN_TITLE_DEFAULT_VALUE}' );
</script>
</td></tr>
<tr><td nowrap="nowrap">{$MOD.COLUMN_TITLE_MAX_SIZE}:</td><td><input type='text' name='max_size' value='{$cf->max_size|default:11}'>
<script>
addToValidate('popup_form', 'max_size', 'int', false,'{$MOD.COLUMN_TITLE_MAX_SIZE}' );
</script>
</td></tr>
<tr><td nowrap="nowrap">{$MOD.COLUMN_TITLE_PRECISION}:</td><td>
<input type='text' name='ext1' value='{$cf->ext1}'>
<script>
addToValidate('popup_form', 'ext1', 'int', false,'{$MOD.COLUMN_TITLE_PRECISION}' );
</script>
</td></tr>


{include file="modules/DynFields/templates/Fields/Forms/coreBottom.tpl"}
