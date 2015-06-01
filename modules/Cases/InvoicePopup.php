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

require_once 'modules/Cases/CaseInvoicePopup.php';

$record = array_get_default($_REQUEST, 'record');
if(! $record) die('No record ID provided');
$result = ListQuery::quick_fetch('aCase', $record, true, array('acl_checks' => 'view'));
if(! $result || $result->failed || ! $result->getAclAllowed('view'))
	die('Error retrieving Case record');

$stage = array_get_default($_REQUEST, 'stage', 'products');

if($stage == 'products') {
	$mgr = new CaseInvoicePopup();
	$mgr->initPopup('ProductCatalog', 'products', $record, $stage, 'hours');
	$mgr->custom_title_html = translate('LBL_ADD_SERVICE_PARTS', 'Cases');
	$mgr->render();
	$stage = $mgr->stage;
	if($mgr->stage == 'products') {
		return;
	}
}

if($stage == 'hours') {
	$mgr = new CaseInvoicePopup();
	$mgr->initPopup('Booking', 'booked_hours', $record, $stage);
	$mgr->addOverrideFilters(array('status' => 'approved'));
	$mgr->custom_title_html = translate('LBL_ADD_BOOKED_HOURS', 'Cases');
	$mgr->render();
	$stage = $mgr->stage;
	if($stage == 'hours') {
		$t = javascript_escape($mgr->custom_title_html);
		$pageInstance->add_js_literal("SUGAR.popups.setTitleHtml('$t');", null, LOAD_PRIORITY_FOOT);
		return;
	}
}

if($stage == 'done') {
	$pageInstance->add_js_literal('SUGAR.popups.close()', null, LOAD_PRIORITY_FOOT);
}

