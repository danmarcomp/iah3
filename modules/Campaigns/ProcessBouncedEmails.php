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

 * Description:
 ********************************************************************************/
//find all mailboxes of type bounce.

/* longreach - modified - from EmailImport we pass not full header, 
 * but fromname and fromemail concatenated -- these are the only fields
 * from header used by this function
 
function campaign_process_bounced_emails($email,$email_header) {
 
 */

// invalid email detection rules contributed by GroupWare-AG
function match_invalid_email_response(IAHEmailParser $email) {
	static $patstr;
	if(! isset($patstr)) {
		$patterns = array(
			'permanent[ ]*error', // vpopmail
			'user unknown', // postfix
			'domain not found', // postfix
			'no such user', // generic
			'host not found', // generic
			'user[ ]*not listed in domino directory', // lotus notes
			'no[t] route found to domain', // lotus notes
			'check dns', // lotus notes
			'not our customer', // comcast.net
			'mailbox not found', // aol.com
			'delivery to the following recipients failed', // hotmail
			'this user doesn\'t', // yahoo
			'destination server for this recipient could not be found', // exchange
		);
		$patstr = '/'.join('|', $patterns).'/i';
	}
	return preg_match($patstr, $email->body);
}

function match_bounced_email($from, $subject) {
	static $patstr;
	if(preg_match('/(MAILER-DAEMON|postmaster|administrator).*?@/i', $from))
		return true;
	if(! isset($patstr)) {
		$patterns = array(
			'error',
			'undeliver',
			'returned\s+mail',
			'delivery\s+fail',
			'failure\s+notice',
			'not\s+be\s+delivered',
		);
		$patstr = '/'.join('|', $patterns).'/i';
	}
	return preg_match($patstr, $subject);
}

// mark invalid email address - unimplemented in sugar 4.5.x/5.0x todate
// feature contributed by GroupWare-AG with credits to the sugar community forum
function mark_invalid_email_address($target_type, $target_id) {
	global $db;
	$target_tables = array(
		'Prospects' => 'prospects',
		'Contacts' => 'contacts',
		'Leads' => 'leads',
	);
	$tbl = array_get_default($target_tables, $target_type);
	if($tbl) {
		$update_sql = "UPDATE `$tbl` SET invalid_email=1 where id='".$db->quote($target_id)."'";
		$result = $db->query($update_sql);
		return $result;
	}
	return false;
}

/* Andrey :
 * regular expression matches used to detect bounced email are far
 * from ideal -- not all MTAs send matching text. There is a Perl
 * module in CPAN for processing bounced email -- we could learn more
 * from there, for exmple, how to detect reason of bounce
 * (invalid email/full mailbox/other....)
*/

function campaign_process_bounced_emails(IAHEmailParser $email) {
	global $timedate;

	if(match_bounced_email($email->from[0], $email->subject)) {

		//do we have the identifier tag in the email?
		
		// message body may have been wrapped
		$text = preg_replace('/[\r\n]/', '', $email->getPlainBody());
		
		$matches = array();
		if (preg_match('/removeme.php\?(.*&)?identifier=[a-z0-9\-]*/',$text,$matches)) {
			$identifiers = preg_split('/removeme.php\?(.*&)?identifier=/',$matches[0],-1,PREG_SPLIT_NO_EMPTY);
			if (!empty($identifiers)) {

				//array should have only one element in it.
				$identifier = trim($identifiers[0]);
				$lq = new ListQuery('CampaignLog');
				$lq->addSimpleFilter('activity_type', 'targeted', null, 'type');
				$lq->addSimpleFilter('target_tracker_key', $identifier, null, 'key');
				$row = $lq->runQuerySingle();
				if ($row && ! $row->failed) {
					//do not create another campaign_log record is we already have an
					//invalid email or send error entry for this tracker key.
					$lq->addSimpleFilter('activity_type', array('invalid email', 'send error'), null, 'type');
					$row_count = $lq->fetchResultCount();

					if (! $row_count) {
						$row = $row->row;
						$bounce = RowUpdate::blank_for_model('CampaignLog');
						$bounce->set(array(
							'campaign_id' => $row['campaign_id'],
							'target_tracker_key' => $identifier,
							'target_id' => $row['target_id'],
							'target_type' => $row['target_type'],
							'list_id' => $row['list_id'],

							'activity_date' => $timedate->get_gmt_db_datetime(),
							'related_type' => $row['related_type'],
							'related_id' => $row['related_id'],
							'marketing_id' => $row['marketing_id'],
							'more_information' => $row['more_information'],
						));
					
						//do we have the phrase permanent error in the email body.
						if (match_invalid_email_response($email)) {
							//invalid email address
							$bounce->set('activity_type', 'invalid email');
							
							$GLOBALS['log']->debug("campaign_process_bounced_emails() - invalid email: mark email address as invalid");
							mark_invalid_email_address($row['target_type'], $row['target_id']);
							
						} else {
							//other -bounced email.	
							$bounce->set('activity_type', 'send error');
						}
						$bounce->save();
					}
				} else {
					$GLOBALS['log']->info("Warning: skipping bounced email with this tracker_key(identifier) in the message body ".$identifier);
				}			
		} else {
			//todo mark the email address as invalid. search for prospects/leads/contact associated
			//with this email address and set the invalid_email flag... also make email available.
		}
	}  else {
		$GLOBALS['log']->info("Warning: skipping bounced email because it does not have the removeme link.");	  	
  	}
  	
	// longreach - added - signal that message can be ignored
	return true;
					
  } else {
	$GLOBALS['log']->info("Warning: skipping email, not a recognized bounce email.");
  }
  
  // longreach - added
  return false;
  
}
?>
