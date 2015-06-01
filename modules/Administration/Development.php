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


echo "\n<p>\n";
echo get_module_title('MigrateFields', $mod_strings['LBL_EXTERNAL_DEV_TITLE'], true);
echo "\n</p>\n";

?>
<p>
<table width="100%" cellpadding="0" cellspacing="<?php echo $gridline;?>" border="0" class="tabDetailView2">
<tr>
	<td class="tabDetailViewDL2" nowrap><?php echo get_image($image_path.'ImportCustomFields','alt="'. $mod_strings['LBL_IMPORT_CUSTOM_FIELDS_TITLE'].'" align="absmiddle" border="0"'); ?>&nbsp;<a href="./index.php?module=Administration&action=ImportCustomFieldStructure" class="tabDetailViewDL2Link"><?php echo $mod_strings['LBL_IMPORT_CUSTOM_FIELDS_TITLE']; ?></a></td>
	<td class="tabDetailViewDF2"> <?php echo $mod_strings['LBL_IMPORT_CUSTOM_FIELDS'] ; ?> </td>
</tr>
<tr>
	<td class="tabDetailViewDL2" nowrap><?php echo get_image($image_path.'ExportCustomFields','alt="'. $mod_strings['LBL_EXPORT_CUSTOM_FIELDS_TITLE'].'" align="absmiddle" border="0"'); ?>&nbsp;<a href="./index.php?module=Administration&action=ExportCustomFieldStructure" class="tabDetailViewDL2Link"><?php echo $mod_strings['LBL_EXPORT_CUSTOM_FIELDS_TITLE']; ?></a></td>
	<td class="tabDetailViewDF2"> <?php echo $mod_strings['LBL_EXPORT_CUSTOM_FIELDS'] ; ?> </td>
</tr>

</table></p>


