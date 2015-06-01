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
global $mod_strings;
global $app_strings;
global $app_list_strings;
global $theme;
global $current_user;

$lbl_required_symbol = $app_strings['LBL_REQUIRED_SYMBOL'];
$lbl_default_status = $app_list_strings['case_status_default_key'];
$lbl_subject = $mod_strings['LBL_SUBJECT'];
$lbl_save_button_title = $app_strings['LBL_SAVE_BUTTON_TITLE'];
$lbl_save_button_key = $app_strings['LBL_SAVE_BUTTON_KEY'];
$lbl_save_button_label = $app_strings['LBL_SAVE_BUTTON_LABEL'];
$user_id = $current_user->id;

// longreach - start added
$queue_user_id = AppConfig::setting('company.case_queue_user');
if (!empty($queue_user_id)) {
	$user_id = $queue_user_id;
}
// longreach - end added


$the_form = get_left_form_header($mod_strings['LBL_NEW_FORM_TITLE']);
$the_form .= <<<EOQ
		<form name="CaseSave" onSubmit="return check_form('CaseSave')" method="POST" action="index.php">
			<input type="hidden" name="module" value="Cases">
			<input type="hidden" name="record" value="">
			<input type="hidden" name="priority" value="P2">
			<input type="hidden" name="status" value="${lbl_default_status}">
			<input type="hidden" name="assigned_user_id" value='${user_id}'>
			<input type="hidden" name="action" value="Save">



		${lbl_subject}&nbsp;<span class="required">${lbl_required_symbol}</span><br>
		<p><input name='name' type="text" size='20' maxlength="255" value=""><br>
EOQ;
if(AppConfig::setting('site.feature.require_accounts')){

///////////////////////////////////////
///
/// SETUP ACCOUNT POPUP

$popup_request_data = array(
	'call_back_function' => 'set_return',
	'form_name' => "CaseSave",
	'field_to_name_array' => array(
		'id' => 'account_id',
		'name' => 'account_name',
		),
	);

$json = getJSONobj();
$encoded_popup_request_data = $json->encode($popup_request_data);

//
///////////////////////////////////////
// longreach - added
$disabled = '';
if (ACLController::moduleSupportsACL('Accounts')  && !ACLController::checkAccess('Accounts', 'list', true)) {
    $disabled = ' disabled="disabled" ';
}

// longreach - added $disabled
$the_form .= <<<EOQ
		${mod_strings['LBL_ACCOUNT_NAME']}&nbsp;<span class="required">${lbl_required_symbol}</span><br>
<input type="text" name="account_name" id="account_name" class="sqsEnabled" autocomplete="off" value="" size="16">
<input type="hidden" name="account_id" id="account_id" value="">
<input type="button" $disabled name="btn1" class="button" title="{$app_strings['LBL_SELECT_BUTTON_TITLE']}" accesskey="{$app_strings['LBL_SELECT_BUTTON_KEY']}" value='{$app_strings['LBL_SELECT_BUTTON_LABEL']}'
	onclick='open_popup("Accounts", 600, 400, "", true, false, {$encoded_popup_request_data});' /><br>
EOQ;
}
$the_form .= <<<EOQ
<p>		<input title="${lbl_save_button_title}" accessKey="${lbl_save_button_key}" class="button" type="submit" name="button" value="  ${lbl_save_button_label}  " ></p>
		
		</form>
EOQ;
// longreach - start added
$qsAccount = array( 
    'method' => 'query',
    'modules' => array('Accounts'), 
	'group' => 'or', 
	'field_list' => array('name', 'id', ), 
    'populate_list' => array('account_name', 'account_id'), 
	'conditions' => array(array('name'=>'name','op'=>'like_custom','end'=>'%','value'=>'')), 
	'order' => 'name', 
	'limit' => '30',
	'no_match_text' => $app_strings['ERR_SQS_NO_MATCH']
); 
require_once('include/QuickSearchDefaults.php');
$qsd = new QuickSearchDefaults();
$quicksearch_js = $qsd->GetQSScripts();
$json = getJSONobj();
$quicksearch_js .= '<script type="text/javascript" language="javascript">sqs_objects = {"account_name" : ' . $json->encode($qsAccount) . '}</script>';
$the_form .= $quicksearch_js;
// longreach - end added

require_once('include/javascript/javascript.php');
require_once('modules/Cases/Case.php');
$javascript = new javascript();
$javascript->setFormName('CaseSave');
$javascript->setSugarBean(new aCase());
$javascript->addRequiredFields('');
// longreach - start
$javascript->addToValidateBinaryDependency('account_name', 'alpha', $app_strings['ERR_SQS_NO_MATCH_FIELD'] . $mod_strings['LBL_ACCOUNT_NAME'], 'false', '', 'account_id');
$the_form .=$javascript->getScript();

// longreach - start added
$the_form.= <<<SCRIPT
<script type="text/javascript">
addToValidate('EditView', 'account_name', 'alpha', true, '{$mod_strings['LBL_ACCOUNT_NAME']}' );
</script>
SCRIPT;
// longreach - end added

$the_form .= get_left_form_footer();

return $the_form;
}

?>
