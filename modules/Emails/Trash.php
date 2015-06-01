<?php
require_once 'include/database/ListQuery.php';
require_once 'include/database/RowUpdate.php';
require_once 'modules/Emails/Email.php';

$untrash_to = array_get_default($_REQUEST, 'untrash_to');
$record_id = array_get_default($_REQUEST, 'record');
$uids = explode(';', $_POST['list_uids']);

if (! empty($record_id)) {

    Email::move_to_trash($record_id, $untrash_to);

} elseif (! empty($uids)) {

    for ($i = 0; $i < sizeof($uids); $i++) {
        Email::move_to_trash($uids[$i], $untrash_to);
    }

}

return array(
	'perform', 
	array(
		'module' => 'Emails',
		'action' => 'index',
		'layout' => '',
	)
);
?>