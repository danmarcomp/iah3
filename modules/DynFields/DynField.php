<?php

require_once 'data/SugarBean.php';
//require_once 'modules/Administration/Common.php';
require_once 'modules/DynFields/FieldCases.php';

class DynField extends SugarBean
{
	var $table_name = "fields_meta_data";
	var $object_name = "DynField";
	var $new_schema = true;
	var $avail_fields = array();
	var $module;
	var $base_bean_name;
	var $cached_field_types = array();
	var $clear_bean = true;
	var $errors = array();
	
	function __construct($module=null, $base_bean_name=null)
	{
		parent::__construct();
		if(! $base_bean_name && $module) {
			$base_bean_name = AppConfig::module_primary_bean($module);
		}
		$this->module = $module;
		$this->base_bean_name = $base_bean_name;
		$this->getAvailableFields();
	}

	function getAvailableFields($clean=false) {
		if(! $this->avail_fields || $clean)
			$this->avail_fields = self::load_available_fields($this->module, $this->base_bean_name);

        return $this->avail_fields;
    }
    
    static function &load_available_fields($module=null, $base_bean_name=null) {
		$rows = AppConfig::db_all_rows('DynField');
		if(isset($module) || isset($base_bean_name)) {
			$ret = array();
			foreach($rows as $r) {
				if(isset($module) && $r['custom_module'] != $module)
					continue;
				if(isset($base_bean_name) && $r['custom_bean'] != $base_bean_name)
					continue;
				$ret[$r['id']] = $r;
			}
			return $ret;
		}
		return $rows;
    }


	function addUpdateField($name, $options) {
        if(empty($options['label'])){
            $options['label'] = $name;
        }
        $label = $this->addLabel($options['label']);

		$mod_name = $this->module;
		$base_bean_name = $this->base_bean_name;
        $db_name = $this->getDBName($name);
        if(! $db_name)
        	return false;
        if(substr($db_name, -2) != '_c')
        	$db_name .= '_c';
		if($db_name === 'id_c')
			return false;
		$row_id = md5($mod_name.$base_bean_name.$db_name);
		if(! empty($options['id']) && $options['id'] != $row_id)
			return false;

		$row = ListQuery::quick_fetch('DynField', $row_id);
	    if ($row != null) 
			$upd = new RowUpdate($row);
		else  {
			$upd = new RowUpdate($this->object_name);
			$options['id'] = $row_id;
			$upd->new_record = true;
		}
        $options['custom_module'] = $mod_name;
        $options['custom_bean'] = $this->base_bean_name;
        $options['name'] = $db_name;
		$upd->set($options);
		
		if($upd->save()) {
			AppConfig::set_expiry('model', time() + 100);
			require_once 'include/database/DBChecker.php';
			$checker = new DBChecker();
			$custom_model = $this->getCustomTableName();
			$checker->checkRepairModel($custom_model, DB_CHECK_STANDARD | DB_CHECK_AUDIT, true, false);

			$this->getAvailableFields(true);
			return $db_name;
		}
		
		$this->errors = $upd->getErrors();
		return false;
    }
	
	function dropField($name){
		$mod_name = $this->module;
		$base_bean_name = $this->base_bean_name;
        $db_name = $this->getDBName($name);
        if(! $db_name)
        	return false;
        if(substr($db_name, -2) != '_c')
        	$db_name .= '_c';
		if($db_name === 'id_c')
			return false;
		$row_id = md5($mod_name.$base_bean_name.$db_name);
		$row = ListQuery::quick_fetch('DynField', $row_id);
	    if ($row != null) {
	        $upd = new RowUpdate($row);
			if ($upd->deleteRow()) {
				$this->getAvailableFields(true);
				return true;
			}
		}
		return false;
    }

	function addLabel($label)
	{
        global $current_language;
        $limit = 10;
        $count = 0;
        $field_key = $this->getDBName($label);
        $curr_field_key = $this->getDBName($label);
        /*while( ! create_field_label($this->module, $current_language, $curr_field_key, $label) )
        {
            $curr_field_key = $field_key. "_$count";
            if ( $count == $limit)
            {
                return $curr_field_key;
            }
            $count++;
        }*/
        return $curr_field_key;
    }

	function getDBName($name)
	{
        // Remove any non-db friendly characters
        $return_value = preg_replace("/[^\w]+/","_",$name);
        
        return $return_value;
    }
    
    function getCustomTableName() {    	
		$base_table = AppConfig::setting("model.detail.{$this->base_bean_name}.table_name");
		if(! $base_table)
			return false;
		$table_name = $base_table . '_cstm';
		return $table_name;
    }


	function getField($name, $type='')
	{
		$db_name = $this->getDBName($name);
        if(! isset($this->avail_fields[$name]) && isset($this->avail_fields[$db_name])){
            $name = $db_name;
        }
        if(empty($type)){
            if(isset($this->avail_fields[$name])){
                $type = $this->avail_fields[$name]['data_type'];
                if($type == 'text'){
                    $type = 'textarea';
                }
            }
        }

        $field = get_custom_field_widget($type);

        if(isset($this->avail_fields[$name])){
            $field->set($this->avail_fields[$name]);
        }
        
        return $field;
    }
    
    
    function getCustomFieldDefinitions() {
		$fields = $this->getAvailableFields();
		return self::get_custom_field_definitions($fields);
    }
    
    static function get_custom_field_definitions(array $fields) {
		$defs = array();
		foreach($fields as $spec) {
			$dt = $spec['data_type'];
			$ret = array(
				'name' => $spec['name'],
				'vname' => $spec['label'],
				'type' => $dt,
				'required' => ($spec['required_option'] === 'required'),
				'len' => $spec['max_size'],
				'massupdate' => $spec['mass_update'],
				'audited' => $spec['audited'],
				'default' => $spec['default_value'],
				'duplicate_merge' => $spec['duplicate_merge'],
				'help' => $spec['help'],
                'calc_formula' => $spec['formula'],
			);
            if ($dt == 'calculated')
                $ret['editable'] = false;
			foreach($ret as $k => $v)
				if(! isset($v))
					unset($ret[$k]);
			if($dt === 'enum' || $dt === 'multienum') {
				$ret['options'] = $spec['ext1'];
				// FIXME for multienum, ext2 represents the number of displayed rows - NYI
			}
			if($dt === 'float') {
				$ret['decimal_places'] = $spec['ext1'];
			}
			if($dt === 'int') {
				$ret['min'] = $spec['ext1'];
				$ret['max'] = $spec['ext2'];
			}
			if($dt === 'ref') {
				// use given name for ID field
				$ret['id_name'] = $ret['name'] . '_id';
				$ret['rname'] = 'id';
				$ret['module'] = $spec['ext1'];
				$ret['bean_name'] = AppConfig::module_primary_bean($spec['ext1']);

				unset($ret['len']);
				unset($ret['help']);
				unset($ret['default']);
			}
			if($dt === 'text' || $dt === 'html') {
				$ret['default'] = $spec['ext4'];
			}
			if($dt == 'date') {
				$ret['default'] = $spec['ext1'];
			}
			
			$defs[$ret['name']] = $ret;
		}
		return $defs;
    }
    
    function getErrors() {
    	return $this->errors;
	}


	static public function translate_label($spec)
	{
		return translate($spec['raw_values']['label'], $spec['raw_values']['custom_module']);
	}
    
	static function listupdate_perform($mu, $perform, &$listFmt, &$list_result, $uids)
	{
		$lq = new ListQuery('DynField');
		$lq->addFilterPrimaryKey($uids);
		$lq->filter_deleted = false;
		$ret = $lq->runQuery();
		$usedBeans = array();
		foreach ($ret->getRowIndexes() as $idx) {
			$row = $ret->getRowResult($idx);
			$usedBeans[$row->getField('custom_bean')] = 1;
		}

		AppConfig::db_invalidate('DynField');
		AppConfig::invalidate_cache('model');
		foreach (array_keys($usedBeans) as $bean_name) {
			AppConfig::setting("model.custom_field_defs.$bean_name");
			$model = new ModelDef($bean_name);
			$model->getLinkDefinition('_cstm');
		}
	}
}

