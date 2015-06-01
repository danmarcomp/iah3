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

 * Description:  base form for account
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/
 
class AccountFormBase{
	
	// longreach - start added
	var $savingPrimaryContact = false;
	var $contactPrefix = '_contact_';
	// longreach - end added
	
function checkForDuplicates($prefix){
	require_once('include/formbase.php');
	require_once('modules/Accounts/Account.php');
	$focus = new Account();
	$query = '';
	$baseQuery = 'select id, name, website, billing_address_city  from accounts where deleted!=1 and ';
	if(!empty($_POST[$prefix.'name'])){	
		$query = $baseQuery ."  name like '".$_POST[$prefix.'name']."%'";
	}
	
	if(!empty($_POST[$prefix.'billing_address_city']) || !empty($_POST[$prefix.'shipping_address_city'])){	
		
		$temp_query = '';
		if(!empty($_POST[$prefix.'billing_address_city'])){	
			if(empty($temp_query)){
				$temp_query =  "  billing_address_city like '".$_POST[$prefix.'billing_address_city']."%'";
			}else {
				$temp_query .= "or billing_address_city like '".$_POST[$prefix.'billing_address_city']."%'";
			}
		}
		if(!empty($_POST[$prefix.'shipping_address_city'])){	
			if(empty($temp_query)){
				$temp_query = "  shipping_address_city like '".$_POST[$prefix.'shipping_address_city']."%'";
			}else {
				$temp_query .= "or shipping_address_city like '".$_POST[$prefix.'shipping_address_city']."%'";
			}
		}
		if(empty($query)){
			$query .= $baseQuery;	
		}else{
			$query .= ' AND ';	
		}
		$query .=   ' ('. $temp_query . ' ) ';
		
	}
		
	if(!empty($query)){
		$rows = array();
		global $db;
		$result = $db->query($query);
		$i=-1;
		while(($row=$db->fetchByAssoc($result)) != null) {
			$i++;
			$rows[$i] = $row;
		}
		if ($i==-1) return null;
		
		return $rows;		
	}
	return null;
}


function buildTableForm($rows, $mod='Accounts'){
	if(!ACLController::checkAccess('Accounts', 'edit', true)){
		return '';
	}
	global $odd_bg, $even_bg, $action;
	if(!empty($mod)){
	global $current_language;
	$mod_strings = return_module_language($current_language, $mod);
	}else global $mod_strings;
	global $app_strings;
	$cols = sizeof($rows[0]) * 2 + 1;
	if ($action != 'ShowDuplicates') 
	{
		$form = "<form action='index.php' method='post' id='dupAccounts'  name='dupAccounts'><input type='hidden' name='selectedAccount' value=''>";
		$form .= '<table width="100%"><tr><td>'.$mod_strings['MSG_DUPLICATE']. '</td></tr><tr><td height="20"></td></tr></table>';
		unset($_POST['selectedAccount']);
	}
	else 
	{
		$form = '<table width="100%"><tr><td>'.$mod_strings['MSG_SHOW_DUPLICATES']. '</td></tr><tr><td height="20"></td></tr></table>';
	}

	$form .=  get_form_header($mod_strings['LBL_DUPLICATE'], "", '');
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
									
					$form .= "<td class='listViewThS1'>". $mod_strings[$mod_strings['db_'.$key]]. "</td>";
		}}
	
		$form .= "</tr>";
	}
		
	$bgcolor = $odd_bg;
	$rowColor = 'oddListRowS1';
	foreach($rows as $row){
		
		$form .= "<tr class='$rowColor'  bgcolor='$bgcolor'>";	
		if ($action != 'ShowDuplicates') 
		{
		$form .= "<td width='1%' class='$rowColor' nowrap><a href='#'  class='listViewTdLinkS1' onclick='document.dupAccounts.selectedAccount.value=\"${row['id']}\"; document.dupAccounts.submit(); '>[${app_strings['LBL_SELECT_BUTTON_LABEL']}]</a>&nbsp;&nbsp;</td>\n";	
		}
		foreach ($row as $key=>$value){
				if($key != 'id'){
								
					$form .= "<td class='$rowColor'><a target='_blank' class='listViewTdLinkS1' href='index.php?module=Accounts&action=DetailView&record=${row['id']}'>$value</a></td>\n";
		
				}}
		
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
	
	// handle buttons
	if ($action == 'ShowDuplicates') {
		$return_action = 'ListView'; // cn: bug 6658 - hardcoded return action break popup -> create -> duplicate -> cancel
		$return_action = (isset($_REQUEST['return_action']) && !empty($_REQUEST['return_action'])) ? $_REQUEST['return_action'] : $return_action;
		$form .= "</table><br><input type='hidden' name='selectedAccount' id='selectedAccount' value=''><input title='${app_strings['LBL_SAVE_BUTTON_TITLE']}' accessKey='${app_strings['LBL_SAVE_BUTTON_KEY']}' class='button' onclick=\"this.form.action.value='Save';\" type='submit' name='button' value='  ${app_strings['LBL_SAVE_BUTTON_LABEL']}  '>";
		// longreach - added
		$form .= "<input title='${app_strings['LBL_EDIT_BUTTON_TITLE']}' accessKey='${app_strings['LBL_EDIT_BUTTON_KEY']}' class='button' onclick=\"this.form.action.value='EditView';\" type='submit' name='button' value='  ${app_strings['LBL_EDIT_BUTTON_LABEL']}  '>";
         
        if (!empty($_REQUEST['return_module']) && !empty($_REQUEST['return_action']) && !empty($_REQUEST['return_id']))
            $form .= "<input title='${app_strings['LBL_CANCEL_BUTTON_TITLE']}' accessKey='${app_strings['LBL_CANCEL_BUTTON_KEY']}' class='button' onclick=\"location.href='index.php?module=".$_REQUEST['return_module']."&action=".$_REQUEST['return_action']."&record=".$_REQUEST['return_id']."'\" type='button' name='button' value='  ${app_strings['LBL_CANCEL_BUTTON_LABEL']}  '></form>";
        else                
            $form .= "<input title='${app_strings['LBL_CANCEL_BUTTON_TITLE']}' accessKey='${app_strings['LBL_CANCEL_BUTTON_KEY']}' class='button' onclick=\"location.href='index.php?module=Accounts&action=ListView'\" type='button' name='button' value='  ${app_strings['LBL_CANCEL_BUTTON_LABEL']}  '></form>";
	} else {
		$form .= "</table><BR><input type='submit' class='button' name='ContinueAccount' value='${mod_strings['LNK_NEW_ACCOUNT']}'></form>\n";
	}
	return $form;
	
	
		
}

function getForm($prefix, $mod='', $form=''){
	if(!ACLController::checkAccess('Accounts', 'edit', true)){
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
		<form name="${prefix}AccountSave" onSubmit="return check_form('${prefix}AccountSave');" method="POST" action="index.php">
			<input type="hidden" name="${prefix}module" value="Accounts">
			<input type="hidden" name="${prefix}action" value="Save">
EOQ;
// longreach - start added
if (!empty($GLOBALS['B2C']))
$the_form .= $this->getB2CFormBody($prefix, $mod, $prefix."AccountSave");
else
// longreach - end added
$the_form .= $this->getFormBody($prefix, $mod, $prefix."AccountSave");
$the_form .= <<<EOQ
		<p><input title="$lbl_save_button_title" accessKey="$lbl_save_button_key" class="button" type="submit" name="button" value="  $lbl_save_button_label  " ></p>
		</form>

EOQ;
$the_form .= get_left_form_footer();
$the_form .= get_validate_record_js();

return $the_form;	
}


function getFormBody($prefix,$mod='', $formname=''){
	if(!ACLController::checkAccess('Accounts', 'edit', true)){
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
$lbl_account_name = $mod_strings['LBL_ACCOUNT_NAME'];
$lbl_phone = $mod_strings['LBL_PHONE'];
$lbl_website = $mod_strings['LBL_WEBSITE'];
$lbl_save_button_title = $app_strings['LBL_SAVE_BUTTON_TITLE'];
$lbl_save_button_key = $app_strings['LBL_SAVE_BUTTON_KEY'];
$lbl_save_button_label = $app_strings['LBL_SAVE_BUTTON_LABEL'];
$user_id = $current_user->id;

	$form = <<<EOQ
			<p><input type="hidden" name="record" value="">
			<input type="hidden" name="email1" value="">
			<input type="hidden" name="email2" value="">
			<input type="hidden" name="assigned_user_id" value='${user_id}'>
			<input type="hidden" name="action" value="Save">
		$lbl_account_name&nbsp;<span class="required">$lbl_required_symbol</span><br>
		<input name='name' type="text" value=""><br>
		$lbl_phone<br>
		<input name='phone_office' type="text" value=""><br>
		$lbl_website<br>
		<input name='website' type="text" value="http://"></p>
		
		 
EOQ;
require_once('include/javascript/javascript.php');
require_once('modules/Accounts/Account.php');
$javascript = new javascript();
$javascript->setFormName($formname);
$javascript->setSugarBean(new Account());
$javascript->addRequiredFields($prefix);
$form .=$javascript->getScript();
$mod_strings = $temp_strings;
return $form;
}



function getWideFormBody($prefix, $mod='',$formname='',  $contact=''){
	if(!ACLController::checkAccess('Accounts', 'edit', true)){
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
$account = new Account();

$lbl_required_symbol = $app_strings['LBL_REQUIRED_SYMBOL'];
$lbl_account_name = $mod_strings['LBL_ACCOUNT_NAME'];
$lbl_phone = $mod_strings['LBL_PHONE'];
$lbl_website = $mod_strings['LBL_WEBSITE'];
if (isset($contact->assigned_user_id)) {
	$user_id=$contact->assigned_user_id;
} else {
	$user_id = $current_user->id;
}







		$form="";




	$form .= <<<EOQ
		<input type="hidden" name="${prefix}record" value="">
		<input type="hidden" name="${prefix}phone_fax" value="{$contact->phone_fax}">
		<input type="hidden" name="${prefix}phone_other" value="{$contact->phone_other}">
		<input type="hidden" name="${prefix}email1" value="{$contact->email1}">
		<input type="hidden" name="${prefix}email2" value="{$contact->email2}">
		<input type='hidden' name='${prefix}billing_address_street' value='{$contact->primary_address_street}'><input type='hidden' name='${prefix}billing_address_city' value='{$contact->primary_address_city}'><input type='hidden' name='${prefix}billing_address_state'   value='{$contact->primary_address_state}'><input type='hidden' name='${prefix}billing_address_postalcode'   value='{$contact->primary_address_postalcode}'><input type='hidden' name='${prefix}billing_address_country'  value='{$contact->primary_address_country}'>
		<input type='hidden' name='${prefix}shipping_address_street' value='{$contact->primary_address_street}'><input type='hidden' name='${prefix}shipping_address_city' value='{$contact->primary_address_city}'><input type='hidden' name='${prefix}shipping_address_state'   value='{$contact->primary_address_state}'><input type='hidden' name='${prefix}shipping_address_postalcode'   value='{$contact->primary_address_postalcode}'><input type='hidden' name='${prefix}shipping_address_country'  value='{$contact->primary_address_country}'>
		<input type="hidden" name="${prefix}assigned_user_id" value='${user_id}'>
		<table width='100%' border="0" cellpadding="0" cellspacing="0">
		<tr>
		<td width="20%" nowrap class="dataLabel">$lbl_account_name&nbsp;<span class="required">$lbl_required_symbol</span></td>
		<TD width="80%" nowrap class="dataLabel">{$mod_strings['LBL_DESCRIPTION']}</TD>
		</tr>
		<tr>
		<td nowrap class="dataField"><input name='{$prefix}name' type="text" value="$contact->account_name"></td>
		<TD rowspan="5" class="dataField"><textarea name='{$prefix}description' rows='6' cols='50'></textarea></TD>
		</tr>
		<tr>
		<td nowrap class="dataLabel">$lbl_phone</td>
		</tr>
		<tr>
		<td nowrap class="dataField"><input name='{$prefix}phone_office' type="text" value="$contact->phone_work"></td>
		</tr>
		<tr>
		<td nowrap class="dataLabel">$lbl_website</td>
		</tr>
		<tr>
		<td nowrap class="dataField"><input name='{$prefix}website' type="text" value="http://"></td>
		</tr>
EOQ;
	//carry forward custom lead fields common to accounts during Lead Conversion
	$tempAccount = new Account();
	if (method_exists($contact, 'convertCustomFieldsForm')) $contact->convertCustomFieldsForm($form, $tempAccount, $prefix);
	unset($tempAccount);
$form .= <<<EOQ
		</TABLE>
EOQ;
	require_once('include/javascript/javascript.php');
require_once('modules/Accounts/Account.php');
$javascript = new javascript();
$javascript->setFormName($formname);
$javascript->setSugarBean(new Account());
$javascript->addRequiredFields($prefix);
$form .=$javascript->getScript();
$mod_strings = $temp_strings;
	return $form;
}


function handleSave($prefix,$redirect=true, $useRequired=false){
    
	// longreach - added
	if ($this->savingPrimaryContact) $this->fillContactInfoFromPost($this->contactPrefix, $prefix);

	require_once('modules/Accounts/Account.php');
    require_once ('include/utils.php');
	require_once('include/formbase.php');
	
	$focus = new Account();

	if($useRequired &&  !checkRequired($prefix, array_keys($focus->required_fields))){
		return null;
	}
	$focus = populateFromPost($prefix, $focus);
	if (isset($GLOBALS['check_notify'])) {
		$check_notify = $GLOBALS['check_notify'];
	}
	else {
		$check_notify = FALSE;
	}
	
	if (empty($_POST['record']) && empty($_POST['dup_checked'])) {
		$duplicateAccounts = $this->checkForDuplicates($prefix);
		if(isset($duplicateAccounts)){
			$get='module=Accounts&action=ShowDuplicates';
			
			//add all of the post fields to redirect get string
			foreach ($focus->column_fields as $field) 
			{
				if (!empty($focus->$field))
				{
					$get .= "&Accounts$field=".urlencode($focus->$field);
				}	
			}
			
			foreach ($focus->additional_column_fields as $field) 
			{
				if (!empty($focus->$field))
				{
					$get .= "&Accounts$field=".urlencode($focus->$field);
				}	
			}
			
			// longreach - start addded
			if (!empty($GLOBALS['B2C'])) {
				require_once 'modules/Contacts/Contact.php';
				$cn = new Contact;
				$cn = populateFromPost($this->contactPrefix, $cn);

				foreach ($cn->column_fields as $field) 
				{
					if (!empty($cn->$field))
					{
						$get .= "&Contacts$field=".urlencode($cn->$field);
					}	
				}
				
				foreach ($cn->additional_column_fields as $field) 
				{
					if (!empty($cn->$field))
					{
						$get .= "&Contacts$field=".urlencode($cn->$field);
					}	
				}
			}
			// longreach - end added


			//create list of suspected duplicate account id's in redirect get string
			$i=0;
			foreach ($duplicateAccounts as $account)
			{
				$get .= "&duplicate[$i]=".$account['id'];
				$i++;
			}

			//add return_module, return_action, and return_id to redirect get string
			$get .= '&return_module=';
			if(!empty($_POST['return_module'])) $get .= $_POST['return_module'];
			else $get .= 'Accounts';
			$get .= '&return_action=';
			if(!empty($_POST['return_action'])) $get .= $_POST['return_action'];
			else $get .= 'DetailView';
			if(!empty($_POST['return_id'])) $get .= '&return_id='.$_POST['return_id'];
			if(!empty($_POST['popup'])) $get .= '&popup='.$_POST['popup'];
			if(!empty($_POST['create'])) $get .= '&create='.$_POST['create'];
			if(!empty($_POST['to_pdf'])) $get .= '&to_pdf='.$_POST['to_pdf'];
			
			//echo $get;
			//die;
			//now redirect the post to modules/Accounts/ShowDuplicates.php
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
	if(!$focus->ACLAccess('Save')){
		ACLController::displayNoAccess(true);
		sugar_cleanup(true);
	}
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
		else $get .= 'Accounts';
		$get .= '&action=';
		if(!empty($_POST['return_action'])) $get .= $_POST['return_action'];
		else $get .= 'Popup';
		if(!empty($_POST['return_id'])) $get .= '&return_id='.$_POST['return_id'];
		if(!empty($_POST['popup'])) $get .= '&popup='.$_POST['popup'];
		if(!empty($_POST['create'])) $get .= '&create='.$_POST['create'];
		if(!empty($_POST['inline'])) $get .= '&inline='.$_POST['inline'];
		if(!empty($_POST['to_pdf'])) $get .= '&to_pdf='.$_POST['to_pdf'];
		$get .= '&name=' . $focus->name;
		$get .= '&query=true';
		header("Location: index.php?$get");
		return;
	}

	// longreach - start added
	if ($this->savingPrimaryContact && $focus->primary_contact_id) {
		$focus->load_relationship('contacts');
		$focus->contacts->add($focus->primary_contact_id);
	}
	// longreach - end added	
    
	if($redirect){
		handleRedirect($return_id,'Accounts');
	}else{
		return $focus;	
	}
}

// longreach - start added
function fillContactInfoFromPost($cprefix = '_contact_', $aprefix = '')
{
	require_once 'modules/Accounts/Account.php';
	$map = Account::getB2CContactFieldsMap();
	foreach ($map as $cf => $af) {
		if (isset($_POST[$cprefix . $cf])) {
			$_POST[$aprefix . $af] = $_REQUEST[$aprefix . $af] = $_POST[$cprefix . $cf];
		}
	}
}
 
function getB2CFormBody($prefix,$mod='', $formname='') 
{
	if(!ACLController::checkAccess('Accounts', 'edit', true)){
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
	$c_mod_strings = return_module_language($current_language, 'Contacts');

	$lbl_required_symbol = $app_strings['LBL_REQUIRED_SYMBOL'];
	$lbl_account_name = $mod_strings['LBL_ACCOUNT_NAME'];
	$lbl_phone = $mod_strings['LBL_PHONE'];
	$lbl_website = $mod_strings['LBL_WEBSITE'];

	$lbl_first_name = $c_mod_strings['LBL_FIRST_NAME'];
	$lbl_last_name = $c_mod_strings['LBL_LAST_NAME'];
	$lbl_phone = $c_mod_strings['LBL_PHONE'];
	$lbl_email_address = $c_mod_strings['LBL_EMAIL_ADDRESS'];
	$lbl_postal_code = $c_mod_strings['LBL_POSTAL_CODE'];

	$lbl_save_button_title = $app_strings['LBL_SAVE_BUTTON_TITLE'];
	$lbl_save_button_key = $app_strings['LBL_SAVE_BUTTON_KEY'];
	$lbl_save_button_label = $app_strings['LBL_SAVE_BUTTON_LABEL'];
	$user_id = $current_user->id;

	$form = <<<EOQ
<p>		$lbl_first_name<br>
		<input name="_contact_first_name" type="text" value=""><br>
		$lbl_last_name <span class="required">$lbl_required_symbol</span><br>
		<input name='_contact_last_name' type="text" value=""><br>
		$lbl_postal_code <br>
		<input name='_contact_primary_address_postalcode' type="text" value=""><br>
		$lbl_phone<br>
		<input name='_contact_phone_work' type="text" value=""><br>
		$lbl_email_address<br>
		<input name='_contact_email1' type="text" value=""></p>
		
		 
EOQ;
require_once('include/javascript/javascript.php');
require_once('modules/Accounts/Account.php');
$javascript = new javascript();
$javascript->setFormName($formname);
$javascript->setSugarBean(new Account());
$javascript->addRequiredFields('_contact_');
$form .=$javascript->getScript();
$mod_strings = $temp_strings;
return $form;
 }
// longreach - end added



}
?>
