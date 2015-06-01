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


	require_once ("include/pdf/PDFManager.php");

	require_once('modules/Tasks/Task.php');

	require_once('modules/Calendar/CalendarDataUtil.php');
	require_once('modules/Calendar/CalendarDateTime.php');
	$params = array(
		'display_meetings',
		'display_calls',
		'display_project_tasks',
		'display_events',
		'display_tasks',
	);
	foreach ($params as $param) {
		CalendarDataUtil::param($param, true);
	}
	CalendarDataUtil::param('add_contacts', true);
	
	global $db;
	global $mod_strings;
	global $current_user, $current_language;
	$task_strings = return_module_language($current_language, 'Tasks');
	
	$today=gmdate("Y-m-d H:i:s");
	$today = $timedate->handle_offset($today, "Y-m-d H:i:s");
	list($today_date) = explode(' ', $today);

	$display_date = array_get_default($_REQUEST, 'display_date', '');
	if(! preg_match('/\d{4}-\d{1,2}-\d{1,2}/', $display_date))
		$display_date = $today_date;

	$tasks_start = $timedate->handle_offset($display_date . ' 00:00:00', "Y-m-d H:i:s", false);
	$tasks_end = $timedate->handle_offset($display_date . ' 23:59:59', "Y-m-d H:i:s", false);

	$user_id = $current_user->id;
	$user_full_name = from_html(trim($current_user->first_name . ' ' . $current_user->last_name));

	$tasks_query="SELECT tasks.name, tasks.priority, tasks.date_due as my_date ".
		"FROM tasks WHERE tasks.assigned_user_id='$user_id' AND tasks.status<>'Completed' AND tasks.status<>'Deferred' ".
		//"AND (tasks.date_start IS NULL OR NOT tasks.date_start OR tasks.date_start BETWEEN '$tasks_start' AND '$tasks_end' ) ".
		"AND (tasks.date_start IS NULL OR NOT tasks.date_start OR tasks.date_start <= '$tasks_start' ) ".
		"AND NOT tasks.deleted ".
		"ORDER BY tasks.priority DESC, (tasks.date_due IS NULL OR tasks.date_due = 0), tasks.date_due DESC ";

	$tasks_result = $db->query($tasks_query);	
	$tasks = array();
	$i=0;
	while($tasks_rows = $db->fetchByAssoc($tasks_result, -1, false)){
		$tasks[$i]['name'] = $tasks_rows['name'];
		$tasks[$i]['priority'] = $app_list_strings['task_priority_dom'][$tasks_rows['priority']];
		$tasks[$i]['my_date'] = $task_strings['LBL_DATE_DUE_FLAG'];
		if($tasks_rows['my_date'] != ''){
			$tasks[$i]['my_date'] = $timedate->swap_formats($timedate->handle_offset($tasks_rows['my_date'], "Y-m-d H:i:s"), 'Y-m-d H:i:s', $timedate->get_date_time_format());
		}
		$i++;
	}

	// Getting info from object
	$final_array = array();
	$ts = strtotime($display_date);
	$calDateTime = new CalendarDateTime($display_date, true, CAL_BASE_LOCAL, true);
	$activities = CalendarDataUtil::getActivitiesOnDay($calDateTime, 'user', array($current_user->id));
	foreach($activities as $k => $object){
		if ($k === 'summary') continue;
		if(!empty($object['id'])) {
			$final_array[$object['id']]['id']=$object['id'];
			$final_array[$object['id']]['name']=from_html($object['subject']);
			$final_array[$object['id']]['time_start']= substr($object['startDateTime'], 11, 8) ;
			$final_array[$object['id']]['end_time'] = substr($object['endDateTime'], 11, 8);
			$final_array[$object['id']]['module_dir']=$object['module'];
			$final_array[$object['id']]['is_daylong']=@$object['is_daylong'];
			$final_array[$object['id']]['contacts'] = $object['contacts'];
		}
	}
	foreach($final_array as $my_calendar){
		$hour=substr($my_calendar['time_start'],0,2).":00";
		$calendar_array[$hour][$my_calendar['id']]=$my_calendar;
	}


	$allday = array();
	for($i = 0; $i < 24; $i++) {
		$current_index = sprintf('%02d:00', $i);
		$hr = $timedate->to_display_time($current_index.':00', true, false);
		$lines = array();
		if(isset($calendar_array[$current_index])){
			foreach($calendar_array[$current_index] as $event){
				$typename = $app_list_strings['moduleListSingular'][$event['module_dir']];
				$hour = substr($event['time_start'], 0, 2);
				$minute = substr($event['time_start'], 3, 2);
				$tstart = sprintf('%02d:%02d:00', $hour, $minute);
				$tend = $event['end_time'] . ':00';
				$t = $timedate->to_display_time($tstart, true, false);
				if (!$event['is_daylong']) {
					if ($event['module_dir'] == 'Tasks') {
						$text = $t . '  ' . $typename . ': ' . $event['name'];
					} else {
						$t .= '-'. $timedate->to_display_time($tend, true, false);
						$text = $t . '  ' . $typename . ': ' . $event['name'];
					}
				} else {
					$text = $typename . ': ' . $event['name'];
				}
				if(count($event['contacts'])) {
					$text .= $mod_strings['LBL_PDF_EVENT_WITH'];
					$x = 0;
					foreach($event['contacts'] as $cid => $contact) {
						if ($cid == $current_user->id) continue;
						$text .= ($x == 0 ? '' : ', ');
						$text .= $contact['name'];
						$x++;
					}
				}
				if ($event['is_daylong']) {
					$allday[] = array('text' => $text);
				} else {
					$lines[] = $text;
				}
			}
		}
		$data[$current_index]['idx'] = $i;
		$data[$current_index]['hour'] = $hr;
		$text = implode("\n\n", $lines);
		$data[$current_index]['compromises'] = $text;
	}

	$startHour = $current_user->day_begin_hour;
	$endHour = $current_user->day_end_hour;
	if(empty($startHour) && empty($endHour)) {
		$startHour = 9;
		$endHour = 18;
	}

	foreach ($data as $k => $v) {
		if ($v['idx'] >= $startHour && $v['idx'] <= $endHour) {
			continue;
		}
		if (empty($v['compromises'])) {
			unset($data[$k]);
		}
	}

	$fname = filename_safe_string($display_date . ' ' . $user_full_name.'.pdf');
	$pdf = new PDFManager($fname);
	
	$margins = $pdf->getMargins();

	//ob_clean();
		
	//initialize document
	$pdf->new_page();

	$header_style = array(
		'background-color' => array(200,200,200),
		'border-color' => array(0,0,0),
		'border' => 1,
	);
	$cols = array (
		'text' => array(
			'title' => $mod_strings['LBL_PDF_ALLDAY_EVENTS'],
			'width' => '46%',
		),
	);
	$opts = array(
		'padding' => 8,
		'font-size' => 8,
	);

	$topY = $pdf->getY();

	$image = $pdf->get_company_logo_info();
	$pdf->Image($image['path'], $margins['left'], $topY + $pdf->lv('2mm'), $pdf->lv('40mm'));

	$topY = $pdf->getY() + $pdf->lv('20mm');

	$pdf->setY($topY);


	if (!empty($allday)) {
		$pdf->DrawTable($allday, $cols, '', $header_style, $opts);
		$pdf->setY($pdf->getY() + $pdf->lv('8mm'));
	}

	$cols = array (
		'hour' => array(
			'title' => $mod_strings['LBL_PDF_LIST_TIME'],
			'width' => '8%',
		),
		'compromises' => array(
			'title' => $mod_strings['LBL_PDF_LIST_EVENTS'],
			'width' => '38%',
		),
	);
	$pdf->DrawTable($data, $cols, '', $header_style, $opts);
	
	$cols = array (
		'name' => array(
			'title' => $mod_strings['LBL_PDF_LIST_TASK'],
			'width' => '52%',
		),
		'priority' => array(
			'title' => $mod_strings['LBL_PDF_LIST_PRIORITY'],
			'width' => '19%',
		),
		'my_date' => array(
			'title' => $mod_strings['LBL_PDF_LIST_DUE_DATE'],
			'width' => '28.6%',
		),
	);
	$opts = array(
		'padding' => 6,
		'font-size' => 9,
	);
	
	$date_string = custom_strftime('%x', $calDateTime->localDateTime_t);
	$title = array(
		'font-size' => 15,
		'line-height' => ($topY - $margins['top']) / 3,
		'padding' => 0,
		'text' => $user_full_name . "\n" . $date_string . "\n ",
		'repeat' => false,
	);
	
	$pdf->setXY($pdf->lv('-50%'), $margins['top']);
	$pdf->DrawTable($tasks, $cols, $title, $header_style, $opts);

	$pdf->serve_dynamic(true);

?>
