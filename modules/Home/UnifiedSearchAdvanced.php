<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/*********************************************************************************
 * The contents of this file are subject to the SugarCRM Public License Version
 * 1.1.3 ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied.  See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * All copies of the Covered Code must include on each user interface screen:
 *    (i) the "Powered by SugarCRM" logo and
 *    (ii) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.  See full license for
 * requirements.
 *
 * The Original Code is: SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) 2004-2006 SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 ********************************************************************************/
/*********************************************************************************

 * Description:  TODO: To be written.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/

require_once('modules/Home/SearchViewManager.php');

class UnifiedSearchAdvanced {
	const MIN_SEARCH_CHARS = 2;
	
	var $prefix_match_only = 0;
	var $model_index;
	var $form_template = 'modules/Home/UnifiedSearchAdvancedForm.tpl';
	
	function getModelIndex() {
		if (AppConfig::is_mobile())
			$mobile_modules = AppConfig::setting('modinfo.mobile_modules');
		else
			$mobile_modules = null;
		if(! $this->model_index) {
			$all_models = AppConfig::setting("model.index.unified_search");
			$allow_models = array();
			foreach($all_models as $m) {
				$mod = AppConfig::module_for_model($m);
				if ($mobile_modules && !in_array($mod, $mobile_modules))
					continue;
				if(ACLController::checkAccess($mod, 'list'))
					$allow_models[] = $m;
			}
			$default = $titles = array();
			foreach($allow_models as $m) {
				$us = AppConfig::setting("model.detail.$m.unified_search");
				if(! is_array($us) || array_get_default($us, 'default', true))
					$default[] = $m;
				$mod = AppConfig::module_for_model($m);
				$titles[$m] = translate('LBL_MODULE_TITLE', $mod);
			}
			asort($titles);
			$this->model_index = array('all' => $all_models, 'allowed' => $allow_models, 'default' => $default, 'titles' => $titles);
		}
		return $this->model_index;
	}
	
	
	function getDropDownDiv($selected, $query_string) {
		global $mod_strings, $app_list_strings, $current_user, $app_strings, $image_path, $beanList;
		require_once('include/Sugar_Smarty.php');
		$sugar_smarty = new Sugar_Smarty();
		
		$index = $this->getModelIndex();
		
		require_once('include/layout/forms/EditableForm.php');
		$frm = new EditableForm('searchform', 'UnifiedSearchAdvancedMain');
		
		$modules_to_search = array();
		$default_mods = array();
		foreach($index['allowed'] as $module) {
			$default_mods[] = "'$module': ". (in_array($module, $index['default']) ? 1 : 0);
			$spec = array(
				'id' => 'cb_'.$module.'_f',
				'name' => 'search_mod_' . $module,
			);
			$checked = in_array($module, $selected) ? 1 : 0;
			$modules_to_search[$module] = array(
				'translated' => $index['titles'][$module],
				'checked' => $checked,
				'checkbox' => $frm->renderCheck($spec, $checked),
			);
		}
		$spec = array(
			'name' => 'prefix_match_only',
		);
		$sugar_smarty->assign('PREFIX_MATCH', $frm->renderCheck($spec, $this->prefix_match_only));
		
        $sugar_smarty->assign('query_string', $query_string);
		$sugar_smarty->assign('IMAGE_PATH', $image_path);
		$sugar_smarty->assign('MODULES_TO_SEARCH', $modules_to_search);
		$sugar_smarty->assign('cb_columns', AppConfig::is_mobile() ? 2 : 4);
		// longreach - added
		$sugar_smarty->assign('APP', $app_strings);
		$sugar_smarty->assign('MOD', $mod_strings);
		$sugar_smarty->assign('default_mods', '{'.implode(",", $default_mods).'}');
		$sugar_smarty->assign('prefix_match_only', $this->prefix_match_only);
		$sugar_smarty->assign('mobile', AppConfig::is_mobile());
		
		$spec = array('name' => 'query_string', 'width' => AppConfig::is_mobile() ? 25 : 60);
		$inp = $frm->renderSearch($spec, $query_string);
		$sugar_smarty->assign('search_input', $inp);
		$frm->exportIncludes();

		return $sugar_smarty->fetch($this->form_template);
	}
	
	
	function showSearch($model, $title, $query_string) {
		if (AppConfig::is_mobile())
			$mgr = new MobileSearchViewManager($query_string, $this->prefix_match_only);
		else
			$mgr = new StandardSearchViewManager($query_string, $this->prefix_match_only);
		if(! $mgr->initForModel($model)) {
			return false;
		}
		$mgr->loadRequest();
		$mgr->setTitle($title);
		$mgr->render();
		return $mgr->getResultCount();
	}
	
	
	function search() {
		global $beanList, $beanFiles, $current_language, $app_strings, $current_user, $mod_strings, $image_path;
		
		$query_string = trim(array_get_default($_REQUEST, 'query_string', ''));
		
		if(isset($_REQUEST['prefix_match_only']))
			$_SESSION['UnifiedSearch_prefix_match_only'] = $_REQUEST['prefix_match_only'];
		$this->prefix_match_only = array_get_default($_SESSION, 'UnifiedSearch_prefix_match_only', 0);
		
		$index = $this->getModelIndex();
		
		$search_mod = array_get_default($_REQUEST, 'search_model');
		if($search_mod && in_array($search_mod, $index['allowed'])) {
			$this->showSearch($search_mod, $index['titles'][$search_mod], $query_string);
			return;
		}
		
		echo get_module_title("Search", $mod_strings['LBL_SEARCH_RESULTS'], true);
	
		if(!empty($_REQUEST['advanced']) && $_REQUEST['advanced'] != 'false') {
			$smods = array();
			foreach($index['allowed'] as $m)
				if(! empty($_REQUEST['search_mod_'.$m]))
					$smods[] = $m;
			if($smods)
				$current_user->setPreference('globalSearch', $smods, 0, 'models'); // save selections to user preference
		} else {
			$smods = $current_user->getPreference('globalSearch', 'models');
			if($smods)
				$smods = array_intersect($smods, $index['allowed']);
		}
		if(! $smods)
			$smods = $index['default'];
		
		echo $this->getDropDownDiv($smods, $query_string);
		
		$module_results = array();
		$module_counts = array();
		$has_results = false;

		if(strlen($query_string) < self::MIN_SEARCH_CHARS || ! count($smods)) {
			echo '<br>';
            echo '<h4>'.str_replace('{N}', self::MIN_SEARCH_CHARS, translate('LBL_SEARCH_MORE_CHARS', 'Home')).'</h4>';
            return;
		}
		else {
    		foreach($smods as $model) {
    			ob_start();
    			$c = $this->showSearch($model, $index['titles'][$model], $query_string);
    			if($c !== false)
    				$module_counts[$model] = $c;
    			$module_results[$model] = ob_get_clean();
    		}
        }
        
        $has_results = array_sum($module_counts);
		
		if($has_results) {
			$found = $unfound = array();
			foreach($module_counts as $mod => $c) {
				if($c)
					$found[] = $mod;
				else
					$unfound[] = $mod;
			}
			foreach(array_merge($found, $unfound) as $mod) {
				echo "<a name=\"us_$mod\"></a>";
				echo $module_results[$mod];
			}
		}
		else {
			echo '<br>';
            echo translate('LBL_NO_RESULTS', 'Home');
            echo translate('LBL_NO_RESULTS_TIPS', 'Home');
		}
		
	}
}

?>
