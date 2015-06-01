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
if (!defined('inScheduler')) die('Unauthorized access');
/*$Id: EmailExpiredSubcontracts.php 7243 2010-05-10 04:49:11Z andrey $*/

require_once('modules/SubContracts/SubContract.php');
require_once('XTemplate/xtpl.php');
require_once('include/SugarPHPMailer.php');

//functions
if (!defined('SUBCONTRACT_EMAIL_FUNCTIONS')) {
	
	define('SUBCONTRACT_EMAIL_FUNCTIONS', 1);
	
	function parse_subcontracts_list(&$list, $list_title, $module = 'Service', $action = 'DetailView', $template = 'modules/Service/ExpiringSubcontractList.html')
	{
		$xtpl=new XTemplate($template);
		$xtpl->assign('LIST_TITLE', $list_title);
		foreach($list as $item) {
			$xtpl->assign('NAME', $item->name);
			$xtpl->assign('PARENT', $item->main_contract_name);
			$xtpl->assign('URL', AppConfig::site_url()."/index.php?module=$module&action=$action&record={$item->id}");
			$xtpl->parse('main.row');
		}
		$xtpl->parse('main');
		return $xtpl->text("main");
	}
	
	function email_subcontracts($subcontracts, $sheduler, $current_user, $title)
	{
		$user = new User;
		if(! $user->retrieve($sheduler->options_arr['send_notifications_to']))
			return false;
		if(empty($user->email1) && empty($user->email2))
			return false;
		$email = new SugarPHPMailer();
		$email->InitForSend(true);
		if (!empty($user->email1))
			$email->AddAddress($user->email1/* , $user->name */);
		else if (!empty($user->email2))
			$email->AddAddress($user->email2/* , $user->name */);
		$email->AddReplyTo($email->From,$email->FromName);
		$email->Subject = $title;
		$email->Body = $subcontracts;
		$email->prepForOutbound();
		$email->Send();
	}
}
//end of functions


$focus = new SubContract;
$module = 'Service';
$action = 'DetailView';

global $current_language;
global $current_user;

$subcontracts = '';

$cur_mod_strings = return_module_language($current_language, $module);

$result1 = $focus->get_list('', "`$focus->table_name`.`date_expire` = DATE_ADD(CURDATE(),INTERVAL 60 DAY)");

$list1 = $result1['list'];

if (count($list1)) {
	//send email
	$subcontracts .= parse_subcontracts_list($list1, $cur_mod_strings['LBL_EXPIRING_SUBCONTRACTS'] . 60 . $cur_mod_strings['LBL_DAYS']);
}

$result2 = $focus->get_list('', "`$focus->table_name`.`date_expire` = DATE_ADD(CURDATE(),INTERVAL 30 DAY)");

$list2 = $result2['list'];

if (count($list2)) {
	//send email
	$subcontracts .= parse_subcontracts_list($list2, $cur_mod_strings['LBL_EXPIRING_SUBCONTRACTS'] . 60 . $cur_mod_strings['LBL_DAYS']);
}

$result3 = $focus->get_list('', "`$focus->table_name`.`date_expire` < CURDATE()");

$list3 = $result3['list'];

if (count($list3)) {
	//send email
	$subcontracts .= parse_subcontracts_list($list3, $cur_mod_strings['LBL_EXPIRED_SUBCONTRACTS']);
}

if (!empty($subcontracts))
	email_subcontracts($subcontracts, $this, $current_user, $cur_mod_strings['LBL_EXPIRING_SUBCONTRACTS_LIST']);

?>
