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


class ShippingProvider extends SugarBean 
{
	// Stored fields
	var $id;
	var $name;
	var $status; 
	var $deleted;
	var $date_entered;
	var $date_modified;
	var $hide = '';
	var $unhide = '';

	var $table_name = "shipping_providers";
	var $object_name = "ShippingProvider";
	var $module_dir = "ShippingProviders";
	var $new_schema = true;

	var $column_fields = Array("id"
		,"name"
		,"status"
        ,"deleted"
        ,"date_entered"
        ,"date_modified"
		);
	var $list_fields;
	
	
	var $required_fields = array('name'=>1, 'status'=>2);
	

	function ShippingProvider() 
	{
		parent::SugarBean();
		$this->list_fields =  array_merge($this->column_fields, array('hide', 'unhide'));
	}

		
	
	function getDefaultProviderName(){
		return translate('LBL_NONE', 'ShippingProviders');	
	}
	
		 
	 
/**	 function list_view_parse_additional_sections(&$list_form)
	{
		global $isMerge;
		
		if(isset($isMerge) && $isMerge && $this->id != '-99'){
		$list_form->assign('PREROW', '<input name="mergecur[]" type="checkbox" value="'.$this->id.'">');
		}
		return $list_form;
	}**/
     function retrieve($id, $encode = true){
     	if($id == '-99'){
     		$this->name = $this->getDefaultProviderName();
     		$this->id = '-99';
     		$this->deleted = 0;
     		$this->status = 'Active';
     		$this->hide = '<!--';
     		$this->unhide = '-->';
     	}else{
     		parent::retrieve($id, $encode);	
     	}
     	if(!isset($this->name) || $this->deleted == 1){
     		$this->name = 	$this->getDefaultProviderName();
     		$this->id = '-99';
     		$this->deleted = 0;
     		$this->status = 'Active';
     		$this->hide = '<!--';
     		$this->unhide = '-->';
     	}
     	
     }
        

}


?>
