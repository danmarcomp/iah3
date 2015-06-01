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
 * $Header$
 * Description:
 ********************************************************************************/
$default_versions = array();


$new_db = & PearDatabase::getInstance();

$db_version = '2.5.1';
$dirName ='custom/include/language'; 
if(is_dir($dirName))
{
	$d = dir($dirName);
	while($entry = $d->read()) {
			 if ($entry != "." && $entry != "..") {
				// echo $dirName."/".$entry;
					  if (is_file($dirName."/".$entry) && substr($entry, -9)=='.lang.php') {
					$custom_lang_file = $dirName."/".$entry;
					  
	if(is_readable($custom_lang_file))
	{
		$pattern = '/\$app_list_strings[\ ]*=[\ ]*array/';
		$handle = @fopen($custom_lang_file, 'rt');
		$subject = fread($handle, filesize($custom_lang_file));
		fclose($handle);
		$matches = preg_match($pattern, $subject);
		if($matches > 0)
		{
			$db_version = '0';
		}
	}
	}}}
}
$default_versions[] = array('name'=>'Custom Labels', 'db_version' =>'3.0', 'file_version'=>'3.0');
$default_versions[] = array('name'=>'Chart Data Cache', 'db_version' =>'3.5.1', 'file_version'=>'3.5.1');
$default_versions[] = array('name'=>'htaccess', 'db_version' =>'3.5.1', 'file_version'=>'3.5.1');
$default_versions[] = array('name'=>'DST Fix', 'db_version' =>'3.5.1b', 'file_version'=>'3.5.1b');
$default_versions[] = array('name'=>'Rebuild Relationships', 'db_version' =>'5.3.1', 'file_version'=>'5.3.1');
$default_versions[] = array('name'=>'Rebuild Extensions', 'db_version' =>'4.0.0', 'file_version'=>'4.0.0');
$default_versions[] = array('name'=>'Studio Files', 'db_version' =>'4.5.0', 'file_version'=>'4.5.0');

// longreach - added
$default_versions[] = array('name'=>'Upgrade Quotes/Invoices', 'db_version' =>'4.2.2', 'file_version'=>'4.2.2');
$default_versions[] = array('name'=>'Email Bodies for Fulltext Search', 'db_version' =>'5.0.0', 'file_version'=>'5.0.0');
$default_versions['Products stock'] = array('name'=>'Products stock', 'db_version' =>'5.3.0', 'file_version'=>'5.3.0');
$default_versions['Bills Associations'] = array('name'=>'Bills Associations', 'db_version' =>'5.3.1', 'file_version'=>'5.3.1');
$default_versions['Default Software Product'] = array('name'=>'Default Software Product', 'db_version' =>'6.0.2', 'file_version'=>'6.0.2');
$default_versions['Credit Notes'] = array('name'=>'Credit Notes', 'db_version' =>'6.6.0', 'file_version'=>'6.6.0');
$default_versions['Customers/Vendors'] = array('name'=>'Customers/Vendors', 'db_version' =>'6.5.0', 'file_version'=>'6.5.0');
$default_versions['Recurring Services'] = array('name'=>'Recurring Services', 'db_version' =>'6.7.0', 'file_version'=>'6.7.0');
$default_versions['Invoice Gross Profit'] = array('name'=>'Invoice Gross Profit', 'db_version' =>'6.7.0', 'file_version'=>'6.7.0');

?>
