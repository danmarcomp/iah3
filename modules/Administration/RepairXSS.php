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
 * *******************************************************************************/
/*********************************************************************************

 * Description:
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc. All Rights
 * Reserved. Contributor(s): ______________________________________..
 *********************************************************************************/
require_once('include/Sugar_Smarty.php');
include("include/modules.php"); // provides $moduleList, $beanList, etc.

///////////////////////////////////////////////////////////////////////////////
////	UTILITIES
/**
 * Cleans all SugarBean tables of XSS - no asynchronous calls.  May take a LONG time to complete.
 * Meant to be called from a Scheduler instance or other timed or other automation.
 */
function cleanAllBeans() {
	
}
////	END UTILITIES
///////////////////////////////////////////////////////////////////////////////


///////////////////////////////////////////////////////////////////////////////
////	PAGE OUTPUT
if(isset($runSilent) && $runSilent == true) {
	// if called from Scheduler
	cleanAllBeans();
} else {
	$hide = array('Activities', 'Home', 'iFrames', 'Calendar', 'Dashboard');

	sort($moduleList);
	$options = array();
	$options[] = $app_strings['LBL_NONE'];
	$options['all'] = "--{$app_strings['LBL_TABGROUP_ALL']}--";
	
	foreach($moduleList as $module) {
		if(!in_array($module, $hide)) {
			$options[$module] = $module;
		}
	}
	
	$options = get_select_options_with_id($options, '');
	$beanDropDown = "<select onchange='SUGAR.Administration.RepairXSS.refreshEstimate(this);' id='repairXssDropdown'>{$options}</select>";
	
	echo get_module_title('Administration', $mod_strings['LBL_REPAIRXSS_TITLE'].":", true);
	echo "<script>var done = '{$mod_strings['LBL_DONE']}';</script>";
	
	$smarty = new Sugar_Smarty(); 
	$smarty->assign("mod", $mod_strings);
	$smarty->assign("beanDropDown", $beanDropDown);
	$smarty->display("modules/Administration/templates/RepairXSS.tpl");
} // end else
