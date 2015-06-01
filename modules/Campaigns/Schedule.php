<?php
if(! ACLController::checkAccess('EmailMarketing', 'list'))
	ACLController::displayNoAccess();

require_once('modules/Campaigns/ScheduleViewManager.php');

$manager = new ScheduleViewManager('listview', array('is_primary' => true));
$manager->loadRequest();

if(! $manager->initModuleView('Campaigns'))
	ACLController::displayNoAccess();

$manager->render();
?>