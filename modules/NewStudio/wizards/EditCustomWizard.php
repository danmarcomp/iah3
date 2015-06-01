<?php

require_once 'modules/NewStudio/wizards/StudioWizardBase.php';
require_once 'modules/DynFields/DynField.php';
require_once 'include/ListView/ListViewManager.php';

class EditCustomWizard extends StudioWizardBase
{
	var $errors = array();

	public function process()
	{
		$edit_mod = array_get_default($this->params, 'edit_module');
		if($edit_mod)
			$bean_name = AppConfig::module_primary_bean($edit_mod);
		else
			$bean_name = null;
		if (isset($this->params['DeleteCustom'])) {
			if (!empty($this->params['edit'])) {
				$base = ListQuery::quick_fetch('DynField', $this->params['edit']);
				$upd = RowUpdate::for_result($base);
				$upd->markDeleted();
				
				AppConfig::db_invalidate('DynField');
				AppConfig::invalidate_cache('model');
				AppConfig::setting("model.custom_field_defs.$bean_name");
				$model = new ModelDef($bean_name);
				$model->getLinkDefinition('_cstm');
				
				return array(
					'wizard' => 'EditCustom',
					'edit_module' => $this->params['edit_module'],
				);
			}
		} elseif (!empty($this->params['save'])) {
			$fields = array(
				'data_type',
				'required_option',
				'max_size',
				'audited',
				'ext1',
				'default_value',
				'mass_update',
                'formula'
			);
						
			if (!empty($this->params['edit'])) {
				$base = ListQuery::quick_fetch('DynField', $this->params['edit']);
			}
			if(! empty($base))
				$upd = RowUpdate::for_result($base);
			else
				$upd = RowUpdate::blank_for_model('DynField');
			
			foreach ($fields as $f) {
				$upd->set($f, array_get_default($this->params, $f));
			}
			if ($this->params['data_type'] == 'date') {
				global $timedate;
				//$upd->set('default_value', $timedate->to_db_date($this->params['default_value'], false));
			}
			$upd->set(array(
				'name' => $this->params['field_name'],
				'custom_module' => $this->params['edit_module'],
				'custom_bean' => $bean_name,
			));

			$langKey = strtoupper('LBL_CSTM_' . $this->params['field_name']);
			AppConfig::set_local("lang.strings.current.{$this->params['edit_module']}.$langKey", $this->params['label']);
			AppConfig::save_local('lang');
			$upd->set('label', $langKey);

			try {
				$upd->save();
				AppConfig::db_invalidate('DynField');
				AppConfig::invalidate_cache('model');

				// re-load custom table def
				AppConfig::setting("model.custom_field_defs.$bean_name");
				$model = new ModelDef($bean_name);
				$model->getLinkDefinition('_cstm');

				require_once 'include/database/DBChecker.php';
				$checker = new DBChecker();
				$checker->reloadModels();
				$cust_table = AppConfig::setting("model.detail.$bean_name.table_name") . '_cstm';
				$checker->checkRepairModel($cust_table, DB_CHECK_STANDARD | DB_CHECK_AUDIT, true, false);
				return array(
					'wizard' => 'EditCustom',
					'edit_module' => $this->params['edit_module'],
				);
			} catch(Exception $e) {
				$this->errors[] = $e->getMessage();
				pr2($this->errors); exit;
			}
		}
	}

	public function render()
	{
		if (!isset($this->params['edit_module'])) {
			return $this->renderModules();
		} elseif (!isset($this->params['edit'])) {
			return $this->renderFieldsList();
		}
		return $this->renderEdit();
	}

	private function renderModules()
	{
		global $mod_strings, $app_list_strings;
		$sep = translate('LBL_SEPARATOR', 'app');
		echo get_module_title('Administration', $mod_strings['LBL_MODULE_TITLE'] . $sep . $mod_strings['LBL_EDIT_CUSTOM'] . $sep . $mod_strings['LBL_SELECT_MODULE'], false);
		
		$cust_count = array();
		$defs = AppConfig::setting('model.custom_field_defs');
		if($defs) {
			foreach($defs as $bean => $rows)
				$cust_count[$bean] = count($rows);
		}
		
		$modules = AppConfig::setting('modinfo.index.normal');
		$modules = array_merge($modules, AppConfig::setting('modinfo.index.manual'));
		asort($modules);
		$i = 0;
		echo "<table border='0' cellpadding='0' cellspacing='8' width='700'>";
		foreach ($modules as $mod) {
			if (($i & 3) == 0) {
				echo "<tr>";
			}
			$bean = AppConfig::module_primary_bean($mod);
			if(! $bean) continue;
			$modName = array_get_default($app_list_strings['moduleList'], $mod, $mod);
			$icon = get_image($bean, '');
			$c = array_get_default($cust_count, $bean);
			$c = $c ? " ($c)" : '';
			echo "<td>$icon <a href=\"index.php?module=NewStudio&wizard=EditCustom&edit_module=$mod\" class=\"body\">$modName</a>{$c}&nbsp;&nbsp;</td>";
			if (($i & 3) == 3) {
				echo "</tr>";
			}
			$i++;
		}
		if (($i & 3) != 3) {
			while ($i & 3) {
				echo "<td>&nbsp;</td>";
				$i++;
			}
			echo "</tr>";
		}
		echo "</table>";
	}

	private function renderFieldsList()
	{
		global $app_list_strings, $mod_strings;
		$mod = $this->params['edit_module'];
		$sep = translate('LBL_SEPARATOR', 'app');
		if (!defined('ASYNC_ENTRY')) {
			echo get_module_title('Administration', $mod_strings['LBL_MODULE_TITLE'] . $sep . $mod_strings['LBL_EDIT_CUSTOM'] . $sep . $app_list_strings['moduleList'][$mod], false);
		}
		$_REQUEST['custom_module'] = $mod;
		$_REQUEST['query'] = 1;
		$lv = new ListViewManager('listview', true);
        $lv->show_create_button = false;
        $lv->show_filter = false;
		$lv->show_tabs = false;
		//$lv->show_mass_update = false;
		$lv->show_title = false;
		$lv->addHiddenFields(array('edit_module' => $mod, 'wizard' => 'EditCustom'));
		$lv->loadRequest();
                	$detail_link = isset($this->detail_link_url) ? $this->detail_link_url : '#';
		if(! $lv->initModuleView('DynFields', 'Standard'))
			ACLController::displayNoAccess();
		$buttons = array();
		$buttons['create'] = array(
			'icon' => 'icon-edit',
			'label' => translate('LBL_CREATE_BUTTON_LABEL'),
			'perform' => "window.location.href='index.php?module=NewStudio&wizard=EditCustom&edit_module=$mod&edit=';",
			'type' => 'button',
			'params' => array(
				'toplevel' => 1,
			),
		);
		$fmt = $lv->getFormatter();
		$fmt->addButtons($buttons);
        $fmt->detail_link_url = 'index.php?module=NewStudio&action=index&wizard=EditCustom&edit={ID}&edit_module=' . $mod;
		$lv->render();
	}

	private function renderEdit()
	{
		global $app_list_strings, $mod_strings, $app_strings;
		$mod = $this->params['edit_module'];
		$sep = translate('LBL_SEPARATOR', 'app');
		echo get_module_title('Administration', $mod_strings['LBL_MODULE_TITLE'] . $sep . $mod_strings['LBL_EDIT_CUSTOM'] . $sep . $app_list_strings['moduleList'][$mod], false);
		$id = $this->params['edit'];
		$params = array();
		
		$fields = array(
			'label',
			'max_size',
			'data_type',
			'audited',
			'ext1',
			'default_value',
			'mass_update',
            'formula',
            'required_option',
		);
			
		if ($id && ($cf = ListQuery::quick_fetch('DynField', $id))) {
			$params['name'] = $cf->getField('name');
			foreach($fields as $f)
				$params[$f] = $cf->getField($f);
			$params['label'] = translate($cf->getField('label'), $this->params['edit_module']);
		}
		if (!empty($this->params['save'])) {
			foreach ($fields as $f) {
				$params[$f] = array_get_default($this->params, $f);
			}
		}
		
		$dom_opts = array_keys(AppConfig::setting('lang.lists.current.app', array()));
		$exclude = array_merge(
			AppConfig::setting("lang.detail.current.app.lists_locked", array()),
			AppConfig::setting("lang.detail.current.app.lists_generated", array()),
			AppConfig::setting("lang.detail.current.app.lists_hidden", array())
		);
		$dom_opts = array_diff($dom_opts, $exclude);
		sort($dom_opts);
		$params['dom_opts'] = $dom_opts;

		$date_default_opts = array('' => '');
		$date_default_opts += AppConfig::setting('lang.lists.current.NewStudio.date_default_values', array());
		$params['date_default_opts'] = $date_default_opts;
		
		$beans = AppConfig::setting('modinfo.primary_beans');
		foreach ($beans as $mod => $bean) {
			$name = array_get_default($GLOBALS['app_list_strings']['moduleListSingular'], $mod, $bean);
			$params['rel_modules'][$mod] = $name;
		}

		global $pageInstance;
		$pageInstance->add_js_include('modules/NewStudio/custom.js', null, LOAD_PRIORITY_BODY);
		$json = getJSONObj();
		$paramsJS = $json->encode($params);
		$pageInstance->add_js_literal('NewStudio.init(' . $paramsJS . '); NewStudio.renderForm();', null, LOAD_PRIORITY_FOOT);

        $dataTypes = array(
			'varchar', 'text', 'int', 'double', 'bool', 'email',
			'enum', 'multienum', 'date', 'url', 'html',

			'calculated', 'ref', 'currency', 'name',
		);

        $typeSelect = '<select id="data_type" name="data_type" onchange="NewStudio.renderForm();">';
		foreach ($dataTypes as $t) {
			$sel = ($t == @$params['data_type']) ? 'selected="selected"' : '';
			$typeSelect .= '<option value="' . $t . '" ' . $sel . ' >';
			$typeSelect .= translate('LBL_TYPE_' . strtoupper($t));
			$typeSelect .= '</option>';
		}
		$typeSelect .= '</select>';
		
		$nameVal = to_html(array_get_default($params, 'name'));
		$labelVal = to_html(array_get_default($params, 'label'));
		$auditChecked = array_get_default($params, 'audited') ? 'checked="checked"' : '';
		$massChecked = array_get_default($params, 'mass_update') ? 'checked="checked"' : '';
		$reqSelect = '<select id="required_option" name="required_option">';
		$sel = (array_get_default($params, 'required_option') == 'optional') ? 'selected="selected"' : '';
		$reqSelect .= "<option value=\"optional\" $sel>Optional</option>";
		$sel = (array_get_default($params, 'required_option') == 'required') ? 'selected="selected"' : '';
		$reqSelect .= "<option value=\"required\" $sel>Required</option>";
		$reqSelect .= "</select>";

		$html = <<<HTML
<form method="post" action="index.php" id="StudioCustomForm" autocomplete="off">
<input type="hidden" name="module" value="NewStudio">
<input type="hidden" name="action" value="index">
<input type="hidden" name="wizard" value="EditCustom">
<input type="hidden" name="edit_module" value="{$this->params['edit_module']}">
<input type="hidden" name="edit" value="$id">
<input type="hidden" name="save" value="1">
<table class="tabForm">
<tbody id="custom_fields_main">
<tr>
<td class="dataLabel">{$mod_strings['LBL_DATA_TYPE']}</td>
<td class="dataField">
$typeSelect
</td></tr>

<tr>
<td class="dataLabel">{$mod_strings['LBL_FIELD_NAME']}</td>
<td class="dataField"><input type="text" name="field_name" id="field_name" value="{$nameVal}"></td>
</tr>

<tr>
<td class="dataLabel">{$mod_strings['LBL_FIELD_LABEL']}</td>
<td class="dataField"><input type="text" name="label" id="label" value="{$labelVal}"></td>
</tr>

<tr id="audit_row">
<td class="dataLabel">{$mod_strings['LBL_AUDIT']}</td>
<td class="dataField"><input type="checkbox" name="audited" id="audited" value="1" {$auditChecked}></td>
</tr>

<tr id="mass_update_row">
<td class="dataLabel">{$mod_strings['LBL_MASSUPDATE']}</td>
<td class="dataField"><input type="checkbox" name="mass_update" id="mass_update" value="1" {$massChecked}></td>
</tr>

<tr>
<td class="dataLabel">{$mod_strings['LBL_REQUIRED']}</td>
<td class="dataField">$reqSelect</td>
</tr>


</tbody>

<tbody id="custom_fields_body">
</tbody>

<tbody id="custom_fields_formula" style="display: none;">
<tr>
    <td class="dataLabel">&nbsp;</td>
    <td class="dataLabel">
        <table>
            <tr>
            <td class="dataLabel"><div style="font-weight: bold;">{$mod_strings['LBL_FORMULA_FUNTIONS']}</div></td>
            <td class="dataLabel"><div style="font-weight: bold;">{$mod_strings['LBL_FORMULA_FIELDS']}</div></td>
            </tr>
            <tr>
            <td class="dataField">
                <div style="max-height: 100px; min-height: 10px ! important; background: none repeat scroll 0 0 #FAFAFA;  width: 200px;" class="dialog-content" id="le_fields_content">
                {$this->getFunctionListHtml()}
                </div>
            </td>
            <td class="dataField">
                <div style="max-height: 100px; min-height: 10px ! important; width: 300px; background: none repeat scroll 0 0 #FAFAFA; overflow-x: hidden;"  class="dialog-content" id="re_fields_content">
                {$this->getFieldListHtml()}
                </div>
            </td>
            </tr>
        </table>
    </td>
</tr>
</tbody>

<tbody id="custom_fields_foot">
<tr><td colspan="2">
<div id="custom_errors">
</div>
</tr>
<tr><td colspan="2">
<button type="submit" onclick="return NewStudio.save();" class="input-button input-outer"><div class="input-icon icon-accept left"></div><span class="input-label">{$app_strings['LBL_SAVE_BUTTON_LABEL']}</span></button>
<button type="submit" class="input-button input-outer" name="DeleteCustom"><div class="input-icon icon-delete left"></div><span class="input-label">{$app_strings['LBL_DELETE_BUTTON_LABEL']}</span></button>
</td></tr>
</tbody>
</table>
</form>
HTML;
		echo $html;
	}

    private function getFieldListHtml() {
        $fields = $this->getModelFields();
        $html = '';

        for ($i = 0; $i < sizeof($fields); $i++) {
            $html .= '<div id="newfield_' .$fields[$i]. '" class="edit_layout_wrapper" onclick="NewStudio.addToFormula(this);">$' .$fields[$i]. '</div>';
        }

        return $html;
    }

    private function getFunctionListHtml() {
        require_once('include/layout/FormulaParser.php');
        $functions = FormulaParser::getFunctions();
        $html = '';

        foreach ($functions as $func => $tooltip) {
            $html .= '<div id="newfunc_' .$func. '" class="edit_layout_wrapper" onclick="NewStudio.addToFormula(this);" onmouseover="return SUGAR.popups.tooltip(\''.$tooltip.'\', this);">' .$func. '</div>';
        }

        return $html;
    }

    private function getModelFields() {
        $edit_model = new ModelDef(AppConfig::module_primary_bean($this->params['edit_module']));
        return $edit_model->getAllDisplayFields();
    }
}

