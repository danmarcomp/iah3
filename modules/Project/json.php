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

$json_supported_actions['portal_get_project_documents'] = array('login_required' => 'portal');

function json_portal_get_project_documents()
{
	if($_SESSION['type'] == 'lead'){
		return json_bad_request(array('error' => 'no_access'));
	}

	require_once 'modules/Documents/Document.php';

	$project_id = array_get_default($_REQUEST, 'project_id');
	$list = get_project_documents($project_id);

	$seed = new Document;
	$select_fields = array_merge($seed->column_fields, $seed->additional_column_fields);
	$arRecords = json_convert_bean_list($list, $select_fields);

    // Generate the return array
	$arReturn = array (
    	'list' => $arRecords,
    );

	json_return_value($arReturn);
}

$json_supported_actions['portal_get_project_contacts'] = array('login_required' => 'portal');
function json_portal_get_project_contacts()
{
	if($_SESSION['type'] == 'lead'){
		return json_bad_request(array('error' => 'no_access'));
	}

	require_once 'modules/Contacts/Contact.php';

	$project_id = array_get_default($_REQUEST, 'project_id');
	$list = get_project_contacts($project_id);

	$seed = new Contact;
	$select_fields = array_merge($seed->column_fields, $seed->additional_column_fields);
	$arRecords = json_convert_bean_list($list, $select_fields);

    // Generate the return array
	$arReturn = array (
    	'list' => $arRecords,
    );

	json_return_value($arReturn);
}


