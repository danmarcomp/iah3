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
require_once('modules/ProjectTask/ProjectTask.php');

class CostsWidget extends FormTableSection {

	function init($params, $model=null) {
		parent::init($params, $model);
		if(! $this->id)
			$this->id = 'task_costs';
	}
	
	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, $context) {
		if($gen->getLayout()->getEditingLayout()) {
			$params = array();
			return $this->renderOuterTable($gen, $parents, $context, $params);		
		}

		return $this->renderHtmlView($row_result);
	}
	
	function getRequiredFields() {
		return array();
	}
	
	function renderHtmlView(RowResult &$row_result) {
        global $mod_strings, $current_user;

        $id = $row_result->getField('id');
        $project_id = $row_result->getField('parent_id');
        $exchange_rate = $row_result->getField('exchange_rate');
        $currency_id = $row_result->getField('currency_id');

        if (! $currency_id)
            $currency_id = '-99';

        $booking_info = ProjectTask::get_booking_info($id, $currency_id, $exchange_rate);
        $show_financials = (bool)$current_user->getPreference('financial_information');
        $estimated_effort = format_number($booking_info['estimated_effort']);
        $estimated_effort = EditableForm::format_duration($estimated_effort * 60);
        $actual_row = '';
        $estimated_html = '';
        $width = "80%";

        if ($show_financials) {
            $fparams = array('currency_id' => $currency_id);

            $width = "30%";
            $estimated_cost = currency_format_number($booking_info['estimated_cost'], $fparams);

            $estimated_html = <<<EOQ
                <td class="tabDetailViewDL" width="20%">{$mod_strings['LBL_ESTIMATED_COST']}</td>
                <td class="tabDetailViewDF" colspan="3" width="30%">{$estimated_cost}</td>
EOQ;

            if($this->useTimesheets($project_id)) {
                $actual_effort = EditableForm::format_duration($booking_info['actual_effort']);
                $actual_cost = currency_format_number($booking_info['actual_cost'], $fparams);

                $actual_row = <<<EOQ
                  <tr>
                  <td class="tabDetailViewDL" width="20%">{$mod_strings['LBL_ACTUAL_EFFORT']}</td>
                  <td class="tabDetailViewDF" colspan="3" width="30%">{$actual_effort}</td>
                  <td class="tabDetailViewDL" width="20%">{$mod_strings['LBL_ACTUAL_COST']}</td>
                  <td class="tabDetailViewDF" colspan="3" width="30%">{$actual_cost}</td>
                  </tr>
EOQ;
            }
        }

        $body = <<<EOQ
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabDetailView" style="margin-top: 0.5em">
            <tr>
            <td class="tabDetailViewDL" width="20%">{$mod_strings['LBL_ESTIMATED_EFFORT']}</td>
            <td class="tabDetailViewDF" colspan="3" width="{$width}">{$estimated_effort}</td>
            {$estimated_html}
            </tr>
            {$actual_row}
            </table>
EOQ;

        return $body;
	}

    /**
     * Check use parent project timesheets or not
     *
     * @return bool
     */
    function useTimesheets($project_id) {
        return ProjectTask::use_timesheets($project_id);
    }

	function loadUpdateRequest(RowUpdate &$update, array $input) {}
	
	function validateInput(RowUpdate &$update) {
		return true;
    }
	
	function afterUpdate(RowUpdate &$update) {}
}
?>