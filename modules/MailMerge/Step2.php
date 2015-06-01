 <?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
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



/*
 * Created on Oct 4, 2005
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
require_once('XTemplate/xtpl.php');
require_once("data/Tracker.php");

//require_once('include/utils.php');
require_once('include/json_config.php');
$json_config = new json_config();

global $app_strings;
global $app_list_strings;
global $mod_strings;
global $current_user;
global $theme;

$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";
require_once($theme_path.'layout_utils.php');


$xtpl = new XTemplate('modules/MailMerge/Step2.html');
$xtpl->assign("MOD", $mod_strings);
$xtpl->assign("APP", $app_strings);
$xtpl->assign('JSON_CONFIG_JAVASCRIPT', $json_config->get_static_json_server(false, true));

if(isset($_POST['mailmerge_module']))
{
	$_SESSION['MAILMERGE_MODULE'] = $_POST['mailmerge_module'];	

	if($_SESSION['MAILMERGE_MODULE'] == 'Contacts' || $_SESSION['MAILMERGE_MODULE'] == 'Leads')
	{
		
		$_SESSION['MAILMERGE_SKIP_REL'] = true;
	}
}
$step_txt = "Step 2: ";
if(!empty($_SESSION['SELECTED_OBJECTS_DEF'])){
	$selObjs = $_SESSION['SELECTED_OBJECTS_DEF'];
	$sel_obj = array();
	parse_str($selObjs,$sel_obj);
	$idArray = array();

	foreach($sel_obj as $key => $value){
		$value = str_replace("##", "&", $value);
		$idArray[$key] = $value;
      
	}
     
	$xtpl->assign("MAILMERGE_PRESELECTED_OBJECTS", get_select_options_with_id($idArray, '0'));
	$step_txt .= "Refine list of ".$_SESSION['MAILMERGE_MODULE']." to merge.";
}
else
{
	$step_txt .= "Select list of ".$_SESSION['MAILMERGE_MODULE']." to merge.";
}

if(isset($_SESSION['MAILMERGE_SKIP_REL']) && $_SESSION['MAILMERGE_SKIP_REL'])
{
	$xtpl->assign("STEP", "4");	
	
}
else
{

	$selected = '';
	if(isset($_SESSION['MAILMERGE_CONTAINS_CONTACT_INFO']) && $_SESSION['MAILMERGE_CONTAINS_CONTACT_INFO']){
		$selected = $_SESSION['MAILMERGE_CONTAINS_CONTACT_INFO'];	
	}
	$xtpl->assign("STEP", "3");	
	//$xtpl->assign("MAIL_MERGE_CONTAINS_CONTACT_INFO", '<table><tr><td><input id="contains_contact_info" name="contains_contact_info" class="checkbox" type="checkbox" '.$checked.'/></td><td>'.$mod_strings['LBL_CONTAINS_CONTACT_INFO'].'</td></tr></table>');
	$rel_options = array(""=>"--None--", "Contacts"=>"Contacts");
	if($_SESSION['MAILMERGE_MODULE'] == "Accounts"){
		$rel_options["Opportunities"] = "Opportunities";
	}
	elseif($_SESSION['MAILMERGE_MODULE'] == "Opportunities"){
		$rel_options["Accounts"] = "Accounts";
	}
	$xtpl->assign("MAIL_MERGE_CONTAINS_CONTACT_INFO", '<table><tr><td>'.$mod_strings['LBL_CONTAINS_CONTACT_INFO'].'</td><td><select id="contains_contact_info" name="contains_contact_info">'.get_select_options_with_id($rel_options, $selected).'</select></td></tr></table>');
}

$xtpl->assign("MAILMERGE_MODULE", $_SESSION['MAILMERGE_MODULE']);
$xtpl->assign("MAILMERGE_PREV", get_image($image_path.'previous','border="0" style="margin-left: 1px;" alt="Previous" id="prevItems" onClick="decreaseOffset();getObjects();"'));
$xtpl->assign("MAILMERGE_NEXT", get_image($image_path.'next','border="0" style="margin-left: 1px;" alt="Next" id="nextItems" onClick="increaseOffset();getObjects();"'));
$xtpl->assign("MAILMERGE_RIGHT_TO_LEFT", get_image($image_path.'leftarrow_big','border="0" style="margin-left: 1px;" alt="Remove Item(s)" onClick="moveLeft();"'));
$xtpl->assign("MAILMERGE_LEFT_TO_RIGHT", get_image($image_path.'rightarrow_big','border="0" style="margin-left: 1px;" alt="Add Item(s)" onClick="moveRight();"'));
$xtpl->assign("MAIL_MERGE_HEADER_STEP_2", $step_txt);


if(!empty($_POST['document_id']))
{
	$_SESSION['MAILMERGE_DOCUMENT_ID'] = $_POST['document_id'];
}


$xtpl->parse("main");
$xtpl->out("main");

function displaySelectionBox($objectList)
{
	$html = '<select id="display_objs" name="display_objs[]" size="10" multiple="multiple" size="10" >';
	foreach($objectList as $key=>$value)
	{
		$html .= '<option value="'.$key.'">'.$value.'</option>';
	}
	$html .= '</select>';
	return $html;
}

?>
