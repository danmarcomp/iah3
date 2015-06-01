<?php
require_once('modules/Meetings/scheduler_dialog/ActivityDialog.php');

$model = 'Meeting';

if (! empty($_REQUEST['edit_model']))
    $model = $_REQUEST['edit_model'];

$dialog = new ActivityDialog($model);
if($dialog->performUpdate()) {
	echo '<script nodisplay></script>';
	return;
}

echo $dialog->render();
$pageInstance->title = null;
?>
