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
require_once('modules/Meetings/widgets/SchedulerWidget.php');

class MeetingDialogWidget extends FormSection {

    /**
     * @var SchedulerWidget
     */
    var $scheduler_widget;

	function init($params, $model=null) {
		parent::init($params, $model);
		if(! $this->id)
			$this->id = 'meeting_dialog';

        $this->setSchedulerWidget($params, $model);
    }

    function setSchedulerWidget($params, $model) {
        $widget = new SchedulerWidget();
        $widget->init($params, $model);
        $this->scheduler_widget = $widget;
    }

	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, $context) {
        return $this->renderHtmlEdit($gen, $row_result);
	}
	
	function getRequiredFields() {
        $fields = array('name', 'date_start', 'assigned_user', 'is_daylong',
            'is_private', 'duration', 'is_publish', 'status', 'parent', 'location',
            'description', 'direction', 'phone_number');

		return $fields;
	}
	
	function renderHtmlEdit(HtmlFormGenerator &$gen, RowResult &$row_result) {
        global $image_path;
        $module = $this->model->module_dir;
        $bean_name = $this->model->name;
        $title_img = get_image($image_path . $module, "border='0' hspace='0' vspace='0' style='margin: -2px 0'");

        $fields = $this->getRequiredFields();
        $display = array();
        
        $clabel = array(
        	'date_start' => 'LBL_DATE',
        );
        
        for ($i = 0; $i < sizeof($fields); $i++) {
            if (isset($row_result->fields[$fields[$i]])) {
            	$label = $row_result->fields[$fields[$i]]['vname'];
            	if(isset($clabel[$fields[$i]]))
            		$label = $clabel[$fields[$i]];
                $label = translate($label, $module);
                $form_field = null;

                if ($fields[$i] == 'name') {
                    $form_field = new FormField(array('width' => 55, 'len' => 50));
                } elseif ($fields[$i] == 'assigned_user') {
                    $form_field = new FormField(array('width' => 18));
                } elseif ($fields[$i] == 'description') {
                    $form_field = new FormField(array('width' => 78, 'height' => 2));
                } else if($fields[$i] == 'is_publish') {
                    $form_field = new FormField(array('show_label' => true));
                }

                $value = $gen->form_obj->renderField($row_result, $fields[$i], $form_field);
                $display[$fields[$i]] = array('label' => $label, 'value' => $value);
            }
        }

        $direction = '';
        if ($bean_name == 'Call') {
            $direction = $display['direction']['value'];
            $extra_field = 'phone_number';
        } else {
            $extra_field = 'location';
        }

        $body = <<<EOQ
	        <input type="hidden" name="edit_module" value="{$module}" />
	        <input type="hidden" name="edit_model" value="{$bean_name}" />
            <input type="hidden" name="recurrence_rules" value="" />
            <div id="dlg_title" style="display: none">{$title_img}&nbsp;{$row_result->getField('name')}</div>
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm" style="padding:5px"><tbody>
            <tr><td><table width="100%" border="0" cellspacing="1" cellpadding="1"><tbody>
                <tr>
                    <td class="dataLabel_s" align="right">{$display['name']['label']}<span class="requiredStar">*</span></td>
                    <td class="dataField_s" colspan="3">{$display['name']['value']}</td>
                </tr>
				<tr valign="top">
					<td class="dataLabel_s" align="right">{$display['date_start']['label']}</td>
					<td class="dataField_s">{$display['date_start']['value']}</td>
					<td class="dataLabel_s" valign="top" align="right">{$display['duration']['label']}</td>
					<td valign="top" class="dataField_s">{$display['duration']['value']}</td>
				</tr>
				{$this->renderSchedulerWidget($gen, $row_result)}
				<tr><td colspan="4" style="height: 10px; line-height:10px"><div style="overflow:hidden;height:0;border-top: 1px solid #BBBBBB;">&nbsp;</div></td></tr>
				<tr>
					<td class="dataLabel_s" align="right">{$display['status']['label']}</td>
					<td class="dataField_s">{$display['status']['value']}&nbsp;{$direction}</td>
					<td class="dataLabel_s" align="right" width="15%">{$display['assigned_user']['label']}<span class="requiredStar">*</span></td>
					<td class="dataField_s">{$display['assigned_user']['value']}</td>
				</tr>
				<tr>
					<td class="dataLabel_s" align="right">{$display['parent']['label']}</td>
					<td class="dataField_s">{$display['parent']['value']}</td>
					<td class="dataLabel_s" align="right">&nbsp;</td>
					<td class="dataField_s">{$display['is_publish']['value']}</td>
				</tr>
                <tr>
                    <td class="dataLabel_s" align="right" >{$display[$extra_field]['label']}</td>
                    <td class="dataField_s">{$display[$extra_field]['value']}</td>
                    <td class="dataLabel_s" align="right">&nbsp;</td>
                    <td class="dataField_s">&nbsp;</td>
                </tr>
				<tr>
					<td valign="top" class="dataLabel_s" align="right">{$display['description']['label']}</td>
					<td colspan="3" class="dataField_s">{$display['description']['value']}</td>
				</tr>
            </tbody></table></td></tr>
            </tbody></table>
EOQ;

        $layout =& $gen->getLayout();
        $layout->addScriptLiteral("var t = $('dlg_title'); if(t) MeetingsEditView.setTitle(t.innerHTML); var defaultEditModule = '".ActivityDialog::DEFAULT_MODULE."';");
        $this->addAdditionalJs($layout, $row_result);

        return $body;
	}

    function renderSchedulerWidget(HtmlFormGenerator &$gen, RowResult &$row_result) {
        global $mod_strings;

        $html = <<<EOQ
            <tr>
                <td valign="top" class="dataLabel_s" align="right">{$mod_strings['LBL_PARTICIPANTS']}</td>
                <td colspan="3" class="dataField_s" style="padding-bottom: 8px;">
                <div id="div_participants">{$this->scheduler_widget->renderHtmlEdit($gen, $row_result, true)}</div>
                </td>
            </tr>

EOQ;

        return $html;
    }

    /**
     * Add additional javascript
     *
     * @param FormLayout $layout
     * @param RowResult $row_result
     * @return void
     */
    function addAdditionalJs(FormLayout &$layout, RowResult $row_result) {
        global $app_strings, $current_user, $timedate;

        $startTime = ActivityDialog::DEFAULT_START_TIME;
        $endTime = ActivityDialog::DEFAULT_END_TIME;

        if(isset($current_user->day_begin_hour)) {
            $hrs = $current_user->day_begin_hour;
            $startTime = sprintf('%02d:%02d', floor($hrs), ($hrs - floor($hrs)) * 60);
            $hrs = $current_user->day_end_hour;
            $endTime = sprintf('%02d:%02d', floor($hrs), ($hrs - floor($hrs)) * 60);
        }

        $display_start_time = $timedate->to_display_time($startTime.':00', true, false);

        list($startHour, $startMin) = explode(":", $startTime);
        list($endHour, $endMin) = explode(":", $endTime);
        $duration_hours = $endHour - $startHour;
        $duration_minutes = $endMin - $startMin;
        $duration = ($duration_hours * 60) + $duration_minutes;
        $orig_ts = $timedate->to_display_time($row_result->getField('date_start', ''));
        $orig_duration = $row_result->getField('duration');

        $dayLongParam = "var day_start_time = '$display_start_time';
            var day_duration = '$duration'; var orig_start_time = '$orig_ts'; var orig_duration = '$orig_duration'";

        $layout->addScriptLiteral($dayLongParam, LOAD_PRIORITY_FOOT);
    }

	function loadUpdateRequest(RowUpdate &$update, array $input) {
        if (! empty($input['date_start']) && ! empty($input['duration'])) {
        	global $timedate;
            require_once('modules/Calendar/DateTimeUtil.php');

            $dt_start = $timedate->to_db($input['date_start']);
            list($date_start, $time_start) = explode(' ', $dt_start, 2);
            $date_time_start = DateTimeUtil::get_time_start($date_start, $time_start);
            $date_time_end = DateTimeUtil::get_time_end($date_time_start, 0, $input['duration']);
            $date_end = "{$date_time_end->year}-{$date_time_end->month}-{$date_time_end->day}";

            $update->set(array('date_end' => $date_end));
        }

        $upd_invites = array();
        $this->scheduler_widget->addInviteesIdsFromInput($input, $upd_invites);
        $update->setRelatedData('event_scheduler_rows', $upd_invites);
	}
	
	function validateInput(RowUpdate &$update) {
		return true;
	}
	
	function afterUpdate(RowUpdate &$update) {}
}
?>
