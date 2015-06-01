<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
if (!defined('inScheduler')) die('Unauthorized access');
/*$Id: EmailOverdue.php 7243 2010-05-10 04:49:11Z andrey $*/

//functions
if (!defined('PROJECT_TASKS_EMAIL_FUNCTIONS')) {
	
	define('PROJECT_TASKS_EMAIL_FUNCTIONS', 1);
	
	function parse_overdued_list(&$list, $list_title, $module = 'ProjectTask', $action = 'DetailView', $template = 'modules/ProjectTask/OverdueTasksList.html')
	{
		$xtpl=new XTemplate($template);
		$xtpl->assign('LIST_TITLE', $list_title);
		foreach($list as $item) {
			$xtpl->assign('NAME', $item->name);
			$xtpl->assign('PARENT', $item->parent_name);
			$xtpl->assign('URL', AppConfig::site_url()."/index.php?module=$module&action=$action&record={$item->id}");
			$xtpl->parse('main.row');
		}
		$xtpl->parse('main');
		return $xtpl->text("main");
	}
	
	function email_overdued($overdued, $user_id, $current_user, $title)
	{
		$user = new User;
		if(! $user->retrieve($user_id))
			return false;
		if(empty($user->email1) && empty($user->email2))
			return false;
		$email = new SugarPHPMailer();
		$email->InitForSend(true);
		
		if(! empty($user->email1))
			$email->AddAddress($user->email1/* , $user->name */);
		else if(! empty($user->email2))
			$email->AddAddress($user->email2/* , $user->name */);
		
		$email->AddReplyTo($email->From,$email->FromName);
		$email->Subject = $title;
		$email->Body = $overdued;
		
		$email->prepForOutbound();
		$email->Send();
	}
}
//end of functions

require_once('modules/ProjectTask/ProjectTask.php');
require_once('XTemplate/xtpl.php');
require_once('include/SugarPHPMailer.php');

$focus = new ProjectTask;
$module = 'ProjectTask';
$action = 'DetailView';

global $current_language;
global $current_user;

$notifieds = array();

$cur_mod_strings = return_module_language($current_language, $module);

$result = $focus->get_list('', "`$focus->table_name`.`status` != 'Completed' AND CURDATE() > DATE(`$focus->table_name`.`date_due`) AND 1 = ((TO_DAYS(CURDATE()) - TO_DAYS(DATE(`$focus->table_name`.`date_due`)) - 1) / 7)");

$list = $result['list'];

if (count($list)) {
	foreach($list as $item) {
		if (empty($notifieds[$item->assigned_user_id]))
			$notifieds[$item->assigned_user_id] = array();
		if (empty($notifieds[$item->project_manager_id]))
			$notifieds[$item->project_manager_id] = array();
		$notifieds[$item->project_manager_id][] = $item;
		if ($item->project_manager_id != $item->assigned_user_id)
			$notifieds[$item->assigned_user_id][] = $item;
	}
	
}

if (!empty($notifieds)) {
	foreach ($notifieds as $user_id => $list) {
		email_overdued(parse_overdued_list($list, $cur_mod_strings['LBL_OVERDUE_PROJECT_TASKS_LIST']), $user_id, $current_user, $cur_mod_strings['LBL_OVERDUE_PROJECT_TASKS_LIST']);
	}
}

?>
