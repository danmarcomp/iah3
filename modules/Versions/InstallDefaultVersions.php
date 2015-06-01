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



require_once('modules/Versions/Version.php');
require_once('modules/Versions/DefaultVersions.php');

foreach($default_versions as $default_version){
	
	$version = new Version();
	$query="select count(*) the_count from versions where name='{$default_version['name']}'";
	$result=$version->db->query($query);
	$row=$version->db->fetchByAssoc($result);
	if ($row== null or $row['the_count'] ==0) {
		$version->name = $default_version['name'];
		$version->file_version = $default_version['file_version'];
		$version->db_version = $default_version['db_version'];
		$version->save();
	}
}

?>
