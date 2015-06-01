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
if (!defined('inScheduler')) die('Unauthorized access');


require_once 'modules/Calls/Call.php';
require_once 'modules/Meetings/Meeting.php';
require_once 'include/SugarPHPMailer.php';
require_once('include/layout/NotificationManager.php');

global $db, $timedate;
global $current_language;

$mail = new SugarPHPMailer();
$mail->InitForSend(true);

$res = $db->query("SELECT run_interval FROM schedules WHERE type='activities_notification'");
$row = $db->fetchByAssoc($res);
$run_interval = $row['run_interval'] * 60;
$now = gmdate('Y-m-d H:i:00');

$users = array();
$date_clause = "date_start";
$activities = array(
	'Call',
	'Meeting'
);

foreach ($activities as $class) {
	$seed = new $class;
	$query = "SELECT id, $date_clause AS dt, assigned_user_id, name, duration, date_start FROM {$seed->table_name} WHERE deleted = 0 AND email_reminder_time > 0 AND $date_clause >= DATE_ADD('$now', INTERVAL email_reminder_time SECOND) AND $date_clause < DATE_ADD('$now', INTERVAL email_reminder_time + $run_interval SECOND) ";
	$result = $db->query($query, true, "Error retrieving calls list");

	while($row = $db->fetchByAssoc($result)) {
		if (!isset($users[$row['assigned_user_id']])) {
			$users[$row['assigned_user_id']] = new User;
			$users[$row['assigned_user_id']]->retrieve($row['assigned_user_id']);
		}
		$user =& $users[$row['assigned_user_id']];
		if(!empty($user->email1)) {
			$email = $user->email1;
		} elseif(!empty($user->email2)) {
			$email = $user->email1;
		} else {
			continue;
		}

        if (empty($row['duration']) || $row['duration'] == 0) {
            $hours = '0';
            $minutes = '00';
        } else {
            $hours = ($row['duration'] < 60) ? 0 : floor($row['duration'] / 60);
            $minutes = ($row['duration'] < 60) ? $row['duration'] : ($row['duration'] - ($hours * 60));
            if ($minutes < 10) $minutes = '0' . $minutes;
        }

		$invitees = array();
		$seed->retrieve($row['id']);

		$seed->load_relationship('contacts');
		$contact = new Contact;
		$contacts = $seed->contacts->getBeans($contact);
		foreach ($contacts as $contact) {
			$invitees[] = $contact->full_name;
		}

		$seed->load_relationship('users');
		$usr = new User;
		$usrs = $seed->users->getBeans($usr);
		foreach ($usrs as $usr) {
			if ($usr->id != $row['assigned_user_id']) $invitees[] = $usr->full_name;
		}

		if ($class == 'Meeting') {
            $template_name = 'UpcomingMeeting';
		} else {
            $template_name = 'UpcomingCall';
        }

        $template_vars = array(
            'NAME' => array('value' =>  $row['name'], 'in_subject' => true),
            'DATE' => array('value' =>  $timedate->to_display_date_time($row['dt'], true, true, $user), 'in_subject' => true),
            'DURATION'=> array('value' => sprintf('%d:%02d', $hours, $minutes)),
            'INVITEES'=> array('value' => join(', ', $invitees)),
        );

        if ($class == 'Meeting') {
            $resources = array();
            require_once 'modules/Resources/Resource.php';
            $resource = new Resource;
            $seed->load_relationship('resources');
            $rsrcs = $seed->resources->getBeans($resource);
            foreach ($rsrcs as $resource) {
                $resources[] = $resource->name;
            }
            $template_vars['RESOURCES'] = array('value' => join(', ', $resources));
            $template_vars['LOCATION'] = array('value' => $seed->location);
        }

        $mail_template = NotificationManager::loadCustomMessage($seed->module_dir, $template_name, $template_vars);

		$mail->ClearAllRecipients();
		$mail->ClearAttachments();
		$mail->ClearCustomHeaders();
		$mail->AddAddress($email, $user->full_name);
		$mail->Subject = from_html($mail_template['subject']);
		$mail->Body = from_html($mail_template['body']);
		$mail->prepForOutbound(true);
		$mail->Send();
	}
}

