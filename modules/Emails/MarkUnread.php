<?php
require_once('modules/Emails/Email.php');

$record = null;

if (isset($_REQUEST['record']))
    $record = $_REQUEST['record'];

if ($record != null) {
    $email = ListQuery::quick_fetch('Email', $record);

    if ($email != null) {
        $upd = new RowUpdate($email);

        $upd->set('isread', 0);
        $upd->save();
    }
}

return array('perform', array('module' => 'Emails', 'action' => 'DetailView', 'record' => $record, 'record_perform' => 'view', 'mark_unread' => 1));
