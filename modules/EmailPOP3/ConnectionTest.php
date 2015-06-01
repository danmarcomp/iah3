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

require_once('modules/Emails/EmailImport.php');

if(empty($_REQUEST['record']))
	sugar_die('No record ID provided');
$result = ListQuery::quick_fetch('EmailPOP3', $_REQUEST['record'], array('user_id'));
$for_user = null;
if($result) {
	$for_user = $result->getField('user_id');
	if($for_user == '-1') $for_user = null;
	if(! AppConfig::is_admin() && $for_user && $for_user != AppConfig::current_user_id()) {
		pr2('Error: access to mailbox denied');
		return;
	}
}
$import = new EmailImport(false, $for_user, false);

try {
	$import->connection_test($_REQUEST['record']);
}
catch(IAHEmailImportError $e) {
	pr2('Error: '.$e->getMessage(), 'Connection Failed', true);
	return;
}

pr2('Connection successful');
