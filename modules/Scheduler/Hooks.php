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

class ScheduleHooks
{
	static public function pre_title()
	{
		require_bean('Schedule');
		$seed = new Schedule();
		$status = $seed->get_scheduler_status();
		$interval = '';
		if ($status['has_run'])
			$run_status = translate('LBL_SCHEDULER_RUN_RECENTLY', 'Scheduler');
		else	
			$run_status = translate('LBL_SCHEDULER_NOT_RUN_RECENTLY', 'Scheduler');
		if($status['last_interval']) {
			$interval = '<br>' . translate('LBL_LAST_INTERVAL', 'Scheduler') . translate('LBL_SEPARATOR', 'app') . $status['last_interval'];
		}
		$LBL_LAST_RUN = translate('LBL_LAST_RUN', 'Scheduler') . translate('LBL_SEPARATOR', 'app');
		$header = get_form_header(translate('LBL_SCHEDULER_STATUS_TITLE', 'Scheduler'), "", false);
		return $header . <<<HTML
<table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm" style="margin-bottom: 0.5em">
<tr><td>
	<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
	<td align="left" class="dataLabel" colspan="5">
		{$status['image']}
		{$run_status}
		<div style="margin-left: 16px; margin-top: 0.2em">{$LBL_LAST_RUN}{$status['last_run']}{$interval}</div>
	</td>
	</tr>
	</table>
</td></tr>
</table>
HTML;
	}
}


