<?php
require_once 'include/ListView/ListLayoutEditor.php';

class SubpanelLayoutEditor extends ListLayoutEditor
{

	function __construct($module_name, $panel_name, $record, $detail_layout)
	{
		parent::__construct();
		$this->panel_name = $panel_name;
		$this->module_name = $module_name;
		$this->record = $record;
		$this->detail_layout = $detail_layout;
	}
	function getEditorFormLayout($is_report)
	{
		$layout = parent::getEditorFormLayout($is_report);
		$layout['form_buttons']['save']['onclick'] = 'subpanelSaveLayout(this.form); return false;';
		$layout['form_buttons']['cancel']['onclick'] = 'document.close_popup(); return false;';
		$layout['form_hidden_fields']['subpanel'] = $this->panel_name;
		$layout['form_hidden_fields']['edit_module'] = $this->module_name;
		$layout['form_hidden_fields']['record'] = $this->record;
		$layout['form_hidden_fields']['detail_layout'] = $this->detail_layout;
		return $layout;
	}
}


