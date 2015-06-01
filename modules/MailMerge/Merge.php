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
 * Created on Oct 7, 2005
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
require_once('soap/SoapHelperFunctions.php');
require_once('modules/MailMerge/MailMerge.php'); 
require_once('modules/Documents/Document.php'); 
require_once('modules/DocumentRevisions/DocumentRevision.php'); 
require_once('modules/Contacts/Contact.php'); 
require_once('include/upload_file.php');
require_once('XTemplate/xtpl.php');
require_once("data/Tracker.php");

global  $beanList, $beanFiles;

$xtpl = new XTemplate('modules/MailMerge/Merge.html');

$module = $_SESSION['MAILMERGE_MODULE'];
$document_id = $_SESSION['MAILMERGE_DOCUMENT_ID'];
$selObjs = urldecode($_SESSION['SELECTED_OBJECTS_DEF']);
$relObjs = (isset($_SESSION['MAILMERGE_RELATED_CONTACTS']) ? $_SESSION['MAILMERGE_RELATED_CONTACTS'] : '');

$relModule = $_SESSION['MAILMERGE_CONTAINS_CONTACT_INFO'];

if($_SESSION['MAILMERGE_MODULE'] == null)
{
	sugar_die("Error during Mail Merge process.  Please try again.");
}

$_SESSION['MAILMERGE_MODULE'] = null;
$_SESSION['MAILMERGE_DOCUMENT_ID'] = null;
$_SESSION['SELECTED_OBJECTS_DEF'] = null;
$_SESSION['MAILMERGE_SKIP_REL'] = null;
$_SESSION['MAILMERGE_CONTAINS_CONTACT_INFO'] = null;
$item_ids = array();
parse_str($selObjs,$item_ids);

if($module == 'CampaignProspects'){
    $module = 'Prospects';   
    if(!empty($_SESSION['MAILMERGE_CAMPAIGN_ID'])){
    	$targets = array_keys($item_ids);
    	require_once('modules/Campaigns/utils.php');
    	campaign_log_mail_merge($_SESSION['MAILMERGE_CAMPAIGN_ID'],$targets);
    }
}
$class_name = $beanList[$module];
$includedir = $beanFiles[$class_name];
require_once($includedir);
$seed = new $class_name(); 

$fields =  get_field_list($seed);

$document = new DocumentRevision();//new Document();
$document->retrieve($document_id);

if(isset($relModule)){
$rel_class_name = $beanList[$relModule ];
require_once($beanFiles[$rel_class_name]);
$rel_seed = new $rel_class_name();
}

$filter = array();
array_push($filter, 'link');

$merge_array = array();
$merge_array['master_module'] = $module;
$merge_array['related_module'] = $relModule;
$ids = array();

foreach($item_ids as $key=>$value)
{
	if(!empty($relObjs[$key]))
		{
		        $ids[$key] = $relObjs[$key];
				}
					else
						{
								$ids[$key] = '';
									}
									}
									$merge_array['ids'] = $ids;

$dataDir = getcwd() . '/' . AppConfig::cache_dir() . 'MergedDocuments/';
if(!file_exists($dataDir))
{
	mkdir($dataDir);
}
srand((double)microtime()*1000000); 
$mTime = microtime();
$dataFileName = 'sugardata'.$mTime.'.php';
write_array_to_file('merge_array', $merge_array, $dataDir.$dataFileName);
//Save the temp file so we can remove when we are done
$_SESSION['MAILMERGE_TEMP_FILE_'.$mTime] = $dataDir.$dataFileName;
$site_url = AppConfig::site_url();
$templateFile = $site_url.'/'.UploadFile::get_url(from_html($document->filename),$document->id);
$dataFile =$dataFileName;
$redirectUrl = 'index.php?action=index&step=5&module=MailMerge&mtime='.$mTime;
$startUrl = 'index.php?action=index&module=MailMerge&reset=true';

$relModule = trim($relModule);
$contents = "SUGARCRM_MAIL_MERGE_TOKEN#$templateFile#$dataFile#$module#$relModule";

$rtfFileName = 'sugartokendoc'.$mTime.'.doc';
$fp = fopen($dataDir.$rtfFileName, 'w');
fwrite($fp, $contents);
fclose($fp);

$xtpl->assign("MAILMERGE_FIREFOX_URL", $site_url . AppConfig::cache_dir() . 'MergedDocuments/'.$rtfFileName);
$xtpl->assign("MAILMERGE_START_URL", $startUrl);
$xtpl->assign("MAILMERGE_TEMPLATE_FILE", $templateFile);
$xtpl->assign("MAILMERGE_DATA_FILE", $dataFile);
$xtpl->assign("MAILMERGE_MODULE", $module);

$xtpl->assign("MAILMERGE_REL_MODULE", $relModule);

$xtpl->assign("MAILMERGE_REDIRECT_URL", $redirectUrl);
$xtpl->parse("main");
$xtpl->out("main");
?>
