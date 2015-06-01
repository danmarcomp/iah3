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


 /*********************************************************************************
 * $Id: $
 ********************************************************************************/

require_once('include/MailClient/EmailClient.php');
require_once('include/MailClient/IAHEmailParser.php');
require_once('modules/EmailPOP3/EmailPOP3.php');
require_once('modules/EmailFolders/EmailFolder.php');
require_once('modules/Campaigns/ProcessBouncedEmails.php');
require_once 'modules/Emails/utils.php';


class IAHEmailImportError extends IAHInternalError {
	var $error_name = 'Email Import Error';
}


// called by scheduler
function run_email_import() {
	$import = new EmailImport;
	$import->run();
}

function process_campaign_bounces() {
	$import = new EmailImport;
	$import->process_campaign_bounces = true;
	$import->run();
}


class EmailImport
{
	var $db;
	var $accounts;
	var $current_account;
	var $state;
	var $boxhash;
	var $client;

	var $messages_count = 0;
	var $dl_start_time;
	var $break_on = '';
	var $break_state= '';
	var $max_duration;
	var $max_messages;

	var $email_counts = array();
	var $header_cache = array();
	var $seen_cache = array();

	var $archive = false;
	var $check_for_user = false;
	var $limit_scheduled = true;

	function EmailImport($archive = false, $for_user = null, $limit_scheduled = true)
	{
		global $db, $log;
		$this->db =& $db;
		$this->logger =& $log;
		$this->log =& $this;
		$this->archive = $archive;
		if($for_user === true)
			$for_user = AppConfig::current_user_id();
		$this->check_for_user = $for_user;
		$this->limit_scheduled = $limit_scheduled;
		$this->max_duration = AppConfig::setting('email.import.max_session_duration');
		$this->max_messages = AppConfig::setting('email.import.max_session_messages');
		if (!$archive) {
			$this->setup();
		}
	}

	function run()
	{
		$GLOBALS['NEW_EMAILS_COUNT'] = 0;
		$this->dl_start_time = time();
		while ($this->state) {
			$this->log->debug('State: ' . $this->state);
			if (!$this->break_on && ($this->messages_count > $this->max_messages || time() - $this->dl_start_time >= $this->max_duration)) {
				if ($this->messages_count > $this->max_messages) {
					$this->log->warn(
						'Hit maximum message download limit ('
						. $this->max_messages . ' messages), finishing.'
					);
				} else {
					$this->log->warn(
						'Hit message download time limit ('
						. $this->max_duration . ' seconds), finishing.'
					);
				}
				if ($this->break_state) {
					$this->state = $this->break_state;
				}
				$this->break_on = 'get_account';
			}

			if ($this->break_on == $this->state) {
				break;
			}

			$method = 'state_' . $this->state;
			if (method_exists($this, $method)) {
				$this->$method();
			} else {
				$this->log->fatal('Unknown state : ' . $this->state);
			}
			if ($this->check_for_user) {
				$this->update_mail_counts();
			}
		}
	}
	
	function connection_test($mailbox_id) {
		if(! $mailbox_id || ! isset($this->accounts[$mailbox_id]))
			throw new IAHEmailImportError('Mailbox not found');
		$this->current_account = $this->accounts[$mailbox_id];
		$status = $this->do_connect();
		if(! $status) {
			throw new IAHEmailImportError('Connection error: '.$this->client->lastError);
		}
		$status = $this->do_select_folder($folder_name);
		if(! $status) {
			@$this->client->disconnect();
			throw new IAHEmailImportError("Error selecting folder '$folder_name': " . $this->client->lastError);
		}
		$this->client->disconnect();
		return true;
	}

	function setup()
	{
		$query = "SELECT emails_pop3.*, IFNULL(users.user_name,'') iah_user_name, users.email1, users.email2 FROM emails_pop3 LEFT JOIN users ON emails_pop3.user_id=users.id WHERE (!users.deleted OR emails_pop3.user_id='-1') AND !emails_pop3.deleted AND emails_pop3.active";
		if ($this->check_for_user) {
			$query .= " AND emails_pop3.user_id = '" . $this->check_for_user . "'";
		} else if($this->limit_scheduled) {
			$query .= ' AND emails_pop3.scheduler';
		}
		$query .= ' ORDER BY IFNULL(emails_pop3.last_check, 0)';
		$result = $this->db->query($query, true, "Error getting POP accounts list: ");
		$this->accounts = array();
		while ($account = $this->db->fetchByAssoc($result, -1, false)) {
			$addrs = array($account['email'], $account['email1'], $account['email2']);
			$addrs = array_map('strtolower', array_map('trim', $addrs));
			$account['all_addresses'] = array_filter($addrs);
			$this->accounts[$account['id']] = $account;
		}
		reset($this->accounts);
		$this->state = 'get_account';
	}

	function state_get_account()
	{
		global $current_user;
		$this->break_state = '';
		$this->current_account = current($this->accounts);
		while ($this->current_account && (($this->current_account['mailbox_type'] == 'bounce') ^ !empty($this->process_campaign_bounces))) {
			$this->current_account = next($this->accounts);
		}
		next($this->accounts);
		if ($this->current_account) {
			$this->log->debug('Next account: ' . $this->current_account['host'] . ':' . $this->current_account['username']);
			$this->markPointReached = $this->current_account['last_id'] == '!';
			$this->markPoint = $this->current_account['last_id'];
			if ($this->markPointReached) {
				$this->debug('No markpoint from last run');
			} else {
				$this->debug('Markpoint from last run : ' . $this->markPoint);
			}
	        $current_user = new User;
    	    if ($this->current_account['user_id'] != -1) {
        	    $current_user->retrieve($this->current_account['user_id']);
	        } else {
    	        $current_user->id = -1;
	        }
    	    $this->boxhash = md5($this->current_account['host'] . "\n" . $this->current_account['username']);
			$this->state = 'connect';

		} else {
			$this->log->debug('No more accounts to process');
			$this->state = '';
		}
	}
	
	function do_connect() {
		$params = array(
			'host' => $this->current_account['host'],
			'username' => $this->current_account['username'],
			'password' => $this->current_account['password'],
			'port' => $this->current_account['port'],
			'login_method' => 'USER',
			'last_check' => $this->current_account['last_check'],
			'use_ssl' => $this->current_account['use_ssl'],
			'folder' => $this->current_account['imap_folder'],
			'mark_read' => $this->current_account['mark_read'],
		);
		$this->client =& EmailClient::create($this->current_account['protocol'], $params);
		$this->log->debug("Email client class: ".get_class($this->client));
        return $this->client->connect();
	}

	function state_connect()
	{
		$this->break_state = 'save_markpoint';
		$status = $this->do_connect();
        if (!$status) {
			$this->log->error(
				'Error connecting to mail server '
					. $this->current_account['host']
					. ':' . $this->current_account['port']
					. ' (' . $this->client->lastError
					. ') for user '
					. $this->current_account['iah_user_name']
					. ', account ' . $this->current_account['id']
			);
            $this->state = 'get_account';
		} else {
	    	$this->log->debug('Connected to ' . $this->current_account['host']);
            $this->state = 'login';
		}
		$dt = gmdate('Y-m-d H:i:s');
		$query = "UPDATE emails_pop3 SET last_check='$dt' WHERE id='{$this->current_account['id']}'";
		$this->db->query($query, false);
	}

	function state_login()
	{
        $status = $this->client->login();
        if (!$status) {
			$this->log->error(
				'Error logging into '
				. $this->current_account['host']
				. ' as ' . $this->current_account['username']
				. ' for user ' . $this->current_account['iah_user_name']
			);
            $this->state = 'disconnect';
        } else {
			$this->log->debug(
				'Logged into ' . $this->current_account['host']
				. ' as ' . $this->current_account['username']
			);
            $this->state = 'select_folder';
		}
	}
	
	function do_select_folder(&$folder_name) {
		if (!empty($this->current_account['imap_folder']) && $this->current_account['imap_folder'] != 'INBOX') {
			$folder_name = $this->current_account['imap_folder'];
            return $this->client->selectBox($this->current_account['imap_folder']);
        } else
        	$folder_name = 'INBOX';
        return true;
	}

	function state_select_folder()
	{
		$status = $this->do_select_folder($folder_name);
        if (!$status) {
			$this->log->error('Error selecting folder ' . $folder_name);
			$this->state = 'disconnect';
		} else {
			$this->log->debug('Selected folder ' . $folder_name);
			$this->state = 'num_messages';
		}
	}

	function state_num_messages()
	{
		$since = null;
		if(! empty($this->current_account['restrict_since'])) {
			$since_str = EmailPOP3::restrict_since_time($this->current_account['restrict_since']);
			if($since_str)
				$since = date('j-M-Y', strtotime($since_str));
		}
        $this->nMessages = $this->client->numMsg($since);
        if ($this->nMessages === false) {
			$this->log->warn(
				'Error getting number of messages from '
				. $this->current_account['host'] . ' as '
				. $this->current_account['username'] . ' for user ' . $this->current_account['iah_user_name']
				. ': ' . $this->client->lastError
			);
            $this->state = 'disconnect';
        } else {
			$this->log->info(
				$this->nMessages . ' messages on ' .$this->current_account['host']
				. ' for ' . $this->current_account['username']
			);
			$this->msg_number = $this->nMessages + 1;
			$this->state = 'process_next_message';
		}
	}

	function state_process_next_message()
	{
		$this->msg_number --;
		$this->markPoint = '!';
		if ($this->msg_number < 1) {
			if(! $this->markPointReached && $this->nMessages > 0) {
				$this->log->debug('Previous markpoint not found, downloading all messages');
				$this->markPointReached = true;
				$this->msg_number = $this->nMessages + 1;
			} else {
				$this->log->debug('No more messages to process');
				$this->messageId = '!';
				$this->state = 'save_markpoint';
			}
		} else {
			$this->log->debug('Starting processing message # ' . $this->msg_number);
			$this->state = 'get_msg_id';
		}
	}

	function state_disconnect()
	{
		$this->client->disconnect();
		$this->log->debug('Disconnected');
		$this->state = 'get_account';
	}

	function state_get_msg_id()
	{
		$headers = $this->getMessageHeaders($this->msg_number);
		$messageId = '';
		$m = array();
		if (preg_match('/^message-id:(.*)$/mi', $headers, $m)) {
			$messageId = trim($m[1]);
		}
		if (empty($messageId)) {
			$messageId = 'IAH-' . md5($headers);
		}
		$this->messageId  = $messageId;
		$this->debug('Message ID : ' . $this->messageId);

		$this->state = 'check_markpoint';
	}

	function state_check_markpoint()
	{
		if (!$this->markPointReached && $this->current_account['last_id'] == $this->messageId) {
			$this->markPointReached = true;
			$this->log->debug('Reached markpoint');
		} elseif (!$this->markPointReached) {
			$this->log->debug('Markpoint not reached');
		}
		$this->state = 'check_seen_status';
	}

	function state_check_seen_status()
	{
		if ($this->seenMessageID()) {
			if (!$this->markPointReached) {
				$this->log->debug('Message already seen - skipping');
				$this->state = 'process_next_message';
			} else {
				$this->log->debug('Message already seen - account check finished');
				$this->messageId = '!';
				$this->state = 'save_markpoint';
			}
		} else {
			$this->log->debug('Message not seen before');
			$this->state = 'fetch_message';
			$this->messages_count++;
		}
	}

	function state_fetch_message()
	{
		$this->message = $this->client->getMsg($this->msg_number);
		if ($this->message === false) {
			$this->log->warn(
				'Error getting message ' . $this->msg_number
				. ' from ' . $this->current_account['host']
				. ' as ' . $this->current_account['username']
			);
			$this->state = 'save_markpoint';
		} else {
			$this->log->debug(
				'Recieved message ' . $this->msg_number
				. ' from ' . $this->current_account['host']
				. ' as ' . $this->current_account['username']
			);
			$this->state = 'import_message';
		}
	}

	function state_import_message()
	{
		if (!$this->import_message()) {
			$this->log->warn('Error importing message');
		} else {
			$this->log->debug('Message imported');
		}
		$this->state = 'store_id';
	}

	function state_store_id()
	{
		$query = sprintf(
        	'INSERT INTO emails_uidl SET boxhash=\'%s\', uidl=\'%s\', message_id=\'%s\'',
			PearDatabase::quote($this->boxhash), uniqid(''), PearDatabase::quote($this->messageId)
		);
		$this->db->query($query);
		$this->seen_cache[$this->boxhash . $this->messageId] = true;
		$this->state = 'delete_message';
	}

	function state_delete_message()
	{
		if (!$this->current_account['leave_on_server']) {
			if (!$this->client->deleteMsg($this->msg_number)) {
				$this->log->warn(
					'Error marking message ' . $this->msg_number
					. ' as deleted at ' . $this->current_account['host']
					. ' as ' . $this->current_account['username']
				);
				$this->state = 'save_markpoint';
			} else {
				$this->log->debug('Marked message for deletion');
				$this->state = 'process_next_message';
			}
		} else {
			$this->log->debug('Message left on server');
			$this->state = 'process_next_message';
		}
	}

	function state_save_markpoint()
	{
		$this->db->query("UPDATE emails_pop3 SET last_id='".PearDatabase::quote($this->messageId)."' WHERE id='{$this->current_account['id']}'");
		$this->state = 'disconnect';
	}




	function getMessageHeaders($msgNum)
	{
		$key = $this->boxhash . $msgNum;
		if(! isset($this->header_cache[$key]))
			$this->header_cache[$key] = $this->client->getHeaders($msgNum);
		return $this->header_cache[$key];
	}

	function seenMessageID()
	{
		$key = $this->boxhash . $this->messageId;
		if (empty($this->messageId)) return false;
		if(! isset($this->seen_cache[$key])) {
			$mid = PearDatabase::quote(substr($this->messageId, 0, 100));
			$query = "SELECT COUNT(*) AS n FROM emails_uidl WHERE message_id='" . $mid . "' AND boxhash='$this->boxhash'";
			$res = $this->db->query($query);
			$row = $this->db->fetchByAssoc($res);
			$this->seen_cache[$key] = !empty($row['n']);
		}
		return $this->seen_cache[$key];
	}

	function import_message($message = null, $user = null) /* {{{ */
	{
		$parse = new IAHEmailParser();
		try {
			$parse->loadSource($this->archive ? $message : $this->message);
		}
		catch(IAHEmailParseError $e) {
			$this->log->warn('Email parse error: '.$e->getMessage());
			return false;
		}
	
		if ($this->archive) {
        	$user_id = $user->id;
	        $mailbox_id = null;
	        $folder_id = EmailFolder::get_std_folder_id($user_id, STD_FOLDER_INBOX);
	        $status = null; // ?
	        $intent = null;
		} else {
			$user_id = $this->current_account['user_id'];
			$mailbox_id = $this->current_account['id'];
			$folder_id = $this->current_account['email_folder_id'];
			if(! $folder_id && $this->current_account['mailbox_type'] == 'bounce')
				$folder_id = EmailFolder::get_std_folder_id($this->current_account['user_id'], STD_FOLDER_CAMPAIGN);
			if(! $folder_id)
				$folder_id = EmailFolder::get_std_folder_id($this->current_account['user_id'], STD_FOLDER_INBOX);
			$status = 'received';
			$intent = $this->current_account['mailbox_type'];
			if ($this->current_account['mailbox_type'] == 'bounce') {
				if(campaign_process_bounced_emails($parse)) {
					// email not saved
					$this->log->debug("Processed bounce email ({$parse->from[2]})");
					return true;
				}
			}
		}
		
		$email = $parse->getEmailRecord($user_id, $mailbox_id, $folder_id);
		if($status) $email->set('status', $status);
		$email->set('intent', $intent ? $intent : 'pick');
		
		$related = $parse->findRelatedRecords();
		$add_relate = array();
		$acc = $contact = null;
		$from_this_user = false;
		foreach($related as $addr => $rels) {
			if(in_array($addr, $this->current_account['all_addresses'])) {
				continue; // never add things related to the user's address
			}
			else {
				if(isset($rels['Accounts'])) {
					$add_relate = array_merge($add_relate, $rels['Accounts']);
					if (!isset($acc))
						$acc = $rels['Accounts'][0];
				}

				if(isset($rels['Contacts'])) {
					$add_relate = array_merge($add_relate, $rels['Contacts']);
					if(! isset($contact)) {
						$contact = $rels['Contacts'][0];
						$email->set('contact_id', $contact['id']);
					}
				}
				if(isset($rels['Leads']))
					$add_relate = array_merge($add_relate, $rels['Leads']);
			}
			if(isset($rels['Users']))
				$add_relate = array_merge($add_relate, $rels['Users']);
		}

		if (!$this->archive) {
			$pattern = AppConfig::setting('company.case_email_subject', 'Case ID#: nnnn');
			$pattern = preg_quote($pattern, '~');
			$pattern = preg_replace('~\s+~', '\\s+', $pattern);
			$pattern = str_replace ('nnnn', '(\\d+)', $pattern);
			$case_matched = $case_found = null;
			if ($pattern && preg_match("~$pattern~", $parse->getSubject(), $m)) {
				$case_matched = true;
				$case_number = $m[1];
				
				$lq = new ListQuery('aCase');
				$lq->addSimpleFilter('case_number', $case_number);
				$case_result = $lq->runQuerySingle();
				if ($case_result && ! $case_result->failed) {
					$add_relate[] = array(
						'type' => 'Cases',
						'id' => $case_result->getField('id'),
						'rel_name' => 'cases',
					);
					$case_found = true;
				}
			}
        
        	if(! isset($queue_user_loaded)) {
				$queue_user_id = AppConfig::setting('company.case_queue_user');
				if(! $queue_user_id) {
					// select first admin user
					$query = "SELECT id FROM users WHERE is_admin AND status='Active' LIMIT 1";
					$result = $this->db->query($query, false);
					$row = $this->db->fetchByAssoc($row);
					$queue_user_id = $row['id'];
				}
				$queue_user_loaded = true;
			}
			$create_for_user =
				($this->current_account['user_id'] != '-1') ? $this->current_account['user_id'] :
				$queue_user_id;
			if($this->current_account['mailbox_type'] == 'support' && ! $case_matched) {
				$case = RowUpdate::blank_for_model('aCase');
				if($contact) {
					$case->set('cust_contact_id', $contact['id']);
					$case->set('cust_phone_no', $contact['phone_work']);
				}
				if($acc)
					$case->set('account_id', $acc['id']);
				$case->set(array(
					'name' => $parse->getSubject(),
					'description' => $parse->getTextBody(),
					'assigned_user_id' => $create_for_user,
					'created_by' => $queue_user_id,
				));
				if($case->save()) {
					$add_relate[] = array(
						'type' => 'Cases',
						'id' => $case->getPrimaryKeyValue(),
						'rel_name' => 'cases',
					);
				}
			} else if($this->current_account['mailbox_type'] == 'bug') {
				$bug = RowUpdate::blank_for_model('Bug');
				if($contact)
					$bug->set('contact_id', $contact['id']);
				if($acc)
					$bug->set('account_id', $acc['id']);
				$bug->set(array(
					'name' => $parse->getSubject(),
					'description' => $parse->getTextBody(),
					'assigned_user_id' => $create_for_user,
					'created_by' => $queue_user_id,
				));
				if($bug->save()) {
					$add_relate[] = array(
						'type' => 'Bugs',
						'id' => $bug->getPrimaryKeyValue(),
						'rel_name' => 'bugs',
					);
				}
			}
		}

		if(! $parse->fromNoRelateDomain()) {
			$thread_id = $parse->findEmailThread();
			if(! $thread_id) $thread_id = create_guid();
			$email->set('thread_id', $thread_id);
		}


		if ($email->getField('in_reply_to')) {
			$prev_email = ListQuery::quick_fetch_key('Email', 'message_id', $email->getField('in_reply_to'));
			if ($prev_email && !$prev_email->failed) {
				$email->set(array(
					'parent_id' => $prev_email->getField('parent_id'),
					'parent_type' => $prev_email->getField('parent_type'),
				));
			}
		}

		if (! $email->getField('parent_id') && count($add_relate)) {
			$i = 0;
			foreach($add_relate as $rel) {
				if($rel['type'] == 'Users' || $rel['type'] == 'Contacts')
					continue;
				if($i == 0 || ($rel['type'] == 'Cases' || $rel['type'] == 'Bugs')) {
					$email->set(array(
						'parent_type' => $rel['type'],
						'parent_id' => $rel['id'],
					));
				}
				$i++;
			}
		}

		$email->save();
		$email_id = $email->getPrimaryKeyValue();
		$parse->saveAttachments($email_id, $user_id);

		if (! $this->archive && AppConfig::setting('email.keep_raw')) {
			$this->storeRawMessage($parse->message_id, $parse->source);
		}

		foreach ($add_relate as $rel) {
			$email->addUpdateLink($rel['rel_name'], $rel['id']);
		}

		if (! $this->archive) {
			$GLOBALS['NEW_EMAILS_COUNT']++;
			if (!isset($this->email_counts[$this->current_account['user_id']])) {
				$this->email_counts[$this->current_account['user_id']] = 0;
			}
			$this->email_counts[$this->current_account['user_id']]++;
		}
		

		///////////////////////////////////////////////////////////
		// 
		//         Auto-reply
		//
		///////////////////////////////////////////////////////////
		if (!$this->archive) {	
			global $current_user;
			$out_of_office = $current_user->getPreference('out_of_office');
			if((!$out_of_office && $this->current_account['template_id']) || ($out_of_office && $this->current_account['ooo_template_id'])) {
				if($parse->reply_to[0]) {
					$contactAddr = $parse->reply_to[0];
					$contactName = $parse->reply_to[1];
				} else {
					$contactAddr = $parse->from[0];
					$contactName = $parse->from[1];
				}
				if($this->getAutoreplyStatus($contactAddr) 
				&& $this->checkOutOfOffice($parse->getSubject()) 
				&& $this->checkFilterDomain(array($parse->from[0], $parse->reply_to[0]))) { // if we haven't sent this guy 10 replies in 24hours
				
					if(!empty($this->current_account['from_name'])) {
						$from_name = $this->current_account['from_name'];
					} else { // use system default
						$from_name = AppConfig::setting('notify.from_name', '');
					}
					if(!empty($this->current_account['email'])) {
						$from_addr = $this->current_account['email'];
					} else {
						$from_addr = AppConfig::setting('notify.from_address', '');
					}

					$to = array(
						array(
							'email' => $contactAddr,
							'display' => $contactName,
							'entry' => IAHEmailParser::join_email_name($contactAddr, $contactName),
						),
					);
					require_once('modules/EmailTemplates/EmailTemplate.php');
					$et = new EmailTemplate();
					$et->retrieve($out_of_office ? $this->current_account['ooo_template_id'] : $this->current_account['template_id']);
					if(empty($et->body))		{ $et->body = ''; }
					if(empty($et->body_html))	{ $et->body_html = ''; }
					
					$reply = new Email();
					$reply->type				= 'out';
					$reply->to_addrs			= to_html($to[0]['entry']);
					$reply->to_addrs_arr		= $to;
					$reply->cc_addrs_arr		= array();
					$reply->bcc_addrs_arr		= array();
					$reply->from_name			= $from_name;
					$reply->from_addr			= $from_addr;
					$reply->name				= from_html($et->subject);
					$reply->description			= from_html($et->body);
					$reply->description_html	= from_html($et->body_html);
					
					$reply->send();
					$reply->cleanup();
					$et->cleanup();
					$this->setAutoreplyStatus($contactAddr);
				} else {
					$this->log->debug('InboundEmail: auto-reply threshold reached for email ('.$contactAddr.') - not sending auto-reply');
				}
			}
		}
		
		return $email_id;
    }

	function setAutoreplyStatus($addr) {
		$this->db->query('INSERT INTO inbound_email_autoreply (id, deleted, date_entered, date_modified, autoreplied_to) VALUES (
							\''.create_guid().'\',
							0,
							\''.gmdate('Y-m-d H:i:s', strtotime('now')).'\',
							\''.gmdate('Y-m-d H:i:s', strtotime('now')).'\',
							\''.$addr.'\') ');	
	}

	function getAutoreplyStatus($from)
	{
		$q_clean = 'UPDATE inbound_email_autoreply SET deleted = 1 WHERE date_entered < \''.gmdate('Y-m-d H:i:s', strtotime('now -24 hours')).'\'';
		$r_clean = $this->db->query($q_clean);

		$q = 'SELECT count(*) AS c FROM inbound_email_autoreply WHERE deleted = 0 AND autoreplied_to = \''.PearDatabase::quote($from).'\'';
		$r = $this->db->query($q);
		$a = $this->db->fetchByAssoc($r);
		if($a['c'] >= 10) {
			$this->log->debug('Autoreply cancelled - more than 10 replies sent in 24 hours.');
			return false;
		} else {
			return true;
		}	
	}

	function checkOutOfOffice($subject) {
		$ooto = array("Out of the Office", "Out of Office");
		
		foreach($ooto as $str) {
			if(preg_match("~{$str}~i", $subject)) {
				$this->log->debug('Autoreply cancelled - found "Out of Office" type of subject.');
				return false;
			}
		}
		return true; // no matches to ooto strings
	}

	function checkFilterDomain(array $addrs)
	{
		$filterDomain = $this->current_account['filter_domain'];
		if(!isset($filterDomain) || empty($filterDomain)) {
			return true; // nothing set for this
		} else {
			$filterDomain = '@'.strtolower($filterDomain);
			foreach($addrs as $addr) {
				if($addr && strpos($addr, $filterDomain) !== false) {
					$this->log->debug('Autoreply cancelled - email address domain matches filter domain.');
					return false;
				}
			}
			return true; // no match
		}
	}

	function warn($msg)
	{
		$this->logger->warn('*EmailImport* : ' . $msg);
	}

	function info($msg)
	{
		$this->logger->info('*EmailImport* : ' . $msg);
	}

	function debug($msg)
	{
		$this->logger->debug('*EmailImport* : ' . $msg);
    }

	function error($msg)
	{
		$this->logger->error('*EmailImport* : ' . $msg);
    }

	function fatal($msg)
	{
		$this->logger->fatal('*EmailImport* : ' . $msg);
	}

	function update_mail_counts()
	{
		global $current_user;
		foreach ($this->email_counts as $id => $count) {
			$user = new User();
			if ($user->retrieve($id)) {
				$user->setPreference('new_email_count', $count, 0, 'email_import');
				$user->setPreference('new_email_timestamp', time(), 0, 'email_import');
				if(empty($current_user) || $current_user->id != $user->id)
					$user->savePreferencesToDB();
			}
			$user->cleanup();
		}
	}
	

	function storeRawMessage($messageId, $message)
	{
		$destination = get_raw_message_filename($messageId);
		$fp = @fopen($destination, 'wb');
		if ($fp) {
			@fwrite($fp, $message);
			@fclose($fp);
		}
	}
}

