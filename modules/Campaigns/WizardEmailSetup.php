<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
require_once('include/DetailView/DetailFactory.php');
global $mod_strings;

$mgr = DetailFactory::getManager('standard');
$layout = 'WizardEmailSetup';
$mgr->layout_name = $layout;
$mgr->setModule('Campaigns');
$mgr->action = 'WizardEmailSetup';

$perform = 'edit';
if (isset($_REQUEST['record_perform']) && $_REQUEST['record_perform'] == 'save') {
    $perform = 'save';
}

$mgr->perform = $perform;
$mgr->standardInit();
$mgr->form_title = $mod_strings['LBL_EMAIL_SETUP_WIZARD_TITLE'];

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

if ($mgr->perform == 'save') {
	$upd = new RowUpdate($mgr->record);
    $mgr->form_gen->loadUpdateRequest($upd, $_REQUEST);
    $mgr->form_gen->afterUpdate($upd);

    $return_url = "index.php?module=Campaigns&action=index";
    echo '<script>document.location.href="'.addcslashes($return_url, '"').'";</script>';
    return true;
}

global $pageInstance;
$t = $mgr->getPageTitle();
if(strlen($t))
    $pageInstance->set_title($t);

echo $mgr->renderLayout();
?>
