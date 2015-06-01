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


<table><tr><td nowrap="nowrap">{$MOD.COLUMN_TITLE_NAME}:</td><td width="50%">
<input type="text" name="name" value="{$cf->name}" {$NOEDIT}/>
<script>
addToValidate('popup_form', 'name', 'DBName', true,'{$MOD.COLUMN_TITLE_NAME} [a-zA-Z_]' );
</script>
</td></tr>

<tr><td nowrap="nowrap">{$MOD.COLUMN_TITLE_LABEL}:</td><td><input type="text" name="label" value="{$cf->label}" {$NOEDIT}/></td></tr>

<tr><td nowrap="nowrap">{$MOD.COLUMN_TITLE_HELP_TEXT}:</td><td><input type="text" name="help" value="{$cf->help}"/></td></tr>

