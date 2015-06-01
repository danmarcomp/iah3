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
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');


function booking_button(&$focus) {
	global $app_strings;
	$mod = $focus->module_dir; $id = $focus->id;
	require_once('modules/Booking/BookedHours.php');
	if(! BookedHours::can_book_related_to($mod, $id))
		return '';
	$button = <<<EOQ
		<input type="button" class="button" value="{$app_strings['LBL_BOOKING_BUTTON_LABEL']}"
			accessKey="{$app_strings['LBL_BOOKING_BUTTON_KEY']}" title="{$app_strings['LBL_BOOKING_BUTTON_TITLE']}"
			onclick="showingSlider('booking') ? showSlider('booking') : BookingEditor.init('$mod', '$id', 'slider');" />
EOQ;
	return $button;
}

function booking_editor_json() {
	global $app_strings, $current_language;
	//$book_str = return_module_language($current_language, 'Booking');
	$images = array(
		'insert' => array('name' => 'plus_inline', 'title' => $app_strings['LNK_INS']),
		'remove' => array('name' => 'delete_inline', 'title' => $app_strings['LNK_REMOVE']),
	);
	foreach($images as $idx => $img) {
		$src = isset($img['name']) ? $img['name'] : $idx;
		unset($img['name']);
		$images[$idx] = array_merge(get_image_info($src), $img);
	}
	$json = getJSONobj();
	$image_meta_json = $json->encode($images);
	
	require_once('modules/BookingCategories/BookingCategory.php');
	$catseed = new BookingCategory();
	$cats = $catseed->get_option_list('work', true, true);
	$ord = array();
	$vals = array();
	$groups = array();
	foreach($cats as $grp => $cs) {
		if(is_array($cs))
			foreach($cs as $id => $c) {
				$groups[$id] = $grp;
				$vals[$id] = $c;
			}
		else
			$vals[$grp] = $cs;
	}
	$data = array('categories_order' => array_keys($vals), 'categories' => $vals, 'categories_groups' => $groups);
	$sysdata_json = $json->encode($data);
	
	return <<<EOQ
		ImageMeta = {$image_meta_json};
		SysData = {$sysdata_json};
EOQ;
}

function booking_slider() {
	$booking_editor_json = booking_editor_json();

	global $pageInstance;
	$pageInstance->add_js_include('modules/Booking/editor.js');
	$pageInstance->add_js_literal("
		function BookingAction()
		{
			//window.location.href = url;
			showSlider('booking');
		}
		$booking_editor_json
	");
	$pageInstance->add_js_language('Booking');

	$html = <<<EOQ
		<table id="detail_slider_content_booking" width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">
		<tr>
		<td>
			<table id="booking_hours_table" cellpadding="0" cellspacing="2" width="100%" border="0">
			</table>
		</td>
		</tr>
		</table>
EOQ;

	$style="display:none; overflow: hidden; height: 1px;";
	return detail_view_slider('booking', $html, $style);
}

function booking_quick_save() {
	require_once('modules/Booking/BookedHours.php');
	global $current_user, $timedate;
	$def_rel_id = array_get_default($_REQUEST, 'related_id');
	$def_rel_type = array_get_default($_REQUEST, 'related_type');
	$now = $timedate->get_gmt_db_datetime();
	$now_date = $timedate->to_display_date($now);
	$def_date = array_get_default($_REQUEST, 'date_start', $now_date);
	$uid = $current_user->id; // current user only at the moment
	$ret = array('created' => 0, 'failed' => 0, 'ids' => array());
	if(! empty($_REQUEST['hours_data'])) {
		$json = getJSONobj();
		$data = $json->decode(from_html($_REQUEST['hours_data']));
		foreach($data['hours'] as $row) {
			$fail = false;
			$seed = new BookedHours();
			$seed->format_all_fields();
			foreach($seed->column_fields as $f)
				if($f != 'id' && isset($row[$f]))
					$seed->$f = $row[$f];
			$seed->time_start = $timedate->lax_parse_time($seed->time_start);
			if(empty($seed->related_id)) {
				$seed->related_type = $def_rel_type;
				$seed->related_id = $def_rel_id;
			}
			$seed->assigned_user_id = $uid;
			if(! $seed->init_inherited_values(true)) {
				$fail = "cannot book hours against related object";
			}
			if(empty($seed->status))
				$seed->status = 'pending';
			if(empty($seed->date_start))
				$seed->date_start = $def_date;
			foreach($seed->required_fields as $f => $reqd)
				if($reqd && empty($seed->$f)) {
					$fail = "missing $f";
					break;
				}
			if(! $seed->ACLAccess('Save')) {
				$fail = "permission denied";
			}
			if($fail) {
				$ret['failed'] ++;
				$ret['errors'][] = $fail;
				continue;
			}
			if (!empty($seed->booking_category_id)) {
				require_once 'modules/BookingCategories/BookingCategory.php';
				$cat = new BookingCategory;
				if ($cat->retrieve($seed->booking_category_id)) {
					$seed->billing_rate_usd = $cat->billing_rate_usd;
				}
			}
			if (!empty($seed->assigned_user_id)) {
				require_once 'modules/HR/Employee.php';
				$emp = new Employee;
				if ($emp->retrieve_for_user_id($seed->assigned_user_id)) {
					$seed->paid_rate_usd = $emp->std_hourly_rate_usdollar;
				}
			}
			$new_id = $seed->save();
			$ret['ids'][$row['id']] = $new_id;
			$ret['created'] ++;
		}
	}
	return $ret;
}

function booking_go_to_target_list() {
    $redirect_params = array('module' => 'Booking', 'action' => 'SelectBookingObject');
    $path = 'index.php?' . encode_query_string($redirect_params);
    header('Location: '.$path);
}

function booking_show_target_list() {	
	global $app_list_strings, $mod_strings;
	global $theme;
	require_once("themes/$theme/layout_utils.php");

	$html = get_module_title('Booking', $mod_strings['LBL_MODULE_NAME'].': '.$mod_strings['LBL_CREATE_NEW_TITLE'], true);
	
	$hours = new BookedHours();
	$classes = $hours->bookable_classes['work'];

	$classes['none'] = $mod_strings['LBL_RELATE_TO_NONE'];

    $html .= '<ul>';
    foreach($classes as $mod => $_cls) {
        $title = array_get_default($app_list_strings['moduleList'], $mod, $_cls);
        $lnk = "<a href=\"index.php?module=Booking&action=EditView&related_type=$mod\">$title</a>";
        $html .= "<li>$lnk</li>";
    }
    $html .= '</ul>';

    echo $html;
}

?>
