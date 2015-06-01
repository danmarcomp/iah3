<?php

require_once 'modules/Contacts/GoogleContactsSync.php';
global $db;

$seedUser = new User;
$users = $seedUser->get_full_list();
foreach ($users as $user) {
	$sync = new GoogleContactsSync($user);
	$sync->sync();
}

