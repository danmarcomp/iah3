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

 * Description:  Base form for contact
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/

class ContactFormBase {

function checkForDuplicates($prefix){
	global $local_log;
    require_once('include/formbase.php');
	require_once('modules/Contacts/Contact.php');
	$focus = new Contact();
	$query = '';
	$baseQuery = 'select id,first_name, last_name, title, email1, email2  from contacts where deleted!=1 and (';
	if(!empty($_POST[$prefix.'first_name']) && !empty($_POST[$prefix.'last_name'])){
		$query = $baseQuery ."  (first_name like '". $_POST[$prefix.'first_name'] . "%' and last_name = '". $_POST[$prefix.'last_name'] ."')";
	}else{
			$query = $baseQuery ."  last_name = '". $_POST[$prefix.'last_name'] ."'";
	}
	if(!empty($_POST[$prefix.'email1'])){
		if(empty($query)){
		$query = $baseQuery. "  email1='". $_POST[$prefix.'email1'] . "' or email2 = '". $_POST[$prefix.'email1'] ."'";
		}else {
			$query .= "or email1='". $_POST[$prefix.'email1'] . "' or email2 = '". $_POST[$prefix.'email1'] ."'";
		}
	}
	if(!empty($_POST[$prefix.'email2'])){
		if(empty($query))	{
			$query = $baseQuery. "  email1='". $_POST[$prefix.'email2'] . "' or email2 = '". $_POST[$prefix.'email2'] ."'";
		}else{
			$query .= "or email1='". $_POST[$prefix.'email2'] . "' or email2 = '". $_POST[$prefix.'email2'] ."'";
		}

	}

	if(!empty($query)){
		$rows = array();
		global $db;
		$result = $db->query($query.')');
		$i=-1;
		while (($row= $db->fetchByAssoc($result)) != null) {
			++$i;
			$rows[$i]=$row;
		}
		if ($i==-1) return null;
		else return $rows;
	}
	return null;
}

function buildTableForm($rows, $mod=''){
	global $odd_bg, $even_bg, $action;
	if(!empty($mod)){
	global $current_language;
	$mod_strings = return_module_language($current_language, $mod);
	}else global $mod_strings;
	global $app_strings;
	$cols = sizeof($rows[0]) * 2 + 1;
	if ($action != 'ShowDuplicates') 
	{
		$form = '<table width="100%"><tr><td>'.$mod_strings['MSG_DUPLICATE']. '</td></tr><tr><td height="20"></td></tr></table>';
		$form .= "<form action='index.php' method='post' name='dupContacts'>
					<input type='hidden' name='selectedContact' value=''>";
	}
	else 
	{
		$form = '<table width="100%"><tr><td>'.$mod_strings['MSG_SHOW_DUPLICATES']. '</td></tr><tr><td height="20"></td></tr></table>';
	}
	$form .= get_form_header($mod_strings['LBL_DUPLICATE'],"", '');
	$form .= "<table width='100%' cellpadding='0' cellspacing='0'>	<tr class='listViewThS1'>	";
	if ($action != 'ShowDuplicates') 
	{
		$form .= "<td class='listViewThS1'> &nbsp;</td>";
	}

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
		if ($action != 'ShowDuplicates') 
		{
			$form .= "<td width='1%' bgcolor='$bgcolor' nowrap ><a href='#' onClick=\"document.dupContacts.selectedContact.value='${row['id']}';document.dupContacts.submit() \">[${app_strings['LBL_SELECT_BUTTON_LABEL']}]</a>&nbsp;&nbsp;</td>\n";
		}
		$wasSet = false;

		foreach ($row as $key=>$value){
				if($key != 'id'){


					if(!$wasSet){
						$form .= "<td scope='row' class='$rowColor' bgcolor='$bgcolor'><a class='listViewTdLinkS1' target='_blank' href='index.php?module=Contacts&action=DetailView&record=${row['id']}'>$value</a></td>\n";
						$wasSet = true;
					}else{
											$form .= "<td class='$rowColor' bgcolor='$bgcolor'><a target='_blank' class='listViewTdLinkS1'  href='index.php?module=Contacts&action=DetailView&record=${row['id']}'>$value</a></td>\n";
					}

					}
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
	$form .= "<tr class='listViewThS1'><td colspan='$cols' class='blackline'></td></tr>";
	if ($action == 'ShowDuplicates') 
	{
		$form .= "</table><br> <input title='${app_strings['LBL_SAVE_BUTTON_TITLE']}' accessKey='${app_strings['LBL_SAVE_BUTTON_KEY']}' class='button' onclick=\"this.form.action.value='Save';\" type='submit' name='button' value='  ${app_strings['LBL_SAVE_BUTTON_LABEL']}  '> "; 
		// longreach - added
		$form .= " <input title='${app_strings['LBL_EDIT_BUTTON_TITLE']}' accessKey='${app_strings['LBL_EDIT_BUTTON_KEY']}' class='button' onclick=\"this.form.action.value='EditView';\" type='submit' name='button' value='  ${app_strings['LBL_EDIT_BUTTON_LABEL']}  '> ";
        if (!empty($_REQUEST['return_module']) && !empty($_REQUEST['return_action']) && !empty($_REQUEST['return_id']))
            $form .= "<input title='${app_strings['LBL_CANCEL_BUTTON_TITLE']}' accessKey='${app_strings['LBL_CANCEL_BUTTON_KEY']}' class='button' onclick=\"location.href='index.php?module=".$_REQUEST['return_module']."&action=". $_REQUEST['return_action']."&record=".$_REQUEST['return_id']."'\" type='button' name='button' value='  ${app_strings['LBL_CANCEL_BUTTON_LABEL']}  '></form>";
        else                
            $form .= "<input title='${app_strings['LBL_CANCEL_BUTTON_TITLE']}' accessKey='${app_strings['LBL_CANCEL_BUTTON_KEY']}' class='button' onclick=\"location.href='index.php?module=Contacts&action=ListView'\" type='button' name='button' value='  ${app_strings['LBL_CANCEL_BUTTON_LABEL']}  '></form>";
	}
	else 
	{
		$form .= "</table><br><input type='submit' class='button' name='ContinueContact' value='${mod_strings['LNK_NEW_CONTACT']}'></form>";
	}
	return $form;





}
function getWideFormBody($prefix, $mod='',$formname='',  $contact = '', $portal = true){
		if(!ACLController::checkAccess('Contacts', 'edit', true)){
		return '';
	}
	require_once('modules/Contacts/Contact.php');
	if(empty($contact)){
		$contact = new Contact();
	}
	
	global $mod_strings;
	$temp_strings = $mod_strings;
	if(!empty($mod)){
		global $current_language;
		$mod_strings = return_module_language($current_language, $mod);
	}
	global $app_strings;
	global $current_user;
	global $app_list_strings;
	$lbl_required_symbol = $app_strings['LBL_REQUIRED_SYMBOL'];
	$lbl_first_name = $mod_strings['LBL_FIRST_NAME'];
	$lbl_last_name = $mod_strings['LBL_LAST_NAME'];
	$lbl_phone = $mod_strings['LBL_OFFICE_PHONE'];
	$lbl_address =  $mod_strings['LBL_PRIMARY_ADDRESS'];
	
	if (isset($contact->assigned_user_id)) {
		$user_id=$contact->assigned_user_id;
	} else {
		$user_id = $current_user->id;
	}







		
	$lbl_email_address = $mod_strings['LBL_EMAIL_ADDRESS'];
	$salutation_options=get_select_options_with_id($app_list_strings['salutation_dom'], $contact->salutation);
	if (isset($contact->lead_source)) {
		$lead_source_options=get_select_options_with_id($app_list_strings['lead_source_dom'], $contact->lead_source);
	} else {
		$lead_source_options=get_select_options_with_id($app_list_strings['lead_source_dom'], '');
	}			
		
	$form="";



	if ($formname == 'ConvertProspect') {
		$lead_source_label = "<td class='dataLabel'>&nbsp;</td>";
		$lead_source_field = "<td class='dataField'>&nbsp;</td>";
	} else {
		$lead_source_label = "<td class='dataLabel' nowrap>${mod_strings['LBL_LEAD_SOURCE']}</td>";
		$lead_source_field = "<td class='dataField'><select name='${prefix}lead_source'>$lead_source_options</select></td>";
	}
		
	// longreach - rearranged fields layout		
	$form .= <<<EOQ
		<input type="hidden" name="${prefix}record" value="">
		<input type="hidden" name="${prefix}assigned_user_id" value='${user_id}'>
		<table border='0' celpadding="0" cellspacing="0" width='100%'>
		<tr>
		<td  nowrap class='dataLabel'>$lbl_first_name</td>
		<td  class='dataLabel'>$lbl_last_name&nbsp;<span class="required">$lbl_required_symbol</span></td>
		<td  nowrap class='dataLabel'>&nbsp;</td>
		<td  class='dataLabel'>&nbsp;</td>
		</tr>
		<tr>
		<td class='dataField'><select name='${prefix}salutation'>$salutation_options</select>&nbsp;<input name="${prefix}first_name" type="text" value="{$contact->first_name}"></td>
		<td class='dataField'><input name='${prefix}last_name' type="text" value="{$contact->last_name}"></td>
		<td class='dataField' nowrap>&nbsp;</td>
		<td class='dataField' nowrap>&nbsp;</td>
		</tr>

		<tr>
		<td class='dataLabel' nowrap>${mod_strings['LBL_TITLE']}</td>
		<td class='dataLabel' nowrap>${mod_strings['LBL_DEPARTMENT']}</td>
		<td class='dataLabel' nowrap>&nbsp;</td>
		<td class='dataLabel' nowrap>&nbsp;</td>
		</tr>
		<tr>
		<td class='dataField' nowrap><input name='${prefix}title' type="text" value="{$contact->title}"></td>
		<td class='dataField' nowrap><input name='${prefix}department' type="text" value="{$contact->department}"></td>
		<td class='dataField' nowra>&nbsp;</td>
		<td class='dataField' nowrap>&nbsp;</td>
		</tr>

		<tr>
		<td nowrap colspan='4' class='dataLabel'>$lbl_address</td>
		</tr>

		<tr>
		<td nowrap colspan='4' class='dataField'><textarea cols='80' rows='2' name='${prefix}primary_address_street'>{$contact->primary_address_street}</textarea></td>
		</tr>

		<tr>
		<td class='dataLabel'>${mod_strings['LBL_CITY']}</td>
		<td class='dataLabel'>${mod_strings['LBL_STATE']}</td>
		<td class='dataLabel'>${mod_strings['LBL_POSTAL_CODE']}</td>
		<td class='dataLabel'>${mod_strings['LBL_COUNTRY']}</td>
		</tr>

		<tr>
		<td class='dataField'><input name='${prefix}primary_address_city'  maxlength='100' value='{$contact->primary_address_city}'></td>
		<td class='dataField'><input name='${prefix}primary_address_state'  maxlength='100' value='{$contact->primary_address_state}'></td>
		<td class='dataField'><input name='${prefix}primary_address_postalcode'  maxlength='100' value='{$contact->primary_address_postalcode}'></td>
		<td class='dataField'><input name='${prefix}primary_address_country'  maxlength='100' value='$contact->primary_address_country'></td>
		</tr>


		<tr>
		<td nowrap class='dataLabel'>$lbl_phone</td>
		<td nowrap class='dataLabel'>${mod_strings['LBL_MOBILE_PHONE']}</td>
		<td nowrap class='dataLabel'>${mod_strings['LBL_FAX_PHONE']}</td>
		<td nowrap class='dataLabel'>${mod_strings['LBL_HOME_PHONE']}</td>
		</tr>

		<tr>
		<td nowrap class='dataField'><input name='${prefix}phone_work' type="text" value="{$contact->phone_work}"></td>
		<td nowrap class='dataField'><input name='${prefix}phone_mobile' type="text" value="{$contact->phone_mobile}"></td>
		<td nowrap class='dataField'><input name='${prefix}phone_fax' type="text" value="{$contact->phone_fax}"></td>
		<td nowrap class='dataField'><input name='${prefix}phone_home' type="text" value="{$contact->phone_home}"></td>
		</tr>

		<tr>
		<td class='dataLabel' nowrap>$lbl_email_address</td>
		<td class='dataLabel' nowrap>${mod_strings['LBL_OTHER_EMAIL_ADDRESS']}</td>
		<td class='dataLabel' nowrap>${mod_strings['LBL_OTHER_PHONE']}</td>
		$lead_source_label
		</tr>

		<tr>
		<td class='dataField' nowrap><input name='${prefix}email1' type="text" value="{$contact->email1}"></td>
		<td class='dataField' nowrap><input name='${prefix}email2' type="text" value="{$contact->email2}"></td>
		<td class='dataField' nowrap><input name='${prefix}phone_other' type="text" value="{$contact->phone_other}"></td>		
		$lead_source_field
		</tr>

		<tr>
		<td nowrap colspan='4' class='dataLabel'>${mod_strings['LBL_DESCRIPTION']}</td>
		</tr>
		<tr>
		<td nowrap colspan='4' class='dataField'><textarea cols='80' rows='4' name='${prefix}description' >{$contact->description}</textarea></td>
		</tr>
EOQ;

	//carry forward custom lead fields common to contacts during Lead Conversion
	$tempContact = new Contact();

	if (method_exists($contact, 'convertCustomFieldsForm')) $contact->convertCustomFieldsForm($form, $tempContact, $prefix);
	unset($tempContact);

$form .= <<<EOQ

		</table>
		<input type='hidden' name='${prefix}department'  value='{$contact->department}'>
		<input type='hidden' name='${prefix}phone_other'  value='{$contact->phone_other}'>
		<input type='hidden' name='${prefix}alt_address_street'  value='{$contact->alt_address_street}'>
		<input type='hidden' name='${prefix}alt_address_city' value='{$contact->alt_address_city}'><input type='hidden' name='${prefix}alt_address_state'   value='{$contact->alt_address_state}'><input type='hidden' name='${prefix}alt_address_postalcode'   value='{$contact->alt_address_postalcode}'><input type='hidden' name='${prefix}alt_address_country'  value='{$contact->alt_address_country}'>
		<input type='hidden' name='${prefix}do_not_call'  value='{$contact->do_not_call}'>
		<input type='hidden' name='${prefix}email_opt_out'  value='{$contact->email_opt_out}'>
EOQ;

	if ($portal == true){
		if (isset($contact->portal_name)) {
			$form.="<input type='hidden' name='${prefix}portal_name'  value='{$contact->portal_name}'>";
		} else {
			$form.="<input type='hidden' name='${prefix}portal_name'  value=''>";
		}
		if (isset($contact->portal_app)) {
			$form.="<input type='hidden' name='${prefix}portal_app'  value='{$contact->portal_app}'>";
		} else {
			$form.="<input type='hidden' name='${prefix}portal_app'  value=''>";
		}
			
	
		if(!empty($contact->portal_name) && !empty($contact->portal_app)){
			$form .= "<input name='${prefix}portal_active' type='hidden' size='25'  value='1' >";
		}
	    
	    if(isset($contact->portal_password)){   
	        $form.="<input type='password' name='${prefix}portal_password1'  value='{$contact->portal_password}'>";
	        $form.="<input type='password' name='${prefix}portal_password'  value='{$contact->portal_password}'>";
	        $form .= "<input name='${prefix}old_portal_password' type='hidden' size='25'  value='{$contact->portal_password}' >";
	    }else{
	        $form.="<input type='password' name='${prefix}portal_password1'  value=''>";
	        $form.="<input type='password' name='${prefix}portal_password'  value=''>";
	        $form .= "<input name='${prefix}old_portal_password' type='hidden' size='25'  value='' >";
	    }
	}
	require_once('include/javascript/javascript.php');
	require_once('modules/Contacts/Contact.php');
	$javascript = new javascript();
	$javascript->setFormName($formname);
	$javascript->setSugarBean(new Contact());
	$javascript->addField('email1','false',$prefix);
	$javascript->addField('email2','false',$prefix);
	$javascript->addRequiredFields($prefix);
	
	$form .=$javascript->getScript();
	$mod_strings = $temp_strings;
	return $form;
}

function getFormBody($prefix, $mod='', $formname=''){
	if(!ACLController::checkAccess('Contacts', 'edit', true)){
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
if ($formname == 'EmailEditView')
{
		$form = <<<EOQ
		<input type="hidden" name="${prefix}record" value="">
		<input type="hidden" name="${prefix}email2" value="">
		<input type="hidden" name="${prefix}phone_work" value="">
		<input type="hidden" name="${prefix}assigned_user_id" value='${user_id}'>
		$lbl_first_name<br>
		<input name="${prefix}first_name" type="text" value="" size=10><br>
		$lbl_last_name&nbsp;<span class="required">$lbl_required_symbol</span><br>
		<input name='${prefix}last_name' type="text" value="" size=10><br>
		$lbl_email_address&nbsp;<span class="required">$lbl_required_symbol</span><br>
		<input name='${prefix}email1' type="text" value=""><br><br>

EOQ;
}
else
{
		$form = <<<EOQ
		<input type="hidden" name="${prefix}record" value="">
		<input type="hidden" name="${prefix}email2" value="">
		<input type="hidden" name="${prefix}assigned_user_id" value='${user_id}'>
		$lbl_first_name<br>
		<input name="${prefix}first_name" type="text" value=""><br>
		$lbl_last_name&nbsp;<span class="required">$lbl_required_symbol</span><br>
		<input name='${prefix}last_name' type="text" value=""><BR>
		$lbl_phone<br>
		<input name='${prefix}phone_work' type="text" value=""><br>
		$lbl_email_address<br>
		<input name='${prefix}email1' type="text" value=""><br><br>

EOQ;
}
require_once('include/javascript/javascript.php');
require_once('modules/Contacts/Contact.php');
$javascript = new javascript();
$javascript->setFormName($formname);
$javascript->setSugarBean(new Contact());
$javascript->addField('email1','false',$prefix);
$javascript->addRequiredFields($prefix);

$form .=$javascript->getScript();
$mod_strings = $temp_strings;
return $form;

}
function getForm($prefix, $mod=''){
	if(!ACLController::checkAccess('Contacts', 'edit', true)){
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

		<form name="${prefix}ContactSave" onSubmit="return check_form('${prefix}ContactSave')" method="POST" action="index.php">
			<input type="hidden" name="${prefix}module" value="Contacts">
			<input type="hidden" name="${prefix}action" value="Save">
EOQ;
$the_form .= $this->getFormBody($prefix,'Contacts', "${prefix}ContactSave");
$the_form .= <<<EOQ
		<input title="$lbl_save_button_title" accessKey="$lbl_save_button_key" class="button" type="submit" name="${prefix}button" value="  $lbl_save_button_label  " >
		</form>

EOQ;
$the_form .= get_left_form_footer();
$the_form .= get_validate_record_js();

return $the_form;


}


function handleSave($prefix,$redirect=true, $useRequired=false){
   
	global $theme, $current_user;
	$theme_path="themes/".$theme."/";
	require_once('modules/Contacts/Contact.php');
	require_once($theme_path.'layout_utils.php');
    require_once ('include/utils.php');
	require_once('include/formbase.php');
	require_once('XTemplate/xtpl.php');
	global $timedate;
	
	$focus = new Contact();

	if($useRequired &&  !checkRequired($prefix, array_keys($focus->required_fields))){
		return null;
	}
	if (!empty($_POST[$prefix.'new_reports_to_id'])) {
		$focus->retrieve($_POST[$prefix.'new_reports_to_id']);
		$focus->reports_to_id = $_POST[$prefix.'record'];
		
	}

	else {
		$focus = populateFromPost($prefix, $focus);
        
        if(isset($focus->portal_password) && $focus->portal_password != $_POST[$prefix.'old_portal_password']){
            $focus->portal_password = md5($focus->portal_password);    
        }
	}
	if(!$focus->ACLAccess('Save')){
			ACLController::displayNoAccess(true);
			sugar_cleanup(true);
	}
	if($_REQUEST['action'] != 'BusinessCard' && $_REQUEST['action'] != 'ConvertLead' && $_REQUEST['action'] != 'ConvertProspect')
	{
		if (isset($_POST[$prefix.'sync_contact'])){
			 $focus->contacts_users_id = $current_user->id;
		}
		else{
			if (!isset($focus->users))
			{
	      	  	$focus->load_relationship('user_sync');
			}
	      	$focus->contacts_users_id = null;
			$focus->user_sync->delete($focus->id, $current_user->id);
		}
	}

	if (isset($GLOBALS['check_notify'])) {
		$check_notify = $GLOBALS['check_notify'];
	}
	else {
		$check_notify = FALSE;
	}

	if (empty($_POST['record']) && empty($_POST['dup_checked'])) {
		$duplicateContacts = $this->checkForDuplicates($prefix);
		if(isset($duplicateContacts)){
			$get='module=Contacts&action=ShowDuplicates';
			
			if(isset($_POST['inbound_email_id']) && !empty($_POST['inbound_email_id'])) {
				$get .= '&inbound_email_id='.$_POST['inbound_email_id'];
			}
			
			//add all of the post fields to redirect get string
			foreach ($focus->column_fields as $field) 
			{
				if (!empty($focus->$field))
				{
					$get .= "&Contacts$field=".urlencode($focus->$field);
				}	
			}
			
			foreach ($focus->additional_column_fields as $field) 
			{
				if (!empty($focus->$field))
				{
					$get .= "&Contacts$field=".urlencode($focus->$field);
				}	
			}

			//create list of suspected duplicate contact id's in redirect get string
			$i=0;
			foreach ($duplicateContacts as $contact)
			{
				$get .= "&duplicate[$i]=".$contact['id'];
				$i++;
			}

			//add return_module, return_action, and return_id to redirect get string
			$get .= "&return_module=";
			if(!empty($_POST['return_module'])) $get .= $_POST['return_module'];
			else $get .= "Contacts";
			$get .= "&return_action=";
			if(!empty($_POST['return_action'])) $get .= $_POST['return_action'];
			else $get .= "DetailView";
			if(!empty($_POST['return_id'])) $get .= "&return_id=".$_POST['return_id'];
			if(!empty($_POST['popup'])) $get .= '&popup='.$_POST['popup'];
			if(!empty($_POST['create'])) $get .= '&create='.$_POST['create'];
			if(!empty($_POST['to_pdf'])) $get .= '&to_pdf='.$_POST['to_pdf'];
			
			// for InboundEmail flow
			if(!empty($_POST['start'])) $get .= '&start='.$_POST['start'];
			//now redirect the post to modules/Contacts/ShowDuplicates.php
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
	global $current_user;
	if(is_admin($current_user)){
		if (!isset($_POST[$prefix.'portal_active'])) $focus->portal_active = '0';
		//if no password is set set account to inactive for portal
		if(empty($_POST[$prefix.'portal_name']))$focus->portal_active = '0';
		
	}

	///////////////////////////////////////////////////////////////////////////////
	////	INBOUND EMAIL HANDLING
	///////////////////////////////////////////////////////////////////////////////
	if(isset($_REQUEST['inbound_email_id']) && !empty($_REQUEST['inbound_email_id'])) {
		// fake this case like it's already saved.
		$focus->save($check_notify);
		require_once('modules/Emails/Email.php');
		$email = new Email();
		$email->retrieve($_REQUEST['inbound_email_id']);
		$email->parent_type = 'Contacts';
		$email->parent_id = $focus->id;
		// longreach - added
		$email->parent_name = $focus->first_name . ' ' . $focus->last_name;
		$email->assigned_user_id = $current_user->id;
		$email->status = 'read';
		$email->save();
		$email->load_relationship('contacts');
		$email->contacts->add($focus->id);
		
		header("Location: index.php?&module=Emails&action=EditView&type=out&reply=true&record=".$_REQUEST['inbound_email_id']."&parent_id=".$email->parent_id."&parent_type=".$email->parent_type.'&parent_name=' . urlencode($email->parent_name));
		exit();
	}
	////	END INBOUND EMAIL HANDLING
	///////////////////////////////////////////////////////////////////////////////	

	$focus->save($check_notify);
	$return_id = $focus->id;
	$GLOBALS['log']->debug("Saved record with id of ".$return_id);

    if (!empty($_POST['is_ajax_call']) && $_POST['is_ajax_call'] == '1') {
        $json = getJSONobj();
        echo $json->encode(array('status' => 'success', 
                                 'get' => ''));    
        return null;
    }
	
	if(isset($_POST['popup']) && $_POST['popup'] == 'true') {
		$get = '&module=';
		if(!empty($_POST['return_module'])) $get .= $_POST['return_module'];
		else $get .= 'Contacts';
		$get .= '&action=';
		if(!empty($_POST['return_action'])) $get .= $_POST['return_action'];
		else $get .= 'Popup';
		if(!empty($_POST['return_id'])) $get .= '&return_id='.$_POST['return_id'];
		if(!empty($_POST['popup'])) $get .= '&popup='.$_POST['popup'];
		if(!empty($_POST['create'])) $get .= '&create='.$_POST['create'];
		if(!empty($_POST['to_pdf'])) $get .= '&to_pdf='.$_POST['to_pdf'];
		if(!empty($_POST['inline'])) $get .= '&inline='.$_POST['inline'];
		$get .= '&first_name=' . $focus->first_name;
		$get .= '&last_name=' . $focus->last_name;
		$get .= '&query=true';
		header("Location: index.php?$get");
		return;
	}
	
	if($redirect){
		$this->handleRedirect($return_id);
	}else{
		return $focus;
	}
}

function handleRedirect($return_id){
  
	if(isset($_POST['return_module']) && $_POST['return_module'] != "") {
		$return_module = $_POST['return_module'];
	}
	else {
		$return_module = "Contacts";
	}
	
	if(isset($_POST['return_action']) && $_POST['return_action'] != "") {
		// if we create a new record "Save", we want to redirect to the DetailView
		if($_REQUEST['action'] == "Save" && $_REQUEST['return_module'] != "Home") {
			$return_action = 'DetailView';
		} else {
			// if we "Cancel", we go back to the list view.
			$return_action = $_REQUEST['return_action'];
		}
	}
	else {
		$return_action = "DetailView";
	}
	
	if(isset($_POST['return_id']) && $_POST['return_id'] != "") {
		$return_id = $_POST['return_id'];
	}
	header("Location: index.php?action=$return_action&module=$return_module&record=$return_id");
}

}


?>
