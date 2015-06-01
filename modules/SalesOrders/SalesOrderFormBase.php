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


require_once('modules/Quotes/QuoteFormBase.php');


class SalesOrderFormBase extends QuoteFormBase {
	var $log;
	var $db;
    
	function SalesOrderFormBase()
	{
		parent::QuoteFormBase();
	}
		
	function checkForDuplicates($prefix){
		require_once('include/formbase.php');
		require_once('modules/SalesOrders/SalesOrder.php');
		$focus = new SalesOrder();
		$query = '';
		$baseQuery = 'select id, name, amount, date_closed  from sales_orders where deleted!=1 and (';
	
		if(isset($_POST[$prefix.'name']) && !empty($_POST[$prefix.'name'])){
			$query = $baseQuery ."  name like '%".$_POST[$prefix.'name']."%'";
			$query .= getLikeForEachWord('name', $_POST[$prefix.'name']);
		}
		if(!empty($query)){
			$rows = array();
			require_once('include/database/PearDatabase.php');
			$db = new PearDatabase();
			$result =& $db->query($query.');');
			if($db->getRowCount($result) == 0){
				return null;
			}
			for($i = 0; $i < $db->getRowCount($result); $i++){
				$rows[$i] = $db->fetchByAssoc($result, $i);
			}
			return $rows;
		}
		return null;
	}

	function getForm($prefix, $mod='SalesOrders'){
		if(!empty($mod)){
			global $current_language;
			$mod_strings = return_module_language($current_language, $mod);
		} else global $mod_strings;
		
		global $app_strings;
		
		$lbl_save_button_title = $app_strings['LBL_SAVE_BUTTON_TITLE'];
		$lbl_save_button_key = $app_strings['LBL_SAVE_BUTTON_KEY'];
		$lbl_save_button_label = $app_strings['LBL_SAVE_BUTTON_LABEL'];
		
		
		$the_form = get_left_form_header($mod_strings['LBL_NEW_FORM_TITLE']);
		$the_form .= <<<EOQ
				<form name="{$prefix}EditView" onSubmit="return check_form('{$prefix}EditView')" method="POST" action="index.php">
					<input type="hidden" name="{$prefix}module" value="SalesOrder">
					<input type="hidden" name="${prefix}action" value="Save">
EOQ;
		$the_form .= $this->getFormBody($prefix, $mod, "{$prefix}EditView");
		$the_form .= <<<EOQ
				<input title="$lbl_save_button_title" accessKey="$lbl_save_button_key" class="button" type="submit" name="button" value="  $lbl_save_button_label  " >
				</form>
		
EOQ;
		$the_form .= get_left_form_footer();
		$the_form .= get_validate_record_js();
		
		return $the_form;
	}
	
	// create invoice main // vlozeni jednoduche invoice
	function getFormBody($prefix, $mod='SalesOrders', $formname=''){
		if(!empty($mod)){
			global $current_language;
			$mod_strings = return_module_language($current_language, $mod);
		} else global $mod_strings;
		
		global $app_strings;
		global $app_list_strings;
		global $theme;
		global $current_user;
		
		$lbl_required_symbol = $app_strings['LBL_REQUIRED_SYMBOL'];
		$lbl_invoice_subject = $mod_strings['LBL_INVOICE_SUBJECT'];
		
		$user_id = $current_user->id;
		
		require_once('include/TimeDate.php');
		$timedate = new TimeDate();
		$ntc_date_format = $timedate->get_user_date_format();
		$cal_dateformat = $timedate->get_cal_date_format();
		
			
		// Set up account popup
		require_once('include/JSON.php');
		$popup_request_data = array(
			'call_back_function' => 'set_return',
			'form_name' => $formname,
			'field_to_name_array' => array(
				'id' => 'account_id',
				'name' => 'account_name',
				),
			);
		$json = new JSON(JSON_LOOSE_TYPE);
		$encoded_popup_request_data = $json->encode($popup_request_data);
		
		
		// Unimplemented until jscalendar language files are fixed
		// $cal_lang = (empty($cal_codes[$current_language])) ? $cal_codes[$default_language] : $cal_codes[$current_language];
		$cal_lang = "en";
		
		
		$the_form = <<<EOQ
		<p>
		
					<input type="hidden" name="{$prefix}record" value="">
					<input type="hidden" name="{$prefix}assigned_user_id" value='${user_id}'>
		
		
		
				$lbl_invoice_subject&nbsp;<span class="required">$lbl_required_symbol</span><br>
				<span><input name='{$prefix}name' type="text" value=""></span><br>
EOQ;


$disabled = '';
if (ACLController::moduleSupportsACL('Accounts')  && !ACLController::checkAccess('Accounts', 'list', true)) {
    $disabled = ' disabled="disabled" ';
}
		$the_form .= <<<EOQ
				${mod_strings['LBL_ACCOUNT_NAME']}&nbsp;<span class="required">$lbl_required_symbol</span><br>
				<span><input class="sqsEnabled" autocomplete="off" name='account_name' type='text' id="account_name" value="" size="16"></span><input name='account_id' type="hidden" value='' id="account_id">&nbsp;<input  title="{$app_strings['LBL_SELECT_BUTTON_TITLE']}" accessKey="{$app_strings['LBL_SELECT_BUTTON_KEY']}" type="button" class="button" value='{$app_strings['LBL_SELECT_BUTTON_LABEL']}' $disabled
					onclick='open_popup("Accounts", 600, 400, "", true, false, {$encoded_popup_request_data});'><br>
EOQ;
		$the_form .= <<<EOQ
				${mod_strings['LBL_DUE_DATE']}&nbsp;<span class="required">$lbl_required_symbol</span><br>
				<span class="dateFormat">$ntc_date_format</span><br>
				<input name='{$prefix}due_date' size='12' maxlength='10' id='{$prefix}jscal_field' type="text" value=""> <img src="themes/$theme/images/jscalendar.gif" alt="{$app_strings['LBL_ENTER_DATE']}"  id="jscal_trigger" align="absmiddle"><br>
EOQ;
		$the_form .= <<<EOQ
				</p>
		
				<script type="text/javascript">
					Calendar.setup ({
						inputField : "{$prefix}jscal_field", ifFormat : "$cal_dateformat", showsTime : false, button : "jscal_trigger", singleClick : true, step : 1
					});
				</script>
EOQ;
		
$qsAccount = array( 
    'method' => 'query',
    'modules' => array('Accounts'), 
	'group' => 'or', 
	'field_list' => array('name', 'id', ), 
    'populate_list' => array('account_name', 'account_id'), 
	'conditions' => array(array('name'=>'name','op'=>'like_custom','end'=>'%','value'=>'')), 
	'order' => 'name', 
	'limit' => '30',
	'no_match_text' => $app_strings['ERR_SQS_NO_MATCH']
); 
$quicksearch_js = '<script type="text/javascript" language="javascript">sqs_objects = {"account_name" : ' . $json->encode($qsAccount) . '}</script>';
echo $quicksearch_js;

		require_once('include/javascript/javascript.php');
		require_once('modules/SalesOrders/SalesOrder.php');
		$javascript = new javascript();
		$javascript->setFormName($formname);
		$javascript->setSugarBean(new SalesOrder());
		$javascript->addRequiredFields($prefix);
        $javascript->addToValidateBinaryDependency('account_name', 'alpha', $app_strings['ERR_SQS_NO_MATCH_FIELD'] . $mod_strings['LBL_ACCOUNT_NAME'], 'false', '', 'account_id');
        $the_form .=$javascript->getScript();
        $the_form.= <<<SCRIPT
<script type="text/javascript">
addToValidate('{$prefix}EditView', '{$prefix}account_name', 'alpha', true, '{$mod_strings['LBL_ACCOUNT_NAME']}' );
</script>
SCRIPT;
		
		return $the_form;
	}
	
	function handleSave($prefix,$redirect=true, $useRequired=false){
		
		require_once('modules/SalesOrders/SalesOrder.php');
		require_once('log4php/LoggerManager.php');
		require_once('include/formbase.php');
		
		$focus = new SalesOrder();
		if($useRequired &&  !checkRequired($prefix, array_keys($focus->required_fields))){
			return null;
		}
		
		$focus = populateFromPost($prefix, $focus);
		if (isset($GLOBALS['check_notify'])) {
			$check_notify = $GLOBALS['check_notify'];
		}
		else {
			$check_notify = FALSE;
		}
		
		if (empty($_POST['record']) && empty($_POST['dup_checked'])) {
			$duplicateSalesOrder = $this->checkForDuplicates($prefix);
			if(isset($duplicateSalesOrder)){
				$get='module=SalesOrders&action=ShowDuplicates';
				
				//add all of the post fields to redirect get string
				foreach ($focus->column_fields as $field) 
				{
					if (!empty($focus->$field))
					{
						$get .= "&SalesOrders$field=".urlencode($focus->$field);
					}	
				}
				
				foreach ($focus->additional_column_fields as $field) 
				{
					if (!empty($focus->$field))
					{
						$get .= "&SalesOrders$field=".urlencode($focus->$field);
					}	
				}
	
				//create list of suspected duplicate account id's in redirect get string
				$i=0;
				foreach ($duplicateSalesOrder as $account)
				{
					$get .= "&duplicate[$i]=".$account['id'];
					$i++;
				}
	
				//add return_module, return_action, and return_id to redirect get string
				$get .= "&return_module=";
				if(!empty($_POST['return_module'])) $get .= $_POST['return_module'];
				else $get .= "SalesOrders";
				$get .= "&return_action=";
				if(!empty($_POST['return_action'])) $get .= $_POST['return_action'];
				else $get .= "DetailView";
				if(!empty($_POST['return_id'])) $get .= "&return_id=".$_POST['return_id'];
	
				//echo $get;
				//die;
				//now redirect the post to modules/SalesOrders/ShowDuplicates.php
				header("Location: index.php?$get");
				return null;
			}
		}
		$focus->related_invoice_id=$_REQUEST['related_invoice_id'];
		
		$focus->save($check_notify);
		$return_id = $focus->id;
		$GLOBALS['log']->debug("Saved record with id of ".$return_id);
		if($redirect){
			handleRedirect($return_id,'SalesOrders');
		}else{
			return $focus;	
		}
	}

}

?>
