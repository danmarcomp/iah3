<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
require_once('include/DetailView/DetailFactory.php');
global $mod_strings;

$mgr = DetailFactory::getManager('standard');

$layout = 'WizardHome';
if (! empty($_REQUEST['layout']))
    $layout = $_REQUEST['layout'];

$mgr->layout_name = $layout;

$record = null;
if (isset($_REQUEST['record']))
    $record = $_REQUEST['record'];

$mgr->setModule('Campaigns');
$mgr->record_id = $record;
$mgr->action = 'WizardHome';

$perform = 'view';
if (isset($_REQUEST['record_perform']) && $_REQUEST['record_perform'] == 'save') {
    $perform = 'save';
} elseif ($layout == 'WizardNewsletter') {
    $perform = 'edit';
}

$mgr->perform = $perform;
$mgr->standardInit();
$mgr->form_title = $mod_strings['LBL_CAMPAIGN_WIZARD'];

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

if ( $perform == 'save' && (isset($_REQUEST['next']) && $_REQUEST['next'] == 'WizardMarketing') ) {
    $return_url = "index.php?module=$mgr->module&action=WizardMarketing&campaign_id=$mgr->record_id";
    echo '<script>document.location.href="'.addcslashes($return_url, '"').'";</script>';
    return true;
} elseif($nextAction) {
    return $nextAction;
}

global $pageInstance;
$t = $mgr->getPageTitle();
if(strlen($t))
    $pageInstance->set_title($t);

echo $mgr->renderLayout();
?>