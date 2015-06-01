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

class Assembly extends SugarBean
{
	var $id;
	var $date_entered;
	var $created_by;
	var $date_modified;
	var $modified_user_id;
	var $deleted;
	var $name;
	var $purchase_name;
	var $product_category_id;
	var $product_type_id;
	var $supplier_id;
	var $manufacturer_id;
	var $model_id;
	var $product_url;
	var $manufacturers_part_no;
	var $vendor_part_no;
	var $description;
	var $eshop;
	var $image_url;
	var $thumbnail_url;
	
	// not stored
	var $product_category_name;
	var $product_type_name;
	var $supplier_name;
	var $manufacturer_name;
	var $model_name;
	var $created_by_name;
	var $modified_user_name;
	
	var $table_name = 'assemblies';
	var $object_name = 'Assembly';
	var $module_dir = 'Assemblies';
	
	var $new_schema = true;
	
	var $additional_column_fields = array(
		'product_category_name',
		'product_type_name',
		'supplier_name',
		'manufacturer_name',
		'model_name',
		'created_by_name',
		'modified_user_name',
	);
	
	var $relationship_fields = array(
		'supplier_id' => 'suppliers',
	);
	
	
	function Assembly() {
		parent::SugarBean();
	}
	
	function get_products_list($id)
	{
		$list = array();
		
		$query = "
			SELECT products.id, products_assemblies.quantity, products.currency_id, products.exchange_rate,
			products_assemblies.discount_id, products_assemblies.discount_type, products_assemblies.discount_name, products_assemblies.discount_value,
			products.name, products.id, products.manufacturers_part_no, products.tax_code_id,
			products.cost as raw_cost_price, products.list_price as raw_list_price, products.purchase_price as raw_unit_price,
			products.cost_usdollar, products.list_usdollar, products.purchase_usdollar,
			products.support_cost_usdollar, products.support_list_usdollar, products.support_selling_usdollar, products.tax_code_id
			FROM products JOIN products_assemblies ON (products.id = products_assemblies.products_id)
			WHERE products_assemblies.assembly_id='" . PearDatabase::quote($id) . "' and products.deleted = 0 and products_assemblies.deleted = 0
			";
			
		$result = $this->db->query($query, true,"Error retrieving associated products: ");
		
		while ($row = $this->db->fetchByAssoc($result)) {
			$list[] = $row;
		}
		
		return $list;
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

	function findDuplicates($focus = null, $this_module_only = false) {
		global $db;
		$this_mod = 'Assemblies';
		if(!$focus)
			$focus =& $this;
		if(! empty($focus->id) && $focus->module_dir == $this_mod)
			$id_check = 'tbl.id != "'.$db->quote($focus->id).'"';
		$options = array();
		if(! empty($focus->manufacturers_part_no))
			$options[] = 'tbl.manufacturers_part_no = "'.trim($db->quote($focus->manufacturers_part_no)).'"';
		if(! empty($focus->name))
			$options[] = 'tbl.name = "'.trim($db->quote($focus->name)).'"';
		if(! empty($focus->vendor_part_no))
			$options[] = 'tbl.vendor_part_no = "'.trim($db->quote($focus->vendor_part_no)).'"';
		$ret = array();
		if(count($options)) {
			$query = "SELECT id FROM assemblies tbl WHERE NOT deleted";
			if(isset($id_check)) $query .= " AND $id_check";
			$query .= ' AND ('.implode(' OR ', $options).')';
			
			$rows = array();
			$result = $db->query($query, true);
			while($row = $db->fetchByAssoc($result))
				$rows[] = $row['id'];
			if($rows)
				$ret[$this_mod] = $rows;
		}
		if($focus->module_dir == $this_mod && ! $this_module_only) {
			$check = array('ProductCatalog');
			global $beanList, $beanFiles;
			foreach($check as $m) {
				if(! isset($beanList[$m])) continue;
				$bean_name = $beanList[$m];
				require_once($beanFiles[$bean_name]);
				$bean = new $bean_name();
				$ret += $bean->findDuplicates($focus, true);
				$bean->cleanup();
			}
		}
		return $ret;
	}

    static function init_record(RowUpdate &$upd, $input) {
        $update = array();

        if (! empty($input['model_id'])) {
            $model = ListQuery::quick_fetch_row('Model', $input['model_id']);

            if ($model != null) {
                $update['model_id'] = $input['model_id'];
                $manuf = ListQuery::quick_fetch_row('Account', $model['manufacturer_id']);
                if ($manuf != null) {
                    $update['manufacturer_id'] = $manuf['id'];
                    $update['manufacturer_name'] = $manuf['name'];
                }
            }
        }

        $upd->set($update);
    }
    
    static function updated_supplier(RowUpdate &$upd) {
    	if($upd->getFieldUpdated('supplier_id') && ($supp = $upd->getField('supplier_id'))) {
    		$upd->addUpdateLink('suppliers', $supp);
    	}
    }
    
    static function recalc_prices(RowUpdate &$upd) {
		$list_usdollar = 0;
		$cost_usdollar = 0;
		$purchase_usdollar = 0;
		$tax_code_id = '';
		$lq = $upd->getLinkQuery('products');
		
		$lq->addFields(array(
			'cost_usdollar',
			'list_usdollar',
			'purchase_usdollar',
			'tax_code_id',
			'quantity' => '~join.quantity',
			'discount_type' => '~join.discount_type',
			'discount_value' => '~join.discount_value',
		));
		$products = $lq->fetchAll();
		foreach($products->getRowIndexes() as $idx) {
			$product = $products->getRowResult($idx);
			$purchase = $product->getField('purchase_usdollar', 0);
			$quantity = $product->getField('quantity', 0);

			if (! $tax_code_id)
				$tax_code_id = $product->getField('tax_code_id');
			if ($product->getField('discount_type') == 'fixed')
				$purchase -= $product->getField('discount_value');
			else
				$purchase *= (1 - $product->getField('discount_value') / 100.00);
			
			$list_usdollar += $quantity * $product->getField('list_usdollar', 0);
			$cost_usdollar += $quantity * $product->getField('cost_usdollar', 0);
			$purchase_usdollar += $quantity * $purchase;
		}
		
		$upd->set(compact('cost_usdollar', 'list_usdollar', 'purchase_usdollar', 'tax_code_id'));
	}
	



    /**
     * Upload Product Image and Thumbnail Image
     * (or autocreate thumbnail)
     *
     * @param RowUpdate $upd
     * @return void
     */
    static function uploadImage(RowUpdate &$upd) {
        $original = '';
        $tmp = '';
        $images = array();

        require_once('include/upload_file.php');
        $upload_image = new UploadFile('image_file');
        $move = false;

        if (isset($_FILES['image_file']) && $upload_image->confirm_upload()) {
            $original = $_FILES['image_file']['name'];
            $tmp = $_FILES['image_file']['tmp_name'];
            $move = true;
        }

        $upload_thumbnail = new UploadFile('thumbnail_file');
        $thumbnail_url = null;

        if (isset($_FILES['thumbnail_file']) && $upload_thumbnail->confirm_upload()) {
            $upload_thumbnail->final_move('');
            $thumbnail_url = AppConfig::site_url() . '/' . $upload_thumbnail->get_upload_path('');
        } elseif (! empty($original) && (! empty($_REQUEST['image_file_autothumb']) && $_REQUEST['image_file_autothumb']) ) {
            $thumb = createThumbnail($tmp, 90, 90, $original);

            if (is_array($thumb)) {
                $fl = new UploadFile('');
                $fl->use_soap = true;
                $fl->stored_file_name = basename($thumb[0] . '.' . $thumb[1]);
                $fl->create_stored_filename();
                $upload_dir = AppConfig::upload_dir();
                $dst = $fl->stored_file_name;
                @copy($thumb[0], $upload_dir . $dst);
                @unlink($thumb[0]);
                $thumbnail_url = AppConfig::site_url() . '/' . $upload_dir . $dst;
            }
        }

        if ($move) {
            $upload_image->final_move('');
            $images['image_url'] = AppConfig::site_url() . '/' . $upload_image->get_upload_path('');
        }

        if ($thumbnail_url)
            $images['thumbnail_url'] = $thumbnail_url;

        $upd->set($images);
    }
}

?>
