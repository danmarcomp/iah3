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
 * $Id: Menu.php,v 1.43 2005/05/01 16:05:26 ron Exp $
 * Description:  TODO To be written.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/


global $mod_strings;
$module_menu = Array();

	
if(ACLController::checkAccess('Emails', 'edit', true)) $module_menu[] = Array("index.php?module=Emails&action=EditView&type=out&return_module=Emails&return_action=DetailView", $mod_strings['LNK_NEW_SEND_EMAIL'],"CreateEmails", 'Emails');

if(ACLController::checkAccess('Emails', 'edit', true)) $module_menu[] = Array("index.php?module=Emails&action=EditView&type=archived&return_module=Emails&return_action=DetailView", $mod_strings['LNK_NEW_ARCHIVE_EMAIL'],"CreateEmails", 'Emails');

if(ACLController::checkAccess('Emails', 'list', true)) $module_menu[] = Array("index.php?module=Emails&action=index", $mod_strings['LNK_EMAIL_LIST'], 'Emails', 'Emails');

if(ACLController::checkAccess('EmailTemplates', 'list', true)) $module_menu[] = Array("index.php?module=EmailTemplates&action=index", $mod_strings['LNK_EMAIL_TEMPLATE_LIST'],"EmailTemplates","EmailTemplates");

if(ACLController::checkAccess('EmailTemplates', 'edit', true)) $module_menu[] = Array("index.php?module=EmailTemplates&action=EditView&return_module=EmailTemplates&return_action=DetailView", $mod_strings['LNK_NEW_EMAIL_TEMPLATE'],"CreateEmailTemplates","EmailTemplates");

$module_menu[] = Array("index.php?module=EmailFolders&action=index", $mod_strings['LNK_EMAIL_FOLDERS_LIST'],"EmailFolders", 'Emails');
$module_menu[] = Array("index.php?module=EmailFolders&action=EditView", $mod_strings['LNK_NEW_FOLDER'],"CreateEmailFolders", 'Emails');


if(ACLController::checkAccess('Emails','list', true)) $module_menu[] = Array('#', '<span style="display: none">wp_shortcut_fill_0</span>', '');

