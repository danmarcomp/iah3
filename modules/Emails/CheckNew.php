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
 * Portions created by SugarCRM are Copyright (C) 2004-2005 SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 ********************************************************************************/
/*********************************************************************************
 * $Id: $
 * Description:  TODO: To be written.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/

require_once('modules/Emails/EmailImport.php');

$import = new EmailImport(false, true);
$import->run();

global $mod_strings;
if ($GLOBALS['NEW_EMAILS_COUNT']) {
	$lbl = $mod_strings[$GLOBALS['NEW_EMAILS_COUNT'] == 1 ? 'MSG_1_NEW_MESSAGE' : 'MSG_NEW_MESSAGES_COUNT'];
	add_flash_message(sprintf($lbl, $GLOBALS['NEW_EMAILS_COUNT']));
} else {
	add_flash_message($mod_strings['MSG_NO_NEW_MESSAGES']);
}

if(empty($_REQUEST['noredir'])) {
	header('Location: index.php?module=Emails&action=index&std_folder=INBOX');
	sugar_cleanup(true);
}
