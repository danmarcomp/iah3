<?php
/*
 *
 * The contents of this file are subject to the info@hand Software License Agreement Version 1.3
 *
 * ("License"); You may not use this file except in compliance with the License.
 * You may obtain a copy of the License at <http://1crm.com/pdf/swlicense.pdf>.
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the
 * specific language governing rights and limitations under the License,
 *
 * All copies of the Covered Code must include on each user interface screen:
 * (i) the 1CRM copyright notice,
 * (ii) the "Powered by the 1CRM Engine" logo, 
 *
 * (iii) the "Powered by SugarCRM" logo, and
 * (iv) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.
 * See full license for requirements.
 *
 * The Original Code is : 1CRM Engine proprietary commercial code.
 * The Initial Developer of this Original Code is 1CRM Corp.
 * and it is Copyright (C) 2004-2012 by 1CRM Corp.
 *
 * All Rights Reserved.
 * Portions created by SugarCRM are Copyright (C) 2004-2008 SugarCRM, Inc.;
 * All Rights Reserved.
 *
 */
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once('include/DetailView/DetailFactory.php');

$employee_id = '';
if (isset($_REQUEST['employee_id']))
    $employee_id = $_REQUEST['employee_id'];
$now = localtime(time(), 1);
$current_year = $now['tm_year'] + 1900;
$start_month = fiscal_year_start_month();
if(isset($_REQUEST['fiscal_year'])) {
    $fiscal_year = $_REQUEST['fiscal_year'];
} else {
    $fiscal_year = $current_year;
    if($now['tm_mon'] + 1 < $start_month)
        $fiscal_year --;
}

$mgr = DetailFactory::getManager('standard');
$mgr->module = 'HR';
$mgr->action = 'SaveLeave';
$mgr->layout_name = 'Leave';
$mgr->perform = 'edit';
$mgr->model = new ModelDef('EmployeeLeave');
$mgr->standardInit();

$return_url = "index.php?module=HR&action=DetailView&record=$employee_id&fiscal_year=$fiscal_year";
$hidden = array('return_url' => $return_url, 'employee_id' => $employee_id, 'fiscal_year' => $fiscal_year);
$mgr->layout->addFormHiddenFields($hidden, false);
$mgr->form_gen->form_obj->addHiddenFields($hidden);

$nextAction = $mgr->performUpdate();
if($nextAction)
    return $nextAction;

global $pageInstance;
$t = $mgr->getPageTitle();
if(strlen($t))
    $pageInstance->set_title($t);

echo $mgr->renderLayout();

?>