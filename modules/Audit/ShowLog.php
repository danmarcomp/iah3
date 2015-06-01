<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/**
 * Popup Picker
 *
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
 */



require_once('XTemplate/xtpl.php');
require_once('include/utils/db_utils.php');
require_once('modules/Audit/Audit.php');

global $currentModule, $focus;
//we don't want the parent module's string file, but rather the string file specifc to this subpanel


$bean = $beanList[$currentModule];
require_once($beanFiles[$bean]);
$focus = new $bean;

class Audit_Popup_Picker
{

	function __construct()
	{
	}

	/**
	 *
	 */
	function process_page()
	{
		global $theme;
		global $focus;
		global $app_strings;
		global $app_list_strings;
		global $currentModule;
		global $odd_bg;
 		global $even_bg;
 		global $image_path;
		global $audit;
		global $current_language;

		$current_module_strings = return_module_language($current_language, 'Audit');

		$audit_list = Audit::get_audit_list();

		$xtpl=new XTemplate ('modules/Audit/ShowLog.html');
		$xtpl->assign('THEME', $theme);
		$xtpl->assign('MOD', $current_module_strings);
		$xtpl->assign('APP', $app_strings);
		insert_popup_header($theme);

		//output header
		echo "<table width='100%' cellpadding='0' cellspacing='0'><tr><td>";
		$mod_name = array_get_default($app_list_strings['moduleList'], $focus->module_dir, $focus->module_dir);
		echo get_module_title($focus->module_dir, $mod_name.": ".$focus->name, false);
		echo "</td><td align='right' class='moduleTitle'>";
		echo "<A href='javascript:print();' class='utilsLink'>".get_image($image_path.'print.gif', 'alt="'.$app_strings['LNK_PRINT'].'" border="0" align="absmiddle"'). "&nbsp;".$app_strings['LNK_PRINT']."</a>\n";
		echo "</td></tr></table>";

		$oddRow = true;
		$audited_fields = AppConfig::setting("model.audited_fields.{$focus->object_name}");
		sort($audited_fields);
		$fields = '';
		$field_count = count($audited_fields);
		$start_tag = "<table><tr><td class=\"listViewPaginationLinkS1\">";
		$end_tag = "</td></tr></table>";
		$labels = array();
		
		if($field_count > 0)
		{
			$index = 0;
    		foreach($audited_fields as $key)
			{
				$value = AppConfig::setting("model.fields.{$focus->object_name}.$key");
				$index++;
				$vname = '';
				if(isset($value['vname']))
					$vname = $value['vname'];
				else if(isset($value['label']))
					$vname = $value['label'];
				$labels[$key] = translate($vname, $focus->module_dir);
				$fields .= $labels[$key];

    			if($index < $field_count)
    			{
    				$fields .= ", ";
    			}
    		}
    		echo $start_tag. translate('LBL_AUDITED_FIELDS', 'Audit') .$fields.$end_tag;
    	}
    	else
    	{
    		echo $start_tag. translate('LBL_NO_AUDITED_FIELDS_TEXT', 'Audit') .$end_tag;
    	}

		foreach($audit_list as $audit)
		{
			// longreach - modified - use array_key_exists not isset
			if(array_key_exists('before_value_string', $audit))
			{
				$before_value = $audit['before_value_string'];
				$after_value = $audit['after_value_string'];
			}
			else
			{
				$before_value = $audit['before_value_text'];
				$after_value = $audit['after_value_text'];
			}
			
			// longreach - start added
			$blank = '<em>'.array_get_default($current_module_strings, 'LBL_BLANK_VALUE', '').'</em>';
			if(! strlen($before_value)) $before_value = $blank;
			if(! strlen($after_value)) $after_value = $blank;
			// longreach - end added
			
            $activity_fields = array(
                'ID' => $audit['id'],
			    'NAME' => array_get_default($labels, $audit['field_name'], $audit['field_name']),
                'BEFORE_VALUE' => $before_value,
                'AFTER_VALUE' => $after_value,
                'CREATED_BY' => $audit['created_by'],
                'DATE_CREATED' => $audit['date_created'],
			);

			$xtpl->assign("ACTIVITY", $activity_fields);

			if($oddRow)
   			{
        		//todo move to themes
				$xtpl->assign("ROW_COLOR", 'oddListRow');
				$xtpl->assign("BG_COLOR", $odd_bg);
    		}
    		else
    		{
        		//todo move to themes
				$xtpl->assign("ROW_COLOR", 'evenListRow');
				$xtpl->assign("BG_COLOR", $even_bg);
    		}
   			$oddRow = !$oddRow;

			$xtpl->parse("audit.row");
		// Put the rows in.
        }//end foreach

		$xtpl->parse("audit");
		$xtpl->out("audit");
		insert_popup_footer();
    }
} // end of class Popup_Picker
?>
