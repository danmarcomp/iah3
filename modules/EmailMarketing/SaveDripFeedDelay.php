<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$email_id = array_get_default($_REQUEST, 'email_id', '');
$email = ListQuery::quick_fetch('EmailMarketing', $email_id);

if (! $email->failed) {
    $update = new RowUpdate($email);
    $update->limitFields(array('dripfeed_delay', 'dripfeed_delay_unit'));
    $update->loadRequest();
    $inputParams = $update->getInput();
    $update->set($inputParams);
    $update->save();
}
?>
