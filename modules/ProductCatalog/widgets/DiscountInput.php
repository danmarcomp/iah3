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


require_once('include/layout/forms/FormField.php');

class DiscountInput extends FormField {

	function init($params=null, $model=null) {
        parent::init($params, $model);
	}

	function getRequiredFields() {
        $fields = array('~join.discount_id', '~join.discount_value', '~join.discount_type', '~join.discount_name', '~join.assembly_id');
		return $fields;
	}

    function renderListCell(ListFormatter $fmt, ListResult &$result, $row_id) {
        $type = array_get_default($result->rows[$row_id], '~join.discount_type');
        $id = array_get_default($result->rows[$row_id], '~join.discount_id');
        $num_value = array_get_default($result->formatted_rows[$row_id], '~join.discount_value');
        $val = array_get_default($result->formatted_rows[$row_id], '~join.discount_name');

        if ($id != '')
            $type = 'std';

        if ($type == 'percentage' && $num_value != '') {
            $val = $num_value . '%';
        } else if ($type == 'fixed' && $num_value != '') {
            $val = $num_value;
        }

        if ($val == '') $val = '--';

        $assembly_id = array_get_default($result->formatted_rows[$row_id], '~join.assembly_id');
        $label = javascript_escape($this->label);

        $onclick = "SUGAR.popups.openUrl('async.php?module=ProductCatalog&action=DiscountPopup&assembly_id=".$assembly_id."&product_id=".$row_id."&list_id=".$this->list_id."', null, {width: '300px', title_text: '".$label."', resizable: false}); return false;";
        $html = '<span class="list-edit-value" onclick="'.$onclick.'">&nbsp;'.$val.'&nbsp;</span>';

        return $html;
    }
}