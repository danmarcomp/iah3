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


require_once('log4php/LoggerManager.php');
require_once('include/database/PearDatabase.php');
require_once('data/SugarBean.php');
require_once('modules/Currencies/Currency.php');


class TaxRate extends SugarBean 
{
	// Stored fields
	var $id;
	var $name;
	var $status; 
	var $compounding;
	var $rate;
	var $deleted;
	var $date_entered;
	var $date_modified;
	var $hide = '';
	var $unhide = '';

	var $table_name = "taxrates";
	var $object_name = "TaxRate";
	var $module_dir = "TaxRates";
	var $new_schema = true;

	var $column_fields = Array("id"
		,"name"
		,"rate"
		,"status"
		,"compounding"
        ,"deleted"
        ,"date_entered"
        ,"date_modified"
		);
	
	
	var $required_fields = array('name'=>1, 'rate'=>2, 'status'=>3);
	

	function TaxRate() 
	{
		parent::SugarBean();
	}

	static function getDefaultTaxName(){
		return translate('LBL_NONE', 'TaxRates');	
	}
	
	function process_list_query($query, $row_offset, $limit= -1, $max_per_page = -1, $where = '') {
		global $action;
		$add_blank = true;
		if($action == 'Popup') {
			$add_blank = false;
		}
		$show_blank = $add_blank && empty($row_offset);
		if($show_blank) {
			$blank = new TaxRate();
			$blank->retrieve('-99');
			
			$results = parent::process_list_query($query, $row_offset, $limit, $max_per_page, $where);
			array_unshift($results['list'], $blank);
			$max_per_page = AppConfig::setting('layout.list.max_entries_per_page');
			if($results['row_count'] >= $max_per_page)
				array_pop($results['list']);
			$results['row_count'] ++;
		}
		else {
			if($add_blank)
				$row_offset --;
			$results = parent::process_list_query($query, $row_offset, $limit, $max_per_page, $where);
			if($add_blank) {
				$results['previous_offset'] ++;
				$results['next_offset'] ++;
				$results['row_count'] ++;
			}
		}
		
		return $results;
	}
	
	function get_list_view_data() {
        $fields = $this->get_list_view_array();
        //$fields['RATE']	= format_number($this->rate);
        return $fields;
	}
	 
	 function list_view_parse_additional_sections(&$list_form)
	{
		global $isMerge;
		
		if(isset($isMerge) && $isMerge && $this->id != '-99'){
		$list_form->assign('PREROW', '<input name="mergecur[]" type="checkbox" value="'.$this->id.'">');
		}
		return $list_form;
	}
	
	static function get_default_list_row($id='-99') {
		$row = array(
			'id' => '-99',
			'name' => self::getDefaultTaxName(),
			'rate' => '0.00',
			'status' => 'Active',
			'deleted' => 0,
		);
		return $row;
	}
	
	
	static function before_save(RowUpdate &$update) {
		if($update->getField('id') == '-99') {
			$fix = self::get_default_list_row();
			$update->set($fix);
		}
	}

	
	function loadDefaultTaxRate() {
		foreach(self::get_default_list_row() as $k => $v)
			$this->$k = $v;
	}

     function retrieve($id, $encode = true){
     	if($id == '-99'){
     		$this->loadDefaultTaxRate();
     	}else{
     		parent::retrieve($id, $encode);	
     	}
     	if(!isset($this->name) || $this->deleted == 1){
     		$this->loadDefaultTaxRate();
     	}
     	return $this;
     }


	// $date in DB format (YYYY-MM-DD)	
	function setRateOnDate($rate, $date)
	{
		global $moduleList;
		$qb_link = in_array('QBLink', $moduleList);
		if ($qb_link) {
			if (!empty($this->id) && !$this->new_with_id) {
				$query = sprintf(
					"INSERT INTO taxrates_history (id, tax_id, date_active, date_modified, rate) values('%s', '%s', '%s', '%s', %f)",
					create_guid(), 
					$this->db->quote($this->id),
					$this->db->quote($date),
					gmdate('Y-m-d H:i:s'),
					$rate
				);
				$this->db->query($query, true);
				$query = sprintf(
					"SELECT id from taxrates_history where date_active > '%s'",
					$this->db->quote($date)
				);
				$res = $this->db->query($query, true);
				if (!$this->db->fetchByAssoc($res)) {
					$this->rate = $rate;
				}
			}
		} else {
			$this->rate = $rate;
		}
		$this->save();
		return $this->id;
	}

	function getHistory()
	{
		$history = array();
		$query = sprintf(
			"SELECT * from taxrates_history where tax_id = '%s' ORDER BY date_active DESC",
			$this->db->quote($this->id)
		);
		$res = $this->db->query($query, true);
		while ($row = $this->db->fetchByAssoc($res)) {
			$history[] = $row;
		}
		return $history;
	}

	function get_summary_text() {
		return '' . $this->name;
	}

	function get_javascript($rates = null) {
		$data = array();
		if(! is_array($rates)) {
			$rates = $this->get_full_list('name');
			$data = array(
				'-99' => array('id' => '-99', 'name' => '', 'rate' => 0)
			);
		}
		if($rates)
		foreach($rates as $r) {
			$rate = array('id' => $r->id, 'name' => $r->name, 'rate' => $r->rate, 'compounding' => $r->compounding);
			$data[$r->id] = $rate;
		}
		$json = getJSONobj();
		$ord = $json->encode(array_map('trim', array_keys($data)));
		$data = $json->encode($data);
		if(empty($ord) || $ord == '{}') $ord = '[]';
		$js = "<script type=\"text/javascript\">\n".
			"SysData.taxrates_order = $ord;\n".
			"SysData.taxrates = $data;\n".
			"</script>";
		return $js;
	}
}


?>
