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

require_once('modules/Calendar/metadata/CalendarViewerDefs.php');
require_once('modules/Calendar/ExtClassForCalendar/UserEx.php');
require_once('modules/Calendar/CalendarViewer/CalendarViewer.php');
require_once('modules/Calendar/GridEntry.php');

CalendarViewer::initRequest(!empty($_REQUEST['forDashlet']));
$view_type = $_REQUEST['view_type'];

if(isset($CalendarViewerDefs[$view_type])) {
	$def = $CalendarViewerDefs[$view_type];

	require_once($def['viewer_class_file']);
	$viewer = new $def['viewer_class']($_REQUEST);
	if(isset($def['viewer_tpl']) && !empty($def['viewer_tpl'])) {
		$viewer->templatePath = $def['viewer_tpl']; 
	}
	$viewer->forDashlet = !empty($_REQUEST['forDashlet']);
	$viewer->dashletId = array_get_default($_REQUEST, 'dashletId');
	$viewer->async = true;
	echo $viewer->execute();
	$viewer->exportIncludes();
} else {
	ACLController::displayNoAccess(true);
}

?>
