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

require_once('include/vCard.php');
require_once('include/utils.php');
require_once('include/utils/file_utils.php');
require_once('include/utils/zip_utils.php');

global $app_strings;


/**
 * @author longreach - Jason Eggers
 * The export will create a vCard file (.vcf) for each contact selected and put it
 * it in the cache\upload\upgrades\temp directory in a temporary folder. It will
 * then zip up all the vCards and return the zip file to the user. The temporary
 * folder will then be cleaned up and removed.
 */

$temp_dir = AppConfig::upload_dir() . "upgrades/temp";
//create temp directory
$zip_dir = mk_temp_dir( $temp_dir ); 
mkdir($zip_dir . '/vCards');

$zip_file_name = "ExportVCards.zip";

$ids = explode(';', $_POST['list_uids']);
if(count($ids) == 1 && $ids[0]) {
	$vcard = new vCard();
	if($vcard->loadRecord($ids[0], 'Contact')) {
		$vcard->saveVCard();
		sugar_cleanup(true);
	}
}
foreach($ids as $id){
	$vcard = new vCard();
	if(! $vcard->loadRecord($id, 'Contact'))
		continue;
    $filename = $zip_dir . "/vCards/{$vcard->name}.vcf";
    if (!$handle = fopen($filename, 'a')) {
         echo "Cannot open file ($filename)";
         exit;
    }

    // Write content to the file
    if (fwrite($handle, $vcard->toString()) === FALSE) {
        echo "Cannot write to file ($filename)";
        exit;
    }
	unset($handle);
	
}
zip_dir($zip_dir . '/vCards', $zip_dir . '/' . $zip_file_name);


$size = filesize($zip_dir.'/'.$zip_file_name);
$zip_file = file_get_contents($zip_dir.'/'.$zip_file_name); 

/*
//clean up temp directory
$d = dir($zip_dir . '/vCards'); 
while($entry = $d->read()) { 
	if ($entry!= "." && $entry!= "..") { 
		@unlink($zip_dir."/vCards/".$entry); 
	} 
} 
$d->close(); 
@unlink($zip_dir."/".$zip_file_name); 
rmdir($zip_dir . '/vCards'); 
rmdir($zip_dir); 
 */

header("Content-Disposition: attachment; filename=$zip_file_name");
header("Content-Type: application/octet-stream;");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" );
header("Cache-Control: max-age=0");
header("Pragma: public");
if(! AppConfig::get_server_compression())
	header("Content-Length: " . $size);

print $zip_file;
exit;
