<?php

require_once 'modules/Documents/GoogleDocsSync.php';
global $db;

$seedUser = new User;

$users = $seedUser->get_full_list();
foreach ($users as $user) {
	$sync = new GoogleDocsSync($user);
	$sync->sync();
}

