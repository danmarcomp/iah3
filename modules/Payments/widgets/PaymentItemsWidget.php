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
require_once('modules/Payments/Payment.php');
require_once('modules/Currencies/CurrencyUtils.php');

class PaymentItemsWidget extends FormTableSection {

	function init($params, $model=null) {
		parent::init($params, $model);
		if(! $this->id)
			$this->id = 'payment_items';
	}
	
	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, $context) {
		if($gen->getLayout()->getEditingLayout()) {
			$params = array();
			return $this->renderOuterTable($gen, $parents, $context, $params);		
		}

        $lstyle = $gen->getLayout()->getType();
        if($lstyle == 'editview') {
            return $this->renderHtmlEdit($gen, $row_result);
        }
        return '';
	}
	
	function getRequiredFields() {
		return array('direction', 'refund');
	}
	
	function renderHtmlEdit(HtmlFormGenerator &$gen, RowResult &$row_result) {
        global $mod_strings;

        $direction = $this->getDirection($row_result, $_REQUEST);
        $payment_id = '';
        $prefix = '';
        $add_button = '';
        $add_credit_button = '';

        if (! $row_result->new_record) {
            $payment_id = $row_result->getField('payment_id');
            $prefix = $row_result->getField('prefix');
            $assoc = Payment::query_line_items($row_result->getField('id'), true);
            if ($direction == 'incoming')
            	$line_items = $assoc['invoices'];
			elseif ($direction == 'credit')
				$line_items = $assoc['credits'];
            else
            	$line_items = $assoc['bills'];
            $editable = 0;
        } else {
            $source = $this->loadSource($_REQUEST);
            $line_items = array();
            
            if ($source) {
				$line_items[] = $this->getNewLine($source, $direction);
            }

			$is_credit = ($direction == 'credit') ? '1' : '0';
            $add_button = "<button type='button' id='add_line' name='add_line' tabindex='25' class='input-button input-outer' onclick='PaymentEditor.addRow({is_credit: $is_credit}, true);'><div class='input-icon icon-add left'></div><span class='input-label'>{$mod_strings['LBL_ADD_LINE_BUTTON_LABEL']}</span></button>";
            $add_button2 = "<button type='button' id='add_line2' name='add_line' tabindex='25' class='input-button input-outer' onclick='PaymentEditor.addRow({is_credit: true, credit_note:true}, true);'><div class='input-icon icon-add left'></div><span class='input-label'>{$mod_strings['LBL_ADD_LINE_BUTTON_LABEL']}</span></button>";
            $editable = 1;
        }


        $allocation_currency = CurrencyUtils::get_currency_name($row_result->getField('currency_id'), true);
        $total_allocated = $row_result->getField('total_amount');
        $credit_allocated = 0;

        $body = <<<EOQ
            <input name="direction" type="hidden" value="{$direction}" />
            <input name="prefix" value="{$prefix}" type="hidden" />
            <input name="payment_id" value="{$payment_id}" type="hidden" />
			<input name="total_amount" value="{$total_allocated}" type="hidden" />
EOQ;
		if ($direction == 'incoming') $body .= <<<EOQ
            <table class="tabForm" style="margin-top: 1em;" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <th colspan="5" align="left" class="dataLabel"><h4 class="dataLabel">{$mod_strings['LBL_PAYMENT_CREDITS']}</h4></th>
                </tr>
                <tr>
                    <td colspan="5">
                    <table id="CreditAllocationTable" width="100%" border="0" cellspacing="2" cellpadding="2">
                    </table>
                    </td>
                </tr>
                <tr>
                    <td colspan="5">
                    &nbsp;
                    </td>
                </tr>
                <tr>
                    <td class="dataLabel" width="14%">{$add_button2}</td>
                    <td class="dataLabel" colspan="2" style="text-align:right">{$mod_strings['LBL_TOTAL_CREDIT_ALLOCATED']}</td>
                    <td class="dataLabel" style="text-align:right"><span id='totalCredits'>{$total_allocated}</span></td>
                    <td class="dataLabel" id='totalCreditsCurrency'>{$allocation_currency}</td>
                </tr>
			</table>
EOQ;
        $body .= <<<EOQ
            <table class="tabForm" style="margin-top: 1em;" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <th colspan="5" align="left" class="dataLabel"><h4 class="dataLabel">{$mod_strings['LBL_PAYMENT_ALLOCATION']}</h4></th>
                </tr>
                <tr>
                    <td colspan="5">
                    <table id="PaymentAllocationTable" width="100%" border="0" cellspacing="2" cellpadding="2">
                    </table>
                    </td>
                </tr>
                <tr>
                    <td colspan="5">
                    &nbsp;
                    </td>
                </tr>
                <tr>
                    <td class="dataLabel" width="14%">{$add_button}</td>
                    <td class="dataLabel" colspan="2" style="text-align:right">{$mod_strings['LBL_TOTAL_ALLOCATED']}</td>
                    <td class="dataLabel" style="text-align:right"><span id='totalAllocated'>{$total_allocated}</span></td>
                    <td class="dataLabel" id='totalCurrency'>{$allocation_currency}</td>
                </tr>
                <tr style="display:none" id="dollar_totals">
                    <td>&nbsp;</td>
                    <td width="22%" class="dataLabel" style="text-align: right">&nbsp;</td>
                    <td width="22%" class="dataLabel">&nbsp;</td>
                    <td width="22%" class="dataLabel" style="text-align:right" id='totalAllocatedUsd'></td>
                    <td width="22%" class="dataLabel" id='totalCurrencyUsd'></td>
                </tr>
                <tr>
                    <td class="dataLabel">{$mod_strings['LBL_ACCOUNT_BALANCE']}</td>
                    <td class="dataLabel" style="text-align: right" id="balance">&nbsp;</td>
                    <td class="dataLabel" id="balance_currency">&nbsp;</td>
                    <td class="dataLabel">&nbsp;</td>
                    <td class="dataLabel">&nbsp;</td>
                </tr>
            </table>
EOQ;

        $layout =& $gen->getLayout();

        $layout->addScriptInclude('modules/Payments/payments.js', LOAD_PRIORITY_FOOT);

        $balance_field = ($direction != 'outgoing') ? 'balance' : 'balance_payable';
        $items = array('invoices' => $line_items);
        $layout->addScriptLiteral($this->getLineItemsJs($items, $row_result, $balance_field, $direction, $editable), LOAD_PRIORITY_FOOT);

		$form_name = $gen->getFormName();
		$editorSetupJs = "SUGAR.ui.registerInput('$form_name', PaymentEditor);";
        $layout->addScriptLiteral($editorSetupJs, LOAD_PRIORITY_FOOT);

        return $body;
	}

    /**
     * Get payment direction
     *
     * @param RowResult $row_result
     * @param array $input - user input ($_REQUEST)
     * @return string
     */
    function getDirection(RowResult $row_result, $input) {
        $direction = 'incoming';

        if ($row_result->new_record) {
            if (! empty($input['direction'])) {
                $direction = $input['direction'];
            } elseif (isset($input['list_layout_name']) && $input['list_layout_name'] == 'Outgoing') {
                $direction = 'outgoing';
            } elseif (isset($input['invoice_id'])) {
                $direction = 'incoming';
            } elseif (isset($input['creditnote_id'])) {
                $direction = 'credit';
            } elseif (isset($input['bill_id'])) {
                $direction = 'outgoing';
            }
        } else {
            $direction = $row_result->getField('direction', 'incoming');
        }

        return $direction;
    }

    /**
     * Load payment source
     *
     * @param array $input - user input ($_REQUEST) 
     * @return null|RowResult
     */
    function loadSource($input) {
        $source = null;
        $source_name = null;
        $source_id = null;

        if(! empty ($input['invoice_id'])) {
            $source_name = 'Invoice';
            $source_id = $input['invoice_id'];
        } else if (! empty($input['bill_id'])) {
            $source_name = 'Bill';
            $source_id = $input['bill_id'];
        } else if (! empty($input['creditnote_id'])) {
            $source_name = 'CreditNote';
            $source_id = $input['creditnote_id'];
        }

        if ($source_name && $source_id)
            $source = ListQuery::quick_fetch($source_name, $source_id);

        return $source;
    }

    /**
     * Get new payment line item
     *
     * @param RowResult $source
     * @param string $direction
     * @return array
     */
    function getNewLine(RowResult $source, $direction) {
    	if($source->base_model == 'Invoice')
    		$number = 'invoice_number';
    	else if($source->base_model == 'Bill')
    		$number = 'bill_number';
    	else if($source->base_model == 'CreditNote')
    		$number = 'credit_number';
        $display = array('currency_id' => $source->getField('currency_id'), 'currency_symbol' => false);
        $amount_due = unformat_number(currency_format_number($source->getField('amount_due'), $display));

        $new_line = array(
            'id' => '',
            'invoice_id' => $source->getField('id'),
            'invoice_name' => $source->getField('prefix') . $source->getField($number) . ': ' . $source->getField('name'),
            'amount' => $amount_due,
            'amount_usd' => $source->getField('amount_due_usdollar'),
            'invoice_amount_usd' => $source->getField('amount_usdollar'),
            'invoice_amount_due' => 0.0,
            'invoice_amount_due_usd' => 0.0,
            'invoice_currency_id' => $source->getField('currency_id'),
            'currency_id' => $source->getField('currency_id'),
            'exchange_rate' => $source->getField('exchange_rate'),
			'is_credit' => ($source->base_model == 'CreditNote') ? 1 : 0,
        );

        return $new_line;
    }

    /**
     * Get line items javascript
     *
     * @param array $items
     * @param RowResult $row_result
     * @param string $balance_field
     * @param int $editable
     * @return string
     */
    function getLineItemsJs(&$items, RowResult $row_result, $balance_field, $direction, $editable = 1) {
        $editable = (int)$editable;
        $account_balance = '0.0';
		$account_id = $row_result->getField('account_id');
        $module = ($direction != 'outgoing') ? 'Payments' : 'PaymentsOut';
        $table_id = 'PaymentAllocationTable';
        $credit_table_id = 'CreditAllocationTable';

        if ($account_id) {
            $fields = array($balance_field);
            $account = ListQuery::quick_fetch_row('Account', $account_id, $fields);
			if ($account)
				$account_balance = $account[$balance_field];
        }
        
        $setup = array(
            'table_id' => $table_id,
            'credit_table_id' => $credit_table_id,
			'original_account_id' => $account_id,
			'original_amount_usd' => $row_result->getField('amount_usdollar', 0.0),
			'account_balance_usd' => $account_balance,
            'payment_module' => $module,
            'init_lines' => array_merge(
				$this->formatItems($items['invoices'])
			),
			'editable' => $editable,
			'direction' => $direction,
        );
        
        $json = getJSONobj();
		$init = $json->encode($setup);
        $script = "PaymentEditor.init($init);";

        return $script;
    }

    /**
     * Add line items rows
     *
     * @param array $line_items
     * @param bool $credit_note
     * @return string
     */
	function formatItems($line_items) {
		$init_lines = array();

        if(isset($line_items) && is_array($line_items)) {
            foreach ($line_items as $key => $value) {
                $init_lines[] = array(
                    'id' => $value['invoice_id'],
                    'name' => isset($value['invoice_no']) ? $value['invoice_no'] . ': ' . $value['invoice_name'] : $value['invoice_name'],
                    'allocated' => $value['amount'],
                    'allocated_usd' => $value['amount_usd'],
                    'amount_usd' => $value['invoice_amount_usd'],
                    'amount_due' => $value['invoice_amount_due'],
                    'amount_due_usd' => $value['invoice_amount_due_usd'],
                    'currency_id' => $value['currency_id'],
                    'exchange_rate' => $value['exchange_rate'],
                    'is_credit' => $value['is_credit'],
                );
            }
        }

        return $init_lines;
    }

	function loadUpdateRequest(RowUpdate &$update, array $input) {}
	
	function validateInput(RowUpdate &$update) {
		return true;
    }
	
	function afterUpdate(RowUpdate &$update) {}
		
	function renderPdf(PdfFormGenerator &$gen, RowResult &$row_result, array $parents, array $context)
	{
		$gen->pdf->set_focus($row_result);
		$gen->pdf->print_main();
	}
}
?>
