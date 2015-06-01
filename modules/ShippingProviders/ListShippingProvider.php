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


require_once('modules/ShippingProviders/ShippingProvider.php');
 
 class ListProvider{
	var $focus = null;
	var $list = null;
	var $javascript = '<script>';
	function lookupShippingProvider(){
		$this->focus = new ShippingProvider();
		$this->list = $this->focus->get_full_list('name');
		$this->focus->retrieve('-99');
	  	if(is_array($this->list)){
			$this->list = array_merge(Array($this->focus), $this->list);
	  	} else{
	  		$this->list = Array($this->focus);	
	  	} 
		
	}
	function handleAdd(){
		global $current_user;
		if($current_user->is_admin){
			if(isset($_POST['edit']) && $_POST['edit'] == 'true' && isset($_POST['name']) && !empty($_POST['name'])){
				$sp = new ShippingProvider();
				if(isset($_POST['record']) && !empty($_POST['record'])){
	
					$sp->retrieve($_POST['record']);
				}
				$sp->name = $_POST['name'];
				$sp->status = $_POST['status'];
				$sp->save();
				$this->focus = $sp;
			}
		}
	}
		
	function handleUpdate(){
		global $current_user;
		if($current_user->is_admin) {
			if(isset($_POST['id']) && !empty($_POST['id'])&&isset($_POST['name']) && !empty($_POST['name'])) {
				$ids = $_POST['id'];
				$names= $_POST['name'];
							
				$size = sizeof($ids);
				if($size != sizeof($names)) {
					return;	
				}
					$temp = new ShippingProvider();
				for($i = 0; $i < $size; $i++){
					$temp->id = $ids[$i];
					$temp->name = $names[$i];
					$temp->save();
				}
			}
		}
	}
	
	
	function getSelectOptions($id = ''){
		global $current_user;
		//$this->javascript .="var ConversionRates = new Array(); \n";
		$options = '';
		$this->lookupShippingProvider();
		//$setLastRate = false;
		if(isset($this->list ) && !empty($this->list )) {
			foreach ($this->list as $data) {
				if($data->status == 'Active') {
					if($id == $data->id) {
						$options .= '<option value="'. $data->id . '" selected>'.$data->name.'</option>';
					}else{
						$options .= '<option value="'. $data->id . '">'.$data->name.'</option>';
					}
				}
			}
		}
		return $options;
	}
	
	/* obsolete
	function getTable(){
		$this->lookupShippingProvider();
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
		if(isset($this->list ) && !empty($this->list )) {
			foreach ($this->list as $data) {
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
	}
	*/
}


?>
