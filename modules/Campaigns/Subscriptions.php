<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
require_once('include/DetailView/DetailFactory.php');
global $mod_strings;

$mgr = DetailFactory::getManager('standard');
$mgr->loadRequest();

if(isset($_REQUEST['return_module']))
    $module = $_REQUEST['return_module'];

$mgr->setModule($module);
$mgr->module = 'Campaigns';

$mgr->action = 'Subscriptions';
$mgr->layout_name = 'Subscriptions';
$mgr->perform = 'view';
//$mgr->in_popup = true;
$mgr->track_view = false;
$mgr->standardInit();
$mgr->form_title = $mod_strings['LBL_MANAGE_SUBSCRIPTIONS_TITLE'];


if(isset($_REQUEST['return_module']) && isset($_REQUEST['return_record']) && isset($_REQUEST['return_action'])) {

    $hidden = array('return_module' => $_REQUEST['return_module'], 'return_action' => $_REQUEST['return_action'],
        'return_record' => $_REQUEST['return_record']);

    $mgr->layout->addFormHiddenFields($hidden, false);
    $mgr->form_gen->form_obj->addHiddenFields($hidden);
}

$mgr->layout->setFormButtons(array());
$nextAction = $mgr->performUpdate();
if($nextAction)
    return $nextAction;

global $pageInstance;
$t = $mgr->getPageTitle();
if(strlen($t))
    $pageInstance->set_title($t);

echo $mgr->renderLayout();
?>
