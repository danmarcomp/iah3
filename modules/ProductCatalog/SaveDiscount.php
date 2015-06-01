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

$product_id = array_get_default($_REQUEST, 'products_id', '');
$assembly_id = array_get_default($_REQUEST, 'assembly_id', '');

$model = new ModelDef('products_assemblies');
$lq = new ListQuery($model);

$clauses = array(
    "product_id" => array(
        "value" => $product_id,
        "field" => "products_id"
    ),
    "assembly_id" => array(
        "value" => $assembly_id,
        "field" => "assembly_id"
    )
);

$lq->addFilterClauses($clauses);
$result = $lq->runQuerySingle();

if (! $result->failed) {
    $update = RowUpdate::for_result($result);
    $update->loadRequest();
    $inputParams = $update->getInput();

    $type = array_get_default($_REQUEST, 'discount_type', '');
    $value = array_get_default($_REQUEST, 'discount_value', '');
    $name = '';

    if ($type == 'std') {
        $std_id = array_get_default($_REQUEST, 'discount_id', '');
        $discount = ListQuery::quick_fetch_row('Discount', $std_id, array('name', 'discount_type', 'rate', 'fixed_amount'));
        if ($discount) {
            $name = $discount['name'];
            $parent_type = $discount['discount_type'];

            if ($parent_type == 'percentage') {
                $value = $discount['rate'];
            } else {
                $value = $discount['fixed_amount_usdollar'];
            }
            $inputParams['discount_value'] = $value;
            $inputParams['discount_type'] = $parent_type;
        }
    } elseif ($type == 'percentage') {
        $name = ($value != '') ? $value . '%' : '';
    } elseif ($type == 'fixed') {
        $name = $value;
    }

    $inputParams['discount_name'] = $name;
    if ($value == '')
        $inputParams['discount_value'] = 0;
    $update->loadInput($inputParams);

    $update->validate() && $update->save();
}
?>
