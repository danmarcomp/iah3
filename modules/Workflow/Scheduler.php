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
if (!defined('inScheduler')) die('Unauthorized access');


require_once 'modules/Workflow/WFQueryBuilder.php';
require_once 'modules/Workflow/Workflow.php';
require_once 'modules/Workflow/WorkflowProcessor.php';
require_once 'include/database/ListQuery.php';
require_once 'include/database/RowResult.php';
require_once 'include/database/RowUpdate.php';
$wp = new WorkflowProcessor;

$time_limit = ini_get('max_execution_time');
set_time_limit(0);
$now = time();

global $db;

$now = gmdate('Y-m-d H:i:s');

$query = "SELECT w.id FROM workflow w WHERE w.occurs_when = 'time' AND w.deleted = 0 AND w.status='active'";
$res = $db->query($query, true);

while ($row = $db->fetchByAssoc($res)) {
	$w = new Workflow;
	$w->retrieve($row['id']);
	$w->lazy_load();
	$is_update = ($w->execute_mode == 'existingRecordOnly' || $w->execute_mode == 'newRecordAndExisting') ? '1' : '0';
	$cls = AppConfig::module_primary_bean($w->trigger_module);
	if ($cls) {
		$builder = new WFQueryBuilder($w);
		$ids = $builder->getModifiedBeans();
		foreach ($ids as $id) {
			$result = ListQuery::quick_fetch($cls, $id['id']);
			if ($result) {
				$update = RowUpdate::for_result($result);
				$update->new_record = !$is_update;
				if ($wp->process($update, 'after_save', $w, $id['modified_user_id']))
				{
					// Workflow conditions matched, workflow marked as completed
					$db->query("REPLACE INTO workflow_data_audit_link SET workflow_id='{$w->id}', related_id='{$id['id']}'", true);
				}
			}
		}
	}
	$w->cleanup();
	unset($w);
}

