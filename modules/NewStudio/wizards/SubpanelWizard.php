<?php

require_once 'modules/NewStudio/wizards/StudioWizardBase.php';
require_once 'modules/NewStudio/wizards/SubpanelLayoutEditor.php';
require_once 'include/Sugar_Smarty.php';
require_once 'include/ListView/ListViewManager.php';
require_once 'include/config/ModelDef.php';

class SubpanelWizard extends StudioWizardBase
{
	protected $model;

	public function __construct($params)
	{
		parent::__construct($params);
		$modelName = AppConfig::module_primary_bean($this->params['edit_module']);
		$this->model = new ModelDef($modelName);

		$link = $this->model->getLinkTargetModel($this->params['subpanel']);
		$this->linkModelName = $link->getModelName();
		$this->linkModule = $link->getModuleDir();
		$joined = $link->getJoinModel();
		if ($joined) {
			$lspec = $joined->getLinkSpec();
		} else {
			$lspec = $link->getLinkSpec();
		}
		$layout = array_get_default($lspec, 'layout', 'Standard');
		$views = array(
			"views.detail.{$this->linkModule}.subpanel.$layout",
			"views.detail.{$this->linkModule}.list.$layout",
		);
		$this->detail = AppConfig::setting($views);
	}
	
	public function render()
	{
		global $pageInstance;
		$pageInstance->add_js_include('modules/NewStudio/subpanels.js', null, LOAD_PRIORITY_BODY);
		
		$le = new SubpanelLayoutEditor($this->params['edit_module'], $this->params['subpanel'], $this->params['record'], $this->params['detail_layout']);
		$le->initFromModel($this->linkModelName, $this->params['list_id']);
		$layout = AppConfig::setting("views.layout.{$this->linkModule}.{$this->detail['type']}.{$this->detail['name']}");
		$layout = array(
			'columns_spec' => $layout['columns'],
			'name' => 'unused',
		);
		$le->loadLayoutDetails($layout, '');
		$le->disableTabs(array('general', 'filters', 'sorting'));
		$le->tab = 'columns';
		echo $le->render();
	}

	public function process()
	{
		if (!empty($this->params['save'])) {
			require_once 'include/config/format/ConfigWriter.php';
			$json = getJSONObj();
			$columns = $json->decode($this->params['columns']);
			
			ListLayoutEditor::clean_column_input($columns);


			$layout = AppConfig::setting("views.layout.{$this->linkModule}.{$this->detail['type']}.{$this->detail['name']}");
			$layout['columns'] = $columns;
			$filename = AppConfig::custom_dir() . "modules/{$this->linkModule}/new_views/subpanel.{$this->detail['name']}.php";
			$cw = new ConfigWriter;
			$cw->writeFile($filename, array(
					'detail' => array('type' => 'subpanel'),
					'layout' => $layout,
				)
			);
			AppConfig::invalidate_cache('views');
			return array(
				'path' => 'async.php?module=' . $this->params['edit_module']
						. '&action=DetailView'
						. '&record=' . $this->params['record']
						. '&inline=true'
						. '&layout=' . $this->params['detail_layout'],
				'external' => true,
			);
			//http://debian/git/index.php?module=Meetings&action=ListView&record=1580df62-2d8d-e2e4-d7cd-50c7628d2ca1&subpanel=invitees
		}
	}

	
}

