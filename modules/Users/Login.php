<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/*********************************************************************************
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
 ********************************************************************************/
/*********************************************************************************

 * Description: TODO:  To be written.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/

//we don't want the parent module's string file, but rather the string file specific to this subpanel
global $current_language, $app_strings;
$current_module_strings = return_module_language($current_language, 'Users');

$default_user = AppConfig::setting('site.login.default_user', '');
$default_password = AppConfig::setting('site.login.default_password', '');

$login_password = '';
if(isset($_REQUEST['default_user_name']))
	$login_user_name = $_REQUEST['default_user_name'];
else {
	if(! empty($_COOKIE['ck_login_id_20']))
		$login_user_name = get_user_name($_COOKIE['ck_login_id_20']);
	else {
		$login_user_name = $default_user;
		$login_password = $default_password;
	}
}

$current_module_strings['VLD_ERROR'] = $app_strings["\x4c\x4f\x47\x49\x4e\x5f\x4c\x4f\x47\x4f\x5f\x45\x52\x52\x4f\x52"];

$login_error = array_get_default($_SESSION, 'login_error', '');
$err_style = '';

$session_end_error = get_session_end_eror_msg();
if (! empty($session_end_error))
    $login_error = $session_end_error;

if(empty($login_error))
	$err_style = 'display: none';

$static_url = AppConfig::site_static_url();

$pageInstance->add_js_literal(<<<EOS
	function set_login_focus() {
		var form = SUGAR.ui.getForm('LoginForm'),
			uname = SUGAR.ui.getFormInput(form, 'user_name'),
			upass = SUGAR.ui.getFormInput(form, 'user_password');
		if(uname) {
			if(uname.getValue() != '')
				upass && upass.focus() && upass.select();
			else uname.focus();
		}
	}
	
	function do_login() {
		var cant = $('cant_login');
		if(cant && cant.value != '')
			return false;
		var viewp = SUGAR.ui.getViewport(),
			map = {
				'username': 'user_name',
				'password': 'user_password',
				'language': 'user_language',
				'theme': 'user_theme',
				'login_module': 'login_module',
				'login_action': 'login_action',
				'login_record': 'login_record'
			},
			form = SUGAR.ui.getForm('LoginForm'),
			f, k, params = {
				'res_width': viewp.width,
				'res_height': viewp.height
			};
		for(k in map)
			if( (f = form.elements[map[k]]) ) params[k] = f.value;
		
		var now = new Date();
		var d1 = new Date(now.getFullYear(), 0, 1, 0, 0, 0);
		d1GMTString = d1.toGMTString();
		var d2 = new Date(d1GMTString.substring(0, d1GMTString.lastIndexOf(' ') - 1));
		params.gmto = (d1 - d2) / (1000 * 60) * -1;
		
		$('login_button').disabled = true;
		var req = new SUGAR.conn.JSONRequest('login',
			{status_msg: app_string('LBL_LOGGING_IN')},
			params);
		form.user_password.value = '********';
		req.fetch(login_received, login_error);
		return false;
	}
	
	function login_received() {
		var row = this.getResult();
		if(row.result == 'ok') {
			SUGAR.util.loadUrl(row.redirect || 'index.php', app_string('LBL_REDIRECTING'));
		} else {
			login_error(row.message);
		}
	}
	
	function login_error(msg) {
		var form = SUGAR.ui.getForm('LoginForm'),
			uname = SUGAR.ui.getFormInput(form, 'user_name'),
			upass = SUGAR.ui.getFormInput(form, 'user_password');
		if(upass) upass.setValue('');
		$('login_button').disabled = false;
		show_error(msg || app_string('LBL_CONNECTION_FAILED'));
	}
	
	function show_error(msg) {
		$('error_message').innerHTML = msg;
		toggleDisplay('error_row', true);
	}
	
	function clear_error() {
		toggleDisplay('error_row', false);
	}
EOS
, null, LOAD_PRIORITY_HEAD);

$pageInstance->add_css_literal(<<<EOS
	.loginForm {
		margin: 60px auto;
	}
	.mobile .loginForm {
		margin: 0 auto;
	}
	.loginForm .dataField {
		text-align: left;
	}
	.loginLogo {
		padding: 4px;
		background-color: #ffffff;
		border-bottom: 1px solid #bbbbbb;
		text-align: center;
	}
	td.loginMain {
		border: none;
		border-left: 1px solid #bbbbbb;
		vertical-align: middle;
	}
	td.loginFooter {
		border-top: 1px solid #bbbbbb;
	}
	.loginMessage {
		padding: 1em;
		font-size: 14px;
	}
	.loginError {
		border-top: 1px solid #bbbbbb;
	}
EOS
, null, LOAD_PRIORITY_THEME);


$login_message = '';
if(AppConfig::setting('company.use_custom_login_message')) {
	$login_message = from_html(AppConfig::setting('company.login_message', ''));
} else {
	$custom_msg = AppConfig::setting('site.login.custom_message_file');
	if($custom_msg)
		$login_message = @file_get_contents($custom_msg);
}
if(empty($login_message)) {
	$msg_paths = array(); $msg_lang = array();
	$base_lang = AppConfig::setting('locale.base_language', 'en_us');
	$default_lang = AppConfig::setting('locale.defaults.language');
	foreach(array($current_language, $default_lang, $base_lang) as $l) {
		if(! empty($msg_lang[$l])) continue;
		$msg_lang[$l] = true;
		if(is_demo_site())
			$msg_paths[] = "include/language/{$l}.login_message_demo.html";
		$msg_paths[] = "custom/include/language/{$l}.login_message.html";
		$msg_paths[] = "include/language/{$l}.login_message.html";
	}
	$login_message = '';
	foreach($msg_paths as $p)
		if(file_exists($p) && is_readable($p)) {
			$login_message = file_get_contents($p);
			break;
		}
	$site_url = rtrim(AppConfig::site_url(), '/');
	$login_message = str_replace('{SITE_URL}', $site_url, $login_message);
}


$record = new RowResult;
$record->fields = array(
	'user_name' => array(
		'vname' => 'LBL_USER_NAME',
		'type' => 'user_name',
		'editable' => true,
	),
	'user_password' => array(
		'vname' => 'LBL_PASSWORD',
		'type' => 'password',
		'editable' => true,
	),
	'user_theme' => array(
		'vname' => 'LBL_THEME',
		'type' => 'enum',
		'options' => AppConfig::get_themes(AppConfig::is_mobile()),
		'required' => true,
		'editable' => true,
	),
	'user_language' => array(
		'vname' => 'LBL_LANGUAGE',
		'type' => 'enum',
		'options' => AppConfig::get_languages(),
		'required' => true,
		'editable' => true,
	),
);
$record->row = array(
	'user_name' => $login_user_name,
	'user_password' => $login_password,
	'user_theme' => AppConfig::theme(),
	'user_language' => AppConfig::language(),
);
require_once('include/layout/FieldFormatter.php');
$fmt = new FieldFormatter('html', 'editview');
$record->formatted = $fmt->formatRow($record->fields, $record->row);


$model = new ModelDef('User');
$model->addDisplayFieldDefinitions($record->fields);

require_once('include/layout/forms/FormGenerator.php');
$layout_def = ConfigParser::load_file('modules/Users/views/widget.Login.php');

$layout = new FormLayout($layout_def['detail'] + $layout_def['layout'], $model);
$layout->addFormHooks(array('onsubmit' => 'return do_login();'), false);
$layout->addFormInitHook('set_login_focus()');

$hidden = array('module' => 'Users', 'action' => 'Authenticate', 'return_module' => 'Users', 'return_action' => 'Login');

foreach(array('login_module', 'login_action', 'login_record', 'login_layout') as $f)
	if(isset($_REQUEST[$f])) $hidden[$f] = $_REQUEST[$f];

$layout->addFormHiddenFields($hidden);

$form_gen = FormGenerator::new_form('html', $model, $layout, 'LoginForm');
$form_gen->renderForm($record);
$body = $form_gen->getResult();
$form_gen->exportIncludes();

?>
<div class="loginOuter">

<?php if (AppConfig::is_mobile()) { ?>
<table cellpadding="0" cellspacing="0" border="0" align="center" class="tabForm loginForm" style="padding: 0; width: 90%">
<?php } else  { ?>
<table cellpadding="0" cellspacing="0" border="0" align="center" class="tabForm loginForm" style="padding: 0; width: 600px">
<?php } ?>
<tr valign="top">
<?php if (!AppConfig::is_mobile()) { ?>
<td class="loginLeft">
<div class="loginLogo"><img src="<?php echo $static_url; ?>include/images/iah/infoathand-small.png" width="200" height="45" alt="info@hand"></div>
<div class="loginMessage">
<?php echo $login_message; ?>
</div>
</td>
<?php } ?>
<td class="tabForm loginMain" align="center" width="200">
	<?php
		if (AppConfig::is_mobile())
			$body = '<div style="width: 200px; margin: auto">' . $body . '</div>';
		echo $body;
	?>
</td>
</tr>
<tr id="error_row" style="<?php echo $err_style; ?>">
	<td class="dataLabel loginError" style="text-align: center; padding: 2px" colspan="2">
		<span class="error" id="error_message"><?php echo $login_error; ?></span>
	</td>
</tr>
<?php 
	if(! empty($_SESSION['mobile_detected']) || AppConfig::is_mobile()) {
		echo '<tr><td class="dataLabel loginFooter" style="text-align: center; padding: 2px" colspan="2">';
		$switch_mobile = AppConfig::is_mobile() ? 0 : 1;
		echo '<a href="?mobile=' . $switch_mobile . '" class="tabDetailViewDFLink">[';
		echo $app_strings[$switch_mobile ? 'LBL_LOGIN_MOBILE' : 'LBL_LOGIN_NO_MOBILE'];
		echo ']</a></td></tr>';
	}
?>
</table>
</form>

</div>
