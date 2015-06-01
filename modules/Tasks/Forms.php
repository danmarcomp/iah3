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
function get_validate_record_js () {

}

/**
 * Create HTML form to enter a new record with the minimum necessary fields.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 */
function get_new_record_form () {
	if(!ACLController::checkAccess('Tasks', 'edit', true)){
		return '';
	}
global $app_strings, $mod_strings, $app_list_strings;
global $current_user;
global $theme, $timedate;

$user_id = $current_user->id;
$default_status = $mod_strings['LBL_DEFAULT_STATUS'];
$default_priority = $mod_strings['LBL_DEFAULT_PRIORITY'];
$default_parent_type= $app_list_strings['record_type_default_key'];

$cal_dateformat = $timedate->get_cal_date_format();
$ntc_date_format = '('.$timedate->get_user_date_format().')';
$ntc_time_format = '('.$timedate->get_user_time_format().')';
$the_form = get_left_form_header($mod_strings['LBL_NEW_FORM_TITLE']);
$the_form .= <<<EOQ
		<form name="TaskSave" onSubmit="return check_form('TaskSave')" method="POST" action="index.php">
			<input type="hidden" name="module" value="Tasks">
			<input type="hidden" name="record" value="">
			<input type="hidden" name="status" value="${default_status}">
			<input type="hidden" name="assigned_user_id" value='${user_id}'>
			<input type="hidden" name="priority" value="${default_priority}">
			<input type="hidden" name="parent_type" value="${default_parent_type}">
			<input type="hidden" name="action" value="Save">
			<input type="hidden" name="date_due_flag" value="off">
		<p>${mod_strings['LBL_NEW_FORM_SUBJECT']} <span class="required">${app_strings['LBL_REQUIRED_SYMBOL']}</span><br>
		<input name='name' type="text" size="22" value=""><br>
		${mod_strings['LBL_NEW_FORM_DUE_DATE']}&nbsp;<span class="dateFormat">{$ntc_date_format}</span><br>
		<input name='date_due' size="11" maxlength="10" onblur="parseDate(this, '$cal_dateformat');" id='jscal_field' type="text" value=""> <img src="themes/$theme/images/jscalendar.gif" alt="{$app_strings['LBL_ENTER_DATE']}"  id="jscal_trigger" align="absmiddle"><br>
		${mod_strings['LBL_NEW_FORM_DUE_TIME']}&nbsp;<span class="dateFormat">{$ntc_time_format}</span><br>
		<input name='time_due' size="10" maxlength='10' type="text" onchange="validateTimeInput(this);"></p>
		<p><input title="${app_strings['LBL_SAVE_BUTTON_TITLE']}" accessKey="${app_strings['LBL_SAVE_BUTTON_KEY']}" class="button" type="submit" name="button" value="${app_strings['LBL_SAVE_BUTTON_LABEL']}" ></p>
		</form>
		<script type="text/javascript">
		Calendar.setup ({
			inputField : "jscal_field", ifFormat : "$cal_dateformat", showsTime : false, button : "jscal_trigger", singleClick : true, step : 1
		});
		</script>
EOQ;
require_once('include/javascript/javascript.php');
require_once('modules/Tasks/Task.php');
$javascript = new javascript();
$javascript->setFormName('TaskSave');
$javascript->setSugarBean(new Task());
$javascript->addRequiredFields('');
$javascript->addField('date_due', false, '');
$javascript->addField('time_due', false, '');
$the_form .=$javascript->getScript();
$the_form .= get_left_form_footer();


return $the_form;
}

?>
