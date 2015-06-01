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
require_once('modules/Accounts/Account.php');
require_once('include/utils.php');

class Product extends SugarBean {
	
	// Stored fields
  	var $id;
	var $name;
	var $purchase_name;
	var $url;
	var $is_available;  	
	var $date_available;
	var $tax_code_id;  	
	var $supplier_id;
	var $manufacturer_id;
    var $weight_1;
	var $weight_2;
	var $manufacturers_part_no;
	var $vendor_part_no;
	var $product_category_id;
	var $product_type_id;
	var $model_id;
	var $currency_id;
	var $exchange_rate;
	var $cost;
	var $cost_usdollar;
	var $list_price;
	var $list_usdollar;
	var $purchase_price;
	var $purchase_usdollar;
	var $pricing_formula;  	
	var $description;
	var $description_long;
	var $ppf_perc;
	var $track_inventory;
	var $date_entered;
	var $created_by;
	var $date_modified;
	var $modified_user_id;
	
	var $support_cost;
	var $support_cost_usdollar;
	var $support_list_price;
	var $support_list_usdollar;
	var $support_selling_price;
	var $support_selling_usdollar;
	var $support_price_formula;
	var $support_ppf_perc;
	
	var $supplier_name;
	var $manufacturer_name;
	var $currency_name;
	var $currency_symbol;
	var $currency_iso;
	var $product_category_name;
	var $product_type_name;
	var $model_name;
	var $assembly_id;
	var $created_by_name;
	var $modified_user_name;

	var $eshop;
	var $image_url;
	var $thumbnail_url;

	var $all_stock;
	
	var $table_name = 'products';
	var $object_name = 'Product';
	var $module_dir = 'ProductCatalog';
	var $new_schema = true;

	var $in_stock; // virtual
	
	static $stock_prefetch = array();


	// This is used to retrieve related fields from form posts.
	var $additional_column_fields = Array(
		"supplier_name",
		"manufacturer_name",
		"model_name",
		"currency_name",
		"currency_symbol",
		"product_category_name",
		"product_type_name",
		'tax_code_short',
		'tax_code_name',
		'assembly_id',
		'location_id',
		'created_by_name',
		'modified_user_name',
	);
	
	var $relationship_fields = array(
		'assembly_id' => 'assemblies',
		'supplier_id' => 'suppliers',
	);
	
	function Product() {
		parent::SugarBean();
	}

	function calc_stock($warehouse_id, $pids=null)
    {
        if(! $warehouse_id) 
            return;
        if(! isset(self::$stock_prefetch[$warehouse_id]))
            self::$stock_prefetch[$warehouse_id] = array();
        $cache =& self::$stock_prefetch[$warehouse_id];
        
        if(isset($pids)) {
            $fetch = array();
            foreach($pids as $id) {
                if(! isset($cache[$id])) {
                    $fetch[] = $id;
                    $cache[$id] = 0;
                }
            }
            if(! count($fetch))
                return;
            $pc = "product_id IN ('".implode("','", $fetch)."')";
        } else {
            if(isset($cache[$this->id])) {
                $this->in_stock = $cache[$this->id];
                return;
            }
            $pc = "product_id = '" . PearDatabase::quote($this->id) . "'";
            $this->in_stock = 0;
        }
        $query = "SELECT product_id,in_stock FROM products_warehouses WHERE warehouse_id='" . PearDatabase::quote($warehouse_id) . "' AND $pc AND deleted = 0 LIMIT 1";
        $res = $this->db->query($query, true);
        while($row = $this->db->fetchByAssoc($res)) {
            $cache[$row['product_id']] = (int)$row['in_stock'];
            if(! isset($pids)) {
                $this->in_stock = (int)$row['in_stock'];
                break;
            }
        }
    }

	function stock_for_warehouse($id = null)
	{
		static $set = null;
		if ($id) {
			$set = $id;
		}
		return $set;
	}


	function fill_in_tax_code_info() {
		require_once('modules/TaxCodes/TaxCode.php');
		$taxcode = new TaxCode();
		$taxcode->retrieve(empty($this->tax_code_id) ? '-99' : $this->tax_code_id);
		$this->tax_code_name = $taxcode->name;
		$this->tax_code_short = $taxcode->code;
		$taxcode->cleanup();
	}

	function get_text_description($description=false) {
		if($description === false)
			$description = $this->description;
		if(strpos($description, '&lt;p&gt;') !== false) {
			require_once('include/utils/html_utils.php');
			$description = trim(html2plaintext(fix_fck_html(from_html($description)), false));
		}
		return $description;
	}


	/// Called when this object is created or modified.
	///
	function save($check_notify = FALSE) {
		// this must be done before interpreting numeric values
		$this->unformat_all_fields();
		
		// these cannot be null
		if(! isset($this->cost) || number_empty($this->cost))
			$this->cost = 0.0;
		if(! isset($this->list_price) || number_empty($this->list_price))
			$this->list_price = 0.0;
		if(! isset($this->purchase_price) || number_empty($this->purchase_price))
			$this->purchase_price = 0.0;

		$ret = parent::save($check_notify);
		return $ret;
	}
	
	static function update_assembly_prices(RowUpdate $upd) {
		$lq = $upd->getLinkQuery('assemblies');
		$result = $lq->fetchAll();
		foreach($result->getRowIndexes() as $idx) {
			$subupd = $result->getRowUpdate($idx);
			$subupd->save();
		}
	}
	
	static function update_attribute_prices(RowUpdate $upd) {
		$lq = $upd->getLinkQuery('productattributes');
		$result = $lq->fetchAll();
		foreach($result->getRowIndexes() as $idx) {
			$subupd = $result->getRowUpdate($idx);
			$subupd->save();
		}
	}

	function get_search_categories($param, &$searchFields)
	{
		require_once 'modules/ProductCategories/ProductCategory.php';
		return array('' => '') + get_product_categories_list(true);
	}

	function get_search_types($param, &$searchFields)
	{
		require_once 'modules/ProductTypes/ProductType.php';
		$pType = new ProductType;
		$category = array_get_default($searchFields['product_category_id'], 'value');
		$arPT = $pType->get_for_category($category, true);
		$pType->cleanup();
		return array('' => '') + $arPT;
	}
	
	function get_search_categories2(ListFilter &$filter)
	{
		require_once 'modules/ProductCategories/ProductCategory.php';
		return array('' => '') + get_product_categories_list(true);
	}

	function get_search_types2(ListFilter &$filter)
	{
		require_once 'modules/ProductTypes/ProductType.php';
		$pType = new ProductType;
		$category = array_get_default($filter->filter, 'product_category_id', '');
		$arPT = $pType->get_for_category($category, true);
		$pType->cleanup();
		return array('' => '') + $arPT;
	}
	
	function inventory_auto_adjusted($track_inv=-1) {
		if($track_inv == -1)
			$track_inv = $this->track_inventory;
		return (! empty($track_inv) && $track_inv != 'untracked' && $track_inv != 'manual');
	}
	
	function inventory_tracked($track_inv=-1) {
		if($track_inv == -1)
			$track_inv = $this->track_inventory;
		return (! empty($track_inv) && $track_inv != 'untracked');
	}
	
	static function get_products_pending_invoice($case_id) {
		global $db;
		$query = sprintf(
			"SELECT products_cases.product_id as id FROM products_cases ".
			"LEFT JOIN cases ON cases.id=products_cases.case_id AND NOT cases.deleted ".
			"WHERE cases.id='%s' AND NOT cases.deleted ".
				"AND NOT products_cases.deleted ", //AND IFNULL(products_cases.invoice_id,'')='' ",
			$db->quote($case_id));
		if(! empty($query)) {
			$seed = new Product();
			// results are not to be HTML encoded
			$result = $seed->build_related_list($query, $seed, 0, -1, false);
			$seed->cleanup();
		}
		else {
			$result = array();
		}
		return $result;
 	}

	function create_default_stock()
	{
		$existing = array();
		$query = sprintf(
			"SELECT warehouse_id FROM products_warehouses WHERE deleted = 0 AND product_id = '%s' ",
			$this->db->quote($this->id)
		);
		$res = $this->db->query($query, true);
		while ($row = $this->db->fetchByAssoc($res)) {
			$existing[$row['warehouse_id']] = true;
		}

		$this->load_relationship('warehouses');

		$query = "SELECT id FROM company_addresses WHERE is_warehouse AND deleted = 0";
		$res = $this->db->query($query, true);
		while ($row = $this->db->fetchByAssoc($res)) {
			if (empty($existing[$row['id']])) {
				$this->warehouses->add($row['id']);
			}
		}

	}
	
	function findDuplicates($focus = null, $this_module_only = false) {
		global $db;
		$this_mod = 'ProductCatalog';
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
			$query = "SELECT id FROM products tbl WHERE NOT deleted";
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
			$check = array('Assemblies');
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
	
	function popup_form_handler($params, &$form) {
		global $current_user;
		if($current_user->getPreference('product_costs'))
			$form->parse('main.cost_column');
		else
			$form->assign('HIDE_COST', 'style="display: none"');
	}

    static function update_stock_qty(RowUpdate &$upd, $link_name) {
        global $db;
        $id = $upd->getPrimaryKeyValue();
        if($upd->getModelName() == 'Product' && $link_name == 'warehouses' && $id) {
			$r = $db->query("SELECT SUM(IFNULL(in_stock, 0)) as stock_qty FROM products_warehouses WHERE product_id = '" . $db->quote($id) . "' AND deleted = 0", true);
			$rows = $db->fetchRows($r);
			$upd->set('all_stock', $rows[0]['stock_qty']);
			$upd->save();
		}
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

        if(isset($input['assembly_id']))
            $update['assembly_id'] = $input['assembly_id'];

        $update['track_inventory'] = 'semiauto';

        $upd->set($update);
    }

    static function after_save(RowUpdate &$upd) {
    	$supp = $upd->getField('supplier_id');
    	$o_supp = $upd->getField('supplier_id', null, true);
    	if($supp && $supp != $o_supp) {
    		$upd->addUpdateLink('suppliers', $supp);
    	}
    }

    /**
     * Upload Product Image and Thumbnail Image
     * (or autocreate thumbnail)
     *
     * @param RowUpdate $upd
     * @return void
     */
    static function uploadImage(RowUpdate &$upd) {
        require_once('include/upload_file.php');
        $images = array();
        $auto_thumb = array_get_default($_REQUEST, 'image_file_autothumb');

        if ($upd->related_files) {
            $image_file = array_get_default($upd->related_files, 'image_file');
            $thumbnail_file = array_get_default($upd->related_files, 'thumbnail_file');

            if ($image_file) {
                $image_file->final_move('');
                $images['image_url'] = AppConfig::site_url() . $image_file->get_upload_path('');

                $old_image_url = str_replace(AppConfig::site_url(), '', $upd->getField('image_url'));
                if ($old_image_url && file_exists($old_image_url))
                    unlink($old_image_url);
            }

            if ($thumbnail_file) {
                $thumbnail_file->final_move('');
                $images['thumbnail_url'] = AppConfig::site_url() . $thumbnail_file->get_upload_path('');
            } elseif ($image_file && $auto_thumb) {
                $thumb = createThumbnail($image_file->get_upload_path(''), 90, 90, $image_file->original_file_name);

                if (is_array($thumb)) {
                    $fl = new UploadFile('');
                    $fl->use_soap = true;
                    $fl->stored_file_name = basename($thumb[0] . '.' . $thumb[1]);
                    $fl->create_stored_filename();
                    $upload_dir = AppConfig::upload_dir();
                    $dst = $fl->stored_file_name;
                    @copy($thumb[0], $upload_dir . $dst);
                    @unlink($thumb[0]);
                    $images['thumbnail_url'] = AppConfig::site_url() . $upload_dir . $dst;
                }
            }

            if (! empty($images['thumbnail_url'])) {
                $old_thumbnail = str_replace(AppConfig::site_url(), '', $upd->getField('thumbnail_url'));
                if ($old_thumbnail && file_exists($old_thumbnail))
                    unlink($old_thumbnail);
            }

            $upd->related_files = null;
        }

        $upd->set($images);
    }
}

?>
