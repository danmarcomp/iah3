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
require_once('modules/Quotes/products_dialog/ProductsCreator.php');
require_once('modules/Quotes/products_dialog/ProjectCreator.php');
require_once('modules/Quotes/products_dialog/SubContractCreator.php');

$subcontractCreator = new SubContractCreator();
$subcontract = $subcontractCreator->doAction();

$projectCreator = new ProjectCreator();
$project = $projectCreator->doAction();

$parent_id = array_get_default($_REQUEST, 'parent_id', '');
$parent_type = array_get_default($_REQUEST, 'parent_type', '');
$account_id = array_get_default($_REQUEST, 'account_id', '');

$pc = new ProductsCreator($parent_id, $parent_type, $account_id);

$subcontractId = null;
$projectId = null;

if ($subcontract)
    $subcontractId = $subcontract->getPrimaryKeyValue();

if ($project)
    $projectId = $project->getPrimaryKeyValue();

$pc->create($subcontractId, $projectId);
?>