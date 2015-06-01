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
require_once('modules/Project/Project.php');

class FinancialWidget extends FormTableSection {
	function init($params, $model=null) {
		parent::init($params, $model);
		if(! $this->id)
			$this->id = 'project_financial';
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
		return $this->renderHtmlView($row_result);
	}
	
	function getRequiredFields() {
		return array('total_expected_cost', 'total_expected_revenue', 'total_actual_cost', 'total_actual_revenue');
	}
	
	function renderHtmlView(RowResult &$row_result) {
        global $current_user, $mod_strings;

        $show_financials = (bool)$current_user->getPreference('financial_information');
        $body = "";


        if ($show_financials) {
			$financials = new ProjectFinancials();
			$financials->load(RowUpdate::for_result($row_result));	
            $periods = $financials->get_project_financial_periods();

            $numbers = Array();
            foreach($periods as $period) {
                $numbers[$period] = array();
                foreach(ProjectFinancials::$value_fields as $f)
                    $numbers[$period][$f] = 0.0;
            }

            foreach($financials->get_records() as $period => $values) {
                $numbers[$period] = $values;
            }
            ksort($numbers);

			$title = to_html($this->getLabel());
            $body .= <<<EOQ
                <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabDetailView" style="margin-top: 0.5em">
                <tr><td>
                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                    <th align="left" class="tabDetailViewDL" colspan="5"><h4 class="tabDetailViewDL">{$title}</h4></th>
                    </tr>
                    <tr><td>
                    <table width="100%" border='0' cellspacing='0' cellpadding='0' id='financialsTable'><tr>
                        <td width="12%" class="tabDetailViewDL" style="text-align: center" NOWRAP>{$mod_strings["LBL_MONTH"]}</td>
                        <td width="22%" class="tabDetailViewDL" style="text-align: right" NOWRAP>{$mod_strings["LBL_EXPECTED_COST"]}</td>
                        <td width="22%" class="tabDetailViewDL" style="text-align: right" NOWRAP>{$mod_strings["LBL_EXPECTED_REVENUE"]}</td>
                        <td width="22%" class="tabDetailViewDL" style="text-align: right" NOWRAP>{$mod_strings["LBL_ACTUAL_COST"]}</td>
                        <td width="22%" class="tabDetailViewDL" style="text-align: right" NOWRAP>{$mod_strings["LBL_ACTUAL_REVENUE"]}</td>
                        </tr>
EOQ;

            $cparams = array('currency_symbol' => false, 'currency_id' => $row_result->getField('currency_id'));

            foreach($numbers as $period => $values) {
                $body .= '<tr><td class="tabDetailViewDL" style="text-align: left" NOWRAP>' .$period. '</td>';

                foreach(ProjectFinancials::$value_fields as $f) {
                    if($values[$f] == 0)
                        $val = "&mdash;";
                    else
                        $val = currency_format_number($values[$f], $cparams);

                    $body .= '<td class="tabDetailViewDF" style="text-align:right" NOWRAP>' .$val. '</td>';
                }
                $body .= '</tr>';
            }

            $body .= $this->getTotalRow($row_result, $cparams);

            $body .= <<<EOQ
                </table>
                </td>
                </tr>
                </table>
            </td></tr>
            </table>
EOQ;
        }

        return $body;
	}

	function renderHtmlEdit(HtmlFormGenerator &$gen, RowResult &$row_result) {
        global $current_user, $app_strings, $mod_strings;
        $gen->getLayout()->addScriptInclude("modules/Project/financials.js", LOAD_PRIORITY_BODY);

        $show_financials = (bool)$current_user->getPreference('financial_information');
        $body = "";

        if ($show_financials) {
			$use_timesheets = $row_result->getField('use_timesheets');

        	if($row_result->new_record && $_REQUEST['action'] != 'Duplicate') {
        		$records = array();
        	} else {
				$finance = new ProjectFinancials();
				$finance->load(RowUpdate::for_result($row_result));
				$records = $finance->get_records();
			}

            $json = getJSONobj();
            $numbers = $json->encode($records);

            $row_remove_text = $app_strings['LNK_REMOVE'];
            $row_remove_img = "<div class='input-icon icon-delete active-icon' title='$row_remove_text'></div>";

            $add_financials_js = <<<EOS
                financials_data = $numbers;
                row_remove_img = "$row_remove_img";
EOS;

            if (! empty($project_id)) {
                foreach ($records as $period => $data) {
                    $encoded = $json->encode($data);
                    $add_financials_js .= sprintf("financials_changed['%s'] = %s;", $period, $encoded);
                }
            }

            $layout =& $gen->getLayout();
            $layout->addScriptLiteral('readonly_fields["actual_cost"] = ' . ($use_timesheets ? 'true;' : 'false;'));

            $body .= '<input type="hidden" name="financials_data" value="">';

			$title = to_html($this->getLabel());
            $body .= <<<EOQ
                <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm" style="margin-top: 1em">
                <tr><td>
                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                    <th align="left" class="dataLabel" colspan="5"><h4 class="dataLabel">{$title}</h4></th>
                    </tr>
                    <tr><td>
                        <table width="100%" border='0' cellspacing='0' cellpadding='0' id="financialsTable"><tr>
                        <td width="12%" class="dataLabel" NOWRAP>{$mod_strings["LBL_MONTH"]}</td>
                        <td width="21%" class="dataLabel" NOWRAP>{$mod_strings["LBL_EXPECTED_COST"]}</td>
                        <td width="21%" class="dataLabel" NOWRAP>{$mod_strings["LBL_EXPECTED_REVENUE"]}</td>
                        <td width="21%" class="dataLabel" NOWRAP>{$mod_strings["LBL_ACTUAL_COST"]}</td>
                        <td width="21%" class="dataLabel" NOWRAP>{$mod_strings["LBL_ACTUAL_REVENUE"]}</td>
                        <td width="4%" class="dataLabel" NOWRAP>&nbsp;</td>
                        </tr>
                        </table>
                    </td>
                    </tr>
                    </table>
                </td></tr>
                </table>
EOQ;

			$form_name = $gen->getFormName();
			$add_financials_js .= "SUGAR.ui.registerInput('$form_name', FinancialsEditor);";
            $layout->addScriptLiteral($add_financials_js, LOAD_PRIORITY_FOOT);
        }

        return $body;
	}

    /**
     * Get table row with total values
     *
     * @param RowResult $row_result
     * @param array $currency_params
     * @return string
     */
    function getTotalRow(RowResult &$row_result, $currency_params) {
        global $mod_strings;

        $row = '<tr><td class="tabDetailViewDL" style="text-align: left; border-top-style: double;" NOWRAP><strong>'.strtoupper($mod_strings['LBL_TOTAL']).'<strong></td>';

        foreach(ProjectFinancials::$value_fields as $f) {
            if($row_result->getField('total_' . $f) == 0) {
                $val = "&mdash;";
            } else {
                $val = currency_format_number($row_result->getField('total_' . $f), $currency_params);
            }

            $row .= '<td class="tabDetailViewDF" style="text-align:right; border-top-style: double;" NOWRAP><strong>' .$val. '<strong></td>';
        }

        $row .= '</tr>';

        return $row;
    }

    function loadUpdateRequest(RowUpdate &$update, array $input) {
        $upd_financials = array();
        if(! empty($input['financials_data'])) {
            require_once('include/JSON.php');
            $json = new JSON(JSON_LOOSE_TYPE);
            $upd_financials = $json->decode(from_html($input['financials_data']));
        }
        $update->setRelatedData($this->id.'_rows', $upd_financials);
	}
	
	function validateInput(RowUpdate &$update) {
		return true;
	}
	
	function afterUpdate(RowUpdate &$update) {
		$row_updates = $update->getRelatedData($this->id.'_rows');
        $project_id = $update->getPrimaryKeyValue();

        if($project_id != null) {
        	$finance = new ProjectFinancials();
            $finance->load($update);

            if (sizeof($row_updates) > 0){
                foreach($row_updates as $period => $data) {
                    $finance->update_for_period($period, $data);
                }
            }

            $finance->calculate_costs($update->getField('use_timesheets'));
            $finance->save();
        }
	}
	
	function renderPdf(PdfFormGenerator &$gen, RowResult &$row_result, array $parents, array $context) {
        global $current_user, $mod_strings;

        $show_financials = (bool)$current_user->getPreference('financial_information');
        
        if ($show_financials) {
			$finance = new ProjectFinancials();
            $finance->load(RowUpdate::for_result($row_result));
            $periods = $finance->get_project_financial_periods();

            $numbers = Array();
            foreach($periods as $period) {
                $numbers[$period] = array();
                foreach(ProjectFinancials::$value_fields as $f)
                    $numbers[$period][$f] = 0.0;
            }

            foreach($finance->get_records() as $period => $values) {
                $numbers[$period] = $values;
            }
            ksort($numbers);

			$title = $this->getLabel();

			$cols = array(
				'month' => array('width' => '12%', 'title' => $mod_strings['LBL_MONTH']),
				'expected_cost' => array('width' => '22%', 'title' => $mod_strings['LBL_EXPECTED_COST']),
				'expected_revenue' => array('width' => '22%', 'title' => $mod_strings['LBL_EXPECTED_REVENUE']),
				'actual_cost' => array('width' => '22%', 'title' => $mod_strings['LBL_ACTUAL_COST']),
				'actual_revenue' => array('width' => '22%', 'title' => $mod_strings['LBL_ACTUAL_REVENUE']),
			);

            $cparams = array('currency_symbol' => false, 'currency_id' => $row_result->getField('currency_id'));
            $body = '';
            
            foreach($numbers as $period => $values) {
                $body .= '<tr><td class="tabDetailViewDL" style="text-align: left" NOWRAP>' .$period. '</td>';

				$row = array('month' => $period);
                foreach(ProjectFinancials::$value_fields as $f) {
                    if($values[$f] == 0)
                        $val = "-";
                    else
                        $val = currency_format_number($values[$f], $cparams);

					$row[$f] = $val;
				}
				$data[] = $row;
            }
			$gen->pdf->DrawTable($data, $cols, $title, true);

        }
	}
}
?>
