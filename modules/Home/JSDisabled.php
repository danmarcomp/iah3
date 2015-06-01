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


global $app_strings, $mod_strings;

require_once('XTemplate/xtpl.php');
$tpl = new XTemplate('include/utils/FlashMessages.html');
$tpl->assign('WIDTH', '60%');
$tpl->assign('ICON', get_image('include/images/iah/warning', 'align="absmiddle"'));

$fw_module = 'Home';
$fw_action = 'index';
$fw_record = '';

if(! empty($_REQUEST['login_module'])) {
	$fw_module = $_REQUEST['login_module'];
	if(! empty($_REQUEST['login_action']))
		$fw_action = $_REQUEST['login_action'];
	if(! empty($_REQUEST['login_record']))
		$fw_record = $_REQUEST['login_record'];
}

$buttons = '<table border="0" width="100%" cellpadding="0" cellspacing="5"><tr>';
$buttons .= <<<EOH
	<td align="right" width="50%">
	<form action="." method="POST">
	<input type="hidden" name="module" value="{$fw_module}" />
	<input type="hidden" name="action" value="{$fw_action}" />
	<input type="hidden" name="record" value="{$fw_record}" />
	<input type="submit" value="{$mod_strings['LBL_BUTTON_CONTINUE_LABEL']}" />
	</form>
	</td>
EOH;

$buttons .= <<<EOH
	<td align="left">
	<form action="." method="POST">
	<input type="hidden" name="module" value="Users" />
	<input type="hidden" name="action" value="Logout" />
	<input type="submit" value="{$app_strings['LBL_LOGOUT']}" />
	</form>
	</td>
EOH;
$buttons .= '</tr></table>';

$tpl->assign('MESSAGE', $mod_strings['LBL_JS_DISABLED']);
$tpl->parse('static.message');
$tpl->assign('CONTENT', $buttons);
$tpl->parse('static.footer');

$tpl->parse('static');
$tpl->out('static');

?>
