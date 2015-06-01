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


<tr><td nowrap="nowrap">{$MOD.COLUMN_TITLE_REQUIRED_OPTION}:</td><td><input type="checkbox" class="checkbox" name="required_option" value="1" {if !empty($cf->required_option) && $cf->required_option =='required' }checked{/if}/></td></tr>

<tr><td nowrap="nowrap">{$MOD.COLUMN_TITLE_AUDIT}:</td><td><input type="checkbox" class="checkbox" name="audited" value="1" {if !empty($cf->audited) }checked{/if}/></td></tr>
<tr><td nowrap="nowrap">{$MOD.COLUMN_TITLE_DUPLICATE_MERGE}:</td><td>{html_options name="duplicate_merge" id="duplicate_merge" selected=$cf->duplicate_merge options=$duplicate_merge_options}</td></tr>

</table>
