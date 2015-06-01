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
global $app_strings;
global $app_list_strings;
global $current_language;
global $urlPrefix;
global $currentModule;
global $theme;

$current_module_strings = return_module_language($current_language, 'MergeRecords');


$focus = new MergeRecord();
$focus->load_merge_bean($_REQUEST['merge_module'], true, $_REQUEST['record']);

echo "\n<p>\n";
echo get_module_title($mod_strings['LBL_MODULE_NAME'], $current_module_strings['LBL_STEP2_FORM_TITLE']. ' ' . $focus->merge_bean->name, true);
echo "\n</p>\n";

$focus->populate_search_params($_REQUEST);

$where_clauses = Array();
$where_clauses = $focus->create_where_statement();
$where = $focus->generate_where_statement($where_clauses);

$ListView = new ListView();

$ListView->force_mass_update=true;
$ListView->show_mass_update_form=false;
$ListView->show_export_button=false;
$ListView->keep_mass_update_form_open=true;

$bean_list_template_path = 'modules/'.$focus->merge_bean->module_dir.'/ListView.html';
$bean_list_template_var = strtoupper($beanList[$focus->merge_module]);

// Bug 7706: bean_list_template_var is being mapped to BUG TRACKER from the app_list_strings
// and it should be BUG to accommodate for the ListView
if ($bean_list_template_var == 'BUG TRACKER')
	$bean_list_template_var = 'BUG';

$ListView->initNewXTemplate($bean_list_template_path, $focus->merge_bean_strings);
$ListView->setHeaderTitle($focus->merge_bean->name);

//leaving in dependency that there is a name column, needs to be changed
$ListView->setQuery($where, "", "", $bean_list_template_var);
$ListView->setAdditionalDetails(true);

$return_id = $focus->merge_bean->id;
$merge_module = $focus->merge_module;

$ListView->processListView($focus->merge_bean, "main", $bean_list_template_var);

$button_title = $current_module_strings['LBL_PERFORM_MERGE_BUTTON_TITLE'];
$button_key = $current_module_strings['LBL_PERFORM_MERGE_BUTTON_KEY'];
$button_label = $current_module_strings['LBL_PERFORM_MERGE_BUTTON_LABEL'];

$cancel_title=$app_strings['LBL_CANCEL_BUTTON_TITLE'];
$cancel_key=$app_strings['LBL_CANCEL_BUTTON_KEY'];
$cancel_label=$app_strings['LBL_CANCEL_BUTTON_LABEL'];

$error_select=$current_module_strings['LBL_SELECT_ERROR'];
$form_top = <<<EOQ

            <input type="hidden" id="selectCount" name="selectCount[]" value=0>
			<input type="hidden" name="merge_module" value="$merge_module">
			<input type="hidden" name="record" value="$return_id">
			<input type="hidden" name="return_module" value="$focus->merge_module">
			<input type="hidden" name="return_id" value="$return_id">
			<input type="hidden" name="return_action" value="DetailView">
			<input title="$button_title" accessKey="$button_key" class="button" onclick="return verify_selection(this);" type="submit" name="button" value="  $button_label  " >
            <input title="$cancel_title" accessKey="$cancel_key" class="button" onclick="this.form.action.value='DetailView';this.form.module.value='$focus->merge_module';this.form.module.record='$return_id'" type="submit" name="button" value=" $cancel_label   " >
		</form>
        <script>
           function verify_selection(theElement) {
                theElement.form.action.value='Step3';
                var selcount=document.getElementById('selectCount');
                if (parseInt(selcount.value) >0 ) {
                    return true;
                } else {
                    alert("$error_select");
                    return false;
                }
           }
        </script>
EOQ;
echo $form_top;
?>
