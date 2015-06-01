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

class EmailTemplateEditCreate extends FormField {

	function init($params=null, $model=null) {
        parent::init($params, $model);
	}

	function getRequiredFields() {
        $fields = array('campaign_id', 'template');
		return $fields;
	}
	
	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, array $context) {
        global $app_strings;
		$campaign_id = $row_result->getField('campaign_id');
		$tpl_id = $row_result->getField('template_id');
		$extra = '';

		if ($campaign_id) {
			$display = $tpl_id ? '' : 'style="display:none"';
			$extra = '<div style="margin-top:0.2em"><a ' .$display . ' id="template_edit_link" href="index.php?module=EmailTemplates&action=EditView&campaign_id=' . $campaign_id . '" target="_blank" class="tabDetailViewDFLink"><div class="input-icon icon-edit left"></div>'.$app_strings['LBL_EDIT_BUTTON_LABEL'].'</a>';
			$extra .= ' <a href="index.php?module=EmailTemplates&action=EditView&campaign_id=' . $campaign_id . '" target="_blank" class="tabDetailViewDFLink"><div class="input-icon theme-icon create-EmailTemplate left"></div>'.$app_strings['LBL_CREATE_BUTTON_LABEL'].'</a></div>';
		}
		
		$form_name = $gen->getFormName();

		$js = <<<JS
function hide_show_template_edit()
{
	var tpl_id = SUGAR.ui.getFormInput('DetailForm', 'template').getKey();
	if (tpl_id) {
		$('template_edit_link').style.display = '';
		$('template_edit_link').href = "index.php?module=EmailTemplates&action=EditView&campaign_id=$campaign_id&record=" + tpl_id;
	} else {
		$('template_edit_link').style.display = 'none';
	}
}
SUGAR.ui.onInitForm('$form_name', hide_show_template_edit);
JS;
        $layout =& $gen->getLayout();
        $layout->addScriptLiteral($js, LOAD_PRIORITY_FOOT);
		return parent::renderHtml($gen, $row_result, $parents, $context) . $extra;
	}

}
