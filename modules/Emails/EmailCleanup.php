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


 /*********************************************************************************
 * $Id: $
 ********************************************************************************/

    require_once 'include/database/PearDatabase.php';
    require_once('modules/EmailFolders/EmailFolder.php');

    global $log;
	global $db;

	$defs = array(
		'trash_cleanup_age' => array(),
		'inbox_cleanup_age' => array(
			'status' => array('received', 'archived',),
		),
		'sent_cleanup_age' => array(
			'status' => array('sent',),
		),
		'inbox_assoc_cleanup_age' => array(
			'status' => array('received', 'archived',),
			'assoc' => true,
		),
		'sent_assoc_cleanup_age' => array(
			'status' => array('sent',),
			'assoc' => true,
		),
	);

	$assoc = array(
		'emails_accounts',
		'emails_bugs',
		'emails_cases',
		'emails_contacts',
		'emails_invoices',
		'emails_leads',
		'emails_opportunities',
		'emails_project_tasks',
		'emails_projects',
		'emails_prospects',
		'emails_quotes',
		'emails_tasks',
		'emails_users',
	);


	$date = gmdate('Y-m-d H:i:s');

	$query = 'SELECT id FROM users';
    $result = $db->query($query,true,"Error getting users list: ");
    
	while ($row = $db->fetchByAssoc($result)) {
		$user = new User;
		$user->retrieve($row['id']);
		foreach ($defs as $name => $def) {
			$threshold = $user->getPreference($name);
			$m = array();
			if (!preg_match('/^([0-9]+)([dwmy])$/', $threshold, $m)) continue;
			switch ($m[2]) {
				case 'd' : $interval = $m[1] . ' DAY'; break;
				case 'w' : $interval = $m[1]*7 . ' DAY'; break;
				case 'm' : $interval = $m[1] . ' MONTH'; break;
				case 'y' : $interval = $m[1] . ' YEAR'; break;
			}
			$query = "SELECT DATE_SUB('$date', INTERVAL $interval) AS d";
			$res = $db->query($query, true);
			$row = $db->fetchByAssoc($res);
			$date_cut = $row['d'];
			if ($name == 'trash_cleanup_age') {
				$folder_id = EmailFolder::get_std_folder_id($user->id, STD_FOLDER_TRASH);
				$query2 = 'SELECT notes.filename FROM emails LEFT JOIN notes ON notes.parent_id=emails.id WHERE emails.folder = \'' . $folder_id . '\' AND emails.date_modified <= \'' . $date_cut . '\' AND notes.email_attachment=1';

				$res = $db->query($query2, true, "Error cleaning trash: ");
				while ($note = $db->fetchByAssoc($res)) {
					if ((string)$note['filename'] !== '') {
						@unlink(AppConfig::upload_dir() . $note['filename']);
					}
				}

				$query = 'DELETE FROM emails WHERE folder = \'' . $folder_id . '\' AND date_modified <= \'' . $date_cut . '\'';
    			$db->query($query, true, "Error cleaning trash: ");
			} else {
				$query = 'DELETE emails.*, notes.*, ' . join('.*, ', $assoc) . '.* FROM emails ';
				$query2 = 'SELECT emails.id, notes.filename FROM emails ';
				$where = array();
				foreach ($assoc as $table) {
					$extra = " LEFT JOIN $table ON $table.email_id = emails.id AND $table.deleted = 0 ";
					$query .= $extra;
					$query2 .= $extra;
					$where[] = " $table.deleted IS " . ((empty($def['assoc'])) ? '' : 'NOT' ) . ' NULL';
				}
				$extra = " LEFT JOIN notes ON emails.id = notes.parent_id ";
				$extra .= ' WHERE (';
				$extra .= join(empty($def['assoc']) ? ' AND ' : ' OR ', $where);
				$extra .= ') AND emails.date_modified <= \'' . $date_cut . '\'';

				$extra .= ' AND (emails.status =\'' . join('\' OR emails.status=\'', $def['status']) . '\')';
				$extra .= ' AND (emails.assigned_user_id =\'' . $user->id . '\')';

				$query .= $extra;
				$query2 .= $extra;

				$query2 .= ' AND notes.email_attachment=1 ';

				$res = $db->query($query2, true, "Error cleaning trash: ");
				while ($note = $db->fetchByAssoc($res)) {
					if ((string)$note['filename'] !== '') {
						@unlink(AppConfig::upload_dir() . $note['filename']);
					}
				}

    			$db->query($query, true, "Error cleaning trash: ");
			}
		}
	}
	$query = 'DELETE emails_bodies.* FROM emails_bodies LEFT JOIN emails ON emails.id = emails_bodies.email_id WHERE emails.id IS NULL';
    $db->query($query,true,"Error cleaning trash: ");

