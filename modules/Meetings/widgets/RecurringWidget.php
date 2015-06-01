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
require_once 'modules/Recurrence/RecurrenceRule.php';

class RecurringWidget extends FormTableSection {

	var $model_name;

	function init($params, $model=null) {
		parent::init($params, $model);
		if(! $this->id)
			$this->id = 'recurring';
		if($model) $this->model_name = $model->name;
	}
	
	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, array $context) {
        $lstyle = $gen->getLayout()->getType();

        if($lstyle == 'editview')
            return $this->renderHtmlEdit($gen, $row_result);

        return $this->renderHtmlView($row_result);
	}
	
	function getRequiredFields() {
		return array('is_recurring', 'recurrence_of_id', 'recurrence_index');
	}

	function renderHtmlView(RowResult &$row_result) {
        global $app_strings;


        $id = ! $row_result->getField('recurrence_of_id') ?  $row_result->getField('id') : $row_result->getField('recurrence_of_id');
        $idx = ! $row_result->getField('recurrence_index') ? 0 : $row_result->getField('recurrence_index');
        $next_id = Meeting::get_recurring_instance_id($id, $idx, 'next');
        $prev_id = Meeting::get_recurring_instance_id($id, $idx, 'prev');
        $recurrence_label = 'LBL_IS_RECURRENCE';

        if ($idx == 0)
            $recurrence_label = 'LBL_IS_FIRST_RECURRING';

        $html = '<table class="tabDetailView" width="100%" cellspacing="0" cellpadding="0" border="0"><tr><td colspan="4" class="listViewPaginationTdS1" style="padding: 5px"><span class="pageNumbers" style="font-size: 130%">';
        $html .= get_image('scheduled_inline', 'alt=""', 12, 12) . '&nbsp;' . translate($recurrence_label, $row_result->getModuleDir()). '</span>';

        if ($idx)
            $html .= $this->getInstanceLink($row_result->getField('recurrence_of_id'), 'start');

        if ($prev_id)
            $html .= $this->getInstanceLink($prev_id, 'prev');

        if ($idx)
            $html .= '&nbsp;&nbsp;&nbsp;<span class="pageNumbers">(' . $app_strings['LBL_RECURRENCE_NUMBER'] . ' '.  $idx . ')</span>';

        if ($next_id)
            $html .= $this->getInstanceLink($next_id, 'next');

        $html .= '</td></tr></table>';

        return $html;
	}

	function renderHtmlEdit(HtmlFormGenerator &$gen, RowResult $row_result) {
        global $mod_strings;

        $options = array(
            'all' =>  $mod_strings['LBL_UPDATE_ALL'],
            'this' => $mod_strings['LBL_UPDATE_THIS'],
		);
		
		if ($row_result->getField('recurrence_of_id')) {
			$text = $mod_strings['LBL_RECUR_EDIT_DESCR'];
		} else {
			$text = $mod_strings['LBL_RECUR_EDIT_FIRTS_DESCR'];
		}

        $spec = array('name' => 'break_sequence', 'options' => $options, 'options_add_blank' => false);

        $body =<<<EOQ
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm flashMessage" align="center">
            <tr>
                <td class="dataLabel">{$text}</td>
			</tr>
EOQ;
		if ($row_result->getField('recurrence_of_id')) {
			$body .= <<<EOQ
            <tr>
                <td class="dataLabel">{$gen->form_obj->renderSelect($spec, 'all')}</td>
            </tr>
            </table>
EOQ;
		} else {
			$body .= '<input type="hidden" name="break_sequence" value="all" />';
		}

        return $body;
	}

    /**
     * @param string $instance_id
     * @param string $type: 'start', 'prev', 'next'
     * @return string
     */
    function getInstanceLink($instance_id, $type) {
        global $app_strings;
        $url = 'index.php?module=Meetings&action=DetailView&record=' . $instance_id;

        if ($type == 'start') {
            $label = 'LNK_LIST_START';
        } elseif($type == 'prev') {
            $label = 'LNK_LIST_PREVIOUS';
        } else {
            $label = 'LNK_LIST_NEXT';
        }

        $icon = '<div class="input-icon icon-' .$type. '" style="vertical-align: middle;"></div>';
        $link = '&nbsp;&nbsp;&nbsp;<a href="' . $url . '" class="listViewPaginationLinkS1">';


        if ($type == 'next') {
            $link .= $app_strings[$label] .'&nbsp;'. $icon;
        } else {
            $link .= $icon .'&nbsp;'. $app_strings[$label];
        }

        $link .= '</a>';

        return $link;
    }

    function loadUpdateRequest(RowUpdate &$update, array $input) {}

	function validateInput(RowUpdate &$update) {
		return true;
	}
	
	function afterUpdate(RowUpdate &$update) {}
}
?>
