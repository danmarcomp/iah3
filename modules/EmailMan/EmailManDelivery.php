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

if (! defined('inScheduler')) {
    $camid = array_get_default($_REQUEST, 'campaign_id');
    if($camid) {
        global $current_user;
        if($camid) {
            require_bean('Campaign');
            $ci = new Campaign();
            if(! $ci->retrieve($camid) || ! $ci->ACLAccess('edit'))
			    ACLController::displayNoAccess(true);
        } elseif (! is_admin($current_user)) {
			ACLController::displayNoAccess(true);
        }
	}
}

require_once('modules/EmailMan/EmailDelivery.php');

$campaign_id = null;
if (! empty($_REQUEST['campaign_id']))
	$campaign_id = $_REQUEST['campaign_id'];

$mode = null;
if (isset($_REQUEST['mode']))
	$mode = $_REQUEST['mode'];

$send_all=false;
if (isset($_REQUEST['send_all']) && $_REQUEST['send_all'] == true)
	$send_all= true;

$delivery = new EmailDelivery($campaign_id, $mode, $send_all);
$delivery->process();
$delivery->redirectAfter(defined('inScheduler'), $_REQUEST);
?>
