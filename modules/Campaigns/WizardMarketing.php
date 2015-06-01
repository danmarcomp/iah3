<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
require_once('include/DetailView/DetailFactory.php');
global $mod_strings;

$mgr = DetailFactory::getManager('standard');
$mgr->layout_name = 'WizardMarketing';
$mgr->setModel('EmailMarketing');
$mgr->module = 'Campaigns';
$return_record = '';
$record = '';

if (! empty($_REQUEST['return_record']))
    $return_record = $_REQUEST['return_record'];

if (! empty($_REQUEST['record'])) {
    $record = $_REQUEST['record'];
} elseif (isset($_REQUEST['campaign_id'])) {
    $return_record = $_REQUEST['campaign_id'];

    $marketing = new ListQuery('EmailMarketing', array('id', 'campaign_id'));
    $marketing->addFilterClause(array('field' => 'campaign_id', 'value' => $_REQUEST['campaign_id']));
    $result = $marketing->runQuerySingle();
    if (! $result->failed)
        $record = $result->getField('id');
}
$mgr->record_id = $record;
$mgr->action = 'WizardMarketing';

$perform = 'edit';
if (isset($_REQUEST['record_perform']) && $_REQUEST['record_perform'] == 'save') {
    $perform = 'save';
}

$mgr->perform = $perform;
$mgr->standardInit();
$mgr->form_title = $mod_strings['LBL_CAMPAIGN_WIZARD'];

$return_module = 'Campaigns';
$return_action = 'WizardHome';

if(isset($_REQUEST['return_module']) && isset($_REQUEST['return_action'])) {
    $return_module = $_REQUEST['return_module'];
    $return_action = $_REQUEST['return_action'];
}

$hidden = array('return_module' => $return_module, 'return_action' => $return_action,
    'return_record' => $return_record);

$mgr->layout->addFormHiddenFields($hidden, false);
$mgr->form_gen->form_obj->addHiddenFields($hidden);
$mgr->layout->setFormButtons(array());

$nextAction = $mgr->performUpdate();

$master = 'save';
if (! empty($_REQUEST['wiz_home_next_step'])) {

    if($_REQUEST['wiz_home_next_step'] == 3) {
        //user has chosen to save and schedule this campaign for email
        $master = 'send';
    } elseif($_REQUEST['wiz_home_next_step'] == 2) {
        //user has chosen to save and send this campaign in test mode
        $master = 'test';
    }
}

if ($perform == 'save') {
    if ($master != 'save') {
        $campaign_id = $_REQUEST['campaign_id'];
        return array('perform', array('module' => $mgr->module, 'action' => 'QueueCampaign', 'record' => $campaign_id, 'wiz_mass' => $mgr->record_id, 'mode' => $master,
            'return_record' => $campaign_id, 'return_action' => 'WizardHome', 'return_module' => 'Campaigns'));
    } else {
        $return_url = "index.php?module=$mgr->module&action=WizardHome&record=" . $_REQUEST['campaign_id'];
        echo '<script>document.location.href="'.addcslashes($return_url, '"').'";</script>';
        return true;
    }
}

global $pageInstance;
$t = $mgr->getPageTitle();
if(strlen($t))
    $pageInstance->set_title($t);

echo $mgr->renderLayout();
?>