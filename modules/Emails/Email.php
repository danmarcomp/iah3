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
 * Portions created by SugarCRM are Copyright (C) 2004-2005 SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 ********************************************************************************/

// $Id: Email.php,v 1.123.2.5 2005/10/27 18:31:18 ajay Exp $

require_once('data/SugarBean.php');
require_once('modules/Contacts/Contact.php');
require_once('modules/Accounts/Account.php');
require_once("include/SugarPHPMailer.php");
require_once("modules/EmailFolders/EmailFolder.php");
require_once("modules/Emails/utils.php");

// Email is used to store customer information.
class Email extends SugarBean {
	var $field_name_map;

	// Stored fields
	var $id;
	var $date_entered;
	var $date_modified;
	var $assigned_user_id;
	var $modified_user_id;
	var $created_by;
	var $created_by_name;
	var $modified_by_name;



	var $description;
	var $description_html;
	var $name;
	var $duration_hours;
	var $duration_minutes;
	var $date_start;
	var $parent_type;
	var $parent_id;
	var $contact_id;
	var $user_id;

	var $parent_name;
	var $contact_name;
	var $contact_phone;
	var $contact_email;
	var $account_id;
	var $opportunity_id;
	var $case_id;
	var $assigned_user_name;




	var $from_addr;
	var $from_name;
	var $to_addrs;
    var $cc_addrs;
    var $bcc_addrs;
	var $to_addrs_arr;
    var $cc_addrs_arr;
    var $bcc_addrs_arr;
	var $to_addrs_ids;
	var $to_addrs_names;
	var $to_addrs_emails;
	var $cc_addrs_ids;
	var $cc_addrs_names;
	var $cc_addrs_emails;
	var $bcc_addrs_ids;
	var $bcc_addrs_names;
	var $bcc_addrs_emails;
    var $type = 'archived';
    var $status;
    var $intent;
    var $mailbox_id;

	var $message_id;
	var $thread_id;
	var $in_reply_to;
	var $replace_fields;
    
    // longreach - start added - NOTE: $status is no longer used, but left for compatibility
    var $folder;
    var $folder_name;
    var $folder_reserved;
    var $send_error;
    var $isread;
	var $read_style;
	var $lead_id;
	var $bug_id;
	var $rel_leads_table = "emails_leads";
	var $rel_bugs_table = "emails_bugs";
	// --
	var $reply_to_addr;
	var $reply_to_name;
    // longreach - end added
    

    var $attachments = array();
    var $attachment_image;


    var $descriptionChanged = false;
    var $descriptionHtmlChanged = false;

	var $table_name = "emails";
	var $rel_users_table = "emails_users";
	var $rel_contacts_table = "emails_contacts";
	var $rel_cases_table = "emails_cases";
	var $rel_accounts_table = "emails_accounts";
	var $rel_opportunities_table = "emails_opportunities";
	var $module_dir = 'Emails';
	var $object_name = "Email";

	// This is used to retrieve related fields from form posts.
	var $additional_column_fields = Array('assigned_user_name', 'assigned_user_id', 'contact_id', 'user_id', 'contact_name','to_addrs_id');

    var $saved_attachments = array();    
	
	var $cachePath			= 'cache/modules/Emails';
	var $cacheFile			= 'robin.cache.php';
	
	static $prefetched = array();
	var $list_fetch_bodies = false;
	
    const DEFAULT_EMAIL_TYPE = 'out';
	
	var $new_schema = true;

	/** Returns a list of the associated contacts
	 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc..
	 * All Rights Reserved..
	 * Contributor(s): ______________________________________..
	*/
	function get_contacts()
	{
		// First, get the list of IDs.
		$query = "SELECT contact_id as id from emails_contacts where email_id='$this->id' AND deleted=0";

		return $this->build_related_list($query, new Contact());
	}

	/** Returns a list of the associated users
	 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc..
	 * All Rights Reserved..
	 * Contributor(s): ______________________________________..
	*/
	function get_users()
	{
		// First, get the list of IDs.
		$query = "SELECT user_id as id from emails_users where email_id='$this->id' AND deleted=0";

		return $this->build_related_list($query, new User());
	}

	// longreach - added
	function save($check_notify = false) {
		if (empty($this->message_id)) {
			$this->message_id = self::create_message_id();
		}
		/*if ((empty($this->id) || $this->new_with_id)) {
			$this->assign_thread_id();
		}*/
		$is_new = empty($this->id);
		if(empty($this->folder) || $check_notify) // assigned user changed, move to inbox
			$this->folder = EmailFolder::get_std_folder_id($this->assigned_user_id, STD_FOLDER_INBOX);
		$this->isread = ($this->isread == 'read' ? 1 : ($this->isread == 'unread' ? 0 : $this->isread));
		//if(!empty($this->isread) && $this->isread != 0 && $this->isread != 1)
        //$this->isread = $this->isread == 'unread';
        
		switch ($this->parent_type) {
			case 'Opportunities' :
				$this->opportunity_id = $this->parent_id;
				break;
			case 'Accounts':
				$this->account_id  = $this->parent_id;
				break;
			case 'Contacts':
				$this->contact_id  = $this->parent_id;
				break;
			case 'Cases':
				$this->case_id  = $this->parent_id;
				break;
			case 'Leads':
				$this->lead_id  = $this->parent_id;
				break;
			case 'Bugs':
				$this->bug_id  = $this->parent_id;
				break;
		}

		$this->store_addresses();
		$ret = parent::save($check_notify);
        if($this->descriptionChanged || $this->descriptionHtmlChanged)
			self::saveDescriptions($this->id, $this->description, $this->description_html);
		
		return $ret;
	}
	
	
	function store_addresses($force=false) {
		foreach(array('to', 'cc', 'bcc') as $f) {
			$arr = "{$f}_addrs_arr";
			$addrs_f = "{$f}_addrs";
			$names_f = "{$f}_addrs_names";
			if(! empty($this->$arr) && (empty($this->$addrs_f) || $force)) {
				$addrs = array();
				$names = array();
				foreach($this->$arr as $row) {
					$addrs[] = $row['email'];
					$names[] = array_get_default($row, 'display', '');
				}
				$this->$addrs_f = implode(';', $addrs);
				$this->$names_f = implode(';', $names);
			}
		}
	}

	/** Returns a list of the associated opportunities
	 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc..
	 * All Rights Reserved..
	 * Contributor(s): ______________________________________..
	*/
	function get_opportunities()
	{
		// First, get the list of IDs.
		$query = "SELECT opportunity_id as id from emails_opportunities where email_id='$this->id' AND deleted=0";

		return $this->build_related_list($query, new Opportunity());
	}

	/** Returns a list of the associated accounts
	 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc..
	 * All Rights Reserved..
	 * Contributor(s): ______________________________________..
	*/
	function get_accounts()
	{
		// First, get the list of IDs.
		$query = "SELECT account_id as id from emails_accounts where email_id='$this->id' AND deleted=0";

		return $this->build_related_list($query, new Account());
	}


	/** Returns a list of the associated cases
	 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc..
	 * All Rights Reserved..
	 * Contributor(s): ______________________________________..
	*/
	function get_cases()
	{
		// First, get the list of IDs.
		$query = "SELECT case_id as id from emails_cases where email_id='$this->id' AND deleted=0";

		return $this->build_related_list($query, new aCase());
	}

	function set_notification_body($xtpl, $email)
	{
		$xtpl->assign("EMAIL_SUBJECT", $email->name);
		$xtpl->assign("EMAIL_DATESENT", $email->date_start);

		return $xtpl;
	}

	static function check_email_settings($check_addr = true)
	{
		global $current_user;

        if ($check_addr) {
            $mail_fromaddress = $current_user->getPreference('mail_fromaddress');
            $mail_fromname = $current_user->getPreference('mail_fromname');
            if( empty($mail_fromaddress) || empty($mail_fromname )) {
                return false;
            }
        }

        $send_type = $current_user->getPreference('mail_sendtype') ;

		if ( ! empty($send_type) && $send_type == "SMTP") {
			$mail_smtpserver = $current_user->getPreference('mail_smtpserver');
			$mail_smtpport = $current_user->getPreference('mail_smtpport');
			$mail_smtpauth_req = $current_user->getPreference('mail_smtpauth_req');
			$mail_smtpuser = $current_user->getPreference('mail_smtpuser');
			$mail_smtppass = $current_user->getPreference('mail_smtppass');

			if ( empty($mail_smtpserver) || empty($mail_smtpport) ||
                	(! empty($mail_smtpauth_req) && ( empty($mail_smtpuser) || empty($mail_smtppass)))) {

                return false;
			}
		}

		return true;
	}

	function send()
	{
		$exceptions = null;
		return $this->send2($exceptions);
	}


	function send2(&$exceptions)
	{
		global $current_user;
		if (empty($this->message_id)) {
			$this->message_id = self::create_message_id();
		}
		if ((empty($this->id) || $this->new_with_id)) {
			$this->assign_thread_id();
		}
		$references = $this->get_references();
		require_once 'include/SugarPHPMailer.php';
		$mail = new SugarPHPMailer();
		$mail->exceptions = is_array($exceptions);
		try {
			// Ensure that we have a valid array
			if (is_array($this->to_addrs_arr)) {
				foreach ($this->to_addrs_arr as $addr_arr)
				{
					if ( empty($addr_arr['display']))
					{
								$mail->AddAddress($addr_arr['email'], "");
					}
					else
					{
								$mail->AddAddress($addr_arr['email'], $addr_arr['display']);
					}
				}
			}
			// Ensure that we have a valid array
			if (is_array($this->cc_addrs_arr)) {
				foreach ($this->cc_addrs_arr as $addr_arr)
				{
					if ( empty($addr_arr['display']))
					{
								$mail->AddCC($addr_arr['email'], "");
					}
					else
					{
								$mail->AddCC($addr_arr['email'], $addr_arr['display']);
					}
				}
			}
			// Ensure that we have a valid array
			if (is_array($this->bcc_addrs_arr)) {
				foreach ($this->bcc_addrs_arr as $addr_arr)
				{
					if ( empty($addr_arr['display']))
					{
								$mail->AddBCC($addr_arr['email'], "");
					}
					else
					{
								$mail->AddBCC($addr_arr['email'], $addr_arr['display']);
					}
				}
			}



			if (defined('inScheduler')) {
				$mail->InitForSend(true);
			} else {
				if ($current_user->getPreference('mail_sendtype') == "SMTP") {
					$mail->Mailer = "smtp";
					$mail->Host = $current_user->getPreference('mail_smtpserver');
					$mail->Port = $current_user->getPreference('mail_smtpport');
		
					if ($current_user->getPreference('mail_smtpauth_req'))
					{
						$mail->SMTPAuth = TRUE;
						$mail->Username = $current_user->getPreference('mail_smtpuser');
						$mail->Password = $current_user->getPreference('mail_smtppass');
					}
				}
				elseif ($current_user->getPreference('mail_sendtype') == 'sendmail') {
					$mail->Mailer = "sendmail";			
				}
			}

			if(empty($this->from_addr)) {
				$mail->From = $current_user->getPreference('mail_fromaddress');
				$this->from_addr = $mail->From;
				$mail->FromName =  $current_user->getPreference('mail_fromname');
				$this->from_name = $mail->FromName;
			}
			$mail->From = $this->from_addr;
			// longreach - added - set Return-Path header
			$mail->Sender = $mail->From;
			$mail->FromName = from_html($this->from_name);
			$mail->AddReplyTo($mail->From, $mail->FromName);
			$mail->Subject = from_html($this->name);

			///////////////////////////////////////////////////////////////////////
			////	ATTACHMENTS
			foreach($this->saved_attachments as $note) {
				$mime_type = 'text/plain';
				if($note->object_name == 'Note') {
					if (! empty($note->file->temp_file_location) && is_file($note->file->temp_file_location)) {
						$file_location = $note->file->temp_file_location;
						$filename = $note->file->original_file_name;
						$mime_type = $note->file->mime_type;
					} else {
						$file_location = rawurldecode(UploadFile::get_file_path($note->filename,$note->id));
						$filename = $note->id.$note->filename;
						$mime_type = $note->file_mime_type;
					}
				} elseif($note->object_name == 'DocumentRevision') { // from Documents
					$filename = $note->id.$note->filename;
					$file_location = getcwd().'/'.AppConfig::upload_dir().$filename;
					$mime_type = $note->file_mime_type;
				}

				$filename = substr($filename, 36, strlen($filename)); // strip GUID	for PHPMailer class to name outbound file
				$file_location = from_html($file_location);
				$filename = from_html($filename);
				$mail->AddAttachment($file_location, $filename, 'base64', $mime_type);
			}
			////	END ATTACHMENTS

			foreach ($this->attachments as $note)
			{
				$mime_type = 'text/plain';
				if (! empty($note->file->temp_file_location))
				{
					$file_location = $note->file->temp_file_location;
					$filename = $note->file->original_file_name;
					$mime_type = $note->file->mime_type;
				}
				else
				{
					$file_location = rawurldecode(UploadFile::get_file_path($note->filename,''));
					$filename = basename($note->filename);
					$mime_type = $note->file_mime_type;

				}

				$file_location = from_html($file_location);
				$filename = from_html($filename);
				$mail->AddAttachment(
						$file_location,
						$filename,
						'base64',
						$mime_type);

			}
            $descriptions = $this->loadDescriptions();
			if ( ! empty($descriptions['html']))
			{ 
				// wp: if body is html, then insert new lines at 996 characters. no effect on client side
				// due to RFC 2822 which limits email lines to 998
				$cids = array();
				require_once 'include/utils/html_utils.php';
				$html= show_inline_images($descriptions['html']);
				$html = make_inline_images($html, $cids);
				foreach ($cids as $cid => $filename) {
					$type = 'application/octet-stream';
					$m = array();
					if (preg_match('~\.([a-z]+)$~', $filename, $m)) {
						if (in_array(strtolower($m[1]), array('gif', 'jpg', 'jpeg', 'png', 'tiff', 'bmp'))) {
							$type = 'image/' . strtolower($m[1]);
						}
					}
					$mail->AddEmbeddedImage($filename, $cid, '', 'base64', $type);
				}
				
				$mail->Body = $html;   			
				$mail->AltBody = from_html($descriptions['plain']);
				$mail->IsHTML(true);
			}
			else
			{
				$mail->Body = from_html($descriptions['plain']);
			}
			
			$mail->prepForOutbound();

			$mail->MessageID = $this->message_id;
			if (!empty($this->in_reply_to)) {
				$mail->AddCustomHeader('In-Reply-To: ' . from_html($this->in_reply_to));
			}
			if (!empty($references)) {
				$mail->AddCustomHeader('References: ' . from_html($references));
			}

			if ($mail->Send())
			{
				

				// longreach - start added - Email Folders patch
				$this->folder = EmailFolder::get_std_folder_id($this->assigned_user_id, STD_FOLDER_SENT);
				$this->type = 'out';
				$this->send_error = 0;
				// longreach - end added - Email Folders patch

				
				return true;
			}
		} catch (phpmailerException $e) {
			if (is_array($exceptions)) {
				$exceptions[] = $e->getMessage();
			}
		}

		$GLOBALS['log']->warn("Error emailing:".$mail->ErrorInfo);
		$GLOBALS['send_error_msg'] = $mail->ErrorInfo;

		// longreach - start added - Email Folders patch
		$this->folder = EmailFolder::get_std_folder_id($this->assigned_user_id, STD_FOLDER_DRAFTS);
		$this->type = 'draft';
		// longreach - end added - Email Folders patch


		return false;

	}



	function remove_empty_fields(&$arr)
	{
		$newarr = array();
		foreach($arr as $field)
		{

			// longreach - added
			// without this blank addresses get through and message can be rejected
			$field = trim($field);
			
			if (empty($field))
			{
				continue;
			}
			array_push($newarr,$field);
		}
		return $newarr;
	}
	
	/**
	* regenerate_email_arrays; This function regenerates the send arrays based on the address and name strings
	*
	* @access public
	* @return void
	*/
	function regenerate_email_arrays() {
		// Initialize the address array
		$this->to_addrs_arr = array();
        // Regenerate the to address array
        $arAddresses = $this->remove_empty_fields(explode(";", $this->to_addrs));
        // Regenerate the to names address array
        $arNames = $this->remove_empty_fields(explode(";", $this->to_addrs_names));
    	// Loop through the email addresses
    	for ($i=0;  $i<count($arAddresses); $i++) {
            // Don't add empry strings
            if ($arAddresses[$i] != '') {
            	// Do we have a matching name?
            	if (count($arNames) > $i) {
                	// Add the email address and name
                	$this->to_addrs_arr[] = array (
                    	'email' => $arAddresses[$i],
                		'display' => $arNames[$i],
                	);
				} else {
                    // Add the email address only
                	$this->to_addrs_arr[] = array (
                    	'email' => $arAddresses[$i],
                	);
				}
			}
		}
	}

    /**
	* regenerate_attachment_array; This function regenerates the attachment array
	*
	* @access public
	* @return void
	*/
	function regenerate_attachment_array() {
		// Initialize the attachment array
		$this->saved_attachments = array();
        // Retrieve the related notes
        $strQuery =  "
        	SELECT
        		id
        	FROM
        		notes
        	WHERE
        		notes.parent_id = '{$this->id}'
        	AND
        		notes.deleted = 0
        ";
        // Generate the query
		if ($hResult = $this->db->query($strQuery, true," Error getting attachments")) {
            // Loop through and recreate the attachments
			while ($arRow = $this->db->fetchByAssoc($hResult)) {
                // Create the note object
				$objNote = new Note();
				// Retrieve the note
				$objNote->retrieve($arRow['id']);
				// Add the note to the attachments array
                $this->saved_attachments[] = $objNote;
			}
		}
	}

	function parse_addrs($to_addrs,$to_addrs_ids,$to_addrs_names,$to_addrs_emails)
	{
		$return_arr = array();
		$to_addrs =from_html($to_addrs);
		$to_addrs_arr = explode(";",$to_addrs);
		$to_addrs_arr = $this->remove_empty_fields($to_addrs_arr);
		$to_addrs_ids_arr = explode(";",$to_addrs_ids);
		$to_addrs_ids_arr = $this->remove_empty_fields($to_addrs_ids_arr);
		$to_addrs_emails_arr = explode(";",$to_addrs_emails);
		$to_addrs_emails_arr = $this->remove_empty_fields($to_addrs_emails_arr);
		$to_addrs_names_arr = explode(";",$to_addrs_names);
		$to_addrs_names_arr = $this->remove_empty_fields($to_addrs_names_arr);

		$contact = new Contact();

		for ($i = 0; $i < count($to_addrs_arr); $i++)
		{
			$newarr = array();
			$field = $to_addrs_arr[$i];

			//extract the email address:
			preg_match("/([a-zA-Z0-9\-\+\.\_\']+\@[a-zA-Z0-9\-\+\.\_]+)/",$field,$match);

			
			// longreach - start added - remove email address from display name
			$display = trim(preg_replace("/\s*(<\s*)?([a-zA-Z0-9\-\+\.\_\']+\@[a-zA-Z0-9\-\+\.\_]+)(\s*)>?/", '', $field, 1));
			if(!empty($display))
				$field = $display;
			// longreach - end added
			

			$newarr['display'] = $field;
			if ( empty($match[1]))
			{
				$newarr['email'] = '';
			}
			else
			{
				$newarr['email'] = $match[1];
			}

			if ( isset($to_addrs_emails_arr[$i]) && $to_addrs_emails_arr[$i] == $match[1])
			{
				$newarr['contact_id'] = @$to_addrs_ids_arr[$i];
				$newarr['name'] = @$to_addrs_names_arr[$i];
			}
			else
			{
				$contact_id = $contact->get_contact_id_by_email($newarr['email']);
				if ( ! empty($contact_id))
				{
					$newarr['contact_id'] = $contact_id;
				}
			}
			array_push($return_arr,$newarr);
		}
		$contact->cleanup();
		return $return_arr;

	}


    function setDescription($text) {
        if ($this->descriptionChanged || $text != $this->description) {
            $this->descriptionChanged = true;
            $this->description = $text;
        }
    }

    function setDescriptionHTML($text) {
        if ($this->descriptionHtmlChanged || $text != $this->description_html) {
            $this->descriptionHtmlChanged = true;
            $this->description_html = $text;
        }
    }

    static function saveDescriptions($email_id, $description, $description_html, $force_insert = false) {
		$upgraded = 1;
		$upd = new RowUpdate('emails_bodies');
		$upd->set(compact('email_id', 'description', 'description_html', 'upgraded'));
		$upd->putRow();
    }
    
    function loadDescriptions() {
		$descriptions = array(
			'html' => $this->description_html,
			'plain' => $this->description,
		);

    	if(! empty($this->id) && empty($this->new_with_id)) {
    		$row = ListQuery::quick_fetch_row('emails_bodies', $this->id);
            if ($row) {
                $descriptions['plain'] = $row['description'];
                $descriptions['html'] = $row['description_html'];
            }
        }

        return $descriptions;
    }

    /**
     * Returns the name from the email
     *
     * @static
     * @param string $from_name
     * @param string $part - 'first','last' or 'both'
     * @return string
     */
	static function getName($from_name, $part) {
		$first = '';
		$last = '';
		$email = '';

		if(!empty($from_name)) {
			$name = $from_name;
		} else {
			return '';
		}
		
		$name = trim($name);
		
		if(strpos($name, '<')) {
			$start = strpos($name, '<');
		} elseif(strpos($name, '&lt;')) {
			$start = strpos($name, '&lt;');
		}
		if(!empty($start)) {
			$email = substr($name, $start, strlen($name));
			$name = substr($name, 0, ($start-1));
		}
		
		if(strpos($name, ',')) { // explode last, first format
			$exName = explode(',', $name);
		} elseif (strpos($name, ' ')) { // explode first x last format
			$exName = explode(' ', $name);
			if(count($exName) > 2) {
				$last = $exName[(count($exName) - 1)];
				$loop = count($exName) - 2; // -2 because we only want the values before 'last name'
				$first = '';
				for($i=0; $i<$loop; $i++) {
					$first .= $exName[$i];
				}
			} else {
				$first = $exName[0];
				$last = $exName[1];
			}
		} else { //
			// likely that this is ONLY an email address
			if(strpos($name, "@")) {
				$email = $name;
			} else {
				$first = $name;
			}
		}
		
		$names = array();
		$names['first'] = $first;
		$names['last'] = $last;
		$names['both'] = $first.' '.$last;
		$names['email'] = $email;
		
		return $names[$part];
	}


	function distributionForm($where) {
		global $app_list_strings;
		global $app_strings;
		global $mod_strings;
		global $theme;
		global $current_user;
		
		$distribution	= get_select_options_with_id($app_list_strings['dom_email_distribution'], '');
		$_SESSION['distribute_where'] = $where;
		
		$out = '
		<form name="Distribute" id="Distribute" method="POST" action="index.php">';
		$out .= get_form_header($mod_strings['LBL_DIST_TITLE'], '', false);
		$out .= '
		<table cellpadding="0" cellspacing="0" width="100%" border="0">
			<tr>
				<td>
					<script type="text/javascript">
				
				
						function checkDeps(form) {
							return;
						}

						function mySubmit() {
							var assform = document.getElementById("Distribute");
							var select = document.getElementById("userSelect");
							var assign1 = assform.r1.checked;
							var assign2 = assform.r2.checked;
							var dist = assform.dm.value;
							var assign = false;
							var users = false;
							var rules = false;
							var warn1 = "'.$mod_strings['LBL_WARN_NO_USERS'].'";
							var warn2 = "";

							if(assign1 || assign2) {
								assign = true;
								
							}
							
							for(i=0; i<select.options.length; i++) {
								if(select.options[i].selected == true) {
									users = true;
									warn1 = "";
								}
							}
							
							if(dist != "") {
								rules = true;
							} else {
								warn2 = "'.$mod_strings['LBL_WARN_NO_DIST'].'";
							}
							
							if(assign && users && rules) {

								if(document.getElementById("r1").checked) {
									var mu = document.getElementById("MassUpdate");
									var grabbed = "";
							
									for(i=0; i<mu.elements.length; i++) {
										if(mu.elements[i].type == "checkbox" && mu.elements[i].checked && mu.elements[i].name.value != "massall") {
											if(grabbed != "") { grabbed += "::"; }
											grabbed += mu.elements[i].value;
										}
									}
									var formgrab = document.getElementById("grabbed");
									formgrab.value = grabbed;
								}
								assform.submit();
							} else {
								alert("'.$mod_strings['LBL_ASSIGN_WARN'].'" + "\n" + warn1 + "\n" + warn2);
							}
						}

						function submitDelete() {
							if(document.getElementById("r1").checked) {
								var mu = document.getElementById("MassUpdate");
								var grabbed = "";
						
								for(i=0; i<mu.elements.length; i++) {
									if(mu.elements[i].type == "checkbox" && mu.elements[i].checked && mu.elements[i].name != "massall") {
										if(grabbed != "") { grabbed += "::"; }
										grabbed += mu.elements[i].value;
									}
								}
								var formgrab = document.getElementById("grabbed");
								formgrab.value = grabbed;
							}
							if(grabbed == "") {
								alert("'.$app_strings['LBL_LISTVIEW_NO_SELECTED'].'");
							} else {
								document.getElementById("Distribute").submit();
							}	
						}
				
					</script>
						<input type="hidden" name="module" value="Emails">
						<input type="hidden" name="action" id="action" value="Distribute">
						<input type="hidden" name="grabbed" id="grabbed">
						
					<table cellpadding="1" cellspacing="0" width="100%" border="0" class="tabForm">
						<tr height="20">
							<td scope="col" width="15%" class="dataLabel" NOWRAP align="left" valign="middle">
								'.$mod_strings['LBL_USE'].'&nbsp;
								<input id="r1" type="radio" CHECKED style="border:0px solid #000000" name="use" value="checked" onclick="checkDeps(this.form);">&nbsp;'.$mod_strings['LBL_USE_CHECKED'].'
								<input id="r2" type="radio" style="border:0px solid #000000" name="use" value="all" onclick="checkDeps(this.form);">&nbsp;'.$mod_strings['LBL_USE_ALL'].'
							</select>
							</td>

							<td scope="col" width="25%" class="dataLabel" NOWRAP align="center">
								&nbsp;'.$mod_strings['LBL_TO'].'&nbsp;';
					$out .= $this->userSelectTable();
					$out .=	'</td>
							<td scope="col" width="15%" class="dataLabel" NOWRAP align="left">
								&nbsp;'.$mod_strings['LBL_USING_RULES'].'&nbsp;
								<select name="distribute_method" id="dm" onChange="checkDeps(this.form);">'.$distribution.'</select>
							</td>

							<td scope="col" width="50%" class="dataLabel" NOWRAP align="right">
								<input title="'.$mod_strings['LBL_BUTTON_DISTRIBUTE_TITLE'].'"
									id="dist_button"  
									accessKey="'.$mod_strings['LBL_BUTTON_DISTRIBUTE_KEY'].'" 
									class="button" onClick="this.form.action.value=\'Distribute\'; this.form.module.value=\'Emails\'; mySubmit();" 
									type="button" name="button"
									value="  '.$mod_strings['LBL_BUTTON_DISTRIBUTE'].'  ">';
					if($current_user->is_admin == 'on') {
						$out .= '&nbsp;
								<input title="'.$app_strings['LBL_DELETE_BUTTON_TITLE'].'"
									id="del_button"  
									accessKey="'.$app_strings['LBL_DELETE_BUTTON_KEY'].'" 
									class="button" onClick="this.form.action.value=\'MassDelete\'; this.form.module.value=\'Emails\'; submitDelete();" 
									type="button" name="del_button"
									value="  '.$app_strings['LBL_DELETE_BUTTON'].'  ">'; 	
					}
					
					$out .= '
							</td>
						</tr>
					</table>
					
				</td>
			</tr>
		</table>
		</form>';
	return $out;
	}

	function userSelectTable() {
		global $theme, $image_path;
		global $mod_strings;
		
		$colspan = 1;
		$setTeamUserFunction = '';
		// get users
		/* longreach - removed
		$r = $this->db->query("SELECT users.id, users.user_name, users.first_name, users.last_name FROM users WHERE deleted=0 AND status = 'Active' ORDER BY users.last_name, users.first_name");
		*/
		
		$userTable = '<table cellpadding="0" cellspacing="0" border="0">';
		$userTable .= '<tr><td colspan="2"><b>'.$mod_strings['LBL_USER_SELECT'].'</b></td></tr>';
		$userTable .= '<tr><td><input type="checkbox" style="border:0px solid #000000" onClick="toggleAll(this); setCheckMark(); checkDeps(this.form);"></td> <td>'.$mod_strings['LBL_TOGGLE_ALL'].'</td></tr>';
		$userTable .= '<tr><td colspan="2"><select name="users[]" id="userSelect" multiple size="12">';
		
		/* longreach - removed
		while($a = $this->db->fetchByAssoc($r)) {
			$userTable .= '<option value="'.$a['id'].'" id="'.$a['id'].'">'.$a['first_name'].' '.$a['last_name'].'</option>';	
		}
		*/
		// longreach - start added - use standard query method; ignore portal users
		$user_opts = get_user_array(false);
		foreach($user_opts as $id => $uname) {
			$userTable .= '<option value="'.$id.'" id="'.$id.'">'.$uname.'</option>';
		}
		// longreach - end added
		
		
		$userTable .= '</select></td></tr>';
		$userTable .= '</table>';
		
		$user_img = get_image($image_path."Users", 'border="0"');
		$check_img = get_image($image_path."check_inline", 'border="0"');
		$close_img = get_image($image_path."close", 'border="0"');
		
		$out  = '<script type="text/javascript">';
		$out .= $setTeamUserFunction; 
		$out .= '
					function setCheckMark() {
						var select = document.getElementById("userSelect");
					
						for(i=0 ; i<select.options.length; i++) {
							if(select.options[i].selected == true) {
								document.getElementById("checkMark").style.display="";
								return;
							}
						}
						
						document.getElementById("checkMark").style.display="none";
						return;
					}
									
					function showUserSelect() {
						var targetTable = document.getElementById("user_select");

						// longreach - modified - fix firefox rendering bug
						targetTable.style.display="block";

						return;
					}
					function hideUserSelect() {
						var targetTable = document.getElementById("user_select");
						
						// longreach - modified - fix firefox rendering bug
						targetTable.style.display="none";
						
						return;
					}
					function toggleAll(toggle) {
						if(toggle.checked) {
							var stat = true;
						} else {
							var stat = false;
						}
						var form = document.getElementById("userSelect");
						for(i=0; i<form.options.length; i++) {
							form.options[i].selected = stat;
						}
					}


				</script>
			<span id="showUsersDiv" style="position:relative;">
				<a href="#" id="showUsers" onClick="javascript:showUserSelect();">
					'.$user_img.'</a>&nbsp;
				<a href="#" id="showUsers" onClick="javascript:showUserSelect();">
					<span style="display:none;" id="checkMark">'.$check_img.'</span>
				</a>
				
				
				<div id="user_select" style="width:200px;position:absolute;left:2;top:2;display:none;z-index:1000;">
				<table cellpadding="0" cellspacing="0" border="0" class="listView">
					<tr height="20">
						<td class="listViewThS1" colspan="'.$colspan.'" id="hiddenhead" onClick="hideUserSelect();" onMouseOver="this.style.border = \'outset red 1px\';" onMouseOut="this.style.border = \'inset white 0px\';this.style.borderBottom = \'inset red 1px\';">
							<a href="#" onClick="javascript:hideUserSelect();">'.$close_img.'</a>
							'.$mod_strings['LBL_USER_SELECT'].'
						</td>
					</tr>
					<tr>';
//<td valign="middle" height="30" class="listViewThS1" colspan="'.$colspan.'" id="hiddenhead" onClick="hideUserSelect();" onMouseOver="this.style.border = \'outset red 1px\';" onMouseOut="this.style.border = \'inset white 0px\';this.style.borderBottom = \'inset red 1px\';">

		$out .=	'		<td style="padding:5px" class="oddListRowS1" bgcolor="#fdfdfd" valign="top" align="left" style="left:0;top:0;">
							'.$userTable.'
						</td>
					</tr>
				</table></div>
			</span>';
		return $out;
	}

	/**
	 * distributes emails to users on Round Robin basis
	 * @param	$userIds	array of users to dist to
	 * @param	$mailIds	array of email ids to push on those users
	 * @return  boolean		true on success
	 */
	function distRoundRobin($userIds, $mailIds) {
		// check if we have a 'lastRobin'
		if(!file_exists($this->cachePath.'/'.$this->cacheFile)) {
			$this->writeToCache('robin', array($userIds[0]));
			$lastRobin = $userIds[0];
		} else {
			require_once($this->cachePath.'/'.$this->cacheFile);
			$lastRobin = $robin[0];
		}
				
		foreach($mailIds as $k => $mailId) {
			$userIdsKeys = array_flip($userIds); // now keys are values
			$thisRobinKey = $userIdsKeys[$lastRobin] + 1;
			if(!empty($userIds[$thisRobinKey])) {
				$thisRobin = $userIds[$thisRobinKey];
				$lastRobin = $userIds[$thisRobinKey];
			} else {
				$thisRobin = $userIds[0];
				$lastRobin = $userIds[0];
			}

			$email = new Email();
			$email->retrieve($mailId);
			if($email->checkPessimisticLock()) {
				require_once 'modules/EmailFolders/EmailFolder.php';
				$email->folder = EmailFolder::get_std_folder_id($thisRobin, STD_FOLDER_INBOX);
				$email->assigned_user_id = $thisRobin;
				$email->save();
			} else {
				$GLOBALS['log']->debug('Emails: Round-robin distribution hit a Pessimistic Lock.  Skipping email('.$email->id.').');
			}
			$email->cleanup();
		}
		$this->writeToCache('robin', array($lastRobin));
		return true;
	}

	/**
	 * distributes emails to users on Least Busy basis
	 * @param	$userIds	array of users to dist to
	 * @param	$mailIds	array of email ids to push on those users
	 * @return  boolean		true on success
	 */
	function distLeastBusy($userIds, $mailIds) {
		foreach($mailIds as $k => $mailId) {
			$email = new Email();
			$email->retrieve($mailId);
			if($email->checkPessimisticLock()) {
		
				foreach($userIds as $k2 => $id) {
					$r = $this->db->query("SELECT count(*) AS c FROM emails WHERE assigned_user_id = '.$id.' AND !isread");
					$a = $this->db->fetchByAssoc($r);
					$counts[$id] = $a['c'];
				}
				asort($counts); // lowest to highest
				$countsKeys = array_flip($counts); // keys now the 'count of items'
				$leastBusy = array_shift($countsKeys); // user id of lowest item count
	
				require_once 'modules/EmailFolders/EmailFolder.php';
				$email->folder = EmailFolder::get_std_folder_id($leastBusy, STD_FOLDER_INBOX);
				$email->assigned_user_id = $leastBusy;
				$email->save();
			} else {
				$GLOBALS['log']->debug('Emails: Least-busy distribution hit a Pessimistic Lock.  Skipping email('.$email->id.').');
			}
			$email->cleanup();
		}
		
		return true;
	}

	/**
	 * distributes emails to 1 user
	 * @param	$user		users to dist to
	 * @param	$mailIds	array of email ids to push
	 * @return  boolean		true on success
	 */
	function distDirect($user, $mailIds) {
		foreach($mailIds as $k => $mailId) {
			$email = new Email();
			$email->retrieve($mailId);
			if($email->checkPessimisticLock()) {
				require_once 'modules/EmailFolders/EmailFolder.php';
				$email->folder = EmailFolder::get_std_folder_id($user, STD_FOLDER_INBOX);
				$email->assigned_user_id = $user;
				$email->save();
			} else {
				$GLOBALS['log']->debug('Emails: Least-busy distribution hit a Pessimistic Lock.  Skipping email('.$email->id.').');
			}
			$email->cleanup();
		}
		
		return true;
	}

	/** 
	 * takes all existing queues and writes it to a cached file for performance
	 */
	function writeToCache($varName, $array) {
		if(!function_exists('mkdir_recursive')) {
			require_once('include/dir_inc.php');
		}
		if(!function_exists('write_array_to_file')) {
			require_once('include/utils/file_utils.php');
		}
		// cache results
		if(!file_exists($this->cachePath) || !file_exists($this->cachePath.'/'.$this->cacheFile)) {
			// create directory if not existent
			mkdir_recursive($this->cachePath, false);
		}
		// write cache file
		write_array_to_file($varName, $array, $this->cachePath.'/'.$this->cacheFile);
	}

	function get_search_folder_options($param)
	{
		global $current_user, $mod_strings;
		$folders = EmailFolder::get_folders_list($current_user->id);
		$selected_folder = empty($param['value']) ? '' : $param['value'] ;
		$allNew = 0;
		foreach ($folders as $f) {
			if ($f['reserved'] != STD_FOLDER_TRASH) {
				$allNew += $f['newmessages'];
			}
		}
		
		$grp_folders = EmailFolder::get_group_folders_list();
		$groupNew = 0;
		foreach ($grp_folders as $idx=>$f) {
			$groupNew += $f['newmessages'];
		}
		
		$allNew = $allNew ? " ($allNew)" : '';
		$fs = '<optgroup label="'.translate('LBL_ALL_EMAILS', 'Emails').'">';
		$selected = ('ALL' == $selected_folder) ? ' selected ' : '';
		$fs .= '<option value="ALL"' . $selected . '>' . $mod_strings['LNK_ALL_EMAIL_LIST'] . $allNew . '</option>';
		$groupNew = $groupNew ? " ($groupNew)" : '';
		$selected = ('GROUP' == $selected_folder) ? ' selected ' : '';	
		$fs .= '<option value="GROUP"' . $selected . '>' . $mod_strings['LNK_GROUP_EMAIL_LIST'] . $groupNew . '</option>';
		$fs .= '</optgroup>';
		
		$fs .= '<optgroup label="'.translate('LBL_STANDARD_FOLDERS', 'Emails').'">';
		$have_user_folders = false;
		foreach ($folders as $f) {
			if (!$f['reserved'] && !$have_user_folders) {
				$fs .= '</optgroup><optgroup label="'.translate('LBL_USER_FOLDERS', 'Emails').'">';
				$have_user_folders = true;
			}
			$New = $f['newmessages'] ? " ({$f['newmessages']})" : '';
			if($f['reserved'] == STD_FOLDER_DRAFTS)
				$New = $f['totalmessages'] ? " ({$f['totalmessages']})" : '';
			$selected = ($f['id'] == $selected_folder) ? ' selected ' : '';
			$fs .= '<option value="' . $f['id'] . '"' . $selected . '>' . $f['name'] . $New . '</option>';
		}
		$fs .= '</optgroup>';
		
		if(count($grp_folders)) {
			$fs .= '<optgroup label="'.translate('LBL_GROUP_FOLDERS', 'Emails').'">';
			foreach ($grp_folders as $f) {
				$New = $f['newmessages'] ? " ({$f['newmessages']})" : '';
				$selected = ($f['id'] == $selected_folder) ? ' selected ' : '';
				$fs .= '<option value="' . $f['id'] . '"' . $selected . '>' . $f['name'] . $New . '</option>';
			}
			$fs .= '</optgroup>';
		}
		return $fs;
	}

	function get_search_in_options()
	{
		global $mod_strings;
		return array(
			'subject' => $mod_strings['LBL_SEARCH_SUBJECT'],
			'subject_body' => $mod_strings['LBL_SEARCH_SUBJECT_BODY'],
			'body' => $mod_strings['LBL_SEARCH_BODY'],
		);
	}

	function get_search_folder_where($param)
	{
		global $current_user;
		$_folder = $param['value'];
		if($_folder && $_folder != 'GROUP'  && $_folder != 'ALL' ) {
			$where = "emails.folder= '".PearDatabase::quote($_folder)."'";
		} elseif (!$_folder || $_folder == 'ALL' ) {
		    $fid = EmailFolder::get_std_folder_id($current_user->id, STD_FOLDER_TRASH);
			$where = "emails.folder != '$fid' AND emails.assigned_user_id ='{$current_user->id}'";
		} elseif ($_folder != 'GROUP') {
			$where = " emails.assigned_user_id ='{$current_user->id}'";
		} else {
			$where = " emails.assigned_user_id ='-1'";
		}
		return $where;
	}
	
	function get_search_term_where($param, $searchFields)
	{
		$search_term = $param['value'];
		$search_in = $searchFields['search_in']['value'];
		$clauses = array();
		if($search_term !== '') {
			$parts = array();
			if (empty($search_in) || $search_in == 'subject' || $search_in == 'subject_body') {
				$parts[] = "MATCH (emails.name) AGAINST( '".PearDatabase::quote($search_term)."' IN BOOLEAN MODE)";
			}
			if (isset($search_in) && ($search_in == 'subject_body' || $search_in == 'body')) {
				$parts[] = "/*!!!FTS!!!!*/MATCH (descriptions.description) AGAINST( /*!!!!FTTS!!!!*/'".PearDatabase::quote($search_term)."'/*!!!!FTTE!!!!*/ IN BOOLEAN MODE)/*!!!!FTE!!!!*/";
			}
			if (!empty($parts)) return '(' . join(' OR ', $parts) . ')';
		}
		return '1';
	}

	function get_search_in_where()
	{
		return '1';
	}

	function get_search_contact_where($param)
	{
		global $db;
		$clauses = array();
		$names = trim($param['value']);
		if(! strlen($names))
			return '1';
		$contact_names = explode(" ", $names);
		foreach ($contact_names as $name) if (!empty($name)) {
			$v = $db->quote($name);
			$clauses[] = "(contact_join.first_name like '{$v}%' OR contact_join.last_name like '{$v}%')";
		}
		$all = join(' AND ', $clauses);
		$ret = "($all) OR from_name LIKE '%".$db->quote($names)."%'";
		return $ret;
	}
	

	static function create_message_id()
	{
		global $current_user;
		$id = '<';
		if (!empty($current_user->id)) {
			$id .= $current_user->id . '-';
		}
		$id .= uniqid('');
		$id .= '@' . AppConfig::host_name();
		$id .= '>';
		return $id;
	}

	function get_references()
	{
		$ids = array();
		$query = "SELECT message_id FROM mail_threads WHERE thread_id = '" . $this->db->quote($this->thread_id) . "' AND message_id != '" . $this->db->quote($this->message_id) . "' AND deleted = '0' LIMIT 50";
		$res = $this->db->query($query, true);
		while ($row = $this->db->fetchByAssoc($res)) {
			$ids[] = $row['message_id'];
		}
		$ids = array_unique($ids);
		return join(' ', $ids);
	}

    static function get_related_emails_query($id, $thread_id) {
        $lq = new ListQuery('Email');

        $clauses = array(
            "thread" => array(
                "value" => $thread_id,
                "field" => "thread_id",
            ),
            "active" => array(
                "value" => $id,
                "field" => "id",
                "operator" => "not_eq"
            )
        );

        $lq->addFilterClauses($clauses);
        $lq->addAclFilter('list');

        return $lq;
    }

    function assign_thread_id() {
        global $db;
        if (empty($this->message_id)) {
            $this->message_id = self::create_message_id();
        }

        /*if (! empty($this->thread_id)) {
              $query = "REPLACE INTO mail_threads SET message_id='" . PearDatabase::quote($this->message_id) . "', "
                      . "thread_id='" . $this->thread_id. "', date_modified='".$GLOBALS['timedate']->get_gmt_db_datetime()."'";
              $db->query($query, true);
              return;
        } */

        $query = "SELECT thread_id FROM mail_threads WHERE message_id IN ('";
        $ids = array($this->message_id);

        if (!empty($this->in_reply_to))
            $ids[] = $this->in_reply_to;

        $ids = array_map(array(&$db, 'quote'), $ids);
        $query .= join("','", $ids);
        $query .= "') AND deleted = 0";
        $res = $db->query($query, true);
        $now = gmdate('Y-m-d H:i:s');

        if ($row = $db->fetchByAssoc($res)) {
            $query = "INSERT INTO mail_threads SET message_id = '" . $db->quote($this->message_id) . "', thread_id = '" . $row['thread_id']. "', date_modified='$now'";
            $this->thread_id = $row['thread_id'];
        } else {
            $thread_id = create_guid();
            $query = "INSERT INTO mail_threads SET message_id = '" . $db->quote($this->message_id) . "', thread_id = '$thread_id', date_modified='$now'";
            $this->thread_id = $thread_id;
        }
        $db->query($query, true);
    }
    
    
	static function before_save(RowUpdate &$update) {
        $to = normalize_emails_array(parse_addrs($update->getField('to_addrs')), null, true, true);
        $cc = normalize_emails_array(parse_addrs($update->getField('cc_addrs')), null, true, true);
        $bcc = normalize_emails_array(parse_addrs($update->getField('bcc_addrs')), null, true, true);

        //Start ADD RELATIONSHIPS
        $all_contacts = array_merge($to, $cc, $bcc);
        $rel_contacts = array();
        foreach($all_contacts as $contact) {
            if (isset($contact['contact_id']))
                array_push($rel_contacts, $contact['contact_id']);
        }

        if($rel_contacts && ! $update->getField('contact_id'))
        	$update->set('contact_id', $rel_contacts[0]);
        	
		$update->setRelatedData('rel_contacts', $rel_contacts);
		$recipients = array('to' => $to, 'cc' => $cc, 'bcc'=> $bcc);
		$update->setRelatedData('recipients', $recipients);
	}


    static function after_save(RowUpdate &$update) {
		static $counts_updated = false;
        if($update->getFieldUpdated('description') || $update->getFieldUpdated('description_html')) {
        	$id = $update->getPrimaryKeyValue();
        	$desc = $update->getField('description');
        	$desc_html = $update->getField('description_html');
    		if( ($fmt = $update->getInputFormat()) == 'soap' || $fmt == 'json') {
    			if(isset($desc_html) && ! isset($desc))
					$desc = html2plaintext($desc_html);
    		}
			Email::saveDescriptions($id, $desc, $desc_html);
        }
        
        if($update->getFieldUpdated('parent_id') && ($pid = $update->getField('parent_id'))) {
        	$lname = strtolower($update->getField('parent_type'));
            if ($lname == 'emails')
                $lname = 'related_emails';
        	if($update->linkDefined($lname))
        		$update->addUpdateLink($lname, $pid);
        }
        
        $contacts = $update->getRelatedData('rel_contacts');
		if($contacts)
			$update->addUpdateLinks('contacts', $contacts);

        if($update->getFieldUpdated('contact_id') && ($cid = $update->getField('contact_id'))) {
        	$update->addUpdateLink('contacts', $cid);
        }
        
        if(($tid = $update->getField('thread_id')) && ($mid = $update->getField('message_id'))) {
        	global $db, $timedate;
			$query = "REPLACE INTO mail_threads SET message_id='" . $db->quote($mid) . "', "
					. "thread_id='" . $db->quote($tid). "', date_modified='".$timedate->get_gmt_db_datetime()."'";
			$db->query($query, true);
        }

        global $current_user;
		if(! empty($current_user)) {
			$current_user->invalidate_emails_count();
			if(!$counts_updated && defined('ASYNC_ENTRY') && !AppConfig::setting('site.performance.disable_status_check')) {
				global $pageInstance;
				if ($pageInstance instanceof ModulePage) {
					$pageInstance->add_status_check_javascript(true);
					$counts_updated = true;
				}
			}
		}
    }

    /**
     * Move email to trash or mark as deleted
     *
     * @param RowUpdate $update
     * @return bool|void
     */
    function mark_deleted(RowUpdate &$update) {
        $id = $update->getField('id');
        $email = ListQuery::quick_fetch('Email', $id, array('folder', 'assigned_user_id'));

        if ($email != null) {
            $folder = $email->getField('folder');
            $trash_folder = EmailFolder::get_std_folder_id($email->getField('assigned_user_id'), STD_FOLDER_TRASH);
            if(empty($trash_folder)) $trash_folder = $folder;

            if ($trash_folder == $folder) {
                $note = new Note();
                $where = "notes.parent_id='{$id}'";
                $notes_list = $note->get_full_list("", $where, true);

                for($i = 0; $i < count($notes_list);$i++) {
                    $the_note = $notes_list[$i];
                    UploadFile::unlink_file($the_note->id, $the_note->filename);
                    $the_note->mark_deleted($the_note->id);
                    $the_note->cleanup();
                }

                $email = new Email();
                $update->removeAllLinks($email->rel_users_table);
                $update->removeAllLinks($email->rel_contacts_table);
                $update->removeAllLinks($email->rel_cases_table);
                $update->removeAllLinks($email->rel_accounts_table);
                $update->removeAllLinks($email->rel_opportunities_table);

                $update->markDeleted(true);
            } else {
                $update->set(array('deleted' => 0));
                $update->set(array('folder' => $trash_folder));
                $update->save();
            }
        }
    }

    static function move_to_trash($record_id, $untrash_to = null) {
        $fields = array('assigned_user_id', 'folder', 'folder_ref.reserved');
        $result = ListQuery::quick_fetch('Email', $record_id, $fields);

        if ($result) {
            $src = $result->getField('folder_ref.reserved');
            $upd = RowUpdate::for_result($result);

            if (! empty($untrash_to) && $src == STD_FOLDER_TRASH) {
                $upd->set('folder', $untrash_to);
                $upd->save();
            } else if (empty($untrash_to) && $src != STD_FOLDER_TRASH) {
                require_once 'modules/EmailFolders/EmailFolder.php';
                $upd->set('folder', EmailFolder::get_std_folder_id(AppConfig::current_user_id(), STD_FOLDER_TRASH));
                $upd->save();
            }
        }
    }
    
    static function fix_date_start(RowUpdate &$upd) {
    	$ds = $upd->getField('date_start');
    	if($ds && preg_match('~^\d{4}-\d{2}-\d{2}$~', $ds)) {
    		$ds .= ' 00:00:00';
    		$upd->set('date_start', $ds);
    	}
    }

    static function init_record(RowUpdate &$upd, $input) {
        $values = array();
        if(! $upd->getField('assigned_user_id'))
        	$upd->set('assigned_user_id', AppConfig::current_user_id());

		self::init_default($upd, $input);

        if(! empty($input['reply_to'])) {
			self::init_reply($upd, $input);
        } elseif (! empty($input['forward'])) {
			self::init_forward($upd, $input);
		}
        
        $assigned_user_id = $upd->getField('assigned_user_id');
        
        $type = $upd->getField('type', array_get_default($input, 'type'));

        $folder = $upd->getField('folder');
        if (empty($folder)) {
            if ($type == 'draft') {
                $folder = EmailFolder::get_std_folder_id($assigned_user_id, STD_FOLDER_DRAFTS);
            } else {
                $folder = EmailFolder::get_std_folder_id($assigned_user_id, STD_FOLDER_INBOX);
            }
            $values['folder'] = $folder;
        }
        $message_id = $upd->getField('message_id');
        if (empty($message_id))
            $values['message_id'] = self::create_message_id();
        
        $upd->set($values);
    }
    
    static function load_to_addr($input) {
        $ret = array();

        if(! empty($input['load_id']) && ! empty($input['load_module'])) {
        	$rel_id = $input['load_id'];
            $module = $input['load_module'];
        }
        else if(! empty($input['parent_id']) && ! empty($input['parent_type'])) {
        	$rel_id = $input['parent_id'];
            $module = $input['parent_type'];
        }
        else
        	return $ret;
        
		$contact_model = AppConfig::module_primary_bean($module);

		if ($contact_model) {
			$ret['rel']['parent_id'] = $rel_id;
			$ret['rel']['parent_type'] = $module;
			$ret['to'] = '';

            if ($contact_model == 'aCase') {
                $case = ListQuery::quick_fetch_row('aCase', $rel_id, array('cust_contact_id'));
                if (! empty($case['cust_contact_id'])) {
                    $contact_model = 'Contact';
                    $rel_id = $case['cust_contact_id'];
                }
            }

			if($contact_model == 'User' || $contact_model == 'Contact' || $contact_model == 'Lead' || $contact_model == 'Prospect') {
				$contact = ListQuery::quick_fetch_row($contact_model, $rel_id, array('first_name', 'last_name', 'email1', 'email2'));
	
				if ($contact != null) {
					$addr = array_get_default($contact, 'email1', $contact['email2']);
					if($addr) {
						$ret['to'] = trim($contact['first_name'] .' '. $contact['last_name'] .' <'. $addr .'>');
					}
				}
			}
		}

        return $ret;
    }

    /**
     * Initialize default values or values from request for new record
     *
     * @param RowUpdate $upd
     * @param  array $input - user input($_REQUEST)
     * @return void
     */
    static function init_default(RowUpdate &$upd, $input) {
		global $current_user;
        $update = array();
        
        $recip = self::load_to_addr($input);
        if($recip) {
        	array_extend($update, $recip['rel']);
        	$update['to_addrs'] = $recip['to'];
        }

        $update['from_addr'] = $current_user->getPreference('mail_fromaddress');
        $update['from_name'] = $current_user->getPreference('mail_fromname');
        $auto_bcc = $current_user->getPreference('mail_autobcc_address');
        $update['type'] = array_get_default($input, 'type', self::DEFAULT_EMAIL_TYPE);

        if(empty($update['from_name']))
            $update['from_name'] = $current_user->name;
        if(empty($update['from_addr'])) {
            if (!empty($current_user->email1)) {
                $update['from_addr'] = $current_user->email1;
            } elseif (!empty($current_user->email2)) {
                $update['from_addr'] = $current_user->email2;
            }
        }

        if($auto_bcc)
            $update['bcc_addrs'] = '<'.$auto_bcc.'>';

        if (isset($input['contact_id']))
            $update['contact_id'] = $input['contact_id'];
        if (isset($input['to_email_addrs']))
            $update['to_addrs'] = $input['to_email_addrs'];

        if (isset($update['parent_type']) && $update['parent_type'] == 'Cases' && ! empty($update['parent_id'])) {
            $case_subject = self::get_case_subject($update['parent_id']);
            if ($case_subject)
                $update['name'] = format_reply_subject($case_subject);
        }
        
        $upd->set($update);
    }

    /**
     * If 'reply' email init reply fields
     *
     * @param RowUpdate $upd
     * @param  array $input - user input($_REQUEST)
     * @return void
     */
    static function init_reply(RowUpdate &$upd, $input) {
    	global $current_user;
    	
    	$parent = ListQuery::quick_fetch('Email', $input['reply_to'],
    		array('id', 'name', 'message_id', 'description', 'description_html', 'date_start',
    			'to_addrs', 'cc_addrs', 'reply_to_addr', 'reply_to_name', 'from_addr', 'from_name',
                'parent')
    	);
    	if($parent) {
    		// FIXME - check ACL - can view?

    		if(! $upd->getField('parent_id') && $parent->getField('parent_id')) {
    			$upd->set('parent_type', $parent->getField('parent_type'));
    			$upd->set('parent_id', $parent->getField('parent_id'));
    		}

            $subject = $parent->getField('name');
            if ($upd->getField('parent_type') == 'Cases' && $upd->getField('parent_id')) {
                $case_subject = self::get_case_subject($upd->getField('parent_id'), $subject);
                if ($case_subject)
                    $subject = $case_subject;
            }

            $update['name'] = format_reply_subject($subject);
    		$update['in_reply_to'] = $parent->getField('message_id');
		
			$descriptions = array(
				'plain' => $parent->getField('description'),
				'html' => $parent->getField('description_html'),
			);
			$update['description_html'] = format_reply_body($parent, $descriptions, $current_user->getPreference('email_compose_format') == 'html');
			$exclude_addrs = array_filter(array($current_user->email1, $current_user->email2));

			if(empty($input['reply_all'])) {
				$recipients = array();
				$cc = $bcc = array();
			} else {
				$recipients = parse_addrs($parent->getField('to_addrs'));
				$cc = normalize_emails_array(parse_addrs($parent->getField('cc_addrs')), $exclude_addrs);
				$bcc = normalize_emails_array(parse_addrs($parent->getField('bcc_addrs')), $exclude_addrs);
			}

			$reply_to_addr = $parent->getField('reply_to_addr');
			$reply_to_name = $parent->getField('reply_to_name');
			if(! empty($reply_to_addr)) {
				array_unshift($recipients, format_email_row($reply_to_addr, $reply_to_name));
			} else {
				array_unshift($recipients, format_email_row($parent->getField('from_addr'), $parent->getField('from_name')));
			}
			$recipients = normalize_emails_array($recipients, $exclude_addrs);

			$recipients = assign_email_fields('to_addrs', $recipients);
			$cc = assign_email_fields('cc_addrs', $cc);
			
			$update['to_addrs'] = $recipients['to_addrs'];
			$update['cc_addrs'] = $cc['cc_addrs'];
		}

		$update['type'] = 'out';
		$upd->set($update);
    }

    /**
     * If 'forward' email init formard fields
     *
     * @param RowUpdate $upd
     * @param  array $input - user input($_REQUEST)
     * @return void
     */
    static function init_forward(RowUpdate &$upd, $input) {
    	global $current_user;
    	
        $update = array();
        
    	$parent = ListQuery::quick_fetch('Email', $input['forward'],
    		array('id', 'name', 'message_id', 'description', 'description_html', 'date_start',
    			'to_addrs', 'cc_addrs', 'reply_to_addr', 'reply_to_name', 'from_addr', 'from_name')
    	);
    	if($parent) {
    		// FIXME - check ACL - can view?
    		
    		$update['name'] = format_forward_subject($parent->getField('name'));

			$descriptions = array(
				'plain' => $parent->getField('description'),
				'html' => $parent->getField('description_html'),
			);
			$update['description_html'] = format_reply_body($parent, $descriptions, $current_user->getPreference('email_compose_format') == 'html');
		}
		
		$update['type'] = 'out';
        $upd->set($update);
    }

	static function mark_read_on_view(DetailManager $mgr) {
		if(isset($_REQUEST['mark_unread']) && $_REQUEST['mark_unread'] == 1) return;
		$is_read = $mgr->record->getField('isread');

		if(! $is_read) {
            $upd = RowUpdate::for_result($mgr->record);
            $upd->set('isread', 1);
            $upd->save();
		}
	}

    static function get_case_subject($case_id, $subject = '') {
        $subj = AppConfig::setting('company.case_email_subject', 'Case ID#: nnnn');
        $case = ListQuery::quick_fetch_row('aCase', $case_id, array('name', 'case_number', 'cust_contact_id'));
        $result = '';

        if ($case != null) {
            $number = str_replace('nnnn', $case['case_number'], $subj);
            if ($subject == '' || strpos($subject, $number) === false)
                $result = $case['name'] . ' [' . $number . ']';
        }

        if ($result != '' && $subject != '')
            $result .= ' ' .$subject;

        return $result;
    }

    static function send_notification(RowUpdate $upd) {
        $vars = array(
            'EMAIL_SUBJECT' => array('field' => 'name', 'in_subject' => true),
            'EMAIL_DATESENT' => array('field' => 'date_start')
        );

        $manager = new NotificationManager($upd, 'EmailAssigned', $vars);

        if ($manager->wasRecordReassigned())
            $manager->sendMails();
    }
    
    static function has_attach($spec, $table, $for_order=false) {
		global $db;
		$idf = $db->quoteField('id', $table);
		$notes = $db->quoteField(AppConfig::setting("model.detail.Note.table_name"));
		return "(SELECT COUNT(notes.id) FROM $notes notes WHERE parent_type='Emails' AND parent_id=$idf)";
	}

	static function mutateListButtons($mu, &$buttons)
	{
		if (array_get_default($mu->current_filter, 'folder') == EmailFolder::get_std_folder_id(AppConfig::current_user_id(), STD_FOLDER_TRASH)) {
			$buttons['delete'] = array(
				'vname' => 'LBL_DELETE_BUTTON_LABEL',
				'accesskey' =>  'LBL_DELETE_BUTTON_KEY',
				'order' => 30,
				//'confirm' => 'NTC_DELETE_CONFIRMATION_MULTIPLE',
				'list_id' => $mu->list_id,
				'params' => array(
					'mass_perform' => 'delete',
				),
				'acl' => 'delete',
				'icon' =>'icon-delete',
			);
		} else {
            $buttons['trash'] = array(
                'vname' => 'LBL_TRASH_BUTTON_LABEL',
                'accesskey' =>  'LBL_DELETE_BUTTON_KEY',
                'order' => 30,
                //'confirm' => 'NTC_TRASH_CONFIRMATION',
                'list_id' => $mu->list_id,
                'params' => array(
                	'mass_perform' => 'trash',
                ),
                'acl' => 'edit',
                'icon' =>'icon-delete',
            );
        }
	}
	static function mutateDetailButtons($detail, &$buttons)
	{
		$buttons['delete'] = array(
			'vname' => 'LBL_DELETE_BUTTON_LABEL',
			'accesskey' =>  'LBL_DELETE_BUTTON_KEY',
			'order' => 30,
			'confirm' => 'NTC_DELETE_CONFIRMATION',
			'icon' =>'icon-delete',
			'acl' => 'delete',
			'params' => array(
				'record_perform' => 'delete',
				'type' => 'button',
			),
			'hidden' => 'bean.folder_ref.reserved!=4',
		);
		$buttons['trash'] = array(
			'vname' => 'LBL_TRASH_BUTTON_LABEL',
			'order' => 30,
			'confirm' => 'NTC_TRASH_CONFIRMATION',
			'icon' =>'icon-delete',
			'acl' => 'edit',
			'params' => array(
				'action' => 'Trash',
				'type' => 'button',
			),
			'hidden' => 'bean.folder_ref.reserved=4',
		);
		$buttons['untrash'] = array(
			'vname' => 'LBL_UNTRASH_BUTTON_LABEL',
			'order' => 35,
			'icon' =>'icon-delete',
			'acl' => 'edit',
			'type' => 'group',
			'hidden' => 'bean.folder_ref.reserved!=4',
		);
		$folders = EmailFolder::get_folders_list(AppConfig::current_user_id());
		foreach ($folders as $folder) {
			if ($folder['reserved'] == STD_FOLDER_TRASH) continue;
			$buttons['untrash_' . $folder['id']] = array(
				'label' => $folder['name'],
				'order' => 1,
				'icon' =>'bean-EmailFolder theme-icon',
				'acl' => 'edit',
				'params' => array(
					'action' => 'Trash',
					'untrash_to' => $folder['id'],
					'type' => 'button',
				),
				'group' => 'untrash',
				'hidden' => 'bean.folder_ref.reserved!=4',
			);
		}

		$row = ListQuery::quick_fetch_row('Email', $detail->record_id, array('folder', 'type', 'status'));
		if ($row && $row['type'] != 'out' && $row['status'] != 'draft') {
			if ($row['folder'] == EmailFolder::get_std_folder_id(-1, STD_FOLDER_INBOX)) {
				$buttons['take_return'] = array(
					'vname' => 'LBL_TAKE_FROM_GROUP',
					'params' => array(
						'take_return' => 'Take',
						'action' => 'TakeReturn',
					),
				);
			} else {
				$buttons['take_return'] = array(
					'vname' => 'LBL_RETURN_TO_GROUP',
					'params' => array(
						'take_return' => 'Return',
						'action' => 'TakeReturn',
					),
				);
			}
		}
	}
	
	static function edit_redirect(DetailManager $mgr) {
		if($mgr->getPerform() != 'save') return;
		$upd = $mgr->getRowUpdate();
		if($upd && $upd->getField('status') == 'draft') {
			// redirect to drafts folder
			$folder = EmailFolder::get_std_folder_id(AppConfig::current_user_id(), STD_FOLDER_DRAFTS);
			$mgr->setCustomRedirect('index.php?module=Emails&action=index&layout=Browse&folder='.$folder.'&query=true');
		}
	}
	
	static function set_default_folder(RowUpdate $upd) {
		if($upd->new_record && ! $upd->getField('folder')) {
			$status = $upd->getField('status');
			$uid = $upd->getField('assigned_user_id', AppConfig::current_user_id());
			if($status == 'sent')
				$folder = EmailFolder::get_std_folder_id($uid, STD_FOLDER_SENT);
			else if($status == 'draft')
				$folder = EmailFolder::get_std_folder_id($uid, STD_FOLDER_DRAFTS);
			else
				$folder = EmailFolder::get_std_folder_id($uid, STD_FOLDER_INBOX);
			if($folder)
				$upd->set('folder', $folder);
			if($status == 'sent' || $status == 'draft' || $status == 'archived') {
				if($upd->getField('isread') === null)
					$upd->set('isread', 1);
			}
		}
	}
	
	static function massupdate_trash($mu, $perform, &$listFmt, &$list_result, $uids) {
		if ($perform == 'trash' && $list_result) {
			require_once 'modules/EmailFolders/EmailFolder.php';
			$trash_folder = EmailFolder::get_std_folder_id(AppConfig::current_user_id(), STD_FOLDER_TRASH);
			
			while(! $list_result->failed) {
				foreach($list_result->getRowIndexes() as $idx) {
					$row = $list_result->getRowResult($idx);
					$src = $row->getField('folder_ref.reserved');
					if($src && $src != STD_FOLDER_TRASH) {
						$upd = RowUpdate::for_result($row);
						$upd->set('folder', $trash_folder);
						$upd->save();
					}
				}
				if($list_result->page_finished)
					break;
				$listFmt->pageResult($list_result, true);
			}

		}
	}
	
	static function listupdate_perform($mu, $perform, &$listFmt, &$list_result, $uids)
	{
		if ($perform == 'EmailMultiple') {
			$_REQUEST['email_multi'] = 1;
			$_REQUEST['action_module'] = $list_result->module_dirs[0];
			$ids = array();
			while(! $list_result->failed) {
				foreach($list_result->getRowIndexes() as $idx) {
					$row = $list_result->getRowResult($idx);
					$ids[] = $module_id = $row->getField('id');
				}
				if($list_result->page_finished)
					break;
				$listFmt->pageResult($list_result, true);
			}
			$_REQUEST['list_uids'] = join(';', $ids);

			return array(
				'perform', 
				array(
					'module' => 'Emails',
					'action' => 'EditView',
					'layout' => 'Standard', 
				),
			);
		}
	}
}

