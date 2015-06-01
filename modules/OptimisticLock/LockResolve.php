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
function display_conflict_between_objects($object_1, $object_2, $field_defs,$module_dir, $display_name){
	global  $odd_bg, $even_bg;


	$title = '<tr><td class="listViewThS1">&nbsp;</td>';
	$object1_row= '<tr class="oddListRowS1" bgcolor="'.$odd_bg.'"><td><b>Yours</b></td>';
	$object2_row= '<tr class="evenListRowS1" bgcolor="'.$even_bg.'"><td><b>In Database</b></td>';
	$exists = false;
	
	foreach( $object_1 as  $name=>$value)
	{
		if(isset($value) ){
			
			if( $value != $object_2[$name]){
				
				$title .= '<td class="listViewThS1"><b>&nbsp;' . translate($field_defs[$name]['vname'], $module_dir). '</b></td>';
				$object1_row .= '<td>&nbsp;' . $value. '</td>';
			
				$object2_row .= '<td>&nbsp;' . $object_2[$name]. '</td>';
				$exists = true;
			}
		}
			
	}	
	if($exists){
		echo "<b>A Conflict Exists For - <a href='index.php?action=DetailView&module=$module_dir&record={$object_1['id']}'  target='_blank'>$display_name</a> </b> <br><table  class='listView' border='0' cellspacing='0' cellpadding='2'>$title<td class='listViewThS1' >&nbsp;</td></tr>$object1_row<td><a href='index.php?&module=OptimisticLock&action=LockResolve&save=true'>Accept Your's</a></td></tr>$object2_row<td><a href='index.php?&module=$object_2->module_dir&action=DetailView&record=$object_2->id'>Accept Database</a></td></tr><tr><td colspan='20' class='listViewHRS1'></td></tr></table><br>";
	}else{
		echo "<b>Records Match</b><br>";
	}	
}

if(isset($_SESSION['o_lock_object'])){
	global $beanFiles, $moduleList;
	$object = 	$_SESSION['o_lock_object'];
	require_once($beanFiles[$beanList[$_SESSION['o_lock_module']]]);
	$current_state = new $_SESSION['o_lock_class']();
	$current_state->retrieve($object['id']);
	
	if(isset($_REQUEST['save'])){
		$_SESSION['o_lock_fs'] = true;
		echo  $_SESSION['o_lock_save'];
		die();
	}else{
		display_conflict_between_objects($object, $current_state->toArray(),$current_state->field_defs, $current_state->module_dir, $_SESSION['o_lock_class']);
}}else{
	echo 'No Locked Objects';	
}
	
?>
