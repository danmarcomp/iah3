<?php

class ConfiguratorModel extends ModelDef {
	var $name = 'Configurator';
	
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
}

?>
