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

require_once('modules/ProspectLists/ProspectList.php');


global $mod_strings;


$focus = new ProspectList();

$focus->retrieve($_POST['record']);
if (isset($_POST['isDuplicate']) && $_POST['isDuplicate'] == true) {

	$focus->id='';
	$focus->name=$mod_strings['LBL_COPY_PREFIX'].' '.$focus->name;
	
	$focus->save();
	$return_id=$focus->id; 
	//duplicate the linked items.
	$query  = "select * from prospect_lists_prospects where prospect_list_id = '".$_POST['record']."'";
	$result = $focus->db->query($query);
	if ($result != null) {
	
		while(($row = $focus->db->fetchByAssoc($result)) != null) {
			$iquery ="INSERT INTO prospect_lists_prospects (id,prospect_list_id, related_id, related_type,date_modified) ";
			$iquery .= "VALUES ("."'".create_guid()."',"."'".$focus->id."',"."'".$row['related_id']."',"."'".$row['related_type']."',"."'".gmdate("Y-m-d H:i:s")."')";
			$focus->db->query($iquery); //save the record.	
		}	
	}
}
header("Location: index.php?action=DetailView&module=ProspectLists&record=$return_id");
?>
