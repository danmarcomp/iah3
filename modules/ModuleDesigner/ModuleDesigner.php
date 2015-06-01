<?php

require_once 'include/config/format/ConfigWriter.php';
require_once 'include/config/format/ConfigParser.php';
require_once 'include/upload_file.php';

class ModuleDesigner
{
	public function __construct($module_name, $input)
	{
		$this->input = $input;
		$this->module_name = $module_name;
		$this->errors = array();
	}

	public function save()
	{
		if (empty($this->input['__save']))
			return false;
		if ($this->module_name == '') {
			$this->errors[] = 'LBL_MISSING_MODULE_NAME';
			return false;
		}
		
		$icon = new UploadFile('icon');
		$icon_path = null;
		if ($icon->confirm_upload(true)) {
			$ext = strtolower($icon->file_ext);
			if($ext == 'jpeg') $ext = 'jpg';
			if ($ext == 'gif' || $ext == 'jpg' || $ext == 'png') {
				$icon_path = "modules/{$this->module_name}/icon.$ext";
				$icon->final_move($icon_path, true);
				$this->input['icon_path'] = $icon_path;
			} else {
				$icon->clean_incoming();
			}
		}
		
		$info = $this->generateModInfo();
		$this->generateLanguage($info['by_designer']);
		$dir = 'modules/' . $this->module_name . '/';
		mkdir_recursive($dir . 'metadata');
		mkdir_recursive($dir . 'models');
		mkdir_recursive($dir . 'views');

		$cw = new ConfigWriter;
		if ($info['bean'] || $info['by_designer']) {
			$cw->writeFile($dir . 'metadata/module_info.php', $info['info']);
			if($info['bean'] || $info['update_bean']) {
				$b = !empty($info['bean']) ? $info['bean'] : $info['update_bean'];
				$cw->writeFile($dir . "models/bean.{$this->module_name}.php", $b);
			}
			if($info['bean']) {
				$bean_path = $info['bean']['detail']['bean_file'];
				if(! file_exists($bean_path)) {
					$f = fopen($bean_path, 'w');
					fwrite($f, <<<TEXT
<?php
require_once 'data/SugarBean.php';
class {$this->module_name} extends SugarBean {
	var \$new_schema = true;
	var \$object_name = "{$this->module_name}";
	var \$module_dir = "{$this->module_name}";
}
TEXT
					);
					fclose($f);
				}
				$this->createDefaultViews();
			}
			$repair = true;
			AppConfig::invalidate_cache('display');
			AppConfig::invalidate_cache('views');
		} else {
			$cw->writeDiff(AppConfig::custom_dir() . '/' . $dir . 'metadata/module_info.php', $info['info'], $info['orig_info']);
		}
		
		AppConfig::invalidate_cache('modinfo');
		AppConfig::invalidate_cache('model');
		if(! empty($repair)) {
			require_once 'include/database/DBChecker.php';
			$checker = new DBChecker();
			$checker->checkRepairModel($this->module_name, DB_CHECK_STANDARD | DB_CHECK_AUDIT, true, false);
		}
		AppConfig::invalidate_cache('lang');
		AppConfig::invalidate_cache('acl');
		return true;
	}

	public function getErrors()
	{
		return $this->errors;
	}

	public function isCustom()
	{
		return !is_dir('modules/' . $this->module_name);
	}

	private function generateModInfo()
	{
		$primary_bean = AppConfig::setting("modinfo.by_name.{$this->module_name}.detail.primary_bean");
		$by_designer = AppConfig::setting("modinfo.by_name.{$this->module_name}.detail.created_by_module_designer");

		$bean = null;
		$update_bean = null;
		$ret = $orig = array();

		if (!$primary_bean) {
			$ret['detail']['created_by_module_designer'] = $by_designer = true;
			$bean = array(
				'detail' => array(
					'type' => 'bean',
					'bean_file' => "modules/{$this->module_name}/{$this->module_name}.php",
					'optimistic_locking' => true,
					'table_name' => strtolower($this->module_name) . uniqid('_'),
					'primary_key' => 'id',
					'default_order_by' =>  'name',
				),
				'fields' => array(
					'app.id' => array(),
					'app.date_entered' => array(),
					'app.date_modified' => array(),
					'app.modified_user' => array(),
					'app.assigned_user' => array(),
					'app.created_by_user' => array(),
					'app.deleted' => array(),
					'name' => array(
						'type' =>  'name',
						'vname' => 'LBL_NAME',
						'required' => true,
						'default' => "",
					),
				),
			);
			$primary_bean = $this->module_name;
		} else {
			$fname = 'modules/' . $this->module_name . '/metadata/module_info.php';
			$ret = $orig = ConfigParser::load_file($fname);
			if ($by_designer) {
				$dir = 'modules/' . $this->module_name . '/';
				$update_bean = ConfigParser::load_file($dir . "models/bean.{$this->module_name}.php");
				$update_bean['detail']['reportable'] = empty($this->input['reportable']) ? false : true;
			}
		}

		$ret['detail']['default_group'] = $this->input['tab_group'];
		$ret['detail']['primary_bean'] = $primary_bean;
		if(! empty($this->input['icon_path']))
			$ret['detail']['icon'] = $this->input['icon_path'];

		$ret['acl']['editable'] = $this->input['acl_level'] == 'editable';
		if (!$ret['acl']['editable']) {
			foreach(array('list', 'view', 'edit', 'delete') as $action) {
				$ret['acl']['fixed'][$action] = ACLAction::levelIntToString($this->input['acl_' . $action]);
			}
		}
		return array(
			'by_designer' => $by_designer,
			'info' => $ret,
			'orig_info' => $orig,
			'bean' => $bean,
			'update_bean' => $update_bean,
		);

	}
	
	private function generateLanguage($native)
	{
		$base = AppConfig::setting('lang.base');
		$meta_p = "modules/{$this->module_name}/language/lang.$base.meta.php";
		$orig = null;
		$cw = new ConfigWriter;
		if(file_exists($meta_p)) {
			$meta = $orig = ConfigParser::load_file($meta_p);
		} else
			$meta = array();
		$meta['detail']['label'] = $this->input['mod_title'];
		if(isset($orig) && ! $native) {
			$cw->writeDiff(AppConfig::custom_dir() . $meta_p, $meta, $orig);
		} else {
			$cw->writeFile($meta_p, $meta);
		}
		
		$strings_p = "modules/{$this->module_name}/language/lang.$base.strings.php";
		if($native && ! file_exists($strings_p)) {
			$strings = array();
			$cw->writeFile($strings_p, $strings);
		}
	}

	private function createDefaultViews()
	{
		$views = array(
			'edit' => array(
				'detail' => array(
					'type' => 'editview',
					'title' => 'LBL_MODULE_TITLE',
				),
				'layout' => array(
					'sections' => array(
						array(
							'elements' => array(
								'name',
							),
						),
					),
				),
			),
			'view' => array(
				'detail' => array(
					'type' => 'view',
					'title' => 'LBL_MODULE_TITLE',
				),
				'layout' => array(
					'sections' => array(
						array(
							'elements' => array(
								'name',
							),
						),
					),
				),
			),
			'list' => array(
				'detail' => array(
					'type' => 'list',
					'title' => 'LBL_MODULE_TITLE',
				),
				'layout' => array(
					'columns' => array(
						'name',
						'assigned_user',
					),
				),
			),
		);

		$cw = new ConfigWriter;
		$dir = 'modules/' . $this->module_name . '/';
		foreach ($views as $view => $data) {
			$path = $dir . 'views/' . $view .  '.Standard.php';
			if(! file_exists($path))
				$cw->writeFile($path, $data);
		}

	}

}


