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
class LeadFormBase  {

function checkForDuplicates($prefix, $id){
	require_once('include/formbase.php');
	require_once('modules/Leads/Lead.php');
	$focus = new Lead();
	$query = '';
	$baseQuery = "select id,first_name, last_name,account_name, title, email1, email2  from leads where deleted!=1 and id!='$id' and (status!='Converted' or status is NULL) and (";
	if(isset($_POST[$prefix.'first_name']) && !empty($_POST[$prefix.'first_name']) && isset($_POST[$prefix.'last_name']) && !empty($_POST[$prefix.'last_name'])){
		$query = $baseQuery ." (first_name='". $_POST[$prefix.'first_name'] . "' and last_name = '". $_POST[$prefix.'last_name'] ."')";
	}else{
			$query = $baseQuery ."  last_name = '". $_POST[$prefix.'last_name'] ."'";
	}
	if(isset($_POST[$prefix.'email1']) && !empty($_POST[$prefix.'email1'])){
		if(empty($query)){
		$query = $baseQuery. "  email1='". $_POST[$prefix.'email1'] . "' or email2 = '". $_POST[$prefix.'email1'] ."'";
		}else {
			$query .= "or email1='". $_POST[$prefix.'email1'] . "' or email2 = '". $_POST[$prefix.'email1'] ."'";
		}
	}
	if(isset($_POST[$prefix.'email2']) && !empty($_POST[$prefix.'email2'])){
		if(empty($query))	{
			$query = $baseQuery. "  email1='". $_POST[$prefix.'email2'] . "' or email2 = '". $_POST[$prefix.'email2'] ."'";
		}else{
			$query .= "or email1='". $_POST[$prefix.'email2'] . "' or email2 = '". $_POST[$prefix.'email2'] ."'";
		}

	}

	if(!empty($query)){
		$rows = array();
		global $db;
		$result = $db->query($query.") ");
		if($db->getRowCount($result) == 0){
			return null;
		}
		for($i = 0; $i < $db->getRowCount($result); $i++){
			$rows[$i] = $db->fetchByAssoc($result, $i);
		}
		return $rows;
	}
	return null;
}


function buildTableForm($rows, $mod=''){
	global $odd_bg, $even_bg;
	if(!empty($mod)){
	global $current_language;
	$mod_strings = return_module_language($current_language, $mod);
	}else global $mod_strings;
	global $app_strings;
	$cols = sizeof($rows[0]) * 2 + 1;
	$form = '<table width="100%"><tr><td>'.$mod_strings['MSG_DUPLICATE']. '</td></tr><tr><td height="20"></td></tr></table>';
	$form .= "<form action='index.php' method='post' name='dupLeads'><input type='hidden' name='selectedLead' value=''>";
	$form .= get_form_header($mod_strings['LBL_DUPLICATE'],"", '');
	// longreach - modified
	$form .= "<table width='100%' cellpadding='0' cellspacing='0'>	<tr class='listViewThS1'>";


	require_once('include/formbase.php');
	$form .= getPostToForm();

	if(isset($rows[0])){
		foreach ($rows[0] as $key=>$value){
			if($key != 'id'){


					$form .= "<td scope='col' class='listViewThS1'>". $mod_strings[$mod_strings['db_'.$key]]. "</td>";
			}
		}
		$form .= "</tr>";
	}
	$bgcolor = $odd_bg;
	$rowColor = 'oddListRowS1';
	foreach($rows as $row){


		$form .= "<tr class='$rowColor' bgcolor='$bgcolor'>";
		// longreach - removed
		// $form .= "<td width='1%' nowrap align='center' class='$rowColor'><input type='checkbox' name='selectedLeads[]' value='{$row['id']}'></td>";
		$wasSet = false;

		foreach ($row as $key=>$value){
				if($key != 'id'){

					if(!$wasSet){
						$form .= "<td class='$rowColor' scope='row'><a target='_blank' class='listViewTdLinkS1' href='index.php?module=Leads&action=DetailView&record=${row['id']}'>$value</a></td>";
						$wasSet = true;
					}else{
					$form .= "<td class='$rowColor' ><a target='_blank' class='listViewTdLinkS1' href='index.php?module=Leads&action=DetailView&record=${row['id']}'>$value</a></td>";
		}}
		}
		if($rowColor == 'evenListRowS1'){
			$rowColor = 'oddListRowS1';
			$bgcolor = $odd_bg;
		}else{
			 $rowColor = 'evenListRowS1';
			 $bgcolor = $even_bg;
		}
		$form .= "</tr>";
	}
		$form .= "<tr class='listViewThS1'><td colspan='$cols' class='listViewThS1'></td></tr>";
		// longreach - removed
		// $form .= "</table><br><input type='submit' class='button' name='ContinueLead' value='${app_strings['LBL_NEXT_BUTTON_LABEL']}'></form>";
	// longreach - start added
		$form .= "</table><br><input title='${app_strings['LBL_SAVE_BUTTON_TITLE']}' accessKey='${app_strings['LBL_SAVE_BUTTON_KEY']}' class='button' onclick=\"this.form.action.value='Save';\" type='submit' name='button' value='  ${app_strings['LBL_SAVE_BUTTON_LABEL']}  '>";
		$form .= "<input title='${app_strings['LBL_EDIT_BUTTON_TITLE']}' accessKey='${app_strings['LBL_EDIT_BUTTON_KEY']}' class='button' onclick=\"this.form.action.value='EditView';\" type='submit' name='button' value='  ${app_strings['LBL_EDIT_BUTTON_LABEL']}  '>";
        if (!empty($_REQUEST['return_module']) && !empty($_REQUEST['return_action']) && !empty($_REQUEST['return_id']))
            $form .= "<input title='${app_strings['LBL_CANCEL_BUTTON_TITLE']}' accessKey='${app_strings['LBL_CANCEL_BUTTON_KEY']}' class='button' onclick=\"location.href='index.php?module=".$_REQUEST['return_module']."&action=".$_REQUEST['return_action']."&record=".$_REQUEST['return_id']."'\" type='button' name='button' value='  ${app_strings['LBL_CANCEL_BUTTON_LABEL']}  '></form>";
        else                
            $form .= "<input title='${app_strings['LBL_CANCEL_BUTTON_TITLE']}' accessKey='${app_strings['LBL_CANCEL_BUTTON_KEY']}' class='button' onclick=\"location.href='index.php?module=Leads&action=ListView'\" type='button' name='button' value='  ${app_strings['LBL_CANCEL_BUTTON_LABEL']}  '></form>";
	// longreach - end added
	return $form;





}
function getWideFormBody($prefix, $mod='', $formname=''){
if(!ACLController::checkAccess('Leads', 'edit', true)){
		return '';
	}
global $mod_strings;
$temp_strings = $mod_strings;
if(!empty($mod)){
	global $current_language;
	$mod_strings = return_module_language($current_language, $mod);
}
		global $app_strings;
		global $current_user;
		$lbl_required_symbol = $app_strings['LBL_REQUIRED_SYMBOL'];
		$lbl_first_name = $mod_strings['LBL_FIRST_NAME'];
		$lbl_last_name = $mod_strings['LBL_LAST_NAME'];
		$lbl_phone = $mod_strings['LBL_OFFICE_PHONE'];
		$lbl_address =  $mod_strings['LBL_PRIMARY_ADDRESS'];
		$user_id = $current_user->id;
		$lbl_email_address = $mod_strings['LBL_EMAIL_ADDRESS'];
		$form = <<<EOQ
		<input type="hidden" name="${prefix}record" value="">
		<input type="hidden" name="${prefix}status" value="New">
		<input type="hidden" name="${prefix}assigned_user_id" value='${user_id}'>
		<table class='evenListRow' border='0' width='100%'><tr><td nowrap cospan='1'>$lbl_first_name<br><input name="${prefix}first_name" type="text" value=""></td><td colspan='1'><FONT class="required">$lbl_required_symbol</FONT>&nbsp;$lbl_last_name<br><input name='${prefix}last_name' type="text" value=""></td></tr>
		<tr><td colspan='4'><hr></td></tr>
		<tr><td nowrap colspan='1'>${mod_strings['LBL_TITLE']}<br><input name='${prefix}title' type="text" value=""></td><td nowrap colspan='1'>${mod_strings['LBL_DEPARTMENT']}<br><input name='${prefix}department' type="text" value=""></td></tr>
		<tr><td colspan='4'><hr></td></tr>
		<tr><td nowrap colspan='4'>$lbl_address<br><input type='text' name='${prefix}primary_address_street' size='80'></td></tr>
		<tr><td> ${mod_strings['LBL_CITY']}<BR><input name='${prefix}primary_address_city'  maxlength='100' value=''></td><td>${mod_strings['LBL_STATE']}<BR><input name='${prefix}primary_address_state'  maxlength='100' value=''></td><td>${mod_strings['LBL_POSTAL_CODE']}<BR><input name='${prefix}primary_address_postalcode'  maxlength='100' value=''></td><td>${mod_strings['LBL_COUNTRY']}<BR><input name='${prefix}primary_address_country'  maxlength='100' value=''></td></tr>
		<tr><td colspan='4'><hr></td></tr>
		<tr><td nowrap >$lbl_phone<br><input name='${prefix}phone_work' type="text" value=""></td><td nowrap >${mod_strings['LBL_MOBILE_PHONE']}<br><input name='${prefix}phone_mobile' type="text" value=""></td><td nowrap >${mod_strings['LBL_FAX_PHONE']}<br><input name='${prefix}phone_fax' type="text" value=""></td><td nowrap >${mod_strings['LBL_HOME_PHONE']}<br><input name='${prefix}phone_home' type="text" value=""></td></tr>
		<tr><td colspan='4'><hr></td></tr>
		<tr><td nowrap colspan='1'>$lbl_email_address<br><input name='${prefix}email1' type="text" value=""></td><td nowrap colspan='1'>${mod_strings['LBL_OTHER_EMAIL_ADDRESS']}<br><input name='${prefix}email2' type="text" value=""></td></tr>
		<tr><td nowrap colspan='4'>${mod_strings['LBL_DESCRIPTION']}<br><textarea cols='80' rows='4' name='${prefix}description' ></textarea></td></tr></table>

EOQ;
require_once('include/javascript/javascript.php');
require_once('modules/Leads/Lead.php');
$javascript = new javascript();
$javascript->setFormName($formname);
$javascript->setSugarBean(new Lead());
$javascript->addField('email1','false',$prefix);
$javascript->addField('email2','false',$prefix);
$javascript->addRequiredFields($prefix);
$form .=$javascript->getScript();
$mod_strings = $temp_strings;
return $form;
}

function getFormBody($prefix, $mod='', $formname=''){
	if(!ACLController::checkAccess('Leads', 'edit', true)){
		return '';
	}
global $mod_strings;
$temp_strings = $mod_strings;
if(!empty($mod)){
	global $current_language;
	$mod_strings = return_module_language($current_language, $mod);
}
		global $app_strings;
		global $current_user;
		$lbl_required_symbol = $app_strings['LBL_REQUIRED_SYMBOL'];
		$lbl_first_name = $mod_strings['LBL_FIRST_NAME'];
		$lbl_last_name = $mod_strings['LBL_LAST_NAME'];
		$lbl_phone = $mod_strings['LBL_PHONE'];
		$user_id = $current_user->id;
		$lbl_email_address = $mod_strings['LBL_EMAIL_ADDRESS'];
		$form = <<<EOQ
		<input type="hidden" name="${prefix}record" value="">
		<input type="hidden" name="${prefix}email2" value="">
		<input type="hidden" name="${prefix}status" value="New">
		<input type="hidden" name="${prefix}assigned_user_id" value='${user_id}'>
<p>		$lbl_first_name<br>
		<input name="${prefix}first_name" type="text" value=""><br>
		$lbl_last_name <span class="required">$lbl_required_symbol</span><br>
		<input name='${prefix}last_name' type="text" value=""><br>
		$lbl_phone<br>
		<input name='${prefix}phone_work' type="text" value=""><br>
		$lbl_email_address<br>
		<input name='${prefix}email1' type="text" value=""></p>

EOQ;
require_once('include/javascript/javascript.php');
require_once('modules/Leads/Lead.php');
$javascript = new javascript();
$javascript->setFormName($formname);
$javascript->setSugarBean(new Lead());
$javascript->addField('email1','false',$prefix);
$javascript->addField('email2','false',$prefix);
$javascript->addRequiredFields($prefix);
$form .=$javascript->getScript();
$mod_strings = $temp_strings;
return $form;

}
function getForm($prefix, $mod='Leads'){
	if(!ACLController::checkAccess('Leads', 'edit', true)){
		return '';
	}
if(!empty($mod)){
	global $current_language;
	$mod_strings = return_module_language($current_language, $mod);
}else global $mod_strings;
global $app_strings;

$lbl_save_button_title = $app_strings['LBL_SAVE_BUTTON_TITLE'];
$lbl_save_button_key = $app_strings['LBL_SAVE_BUTTON_KEY'];
$lbl_save_button_label = $app_strings['LBL_SAVE_BUTTON_LABEL'];


$the_form = get_left_form_header($mod_strings['LBL_NEW_FORM_TITLE']);
$the_form .= <<<EOQ

		<form name="${prefix}LeadSave" onSubmit="return check_form('${prefix}LeadSave')" method="POST" action="index.php">
			<input type="hidden" name="${prefix}module" value="Leads">
			<input type="hidden" name="${prefix}action" value="Save">
EOQ;
$the_form .= $this->getFormBody($prefix, $mod, "${prefix}LeadSave");
$the_form .= <<<EOQ
		<p><input title="$lbl_save_button_title" accessKey="$lbl_save_button_key" class="button" type="submit" name="${prefix}button" value="  $lbl_save_button_label  " ></p>
		</form>

EOQ;
$the_form .= get_left_form_footer();
$the_form .= get_validate_record_js();

return $the_form;


}


function handleSave($prefix,$redirect=true, $useRequired=false){
	require_once('modules/Leads/Lead.php');
	
	require_once('include/formbase.php');

	

	$focus = new Lead();

	if($useRequired &&  !checkRequired($prefix, array_keys($focus->required_fields))){
		return null;
	}
	$focus = populateFromPost($prefix, $focus);
	
	
	// longreach - start added - check duplicates
	if (empty($_POST['record']) && empty($_POST['dup_checked'])) {
		$duplicateLeads = $this->checkForDuplicates($prefix, '');
		if(isset($duplicateLeads)){
			$get='module=Leads&action=ShowDuplicates';
			
			//add all of the post fields to redirect get string
			foreach ($focus->column_fields as $field) 
			{
				if (!empty($focus->$field))
				{
					$get .= "&Leads$field=".urlencode($focus->$field);
				}	
			}
			
			foreach ($focus->additional_column_fields as $field) 
			{
				if (!empty($focus->$field))
				{
					$get .= "&Leads$field=".urlencode($focus->$field);
				}	
			}

			//create list of suspected duplicate leads id's in redirect get string
			$i=0;
			foreach ($duplicateLeads as $lead)
			{
				$get .= "&duplicate[$i]=".$lead['id'];
				$i++;
			}

			//add return_module, return_action, and return_id to redirect get string
			$get .= '&return_module=';
			if(!empty($_POST['return_module'])) $get .= $_POST['return_module'];
			else $get .= 'Leads';
			$get .= '&return_action=';
			if(!empty($_POST['return_action'])) $get .= $_POST['return_action'];
			else $get .= 'DetailView';
			if(!empty($_POST['return_id'])) $get .= '&return_id='.$_POST['return_id'];
			if(!empty($_POST['popup'])) $get .= '&popup='.$_POST['popup'];
			if(!empty($_POST['create'])) $get .= '&create='.$_POST['create'];
			if(!empty($_POST['to_pdf'])) $get .= '&to_pdf='.$_POST['to_pdf'];
			
			//now redirect the post to modules/Leads/ShowDuplicates.php
            if (!empty($_POST['is_ajax_call']) && $_POST['is_ajax_call'] == '1')
            {
                $json = getJSONobj();
                echo $json->encode(array('status' => 'dupe', 
                                         'get' => $get));           
            }
            else {
                if(!empty($_POST['to_pdf'])) $get .= '&to_pdf='.$_POST['to_pdf'];
                header("Location: index.php?$get");
            }
			return null;
		}
	}
	// longreach - end added
	
	
	if(!$focus->ACLAccess('Save')){
		ACLController::displayNoAccess(true);
		sugar_cleanup(true);
	}

	if(!empty($GLOBALS['check_notify'])) {
		$focus->save($GLOBALS['check_notify']);
	}
	else {
		$focus->save(FALSE);
	}
	$return_id = $focus->id;
	if (isset($_POST[$prefix.'prospect_id']) &&  !empty($_POST[$prefix.'prospect_id'])) {
		if (!class_exists('Prospect')) {
			require_once('modules/Prospects/Prospect.php');
		}
		$prospect=new Prospect();
		$prospect->retrieve($_POST[$prefix.'prospect_id']);
		$prospect->lead_id=$focus->id;
		$prospect->save();
		
		$linked_beans= $prospect->get_linked_beans('campaigns','CampaignLog');
		if (empty($linked_beans)) $linked_beans=array(); 
		foreach ($linked_beans as $thebean) {
			
			$thebean->id=null;
			$thebean->target_id=$focus->id;
			$thebean->target_type='Leads';
			$thebean->archived=1;
			$thebean->save();
		}
	}

	///////////////////////////////////////////////////////////////////////////////
	////	INBOUND EMAIL HANDLING
	///////////////////////////////////////////////////////////////////////////////
	if(isset($_REQUEST['inbound_email_id']) && !empty($_REQUEST['inbound_email_id'])) {
		if(!isset($current_user)) {
			global $current_user;
		} 
			
		// fake this case like it's already saved.
		require_once('modules/Emails/Email.php');
		$email = new Email();
		$email->retrieve($_REQUEST['inbound_email_id']);
		$email->parent_type = 'Leads';
		// longreach - added
		$email->parent_name = $focus->first_name . ' ' . $focus->last_name;
		$email->parent_id = $focus->id;
		$email->assigned_user_id = $current_user->id;
		// longreach - modified
		$email->isread = 'read';
		$email->save();
		$email->load_relationship('leads');
		$email->leads->add($focus->id);
		
		header("Location: index.php?&module=Emails&action=EditView&type=out&inbound_email_id=".$_REQUEST['inbound_email_id']."&parent_id=".$email->parent_id."&parent_type=".$email->parent_type.'&parent_name='.urlencode($email->parent_name));
		exit();
	}
	////	END INBOUND EMAIL HANDLING
	///////////////////////////////////////////////////////////////////////////////	
	
	$GLOBALS['log']->debug("Saved record with id of ".$return_id);
	if($redirect){
		handleRedirect($return_id, 'Leads');
	}else{
		return $focus;
	}
}



}


?>
