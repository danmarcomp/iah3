<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once('modules/Emails/Email.php');
require_once('modules/Emails/utils.php');


$focus = new Email();

if (!isset($_REQUEST['record'])) {
	sugar_cleanup(true);
}

if (!$focus->retrieve($_REQUEST['record'])) {
	sugar_cleanup(true);
}

if(!$focus->ACLAccess('DetailView')){
	sugar_cleanup(true);
}

$destination = get_raw_message_filename($focus->message_id, false);
if (!file_exists($destination) || !is_readable($destination)) {
	sugar_cleanup(true);
}

echo '<pre>';
readfile($destination);
echo '</pre>';


