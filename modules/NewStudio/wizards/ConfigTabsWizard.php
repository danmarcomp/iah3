<?php
require_once 'modules/NewStudio/wizards/StudioWizardBase.php';

class ConfigTabsWizard extends StudioWizardBase
{
	public function process()
	{
		require_once('modules/MySettings/TabController.php');
		$tabs = new TabController();

		if (! empty($this->params['reset_tabs'])) {
			$tabs->reset_hidden_tabs();
			$tabs->set_users_can_edit(null);
		} else if(! empty($this->params['save_tabs'])) {
			if(isset($this->params['group_1']))
				$tabs->set_hidden_tabs($this->params['group_1']);
			$tabs->set_users_can_edit(!empty($this->params['user_edit_tabs']));
		} else
			return;
			
		AppConfig::save_local();
		return array(
			'wizard' => 'ConfigTabs',
		);
	}

	public function render()
	{
	
		$cpath = AppConfig::local_config_path();
		if(! is_writable($cpath)) {
			echo '<p class="error">' . str_replace('%s', $cpath, translate('MSG_CONFIG_NOT_WRITABLE', 'Configurator')) . '</p>';
		}
		
		global $pageInstance;
		$pageInstance->add_js_include('modules/NewStudio/JSTransaction.js', null, LOAD_PRIORITY_HEAD);
		$pageInstance->add_js_literal('var jstransaction = new JSTransaction();', null, LOAD_PRIORITY_HEAD);
		$pageInstance->add_js_include('modules/NewStudio/studiotabgroups.js', null, LOAD_PRIORITY_HEAD);
		$pageInstance->add_js_include('modules/NewStudio/ygDDListStudio.js', null, LOAD_PRIORITY_HEAD);
		$pageInstance->add_js_include('modules/NewStudio/studiodd.js', null, LOAD_PRIORITY_HEAD);
		$pageInstance->add_js_include('modules/NewStudio/studio.js', null, LOAD_PRIORITY_HEAD);
		
		global $mod_strings;
		global $app_list_strings;
		global $app_strings;
		global $current_user;

		$title = get_module_title('Administration', $mod_strings['LBL_MODULE_TITLE'].": ".$mod_strings['LBL_CONFIGURE_TABS'], true);

		require_once 'modules/MySettings/TabController.php';
		$controller = new TabController();
		$tabs = $controller->get_tabs_system(true);
		$groups = array();
		$groups[$mod_strings['LBL_DISPLAY_TABS']] = array();
		foreach ($tabs[0] as $key=>$value)
		{
			$groups[$mod_strings['LBL_DISPLAY_TABS']][$key] = array('label'=>'<span style="font-size:90%">'.$app_list_strings['moduleList'][$key] . '</span>');
		}
		$groups[ $mod_strings['LBL_HIDE_TABS']]= array();
		foreach ($tabs[1] as $key=>$value)
		{
			$groups[ $mod_strings['LBL_HIDE_TABS']][$key]  = array('label'=>$app_list_strings['moduleList'][$key]);
		}


		global $app_list_strings, $app_strings;
		require_once('include/Sugar_Smarty.php');
		$smarty = new Sugar_Smarty();
		$user_can_edit = $controller->get_users_can_edit();
		$smarty->assign('APP', $GLOBALS['app_strings']);
		$smarty->assign('MOD', $GLOBALS['mod_strings']);
		$smarty->assign('title',  $title);
		$smarty->assign('user_can_edit',  $user_can_edit);
		$smarty->assign('hideKeys', true);
		$smarty->assign('groups',$groups);
		$smarty->assign('description',  $mod_strings['LBL_CONFIG_TABS']);
		$smarty->display("modules/NewStudio/wizards/EditView.tpl");
	}

}

