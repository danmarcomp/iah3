<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once('include/DetailView/DetailFactory.php');
require_once('modules/Users/widgets/UserInfoWidget.php');

$record = '';
if (isset($_REQUEST['record']))
    $record = $_REQUEST['record'];

$user_id = null;
if (isset($_REQUEST['the_user_id']))
    $user_id = $_REQUEST['the_user_id'];

$mgr = DetailFactory::getManager('standard');

UserInfoWidget::renderSignaturePopup($mgr, $record, $user_id);
?>
