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



global $app_strings;
global $app_list_strings;
global $mod_strings;
global $theme;
global $currentModule;
global $gridline;

$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";
require_once($theme_path.'layout_utils.php');



echo get_module_title($mod_strings['LBL_MODULE_NAME'], $mod_strings['LBL_MAINTAIN_TITLE'], true);

echo get_form_header($mod_strings['LBL_DATABASE_MAINTAIN_TITLE'], '', false);
?>
<table width="100%" cellpadding="0" cellspacing="<?php echo $gridline;?>" border="0" class="tabDetailView2">
<tr>
	<td class="tabDetailViewDL2" width="30%" nowrap><?php echo get_image($image_path.'Repair','alt="" align="absmiddle" border="0"'); ?>&nbsp;<a href="./index.php?module=Administration&action=RepairSchema"><?php echo $mod_strings['LBL_REPAIR_DATABASE']; ?></a></td>
	<td class="tabDetailViewDF2"> <?php echo $mod_strings['LBL_REPAIR_DATABASE_DESC'] ; ?> </td>
</tr>
<?php /*<tr>
	<td class="tabDetailViewDL2" width="30%" nowrap><?php echo get_image($image_path.'DataSets','alt="" align="absmiddle" border="0"'); ?>&nbsp;<a href="./index.php?module=Administration&action=ResetDatabase"><?php echo $mod_strings['LBL_RESET_DATABASE_TITLE']; ?></a></td>
	<td class="tabDetailViewDF2"> <?php echo $mod_strings['LBL_RESET_DATABASE'] ; ?> </td>
</tr> */?>
</table>

<?php echo get_form_header($mod_strings['LBL_CACHE_MAINTAIN_TITLE'], '', false); ?>

<table width="100%" cellpadding="0" cellspacing="<?php echo $gridline;?>" border="0" class="tabDetailView2">
<tr>
    <td class="tabDetailViewDL2" width="30%" nowrap><?php echo get_image($image_path.'Notes','alt="" align="absmiddle" border="0"'); ?>&nbsp;<a href="./index.php?module=Administration&action=CleanUploads"><?php echo $mod_strings['LBL_CLEAN_UPLOADS']; ?></a></td>
    <td class="tabDetailViewDF2"> <?php echo $mod_strings['LBL_CLEAN_UPLOADS_TITLE'] ; ?> </td>
</tr>
<tr>
    <td class="tabDetailViewDL2" width="30%" nowrap><?php echo get_image($image_path.'Rebuild','alt="" align="absmiddle" border="0"'); ?>&nbsp;<a href="./index.php?module=Administration&action=ResetCache"><?php echo $mod_strings['LBL_RESET_SYSTEM_CACHE_TITLE']; ?></a></td>
    <td class="tabDetailViewDF2"> <?php echo $mod_strings['LBL_RESET_SYSTEM_CACHE_DESC'] ; ?> </td>
</tr>
<tr>
	<td class="tabDetailViewDL2" width="30%" nowrap><?php echo get_image($image_path.'Rebuild','alt="" align="absmiddle" border="0"'); ?>&nbsp;<a href="./index.php?module=Administration&action=clear_chart_cache"><?php echo $mod_strings['LBL_CLEAR_CHART_DATA_CACHE_TITLE']; ?></a></td>
	<td class="tabDetailViewDF2"> <?php echo $mod_strings['LBL_CLEAR_CHART_DATA_CACHE_DESC'] ; ?> </td>
</tr>
<tr>
	<td class="tabDetailViewDL2" width="30%" nowrap><?php echo get_image($image_path.'Rebuild','alt="" align="absmiddle" border="0"'); ?>&nbsp;<a href="./index.php?module=Administration&action=RebuildSchedulers"><?php echo $mod_strings['LBL_REBUILD_SCHEDULERS_TITLE']; ?></a></td>
	<td class="tabDetailViewDF2"> <?php echo $mod_strings['LBL_REBUILD_SCHEDULERS_DESC_SHORT'] ; ?> </td>
</tr>

<tr>
    <td class="tabDetailViewDL2" width="30%" nowrap><?php echo get_image($image_path.'Rebuild','alt="" align="absmiddle" border="0"'); ?>&nbsp;<a href="./index.php?module=Administration&action=RebuildDashlets"><?php echo $mod_strings['LBL_REBUILD_DASHLETS_TITLE']; ?></a></td>
    <td class="tabDetailViewDF2"> <?php echo $mod_strings['LBL_REBUILD_DASHLETS_DESC_SHORT'] ; ?> </td>
</tr>
<tr>
    <td class="tabDetailViewDL2" width="30%" nowrap><?php echo get_image($image_path.'Rebuild','alt="" align="absmiddle" border="0"'); ?>&nbsp;<a href="./index.php?module=Administration&action=RebuildJSLang"><?php echo $mod_strings['LBL_REBUILD_JAVASCRIPT_LANG_TITLE']; ?></a></td>
    <td class="tabDetailViewDF2"> <?php echo $mod_strings['LBL_REBUILD_JAVASCRIPT_LANG_DESC_SHORT'] ; ?> </td>
</tr>
</table>

<?php echo get_form_header($mod_strings['LBL_OTHER_MAINTAIN_TITLE'], '', false); ?>

<table width="100%" cellpadding="0" cellspacing="<?php echo $gridline;?>" border="0" class="tabDetailView2">
<tr>
	<td class="tabDetailViewDL2" width="30%" nowrap><?php echo get_image($image_path.'LanguagePacks','alt="" align="absmiddle" border="0"'); ?>&nbsp;<a href="./index.php?module=Administration&action=LanguageFiles"><?php echo $mod_strings['LBL_LANGUAGE_FILES_TITLE']; ?></a></td>
	<td class="tabDetailViewDF2"> <?php echo $mod_strings['LBL_LANGUAGE_FILES'] ; ?> </td>
</tr>
<tr>
    <td class="tabDetailViewDL2" width="30%" nowrap><?php echo get_image($image_path.'Activities','alt="" align="absmiddle" border="0"'); ?>&nbsp;<a href="./index.php?module=Administration&action=RepairSyncDates"><?php echo $mod_strings['LBL_REPAIR_SYNC_DATES']; ?></a></td>
    <td class="tabDetailViewDF2"> <?php echo $mod_strings['LBL_REPAIR_SYNC_DATES_DESC'] ; ?> </td>
</tr>
<tr>
    <td class="tabDetailViewDL2" width="30%" nowrap><?php echo get_image($image_path.'Employees','alt="" align="absmiddle" border="0"'); ?>&nbsp;<a href="./index.php?module=Administration&action=RepairEmployees"><?php echo $mod_strings['LBL_REPAIR_EMPLOYEES']; ?></a></td>
    <td class="tabDetailViewDF2"> <?php echo $mod_strings['LBL_REPAIR_EMPLOYEES_DESC'] ; ?> </td>
</tr>
<tr>
    <td class="tabDetailViewDL2" width="30%" nowrap><?php echo get_image($image_path.'EmailFolders','alt="" align="absmiddle" border="0"'); ?>&nbsp;<a href="./index.php?module=Administration&action=RepairEmailFolders"><?php echo $mod_strings['LBL_REPAIR_EMAIL_FOLDERS']; ?></a></td>
    <td class="tabDetailViewDF2"> <?php echo $mod_strings['LBL_REPAIR_EMAIL_FOLDERS_DESC'] ; ?> </td>
</tr>
<tr>
    <td class="tabDetailViewDL2" width="30%" nowrap><?php echo get_image($image_path.'ConfigureTabs','alt="" align="absmiddle" border="0"'); ?>&nbsp;<a href="./index.php?module=Administration&action=ClearUserTabPrefs"><?php echo $mod_strings['LBL_CLEAR_TAB_PREFS']; ?></a></td>
    <td class="tabDetailViewDF2"> <?php echo $mod_strings['LBL_CLEAR_TAB_PREFS_DESC'] ; ?> </td>
</tr>
<tr>
    <td class="tabDetailViewDL2" width="30%" nowrap><?php echo get_image($image_path.'Rebuild','alt="" align="absmiddle" border="0"'); ?>&nbsp;<a href="./index.php?module=Administration&action=UpgradeAccess"><?php echo $mod_strings['LBL_REBUILD_HTACCESS']; ?></a></td>
    <td class="tabDetailViewDF2"> <?php echo $mod_strings['LBL_REBUILD_HTACCESS_DESC'] ; ?> </td>
</tr>

</table>
