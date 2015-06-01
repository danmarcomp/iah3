<?php

require_once 'modules/NewStudio/wizards/StudioWizardBase.php';
require_once 'include/Sugar_Smarty.php';

class GroupTabsWizard extends StudioWizardBase
{
	public function render()
	{
		global $pageInstance, $mod_strings, $app_strings;
		$pageInstance->add_js_include('modules/NewStudio/JSTransaction.js', null, LOAD_PRIORITY_HEAD);
		$pageInstance->add_js_literal('var jstransaction = new JSTransaction();', null, LOAD_PRIORITY_HEAD);
		$pageInstance->add_js_include('modules/NewStudio/studiotabgroups.js', null, LOAD_PRIORITY_HEAD);
		$pageInstance->add_js_include('modules/NewStudio/ygDDListStudio.js', null, LOAD_PRIORITY_HEAD);
		$pageInstance->add_js_include('modules/NewStudio/studiodd.js', null, LOAD_PRIORITY_HEAD);
		$pageInstance->add_js_include('modules/NewStudio/studio.js', null, LOAD_PRIORITY_HEAD);
		
		$cpath = AppConfig::local_config_path();
		if(! is_writable($cpath)) {
			echo '<p class="error">' . str_replace('%s', $cpath, translate('MSG_CONFIG_NOT_WRITABLE', 'Configurator')) . '</p>';
		}
		
		$title = get_module_title('Administration', $mod_strings['LBL_MODULE_TITLE'].": ".$mod_strings['LBL_CONFIGURE_GROUP_TABS'], true);

		$smarty = new Sugar_Smarty();
		$smarty->assign('tabsPerRow', 4);
		$smarty->assign('MOD', $mod_strings);
		$smarty->assign('APP', $app_strings);
		
		global $subModuleList, $tabStructure;
		require_once('include/GroupedTabs/GroupedTabStructure.php');
		$tabManager = new GroupedTabStructure();
		$tabManager->add_home_tabs = false;
		$tabManager->add_other_tab = false;
		$tabManager->add_websites = false;
		$tabs = $tabManager->get_theme_tabs(true, null, 100, 100);
		$smarty->assign('title', $title);
		$smarty->assign('tabs', $tabs);
		$smarty->assign('availableModuleList', $this->getAvailableModules());
		$editImage = get_image('edit_inline', '');
		$smarty->assign('editImage',$editImage);	
		$deleteImage = get_image('delete_inline', '');
		$smarty->assign('deleteImage',$deleteImage);	
		$smarty->display("modules/NewStudio/wizards/EditViewTabs.tpl");
	}

	public function process()
	{
		if (! empty($this->params['reset_group_tabs'])) {
			$tabGroups = AppConfig::setting('module_order.grouped', null, true);
		} else if(! empty($this->params['save_group_tabs'])) {
			global $app_strings;
			$tabGroups = array();
			$selected_lang = (!empty($this->params['dropdown_lang'])?$this->params['dropdown_lang']:$_SESSION['authenticated_user_language']);    	
			$update_app_strings = array();
			for($count = 0; isset($this->params['slot_' . $count]); $count++){
				if ($this->params['delete_' . $count] == 1) continue;	
				$index = $this->params['slot_' . $count];
				$labelID = (!empty($this->params['tablabelid_' . $index]))?$this->params['tablabelid_' . $index]: 'LBL_GROUPTAB' . $count . '_'. time();
				$labelValue = from_html($this->params['tablabel_' . $index]);
				if(empty($GLOBALS['app_strings'][$labelID]) || $GLOBALS['app_strings'][$labelID] != $labelValue){
					$update_app_strings[$labelID] = $labelValue;
				}
				$tabGroups[$labelID] = array('label'=>$labelID);
				$tabGroups[$labelID]['modules']= array();
				for($subcount = 0; isset($this->params[$index.'_' . $subcount]); $subcount++){
					$tabGroups[$labelID]['modules'][] = $this->params[$index.'_' . $subcount];
				}
			}
		} else
			return;
		
		if (!empty($update_app_strings)) {
			//$contents = return_custom_app_list_strings_file_contents($selected_lang);
			foreach($update_app_strings as $labelID => $labelValue) {
				AppConfig::set_local("lang.strings.$selected_lang.app.$labelID", $labelValue);
			}
			AppConfig::save_local('lang');
			AppConfig::invalidate_cache('lang');
		}
		
		AppConfig::set_local('module_order.grouped', $tabGroups);
		AppConfig::save_local('module_order');
		AppConfig::invalidate_cache('module_order');
		
		AppConfig::cache_reset();
		
		return array(
			'wizard' => 'GroupTabs',
		);
	}

	
	private function getAvailableModules()
	{
		global $moduleList, $app_list_strings, $modSemiInvisList;
		static $availableModules = array();
		if (!empty($availableModules)) return $availableModules;
		foreach ($moduleList as $value) {
			$availableModules[$value] = array(
				'label' => $app_list_strings['moduleList'][$value],
				'value'=> $value
			);
		}
		foreach ($modSemiInvisList as $value) {
			$availableModules[$value] = array(
				'label'=> $app_list_strings['moduleList'][$value],
				'value' => $value
			);
		}
		return $availableModules;
    }
}
