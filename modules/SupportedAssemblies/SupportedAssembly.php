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

class SupportedAssembly extends SugarBean
{
	var $id;
	var $date_entered;
	var $created_by;
	var $date_modified;
	var $modified_user_id;
	var $deleted;
	var $name;
	var $product_category_id;
	var $product_type_id;
	var $product_url;
	var $manufacturers_part_no;
	var $vendor_part_no;
	var $account_id;
	var $account_name;
	var $service_subcontract_id;
	var $quantity = 1;
	var $supplier_id;
	var $manufacturer_id;
	var $model_id;
    
	// not stored
	var $product_category_name;
	var $product_type_name;
	var $subcontract_name;
	var $supplier_name;
	var $manufacturer_name;
	var $model_name;
	var $created_by_name;
	var $modified_user_name;

	
	var $table_name = 'supported_assemblies';
	var $object_name = 'SupportedAssembly';
	var $module_dir = 'SupportedAssemblies';
	
	var $new_schema = true;
	
	
	function set_assets_subcontracts() {
		$query = "UPDATE assets SET service_subcontract_id='$this->service_subcontract_id' WHERE supported_assembly_id='$this->id' AND NOT deleted";
		$this->db->query($query, true, "Error reassigning assets to service subcontract");
	}
	

	function get_search_categories()
	{
		require_once 'modules/ProductCategories/ProductCategory.php';
		return array('' => '') + get_product_categories_list(true);
	}

	function get_search_types($param, $searchFields)
	{
		require_once 'modules/ProductTypes/ProductType.php';
		$pType = new ProductType;
		$arPT = $pType->get_for_category(@$searchFields['product_category_id']['value'], true);
		$pType->cleanup();
		return array('' => '') + $arPT;
	}

	function get_products_list($id)
	{
		$list = array();
		
		$query = "
			SELECT assets.id, assets.quantity, assets.name, assets.manufacturers_part_no, assets.tax_code_id, assets.unit_support_usdollar, assets.support_cost_usdollar, assets.purchase_usdollar, serial_numbers.serial_no
			FROM assets LEFT JOIN serial_numbers ON serial_numbers.asset_id = assets.id AND serial_numbers.deleted = 0
			WHERE assets.supported_assembly_id='" . PearDatabase::quote($id) . "' and assets.deleted = 0 GROUP BY assets.id
			";
			
		$result = $this->db->query($query, true,"Error retrieving associated products: ");
		
		while ($row = $this->db->fetchByAssoc($result)) {
			$list[] = $row;
		}
		
		return $list;
	}

    static function init_record(RowUpdate &$upd, $input) {
        $update = array();

        $fields = array('account_id', 'account_name', 'service_subcontract_id');
        for ($i = 0; $i < sizeof($fields); $i++) {
            $field = $fields[$i];
            if (! empty($input[$field])) {
                $update[$field] = urldecode($input[$field]);
            }
        }

        if (! empty($update['service_subcontract_id'])) {
            $subcontract = ListQuery::quick_fetch_row('SubContract', $update['service_subcontract_id'], array('main_contract.account_id'));
            if (is_array($subcontract) && isset($subcontract['main_contract.account_id']))
                $update['account_id'] = $subcontract['main_contract.account_id'];
        }

        $upd->set($update);
    }

    static function init_form(DetailManager $mgr) {
        global $pageInstance;
        $pageInstance->add_js_literal("setOnSubmitEvent();", null, LOAD_PRIORITY_FOOT);
        $account_id = $mgr->record->getField('account_id');
        if ($account_id) {
            $pageInstance->add_js_literal("init_form('".$mgr->form_gen->form_obj->form_name."', '".$account_id."');", null, LOAD_PRIORITY_FOOT);
        }
        $pageInstance->add_js_literal("set_asm_name_extra('".$mgr->form_gen->form_obj->form_name."');", null, LOAD_PRIORITY_FOOT);
    }

    static function set_updated_name(RowUpdate &$upd) {
        $upd->set('name', array_get_default($_REQUEST, 'assembly_name', ''));
    }

    static function after_save(RowUpdate &$upd) {
    	self::set_subcontracts($upd);
    	
    	$supp = $upd->getField('supplier_id');
    	$o_supp = $upd->getField('supplier_id', null, true);
    	if($supp && $supp != $o_supp) {
    		$upd->addUpdateLink('suppliers', $supp);
    	}
    }

    static function set_subcontracts(RowUpdate &$update) {
        if (isset($update->updates['service_subcontract_id'])) {
            global $db;
            $service_contract_id = $update->getField('service_subcontract_id');
            $query = "UPDATE assets SET service_subcontract_id='".$service_contract_id."' WHERE supported_assembly_id='".$update->getField('id')."' AND NOT deleted";
            $db->query($query, true, "Error reassigning assets to service subcontract");
        }
    }
}

?>
