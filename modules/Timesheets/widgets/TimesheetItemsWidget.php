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

class TimesheetItemsWidget extends FormTableSection {
	var $timesheetRows;
	var $hourObjs;

	function init($params, $model=null) {
		parent::init($params, $model);
		if(! $this->id)
			$this->id = 'expense_items';
	}

    function getRequiredFields() {
        return array();
    }

	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, array $context) {
		if($gen->getLayout()->getEditingLayout()) {
			$params = array();
			return $this->renderOuterTable($gen, $parents, $context, $params);		
		}

        global $mod_strings;
		$lstyle = $gen->getLayout()->getType();
		$allow_edit = 0;
		if ($lstyle == 'editview') $allow_edit = 1;
		if (!(($ts_status = $row_result->getField('status')) == 'submitted' || $ts_status == 'approved')) {
			if ($allow_edit) $allow_edit = 2;
		}
		$tbl_class = $allow_edit ? 'tabForm' : 'tabDetailView';
		$lbl_class = $allow_edit ? 'dataLabel' : 'tabDetailViewDL';
		$td_class = $allow_edit ? 'dataField' : 'tabDetailViewDF';

        $this->addJs($row_result, $gen->getFormName(), $allow_edit);
		
		$title = to_html($this->getLabel());
        $body = <<<EOQ
<table width="100%" border="0" cellspacing="0" cellpadding="0" class="{$tbl_class}" style="margin-top: 0.5em"">
<tbody>
<tr>
	<th class="{$lbl_class}" colspan="6"><h4 class="{$lbl_class}">{$title}</h4></th>
</tr>
EOQ;

		$lbl_prev = $mod_strings['LBL_PREVIOUS_PERIOD'];
		$lbl_next = $mod_strings['LBL_NEXT_PERIOD'];
		if ($allow_edit == 2 && $row_result->new_record)  $body .= <<<EOQ
<tr>
	<td style="text-align:left" colspan="3" class="{$lbl_class}">
		<button type="button" class="form-button"  onclick="TimesheetEditor.change_period(-1); return false;"><span class="input-label">$lbl_prev</span></button>
	</td>
	<td style="text-align:right" colspan="3" class="{$lbl_class}">
		<button type="button" class="form-button"  onclick="TimesheetEditor.change_period(1); return false;"><span class="input-label">$lbl_next</span></button>
	</td>
</tr>
EOQ;

		$lbl_app_sel = $mod_strings['LBL_APPROVE_SOME_BUTTON_LABEL'];
		$lbl_app_all = $mod_strings['LBL_APPROVE_ALL_BUTTON_LABEL'];
		$lbl_rej_all = $mod_strings['LBL_REJECT_ALL_BUTTON_LABEL'];

        $body .= <<<EOQ
<tr id="approve_buttons" style="display:none"><td colspan="6" class="{$lbl_class}">
<button class="form-button" onclick="return TimesheetEditor.approveSelected();"><span class="input-label">$lbl_app_sel</span></button>
<button class="form-button" onclick="return TimesheetEditor.approveAll();"><span class="input-label">$lbl_app_all</span></button>
<button class="form-button" onclick="return TimesheetEditor.rejectAll();"><span class="input-label">$lbl_rej_all</span></button>
</td></tr>
EOQ;
        $name = translate('LBL_SUBJECT', 'Booking');
        $quantity = translate('LBL_QUANTITY', 'Booking');
        $related_to = translate('LBL_RELATED_TO', 'Booking');
        $status = translate('LBL_STATUS', 'Booking');
        $empty_cell = '';

        if ($allow_edit == 2)
            $empty_cell = '<td class="{$lbl_class}" width="10%">&nbsp;</td>';

		$body .= <<<EOQ
			<tr>
				<td class="{$lbl_class}" width="10%">&nbsp;</td>
				<td class="{$lbl_class}" style="text-align:left" width="10%"><span style="font-weight: bold;">{$quantity}</span></td>
				<td class="{$lbl_class}" style="text-align:left" width="30%"><span style="font-weight: bold;">{$name}</span></td>
				<td class="{$lbl_class}" style="text-align:left" width="30%"><span style="font-weight: bold;">{$related_to}</span></td>
				<td class="{$lbl_class}" style="text-align:left" width="10%"><span style="font-weight: bold;">{$status}</span></td>
				{$empty_cell}
			</tr>
		</tbody>
		<tbody id="hours_tbody">
		</tbody>
	</table>
EOQ;
		if ($allow_edit == 2) $body .= <<<EOQ
<div id="extra_hours_div" style="display: none; margin-top: 0.5em">
<table width="100%" border="0" cellspacing="0" cellpadding="0" class="{$tbl_class}">
<tbody>
<tr>
<th class="{$lbl_class}"><h4 class="{$lbl_class}">{$mod_strings['LBL_EXTRA_BOOKED_HOURS']}</h4></th>
</tr>
</tbody>
<tbody id="extra_hours_tbody">
</tbody>
</table>
</div>

EOQ;

        return $body;
	}

    function addJs($timesheet, $form_name, $edit = 0) {
        global $pageInstance, $timedate, $mod_strings;

        $pageInstance->add_js_literal('
            SysData = {
                taxrates: {},
                taxrates_order: [],
                taxcodes: {},
                taxcodes_order: [],
                discounts: {},
                discounts_order: [],
                get_tax_rates: function(clsid) {
                    var ret = [];
                    if(isset(this.taxcodes[clsid]))
                        ret = this.taxcodes[clsid].rates;
                    return ret;
                }
            };', null, LOAD_PRIORITY_HEAD);

        $user_dateformat = '('. $timedate->get_user_date_format().')';
        $calendar_dateformat =  $timedate->get_cal_date_format();
        $images_meta = $this->getImagesMeta();

        $editorSetupJs = <<<EOS
            ImageMeta = $images_meta;
            USER_DATEFORMAT = '$user_dateformat';
            CALENDAR_DATEFORMAT = '$calendar_dateformat';
EOS;

		$editorSetupJs .= "SUGAR.ui.registerInput('$form_name', TimesheetEditor);";
        $editorSetupJs .= $this->getItemsJs($timesheet, $edit);
    

        $pageInstance->add_js_literal($editorSetupJs, null, LOAD_PRIORITY_FOOT);
    }

    function getImagesMeta() {
        global $app_strings, $mod_strings;

        $images = array(
            'insert' => array('name' => 'plus_inline', 'title' => $app_strings['LNK_INS']),
            'remove' => array('name' => 'delete_inline', 'title' => $app_strings['LNK_REMOVE']),
            'plus' => array('name' => 'plus_inline'),
            'tree_dots' => array('name' => 'treeDots'),
            'tree_branch' => array('name' => 'treeDotsBranch'),
            'tree_end' => array('name' => 'treeDotsEnd'),
            'calendar' => array('name' => 'jscalendar'),
            'blank' => array('name' => 'blank', 'width' => 12, 'height' => 12),
            'Cases' => array(),
            'Project' => array(),
            'pdf' => array('name' => 'pdf_image_inline',),
        );

        foreach($images as $idx => $img) {
            $src = isset($img['name']) ? $img['name'] : $idx;
            unset($img['name']);
            $images[$idx] = array_merge(get_image_info($src), $img);
        }

        $json = getJSONObj();
        $images_meta = $json->encode($images);

        return $images_meta;
    }
	
	function js_format_date($d) {
		if (empty($d)) $d = date('Y-m-d');
		list($y,$m,$d) = explode('-', $d, 3);
		$d = ltrim($d, '0');
		$m = ltrim($m, '0');
		$y = ltrim($y, '0');
		$m --;
		return "new Date($y, $m, $d)";
	}

    function getItemsJs(RowResult $timesheet, $edit = 0) {
		$json = getJSONObj();

		$dt_start = $timesheet->getField('date_starting');
		$dt_end = $timesheet->getField('date_ending');
		$date_start_js = $this->js_format_date($dt_start);
		$date_end_js = $this->js_format_date($dt_end);

		$ts_status = $timesheet->getField('status');
		$ts_period = $timesheet->getField('timesheet_period');
		$allow_edit = ($edit == 2) ? 'true' : 'false';

		if (! empty($this->timesheetRows)) {
			$hours_data = $json->encode($this->timesheetRows);
		} else {
			$arr = $this->getHours($timesheet);
			$hours_data = $json->encode($arr);
		}
		$ts_fields = array(
			'status' => $ts_status,
		);
		$canApprove = false;
		if (!$edit) {
			if($ts_status == 'submitted' || $ts_status == 'rejected')
				$canApprove = canApprove(AppConfig::current_user_id(), $timesheet);
		}
		$ts_fields['can_approve'] = $canApprove;
		$ts_fields = $json->encode($ts_fields);

		$js = <<<JS
var init_hours = $hours_data;
var ts_period = {$ts_period};
var dt_start = {$date_start_js};
var dt_end = {$date_end_js};
TimesheetEditor.init($allow_edit, ts_period, dt_start, dt_end, init_hours, $ts_fields);
JS;
        return $js;
    }

    function getHours(RowResult $timesheet) {
        require_bean('BookedHours');
        $hours = new BookedHours();
        $ts_id = $timesheet->getField('id');
        $data = $hours->query_hours(false, $ts_id ? $ts_id : 'none', $timesheet->getField('assigned_user_id'), '', $timesheet->getField('date_starting'), $timesheet->getField('date_ending'), true);
        $order = array_keys($data);

        if($ts_id) {
            $extra = $hours->query_hours(false, 'none', $timesheet->getField('assigned_user_id'), '', $timesheet->getField('date_starting'), $timesheet->getField('date_ending'), true);
            array_extend($data, $extra);
            $extra = array_keys($extra);
        } else {
            $extra = array();
        }

        return  array('order' => $order, 'hours' => $data, 'extra' => $extra);
    }

    function getCategories() {
        require_once('modules/BookingCategories/BookingCategory.php');
        $seedcat = new BookingCategory();
        $cats = $seedcat->get_list("", "booking_class='expenses'");
        $categories = array();
        foreach ($cats['list'] as $cat) {
            $categories[$cat->id] = array(
                'name' => from_html($cat->name),
                'expenses_unit' => $cat->expenses_unit,
                'paid_rate_usd' => $cat->paid_rate_usd
            );
        }

        return $categories;
    }

	function loadUpdateRequest(RowUpdate &$update, array $input) {
		if (isset($input['approve_action'])) {
			$this->approveAction($update, $input);
			return;
		}
        $upd_items = array();
        if (!empty($input['line_items'])) {
            $json = getJSONObj();
            $upd_items = $json->decode(from_html($input['line_items']));
        }
		$update->setRelatedData($this->id.'_rows', $upd_items);
		$update->setRelatedData($this->id.'_hours_updated', array_get_default($input, 'hours_data_updated'));
		$update->setRelatedData($this->id . '_submit_status', array_get_default($input, 'submit_timesheet'));
		$this->timesheetRows = $upd_items;
		$this->updateTotalHours($upd_items, $update, $input);
	}

	function updateTotalHours($hours, RowUpdate $update, array $input) {
		$this->hourObjs = array();
		if (!is_array($hours) || empty($hours)) return;
		if (!array_get_default($input, 'hours_data_updated')) return;
		$total_hours = 0;
		$submit_status = $update->getRelatedData($this->id.'_submit_status');
		require_bean('BookedHours');
		foreach($hours['order'] as $id) {
			$booked = new BookedHours();
			if($booked->retrieve($id)) {
				if($submit_status && $booked->status != 'approved') {
					if($submit_status == 1) $booked->status = 'submitted';
					else if($submit_status == 2) $booked->status = 'pending';
				}
				$this->hourObjs[] = $booked;
				$total_hours += ($booked->quantity / 60);
			}
		}
		$upd = array(
			'total_hours' => $total_hours
		);
		$submit_status = $update->getRelatedData($this->id.'_submit_status');
		if ($submit_status == 1) $upd['status'] = 'submitted';
		if ($submit_status == 2) $upd['status'] = 'draft';
		$update->set($upd);
	}
	
	function validateInput(RowUpdate &$update) {
		$id = $update->getField('id');
		$user_id = $update->getField('assigned_user_id');
		$date_starting = $update->getField('date_starting');
		$date_ending = $update->getField('date_ending');
		require_bean('Timesheet');
		$ts = new Timesheet;
		if (!$ts->check_date_range($id, $user_id, $date_starting, $date_ending)) {
            throw new IAHError(translate('MSG_DUPLICATE_PERIOD', 'Timesheets'));
		}
	}
	
	function afterUpdate(RowUpdate &$update) {
		$row_updates = $update->getRelatedData($this->id.'_rows');
		$hours_updated = $update->getRelatedData($this->id.'_hours_updated');
		if(! $hours_updated)
			return;
		
        if (!$update->new_record) {
		    $ts_id = $update->getField('id', null, true);
        } else {
            $ts_id = $update->saved['id'];
        }
		if(is_array($row_updates)) {
			if(isset($row_updates['extra']) && !$update->new_record)
			foreach($row_updates['extra'] as $id) {
				$booked = new BookedHours();
				if($booked->retrieve($id) && $booked->timesheet_id == $ts_id) {
					if($booked->status == 'submitted')
						$booked->status = 'pending';
					$booked->timesheet_id = '';
					$booked->save();
				}
				$booked->cleanup();
			}
		}
		
		foreach($this->hourObjs as $booked) {
			$booked->timesheet_id = $ts_id;
			$booked->save();
			$booked->cleanup();
		}

		$submit_status = $update->getRelatedData($this->id.'_submit_status');
		if($ts_id && $submit_status == '2') {
			$lq = new ListQuery('BookedHours');
			$lq->addSimpleFilter('status', 'submitted', '=');
			$lq->addSimpleFilter('timesheet_id', $ts_id, '=');
			$lr = $lq->runQuery();
			foreach ($lr->getRowIndexes() as $idx) {
				$rr = $lr->getRowResult($idx);
				$ru = new RowUpdate($rr);
				$ru->set(array('status' => 'pending'));
				$ru->save();
			}
		}
	}
	
	function approveAction(RowUpdate &$update, array $input) {
		$ts_id = $update->getField('id');
		$ids = null;
		$status = 'approved';
		switch ($input['approve_action']) {
			case 'approveSelected':
				$ids = array_get_default($input, 'approve_hours', array());
				break;
			case 'rejectAll':
				$status = 'rejected';
				break;
			case 'approveAll':
				break;
			default:
				return;
		}
		$lq = new ListQuery('BookedHours');
		$lq->addSimpleFilter('timesheet_id', $ts_id, '=');
		$lr = $lq->runQuery();
		$ts_status = $status;
		foreach ($lr->getRowIndexes() as $idx) {
			$rr = $lr->getRowResult($idx);
			if (is_null($ids)) {
				$do_update = true;
			} else {
				$hoursStatus = $rr->getField('status');
				$do_update = in_array($rr->getField('id'), $ids) && $hoursStatus != $status;
				$newStatus = $do_update ? $status : $hoursStatus;
				if ($newStatus == 'rejected') $ts_status = 'rejected';
			}
			if ($do_update) {
				$ru = new RowUpdate($rr);
				$ru->set(array('status' => $status));
				$ru->save();
			}
		}
		require_bean('Timesheet');
		$bean = new Timesheet;
		if ($bean->retrieve($ts_id)) {
			if ($bean->status != $ts_status) {
				$bean->status = $ts_status;
				$bean->save();
			}
		}
	}

    function renderPdf(PdfFormGenerator &$gen, RowResult &$row_result, array $parents, array $context) {
        global $mod_strings, $timedate, $app_list_strings;

        $booked_hours = $this->getHours($row_result);

        if (! empty($booked_hours['hours'])) {
            $data = array();
            $cols = array(
                'date' => array('width' => '20%', 'title' => ''),
                'hours' => array('width' => '10%', 'title' => translate('LBL_QUANTITY', 'Booking')),
                'description' => array('width' => '25%', 'title' => translate('LBL_SUBJECT', 'Booking')),
                'related_name' => array('width' => '30%', 'title' => translate('LBL_RELATED_TO', 'Booking')),
                'status' => array('width' => '15%', 'title' => translate('LBL_STATUS', 'Booking')),
            );

            foreach ($booked_hours['hours'] as $line) {
                $line_time = strtotime($line['date_start']);
                $line_time = custom_strftime($mod_strings['LBL_LINE_DATE_FORMAT'], $line_time);

                $data[] = array(
                    'date' => $line_time,
                    'hours' => '',
                    'description' => '',
                    'related_name' => '',
                    'status' => ''
                );

                $related_name = '';
                if ($line['related_name'] && $line['related_type']) {
                    $related_name = $app_list_strings['moduleListSingular'][$line['related_type']];
                    $related_name .= ': ';
                    $related_name .= $line['related_name'];
                }

                $minutes = (int)($line['quantity']);
                $start_time =  $timedate->to_display_time($line['date_start'], false, false);
                $quantity = ($minutes > 0) ? ($minutes / 60) : 0;

                $data[] = array(
                    'date' => $start_time,
                    'hours' => sprintf($mod_strings['LBL_DETAIL_PDF_NUM_HOURS'], $quantity),
                    'description' => $line['name'],
                    'related_name' => $related_name,
                    'status' => $app_list_strings['booked_hours_status_dom'][$line['status']]

                );

            }

            $opts = array(
                'border' => 0,
                'header' => array(
                    'border' => 0,
                ),
            );

            $gen->pdf->DrawTable($data, $cols, $mod_strings['LBL_TIMESHEET_BOOKED_HOURS'], true, $opts);
        }
    }
}
?>
