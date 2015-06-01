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
	//returns a list of components that are not of expected version
	function get_invalid_versions(){
		$invalid = array();
		
		require_once('modules/Versions/ExpectedVersions.php');
		require_once('modules/Versions/Version.php');
		
		global $db;
		$seed = new Version();
		$result = $db->query("SELECT * FROM `$seed->table_name` WHERE NOT deleted ORDER BY date_entered");
		$found = array();
		while($row = $db->fetchByAssoc($result)) {
			$vers = new Version();
			$vers->populateFromRow($row);
			$found[$vers->name] = $vers;
		}
		
		foreach($expect_versions as $expect){
			$name = $expect['name'];
			if(! isset($found[$name]) || ! $found[$name]->is_expected_version($expect))
				$invalid[$expect['name']] = $expect;
		}
		
		$seed->cleanup_list($found);
		$seed->cleanup();
		return $invalid;
	}

?>
