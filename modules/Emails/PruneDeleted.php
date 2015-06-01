<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$test = !empty($_REQUEST['test']);
$debug = !empty($_REQUEST['debug']);
if($debug) {
	error_reporting(E_ALL); ini_set('display_errors', 1);
}

require_once('modules/Emails/Email.php');
require_once('modules/EmailFolders/EmailFolder.php');
require_once('modules/Notes/Note.php');
require_once('include/upload_file.php');

$seed = new Email();

$query = "SELECT id FROM emails_folders WHERE reserved='".STD_FOLDER_TRASH."' AND NOT deleted";
$result = $seed->db->query($query, true, "Error retrieving email trash folders: ");
$trash_folders = array();
while($row = $seed->db->fetchByAssoc($result)) {
	$trash_folders[] = $row['id'];
}
$num_trash_folders = count($trash_folders);

if(count($trash_folders)) {
	$where = "(deleted OR folder IN ('".implode("','", $trash_folders)."'))";
}
else
	$where = "deleted";

$where = "$where AND date_modified < DATE_SUB(NOW(), INTERVAL 14 DAY)";

if($debug) {
	$query = "SELECT COUNT(*) AS count_deleted FROM emails WHERE $where";
	$result = $seed->db->query($query, true, "Error retrieving deleted emails: ");
	$row = $seed->db->fetchByAssoc($result);
	$pending_delete_emails = $row['count_deleted'];
}

$query = "SELECT id FROM emails WHERE $where ORDER BY date_entered ASC LIMIT 500";
$result = $seed->db->query($query, true, "Error retrieving deleted emails: ");

$ids = array();
while($row = $seed->db->fetchByAssoc($result)) {
	$ids[] = $row['id'];
}

$deleted_notes = 0;
$deleted_attachments = 0;
$deleted_emails = 0;
while(count($ids)) {
	$seed_ids = array_splice($ids, 0, 50);
	$id_set = "('".implode("','", $seed_ids)."')";
	$query = "SELECT id,filename FROM notes WHERE parent_type='Emails' AND parent_id IN $id_set";
	$result = $seed->db->query($query, true, "Error retrieving email attachments: ");
	$notes = array();
	while($row = $seed->db->fetchByAssoc($result)) {
		$notes[$row['id']] = $row['filename'];
	}
	foreach($notes as $id=>$filename) {
		if(!empty($filename)) {
			if(!$test)
				@UploadFile::unlink_file($id, $filename);
			$deleted_attachments ++;
		}
	}
	
	if(!$test) {
		$query = "DELETE FROM notes WHERE parent_type='Emails' AND parent_id IN $id_set";
		$result = $seed->db->query($query, true, "Error deleting email attachments: ");
		
		$query = "DELETE FROM emails WHERE id IN $id_set";
		$result = $seed->db->query($query, true, "Error deleting emails: ");
	
		$query = "DELETE FROM emails_accounts WHERE email_id IN $id_set";
		$result = $seed->db->query($query, true, "Error deleting email-account relationships: ");
		$query = "DELETE FROM emails_cases WHERE email_id IN $id_set";
		$result = $seed->db->query($query, true, "Error deleting email-case relationships: ");
		$query = "DELETE FROM emails_contacts WHERE email_id IN $id_set";
		$result = $seed->db->query($query, true, "Error deleting email-contact relationships: ");
	}

	$deleted_notes += count($notes);
	$deleted_emails += count($seed_ids);
}

if($debug) {
	print "Found $num_trash_folders trash folders.<br>";
	print "Erased $deleted_emails of $pending_delete_emails emails trashed or marked deleted over 2 weeks ago.<br>";
	print "Erased $deleted_attachments email attachments.<br>";
}


/*
if(!empty($_REQUEST['clean_relationships'])) {
	foreach(array('emails_accounts', 'emails_cases', 'emails_contacts') as $rel) {
		$query = "SELECT $rel.id FROM $rel LEFT JOIN emails ON $rel.email_id = emails.id WHERE emails.id IS NULL";
		$result = $seed->db->query($query, true, "Error retrieving email relationship ids: ");
		$ids = array();
		while($row = $seed->db->fetchByAssoc($result)) {
			$ids[] = $row['id'];
		}
		while(count($ids)) {
			$seed_ids = array_splice($ids, 0, 50);
			$id_set = "('".implode("','", $seed_ids)."')";
			$query = "DELETE FROM $rel WHERE id IN $id_set";
			$result = $seed->db->query($query, true, "Error deleting old email relationships: ");
		}
	}
}
*/


?>
