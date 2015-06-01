<?php
/*
 *
 * The contents of this file are subject to the info@hand Software License Agreement Version 1.3
 *
 * ("License"); You may not use this file except in compliance with the License.
 * You may obtain a copy of the License at <http://1crm.com/pdf/swlicense.pdf>.
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the
 * specific language governing rights and limitations under the License,
 *
 * All copies of the Covered Code must include on each user interface screen:
 * (i) the 1CRM copyright notice,
 * (ii) the "Powered by the 1CRM Engine" logo, 
 *
 * (iii) the "Powered by SugarCRM" logo, and
 * (iv) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.
 * See full license for requirements.
 *
 * The Original Code is : 1CRM Engine proprietary commercial code.
 * The Initial Developer of this Original Code is 1CRM Corp.
 * and it is Copyright (C) 2004-2012 by 1CRM Corp.
 *
 * All Rights Reserved.
 * Portions created by SugarCRM are Copyright (C) 2004-2008 SugarCRM, Inc.;
 * All Rights Reserved.
 *
 */

global $app_list_strings;

$links = array(
	'Contacts' => 'contacts',
	'Leads' => 'leads',
	'Prospects' => 'prospects',
);

$error = null;
$options = get_select_options_with_id($app_list_strings['prospect_list_type_dom'], 'default');
$uids = array_get_default($_POST, 'list_uids', '');
$list_id = array_get_default($_POST, 'list_id', '');
$target_module = array_get_default($_POST, 'target_module', 'Contacts');

if (!isset($links[$target_module])) {
	$error = 'LBL_INVALID_TARGET_MODULE';
}

if ($error) {
	echo '<div class="error">' . translate($error, 'ProspectLists') . '</div>';
}

echo <<<HTML

<form id="convert_form" method="POST">
<input type="hidden" name="module" value="ProspectLists" />
<input type="hidden" name="action" value="PopupConvertContacts" />
<input type="hidden" name="list_id" value="{$list_id}" />
<input type="hidden" name="target_module" value="{$target_module}" />
<table class="tabForm" border="0" cellpadding="0" cellspacing="0">
<tr>
	<td class="dataLabel"><label><input id="create_new" type="radio" name="create_new" value="1" checked="checked" onclick="$('row_type').style.display=''; $('row_create_new').style.display=''; $('row_use_existing').style.display='none'" />&nbsp;{$mod_strings['LBL_CONVERT_NEW']}</label></td>
	<td class="dataLabel" style="padding-left: 1em"><label><input type="radio" name="create_new" value="0" onclick="$('row_type').style.display='none';$('row_create_new').style.display='none'; $('row_use_existing').style.display=''" />&nbsp;{$mod_strings['LBL_CONVERT_EXISTING']}</label></td>
</tr>
<tr id="row_create_new">
	<td class="dataLabel">{$mod_strings['LBL_CONVERT_NEW_NAME']}</td>
	<td class="dataField"><input type="text" name="new_name" id="new_name"/></td>
</tr>
<tr id="row_type">
	<td class="dataLabel">{$mod_strings['LBL_LIST_TYPE']}</td>
	<td class="dataField"><select name="new_type" id="new_type" >$options</select></td>
</tr>
<tr id="row_use_existing" style="display:none">
	<td class="dataLabel">{$mod_strings['LBL_CONVERT_SELECT']}</td>
	<td class="dataField" id="list_select"></td>
</tr>
<tr>
	<td class="dataLabel" style="padding-top: 0.5em">
	<button onclick="return sendConvertForm(this.form)" tabindex="" style="" class="form-button input-outer" type="submit" ><div class="input-icon left icon-accept"></div><span class="input-label">{$mod_strings['LBL_CONVERT_PROCEED']}</span></button>
</td></tr>
</table>
</form>

<script type="text/javascript">
	var form = $('convert_form');
	var attrs = {module: 'ProspectLists'};
    attrs.init_key = '';
	attrs.init_value = '';
	attrs.form = form;
	attrs.key_name = 'selected_list_id';
	attrs.key_id = 'selected_list_id';
    var input = new SUGAR.ui.RefInput('selected_list', attrs);
    SUGAR.ui.registerInput(form, input);
	var el = input.render();
	$('list_select').appendChild(el);

	function sendConvertForm(form)
	{
		if ($('create_new').checked) {
			if ($('new_name').value.replace(/^\s\s*/, '').replace(/\s\s*$/, '') == '') {
				alert('{$mod_strings['LBL_CONVERT_ENTER_NAME']}');
				return false;
			}
		} else {
			if ($('selected_list_id').value.replace(/^\s\s*/, '').replace(/\s\s*$/, '') == '') {
				alert('{$mod_strings['LBL_CONVERT_SELECT_LIST']}');
				return false;
			}
		}
		try {
			var params = {
				create_new: $('create_new').checked ? '1' : '0',
				new_name: $('new_name').value,
				new_type: $('new_type').value,
				selected_list_id: $('selected_list_id').value,
				target_module: '$target_module'
			};
			var ret = sListView.sendMassUpdate('$list_id', 'ConvertToTargetList', null, params);
			SUGAR.ui.PopupManager.close();
			return ret;
		} catch(e) {
			console.error(e);
			return false;
		}
	};

</script>
HTML;

