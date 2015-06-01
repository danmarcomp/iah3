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

action_restricted_for('demo');

if(!is_admin($current_user)) sugar_die("Unauthorized access to administration.");

global $db;

echo "<hr><b>Calls</b><br><br>";

$callQuery = "UPDATE calls SET date_end = date_start WHERE (date_end < date_start || date_end IS NULL) AND deleted=0";
$result = $db->query($callQuery);
echo $db->getAffectedRowCount() . ' calls updated';

echo "<hr><b>Meetings</b><br><br>";
$meetingQuery = "UPDATE meetings SET date_end = date_start WHERE (date_end < date_start || date_end IS NULL) AND deleted=0";
$result = $db->query($meetingQuery);
echo $db->getAffectedRowCount() . ' meetings updated';

echo "<hr><br>".$mod_strings['LBL_DIAGNOSTIC_DONE'];

?>
