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
<tr><td nowrap="nowrap" valign='top'>{$MOD.COLUMN_TITLE_HTML_CONTENT}:</td><td>{if empty($HTML_EDITOR) }<textarea name='ext4' id='ext4' cols=50>{$cf->ext4}</textarea>
{else}
{$HTML_EDITOR}
<input type='hidden' name='ext4' id='ext4' value='{$cf->ext4}'
{/if}
<div id="html_div"></div></td></tr>
{include file="modules/DynFields/templates/Fields/Forms/coreBottom.tpl"}
{if !empty($HTML_EDITOR) }
{literal}
<script>
document.popup_form.presave = function(){
    
    var oEditor = CKEDITOR.instances.htmlcode;
    document.getElementById('ext4').value = oEditor.getData();
    document.getElementById('ext4').style.display = '';

};
</script>
{/literal}
{/if}
