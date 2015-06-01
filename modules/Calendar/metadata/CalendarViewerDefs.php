<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/******************************************************************************
* The contents of this file are subject to the CareBrains Software End User
* License Agreement ('License') which can be viewed at
* http://www.sugarforum.jp/download/cbieula.shtml
* By installing or using this file, You have unconditionally agreed to the
* terms and conditions of the License, and You may not use this file except in
* compliance with the License.  Under the terms of the license, You shall not,
* among other things: 1) sublicense, resell, rent, lease, redistribute, assign
* or otherwise transfer Your rights to the Software, and 2) use the Software
* for timesharing or service bureau purposes such as hosting the Software for
* commercial gain and/or for the benefit of a third party.  Use of the Software
* may be subject to applicable fees and any use of the Software without first
* paying applicable fees is strictly prohibited.
* Your Warranty, Limitations of liability and Indemnity are expressly stated
* in the License.  Please refer to the License for the specific language
* governing these rights and limitations under the License.
*****************************************************************************/

$CalendarViewerDefs = array(
	'day' => array(
		'viewer_class_file' => 'modules/Calendar/CalendarViewer/DayCalendarViewer.php',
		'viewer_class' => 'DayCalendarViewer',
		'viewer_tpl' => 'modules/Calendar/CalendarViewer/DayCalendarView.tpl'
	), 
	'week' => array(
		'viewer_class_file' => 'modules/Calendar/CalendarViewer/WeekCalendarViewer.php',
		'viewer_class' => 'WeekCalendarViewer',
		'viewer_tpl' => 'modules/Calendar/CalendarViewer/WeekCalendarView.tpl'
	), 
	'month' => array(
		'viewer_class_file' => 'modules/Calendar/CalendarViewer/MonthCalendarViewer.php',
		'viewer_class' => 'MonthCalendarViewer',
		'viewer_tpl' => 'modules/Calendar/CalendarViewer/MonthCalendarView.tpl'
	),
	'year' => array(
		'viewer_class_file' => 'modules/Calendar/CalendarViewer/YearCalendarViewer.php',
		'viewer_class' => 'YearCalendarViewer',
		'viewer_tpl' => 'modules/Calendar/CalendarViewer/YearCalendarView.tpl'
	),
	'users_day' => array(
		'viewer_class_file' => 'modules/Calendar/CalendarViewer/UsersDayCalendarViewer.php',
		'viewer_class' => 'UsersDayCalendarViewer',
		'viewer_tpl' => 'modules/Calendar/CalendarViewer/UsersDayCalendarView.tpl'
	),
	'team_day' => array(
		'viewer_class_file' => 'modules/Calendar/CalendarViewer/TeamDayCalendarViewer.php',
		'viewer_class' => 'TeamDayCalendarViewer',
		'viewer_tpl' => 'modules/Calendar/CalendarViewer/TeamDayCalendarView.tpl'
	),
	'team_week' => array(
		'viewer_class_file' => 'modules/Calendar/CalendarViewer/TeamWeekCalendarViewer.php',
		'viewer_class' => 'TeamWeekCalendarViewer',
		'viewer_tpl' => 'modules/Calendar/CalendarViewer/TeamWeekCalendarView.tpl'
	),
	
	'resource_day' => array(
		'viewer_class_file' => 'modules/Calendar/CalendarViewer/ResourceDayCalendarViewer.php',
		'viewer_class' => 'ResourceDayCalendarViewer',
		'viewer_tpl' => 'modules/Calendar/CalendarViewer/ResourceDayCalendarView.tpl'
	),
	'resource_week' => array(
		'viewer_class_file' => 'modules/Calendar/CalendarViewer/ResourceWeekCalendarViewer.php',
		'viewer_class' => 'ResourceWeekCalendarViewer',
		'viewer_tpl' => 'modules/Calendar/CalendarViewer/ResourceWeekCalendarView.tpl'
	),
);