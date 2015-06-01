<?php

class TemplateParser
{
	static $fields = array();

	static function parse_generic($text, array $objects, $params = array())
	{
		$is_array = is_array($text);
		if ($is_array) {
			$texts =& $text;
			$ret = array();
		} else {
			$texts = array($text);
		}
		$pattern = '~\\$([A-Za-z][A-Za-z0-9_]*\\b)~';
		$disable_cache = array_get_default($params, 'disable_cache');
		$aliases = array_get_default($params, 'aliases', array());
		$primary = array_get_default($params, 'primary', false);
		foreach ($texts as $key => $txt) {
			foreach ($objects as $module => $id) {
				$model = AppConfig::module_primary_bean($module);
				$alias = strtolower(AppConfig::module_primary_bean(array_get_default($aliases, $module, $module)));
				$fields = self::getFieldDefs($model);
				$prefixes = array($alias . '_');
				if ($alias == 'acase') {
					$alias = 'case';
					$prefixes[] = 'case_';
				}
				$r = self::fetch($model, $id, $disable_cache);
				$row = array();
				if ($r) {
					foreach ($r as $k => $v) {
						foreach ($prefixes as $prefix) {
							$row[$prefix . $k] = $v;
						}
					}
					foreach ($prefixes as $prefix) {
						$row[$prefix . 'iah_url'] = AppConfig::site_url() . 'index.php?module=' . $module . '&action=DetailView&record=' . $r['id'];
					}

					if (!$primary || $primary == $module) {
						$acc_id = '';
						foreach ($fields as $k => $def) {
							if (array_get_default($def, 'type') == 'ref' && array_get_default($def, 'bean_name') == 'Account') {
								$acc_id = array_get_default($def, 'id_name', $k . '_id');
								break;
							}
						}
						if ($acc_id) {
							$acc_row = self::fetch('Account', $r[$acc_id], $disable_cache);
							if ($acc_row) {
								foreach ($acc_row as $k => $v) {
									$row['account_' . $k] = $v;
									foreach ($prefixes as $prefix) {
										$row[$prefix . 'account_' . $k] = $v;
									}
								}
								$row['account_iah_url'] = AppConfig::site_url() . 'index.php?module=Accounts&action=DetailView&record=' . $acc_row['id'];
								foreach ($prefixes as $prefix) {
									$row[$prefix . 'account_iah_url'] = $row['account_iah_url'];
								}
							}
						}
					}
				}

				$matches = array();
				preg_match_all($pattern, $txt, $matches, PREG_OFFSET_CAPTURE);
				$offset = 0;
				foreach ($matches[1] as $match) {
					$field_name = $match[0];
					$default = false;
					if(substr($field_name, 0, strlen($alias)) == $alias && substr($field_name, -5) == '_name') {
						$alt_name = substr($field_name, strlen($alias)+1, -5);
						if(isset($fields[$alt_name]) && $fields[$alt_name]['type'] == 'ref')
							$default = array_get_default($r, $alt_name, $default);
					}
					$value = array_get_default($row, $field_name, $default);
					if ($value !== false) {
						$match_len = strlen($match[0]);
						$txt = substr_replace($txt, $value, $offset + $match[1] - 1, $match_len + 1);
						$offset += strlen($value) - $match_len - 1;
					}
				}
			}
			if (empty($params['preserve_placeholders']))
				$txt = preg_replace($pattern, '', $txt);
			if ($is_array)
				$ret[$key] = $txt;
			else
				$ret = $txt;
		}
		return $ret;
	}

	private static function fetch($model, $id, $disable_cache = false)
	{
		static $cache = array();
		$key = $model . '_' . $id;
		if (!$disable_cache && isset($cache[$key])) {
			return $cache[$key];
		}
		$fields = self::getFieldDefs($model);
		$row = ListQuery::quick_fetch_row($model, $id, array_keys($fields), array('process_results' => true));
		if ($row) {
			foreach ($row as $k => $v) {
				if (!isset($fields[$k]))
					continue;
				$def = $fields[$k];
				if ($def['type'] == 'enum') {
					$row[$k] = translate($def['options'], $model, (string)$v);
					if (is_array($row[$k])) $row[$k] = '';
				}
			}
		}
		return $cache[$key] = $row;
	}

	private static function getFieldDefs($model)
	{
		if (!isset(self::$fields[$model])) {
			$def = new ModelDef($model);
			self::$fields[$model] = $def->getFieldDefinitions();
			$custom = AppConfig::setting('model.custom_field_defs.' . $model);
			if (is_array($custom))
				self::$fields[$model] += $custom;
		}
		return self::$fields[$model];
	}
}

