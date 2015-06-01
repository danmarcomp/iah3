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
 ********************************************************************************/
/*********************************************************************************

 * Description: TODO:  To be written.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/

global $app_strings;
global $app_list_strings;
global $mod_strings;
global $theme;
global $currentModule;
global $current_language;
global $gridline;
global $current_user;

theme_hide_side_menu(true);



if (!is_admin($current_user))
{
   sugar_die("Unauthorized access to administration.");
}

echo '<div style="padding: 0 2em">';

echo get_module_title('Administration', $mod_strings['LBL_MODULE_TITLE'], true);


//system.
$admin_option_defs=array();
// longreach - added
$admin_option_defs['company_info']= array($image_path . 'CompanyAddress','LBL_COMPANY_INFO_TITLE','LBL_COMPANY_INFO','./index.php?module=Configurator&action=EditView&layout=Company');
$admin_option_defs['configphp_settings']= array($image_path .'Administration','LBL_CONFIGURE_SETTINGS_TITLE','LBL_CONFIGURE_SETTINGS','./index.php?module=Configurator&action=EditView&layout=Settings');
$admin_option_defs['scheduler']= array($image_path . 'Scheduler','LBL_SCHEDULER_TITLE','LBL_SCHEDULER','./index.php?module=Scheduler&action=index');
$admin_option_defs['currencies_management']= array($image_path . 'Currencies','LBL_MANAGE_CURRENCIES','LBL_CURRENCY','./index.php?module=Currencies&action=index');
$admin_option_defs['locale']= array($image_path . 'Currencies','LBL_MANAGE_LOCALE','LBL_LOCALE','./index.php?module=Configurator&action=EditView&layout=Locale');
$admin_option_defs['license']= array($image_path . 'License','LBL_LICENSE_INFO_TITLE','LBL_LICENSE_INFO_TEXT','./index.php?module=LicenseInfo&action=index');

$admin_group_header[] = array('LBL_ADMINISTRATION_HOME_TITLE','',false,$admin_option_defs);

// longreach - start added
$admin_option_defs=array();
$admin_option_defs['maintain']= array($image_path . 'Repair','LBL_MAINTAIN_TITLE','LBL_MAINTAIN','./index.php?module=Administration&action=Maintain');
$admin_option_defs['backup_management']= array($image_path . 'Backups','LBL_BACKUPS_TITLE','LBL_BACKUPS','./index.php?module=Configurator&action=EditView&layout=Backup');
$admin_option_defs['upgrade_wizard']= array($image_path . 'Upgrade','LBL_UPGRADE_WIZARD_TITLE','LBL_UPGRADE_WIZARD','./index.php?module=UWizard&action=index');
$admin_option_defs['diagnostic']= array($image_path . 'Diagnostic','LBL_DIAGNOSTIC_TITLE','LBL_DIAGNOSTIC_DESC','./index.php?module=Administration&action=Diagnostic');
$admin_option_defs['view_logs'] = array($image_path . 'Log', 'LBL_VIEW_LOGS_TITLE', 'LBL_VIEW_LOGS', './index.php?module=Configurator&action=LogView');
$admin_option_defs['manage_import'] = array($image_path . 'Import', 'LBL_MANAGE_IMPORT_TITLE', 'LBL_MANAGE_IMPORT', './index.php?module=ImportDB&action=ListView');

$admin_group_header[]=array('LBL_SYSTEM_TOOLS_TITLE','',false,$admin_option_defs);
// longreach - end added

//users and security.
$admin_option_defs=array();
$admin_option_defs['user_management']= array($image_path . 'Users','LBL_MANAGE_USERS_TITLE','LBL_MANAGE_USERS','./index.php?module=Users&action=index');
$admin_option_defs['roles_management']= array($image_path . 'ACLRoles','LBL_MANAGE_ROLES_TITLE','LBL_MANAGE_ROLES','./index.php?module=ACLRoles&action=index');
$admin_option_defs['securitygroup_management']= array($image_path . 'SecurityGroups','LBL_MANAGE_SECURITYGROUPS_TITLE','LBL_MANAGE_SECURITYGROUPS','./index.php?module=SecurityGroups&action=index');
$admin_option_defs['skills_management']= array($image_path . 'Skills','LBL_MANAGE_SKILLS_TITLE','LBL_MANAGE_SKILLS','./index.php?module=Skills&action=index');
$admin_option_defs['acl_settings']= array($image_path . 'Administration','LBL_ACL_SETTINGS_TITLE','LBL_ACL_SETTINGS','./index.php?module=Configurator&action=EditView&layout=ACL');


$admin_group_header[]=array('LBL_USERS_TITLE','',false,$admin_option_defs);

//email manager.
$admin_option_defs=array();
$admin_option_defs['mass_Email_config']= array($image_path . 'EmailMan','LBL_MASS_EMAIL_CONFIG_TITLE','LBL_MASS_EMAIL_CONFIG_DESC','./index.php?module=Configurator&action=EditView&layout=Email');
$admin_option_defs['mass_Email']= array($image_path . 'EmailMan','LBL_MASS_EMAIL_MANAGER_TITLE','LBL_MASS_EMAIL_MANAGER_DESC','./index.php?module=EmailMan&action=index');
$admin_option_defs['mass_Email_system']= array($image_path . 'EmailTemplates','LBL_MASS_EMAIL_SYSTEM_TITLE','LBL_MASS_EMAIL_SYSTEM_DESC','./index.php?module=EmailMan&action=system');
/* longreach - replaced
$admin_option_defs['mailboxes']= array($image_path . 'InboundEmail','LBL_MANAGE_MAILBOX','LBL_MAILBOX_DESC','./index.php?module=InboundEmail&action=index');
 */
$admin_option_defs['mailboxes']= array($image_path . 'EmailPOP3','LBL_MAILBOXES_TITLE','LBL_MAILBOXES_TEXT','./index.php?module=EmailPOP3&action=index');
// longreach - added
$admin_option_defs['group_folders']= array($image_path . 'EmailFolders','LBL_GROUP_FOLDERS_TITLE','LBL_GROUP_FOLDERS_TEXT','./index.php?module=EmailFolders&action=index&layout=GroupFolders');

$admin_group_header[]=array('LBL_EMAIL_TITLE','',false,$admin_option_defs);

//studio.
$admin_option_defs=array();
$admin_option_defs['layout']= array($image_path . 'Layout','LBL_EDIT_LAYOUT','LBL_EDIT_LAYOUT_DESC','./index.php?module=NewStudio&action=index&wizard=EditLayout');
$admin_option_defs['configure_group_tabs']= array($image_path . 'ConfigureTabs','LBL_CONFIGURE_GROUP_TABS','LBL_CONFIGURE_GROUP_TABS_DESC','./index.php?action=index&module=NewStudio&wizard=GroupTabs');

$admin_option_defs['custom_fields']= array($image_path . 'Layout','LBL_CUSTOM_FIELDS','LBL_CUSTOM_FIELDS_DESC','./index.php?module=NewStudio&action=index&wizard=EditCustom');
$admin_option_defs['configure_tabs']= array($image_path . 'ConfigureTabs','LBL_CONFIGURE_TABS','LBL_CHOOSE_WHICH','./index.php?module=NewStudio&wizard=ConfigTabs&action=index');
$admin_option_defs['dropdowns']= array($image_path . 'Layout','LBL_DROPDOWNS','LBL_DROPDOWNS_DESC','./index.php?module=NewStudio&action=index&wizard=EditDropdowns');
$admin_option_defs['rename_tabs']= array($image_path . 'RenameTabs','LBL_RENAME_TABS','LBL_CHANGE_NAME_TABS',"./index.php?action=index&module=NewStudio&wizard=RenameTabs");
$admin_option_defs['saved_search']= array($image_path . 'Layout','LBL_CUSTOM_LIST_LAYOUTS','LBL_EDIT_CUSTOM_LIST_LAYOUTS',"./index.php?action=index&module=SavedSearch");
$admin_option_defs['portal']= array($image_path . 'iFrames','LBL_IFRAME','DESC_IFRAME','./index.php?module=iFrames&action=index');
$admin_option_defs['workflow']= array($image_path . 'Workflow','LBL_WORKFLOW','LBL_WORKFLOW_DESC',"./index.php?module=Workflow&action=index");
if (in_array('designer', $_SESSION['LIC_PRODUCTS'])) {
	$admin_option_defs['module_designer']= array($image_path . 'ModuleDesigner','LBL_MODULE_BUILDER','LBL_MODULE_BUILDER_DESC','./index.php?module=ModuleDesigner&action=index');
}

//$admin_option_defs['manage_layout']= array($image_path . 'Layout','LBL_MANAGE_LAYOUT','LBL_LAYOUT','./index.php?module=DynamicLayout&action=index');
//$admin_option_defs['dropdown_editor']= array($image_path . 'Dropdown','LBL_DROPDOWN_EDITOR','LBL_DROPDOWN_EDITOR','./index.php?module=Dropdown&action=index');
//$admin_option_defs['edit_custom_fields']= array($image_path . 'FieldLabels','LBL_EDIT_CUSTOM_FIELDS','DESC_EDIT_CUSTOM_FIELDS','./index.php?module=EditCustomFields&action=index');
//$admin_option_defs['migrate_custom_fields']= array($image_path . 'MigrateFields','LBL_EXTERNAL_DEV_TITLE','LBL_EXTERNAL_DEV_DESC','./index.php?module=Administration&action=Development');


// longreach - start added - workflow





// longreach - end added - workflow





$admin_group_header[]=array('LBL_STUDIO_TITLE','',false,$admin_option_defs);



// longreach - start added - quotes
$admin_option_defs = array();
$admin_option_defs['shipping_providers'] = array($image_path . 'Shippers', 'LBL_MANAGE_SHIPPING_PROVIDERS', 'LBL_SHIPPING_PROVIDERS', './index.php?module=ShippingProviders&action=index');
$admin_option_defs['tax_rates'] = array($image_path . 'TaxRates', 'LBL_MANAGE_TAXRATES', 'LBL_TAXRATES', './index.php?module=TaxRates&action=index');
$admin_option_defs['tax_codes'] = array($image_path . 'TaxCodes', 'LBL_MANAGE_TAXCODES', 'LBL_TAXCODES', './index.php?module=TaxCodes&action=index');
$admin_group_header[]=array('LBL_QUOTES_TITLE','',false,$admin_option_defs);
// longreach - end added

// longreach - start added - meeting resources
$admin_option_defs = array();
$admin_option_defs['forum_topics']= array($image_path . 'ForumTopics','LBL_FORUM_TOPICS_TITLE','LBL_FORUM_TOPICS_DESC','./index.php?module=ForumTopics&action=index');
$admin_option_defs['feeds']= array($image_path . 'Feeds','LBL_FEEDS_TITLE','LBL_FEEDS_DESC','./index.php?module=Feeds&action=index');
$admin_option_defs['resources'] = array($image_path . 'Resources', 'LBL_RESOURCES_TITLE', 'LBL_RESOURCES_DESC', './index.php?action=index&module=Resources');
$admin_group_header[]=array('LBL_ACTIVITIES_SUPPORT_TITLE','',false,$admin_option_defs);
// longreach - end added




$files = array_column(AppConfig::setting('ext.admin', array()), 'name');
array_extend($files, glob_unsorted('custom/modules/Administration/administration.*.php'));
foreach ($files as $f) {
	include ($f);
}

require_once('XTemplate/xtpl.php');
$xtpl=new XTemplate ('modules/Administration/index.html');

foreach ($admin_group_header as $values) {
	$title = $values[0];
	if (is_array($title)) {
		$title = translate($title[0], $title[1]);
	} else {
		$title = $mod_strings[$title];
	}
	$group_header_value=get_form_header(to_html($title),$values[1],$values[2]);
	$xtpl->assign("GROUP_HEADER", $group_header_value);

   $colnum=0;
	foreach ($values[3] as $admin_option) {
		$title = array_get_default($admin_option, 1, '');
		if (is_array($title)) {
			$title = translate($title[0], $title[1]);
		} else if($title) {
			$title = translate($title, 'Administration');
		}
		$colnum+=1;
		$icon = array_get_default($admin_option, 0, '');
		if($icon) $icon = get_image($icon, 'border="0" align="absmiddle"');
		$xtpl->assign("ITEM_HEADER_IMAGE", $icon);
		$xtpl->assign("ITEM_URL", array_get_default($admin_option, 3, ''));
		$label = to_html($title);
		if(!empty($admin_option['additional_label']))$label.= ' '. $admin_option['additional_label'];
		if(!empty($admin_option[4])){
			$label = ' <font color="red">'. $label . '</font>';
		}

		$xtpl->assign("ITEM_HEADER_LABEL", $label);
		$desc = array_get_default($admin_option, 2, '');
		if (is_array($desc)) {
			$desc = translate($desc[0], $desc[1]);
		} else if($desc) {
			$desc = translate($desc, 'Administration');
		}
		$xtpl->assign("ITEM_DESCRIPTION", to_html($desc));

		$xtpl->parse('main.group.row.col');
		if (($colnum % 2) == 0) {
			$xtpl->parse('main.group.row');
		}
	}
	//if the loop above ends with an odd entry add a blank column.
	if (($colnum % 2) != 0) {
		$xtpl->parse('main.group.row.empty');
		$xtpl->parse('main.group.row');
	}

	$xtpl->parse('main.group');
}
$xtpl->parse('main');
$xtpl->out('main');

echo '</div>';

?>
