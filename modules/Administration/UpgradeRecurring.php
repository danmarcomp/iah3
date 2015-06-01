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


action_restricted_for('demo');

global $current_user;
if(! is_admin($current_user))
	die('Admin Only Section');

global $db;


$qDone = "SELECT * FROM versions WHERE name = 'Recurring Services'";
$rDone = $db->query($qDone);
$rowsDone = $db->getRowCount($rDone);
if($rowsDone > 0) {
	$done = true;
} else {
	$done = false;
}

if(! $done) {

	$date = "IFNULL(DATE(i.date_entered), '0000-00-00')";
	$query = "
		SELECT 
			IF(MAX($date)='0000-00-00', s.start_date, MAX($date)) AS last_date,
			IF(MAX($date)='0000-00-00', 0, 1) AS has_invoices,
			s.id,
			s.billing_day
		FROM
			monthly_services s
		LEFT JOIN 
			services_invoices si ON s.id = si.service_id
		LEFT JOIN
			invoice i ON i.id = si.invoice_id
		WHERE 
			(si.deleted=0 OR si.deleted IS NULL) AND (i.deleted=0 OR i.deleted IS NULL) AND s.deleted = 0
		GROUP BY
			s.id
	";
	$res = $db->query($query, true);
	while ($row = $db->fetchByAssoc($res)) {
		$d = $row['billing_day'];
		list($y, $m) = explode('-', $row['last_date']);
		if ($row['has_invoices']) {
			$m++;
			if ($m > 12) {
				$m -= 12;
				$y++;
			}
		}
		$nDays = date('t', strtotime(sprintf("%04d-%02d-01", $y, $m)));
		if ($d > $nDays) $d = $nDays;
		$query = sprintf(
			"UPDATE monthly_services SET next_invoice = '%04d-%02d-%02d' WHERE id='%s'",
			$y, $m, $d, $row['id']
		);
		$db->query($query, true);
	}

	require_once('modules/Versions/Version.php');
	unset($_SESSION['upgrade_recurring']);
	Version::mark_upgraded('Recurring Services', '6.7.0', '6.7.0');
	echo translate('LBL_UPGRADE_COMPLETE', 'Administration');
} else {
	echo translate('LBL_UPGRADE_PERFORMED', 'Administration');
}


