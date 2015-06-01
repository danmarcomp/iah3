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


require_once('modules/Accounts/PDFStatement.php');
require_once('modules/Accounts/Account.php');
require_once 'modules/Contacts/Contact.php';
require_once 'modules/Emails/Email.php';
require_once 'modules/EmailFolders/EmailFolder.php';
require_once 'modules/EmailTemplates/EmailTemplate.php';
require_once 'modules/EmailTemplates/TemplateParser.php';

/**
* This class encapsulates the emailing of statements to accounting contacts
*
*/
class CEmailStatement {
	// Class constants
    var $arAccountIds;						///< Contains a list of the account ids to email
    var $module_dir = 'Accounts';

	/**
	* Class constructor
	*/
	function CEmailStatement() {
        // Nothing to do
	}

    /**
	* sendEmail; Sends the email with attached statement to the given contact
	*
	* @access public
	* @return array
	*/
	function createEmailDrafts($strAccountId, $arContactIds, $arNoteIds, $strTemplateId) {
        // Initialize the email id array
        $arEmails = array();
		// Get access to the global variables
		global $current_user;
        // Create the template object
		$objTemplate = new EmailTemplate();
		// Retrieve the email template
        $objTemplate->retrieve($strTemplateId);
        // Loop through each of the contacts
        foreach ($arContactIds as $strContactId) {
        	// Create the contact
            $objContact = new Contact();
            // Retrieve the record
            $objContact->retrieve($strContactId);
            // Create the new email object
            $objEmail = new Email();
            // Assigned to current user
			$objEmail->assigned_user_id = $current_user->id;
			// Email is related to the current contact
			$objEmail->contact_id = $objContact->id;
            // Always send from the current user
            $objEmail->from_name = $current_user->name;
            // Set the from address
			$objEmail->from_addr = $current_user->email1;
            // Set the to address
			$objEmail->to_addrs = $objContact->email1;
            // Create the address array
            $objEmail->to_addrs_emails = "{$objContact->email1};";
            // Create the name array
            $objEmail->to_addrs_names = "{$objContact->name};";
			// Set the bcc and cc array to none
			$objEmail->bcc_addrs_arr = $objEmail->cc_addrs_arr = array();
			// Generate the html content
			$objEmail->description_html = TemplateParser::parse_generic(
				from_html($objTemplate->body_html), 
				array(
					$objContact->module_dir => $objContact->id
				),
				array(
					'aliases' => array ($objContact->module_dir => 'Contacts'),
				)
			);
            $objEmail->description = html2plaintext($objEmail->description_html);
            $objEmail->descriptionHtmlChanged = true;
            // Set the email subject
			$objEmail->name = $objTemplate->subject;
			if (!strlen($objEmail->name)) $objEmail->name = '(empty)';
            //  Set the folder to the drafts folder
            $objEmail->folder = EmailFolder::get_std_folder_id($objEmail->assigned_user_id, STD_FOLDER_DRAFTS);
            // Set the type to draft
			$objEmail->type = 'draft';
			// Set the parent type to account
			$objEmail->parent_type = 'Accounts';
			// Set the parent id
            $objEmail->parent_id = $strAccountId;
            //  Set emails to unread
            $objEmail->isread = 'unread';
			// Send the email
            $strEmailId = $objEmail->save();
            // Loop through and assign the attachments to the email
            foreach ($arNoteIds as $strId) {
        		// Retrieve the notes
        		$objNote = new Note();
        		// Retrieve the note
	            $objNote->retrieve($strId);
                $objNote->id = null;
	            // Set the parent type to Emails
	            $objNote->parent_type = 'Emails';
                // Set the parent id to the generated email
                $objNote->parent_id = $strEmailId;
	            // Add this note to the array
                $objNote->save();
			}
			// Add this email to the array of those sent
			$arEmails[$strEmailId] = array (
            	'subject' => $objEmail->name,
            	'contact_id' => $objContact->id,
            	'contact_name' => $objContact->name,
			);
		}
        foreach ($arNoteIds as $strId) {
            $objNote = new Note();
            if ($objNote->retrieve($strId))
                $objNote->mark_deleted($strId);
        }
		// Return the generated email array
		return $arEmails;
	}
}

?>
