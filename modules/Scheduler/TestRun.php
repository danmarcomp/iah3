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

define('TEST_SCHEDULER', true);

require_once('modules/Scheduler/Schedule.php');

if(empty($_REQUEST['record']))
	sugar_die('No record ID provided');

$focus = ListQuery::quick_fetch('Schedule', $_REQUEST['record']);

if(! $focus)
	sugar_die('Error retrieving schedule');

$return_label = translate('LBL_RETURN_BUTTON_LABEL', 'Scheduler');

?>
<br />
<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
	<td align="left" style="padding-bottom: 2px;">
		<form name="EditView" method="POST" action="index.php" >
			<input type="hidden" name="module" value="Scheduler">
			<input type="hidden" name="record" value="<?php echo $focus->getField('id'); ?>">
			<input type="hidden" name="action" value="index">
			<button class="input-button input-outer" type="submit"><div class="input-icon icon-return left"></div><span class="input-label"><?php echo $return_label; ?></span></button>
		</form>
	</td>
</tr></table>
<br /><br />
<?php
	echo translate('LBL_RUNNING', 'Scheduler') . translate('LBL_SEPARATOR', 'app');
	echo Schedule::translate_type($focus->getField('type'));
	echo '...<br /><br />';

	if(! defined('inScheduler')) define('inScheduler', 1);
	scheduler_run_result($focus);
	
	echo translate('LBL_RUN_OVER', 'Scheduler');

?>
