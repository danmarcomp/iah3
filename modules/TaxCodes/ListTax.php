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

 
 class ListTax{
	var $focus = null;
	var $list = null;
	var $javascript = '';
	
	function lookupTaxRates($status='Active'){
		require_once('modules/TaxRates/TaxRate.php');
		$this->focus = new TaxRate();
		if(!empty($status))
			$where = "status = '$status'";
		$this->list = $this->focus->get_full_list('name', $where);
		$this->focus->retrieve('-99');
	  	if(is_array($this->list)){
		$this->list = array_merge(Array($this->focus), $this->list);
	  	}else{
	  		$this->list = Array($this->focus);	
	  	}
	  	
		
	}
	function handleAdd(){
			global $current_user;
			if($current_user->is_admin){
			if(isset($_POST['edit']) && $_POST['edit'] == 'true' && isset($_POST['name']) && !empty($_POST['name']) && isset($_POST['rate']) && !empty($_POST['rate']) ){
				require_once('modules/TaxRates/TaxRate.php');
				$tax = new TaxRate();
				if(isset($_POST['record']) && !empty($_POST['record'])){
					$tax->retrieve($_POST['record']);
					$tax->format_all_fields();
				}
				$tax->name = $_POST['name'];
				$tax->status = $_POST['status'];
				$tax->compounding = $_POST['compounding'];
				$tax->rate = $_POST['rate'];
				$tax->save();
				$this->focus = $tax;
			}
		}
	}
	
	function getJavascript() {
		require_once('include/JSON.php');
		$json = new JSON(JSON_LOOSE_TYPE);
		$order = array();
		$rates = array();
		if(!empty($this->list)) {
            foreach($this->list as $data) {
				$order[] = $data->id;
                $rates[$data->id] = array('name' => $data->name, 'rate' => $data->rate,
                	'compounding' => $data->compounding,
                );
		    }
		}
		$this->javascript = '<script type="text/javascript">';
		$this->javascript .= 'SysData.taxrates_order = ' . (count($order) ? $json->encode($order) : '[]') . ";\n";
		$this->javascript .= 'SysData.taxrates = ' . $json->encode($rates) . ";\n";
		
	    $this->javascript .= <<<EOS
			function get_taxrate (id) {
				if(typeof(TaxRates[id]) != 'undefined')
					return TaxRates[id].rate;
			}
EOS;
		      
		return $this->javascript . "</script>";

        
	}
	
	
	function getSelectOptions($id = ''){
		global $current_user;
		$options = '';
		$this->lookupTaxRates();
		
		if(isset($this->list ) && !empty($this->list )){
			foreach ($this->list as $data){
				if($data->status == "Active"){
					$selected = ($id == $data->id) ? ' selected' : '';
					$rate = str_replace('.00', '', number_format($data->rate, 2));
					$label = $data->name;
					if($data->id != -99)
						$label .= ' ('.$rate.'%)';
					$options .= '<option value="' .$data->id. '"' .$selected. '>' .$label. '</option>';
				}
			}
		}
		return $options;
	}
		
}


?>
