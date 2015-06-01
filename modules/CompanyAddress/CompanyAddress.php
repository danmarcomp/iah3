<?php
/*
 *
 * The contents of this file are subject to the info@hand Software License Agreement Version 1.3
 *
 * ("License"); You may not use this file except in compliance with the License.
 * You may obtain a copy of the License at <http://1crm.com/pdf/swlicense.pdf>.
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the
 * specific language governing rights and limitations under the License,
 *
 * All copies of the Covered Code must include on each user interface screen:
 * (i) the 1CRM copyright notice,
 * (ii) the "Powered by the 1CRM Engine" logo, 
 *
 * (iii) the "Powered by SugarCRM" logo, and
 * (iv) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.
 * See full license for requirements.
 *
 * The Original Code is : 1CRM Engine proprietary commercial code.
 * The Initial Developer of this Original Code is 1CRM Corp.
 * and it is Copyright (C) 2004-2012 by 1CRM Corp.
 *
 * All Rights Reserved.
 * Portions created by SugarCRM are Copyright (C) 2004-2008 SugarCRM, Inc.;
 * All Rights Reserved.
 *
 */
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');


class CompanyAddress extends SugarBean {

	var $object_name='CompanyAddress';
	var $module_dir = 'CompanyAddress';
	var $new_schema = true;
	var $table_name = 'company_addresses';
	
	var $id;
	var $address; 
	var $address_city; 
	var $address_state; 
	var $address_postalcode; 
	var $address_country;
	var $address_phone;
	var $address_fax;
	var $main;
	var $address_format;
	var $logo;
	var $is_warehouse;

	var $deleted;


	function mark_deleted($id)
	{
		parent::mark_deleted($id);
		$query = "SELECT COUNT(*) AS c FROM company_addresses WHERE deleted = 0 AND main = 1";
		$res = $this->db->query($query);
		$row = $this->db->fetchByAssoc($res);
		if (!$row['c']) {
			$query = "UPDATE company_addresses SET main = 1 WHERE deleted = 0 LIMIT 1";
			$this->db->query($query);
		}
	}
	

	function getAddressArray($id = null, $encode = true)
	{
		static $cache = array();
		$array_key = empty($id) ? 0 : $id;
		if (!isset($cache[$array_key])) {
			$seed = new CompanyAddress;
			if ($id && $seed->retrieve($id, $encode) && !$seed->deleted) {
				$row = array();
				foreach ($seed->column_fields as $f) {
					$row[$f] = $seed->$f;
				}
			} else {
				$query = "SELECT * FROM company_addresses WHERE deleted = 0 AND main";
				$res = $seed->db->query($query);
				if (!($row  = $seed->db->fetchByAssoc($res, -1, $encode))) {
					$row = array();
					foreach ($seed->column_fields as $f) {
						$row[$f] = '';
					}
				}
			}
			$cache[$array_key] = $row;
		}
		return $cache[$array_key];
	}

	function getAll($encode = true)
	{
		$ret = array();
		$res = $this->db->query("SELECT * FROM company_addresses WHERE deleted = 0 ORDER BY main DESC, name");
		while ($row = $this->db->fetchByAssoc($res, $encode)) {
			$ret[] = $row;
		}
		return $ret;
	}

	function get_warehouse_list($all = false)
	{
		$all = !$all ? 'AND is_warehouse=1' : '';
		$res = $this->db->query("SELECT * FROM company_addresses WHERE deleted=0 $all ", true);
		$ret = array();
		while ($row = $this->db->fetchByAssoc($res)) {
			$ret[$row['id']] = $row;
		}
		return $ret;
	}
	
	function get_warehouse_options($selected = null, $all = false)
	{
		$options = array();
		$list = $this->get_warehouse_list($all);
		foreach ($list as $id => $wh) {
			$options[$id] = $wh['name'];
			if (empty($selected) && $wh['main_warehouse']) {
				$selected = $id;
			}
		}
		return get_select_options_with_id($options, $selected);
	}

    static function getMainWarehouseId() {
        $lq = new ListQuery('CompanyAddress', array('id'));
        $lq->addSimpleFilter('main_warehouse', 1);

        $result = $lq->runQuerySingle();
        $id = null;
        if (! $result->failed)
            $id = $result->getField('id');
        return $id;
    }

	static function before_save(RowUpdate &$upd) {
		global $db;
		
		if(! $upd->getField('is_warehouse') && $upd->getField('main_warehouse')) {
			$upd->set('main_warehouse', 0);
		}
		else if($upd->getField('is_warehouse') && ! $upd->getField('main_warehouse')) {
			$query = "SELECT COUNT(*) AS c FROM company_addresses WHERE NOT deleted AND main_warehouse = 1 AND is_warehouse = 1";
			if(! $upd->new_record) {
				$id = $upd->getPrimaryKeyValue();
				$query .= " AND id != '$id'";
			}
			$res = $db->query($query);
			$row = $db->fetchByAssoc($res);
			if(!$row['c']) {
				$upd->set('main_warehouse', 1);
			}
		}
	}
	
	static function after_save(RowUpdate &$upd) {
		global $db;
		
		$id = $upd->getPrimaryKeyValue();
		$main = $upd->getField('main');
		if($main && $upd->getFieldUpdated('main')) {
			$db->query("UPDATE company_addresses SET main = 0 WHERE id != '$id' AND NOT deleted");
		}
		$main_w = $upd->getField('main_warehouse');
		if($main_w && $upd->getFieldUpdated('main_warehouse') && $upd->getField('is_warehouse')) {
			$db->query("UPDATE company_addresses SET main_warehouse = 0 WHERE id != '$id' AND NOT deleted");
		}
	}

}
?>
