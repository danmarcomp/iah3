<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/*********************************************************************************
 * The contents of this file are subject to the SugarCRM Enterprise Subscription
 * Agreement ("License") which can be viewed at
 * http://www.sugarcrm.com/crm/products/sugar-enterprise-eula.html
 * By installing or using this file, You have unconditionally agreed to the
 * terms and conditions of the License, and You may not use this file except in
 * compliance with the License.  Under the terms of the license, You shall not,
 * among other things: 1) sublicense, resell, rent, lease, redistribute, assign
 * or otherwise transfer Your rights to the Software, and 2) use the Software
 * for timesharing or service bureau purposes such as hosting the Software for
 * commercial gain and/or for the benefit of a third party.  Use of the Software
 * may be subject to applicable fees and any use of the Software without first
 * paying applicable fees is strictly prohibited.  You do not have the right to
 * remove SugarCRM copyrights from the source code or user interface.
 *
 * All copies of the Covered Code must include on each user interface screen:
 *  (i) the "Powered by SugarCRM" logo and
 *  (ii) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.  See full license for
 * requirements.
 *
 * Your Warranty, Limitations of liability and Indemnity are expressly stated
 * in the License.  Please refer to the License for the specific language
 * governing these rights and limitations under the License.  Portions created
 * by SugarCRM are Copyright (C) 2004-2007 SugarCRM, Inc.; All Rights Reserved.
 ********************************************************************************/

global $mod_strings, $current_language;
require_once('modules/Calendar/ExtClassForCalendar/UserEx.php');
require_once('modules/Calendar/CalendarDataUtil.php');
require_once('modules/Calendar/CalendarViewer/CalendarViewer.php');
require_once('modules/Calendar/GridEntry.php');

//setlocale(LC_TIME ,$current_language);

echo get_module_title('Calendar', $mod_strings['LBL_MODULE_TITLE'], true);

CalendarViewer::initRequest();
$view_type = $_REQUEST['view_type'];

require_once('modules/Calendar/metadata/CalendarViewerDefs.php');
if(isset($CalendarViewerDefs[$view_type])) {
	$def = $CalendarViewerDefs[$view_type];
	require_once($def['viewer_class_file']);
	$viewer = new $def['viewer_class']($_REQUEST);
	if(isset($def['viewer_tpl']) && !empty($def['viewer_tpl'])) {
		$viewer->templatePath = $def['viewer_tpl']; 
	}
	if(AppConfig::setting('site.allow_debug') && ! empty($_REQUEST['debug']))
		$viewer->debug = true;
	echo $viewer->execute();
	$viewer->exportIncludes();
} else {
	ACLController::displayNoAccess(true);
}

?>