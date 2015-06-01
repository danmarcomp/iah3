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


require_once('data/SugarBean.php');
require_once('modules/TaxRates/TaxRate.php');
require_once('modules/Currencies/Currency.php');

define('STANDARD_TAXCODE_ID', 'standard-tax_-code-0000-000000000001');
define('EXEMPT_TAXCODE_ID', '-99');


class TaxCode extends SugarBean {

	// Stored fields
	var $id;
	var $name;
	var $code;
	var $taxation_scheme;
	var $status;
	var $position;
	var $description;
	var $deleted;
	var $date_entered;
	var $date_modified;
	
	// Static fields
	var $table_name = "taxcodes";
	var $object_name = "TaxCode";
	var $module_dir = "TaxCodes";
	var $new_schema = true;


	function TaxCode() {
		parent::SugarBean();
	}
	
	function get_summary_text() {
		return "$this->code: $this->name";
	}
	
	function process_list_query($query, $row_offset, $limit= -1, $max_per_page = -1, $where = '') {
		$add_blank = empty($row_offset);
		if($add_blank) {
			$blank = new TaxCode();
			$blank->retrieve('-99');
			$results = parent::process_list_query($query, $row_offset, $limit, $max_per_page, $where);
			array_unshift($results['list'], $blank);
			$max_per_page = AppConfig::setting('layout.list.max_entries_per_page');
			if($results['row_count'] >= $max_per_page)
				array_pop($results['list']);
		}
		else {
			$row_offset --;
			$results = parent::process_list_query($query, $row_offset, $limit, $max_per_page, $where);
			$results['previous_offset'] ++;
			$results['next_offset'] ++;
		}

		$results['row_count'] ++;
		
		return $results;
	}
	
	/*function get_list_view_data() {
		$fields = $this->get_list_view_array();
		$fields['RATE']	= format_number($this->rate);
		return $fields;
	}*/
	
	// subpanel query
	function taxrates($id=false, $date = null) {
		global $moduleList;
		$qb_link = in_array('QBLink', $moduleList);
		if($id === false)
			$id = $this->id;
		$id_where = '';
		if($id !== 'ALL')
			$id_where = " AND rel.code_id = '$id' ";
		if ($qb_link && $date) {
			$hist_rel = " LEFT JOIN (select b.* from taxrates a left join (select * from taxrates_history WHERE date_active <= '$date' order by date_active DESC) b on a.id=b.tax_id group by b.tax_id) h ON h.tax_id=tax.id ";
			$order =  ", h.date_active ";
			$rate_f = ' IFNULL(h.rate, tax.rate) ';
		} else {
			$hist_rel = $order = '';
			$rate_f = 'tax.rate';
		}
		$query = "SELECT tax.id, tax.name, tax.status, rel.position, rel.code_id, ".
			" tax.rate std_rate, tax.compounding std_compounding, ".
			" rel.override_rate, rel.override_compounding, ".
			" IF(rel.override_rate, rel.custom_rate, $rate_f) rate, ".
			" if(rel.override_compounding, rel.custom_compounding, tax.compounding) compounding ".
			" FROM taxcodes_rates rel LEFT JOIN taxrates tax ON rel.rate_id = tax.id ".
			$hist_rel .
			" WHERE NOT rel.deleted AND NOT tax.deleted ". $id_where .
			" ORDER BY rel.position " . $order;
		return $query;
	}

	function get_tax_rates($id=false, $return_beans=true, $date = null) {
		$ret = array();
		if($id === false)
			$id = $this->id;
		if(empty($id) || $id == '-99')
			return $ret;
		$query = $this->taxrates($id, $date);
		$r = $this->db->query($query, true, "Error retrieving rates for tax code");
		while($row = $this->db->fetchByAssoc($r)) {
			if($return_beans) {
				$bean = new TaxRate();
				if($bean->retrieve($row['id'])) {
					foreach($row as $k=>$v)
						$bean->$k = $v;
					$ret[$row['id']] = $bean;
				}
			}
			else
				$ret[$row['id']] = $row;
		}
		return $ret;
	}
	
	function get_option_list($include_blank=true, $short_names=false) {
		$all = $this->get_full_list('position asc');
		if($include_blank) {
			$blank = new TaxCode(); $blank->retrieve('-99');
			array_unshift($all, $blank);
		}
		$ret = array();
		foreach($all as $code) {
			$ret[$code->id] = $code->code . ($short_names ? '' : ' : '.$code->name);
		}
		return $ret;
	}
	
	function pre_add_relationship($rel, $values) {
		if($rel == 'taxrates') {
			$rates = $this->get_tax_rates(false, false);
			$maxpos = 0;
			foreach($rates as $r) {
				$maxpos = max($r['position'], $maxpos);
			}
			if(! isset($values['position']))
				$values['position'] = $maxpos + 1;
		}
		return $values;
	}
	
	function populateFromRow($row) {
		$ret = parent::populateFromRow($row);
		if($this->id == STANDARD_TAXCODE_ID)
			$this->init_from_lookup($this->id);
		return $ret;
	}
	
	function retrieve($id = '-99', $encode = true) {
		if($id == '-99') {
			$this->init_from_lookup($id);
			return $this;
		}
		return parent::retrieve($id, $encode);
	}

	static function get_default_list_row($id='-99') {
		if(empty($id)) $id = '-99';
		$lookup = array(
			'-99' => array(
				'LBL_EXEMPT_TAXCODE_NAME',
				'LBL_EXEMPT_TAXCODE_CODE',
				0,
			),
			STANDARD_TAXCODE_ID => array(
				'LBL_STANDARD_TAXCODE_NAME',
				'LBL_STANDARD_TAXCODE_CODE',
				1,
			),
		);
		if(! isset($lookup[$id]))
			return false;
		$ret = array('id' => $id);
		list($name, $code, $pos) = $lookup[$id];
		$ret['name'] = translate($name, 'TaxCodes');
		$ret['code'] = translate($code, 'TaxCodes');
		$ret['status'] = 'Active';
		$ret['position'] = $pos;
		$ret['deleted'] = 0;
		return $ret;
	}
	
	
	static function init_position(RowUpdate &$update) {
		if(! $update->getField('position')) {
			global $db;
			$q = "SELECT MAX(position) FROM {$update->getTableName()} WHERE NOT deleted";
			$r = $db->query($q);
			$row = $db->fetchByRow($r, -1, false);
			$ord = $row ? $row[0] + 1 : 1;
			$update->set('position', $ord);
		}
	}
	
	
	static function before_save(RowUpdate &$update) {
		$id = $update->getField('id');
		if( ($id || !$update->new_record) &&  ($fix = self::get_default_list_row($id)) ) {
			$update->set($fix);
		} else {
			$position = $update->getField('position');
			if(isset($position) && $position < 2)
				$update->set('position', 2);
		}
	}

	
	function get_javascript($codes=null) {
		if(! is_array($codes)) {
			$codes = AppConfig::db_all_objects($this->object_name);
		}
		$query = $this->taxrates('ALL');
		$r = $this->db->query($query, true, "Error retrieving tax rates");
		$rates = array();
		while($row = $this->db->fetchByAssoc($r))
			$rates[$row['code_id']][$row['id']] = $row;
		$data = array();
		foreach($codes as $c) {
			$code = array('id' => $c->id, 'name' => $c->name, 'code' => $c->code, 'position' => $c->position, 'taxation_scheme' => $c->taxation_scheme);
			$code['rates'] = array();
			if(isset($rates[$c->id]))
				$code['rates'] = array_values($rates[$c->id]);
			$data[$c->id] = $code;
		}
		$json = getJSONobj();
		$ord = $json->encode(array_map('trim', array_keys($data)));
		$data = $json->encode($data);
		if(empty($ord) || $ord == '{}') $ord = '[]';
		$js = "<script type=\"text/javascript\">\n".
			"SysData.taxcodes_order = $ord;\n".
			"SysData.taxcodes = $data;\n".
			"</script>";
		return $js;
	}
		
	function init_from_lookup($id) {
		$info = $this->get_default_list_row($id);
		if($info) {
			foreach($info as $k => $v)
				$this->$k = $v;
			return true;
		}
		return false;
	}
	
	function save($check_notify=false) {
		if($this->id == '-99')
			return $this->id;
		if($this->id == STANDARD_TAXCODE_ID)
			$this->init_from_lookup($this->id);
		return parent::save($check_notify);
	}
	
	function mark_deleted($id) {
		if($id == '-99' || $id == STANDARD_TAXCODE_ID)
			return false;
		return parent::mark_deleted($id);
	}

}


?>
