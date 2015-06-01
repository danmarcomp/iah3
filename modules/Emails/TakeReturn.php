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


/*********************************************************************************
 * $Id: TakeReturn.php 7243 2010-05-10 04:49:11Z andrey $
 ********************************************************************************/

require_once('modules/Emails/Email.php');
require_once('modules/EmailFolders/EmailFolder.php');

$record = null;

$to_list = false;

if (isset($_REQUEST['record']))
    $record = $_REQUEST['record'];

if ($record != null) {
    $email = ListQuery::quick_fetch('Email', $record);

    if ($email != null) {
        $updated_data = array();
        $take_return = '';
        if (isset($_REQUEST['take_return']))
            $take_return = $_REQUEST['take_return'];
        if ($take_return == 'Return') {
            $updated_data['assigned_user_id'] = -1;
            $updated_data['isread'] = 0;
			$updated_data['folder'] = EmailFolder::get_std_folder_id(-1, STD_FOLDER_INBOX);
			$to_list = true;
        } elseif ($take_return == 'Take') {
            $updated_data['assigned_user_id'] = AppConfig::current_user_id();
			$updated_data['folder'] = EmailFolder::get_std_folder_id(AppConfig::current_user_id(), STD_FOLDER_INBOX);
        }

		if (!empty($updated_data['folder'])) {
			$upd = new RowUpdate($email);
	        $upd->set($updated_data);
		    $upd->save();
		}
    }
}

if ($to_list) {
	return array(
		'perform',
		array(
			'module' => 'Emails',
			'action' => 'index',
		)
	);
} else {
	return array(
		'perform',
		array(
			'module' => 'Emails',
			'action' => 'DetailView',
			'record' => $record,
			'record_perform' => 'view'
		)
	);
}

