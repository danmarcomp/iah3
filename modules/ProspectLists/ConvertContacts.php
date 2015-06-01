<?php
/*
 *
 * The contents of this file are subject to the info@hand Software License Agreement Version 1.3
 *
 * ("License"); You may not use this file except in compliance with the License.
 * You may obtain a copy of the License at <http://1crm.com/pdf/swlicense.pdf>.
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the
 * specific language governing rights and limitations under the License,
 *
 * All copies of the Covered Code must include on each user interface screen:
 * (i) the 1CRM copyright notice,
 * (ii) the "Powered by the 1CRM Engine" logo, 
 *
 * (iii) the "Powered by SugarCRM" logo, and
 * (iv) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.
 * See full license for requirements.
 *
 * The Original Code is : 1CRM Engine proprietary commercial code.
 * The Initial Developer of this Original Code is 1CRM Corp.
 * and it is Copyright (C) 2004-2012 by 1CRM Corp.
 *
 * All Rights Reserved.
 * Portions created by SugarCRM are Copyright (C) 2004-2008 SugarCRM, Inc.;
 * All Rights Reserved.
 *
 */


require_once('modules/ReportData/ReportData.php');
require_once('modules/ProspectLists/ProspectList.php');

global $current_user;


$name = $_POST['name'];
$list_type = $_POST['list_type'];
$reportdata_id = $_POST['reportdata_id'];
$convert_module = $_POST['convert_module'];
if($convert_module == "Targets") {
	$convert_module = "Prospects";
}

$report_data = new ReportData();
$report_data->retrieve($reportdata_id);
$results = $report_data->get_rows();
$contact_list = array();

foreach($report_data->sources_arr as $srcid => $src)
	if($src['type'] == 'primary')
		$primary_src = $srcid;
if(empty($primary_src))
	sugar_die("No primary source");

foreach($report_data->fields_arr as $fldid => $fld)
	if($fld['source'] == $primary_src && $fld['name'] == 'id')
		$module_id_field = $fldid;
if(empty($module_id_field))
	sugar_die("Error locating module ID field");
	
foreach($results as $row)
	$contact_list[] = $row[$module_id_field];

/** multi-method
if (!empty($_POST['selected'])) {
	
	foreach ($_POST['selected'] as $num => $dummy) {
		$list_id = $_POST['id'][$num];
		$focus = new ProspectList();
		$focus->retrieve($list_id);
		
		foreach($contact_list as $contact_id)
		{
			$focus->set_relationship('prospect_lists_prospects', array( 'related_id'=>$contact_id, 'related_type'=>$convert_module, 'prospect_list_id'=>$list_id ));
		}	
	}
} 
*/
echo "list id: ".$_POST['prospect_lists_id']."<br>";
if(!empty($_POST['prospect_lists_id'])) {
	$list_id = $_POST['prospect_lists_id'];
	$focus = new ProspectList();
	$focus->retrieve($list_id);
	if(empty($focus->assigned_user_id))
		$focus->assigned_user_id = $current_user->id;

	foreach($contact_list as $contact_id)
	{
		$focus->set_relationship('prospect_lists_prospects', array( 'related_id'=>$contact_id, 'related_type'=>$convert_module, 'prospect_list_id'=>$list_id ));
	}	
} elseif(!empty($name) && !empty($list_type)) {
	$focus = new ProspectList();
	$focus->name = $name;
	$focus->list_type = $list_type;
	$focus->save();
		foreach($contact_list as $contact_id)
		{
			$focus->set_relationship('prospect_lists_prospects', array( 'related_id'=>$contact_id, 'related_type'=>$convert_module, 'prospect_list_id'=>$focus->id ));
		}	
}


?>
<script type="text/javascript">
window.close();
</script>
