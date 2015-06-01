<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
require_once('include/DetailView/DetailFactory.php');
global $mod_strings;

$mgr = DetailFactory::getManager('standard');
$mgr->setModel('Lead');
$mgr->module = 'Campaigns';
$mgr->action = 'WebToLeadCreation';

$layout = 'WebToLead';
if (! empty($_REQUEST['layout']))
    $layout = $_REQUEST['layout'];

$mgr->layout_name = $layout;
$mgr->perform = 'view';
$mgr->standardInit();
$mgr->form_title = $mod_strings['LBL_WEB_TO_LEAD_FORM_TITLE'];

$return_module = 'Campaigns';
$return_action = 'index';
$retrun_record = '';

if(isset($_REQUEST['return_module']) && isset($_REQUEST['return_action'])) {
    $return_module = $_REQUEST['return_module'];
    $return_action = $_REQUEST['return_action'];
    if (isset($_REQUEST['return_record']))
        $retrun_record = $_REQUEST['return_record'];
}

$hidden = array('return_module' => $return_module, 'return_action' => $return_action,
    'return_record' => $retrun_record);

$mgr->layout->addFormHiddenFields($hidden, false);
$mgr->form_gen->form_obj->addHiddenFields($hidden);

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