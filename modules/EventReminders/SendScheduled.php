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

require_once 'modules/EventReminders/EventReminder.php';
require_once 'modules/EmailTemplates/EmailTemplate.php';
require_once 'modules/EmailTemplates/TemplateParser.php';
require_once 'include/utils.php';
require_once 'include/SugarPHPMailer.php';

global $timedate;
global $current_user;
global $beanList, $beanFiles;

$er = new EventReminder;

$now = gmdate('Y-m-d H:i:s');

$query = "
	SELECT
		event_reminders.*
	FROM
		event_reminders
	WHERE  deleted = 0
	AND (
		date_send <= '$now'
		OR
		send_on_registration
	)
";

$res = $er->db->query($query, true);
while ($reminder = $er->db->fetchByAssoc($res)) {

	$query = "
		SELECT event_sessions.*, events_customers.customer_type, events_customers.customer_id, events_customers.id AS events_customers_id
		FROM events_customers
		LEFT JOIN event_sessions ON event_sessions.id = events_customers.session_id
		AND event_sessions.deleted = 0
		AND events_customers.deleted = 0
		LEFT JOIN events_reminders_tracker ON events_reminders_tracker.events_customers_id = events_customers.id AND events_reminders_tracker.event_reminder_id = '{$reminder['id']}'
		WHERE events_reminders_tracker.id IS NULL
		AND events_customers.deleted = 0
		AND event_sessions.id = '{$reminder['session_id']}'
	";
	$res2 = $er->db->query($query, true);
	while ($session = $er->db->fetchByAssoc($res2)) {
		$d = preg_split('/[- :]/', $session['date_start']);
		$date_send = $reminder['date_send'];
		$query = "
			INSERT INTO email_queue
			(id, related_type, related_id, recipient_type, recipient_id, template_id, send_on, date_modified)
			VALUES
			('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
			";
		$query = sprintf($query, create_guid(), 'EventSessions', $session['id'], $session['customer_type'], $session['customer_id'], $reminder['template_id'], $date_send, gmdate('Y-m-d H:i:s'));
		$er->db->query($query, true);
		$query = "
			INSERT INTO events_reminders_tracker
			(id, events_customers_id, event_reminder_id, date_modified)
			VALUES
			('%s', '%s', '%s', '%s')
			";
		$query = sprintf($query, create_guid(), $session['events_customers_id'], $reminder['id'], gmdate('Y-m-d H:i:s'));
		$er->db->query($query, true);
	}
}

require_once('modules/Notes/Note.php');
$seedNote = new Note();

$mail = new SugarPHPMailer();
$mail->InitForSend(true);
$mail->ContentType="text/html";
$emailsPerSecond = 10;

$now = gmdate('Y-m-d H:i:s');
$query = "SELECT * FROM email_queue WHERE send_on <= '$now' ORDER BY send_on";
$res = $er->db->limitQuery($query, 0, 500);

while ($row = $er->db->fetchByAssoc($res)) {
	$tpl = new EmailTemplate;
	$tpl->retrieve($row['template_id'], false);

	if (!isset($beanList[$row['recipient_type']])) {
		$recipient = null;
	} else {
		$class = $beanList[$row['recipient_type']];
		if (!class_exists($class)) {
			require_once($beanFiles[$class]);
		}
		$recipient = new $class;
		$recipient->retrieve($row['recipient_id']);
	}
	
	if(! $recipient || (empty($recipient->email1) && empty($recipient->email2)))
		continue;

	if(!isset($beanList[$row['related_type']])){
		$related = null;
	} else {
		$class = $beanList[$row['related_type']];
		if (!class_exists($class)) {
			require_once($beanFiles[$class]);
		}
		$related = new $class;
		$related->retrieve($row['related_id']);
	}


	$mail->ClearAllRecipients();
	$mail->ClearAttachments();
	$mail->ClearCustomHeaders();
	$mail->ClearReplyTos();

	$objects = array(
		$recipient->module_dir => $recipient->id
	);
	
	if ($related)
		$objects[$related->module_dir] = $related->id;
	
	$template_data =  TemplateParser::parse_generic(
		array(
			'subject' => $tpl->subject,
			'body_html' => $tpl->body_html,
			'body' => $tpl->body,
		),
		$objects,
		array('preserve_placeholders' => true)
	);


	if (AppConfig::is_B2C() && $recipient && $recipient->module_dir == 'Accounts') {
		if (!empty($recipient->primary_contact_id)) {
			$template_data =  TemplateParser::parse_generic(
				array(
					'subject' => $template_data['subject'],
					'body_html' => $template_data['body_html'],
					'body' => $template_data['body'],
				),
				array('Contacts' => $recipient->primary_contact_id)
			);
		}
	}

	$template_data =  TemplateParser::parse_generic(
		array(
		'subject' => $template_data['subject'],
		'body_html' => $template_data['body_html'],
		'body' => $template_data['body'],
		),
		array()
	); // clear placeholders

	// Add the notes
	$where = "notes.parent_id='{$tpl->id}'";
	$arNotes = $seedNote->get_full_list("notes.name", $where, true);
	// Do we have any notes?
	if (!empty($arNotes)) {
		// Add attachments
		$mail->handleAttachments($arNotes);
	}

	if(! empty($recipient->email1))
		$mail->AddAddress($recipient->email1, $recipient->name);
	else if(! empty($recipient->email2))
		$mail->AddAddress($recipient->email2, $recipient->name);
	$mail->Subject =  $template_data['subject'];
	$mail->Body = wordwrap($template_data['body_html'], 900);
	$mail->AltBody = $template_data['body'];
	$mail->prepForOutbound(true);
	$success = $mail->send();
	$er->db->query("DELETE FROM email_queue WHERE id = '{$row['id']}'", true);
}

$mail->FinishedSend();

?>
