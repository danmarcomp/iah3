<?php
require_once 'modules/NewStudio/wizards/StudioWizardBase.php';

class RenameTabsWizard extends StudioWizardBase
{
	public function process()
	{
		if (!isset($this->params['tabs_data'])) return;

		require_once 'include/config/format/ConfigWriter.php';
		$json = getJSONObj();
		$tabs = $json->decode($this->params['tabs_data']);
		$cw = new ConfigWriter;

		foreach ($tabs as $lang => $list) {
			foreach ($list as $mod => $label) {
				$detail = AppConfig::setting("lang.detail.$lang.$mod");
				if (array_get_default($detail, 'label') !== $label) {
					$detail['label'] = $label;
					$fname = AppConfig::custom_dir() . "modules/$mod/language/lang.$lang.meta.php";
					$cw->writeFile($fname, array('detail' => $detail));
				}
			}
		}
		
		AppConfig::save_local('lang');

		return array(
			'wizard' => 'RenameTabs',
		);
	}

	public function render()
	{
		global $pageInstance;
		
		global $mod_strings;
		$sep = translate('LBL_SEPARATOR', 'app');
		echo get_module_title('Administration', $mod_strings['LBL_MODULE_TITLE'] . $sep . $mod_strings['LBL_RENAME_TABS'], false);

		$languages = AppConfig::get_languages();
		$detail = AppConfig::setting("lang.detail");
		$tabs = array();
		foreach ($detail as $lang => $modules) {
			foreach ($modules as $mod => $meta) {
				if ($mod == 'app')
					continue;
				$tabs[$lang][$mod] = array_get_default($meta, 'label', $mod);
			}
		}

		$pageInstance->add_js_include('modules/NewStudio/tabs.js', null, LOAD_PRIORITY_HEAD);
		$json = getJSONObj();
		$js = "RenameTabs.init(" . $json->encode($tabs) . ", " . $json->encode($languages) . ");";
		$pageInstance->add_js_literal($js, null, LOAD_PRIORITY_HEAD);
		$js = "RenameTabs.render();";
		$pageInstance->add_js_literal($js, null, LOAD_PRIORITY_FOOT);

		echo <<<HTML
<form method="post" action="index.php">
<input type="hidden" name="module" value="NewStudio">
<input type="hidden" name="action" value="index">
<input type="hidden" name="wizard" value="RenameTabs">
<input type="hidden" name="save" value="1">
<input type="hidden" name="tabs_data" id="tabs_data" value="">

<table id="RenameTabs"></table>
</form>

HTML;
	}

}

