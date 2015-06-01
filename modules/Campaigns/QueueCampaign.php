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

 * Description: Schedules email for delivery. emailman table holds emails for delivery.
 * A cron job polls the emailman table and delivers emails when intended send date time is reached.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/

action_restricted_for('demo');
require_once('modules/Campaigns/EmailQueue.php');

//this is to account for the case of sending directly from summary page in wizards
$from_wiz = false;

if (isset($_REQUEST['wiz_mass'])) {
    $mass[] = $_REQUEST['wiz_mass'];
    $uids = $mass;
    $from_wiz = true;
} else {
    $uids = explode(';', $_REQUEST['mass']);
}

if (isset($_REQUEST['from_wiz']))
    $from_wiz = true;

$campaign_id = null;
if (isset($_REQUEST['record']))
    $campaign_id = $_REQUEST['record'];
$mode = null;
if (isset($_REQUEST['mode']))
	$mode = $_REQUEST['mode'];

$queue = new EmailQueue($campaign_id, $mode);
$queue->scheduling($uids);

return array('perform', $queue->getReturnData($_REQUEST, $from_wiz));

