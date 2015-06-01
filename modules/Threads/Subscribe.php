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

require_once 'include/database/ListQuery.php';
global $current_user;

$record = array_get_default($_REQUEST, 'record');
if (!empty($record)) {
	$lq = new ListQuery('Thread', null, array('link_name' => 'users'));
	$lq->addFields(array('id'));
	$lq->setParentKey($_REQUEST['record']);
	$lq->addFilterPrimaryKey($current_user->id);
	$result = $lq->runQuery();
	$subscribed = count($lq->getResultIds($result));

	$upd = new RowUpdate('Thread');
	if ($upd->retrieveRecord($record)) {
        global $mod_strings;
		if ($subscribed) {
			$upd->removeLink('users', $current_user->id);
            $message =$mod_strings['MSG_UNSUBSCRIBED'];
        } else {
			$upd->addUpdateLink('users', $current_user->id);
            $message = $mod_strings['MSG_SUBSCRIBED'];
        }
        add_flash_message($message);
    }
}


if ($record) {
	$params = array(
		'module' => 'Threads',
		'record' => $record,
		'action' => 'DetailView',
		'layout' => array_get_default($_REQUEST, 'return_layout', 'Standard'),
	);
} else {
	$params = array(
		'module' => 'Threads',
		'action' => 'index',
	);
}

indexRedirect($params);

