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


require_once('include/layout/forms/FormSection.php');

class BookingDialogWidget extends FormSection {

	function init($params, $model=null) {
		parent::init($params, $model);
		if(! $this->id)
			$this->id = 'booking_dialog';
	}
	
	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, $context) {
        return $this->renderHtmlEdit($gen, $row_result);
	}
	
	function getRequiredFields() {
        $fields = array('name', 'date_start', 'quantity', 'booking_category', 'status',
            'related', 'account', 'billing_currency', 'paid_currency', 'billing_rate',
            'paid_rate', 'billing_total', 'paid_total', 'tax_code', 'assigned_user', 'timesheet');
		return $fields;
	}
	
	function renderHtmlEdit(HtmlFormGenerator &$gen, RowResult &$row_result) {
        global $image_path, $mod_strings;

        $module = $this->model->getModuleDir();
        $additional_img = get_image($image_path . "searchMore.gif", "border='0' hspace='0' vspace='0' style='margin: -2px 0'");

        $fields = $this->getRequiredFields();
        $price_fields = array('billing_rate', 'paid_rate', 'billing_total', 'paid_total');
        $display = array();
        
        $clabel = array(
        	'booking_category' => 'LBL_CATEGORY',
        );
        
        for ($i = 0; $i < sizeof($fields); $i++) {
            if ( isset($row_result->fields[$fields[$i]]) && ($fields[$i] != 'assigned_user' && $fields[$i] != 'timesheet') ) {
            	$label = $row_result->fields[$fields[$i]]['vname'];
            	if(isset($clabel[$fields[$i]]))
            		$label = $clabel[$fields[$i]];
                $label = translate($label, $module);
                $form_field = null;

				$value = $gen->form_obj->renderField($row_result, $fields[$i], $form_field);
                $display[$fields[$i]] = array('label' => $label, 'value' => $value);
            }
        }

        $label_class = 'dataLabel dataLabel_s';
        $field_class = 'dataField dataField_s';

        if ($this->id == 'booking_editor') {
            $label_class = 'dataLabel';
            $field_class = 'dataField';
        }

		$user_id = $row_result->getField('assigned_user_id');
        if(! $user_id)
            $user_id = AppConfig::current_user_id();
        $ts_id = $row_result->getField('timesheet_id');

        $body = <<<EOQ
	        <input type="hidden" id="assigned_user_id" value="{$user_id}">
	        <input type="hidden" name="timesheet_id" value="{$ts_id}">
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm" style="padding: 2px">
            <tbody>
                <tr valign="top">
                    <td width="18%" class="{$label_class}" align="right">{$display['name']['label']}<span class="requiredStar">*</span></td>
                    <td width="82%" class="{$field_class}" colspan="3">{$display['name']['value']}</td>
                </tr>
				<tr valign="top">
					<td class="{$label_class}" width="18%" align="right">{$display['date_start']['label']}<span class="requiredStar">*</span></td>
					<td class="{$field_class}" width="43%">{$display['date_start']['value']}</td>
					<td class="{$label_class}" valign="top" width="18%" align="right">{$display['quantity']['label']}<span class="requiredStar">*</span></td>
					<td class="{$field_class}" valign="top" width="21%">{$display['quantity']['value']}</td>
				</tr>
                <tr>
                    <td class="{$label_class}" align="right" valign="top">{$display['booking_category']['label']}<span class="requiredStar">*</span></td>
                    <td class="{$field_class}" valign="top">{$display['booking_category']['value']}</td>
                    <td class="{$label_class}" align="right" valign="top">{$display['status']['label']}<span class="requiredStar">*</span></td>
                    <td class="{$field_class}" valign="top">{$display['status']['value']}</td>
                </tr>
                <tr valign="top">
                    <td class="{$label_class}" align="right" valign="top">{$display['related']['label']}</td>
                    <td class="{$field_class}" colspan="3" valign="top">{$display['related']['value']}</td>
                </tr>
                <tr>
                    <td class="{$label_class}" align="right">{$display['account']['label']}</td>
                    <td class="{$field_class}" colspan="3" valign="top">{$display['account']['value']}</td>
                    <td class="{$label_class}" align="right">&nbsp;</td>
                    <td class="{$field_class}">&nbsp;</td>
                </tr>
                <tr>
                    <td class="dataLabel" colspan="4">
                        <a class="NextPrevLink" href='#' style="font-weight:bold" onclick="HoursEditView.swapAdditional(); return false;">
                        <span id="span_add_details">{$mod_strings['LBL_ADDITIONAL_DETAILS']}</span>
                        {$additional_img}
                        </a>
                    </td>
                </tr>
            </tbody>
            <tbody id="add_content">
                <tr>
                    <td class="{$label_class}" align="right">{$display['billing_currency']['label']}</td>
                    <td class="{$field_class}">{$display['billing_currency']['value']}</td>
                    <td class="{$label_class}" align="right">{$display['paid_currency']['label']}</td>
                    <td class="{$field_class}">{$display['paid_currency']['value']}</td>
                </tr>
                <tr>
                    <td class="{$label_class}" align="right">{$display['billing_rate']['label']}<span class="requiredStar">*</span></td>
                    <td class="{$field_class}">{$display['billing_rate']['value']}</td>
                    <td class="{$label_class}" align="right">{$display['paid_rate']['label']}<span class="requiredStar">*</span></td>
                    <td class="{$field_class}">{$display['paid_rate']['value']}</td>
                </tr>
                <tr>
                    <td class="{$label_class}" align="right">{$display['billing_total']['label']}<span class="requiredStar">*</span></td>
                    <td class="{$field_class}">{$display['billing_total']['value']}</td>
                    <td class="{$label_class}" align="right">{$display['paid_total']['label']}<span class="requiredStar">*</span></td>
                    <td class="{$field_class}">{$display['paid_total']['value']}</td>
                </tr>
                <tr>
                    <td class="{$label_class}" align="right">{$display['tax_code']['label']}</td>
                    <td class="{$field_class}">{$display['tax_code']['value']}</td>
                </tr>
            </tbody></table>
EOQ;

        $layout =& $gen->getLayout();
		$title_img = get_image($module, '');
        $title = javascript_escape("{$title_img}&nbsp;{$row_result->getField('name')}");
        $layout->addScriptLiteral("SUGAR.popups.initContent({title: '$title'});");

        if ($this->id == 'booking_editor')
            $layout->addScriptLiteral("HoursEditView.initEditView('".$gen->form_obj->form_id."');");

        return $body;
	}

	function loadUpdateRequest(RowUpdate &$update, array $input) {}
	
	function validateInput(RowUpdate &$update) {
		return true;
	}
	
	function afterUpdate(RowUpdate &$update) {}
}
?>
