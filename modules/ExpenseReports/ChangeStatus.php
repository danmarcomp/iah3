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
require_once 'modules/ExpenseReports/ExpenseReport.php';

global $timedate, $current_user;
$return = array('perform', array('module' => 'ExpenseReports', 'record_perform' => 'listview'));

$action = null;
if (isset($_POST['status_action']))
    $action = $_POST['status_action'];
$record = null;
if (isset($_POST['record']))
    $record = $_POST['record'];

if ($record != null) {
    $report = ListQuery::quick_fetch('ExpenseReport', $record);

	if ($report != null) {
        $date = gmdate('Y-m-d H:i:s');
        $date = $timedate->to_display_date_time($date);
    
        $upd = RowUpdate::for_result($report);
        $new_data = array();

		if (ExpenseReport::process_status_action($upd, $action)) {
			unset($_POST['status_action']);
			$upd->save();
		}
        $return = array('perform', array('module' => 'ExpenseReports', 'action' => 'DetailView', 'record' => $record, 'record_perform' => 'view'));
	}
}

return $return;
