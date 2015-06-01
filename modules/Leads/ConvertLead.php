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
// longreach - added - check access rights
if (ACLController::moduleSupportsACL('Contacts')  && !ACLController::checkAccess('Contacts', 'edit', true)) {
	ACLController::displayNoAccess(true);
	sugar_cleanup(true);
}

global $app_strings;
global $app_list_strings;

require_once('XTemplate/xtpl.php');
require_once('modules/Leads/Lead.php');
require_once('modules/Opportunities/Opportunity.php');
require_once('modules/Campaigns/utils.php');

global $theme;
$error_msg = '';
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";
require_once($theme_path.'layout_utils.php');
global $current_language;
$mod_strings = return_module_language($current_language, 'Leads');
echo "\n<p>\n";
echo get_module_title($mod_strings['LBL_MODULE_NAME'], $mod_strings['LBL_MODULE_NAME'].": ".$mod_strings['LBL_CONVERTLEAD'], true);
echo "\n</p>\n";
$xtpl=new XTemplate ('modules/Leads/ConvertLead.html');
$xtpl->assign("MOD", $mod_strings);
$xtpl->assign("APP", $app_strings);
$xtpl->assign("IMAGE_PATH", $image_path);$xtpl->assign("PRINT_URL", "index.php?".$GLOBALS['request_string']);

$xtpl->assign("HEADER", $mod_strings['LBL_ADD_BUSINESSCARD']);

$xtpl->assign("MODULE", $_REQUEST['module']);
if ($error_msg != '')
{
	$xtpl->assign("ERROR", $error_msg);
	$xtpl->parse("main.error");
}

if(isset($_POST['handle']) && $_POST['handle'] == 'Save'){
	require_once('modules/Contacts/Contact.php');
	require_once('modules/Contacts/ContactFormBase.php');
	$contactForm = new ContactFormBase();
	require_once('modules/Accounts/AccountFormBase.php');
	$accountForm = new AccountFormBase();
	require_once('modules/Opportunities/OpportunityFormBase.php');
	$oppForm = new OpportunityFormBase();
	require_once('modules/Leads/LeadFormBase.php');
	$leadForm = new LeadFormBase();
	$lead = new Lead();
	$lead->retrieve($_REQUEST['record']);

	$linked_beans[] = $lead->get_linked_beans('calls','Call');
    $linked_beans[] = $lead->get_linked_beans('meetings','Meeting');
    $linked_beans[] = $lead->get_linked_beans('emails','Email');
	$GLOBALS['check_notify'] = FALSE;
	
	if(!isset($_POST['selectedContact']) && !isset($_POST['ContinueContact'])){
		$duplicateContacts = $contactForm->checkForDuplicates('Contacts');
		if(isset($duplicateContacts)){
			$xtpl->assign('FORMBODY', $contactForm->buildTableForm($duplicateContacts,  'Contacts'));
			$xtpl->parse('main.form');
			$xtpl->parse('main');
			$xtpl->out('main');
			return;
		}
	}

	if(isset($_POST['newaccount']) && $_POST['newaccount']=='on' && empty($_POST['selectedAccount']) && empty($_POST['ContinueAccount'])){

		$duplicateAccounts = $accountForm->checkForDuplicates('Accounts');
		if(isset($duplicateAccounts)){
			$xtpl->assign('FORMBODY', $accountForm->buildTableForm($duplicateAccounts));
			$xtpl->parse('main.form');
			$xtpl->parse('main');
			$xtpl->out('main');
			return;
		}
	}

	if(isset($_POST['newopportunity']) && $_POST['newopportunity']=='on' &&!isset($_POST['selectedOpportunity']) && !isset($_POST['ContinueOpportunity'])){

		$duplicateOpps = $oppForm->checkForDuplicates('Opportunities');
		if(isset($duplicateOpps)){
			$xtpl->assign('FORMBODY', $oppForm->buildTableForm($duplicateOpps));
			$xtpl->parse('main.form');
			$xtpl->parse('main');
			$xtpl->out('main');
			return;
		}
	}

	/* longreach - removed
	if(!isset($_POST['selectedLeads']) && !isset($_POST['ContinueLead'])){
		$duplicateLeads = $leadForm->checkForDuplicates('Contacts', $_REQUEST['record']);
		if(isset($duplicateLeads)){
			$xtpl->assign('FORMBODY', $leadForm->buildTableForm($duplicateLeads, 'Leads'));
			$xtpl->parse('main.form');
			$xtpl->parse('main');
			$xtpl->out('main');
			return;
		}
	}
	 */
	if(isset($_POST['selectedContact']) && !empty($_POST['selectedContact'])){
		$contact = new Contact();
		$contact->retrieve($_POST['selectedContact']);
	}else{


		$_POST['Contactscategory'] = $lead->category;
		for ($i = 2; $i <= 10; $i++) {
			$_POST['Contactscategory'.$i] = $lead->{'category' . $i};
		}
		$_POST['Contactsportal_name'] = $lead->portal_name;
		$_POST['Contactsportal_active'] = $lead->portal_active;

		if(!empty($lead->campaign_id)) {
		 $_POST['Contactscampaign_id'] = $lead->campaign_id;
		}
		$contact= $contactForm->handleSave('Contacts',false, false);
		if(isset($lead->campaign_id) && $lead->campaign_id != null){
			CampaignActivityTracker::campaign_log_lead_entry($lead->campaign_id,$lead->id,$contact,'contact');
		}
	}
	// longreach - added B2C check
	if(AppConfig::is_B2C() || (isset($_POST['selectedAccount'])&& !empty($_POST['selectedAccount'])) || (isset($_POST['newaccount']) && $_POST['newaccount']=='on' )){
		if(isset($_POST['selectedAccount']) && !empty($_POST['selectedAccount'])){
			$account = new Account();
			$account->retrieve($_POST['selectedAccount']);
		}else{

			// longreach - start added
			if ($accountForm->savingPrimaryContact = AppConfig::is_B2C()) {
				$accountForm->contactPrefix = 'Contacts';
				$contact->_create_proper_name_field();
				$_POST['Accountsname'] = $_REQUEST['Accountsname'] = $contact->getB2CAccountName();
				$_POST['Accountsprimary_contact_id'] = $_REQUEST['Accountsprimary_contact_id'] = $contact->id;
				if (AppConfig::is_B2C()) $_POST['Accountsassigned_user_id'] = $_REQUEST['Accountsassigned_user_id'] = $contact->assigned_user_id;
			}
			$_POST['Accountspartner'] = $lead->partner;
			if ($lead->partner) {
				$_POST['Accountspartner_id'] = $lead->partner_id;
			}
			// longreach - end added


			$account= $accountForm->handleSave('Accounts',false, false);

		}
	}
	if(isset($_POST['newopportunity']) && $_POST['newopportunity']=='on' ){
		if( isset($_POST['selectedOpportunity']) && !empty($_POST['selectedOpportunity'])){
			$opportunity = new Opportunity();
			$opportunity->retrieve($_POST['selectedOpportunity']);
		}else{
			if(isset($account)){
				$_POST['Opportunitiesaccount_id'] = $account->id;
				$_POST['Opportunitiesaccount_name'] = $account->name;
			}



			$_POST['Opportunitieslead_source'] = $lead->lead_source;
			$_POST['Opportunitiescampaign_id'] = $lead->campaign_id;
			if($current_user->getPreference('currency') ){
				require_once('modules/Currencies/Currency.php');
				$currency = new Currency();
				$currency->retrieve($current_user->getPreference('currency'));
				$_POST['Opportunitiescurrency_id'] = $currency->id;
			}			
			// longreach - start added
			if (isset($app_list_strings['sales_probability_dom'][$_POST['Opportunitiessales_stage']])) {
				$_POST['Opportunitiesprobability'] = $app_list_strings['sales_probability_dom'][$_POST['Opportunitiessales_stage']];
			}
			if (isset($app_list_strings['sales_forecast_dom'][$_POST['Opportunitiessales_stage']])) {
				$_POST['Opportunitiesforecast_category'] = $app_list_strings['sales_forecast_dom'][$_POST['Opportunitiessales_stage']];
			}
			$_POST['Opportunitiespartner'] = $lead->partner;
			if ($lead->partner) {
				$_POST['Opportunitiespartner_id'] = $lead->partner_id;
			}
			// longreach - end added
			$opportunity= $oppForm->handleSave('Opportunities',false, false);
		}
	}
	require_once('modules/Notes/NoteFormBase.php');

	$noteForm = new NoteFormBase();

	if(isset($account)){



		$_POST['AccountNotesparent_id'] = $account->id;
		$accountnote= $noteForm->handleSave('AccountNotes',false, false);

		}
	if(isset($contact)){



		$contactnote= $noteForm->handleSave('ContactNotes',false, false);

		}
	if(isset($opportunity)){



		$opportunitynote= $noteForm->handleSave('OpportunityNotes',false, false);
		}
		



	if(isset($_POST['newmeeting']) && $_POST['newmeeting']=='on' ){
		// longreach - added
		if (AppConfig::is_B2C()) $_POST['Appointmentsparent_id'] = $account->id;
		if(isset($_POST['appointment']) && $_POST['appointment'] == 'Meeting'){
			require_once('modules/Meetings/MeetingFormBase.php');
			$meetingForm = new MeetingFormBase();


			$meeting= $meetingForm->handleSave('Appointments',false, false);
		}else{
			require_once('modules/Calls/CallFormBase.php');
			$callForm = new CallFormBase();
			$call= $callForm->handleSave('Appointments',false, false);
		}
	}

	if(isset($call)){
		if(isset($contact)) {
			$call->load_relationship('contacts');
			$call->contacts->add($contact->id);
		} else if(isset($account)){
			$call->load_relationship('account');
			$call->account->add($account->id);
		}else if(isset($opportunity)){
			$call->load_relationship('opportunity');
			$call->opportunity->add($opportunity->id);			
		}
	}
	if(isset($meeting)){
		if(isset($contact)) {
			$meeting->load_relationship('contacts');
			$meeting->contacts->add($contact->id);
		} else if(isset($account)){
			$meeting->load_relationship('account');
			$meeting->account->add($account->id);
		}else if(isset($opportunity)){
			$meeting->load_relationship('opportunity');
			$meeting->opportunity->add($opportunity->id);			
		}
	}

	if(isset($account)){
		if(isset($contact)) {
			$account->load_relationship('contacts');
			$account->contacts->add($contact->id);
		}
		if(isset($accountnote)){
			$account->load_relationship('notes');
			$account->notes->add($accountnote->id);
		}
	}
	if(isset($opportunity)){
		if(isset($contact)) {
			$opportunity->load_relationship('contacts');
			$opportunity->contacts->add($contact->id);
		} 
		if(isset($opportunitynote)){
			$opportunity->load_relationship('notes');
			$opportunity->notes->add($opportunitynote->id);
		}		
	}
	if(isset($contact)){
		if(isset($contactnote)){
			$contact->load_relationship('notes');
			$contact->notes->add($contactnote->id);
		}				
	}
	
	// longreach - modified - added B2C check
	if(isset($contact) && ! AppConfig::is_B2C()){
		if(isset($_POST['selectedContact']) && $_POST['selectedContact'] == $contact->id){
			// longreach - added
			if (! AppConfig::is_B2C()) {
			$xtpl->assign('ROWVALUE', "<LI>".$mod_strings['LBL_EXISTING_CONTACT']." - <a href='index.php?action=DetailView&module=Contacts&record=".$contact->id."'>".$contact->first_name ." ".$contact->last_name."</a>" );
			$xtpl->parse('main.row');
			// longreach - added
			}
		}else{

			// longreach - added
			if (! AppConfig::is_B2C()) {
			$xtpl->assign('ROWVALUE', "<LI>".$mod_strings['LBL_CREATED_CONTACT']." - <a href='index.php?action=DetailView&module=Contacts&record=".$contact->id."'>".$contact->first_name ." ".$contact->last_name."</a>" );
			$xtpl->parse('main.row');
			// longreach - added
			}
		}
	}
	if(isset($account)){
		
		if(isset($_POST['selectedAccount']) && $_POST['selectedAccount'] == $account->id){
			$xtpl->assign('ROWVALUE', "<LI>".$mod_strings['LBL_EXISTING_ACCOUNT']. " - <a href='index.php?action=DetailView&module=Accounts&record=".$account->id."'>".$account->name."</a>");
			$xtpl->parse('main.row');
		}else{
			$xtpl->assign('ROWVALUE', "<LI>".$mod_strings['LBL_CREATED_ACCOUNT']. " - <a href='index.php?action=DetailView&module=Accounts&record=".$account->id."'>".$account->name."</a>");
			$xtpl->parse('main.row');
		}

	}
	if(isset($opportunity)){
		
		if(isset($_POST['selectedOpportunity']) && $_POST['selectedOpportunity'] == $opportunity->id){
			$xtpl->assign('ROWVALUE', "<LI>".$mod_strings['LBL_EXISTING_OPPORTUNITY']. " - <a href='index.php?action=DetailView&module=Opportunities&record=".$opportunity->id."'>".$opportunity->name."</a>");
			$xtpl->parse('main.row');
		}else{
			$xtpl->assign('ROWVALUE', "<LI>".$mod_strings['LBL_CREATED_OPPORTUNITY']. " - <a href='index.php?action=DetailView&module=Opportunities&record=".$opportunity->id."'>".$opportunity->name."</a>");
			$xtpl->parse('main.row');
		}

	}
	$accountid = 'NULL';
	$contactid = 'NULL';
	$opportunityid = 'NULL';
	if(isset($account)){
		$account->track_view($current_user->id, 'Accounts');
		$accountid = "'".$account->id."'";
		//clone_history($lead->db, $lead->id, $account->id ,'Accounts');
		// longreach - added
		clone_relationship($lead->db,array('document_relations'), 'relation_id', $lead->id, $account->id, array('relation_type' => 'Accounts'));
	}
	if(isset($contact)){
		$contact->track_view($current_user->id, 'Contacts');
		$contactid = "'".$contact->id."'";
		// longreach - removed
		//clone_history($lead->db, $lead->id, $contact->id, 'Contacts');
		clone_relationship($lead->db,array('emails_contacts', 'calls_contacts', 'meetings_contacts',), 'contact_id', $lead->id, $contact->id);
		// longreach - added
		clone_relationship($lead->db,array('document_relations'), 'relation_id', $lead->id, $contact->id, array('relation_type' => 'Contacts'));
		clone_notes($lead->db, $lead->id, $contact->id, 'Contacts');
	}
	if(isset($opportunity)){
		/*track entry for opportunities is created during save
		$opportunity->track_view($current_user->id, 'Opportunities');
		*/
		$opportunityid = "'".$opportunity->id."'";
		// longreach - removed
		//clone_history($lead->db, $lead->id, $opportunity->id ,'Opportunities');
		// longreach - added
		clone_relationship($lead->db,array('document_relations'), 'relation_id', $lead->id, $opportunity->id, array('relation_type' => 'Opportunities'));
	}

    if(isset($contact)) {
        //Set relationships to the new contact
        foreach($linked_beans as $linked_bean)
        {
            foreach($linked_bean as $bean_val)
            {
                $bean_val->load_relationship('contacts');
                $bean_val->contacts->add($contact->id);
            }

        }
    }

    if(isset($account)) {
        foreach($linked_beans as $linked_bean)
        {
            foreach($linked_bean as $bean_val)
            {
				$bean_val->parent_type = 'Accounts';
				$bean_val->parent_id = $account->id;
				$bean_val->save(false);
            }
        }
    }

	$lead = new Lead();
	$lead->retrieve($_REQUEST['record']);
	$lead->converted_lead( "'".$_REQUEST['record']."'", $contactid, $accountid, $opportunityid);
	if(isset($_POST['selectedLeads']) && sizeof($_POST['selectedLeads']) > 0){
		foreach($_POST['selectedLeads'] as $aLead){
			$lead->converted_lead( "'".$aLead."'", $contactid, $accountid, $opportunityid);
		}
	}
	if(isset($call)){
		$call->track_view($current_user->id, 'Calls');
		$xtpl->assign('ROWVALUE', "<LI>".$mod_strings['LBL_CREATED_CALL']. " - <a href='index.php?action=DetailView&module=Calls&record=".$call->id."'>".$call->name."</a>");
		$xtpl->parse('main.row');
		}
	if(isset($meeting)){
		$meeting->track_view($current_user->id, 'Meetings');
		$xtpl->assign('ROWVALUE', "<LI>".$mod_strings['LBL_CREATED_MEETING']. " - <a href='index.php?action=DetailView&module=Meetings&record=".$meeting->id."'>".$meeting->name."</a>");
		$xtpl->parse('main.row');
		}
		$xtpl->assign('ROWVALUE',"&nbsp;");
		$xtpl->parse('main.row');
		$xtpl->assign('ROWVALUE',"<a href='index.php?module=Leads&action=ListView'>{$mod_strings['LBL_BACKTOLEADS']}</a>");
	$xtpl->parse('main.row');
	$xtpl->parse('main');
	$xtpl->out('main');
}

else{

$lead = new Lead();
$lead->retrieve($_REQUEST['record']);
$xtpl->assign('RECORD', $_REQUEST['record']);
$xtpl->assign('TABLECLASS', 'tabForm');
//CONTACT
// longreach - added
if (! AppConfig::is_B2C()) {
$xtpl->assign('FORMHEADER',$mod_strings['LNK_NEW_CONTACT']);
// longreach - start added
} else {
$xtpl->assign('FORMHEADER',$mod_strings['LNK_NEW_ACCOUNT']);
}
// longreach - end added
$xtpl->assign('OPPNEEDSACCOUNT',$mod_strings['NTC_OPPORTUNITY_REQUIRES_ACCOUNT']);
if (AppConfig::setting('site.features.require_accounts')) {
	$xtpl->assign('CHECKOPPORTUNITY', "&& checkOpportunity()");
}
else {
	$xtpl->assign('CHECKOPPORTUNITY', "");
}
$xtpl->parse("main.startform");
require_once('modules/Contacts/ContactFormBase.php');
$contactForm = new ContactFormBase();
$xtpl->assign('FORMBODY',$contactForm->getWideFormBody('Contacts', 'Contacts','ConvertLead', $lead, false));
$xtpl->assign('FORMFOOTER',get_form_footer());
$xtpl->assign('CLASS', 'dataLabel');

// longreach - added condition - check access rights
$postform = '';
if (!ACLController::moduleSupportsACL('Notes')  || ACLController::checkAccess('Notes', 'edit', true)) {
require_once('modules/Notes/NoteFormBase.php');
$noteForm = new NoteFormBase();
$postform = "<div id='contactnotelink'><a href='javascript:toggleDisplay(\"contactnote\");'>${mod_strings['LNK_NEW_NOTE']}</a></div>";
$postform .= '<div id="contactnote" style="display:none">'.$noteForm->getFormBody('ContactNotes', 'Notes','ConvertLead', 80).'</div>';
}
$xtpl->assign('POSTFORM',$postform);
$xtpl->parse("main.form");


$xtpl->assign('HEADER', $app_strings['LBL_RELATED_RECORDS']);
$xtpl->parse("main.hrrow");
//Account

///////////////////////////////////////
///
/// SETUP PARENT POPUP

$popup_request_data = array(
	'call_back_function' => 'set_return',
	'form_name' => 'ConvertLead',
	'field_to_name_array' => array(
		'id' => 'selectedAccount',
		'name' => 'display_account_name',
		),
	);

$json = getJSONobj();
$encoded_popup_request_data = $json->encode($popup_request_data);

//
///////////////////////////////////////
// longreach - start added  - check access rights
$disabled = '';
if (ACLController::moduleSupportsACL('Accounts')  && !ACLController::checkAccess('Accounts', 'list', true)) {
    $disabled = ' disabled="disabled" ';
}
// longreach - end added

// longreach - added
if (! AppConfig::is_B2C()) {

$selectAccountButton = "<div id='newaccountdivlink' style='display:inline'><b>{$mod_strings['LNK_SELECT_ACCOUNT']}</b>&nbsp;<input readonly='readonly' name='display_account_name' id='display_account_name' type=\"text\" value=\"\"><input name='selectedAccount' id='selectedAccount' type=\"hidden\" value=''>&nbsp;<input type='button' title=\"{$app_strings['LBL_SELECT_BUTTON_TITLE']}\" accessKey=\"{$app_strings['LBL_SELECT_BUTTON_KEY']}\" type=\"button\"  class=\"button\" value='{$app_strings['LBL_SELECT_BUTTON_LABEL']}' name=btn1 $disabled onclick='open_popup(\"Accounts\", 600, 400, \"\", true, false, $encoded_popup_request_data);'> <input type='button' title=\"{$app_strings['LBL_CLEAR_BUTTON_TITLE']}\" accessKey=\"{$app_strings['LBL_CLEAR_BUTTON_KEY']}\" type=\"button\"  class=\"button\" value='{$app_strings['LBL_CLEAR_BUTTON_LABEL']}' name=btn1 LANGUAGE=javascript onclick='document.forms[\"ConvertLead\"].selectedAccount.value=\"\";document.forms[\"ConvertLead\"].display_account_name.value=\"\"; '><br><b>{$app_strings['LBL_OR']}</b></div>";
$xtpl->assign('FORMHEADER',get_form_header($mod_strings['LNK_NEW_ACCOUNT'], '', ''));
require_once('modules/Accounts/AccountFormBase.php');
$accountForm = new AccountFormBase();
$xtpl->assign('CLASS', 'evenListRow');

// longreach - added  - check access rights
if (!ACLController::moduleSupportsACL('Accounts')  || ACLController::checkAccess('Accounts', 'edit', true)) {
    $selectAccountButton .= "<h5 class='dataLabel'><label><input class='checkbox' type='checkbox' name='newaccount' onclick='document.forms[\"ConvertLead\"].selectedAccount.value=\"\";document.forms[\"ConvertLead\"].display_account_name.value=\"\";toggleDisplay(\"newaccountdiv\");'> ".$mod_strings['LNK_NEW_ACCOUNT']."</label></h5><div id='newaccountdiv' style='display:none'>".$accountForm->getWideFormBody('Accounts', 'Accounts','ConvertLead', $lead );
}

/* longreach - modified
$xtpl->assign('FORMBODY',$selectAccountButton."<h5 class='dataLabel'><label><input class='checkbox' type='checkbox' name='newaccount' onclick='document.forms[\"ConvertLead\"].selectedAccount.value=\"\";document.forms[\"ConvertLead\"].display_account_name.value=\"\";toggleDisplay(\"newaccountdiv\");'> ".$mod_strings['LNK_NEW_ACCOUNT']."</label></h5><div id='newaccountdiv' style='display:none'>".$accountForm->getWideFormBody('Accounts', 'Accounts','ConvertLead', $lead ));
 */
$xtpl->assign('FORMBODY',$selectAccountButton);

$xtpl->assign('FORMFOOTER',get_form_footer());

// longreach - added condition - check access rights
$postform = '';
if (!ACLController::moduleSupportsACL('Notes')  || ACLController::checkAccess('Notes', 'edit', true)) {
require_once('modules/Notes/NoteFormBase.php');
$noteForm = new NoteFormBase();
$postform = "<div id='accountnotelink'><a href='javascript:toggleDisplay(\"accountnote\");'>${mod_strings['LNK_NEW_NOTE']}</a></div>";
$postform .= '<div id="accountnote" style="display:none">'.$noteForm->getFormBody('AccountNotes', 'Notes','ConvertLead', 85).'</div><br>';
if(!empty($lead->account_name)){
	$postform.='<script>document.forms["ConvertLead"].newaccount.checked=true;toggleDisplay("newaccountdiv");</script>';
}
}
$xtpl->assign('POSTFORM',$postform);
$xtpl->parse("main.headlessform");

// longreach - added
}


//OPPORTUNITTY
// longreach - added condition - check access rights
if (!ACLController::moduleSupportsACL('Opportunities')  || ACLController::checkAccess('Opportunities', 'edit', true)) {
$xtpl->assign('FORMHEADER',get_form_header($mod_strings['LNK_NEW_OPPORTUNITY'], '', ''));
require_once('modules/Opportunities/OpportunityFormBase.php');
$oppForm = new OpportunityFormBase();
$xtpl->assign('CLASS', 'evenListRow');
$xtpl->assign('FORMBODY',"<h5 class='dataLabel'><label><input class='checkbox' type='checkbox' name='newopportunity' onclick='toggleDisplay(\"newoppdiv\");'> ".$mod_strings['LNK_NEW_OPPORTUNITY']."</label></h5><div id='newoppdiv' style='display:none'>".$oppForm->getWideFormBody('Opportunities', 'Opportunities','ConvertLead', $lead , false));
$xtpl->assign('FORMFOOTER',get_form_footer());
// longreach - added condition - check access rights
    $postform = '';
    if (!ACLController::moduleSupportsACL('Notes')  || ACLController::checkAccess('Notes', 'edit', true)) {
require_once('modules/Notes/NoteFormBase.php');
$noteForm = new NoteFormBase();
$postform = "<div id='oppnotelink'><a href='javascript:toggleDisplay(\"oppnote\");'>${mod_strings['LNK_NEW_NOTE']}</a></div>";
$postform .= '<div id="oppnote" style="display:none">'.$noteForm->getFormBody('OpportunityNotes', 'Notes','ConvertLead', 85).'</div><br>';
    }
$xtpl->assign('POSTFORM',$postform);
$xtpl->parse("main.headlessform");
}



// longreach - added condition - check access rights
if (ACLController::checkAccess('Calls', 'edit', true) || ACLController::checkAccess('Meetings', 'edit', true)) {
//Appointment
require_once('modules/Calls/CallFormBase.php');
$callForm = new CallFormBase();

$xtpl->assign('FORMBODY', "<h5 class='dataLabel'><label><input class='checkbox' type='checkbox' name='newmeeting' onclick='toggleDisplay(\"newmeetingdiv\");'> ".$mod_strings['LNK_NEW_APPOINTMENT']."</label></h5><div id='newmeetingdiv' style='display:none'>".$callForm->getWideFormBody('Appointments', 'Calls','ConvertLead')."</div><br>");
$xtpl->assign('FORMFOOTER', get_form_footer());
$xtpl->assign('POSTFORM','');
$xtpl->parse("main.headlessform");
}

$xtpl->parse("main.endform");

$xtpl->parse("main");

$xtpl->out("main");
}
?>
