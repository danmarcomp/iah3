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


require_once('include/database/PearDatabase.php');

global $section;

if(!isset($section)) {
    $section = '';
    if(isset($_REQUEST['section']))
        $section = $_REQUEST['section'];
    if(isset($_REQUEST['section_basic']))
        $section = $_REQUEST['section_basic'];
}
$section = PearDatabase::quote($section);

if($section == 'hr') {
	$actions = ACLAction::getUserActions($current_user->id, false);
	if (!ACLController::checkModuleAllowed('HR', $actions)) {
		ACLController::displayNoAccess(true);
		sugar_cleanup(true);
	}
	if($GLOBALS['action'] == 'Popup' && ! isset($_REQUEST['unassoc_docs_only'])) {
		$_REQUEST['unassoc_docs_only'] = 'true';
		$GLOBALS['unassoc_docs_only'] = 'true';
	}
}

elseif($section != '')
    sugar_die("Unknown documents section");

if($section != 'hr' && ! empty($_REQUEST['unassoc_docs_only']))
	unset($_REQUEST['unassoc_docs_only']);

?>
