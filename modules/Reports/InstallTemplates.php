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

global $current_user;
if(!is_admin($current_user))
	sugar_die("You are not an administrator.");

require_once('modules/Reports/ReportTemplate.php');

function print_result(array $rows, $title) {
	if(! $rows) return;
	$content = implode("\n", $rows);
	pr2($content, $title);
}

$status = ReportTemplate::scan_template_dir();
print_result($status['added'], 'Installed Report Templates');
print_result($status['updated'], 'Updated Report Templates');
print_result($status['invalid'], 'Invalid Report Templates');

require_once('modules/Reports/Report.php');
$created = array();
$errored = array();
$idx = 0;
foreach(ReportTemplate::all_by_filename(true) as $tpl) {
	$idx ++;
	try {
		$report = Report::load_template($tpl, true);
	} catch(IAHInternalError $e) {
		$errored[] = $tpl->name . ': ' . $e->getMessage();
		continue;
	}
	if($report->save())
		$created[] = $report->getField('name');
	else
		$errored[] = $tpl->name . ': ' . 'Error saving report';
}
print_result($created, 'Created Reports');
print_result($errored, 'Errors');


/*
// fix up reports which may have been created with the wrong ID
$query = "SELECT reports.id, tpl.chart_name FROM reports, reports_templates tpl "
		."WHERE tpl.id=reports.from_template_id AND NOT reports.deleted";
$result = $tpl->db->query($query, true, "Error querying reports");
$changes = array();
while($row = $tpl->db->fetchByAssoc($result)) {
	$id = md5(strtolower(str_replace(' ', '_',$row['chart_name'])));
	if($id != $row['id'])
		$changes[$row['id']] = $id;
}

foreach($changes as $oldid => $newid) {
	$query = array();
	$query[] = "UPDATE reports SET id='$newid' WHERE id='$oldid'";
	$query[] = "UPDATE reports_data SET report_id='$newid' WHERE report_id='$oldid'";
	$query[] = "UPDATE reports_users SET report_id='$newid' WHERE report_id='$oldid'";
	$query[] = "UPDATE tracker SET item_id='$newid' WHERE item_id='$oldid' AND module_name='Reports'";
	foreach($query as $q)
		$tpl->db->query($q, true);
}
*/

?>