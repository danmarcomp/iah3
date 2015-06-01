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

// Assets is used to store customer information.
class Asset extends SugarBean {

	// Stored fields
  	var $id;
	var $name;
	var $url;
	var $date_available;
	var $warranty_start_date;
	var $warranty_expiry_date;
	var $tax_code_id;  	
	var $supplier_id;
	var $manufacturer_id;
	var $model_id;
	var $weight_1;
	var $weight_2;
	var $manufacturers_part_no;
	var $vendor_part_no;
	var $product_category_id;
	var $product_type_id;
	var $currency_id;
	var $exchange_rate;
	var $purchase_price;
	var $purchase_usdollar;
	var $unit_support_price;
	var $unit_support_usdollar;
	var $support_cost;
	var $support_cost_usdollar;
	//var $pricing_formula;  	
	var $description;
	var $deleted;
	var $account_id;
	var $account_role;	//the key
	var $account_role_name;	//the translated name
	var $date_entered;
	var $created_by;
	var $date_modified;
	var $modified_user_id;
	var $supported_assembly_id;
	var $service_subcontract_id;
	var $quantity = 1;
	
	// not stored
	var $account_name;
	var $supported_assembly_name;
	var $supplier_name;
	var $manufacturer_name;
	var $model_name;
	var $currency_name;
	var $currency_symbol;
	var $product_category_name;
	var $product_type_name;
	var $created_by_name;
	var $modified_user_name;
	var $subcontract_name;


	var $serial_numbers_table = 'serial_numbers';

	var $table_name = 'assets';
	var $object_name = 'Asset';
	var $module_dir = 'Assets';
	var $new_schema = true;

	
	function fill_in_tax_code_info() {
		require_once('modules/TaxCodes/TaxCode.php');
		$taxcode = new TaxCode();
		$taxcode->retrieve(empty($this->tax_code_id) ? '-99' : $this->tax_code_id);
		$this->tax_code_name = $taxcode->name;
		$this->tax_code_short = $taxcode->code;
		$taxcode->cleanup();
	}


	/// Called when this object is created or modified.
	///
	function save($check_notify = FALSE) {
		// this must be done before interpreting numeric values
		$this->unformat_all_fields();
		
		require_once('modules/Currencies/Currency.php');
		$currency = new Currency();
		$currency->retrieve($this->currency_id);
		$rate_changed = adjust_exchange_rate($this, $currency);
		
		$this->purchase_usdollar = $currency->convertToDollar($this->purchase_price);
		$this->unit_support_usdollar = $currency->convertToDollar($this->unit_support_price);
		$this->support_cost_usdollar = $currency->convertToDollar($this->support_cost);
		
		$currency->cleanup();
		return parent::save($check_notify);
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

	
	function get_assets_pending_invoice(&$bean) {
		global $db;
		$query = sprintf(
			"SELECT assets_cases.asset_id as id FROM assets_cases ".
			"LEFT JOIN cases ON cases.id=assets_cases.case_id AND NOT cases.deleted ".
			"WHERE cases.id='%s' AND NOT cases.deleted ".
				"AND NOT assets_cases.deleted ",
			$db->quote($bean->id));
		if(! empty($query)) {
			$seed = new Asset();
			// results are not to be HTML encoded
			$result = $seed->build_related_list($query, $seed, 0, -1, false);
			$seed->cleanup();
		}
		else
			$result = array();
		return $result;
	}

    static function init_form(DetailManager $mgr) {
        global $pageInstance;
        $pageInstance->add_js_literal("setOnSubmitEvent();", null, LOAD_PRIORITY_FOOT);
        $account_id = $mgr->record->getField('account_id');
        if ($account_id) {
            $pageInstance->add_js_literal("init_form('".$mgr->form_gen->form_obj->form_name."', '".$account_id."');", null, LOAD_PRIORITY_FOOT);
        }
        $pageInstance->add_js_literal("set_name_extra('".$mgr->form_gen->form_obj->form_name."');", null, LOAD_PRIORITY_FOOT);
    }

    static function init_record(RowUpdate &$upd, $input) {
        $update = array();

        $fields = array('supported_assembly_id', 'account_id', 'account_name', 'service_subcontract_id');
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

    static function set_updated_name(RowUpdate &$upd) {
        $name = array_get_default($_REQUEST, 'product_name', '');
        if (! empty($name))
            $upd->set('name', array_get_default($_REQUEST, 'product_name', ''));
    }

    static function after_save(RowUpdate &$upd) {
    	$supp = $upd->getField('supplier_id');
    	$o_supp = $upd->getField('supplier_id', null, true);
    	if($supp && $supp != $o_supp) {
    		$upd->addUpdateLink('suppliers', $supp);
    	}
    }

}


function assign_asset_to_subc( $asset_id, $subcontract_id )
{
	$query = "
		UPDATE  assets 
		   SET	service_subcontract_id = '$subcontract_id'
		 WHERE  deleted != 1
		   AND	id = '$asset_id'
		";

	$asset = new Asset();
	$asset->db->query($query, true, "Error assigning an asset to a subcontract.");
	$asset->cleanup();
}

function remove_asset_from_subc( $asset_id, $subcontract_id )
{
	$query = "
		UPDATE  assets 
		   SET	service_subcontract_id = ''
		 WHERE  deleted != 1
		   AND	id = '$asset_id'
		   AND  service_subcontract_id = '$subcontract_id'
		";

	$asset = new Asset();
	$asset->db->query($query, true, "Error removing an asset from a subcontract.");
	$asset->cleanup();
}

function asset_acct_rolechange( $account_role, $record )
{
	$query = "
		UPDATE 	assets 
		   SET 	account_role = '$account_role' 
		 WHERE	id = '$record'
		";

	$asset = new Asset();
	$asset->db->query($query, true, "Error changing asset-account relationship role.");
	$asset->cleanup();
}

?>
