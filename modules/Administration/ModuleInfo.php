<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once('data/SugarBean.php');

// we only extend SugarBean to make presentation (via ListView) easy
class ModuleInfo extends SugarBean {
	var $id;
	var $name;
	var $beanInfo;
	var $object_name;

	var $field_defs = array();
	
	function ModuleInfo($name) {
		$this->name = $name;
		$this->id = $name;
		$this->beanInfo = array();
	}
	function add_bean_info($name, $file, $seed) {
		$bean = array('name' => $name, 'file' => $file);
		$bean['seed'] =& $seed;
		$bean['records'] = $this->query_record_count($seed);
		if($bean['records'] === false)
			return false;
		$this->beanInfo[] = $bean;
	}
	
	function &get_list() {
		global $beanList, $beanFiles;
		$module_info = array();
		
		foreach($beanList as $mod=>$bean) {
			$p = strpos($mod, '_');
			if($p === false)
				$mname = $mod;
			else
				$mname = substr($mod, 0, $p);
			if($mname == 'Import' || $mname == 'Administration' || $mname == 'Trackers')
				continue;

			if(empty($beanFiles[$bean]))
				continue;
			$beanFile = $beanFiles[$bean];
			require_once($beanFile);
			$seed = new $bean();
			
			if(empty($seed->module_dir))
				continue;
			$mod_dir = $seed->module_dir;
			
			if(!isset($module_info[$mod_dir]))
				$module_info[$mod_dir] = new ModuleInfo($mod_dir);
			
			$module_info[$mod_dir]->add_bean_info($bean, $beanFile, $seed);
		}
		
		ksort($module_info);
		return $module_info;
	}
	
	function clear_module() {
		global $current_user;
		foreach(array_keys($this->beanInfo) as $k) {
			$seed = $this->beanInfo[$k]['seed'];
			$query = "UPDATE $seed->table_name SET deleted=1 WHERE deleted=0";
			if($this->name == 'Users')
				$query .= " AND id != '1' AND id != '$current_user->id'";
			$seed->db->query($query, true);
		}
	}
	
	function get_list_view_data() {
		$num_records = array();
		foreach(array_keys($this->beanInfo) as $k) {
			$bean =& $this->beanInfo[$k];
			$num_records[] = $bean['seed']->object_name . ' ('.$bean['records'].')';
		}
		return array(
			'MODULE' => $this->name,
			'NUM_RECORDS' => join(', ', $num_records),
		);
	}
	
	function query_record_count(&$seed) {
		$query = "SELECT COUNT(*) AS records FROM $seed->table_name WHERE deleted=0";
		$result = $seed->db->limitQuery($query,0,1,false,"Error querying row count for ".$seed->object_name);
		if(!$result)
			return false;
		$row = $seed->db->fetchByAssoc($result);
		return $row['records'];
	}
}

?>
