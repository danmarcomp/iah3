<?php

class ModuleDesignerModel extends ModelDef {
	var $name = 'ModuleDesigner';
	
	function __construct() {
	}

	function getDisplayField($name) {
		$def = parent::getDisplayField($name);
		if($def) {
			if(! isset($def['type'])) $def['type'] = 'varchar';
			$def['name'] = $name;
			$def['source']['type'] = 'db';
		}
		return $def;
	}
	
	function getDisplayFieldDefinitions() {
		$defs = parent::getDisplayFieldDefinitions();
		foreach($defs as $k => &$def) {
			if(! isset($def['type'])) $def['type'] = 'varchar';
			$def['name'] = $k;
			$def['source']['type'] = 'db';
		}
		return $defs;
	}

	public static function tabGroups()
	{
		$ret = array();
		$groups = array_keys(AppConfig::setting('module_order.grouped'));
		foreach ($groups as $group) {
			$ret[$group] = translate($group, 'app');
		}
		return $ret;
	}

	public static function aclOptions()
	{
		require_once 'modules/ACLActions/actiondefs.php';
		return array(
	 		ACL_ALLOW_ADMIN => translate('LBL_ACCESS_ADMIN', 'ACLActions'),
	 		ACL_ALLOW_OWNER => translate('LBL_ACCESS_OWNER', 'ACLActions'),
			ACL_ALLOW_ALL => translate('LBL_ACCESS_ALL', 'ACLActions'),
			ACL_ALLOW_NONE => translate('LBL_ACCESS_NONE', 'ACLActions'),
			ACL_ALLOW_DISABLED => translate('LBL_ACCESS_DISABLED', 'ACLActions'),
		);
	}
	
	public static function populateValues($module_name, $input)
	{
		$info =  AppConfig::setting("modinfo.by_name.$module_name.detail", array());
		$ret = array(
			'mod_name!regexp' => $module_name,
			'tab_group' => array_get_default($info, "default_group", 'LBL_TABGROUP_SALES_MARKETING'),
		);
		
		$default = AppConfig::setting("modinfo.by_name.$module_name.acl.fixed.access", 'all');

		foreach (array('list', 'view', 'edit', 'delete') as $type) {
			$ret['acl_' . $type] = ACLAction::levelStringToInt(
				AppConfig::setting("modinfo.by_name.$module_name.acl.fixed.$type", 
					AppConfig::setting("modinfo.by_name.$module_name.acl.defaults.$type", $default)
				)
			);
		}
		$ret['mod_title'] = translate('LBL_MODULE_TITLE', $module_name);
		if (empty($module_name) || $ret['mod_title'] == 'LBL_MODULE_TITLE')
			$ret['mod_title'] = '';

		if (AppConfig::setting("modinfo.by_name.$module_name.acl.editable", true)) {
			$ret['acl_level'] = 'editable';
		} else {
			$ret['acl_level'] = 'fixed';
		}
		global $theme;
		$path = "themes/$theme/images/$module_name.png";
		if (!is_readable($path))
			$path = "themes/$theme/images/$module_name.gif";
		if (is_readable($path))
			$ret['current_icon'] = $path;
		$model_name = AppConfig::setting("modinfo.by_name.$module_name.detail.primary_bean");
		$ret['reportable'] = AppConfig::setting("model.detail.{$model_name}.reportable", true);
		$ret['created_by_module_designer'] = AppConfig::setting("modinfo.by_name.{$module_name}.detail.created_by_module_designer");

		foreach ($ret as $k => $v) {
			if (!isset($input[$k]))
				$input[$k] = $v;
		}
		return $input;
	}
}

