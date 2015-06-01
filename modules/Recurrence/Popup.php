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



require_once('XTemplate/xtpl.php');
require_once('modules/Recurrence/RecurrenceRule.php');
global $theme;
require_once("themes/$theme/layout_utils.php");

global $app_strings;
global $app_list_strings;
global $mod_strings;
global $currentModule;

$image_path = "themes/$theme/images/";

if(!isset($_REQUEST['parent_type']))
	sugar_die("Missing parent type");
$parent_type = $_REQUEST['parent_type'];
$parent_id = isset($_REQUEST['parent_id']) ? $_REQUEST['parent_id'] : '';


$xtpl = new XTemplate('modules/Recurrence/Popup.html');
$xtpl->assign("MOD", $mod_strings);
$xtpl->assign("APP", $app_strings);
$xtpl->assign("THEME", $theme);
$xtpl->assign("IMAGE_PATH", $image_path);


$lbl_save_button_label = $app_strings['LBL_SAVE_BUTTON_LABEL'];
$buttons = <<<EOQ
<button class='input-button input-outer' type='button' onclick="recur_schedule.save(); SUGAR.popups.close();"><div class="input-icon icon-accept left"></div><span class="input-label">$lbl_save_button_label</span></button>
<!--<input title='Check Dates [Alt+C]' accessKey='C' class='button' type='button' name='button' value='  Check Dates  ' onclick="recur_schedule.check_dates();">-->
<button class='input-button input-outer' onclick="SUGAR.popups.close();" type='button'><div class="input-icon icon-cancel left"></div><span class="input-label">{$app_strings['LBL_CANCEL_BUTTON_LABEL']}</span></button>
EOQ;

//insert_popup_header($theme);
echo get_form_header($mod_strings['LBL_RECURRENCE_FORM_TITLE'], $buttons, false);

if(!empty($_REQUEST['recur_rules'])) {
	$recur_rules = from_html($_REQUEST['recur_rules']);
} else if(!empty($parent_id)) {
	$recur = new RecurrenceRule();
	$rules =& $recur->retrieve_by_parent($parent_type, $parent_id);
	$recur_rules = $recur->rules_to_JSON($rules);
}
if(empty($recur_rules))
	$recur_rules = '[]';
$xtpl->assign('RECUR_RULES', $recur_rules);
$xtpl->assign('FORM_NAME', $_REQUEST['form']);

$xtpl->parse('main');
$xtpl->out('main');

echo get_form_footer();
//insert_popup_footer();

$pageInstance->add_js_language('Recurrence', null, LOAD_PRIORITY_BODY);

?>
