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


require_once('include/layout/forms/FormTableSection.php');
require_once('modules/Cases/Case.php');
require_once('modules/PrepaidAmounts/PrepaidAmount.php');

class PrepaidInfoWidget extends FormTableSection {

	function init($params, $model=null) {
		parent::init($params, $model);
		if(! $this->id)
			$this->id = 'prepaid_info';
	}
	
	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, $context) {
		if($gen->getLayout()->getEditingLayout()) {
			$params = array();
			return $this->renderOuterTable($gen, $parents, $context, $params);		
		}

        $related_id = $row_result->getField('related_id');
        $related_type = $row_result->getField('related_type');

        if ( $row_result->new_record && ($related_id && $related_type == 'Cases') ){
            return $this->renderHtmlView($gen, $row_result);
        } else {
            return '';
        }
	}
	
	function getRequiredFields() {
		return array();
	}
	
	function renderHtmlView(HtmlFormGenerator &$gen, RowResult &$row_result) {
        global $mod_strings, $current_user, $gridline;

        $currency_id = $row_result->getField('currency_id');
        $related_id = $row_result->getField('related_id');
        $related_type = $row_result->getField('related_type');

        $case = new aCase();
        $case->id = $related_id;
        $costs = $case->calculateCosts($currency_id);

        $approved = $costs['hours']['approved'];
        $approved_count = format_number($approved['quantity'] / 60);
        $approved_total = currency_format_number($approved['bill'], array('currency_id' => $currency_id));
        $unapproved = $costs['hours']['unapproved'];
        $unapproved_count = format_number($unapproved['quantity'] / 60);
        $unapproved_total = currency_format_number($unapproved['bill'], array('currency_id' => $currency_id));

        $prepaid_amount = new PrepaidAmount();
        $balance = $prepaid_amount->getBalance($row_result->getField('subcontract_id'), $currency_id, $related_type, $related_id, $row_result->getField('id'));
        $diff = $approved['bill'] + $balance['prev'];

        $credit = currency_format_number($balance['total'], array('currency_id' => $currency_id));
        if($balance['total'] < $diff)
            $credit = '<span class="error">'.$credit.'</span>';

        $debit_total =  currency_format_number($balance['prev'], array('currency_id' => $currency_id));
        $balance = currency_format_number($diff, array('currency_id' => $currency_id));

        $body = <<<EOQ
            <table width="60%" border="0" cellspacing="{$gridline}" cellpadding="0" class="tabDetailView" align="center" style="margin-top: 0.5em">
            <tr>
                <td class="tabDetailViewDL">{$mod_strings['LBL_APPROVED_BOOKED_HOURS']}</td>
                <td class="tabDetailViewDF" align="right">{$approved_total}&nbsp;</td>
                <td class="tabDetailViewDF" align="center">{$approved_count} {$mod_strings['LBL_HOURS']}</td>
            </tr>
            <tr>
                <td class="tabDetailViewDL"><em>{$mod_strings['LBL_UNAPPROVED_BOOKED_HOURS']}</em></td>
                <td class="tabDetailViewDF" align="right"><em>{$unapproved_total}&nbsp;</em></td>
                <td class="tabDetailViewDF" align="center"><em>{$unapproved_count} {$mod_strings['LBL_HOURS']} {$mod_strings['LBL_NOT_BILLED']}</em></td>
            </tr>
            <tr>
                <td class="tabDetailViewDL">{$mod_strings['LBL_APPLIED_DEBITS']}</td>
                <td class="tabDetailViewDF" align="right">{$debit_total}&nbsp;</td>
                <td class="tabDetailViewDF" align="center">&nbsp;</td>
            </tr>
            <tr>
                <td class="tabDetailViewDL" width="40%">{$mod_strings['LBL_CASE_BALANCE']}</td>
                <td class="tabDetailViewDF" align="right" width="20%">{$balance}&nbsp;</td>
                <td class="tabDetailViewDF" width="40%" align="center">&nbsp;</td>
            </tr>
            <tr>
                <td class="tabDetailViewDL" width="40%">{$mod_strings['LBL_SUBCONTRACT_CREDIT_BALANCE']}</td>
                <td class="tabDetailViewDF" align="right" width="20%">{$credit}&nbsp;</td>
                <td class="tabDetailViewDF" width="40%" align="center">&nbsp;</td>
            </tr>
            </table>
EOQ;

        if ($row_result->new_record) {
            $layout =& $gen->getLayout();
            $layout->addScriptInclude('modules/PrepaidAmounts/pa.js', LOAD_PRIORITY_FOOT);
            $layout->addScriptLiteral("setAmount('".$diff."')", LOAD_PRIORITY_FOOT);
        }

        return $body;
	}

	function loadUpdateRequest(RowUpdate &$update, array $input) {}
	
	function validateInput(RowUpdate &$update) {
		return true;
    }
	
	function afterUpdate(RowUpdate &$update) {}
}
?>