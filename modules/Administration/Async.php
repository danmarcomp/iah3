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
 * *******************************************************************************/
/*********************************************************************************

 * Description:
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc. All Rights
 * Reserved. Contributor(s): ______________________________________..
 *********************************************************************************/

require_once("include/entryPoint.php");

$json = getJSONObj();
$out = "";

switch($_REQUEST['adminAction']) {
	///////////////////////////////////////////////////////////////////////////
	////	REPAIRXSS
	case "refreshEstimate":
		include("include/modules.php"); // provide $moduleList
		$target = $_REQUEST['bean'];
		
		$count = 0;
		$toRepair = array();
		
		if($target == 'all') {
			$hide = array('Activities', 'Home', 'iFrames', 'Calendar', 'Dashboard');
		
			sort($moduleList);
			$options = array();
			
			foreach($moduleList as $module) {
				if(!in_array($module, $hide)) {
					$options[$module] = $module;
				}
			}

			foreach($options as $module) {
				if(!isset($beanFiles[$beanList[$module]]))
					continue;
				
				$file = $beanFiles[$beanList[$module]];
				
				if(!file_exists($file))
					continue;
					
				require_once($file);
				$bean = new $beanList[$module]();
				
				$q = "SELECT count(*) as count FROM {$bean->table_name}";
				$r = $bean->db->query($q);
				$a = $bean->db->fetchByAssoc($r);
				
				$count += $a['count'];
				
				// populate to_repair array
				$q2 = "SELECT id FROM {$bean->table_name}";
				$r2 = $bean->db->query($q2);
				$ids = '';
				while($a2 = $bean->db->fetchByAssoc($r2)) {
					$ids[] = $a2['id'];
				}
				$toRepair[$module] = $ids;
			}
		} elseif(in_array($target, $moduleList)) {
			require_once($beanFiles[$beanList[$target]]);
			$bean = new $beanList[$target]();
			$q = "SELECT count(*) as count FROM {$bean->table_name}";
			$r = $bean->db->query($q);
			$a = $bean->db->fetchByAssoc($r);
			
			$count += $a['count'];
			
			// populate to_repair array
			$q2 = "SELECT id FROM {$bean->table_name}";
			$r2 = $bean->db->query($q2);
			$ids = '';
			while($a2 = $bean->db->fetchByAssoc($r2)) {
				$ids[] = $a2['id'];
			}
			$toRepair[$target] = $ids;
		}
		
		$out = array('count' => $count, 'target' => $target, 'toRepair' => $toRepair);
	break;
	
	case "repairXssExecute":
		if(isset($_REQUEST['bean']) && !empty($_REQUEST['bean']) && isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
			include("include/modules.php"); // provide $moduleList
			$target = $_REQUEST['bean'];
			require_once($beanFiles[$beanList[$target]]);
			
			$ids = $json->decode(from_html($_REQUEST['id']));
			$count = 0;
			foreach($ids as $id) {
				if(!empty($id)) {
					$bean = new $beanList[$target]();
					$bean->retrieve($id);
					$bean->new_with_id = false;
					$bean->save(); // cleanBean() is called on save()
					$count++;
				}
			}
			
			$out = array('msg' => "success", 'count' => $count);
		} else {
			$out = array('msg' => "failure: bean or ID not defined");
		}
	break;
	////	END REPAIRXSS
	///////////////////////////////////////////////////////////////////////////
	
	default:
		die();
	break;	
}

$ret = $json->encode($out, true);
echo $ret;
