<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
require_once('include/DetailView/DetailFactory.php');
global $mod_strings;

$mgr = DetailFactory::getManager('standard');
$mgr->setModule('Campaigns');
$mgr->action = 'CampaignDiagnostic';
$mgr->layout_name = 'Diagnostic';
$mgr->perform = 'view';
$mgr->standardInit();
$mgr->form_title = $mod_strings['LBL_CAMPAIGN_DIAGNOSTICS'];

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