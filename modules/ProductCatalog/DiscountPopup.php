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

require_once('include/layout/forms/FormGenerator.php');

$assembly_id = array_get_default($_REQUEST, 'assembly_id', '');
$product_id = array_get_default($_REQUEST, 'product_id', '');
$list_id = array_get_default($_REQUEST, 'list_id', '');


$params = array(
    'type' => 'editview',
    'base_module' => 'products_assemblies',
    'buttons_position' => 'bottom',
    'editable' => true,
);

$params['sections'] = array(
    array(
        'label_position' => 'top',
        'columns' => 1,
        'widths' => array('50%'),
        'elements' => array(array('name' => 'discount_type', 'onchange' => 'changeType(this.getValue());'), '', 'discount', 'discount_value')
    ),
);

$model = new ModelDef('products_assemblies');
$layout = new FormLayout($params, $model);
$form = 'DiscountForm';
$panel_id = $list_id . '-outer';
$onsubmit = 'return submitForm("'.$list_id.'");';
$layout->addFormHooks(array('onsubmit' => $onsubmit), false);
$hidden = array('list_id' => $list_id, 'module' => 'ProductCatalog', 'action' => 'SaveDiscount',
    'assembly_id' => $assembly_id, 'products_id' => $product_id, 'in_popup' => 1);
$layout->addFormHiddenFields($hidden);

$buttons = array(
    'save' => array(
        'vname' => 'LBL_SAVE_BUTTON_LABEL',
        'accesskey' => 'LBL_SAVE_BUTTON_KEY',
        'order' => 1,
        'icon' => 'icon-accept',
        'type' => 'button',
        'onclick' => $onsubmit
    ),
    'cancel' => array(
        'type' => 'button',
        'vname' => 'LBL_CANCEL_BUTTON_LABEL',
        'accesskey' => 'LBL_CANCEL_BUTTON_KEY',
        'order' => 5,
        'icon' => 'icon-cancel',
        'onclick' => 'return SUGAR.popups.close();',
        'params' => array(
            'format' => 'html',
        ),
    ),
);
$layout->addFormButtons($buttons);
$layout->addScriptInclude('modules/ProductCatalog/discount_popup.js');

$fields = array('discount', 'discount_value', 'discount_type', 'discount_name');
$lq = new ListQuery($model, $fields);

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

if ($result->getField('discount_id') != '') {
    $type = 'std';
    $result->row['discount_type'] = 'std';
} else {
    $type = $result->getField('discount_type');
}

$layout->addScriptLiteral("changeDiscountType('".$type."');", LOAD_PRIORITY_FOOT);

$gen = FormGenerator::html_form($model, $layout, $form, $form);
$gen->formatResult($result);
$gen->renderForm($result);
$out = $gen->getResult();
$gen->exportIncludes();

echo $out;
?>