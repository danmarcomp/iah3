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
require_once('include/Tally/TallyUpdate.php');

class ProductsCreator {

    const DEFAULT_CURRENCY_ID = '-99';

    const DEFAULT_EXCHANGE_RATE = 1;

    /**
     * Parent object type
     *
     * @var string
     */
    private $parent_type;

    /**
     * Parent object ID
     *
     * @var string
     */
    private $parent_id;

    /**
     * Parent record
     *
     * @var null|RowResult
     */
    private $parent;

    /**
     * Parent Account ID
     *
     * @var string
     */
    private $account_id;

    /**
     * Selected products IDs
     *
     * @var array
     */
    private $selected_ids;

    /**
     * Select all products
     *
     * @var bool
     */
    private $select_all;

    /**
     * Products for converting
     *
     * @var array
     */
    private $products;

    /**
     * Assemblies for converting
     *
     * @var array
     */
    private $assemblies;

    /**
     * @param string $parent_id - parent record ID
     * @param string $parent_type - parent record Model
     * @param string $account_id - parent record Account ID
     */
    public function __construct($parent_id, $parent_type, $account_id) {
        $this->parent_id = $parent_id;
        $this->parent_type = $parent_type;
        $this->account_id = $account_id;
        $this->select_all = false;
        $this->products = array();
        $this->assemblies = array();
        $this->parent = null;

        $this->loadSelected();
        $this->loadItemsForConverting();
        $this->loadParent();
    }

    /**
     * Create new supported items
     *
     * @param null|string $subcontractId
     * @param null|string $projectId
     * @return void
     */
    public function create($subcontractId = null, $projectId = null) {
        if (sizeof($this->products) > 0) {
            foreach ($this->products as $id => $product) {
                $this->createProduct($product, $subcontractId, $projectId);
            }
        }

        if (sizeof($this->assemblies) > 0) {
            foreach ($this->assemblies as $id => $assembly) {
                $this->createAssembly($assembly, $subcontractId, $projectId);
            }
        }
    }

    /**
     * Create Asset (Supported Product)
     *
     * @param array $data
     * @param null|string $subcontractId
     * @param null|string $projectId
     */
    private function createProduct($data, $subcontractId = null, $projectId = null) {
        $asset = RowUpdate::blank_for_model('Asset');

        $new_data = array(
            'currency_id' => $this->parent->getField('currency_id', self::DEFAULT_CURRENCY_ID),
            'exchange_rate' => $this->parent->getField('exchange_rate', self::DEFAULT_EXCHANGE_RATE),
            'name' => $data['name'],
            'quantity' => $data['quantity'],
            'account_id' => $this->account_id,
            'tax_code_id' => $data['tax_class_id'],
            'purchase_price' => $data['unit_price'],
            'service_subcontract_id' => $subcontractId,
            'project_id' => $projectId
        );

        $relatedProduct = ListQuery::quick_fetch('Product', $data['related_id']);

        if ($relatedProduct) {
            $map = $this->getAssetFieldMap();

            foreach ($relatedProduct->row as $parent_field => $value) {
                $asset_field = array_get_default($map, $parent_field, null);
                if ($asset_field !== null)
                    $new_data[$asset_field] = $value;
            }
        }

        if (isset($data['assembly_id']))
            $new_data['supported_assembly_id'] = $data['assembly_id'];

        $asset->set($new_data);
        $asset->save();
    }

    /**
     * Create Supported Assembly
     *
     * @param array $data
     * @param null|string $subcontractId
     * @param null|string $projectId
     */
    private function createAssembly($data, $subcontractId = null, $projectId = null) {
        $assembly = RowUpdate::blank_for_model('SupportedAssembly');

        $new_data = array(
            'currency_id' => $this->parent->getField('currency_id', self::DEFAULT_CURRENCY_ID),
            'exchange_rate' => $this->parent->getField('exchange_rate', self::DEFAULT_EXCHANGE_RATE),
            'name' => $data['name'],
            'quantity' => $data['quantity'],
            'account_id' => $this->account_id,
            'service_subcontract_id' => $subcontractId,
            'project_id' => $projectId
        );

        $relatedAssembly = ListQuery::quick_fetch('Assembly', $data['related_id']);

        if ($relatedAssembly) {
            $map = $this->getAssemblyFieldMap();

            foreach ($relatedAssembly->row as $parent_field => $value) {
                $supported_field = array_get_default($map, $parent_field, null);
                if ($supported_field !== null)
                    $new_data[$supported_field] = $value;
            }
        }

        $assembly->set($new_data);

        if ($assembly->save()) {
            if ( isset($data['related_products']) && (is_array($data['related_products']) && sizeof($data['related_products']) > 0) ) {

                foreach ($data['related_products'] as $rel_id => $rel_data) {
                    $rel_data['assembly_id'] = $assembly->getPrimaryKeyValue();
                    $this->createProduct($rel_data, $subcontractId, $projectId);
                }

            }
        }
    }

    /**
     * Load selected products IDs
     *
     */
    private function loadSelected() {
        $uids = array_get_default($_REQUEST, 'list_uids', '');

        if ($uids == 'all') {
            $this->selected_ids = array();
            $this->select_all = true;
        } else {
            $this->selected_ids = array_unique(array_filter(explode(';', $uids)));
        }
    }

    /**
     * Load parent record
     *
     * @return void
     */
    private function loadParent() {
        $parent = ListQuery::quick_fetch($this->parent_type, $this->parent_id);

        if ($parent)
            $this->parent = $parent;
    }

    /**
     * Load items for converting to supported item (based on selected items)
     *
     * @return void
     */
    private function loadItemsForConverting() {
        $items = $this->getItems();

        foreach ($items as $key => $grp) {
            foreach ($grp['lines'] as $line_id => $line) {
                $parent_id = $line['parent_id'];
                $is_assembly = ($line['related_type'] == 'Assemblies');

                if (! $this->isSelected($line_id, $parent_id) || ! empty($line['is_comment']))
                    continue;

                if (! $is_assembly) {
                    if (empty($parent_id) && ! isset($this->products[$line_id])) {
                        $this->products[$line_id] = $line;
                    } else {
                        if (! isset($this->assemblies[$parent_id]))
                            $this->assemblies[$parent_id] = $grp['lines'][$parent_id];
                        $this->assemblies[$parent_id]['related_products'][$line_id] = $line;
                    }
                } else {
                    if (! isset($this->assemblies[$line_id]))
                        $this->assemblies[$line_id] = $line;
                }
            }
        }
    }

    /**
     * Get line items
     *
     * @return array
     */
    private function getItems() {
        $focus_result = ListQuery::quick_fetch($this->parent_type, $this->parent_id);
        $tally = TallyUpdate::for_result($focus_result);
        $groups = $tally->getGroups();

        return $groups;
    }

    /**
     * Is selected item or not
     *
     * @param string $itemId
     * @param string $parentId
     * @return bool
     */
    private function isSelected($itemId, $parentId = null) {
        if ($this->select_all) {
            return true;
        } else {
            if ($parentId && in_array($parentId, $this->selected_ids)) {
                return true;
            } elseif(in_array($itemId, $this->selected_ids)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get Asset (Supported Product) fields map
     *
     * @return array
     */
    private function getAssetFieldMap() {
        $asset_map = array(
        	'url' => 'url',
        	'date_available' => 'date_available',
        	'supplier_name' => 'supplier_name',
        	'supplier_id' => 'supplier_id',
        	'manufacturer_name' => 'manufacturer_name',
        	'manufacturer_id' => 'manufacturer_id',
        	'model_name' => 'model_name',
        	'model_id' => 'model_id',
        	'weight_1' => 'weight_1',
        	'weight_2' => 'weight_2',
        	'manufacturers_part_no' => 'manufacturers_part_no',
        	'product_category_id' => 'product_category_id',
        	'vendor_part_no' => 'vendor_part_no',
        	'product_type_id' => 'product_type_id',
        	'description' => 'description',
            'support_selling_price' => 'unit_support_price',
            'support_cost' => 'support_cost',
        );

        return $asset_map;
    }

    /**
     * Get Supported Assembly fields map
     *
     * @return array
     */
    private function getAssemblyFieldMap() {
        $assembly_map = array(
        	'product_category_id' => 'product_category_id',
        	'product_type_id' => 'product_type_id',
        	'supplier_id' => 'supplier_id',
        	'manufacturer_id' => 'manufacturer_id',
        	'model_id' => 'model_id',
        	'model_name' => 'model_name',
        	'product_url' => 'product_url',
        	'manufacturers_part_no' => 'manufacturers_part_no',
        	'vendor_part_no' => 'vendor_part_no',
        	'description' => 'description',
        );

        return $assembly_map;
    }
}
?>