<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/*********************************************************************************
 * The contents of this file are subject to the SugarCRM Public License Version
 * 1.1.3 ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied.  See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * All copies of the Covered Code must include on each user interface screen:
 *    (i) the "Powered by SugarCRM" logo and
 *    (ii) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.  See full license for
 * requirements.
 *
 * The Original Code is: SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) 2004-2006 SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 ********************************************************************************/
/*********************************************************************************

 * Description:  TODO: To be written.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/


require_once('include/JSON.php');
require_once('modules/Users/Forms.php');
require_once('XTemplate/xtpl.php');

global $app_strings;
global $app_list_strings;
global $mod_strings;
global $timedate;

theme_hide_side_menu(true, true);

if(!empty($_REQUEST['userOffset'])) { // ajax call to lookup timezone
    echo 'userTimezone = "' . $timedate->guessTimeZone($_REQUEST['userOffset']*60) . '";';
    die();
}
$xtpl = new XTemplate('modules/Users/SetTimezone.html');
$xtpl->assign('MOD', $mod_strings);
$xtpl->assign('APP', $app_strings);


$selectedZone = $current_user->getPreference('timezone');
if(empty($selectedZone) && !empty($_REQUEST['gmto'])) {
	$selectedZone = $timedate->guessTimeZone(-60 * $_REQUEST['gmto']);
}

// longreach - start added
if(empty($selectedZone)) {
	$selectedZone = AppConfig::system_timezone();
}
// longreach - end added

$zones = $timedate->getTimeZones(true, true);
$timezoneOptions = get_select_options_with_id($zones, $selectedZone);

$xtpl->assign('TIMEZONEOPTIONS', $timezoneOptions);
$xtpl->parse('main');
$xtpl->out('main');
?>
