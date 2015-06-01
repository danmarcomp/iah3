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

$db = & PearDatabase::getInstance();
$result = $db->query('SELECT * FROM fields_meta_data WHERE deleted = 0');
$fields = array();
$str = '';
while($row = $db->fetchByAssoc($result)){
	foreach($row as $name=>$value){
		$str.= "$name:::$value\n";	
	}
	$str .= "DONE\n";
}
ob_get_clean();

header("Content-Disposition: attachment; filename=CustomFieldStruct.sugar");
header("Content-Type: text/txt; charset={$app_strings['LBL_CHARSET']}");
header( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
header( "Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" );
header( "Cache-Control: post-check=0, pre-check=0", false );
if(! AppConfig::get_server_compression())
	header("Content-Length: ".strlen($str));
echo $str;
?>
