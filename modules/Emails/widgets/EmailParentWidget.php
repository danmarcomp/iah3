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

class EmailParentWidget extends FormField {

    function renderListCell(ListFormatter $fmt, ListResult &$list_result, $row_id) {
		$row = $list_result->getRowResult($row_id);
		if($row)
			return $this->renderDropdown($row, $fmt->list_id .'-'. $fmt->form_name);
		return '';
	}
	
	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, array $context) {
		if($gen->getLayout()->getType() == 'view')
			return $this->renderDropdown($row_result, $gen->getFormName());
		return parent::renderHtml($gen, $row_result, $parents, $context);
	}
	
	function renderDropdown(RowResult $row, $form_id) {
		$value = $row->getField('parent', '', true);

		if ($value == '') {
			$result_id = $row->getField($row->primary_key);
			$button_id = $result_id . '-quick_create';
			$value = '<span class="list-edit-value input-inline-arrow" id="' .$button_id. '">'.translate('LBL_QUICK_CREATE', 'Emails').'</span>';

			$json = getJSONobj();
			global $pageInstance;
			$pageInstance->add_js_literal("SUGAR.ui.setupEmailParentsMenu('{$form_id}', '{$button_id}', {$json->encode($this->getParentsOptions())}, '{$result_id}');", null, LOAD_PRIORITY_FOOT);
		}
		
		return $value;

	}

    function getParentsOptions() {
        $opts = array(
            'Cases' => array(
                'label' => 'LBL_LIST_CASE',
            ),
            'Leads' => array(
                'label' => 'LBL_LIST_LEAD',
            ),
            'Contacts' => array(
                'label' => 'LBL_LIST_CONTACT',
            ),
            'Bugs' => array(
                'label' => 'LBL_LIST_BUG',
            ),
            'Tasks' => array(
                'label' => 'LBL_LIST_TASK',
            ),
        );

        $keys = array();
        $values = array();

        foreach($opts as $mod => $opt) {
            $keys[] = $mod;
            $values[] = array('label' => translate($opt['label'], 'Emails'), 'icon' => 'theme-icon module-'.$mod);
        }

        return array('keys' => $keys, 'values' => $values, 'icon_key' => 'icon');
    }
}
?>