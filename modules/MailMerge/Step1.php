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
/*
 * Created on Oct 4, 2005
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
require_once('XTemplate/xtpl.php');
require_once("data/Tracker.php");
require_once('themes/'.$theme.'/layout_utils.php');

require_once('modules/MailMerge/modules_array.php');
require_once('modules/Documents/Document.php');
require_once('include/json_config.php');
$json_config = new json_config();

global $app_strings;
global $app_list_strings;
global $mod_strings;
global $current_user;
global  $beanList, $beanFiles;

$xtpl = new XTemplate('modules/MailMerge/Step1.html');
$xtpl->assign("MOD", $mod_strings);
$xtpl->assign("APP", $app_strings);
$xtpl->assign('JSON_CONFIG_JAVASCRIPT', $json_config->get_static_json_server(false, true));

$module_list = $modules_array;

if(isset($_REQUEST['reset']) && $_REQUEST['reset'])
{
	$_SESSION['MAILMERGE_MODULE'] = null;
	$_SESSION['MAILMERGE_DOCUMENT_ID'] = null;
	$_SESSION['SELECTED_OBJECTS_DEF'] = null;	
	$_SESSION['MAILMERGE_SKIP_REL'] = null;
	$_SESSION['MAILMERGE_RECORD'] = null;
	$_SESSION['MAILMERGE_RECORDS'] = null;
	$_SESSION['MAILMERGE_CONTAINS_CONTACT_INFO'] = null;
}
$fromListView = false;
if(!empty($_REQUEST['record']))
{
	$_SESSION['MAILMERGE_RECORD'] = $_REQUEST['record'];	
}
else if(isset($_REQUEST['uid'])) {
	$_SESSION['MAILMERGE_RECORD'] = explode(',', $_REQUEST['uid']);
    
}
else if(isset($_REQUEST['entire']) && $_REQUEST['entire'] == 'true') {
	// do entire list
	$focus = 0;

	$bean = $beanList[ $_SESSION['MAILMERGE_MODULE']];
	require_once($beanFiles[$bean]);
	$focus = new $bean;
	
	if(isset($_SESSION['export_where']) && !empty($_SESSION['export_where'])) { // bug 4679
		$where = $_SESSION['export_where'];
	} else {
		$where = '';
	}
    $beginWhere = substr(trim($where), 0, 5);
    if ($beginWhere == "where")
        $where = substr(trim($where), 5, strlen($where));
	$query = $focus->create_export_query($order_by,$where);
	
	$result = $db->query($query,true,"Error mail merging {$_SESSION['MAILMERGE_MODULE']}: "."<BR>$query");

	$new_arr = array();
	while($val = $db->fetchByAssoc($result,-1,false))
	{
		array_push($new_arr, $val['id']);
	}
	$_SESSION['MAILMERGE_RECORD'] = $new_arr;
}
else if(isset($_SESSION['MAILMERGE_RECORDS']))
{

	$fromListView = true;
	$_SESSION['MAILMERGE_RECORD'] = $_SESSION['MAILMERGE_RECORDS'];
	$_SESSION['MAILMERGE_RECORDS'] = null;
}

if(isset($_SESSION['MAILMERGE_RECORD']))
{
	if(!empty($_POST['return_module']) && $_POST['return_module'] != "MailMerge")
	{
		$rModule = $_POST['return_module'];
	}
	else if($fromListView)
	{
		$rModule = 	$_SESSION['MAILMERGE_MODULE_FROM_LISTVIEW'];
		$_SESSION['MAILMERGE_MODULE_FROM_LISTVIEW'] = null;
	}
	else
	{
		$rModule = $_SESSION['MAILMERGE_MODULE'];
	}

	$_SESSION['MAILMERGE_MODULE'] = $rModule;
	if(!empty($rModule) && $rModule != "MailMerge")
	{
	$class_name = $beanList[$rModule];
	$includedir = $beanFiles[$class_name];
	require_once($includedir);
	$seed = new $class_name();

	$selected_objects = '';
	foreach($_SESSION['MAILMERGE_RECORD'] as $record_id)
	{

    		if($rModule == 'Campaigns'){
    			require_once('modules/Prospects/Prospect.php');
    			$prospect = new Prospect();
    			$prospect_module_list = array('leads', 'contacts', 'prospects', 'users');
    			foreach($prospect_module_list as $mname){
	    			$pList = $prospect->retrieveTargetList("campaigns.id = '$record_id' AND related_type = #$mname#", array('id', 'first_name', 'last_name'));
	    			foreach($pList['list'] as $bean){
	    				$selected_objects .= $bean->id.'='.str_replace("&", "##", $bean->name).'&';
	    			}
    			}
    		}else{
		$seed->retrieve($record_id);	
		$selected_objects .= $record_id.'='.str_replace("&", "##", $seed->name).'&';
			}
	}	

	if($rModule != 'Contacts' && $rModule != 'Leads' && $rModule != 'Products' && $rModule != 'Campaigns' && $rModule != 'Projects')
	{
		$_SESSION['MAILMERGE_SKIP_REL'] = false;
		$xtpl->assign("STEP", "2");
		$xtpl->assign("SELECTED_OBJECTS", $selected_objects);
		$_SESSION['SELECTED_OBJECTS_DEF'] = $selected_objects;
	}
	else
	{
		$_SESSION['MAILMERGE_SKIP_REL'] = true;
		$xtpl->assign("STEP", "2");
		$_SESSION['SELECTED_OBJECTS_DEF'] = $selected_objects;
	}
}
else
{
	$xtpl->assign("STEP", "2");
}

}
else
{
	$xtpl->assign("STEP", "2");
}
$modules = $module_list;

$xtpl->assign("MAILMERGE_MODULE_OPTIONS", get_select_options_with_id($modules, $_SESSION['MAILMERGE_MODULE']));
$xtpl->assign("MAILMERGE_TEMPLATES", get_select_options_with_id(getDocumentRevisions(), '0'));

if(isset($_SESSION['MAILMERGE_MODULE'])){
	$module_select_text = $mod_strings['LBL_MAILMERGE_SELECTED_MODULE'];
	$xtpl->assign("MAILMERGE_NUM_SELECTED_OBJECTS",count($_SESSION['MAILMERGE_RECORD'])." ".$_SESSION['MAILMERGE_MODULE']." Selected");
}
else{
	$module_select_text = $mod_strings['LBL_MAILMERGE_MODULE'];
}
$xtpl->assign("MODULE_SELECT", $module_select_text);

$user_merge = $current_user->getPreference('mailmerge_on');
if ($user_merge != 'on' || ! AppConfig::setting('mailmerge.enabled')){
	$xtpl->assign("ADDIN_NOTICE", $mod_strings['LBL_ADDIN_NOTICE']);
	$xtpl->assign("DISABLE_NEXT_BUTTON", "disabled");
}


$xtpl->parse("main");
$xtpl->out("main");

function get_user_module_list($user){
	global $app_list_strings, $current_language;
	$app_list_strings = return_app_list_strings_language($current_language);
	$modules = query_module_access_list($user, true);
	return $modules;
}

function getDocumentRevisions()
{			
	$document = new Document();

	$currentDate = gmdate("Y-m-d H:i:s");
	if ($document->db->dbType=="mysql") {
		$empty_date=db_convert("'0000-00-00'", 'datetime');
	}

	else {
		$empty_date=db_convert("'1970-01-01 00:00:00'", 'datetime');
	}




	
			$query = "SELECT revision, document_name, document_revisions.id FROM document_revisions
LEFT JOIN documents on documents.id = document_revisions.document_id WHERE ((active_date <= ".db_convert("'".$currentDate."'", 'datetime')." AND exp_date > ".db_convert("'".$currentDate."'", 'datetime').") OR (active_date is NULL) or (active_date = ".$empty_date.") or (active_date <= ".db_convert("'".$currentDate."'", 'datetime')." AND ((exp_date = ".$empty_date.") OR (exp_date is NULL)))) AND is_template = 1 AND template_type = 'mailmerge' AND documents.deleted = 0 ORDER BY document_name";

			$result = $document->db->query($query,true,"Error retrieving $document->object_name list: ");

                        $list = Array();
                        $list['None'] = 'None';
                        while(($row = $document->db->fetchByAssoc($result)) != null)
                            {
                                $revision = null;
                                $docName = $row['document_name'];
                                $revision = $row['revision'];
                                if(!empty($revision));
                                {
                                        $docName .= " (rev. ".$revision.")";
                                }
                                $list[$row['id']] = $docName;
                            }
                        return $list;

}
?>
