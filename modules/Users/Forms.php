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

 * Description:  Contains a variety of utility functions used to display UI
 * components such as form headers and footers.  Intended to be modified on a per
 * theme basis.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/

/**
 * Create javascript to validate the data entered into a record.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 */
function user_get_validate_record_js () {
global $mod_strings;
global $app_strings;

$lbl_last_name = $mod_strings['LBL_LIST_LAST_NAME'];
$lbl_user_name = $mod_strings['LBL_LIST_USER_NAME'];
$err_missing_required_fields = $app_strings['ERR_MISSING_REQUIRED_FIELDS'];
$err_invalid_email_address = $app_strings['ERR_INVALID_EMAIL_ADDRESS'];
$err_self_reporting = $app_strings['ERR_SELF_REPORTING'];
$err_password_mismatch = $mod_strings['ERR_PASSWORD_MISMATCH'];
$err_password_missing = $mod_strings['ERR_INVALID_PASSWORD'];

$the_script  = <<<EOQ
function trim(s) {
	while (s.substring(0,1) == " ") {
		s = s.substring(1, s.length);
	}
	while (s.substring(s.length-1, s.length) == ' ') {
		s = s.substring(0,s.length-1);
	}

	return s;
}

function verify_data(form) {
	var isError = false;
	var errorMessage = "";
	if (trim(form.last_name.value) == "") {
		isError = true;
		errorMessage += "\\n$lbl_last_name";
	}
	if (trim(form.sugar_user_name.value) == "") {
		isError = true;
		errorMessage += "\\n$lbl_user_name";
	}

	if (isError == true) {
		alert("$err_missing_required_fields" + errorMessage);
		return false;
	}
	if (trim(form.email1.value) != "" && !/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,4})+$/.test(form.email1.value)) {
		alert('"' + form.email1.value + '" $err_invalid_email_address');
		return false;
	}
	if (trim(form.email2.value) != "" && !/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,4})+$/.test(form.email2.value)) {
		alert('"' + form.email2.value + '" $err_invalid_email_address');
		return false;
	}
	if (document.EditView.return_id.value != '' && (document.EditView.return_id.value == form.reports_to_id.value)) {
		alert('$err_self_reporting');
		return false;
	}
	
	if(typeof(document.EditView.new_password1) != "undefined" && typeof(document.EditView.new_password1.value) != "undefined") {
		if(document.EditView.new_password1.value != '' && document.EditView.new_password2.value != '') {
			if(document.EditView.new_password1.value != document.EditView.new_password2.value) {
				alert('$err_password_mismatch');
				return false;
			}
		} else {
			alert('$err_password_missing');
			return false;
		}
	}

	// longreach - added
	if(form.day_begin_hour && form.day_end_hour &&
		1*form.day_begin_hour.value > 1*form.day_end_hour.value) {
			alert(form.day_begin_hour.value);
			alert(mod_string('ERR_DAY_END_BEFORE_BEGIN'));
			return false;
	}
	
	return true;
}
EOQ;

return $the_script;
}

function user_get_chooser_js()
{
$the_script  = <<<EOQ
function set_chooser() {	
	var display_tabs_def = '';
	var hide_tabs_def = '';
	
	var display_td = document.getElementById('display_tabs_td');
	var hide_td    = document.getElementById('hide_tabs_td');
	
	var display_ref = display_td.getElementsByTagName('select')[0];
	
	for(i=0; i < display_ref.options.length ;i++)
	{
			 display_tabs_def += "display_tabs[]="+display_ref.options[i].value+"&";
	}
	
	if(hide_td != null)
	{
		var hide_ref = hide_td.getElementsByTagName('select')[0];
		
		for(i=0; i < hide_ref.options.length ;i++)
		{
			 hide_tabs_def += "hide_tabs[]="+hide_ref.options[i].value+"&";
		}
	}
	
	document.EditView.display_tabs_def.value = display_tabs_def;
	document.EditView.hide_tabs_def.value = hide_tabs_def;
}
EOQ;

return $the_script;
}

function user_get_confsettings_js() {
  global $mod_strings;
  global $app_strings;

  $lbl_last_name = $mod_strings['LBL_MAIL_FROMADDRESS'];
  $err_missing_required_fields = $app_strings['ERR_MISSING_REQUIRED_FIELDS'];

  return <<<EOQ
function notify_setrequired(f) {
  if(typeof document.getElementById("smtp_settings") != 'undefined') { 
  	document.getElementById("smtp_settings").style.display = (f.mail_sendtype.value == "SMTP") ? "inline" : "none";
  	document.getElementById("smtp_settings").style.visibility = (f.mail_sendtype.value == "SMTP") ? "visible" : "hidden";
  }
  if(typeof document.getElementById("smtp_auth") != 'undefined') {
	document.getElementById("smtp_auth").style.display = (document.getElementById("mail_smtpauth_req").checked) ? "inline" : "none";
 	document.getElementById("smtp_auth").style.visibility = (document.getElementById("mail_smtpauth_req").checked) ? "visible" : "hidden";	
  }
  return true;
}

function add_checks(f) {
  removeFromValidate('EditView', 'mail_smtpserver');
  removeFromValidate('EditView', 'mail_smtpport');
  removeFromValidate('EditView', 'mail_smtpuser');
  removeFromValidate('EditView', 'mail_smtppass');

  if (f.mail_sendtype.value == "SMTP") {
    addToValidate('EditView', 'mail_smtpserver', 'varchar', 'true', '{$mod_strings['LBL_MAIL_SMTPSERVER']}');
    addToValidate('EditView', 'mail_smtpport', 'int', 'true', '{$mod_strings['LBL_MAIL_SMTPPORT']}');
    if (f.mail_smtpauth_req.checked) {
      addToValidate('EditView', 'mail_smtpuser', 'varchar', 'true', '{$mod_strings['LBL_MAIL_SMTPUSER']}');
      addToValidate('EditView', 'mail_smtppass', 'varchar', 'true', '{$mod_strings['LBL_MAIL_SMTPPASS']}');
    }
  }
  return true;
}

notify_setrequired(document.EditView);

EOQ;
}




