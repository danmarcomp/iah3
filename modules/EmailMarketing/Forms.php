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
function get_validate_record_js () {
global $mod_strings;
global $app_strings;

$err_missing_required_fields = $app_strings['ERR_MISSING_REQUIRED_FIELDS'];
$err_lbl_send_message= $mod_strings['LBL_MESSAGE_FOR'];
$the_script  = <<<EOQ

<script type="text/javascript" language="Javascript">
<!--  to hide script contents from old browsers

function trim(s) {
	while (s.substring(0,1) == " ") {
		s = s.substring(1, s.length);
	}
	while (s.substring(s.length-1, s.length) == ' ') {
		s = s.substring(0,s.length-1);
	}

	return s;
}

function verify_data(form,formname) {
	if (!check_form(formname))
		return false;

	var isError = false;
	var errorMessage = "";
		
	var thecheckbox=document.getElementById('all_prospect_lists');
	var theselectbox=document.getElementById('message_for');		

	if (!thecheckbox.checked && theselectbox.selectedIndex < 0)  {
		isError=true;
		errorMessage="$err_lbl_send_message";
	}
			
	if (isError == true) {
		alert("$err_missing_required_fields" + errorMessage);
		return false;
	}
	return true;
}

// end hiding contents from old browsers  -->
</script>

EOQ;

return $the_script;

}

/**
 * Create HTML form to enter a new record with the minimum necessary fields.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 */

?>
