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
if(!defined('inScheduler')) die('Unauthorized access');

require_once('modules/Reports/utils.php');
require_once('include/TimeDate.php');
require_once('include/SugarPHPMailer.php');
require_once('include/layout/NotificationManager.php');
require_once('include/ListView/ListFormatter.php');
require_once('include/ListView/ListViewExporter.php');

global $log, $db;

$mail = new SugarPHPMailer();
$mail->InitForSend(true);

$timedate = new TimeDate();

$lq = new ListQuery('Report');
$now = $timedate->get_gmt_db_datetime();

$lq->addFilterClauses(array(
	array('field' => 'run_method', 'value' => 'scheduled'),
	"(next_run IS NULL OR next_run < '$now')",
));


$reports = $lq->fetchAll('last_run asc');

foreach($reports->getRowIndexes() as $idx) {

    $report = $reports->getRowResult($idx);
    $report_id = $report->getField($report->primary_key);
	$data_id = run_report_result($report, true, true);

	if(! $data_id) {
		$log->error("Error running scheduled report: '{$report->getField('name')}' ({$report_id})");
		continue;
	}
	
    $query = 'SELECT users.id, users.user_name, users.email1 AS email, '.db_concat('users', array('first_name', 'last_name')).' AS name,
        reports_users.report_notify_format AS format FROM users
        INNER JOIN reports_users ON (users.id = reports_users.user_id AND reports_users.report_id="'.$report_id.'")
        WHERE reports_users.deleted = 0 AND users.deleted = 0
        AND reports_users.report_notify_enabled';


	$result = $db->query($query, true, "Error retrieving notified users");
	$targets = array();

	while($row = $db->fetchByAssoc($result, -1, false)) {
		if(! empty($row['email']))
			$targets[] = $row;
	}
	
    $url = AppConfig::site_url()."index.php?module={$report->getField('primary_module')}&action=index&layout=Reports&report_id={$report_id}&data_id={$data_id}";

    $template_vars = array(
        'REPORT_NAME' => array('value' =>  $report->getField('name'), 'in_subject' => true),
        'REPORT_DATE' => array('value' =>  date($timedate->get_date_format()), 'in_subject' => true),
        'URL'=> array('value' => $url)
    );

	$model = AppConfig::module_primary_bean($report->getField('primary_module'));
	if(! $model) {
		$GLOBALS['log']->fatal("Cannot run report ($report_id): unknown model");
		continue;
	}
	try {
		$list_fmt = new ListFormatter($model);
	} catch(IAHModelError $e) {
		$GLOBALS['log']->fatal("Cannot run report ($report_id): {$e->getMessage()}");
		continue;
	}
	$list_fmt->loadReportData($report, $data_id);

    $mail_template = NotificationManager::loadCustomMessage('Reports', 'ReportNotify', $template_vars);
	$generated = array();

	foreach($targets as $target) {
		$mail->ClearAllRecipients();
		$mail->ClearAttachments();
		$mail->ClearCustomHeaders();

		$mail->AddAddress($target['email'], $target['name']);
		$mail->Subject = from_html($mail_template['subject']);
		$mail->Body = from_html($mail_template['body']);
		$format = $target['format'];

		if ($format != 'link') {
			$export = new ListViewExporter($format);
			$export->setDataResult($list_fmt->data_result->row);
			$output =& $export->outputFormatter($list_fmt, null, false);

            if($output['format'] == 'string') {
                $mail->AddStringAttachment($output['data'], $output['filename'], "base64", $output['mimetype']);
            } else if($output['format'] == 'file') {
                $mail->AddAttachment($output['path'], $output['filename'], "base64", $output['mimetype']);
            } else {
                $log->warn("Scheduled Report: unknown output format '{$output['format']}'");
                continue;
            }
		}

		$mail->prepForOutbound(true);
		$success = $mail->send();

        if(! $success)
			$log->warn("Scheduled Report: error sending e-mail (method: {$mail->Mailer}), (error: {$mail->ErrorInfo})");
	}
}
?>
