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

 
 class ListDiscounts {
	var $focus = null;
	var $list = null;
	var $javascript = '';
	
	function lookupDiscounts($status='Active', $type = 'percentage'){
		require_once('modules/Discounts/Discount.php');
		$this->focus = new Discount();
		if(!empty($type)) {
			$where = "discount_type = '$type'";
			$rows = $this->focus->get_full_list('name', $where);
			$this->focus->retrieve('-99');
			if(is_array($rows)){
				$rows = array_merge(array($this->focus), $rows);
			}else{
				$rows = array($this->focus);	
			}
		} else {
			$rows = AppConfig::db_all_objects('Discount');
		}
		if($status) {
			foreach(array_keys($rows) as $k)
				if($rows[$k]->status != $status)
					unset($rows[$k]);
		}
		$this->list = $rows;
	}
	
	function handleAdd(){
			global $current_user;
			if($current_user->is_admin){
			if(isset($_POST['edit']) && $_POST['edit'] == 'true' && isset($_POST['name']) && !empty($_POST['name']) && isset($_POST['rate']) && !empty($_POST['rate']) ){
				require_once('modules/Discounts/Discount.php');
				$discount = new Discount();
				if(isset($_POST['record']) && !empty($_POST['record'])){
					$discount->retrieve($_POST['record']);
					$discount->format_all_fields();
				}
				$discount->name = $_POST['name'];
				$discount->status = $_POST['status'];
				$discount->rate = $_POST['rate'];
				$discount->save();
				$this->focus = $discount;
			}
			}
		
	}
	
	function getJavascript() {
		require_once('include/JSON.php');
		$json = new JSON(JSON_LOOSE_TYPE);
		$order = array('-99');
		$all = array();
		$all['-99'] = array(
			'id' => '-99',
			'name' => translate('LBL_NONE', 'app'),
			'rate' => 0,
			'amount' => 0,
			'type' => 'StdPercentDiscount',
			'related_type' => 'Discounts',
		);
		if(!empty($this->list)) {
            foreach($this->list as $data) {
				$order[] = $data->id;
                $all[$data->id] = array(
                	'id' => $data->id,
                	'name' => $data->name,
                	'rate' => $data->rate,
                	'amount' => $data->fixed_amount,
                	'type' => ($data->discount_type == 'percentage' ? 'StdPercentDiscount' : 'StdFixedDiscount'),
                	'related_type' => 'Discounts',
                );
		    }
		}
		$this->javascript = '<script type="text/javascript">';
		$this->javascript .= 'SysData.discounts_order = ' . (count($order) ? $json->encode($order) : '[]') . ";\n";
		$this->javascript .= 'SysData.discounts = ' . $json->encode($all) . ";\n";
		
	    $this->javascript .= <<<EOS
			function get_discount (id) {
				if(typeof(Discounts[id]) != 'undefined')
					return Discounts[id].rate;
			}
EOS;
		      
		return $this->javascript . "</script>";

        
	}
	
	
	function getSelectOptions($id = ''){
		global $current_user;
		$options = '';
		$this->lookupDiscounts();
		
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
	
	/*
	function getTable(){
		$this->lookupCurrencies();
		$usdollar = translate('LBL_US_DOLLAR');
		$currency = translate('LBL_CURRENCY');
		$currency_sym = translate('LBL_CURRENCY_SYMBOL');
		$rate = translate('LBL_RATE');
		$add = translate('LBL_ADD');
		$delete = translate('LBL_DELETE');
		$update = translate('LBL_UPDATE');
		
		$form = $html = "<br><table cellpadding='0' cellspacing='0' border='0'  class='tabForm'><tr><td><tableborder='0' cellspacing='0' cellpadding='0'>";
		$form .= <<<EOQ
					<form name='DeleteTax' action='index.php' method='post'><input type='hidden' name='action' value='{$_REQUEST['action']}'>
					<input type='hidden' name='module' value='{$_REQUEST['module']}'><input type='hidden' name='deleteTax' value=''></form>

					<tr><td><B>$currency</B></td><td><B>ISO 4217</B>&nbsp;</td><td><B>$currency_sym</B></td><td colspan='2'><B>$rate</B></td></tr>
					<tr><td>$usdollar</td><td>USD</td><td>$</td><td colspan='2'>1</td></tr>
					<form name="UpdateTax" action="index.php" method="post"><input type='hidden' name='action' value='{$_REQUEST['action']}'>
					<input type='hidden' name='module' value='{$_REQUEST['module']}'>
EOQ;
		if(isset($this->list ) && !empty($this->list )){
		foreach ($this->list as $data){
			
			$form .= '<tr><td><input type="hidden" name="id[]" value="'.$data->id.'">'.$data->name. '<input type="hidden" name="name[]" value="'.$data->name.'"></td><td>'.$data->rate.'&nbsp;</td><td><input type="text" name="rate[]" value="'.$data->rate.'"><td>&nbsp;<input type="button" name="delete" class="button" value="'.$delete.'" onclick="document.forms[\'DeleteTax\'].deleteTax.value=\''.$data->id.'\';document.forms[\'DeleteTax\'].submit();"> </td></tr>';
		}
		}
		$form .= <<<EOQ
					<tr><td></td><td></td><td></td><td></td><td></td><td>&nbsp;<input type='submit' name='Update' value='$update' class='button'></TD></form> </td></tr>
					<tr><td colspan='3'><br></td></tr>
					<form name="AddTax" action="index.php" method="post">
					<input type='hidden' name='action' value='{$_REQUEST['action']}'>
					<input type='hidden' name='module' value='{$_REQUEST['module']}'>
					<tr><td><input type = 'text' name='addname' value=''>&nbsp;</td><td colspan='2'>&nbsp;<input type ='text' name='addrate'></td><td>&nbsp;<input type='submit' name='Add' value='$add' class='button'></td></tr>
					</form></table></td></tr></table>
EOQ;
	return $form;
		
	}*/
				
		
}


?>
