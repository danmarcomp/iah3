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
require_once('modules/HR/Employee.php');

class EmployeeWidget extends FormTableSection {

	function init($params, $model=null) {
		parent::init($params, $model);
	}
	
	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, $context) {
		if($gen->getLayout()->getEditingLayout()) {
			$params = array();
			return $this->renderOuterTable($gen, $parents, $context, $params);		
		}

        if ($gen->getLayout()->getType() == 'view') {
    		return $this->renderHtmlView($gen, $row_result);
        } else {
            return '';
        }
	}
	
	function getRequiredFields() {
		return array();
	}
	
	function renderHtmlView(HtmlFormGenerator &$gen, RowResult &$row_result) {
		$title = to_html($this->getLabel());
        $body = '';

        if ($this->id == 'employee_leave')
            $body = $this->renderLeave($gen, $row_result, $title);

        return $body;
    }

    /**
     * Render html for Employee Leave section
     *
     * @param HtmlFormGenerator $gen
     * @param RowResult $row_result
     * @param string $title
     * @return string
     */
    function renderLeave(HtmlFormGenerator &$gen, RowResult &$row_result, $title) {
        global $mod_strings, $app_strings;

        $now = localtime(time(), 1);
        $current_year = $now['tm_year'] + 1900;
        $start_month = fiscal_year_start_month();

        if(isset($_REQUEST['fiscal_year'])) {
            $fiscal_year = $_REQUEST['fiscal_year'];
        } else {
            $fiscal_year = $current_year;
            if($now['tm_mon'] + 1 < $start_month)
                $fiscal_year --;
        }

        $fy_options = array();
        for($y = 2000; $y <= $current_year; $y++) {
            $fy_options[$y] = "FY $y";
            if($start_month != 1)
                $fy_options[$y].= '-'.($y+1);
        }

        $onchange = "return SUGAR.ui.sendForm(document.forms.DetailForm, {'fiscal_year':this.field.value}, null);";
        $spec = array('name' => 'fiscal_year', 'options' => $fy_options, 'onchange' => $onchange);
        $fiscal_years_select = $gen->form_obj->renderSelect($spec, $fiscal_year);

        $edit_button_click = "return SUGAR.ui.sendForm(document.forms.DetailForm, {'action':'EditLeave', 'employee_id':this.form.record.value, 'record':'', 'layout':'Leave'}, null);";

        $body = <<<EOQ
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabDetailView" style="margin-top: 0.5em">
            <tr><td>
                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                <th align="left" class="tabDetailViewDL" style="text-align: left" colspan="5">
                    <table cellpadding="0" cellspacing="2">
                    <tr>
                    <td>
                        <h4 class="tabDetailViewDL">{$title}</h4>
                    </td>
                    <td>
                        {$fiscal_years_select}
                        <button type="button" class="input-button input-outer" onclick="{$edit_button_click}"><div class="input-icon icon-edit left"></div><span class="input-label">{$app_strings['LBL_EDIT_BUTTON']}</span></button>
                    </td>
                    </tr>
                    </table>
                </th>
                </tr>
                <tr><td>
                <table width="100%" border='0' cellspacing=' cellpadding='0'>
                    <tr>
                    <td width="12%" class="tabDetailViewDL" style="text-align: left" rowspan="2" NOWRAP>{$mod_strings['LBL_MONTH']}</td>
                    <td width="44%" class="tabDetailViewDL" style="text-align: center" colspan="3" NOWRAP>{$mod_strings['LBL_VACATION_DAYS']}</td>
                    <td width="44%" class="tabDetailViewDL" style="text-align: center" colspan="3" NOWRAP>{$mod_strings['LBL_SICK_DAYS']}</td>
                    </tr><tr>
                    <td width="15%" class="tabDetailViewDL" style="text-align: left" NOWRAP>{$mod_strings['LBL_TAKEN']}</td>
                    <td width="15%" class="tabDetailViewDL" style="text-align: left" NOWRAP>{$mod_strings['LBL_ACCRUED']}</td>
                    <td width="14%" class="tabDetailViewDL" style="text-align: left" NOWRAP>{$mod_strings['LBL_BALANCE']}</td>
                    <td width="15%" class="tabDetailViewDL" style="text-align: left" NOWRAP>{$mod_strings['LBL_TAKEN']}</td>
                    <td width="15%" class="tabDetailViewDL" style="text-align: left" NOWRAP>{$mod_strings['LBL_ACCRUED']}</td>
                    <td width="14%" class="tabDetailViewDL" style="text-align: left" NOWRAP>{$mod_strings['LBL_BALANCE']}</td>
                    </tr>
EOQ;

        $employee = new Employee();
        $employee->id = $row_result->getField('id');
        $employee_leave =& $employee->get_employee_leave($fiscal_year);
        $leave_taken =& $employee_leave->get_records_for_fiscal_year();
        $fields = array('vacation_taken', 'sickleave_taken',
            'vacation_accrued', 'sickleave_accrued',
            'vacation_balance', 'sickleave_balance');
        $vacation_allowed = $employee_leave->vacation_allowed;
        $sick_days_allowed = $employee_leave->sick_days_allowed;
        $vacation_balance = $employee_leave->vacation_carried_over;

        $sickleave_balance = 0;
        $row = 1;

        foreach($leave_taken as $period => $values) {
            $values['vacation_accrued'] = $vacation_allowed * $row / 12;
            $values['sickleave_accrued'] = $sick_days_allowed * $row / 12;
            $vacation_balance -= $values['vacation_taken'];
            $sickleave_balance -= $values['sickleave_taken'];
            $values['vacation_balance'] = $vacation_balance + $values['vacation_accrued'];
            $values['sickleave_balance'] = $sickleave_balance + $values['sickleave_accrued'];
            $row ++;

            foreach($fields as $f) {
                $val = format_number($values[$f], 2, 2);
                if($values[$f] == 0) {
                    $val = '&mdash;';
                } else if($val[0] == '-') {
                    $val = "<span class='overdueTask'>$val</span>";
                }
                $values[$f] = $val;
            }

            $body .= <<<EOQ
                <tr>
                <td class="tabDetailViewDL" style="text-align: left" NOWRAP>{$period}</td>
                <td class="tabDetailViewDF" NOWRAP>{$values['vacation_taken']}</td>
                <td class="tabDetailViewDF" NOWRAP>{$values['vacation_accrued']}</td>
                <td class="tabDetailViewDF" NOWRAP>{$values['vacation_balance']}</td>
                <td class="tabDetailViewDF" NOWRAP>{$values['sickleave_taken']}</td>
                <td class="tabDetailViewDF" NOWRAP>{$values['sickleave_accrued']}</td>
                <td class="tabDetailViewDF" NOWRAP>{$values['sickleave_balance']}</td>
                </tr>
EOQ;
        }

        $body .= "</table></td></tr></table></td></tr></table>";

        return $body;
    }

	function loadUpdateRequest(RowUpdate &$update, array $input) {}
	
	function validateInput(RowUpdate &$update) {
		return true;
	}
	
	function afterUpdate(RowUpdate &$update) {}
		
	function renderPdf(PdfFormGenerator &$gen, RowResult &$row_result, array $parents, array $context) {
		if ($this->id == 'employee_leave') $this->renderPdfLeave($gen, $row_result, $parents, $context);
		elseif ($this->id == 'dependants_subpanel') $this->renderPdfDependants($gen, $row_result, $parents, $context);
	}

	function renderPdfLeave(PdfFormGenerator &$gen, RowResult &$row_result, array $parents, array $context) {
        global $mod_strings;

        $now = localtime(time(), 1);
        $current_year = $now['tm_year'] + 1900;
        $start_month = fiscal_year_start_month();

        if(isset($_REQUEST['fiscal_year'])) {
            $fiscal_year = $_REQUEST['fiscal_year'];
        } else {
            $fiscal_year = $current_year;
            if($now['tm_mon'] + 1 < $start_month)
                $fiscal_year --;
        }

        $fy_options = array();
        for($y = 2000; $y <= $current_year; $y++) {
            $fy_options[$y] = "FY $y";
            if($start_month != 1)
                $fy_options[$y].= '-'.($y+1);
        }

		$title =  $this->getLabel() . ' [' . $fy_options[$fiscal_year] . ']';

		$cols = array(
			'month' => array('width' => '10%'),
			'label1' => array('width' => '45%', 'title' => $mod_strings['LBL_VACATION_DAYS']),
			'label2' => array('width' => '45%', 'title' => $mod_strings['LBL_SICK_DAYS']),
		);
		$data = array(
		);
		$gen->pdf->DrawTable($data, $cols, $title, true);

		$cols = array(
			'month' => array('width' => '10%'),
			'vt' => array('width' => '15%'),
			'va' => array('width' => '15%'),
			'vb' => array('width' => '15%'),
			'st' => array('width' => '15%'),
			'sa' => array('width' => '15%'),
			'sb' => array('width' => '15%'),
		);
		$data = array(
			array(
				'month'=> $mod_strings['LBL_MONTH'],
				'vt'=> $mod_strings['LBL_TAKEN'],
				'va'=> $mod_strings['LBL_ACCRUED'],
				'vb'=> $mod_strings['LBL_BALANCE'],
				'st'=> $mod_strings['LBL_TAKEN'],
				'sa'=> $mod_strings['LBL_ACCRUED'],
				'sb'=> $mod_strings['LBL_BALANCE'],
			),
		);
		$gen->pdf->DrawTable($data, $cols, '', false, array('background-color' => array(200, 200, 200)));

		$data = array();

        $employee = new Employee();
        $employee->id = $row_result->getField('id');
        $employee_leave =& $employee->get_employee_leave($fiscal_year);
        $leave_taken =& $employee_leave->get_records_for_fiscal_year();
        $fields = array('vacation_taken', 'sickleave_taken',
            'vacation_accrued', 'sickleave_accrued',
            'vacation_balance', 'sickleave_balance');
        $vacation_allowed = $employee_leave->vacation_allowed;
        $sick_days_allowed = $employee_leave->sick_days_allowed;
        $vacation_balance = $employee_leave->vacation_carried_over;

        $sickleave_balance = 0;
        $row = 1;

        foreach($leave_taken as $period => $values) {
            $values['vacation_accrued'] = $vacation_allowed * $row / 12;
            $values['sickleave_accrued'] = $sick_days_allowed * $row / 12;
            $vacation_balance -= $values['vacation_taken'];
            $sickleave_balance -= $values['sickleave_taken'];
            $values['vacation_balance'] = $vacation_balance + $values['vacation_accrued'];
            $values['sickleave_balance'] = $sickleave_balance + $values['sickleave_accrued'];
            $row ++;

            foreach($fields as $f) {
                $val = format_number($values[$f], 2, 2);
                if($values[$f] == 0) {
                    $val = '-';
                }
                $values[$f] = $val;
            }

			$data[] = array(
				'month' => $period,
				'vt' => $values['vacation_taken'],
				'va' => $values['vacation_accrued'],
				'vb' => $values['vacation_balance'],
				'st' => $values['sickleave_taken'],
				'sa' => $values['sickleave_accrued'],
				'sb' => $values['sickleave_balance'],
			);
		}

		$gen->pdf->DrawTable($data, $cols, '', false);
		return;
		
	}

	function renderPdfDependants(PdfFormGenerator &$gen, RowResult &$row_result, array $parents, array $context) {
        global $mod_strings;
		$title =  $this->getLabel();
		
		$cols = array(
			'name' => array('width' => '42%', 'title' => $mod_strings['LBL_LIST_DEPENDANT_NAME']),
			'date' => array('width' => '22%', 'title' => $mod_strings['LBL_LIST_DATE_OF_BIRTH']),
			'relationship' => array('width' => '36%', 'title' => $mod_strings['LBL_LIST_RELATIONSHIP']),
		);
		$data = array();

        $fields = array('id', 'employee', 'first_name', 'last_name',
            'dob', 'relationship');
        $lq = new ListQuery('EmployeeDependant', $fields);
        $lq->addPrimaryKey();
        $lq->addAclFilter('edit');
        $lq->addSimpleFilter('employee_id', $row_result->getField('id'));
        $result = $lq->fetchAll();

        if ($result) {
			foreach($result->rows as $row) {
				$data[] = array(
					'name' => $row['first_name'] . ' ' . $row['last_name'],
					'date'=> $row['dob'],
					'relationship' => $row['relationship'],
				);
			}
			$gen->pdf->DrawTable($data, $cols, $title, true);
		}
    }
}
?>