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

 * Description:  TODO: To be written.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/
require_once('XTemplate/xtpl.php');
require_once('data/Tracker.php');
require_once('modules/MergeRecords/MergeRecord.php');
require_once('themes/'.$theme.'/layout_utils.php');
require_once('log4php/LoggerManager.php');
require_once('include/ListView/ListView.php');
require_once('include/JSON.php');

global $app_strings;
global $mod_strings;
global $app_list_strings;
global $current_language;
global $currentModule;
global $theme;

$json=new JSON(JSON_LOOSE_TYPE);

$current_module_strings = return_module_language($current_language, 'MergeRecords');

if (!isset($where)) $where = "";

$focus = new MergeRecord();

////////////////////////////////////////////////////////////
//get instance of master record and retrieve related record
//and items
////////////////////////////////////////////////////////////
$focus->merge_module = $_REQUEST['return_module'];
$focus->load_merge_bean($focus->merge_module, true, $_REQUEST['record']);


//get all available column fields
//TO DO: add custom field handling
$avail_fields=array();
$sel_fields=array();
$temp_field_array = $focus->merge_bean->field_defs;
$bean_data=array();
foreach($temp_field_array as $field_array)
{
	if (isset($field_array['merge_filter'])) {
		if (strtolower($field_array['merge_filter'])=='enabled' or strtolower($field_array['merge_filter'])=='selected') {
			$col_name = $field_array['name'];

                            
			if(!isset($focus->merge_bean_strings[$field_array['vname']])) {
				$col_label = $col_name;
			}
			else {
				$col_label = str_replace(':', '', $focus->merge_bean_strings[$field_array['vname']]);
			}
			
			if (strtolower($field_array['merge_filter'])=='selected') {
				$sel_fields[$col_name]=$col_label;
			} else {
                $avail_fields[$col_name] = $col_label;
            }
			
			$bean_data[$col_name]=$focus->merge_bean->$col_name;
		}
	}
}

/////////////////////////////////////////////////////////

//Print the master record header to the page
echo "\n<p>\n";
echo get_module_title($mod_strings['LBL_MODULE_NAME'], $mod_strings['LBL_LBL_MERGRE_RECORDS_STEP_1']." - ".$focus->merge_bean->name, true);
echo "\n</p>\n";

$xtpl = new XTemplate ('modules/MergeRecords/Step1.html');
$xtpl->assign("MOD", $mod_strings);
$xtpl->assign("APP", $app_strings);
$xtpl->assign("BEANDATA",$json->encode($bean_data));
//This is for the implemetation of finding all dupes for a module, not just
//dupes for a particular record
//commenting this out for now
//$choose_master_by_options = array('First Record Found', 'Most Recent Record', 'Oldest Record', 'Record Containing Most Data');
//$xtpl->assign("CHOOSE_MASTER_BY_OPTIONS", get_select_options_with_id($choose_master_by_options, 'First Record Found'));

$xtpl->assign("MERGE_MODULE", $focus->merge_module);
$xtpl->assign("ID", $focus->merge_bean->id);

$xtpl->assign("FIELD_AVAIL_OPTIONS", get_select_options_with_id($avail_fields,''));

if(isset($_REQUEST['return_id'])) $xtpl->assign("RETURN_ID", $_REQUEST['return_id']);
$xtpl->assign("RETURN_ACTION", $_REQUEST['return_action']);
$xtpl->assign("RETURN_MODULE", $_REQUEST['return_module']);

//set the url
$port=null;
if(isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) {
    $port = $_SERVER['SERVER_PORT'];
}
$xtpl->assign("URL", appendPortToHost(AppConfig::site_url(), $port));
//set image page.
global $theme;
$theme_path="themes/".$theme."/";
$xtpl->assign("IMAGE_PATH", $theme_path);

//process preloaded filter.
$pre_loaded=null;
foreach ($sel_fields as $colName=>$colLabel) {
    $pre_loaded.=addFieldRow($colName,$colLabel,$bean_data[$colName]);
}
$xtpl->assign("PRE_LOADED_FIELDS",$pre_loaded);
$xtpl->assign("OPERATOR_OPTIONS",$json->encode($app_list_strings['merge_operators_dom']));


$xtpl->parse("main.field_select_block");

$xtpl->parse("main");
$xtpl->out("main");


/**
 * This function is equivalent of AddFieldRow in Step1.js. is being used to
 * preload the filter criteria based on the vardef.
 * <span><table><tr><td></td><td></td><td></td></tr></table></span>
 */
function addFieldRow($colName,$colLabel,$colValue) {
    global $theme, $app_list_strings;
    
    static $operator_options;
    if (empty($operator_options)) {
        $operator_options= get_select_options($app_list_strings['merge_operators_dom'],'');
    }
        
    $snippet=<<<EOQ
    <span id=filter_{$colName} style='visibility:visible' value="{$colLabel}" valueId="{$colName}">
        <table width='100%' border='0' cellpadding='0'>
            <tr>
                <td width='2%'><a class="listViewTdToolsS1" href="javascript:remove_filter('filter_{$colName}')"><img src='themes/{$theme}/images/delete_inline.gif' align='absmiddle' alt='rem' border='0' height='12' width='12'>&nbsp;</a></td>
                <td width='20%'>{$colLabel}:&nbsp;</td>
                <td width='10%'><select name='{$colName}SearchType'>{$operator_options}</select></td>
                <td width='68%'><input value="{$colValue}" id="{$colName}SearchField" name="{$colName}SearchField" type="text"></td>                  
            </tr> 
        </table>
    </span>
EOQ;

    return $snippet;
}

?>
