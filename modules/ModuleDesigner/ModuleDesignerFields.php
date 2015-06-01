<?php

class ModuleDesignerFields
{

	public static $dataTypes = array(
		'varchar', 'text', 'int', 'double', 'bool', 'email',
		'enum', 'multienum', 'date', 'url', 'html', 'calculated',
		'ref', 'currency', 'name',

		'module_name',
	);

	public function __construct($module_name)
	{
		$this->module_name = $module_name;
		$this->model_name = AppConfig::setting("modinfo.by_name.$module_name.detail.primary_bean");
	}

	public function save($fields)
	{
		if (!empty($fields)) {
			$updateLang = array();
			require_once 'include/config/format/ConfigParser.php';
			require_once 'include/config/format/ConfigWriter.php';
			$orig_filename = "modules/{$this->module_name}/models/bean.{$this->model_name}.php";
			$filename = AppConfig::custom_dir() . "modules/{$this->module_name}/models/bean.{$this->model_name}.php";
			$defs = AppConfig::setting("model.fields.{$this->model_name}");
			$orig = ConfigParser::load_file($orig_filename);
			$content = ConfigParser::load_files(array($orig_filename, $filename));

			$createLinks = array();
			foreach ($fields as $fid => $field) {
				$isNew = strpos($fid, 'newfield~') === 0;
				if ($isNew && $field['type'] == 'ref') {
					$createLinks[] = $field;
				}
				$name = $field['name'];
				unset($field['name']);
				if (strlen(array_get_default($field, 'label', ''))) {
					$langKey = 'MB_LABEL_' . strtoupper($name);
					$field['vname'] = $langKey;
					$updateLang[$langKey] = $field['label'];
					unset($field['label']);
				}
				$def = array_get_default($defs, $name, array());
				$configName = array_get_default($def, 'from_app_field', $name);
				$configValue = array_get_default($orig['fields'], $configName, null);
				$isStandard = !is_null($configValue);
				if (!$isStandard)
					$configValue = array();
				foreach ($field as $k => $v) {
					if ($def[$k] !== $v || !$isStandard)
						$configValue[$k] = $v;
				}
				if ($configValue['type'] == 'bool')
					$configValue['default'] = $configValue['default'] ? 1 : 0;
				if (!$isStandard) {
					unset($configValue['source']);
				}
				$content['fields'][$configName] = $configValue;
			}

			$links_rels =  $this->createLinksAndRels($createLinks);
			$links = array_merge(array_get_default($content, 'links', array()), $links_rels['links']);
			$relationships = array_merge(array_get_default($content, 'relationships', array()), $links_rels['relationships']);
			$cw = new ConfigWriter;
			$cw->writeDiff($filename, $content, $orig);
			if (!empty($updateLang)) {
				foreach ($updateLang as $k => $v) {
					AppConfig::set_local("lang.strings.base.{$this->module_name}.$k", $v);
				}
				AppConfig::save_local('lang');
			}
			require_once 'include/database/DBChecker.php';
			$checker = new DBChecker();
			$checker->reloadModels();
			$checker->checkRepairModel($this->model_name, DB_CHECK_STANDARD | DB_CHECK_AUDIT, true, false);
			return array(
				'redirect', 
				array(
					'module' => 'ModuleDesigner',
					'action' => 'index',
					'layout' => '', 
				)
			);
		}
	}

	private function createLinksAndRels($fields)
	{
		$rels = $links = array();
		foreach ($fields as $f) {
			$modules = array();
			if (empty($f['bean_name'])) {
				foreach ($GLOBALS['app_list_strings']['moduleListSingular'] as $k => $v) {
					$bean = AppConfig::module_primary_bean($k);
					if ($bean) {
						$modules[] = $k;
					}
				}
			} else {
				$modules[] = '';
			}
			foreach ($modules as $mod) {
				$rel = array();
				$rel_name = 'mb_rel_' . $this->module_name . '_' . $f['name'];
				$link_name = 'mb_link_' . $this->module_name . '_' . $f['name'];
				if ($mod) {
					$rel_name .= '_' . $mod;
					$link_name .= '_' . $mod;
				}
				$rel['key'] = $f['name'] . '_id';
				$rel['target_key'] = 'id';
				if ($mod) {
					$rel['role_column'] = $f['name'] . '_mb_module_';
					$rel['role_value'] = $mod;
					$rel['target_bean'] = AppConfig::module_primary_bean($mod);
				} else {
					$rel['target_bean'] = $f['bean_name'];
					$mod = AppConfig::module_for_model($f['bean_name']);
				}
				$rel['relationship_type'] = 'one-to-many';
				$rels[$rel_name] = $rel;

				$link = array(
					'relationship' => $rel_name,
					'module' => $mod,
					'bean_name' =>  $rel['target_bean'],
					'vname' => 'MB_LINK_' . strtoupper($f['name']  . $mod),
				);
				$links[$link_name] = $link;
			}
		}
		return array(
			'links' => $links,
			'relationships' => $rels,
		);
	}
	
	public function getFieldListHtml() {
        $fields = $this->getModelFields();
        $html = '';

        for ($i = 0; $i < sizeof($fields); $i++) {
            $html .= '<div id="newfield_' .$fields[$i]. '" class="edit_layout_wrapper" onclick="ModuleDesignerFields.addToFormula(this);">$' .$fields[$i]. '</div>';
        }

        return $html;
    }

    public function getFunctionListHtml() {
        require_once 'include/layout/FormulaParser.php';
        $functions = FormulaParser::getFunctions();
        $html = '';

        foreach ($functions as $func => $tooltip) {
            $html .= '<div id="newfunc_' .$func. '" class="edit_layout_wrapper" onclick="ModuleDesignerFields.addToFormula(this);" onmouseover="return SUGAR.popups.tooltip(\''.$tooltip.'\', this);">' .$func. '</div>';
        }

        return $html;
    }

    private function getModelFields() {
		$edit_model = new ModelDef(AppConfig::module_primary_bean($this->module_name));
		return $edit_model->getAllDisplayFields();
    }

}

