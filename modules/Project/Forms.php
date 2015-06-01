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
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');


require_once("themes/$theme/layout_utils.php");
require_once('XTemplate/xtpl.php');

/**
 * Create javascript to validate the data entered into a record.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 */
function get_validate_record_js () {
	return '';
}

/**
 * Create HTML form to enter a new record with the minimum necessary fields.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 */
function get_new_record_form($form='NewProject') {
    if (!ACLController::checkAccess('Project', 'edit', true)) {
        return '';
    }
	global $app_strings, $app_list_strings;
	global $mod_strings;
	global $image_path;
	global $current_user;
		
	$the_form = get_left_form_header($mod_strings['LBL_NEW_FORM_TITLE']);
	
	$xtpl = new XTemplate('modules/Project/NewProjectForm.html');
    if(!ACLController::checkAccess('Accounts', 'list', true)) {
        $xtpl->assign('ACCOUNTS_POPUP_DISABLED', ' disabled="disabled" ');
    }
	$xtpl->assign('APP', $app_strings);
	$xtpl->assign('MOD', $mod_strings);
	$xtpl->assign('FORM', $form);
	$xtpl->assign('IMAGE_PATH', $image_path);
	$xtpl->assign('CURRENT_USER', $current_user->id);
	$phase_opts = get_select_options_with_id($app_list_strings['project_status_dom'], '');
	//echo "<pre>$phase_opts</pre>";
	$xtpl->assign('PROJECT_PHASE_OPTIONS', $phase_opts);
	
	require_once('include/TimeDate.php');
	$timedate = new TimeDate();
	$xtpl->assign("CALENDAR_DATEFORMAT", $timedate->get_cal_date_format());
	$xtpl->assign('USER_DATEFORMAT', $timedate->get_user_date_format());
	
	// Set up account popup
	require_once('include/JSON.php');
	$popup_request_data = array(
		'call_back_function' => 'set_return',
		'form_name' => $form,
		'field_to_name_array' => array(
			'id' => 'account_id',
			'name' => 'account_name',
			),
		);
	$json = new JSON(JSON_LOOSE_TYPE);
	$encoded_popup_request_data = $json->encode($popup_request_data);
	$xtpl->assign('encoded_popup_request_data', $encoded_popup_request_data);
	
	require_once('include/TimeDate.php');
	$timedate = new TimeDate();
	$start_date = date('Y-m-d');
	$xtpl->assign('DATE_STARTING', $timedate->to_display_date($start_date));

	$xtpl->parse('main');
	$the_form .= $xtpl->text('main');
	
	$the_form .= get_left_form_footer();
	$the_form .= get_validate_record_js();
	
	require_once('include/javascript/javascript.php');
	require_once('modules/Project/Project.php');
	$javascript = new javascript();
	$javascript->setFormName($form);
	$javascript->setSugarBean(new Project());
	$javascript->addAllFields('');
	$the_form .= $javascript->getScript();
	
	return $the_form;

}

?>