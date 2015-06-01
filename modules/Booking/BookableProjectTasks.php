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
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');


require_once('modules/ProjectTask/ProjectTask.php');

class BookableProjectTasks extends ProjectTask {
	
	function create_new_list_query($order_by, $where,$filter=array(),$params=array(), $show_deleted = 0,$join_type='', $return_array = false, $parentbean, $singleSelect = false) {
		$uid = $GLOBALS['db']->quote($GLOBALS['current_user']->id);
		$query = array(
			'select' => "SELECT DISTINCT project_task.*, project.name AS parent_name ",
			'from' => "FROM project_task ".
				"LEFT JOIN project ON project.id=project_task.parent_id AND NOT project.deleted ".
				"LEFT JOIN projecttasks_users ptu ON project_task.id=ptu.projecttask_id AND ptu.user_id='$uid' AND NOT ptu.deleted ",
			'from_min' => "FROM project_task",
			'where' => "WHERE ptu.booking_status='Active' ".
				"AND NOT project_task.deleted AND project_task.status='In Progress' ".
				"AND project.project_phase='Active - In Progress' ",
			'order_by' => $order_by ? "ORDER BY $order_by " : '',
			'group_by' => '',
		);
		if(! $return_array)
			$query = $query['select'] . $query['from'] . $query['where'] . $query['order_by'];
		return $query;
	}

}

?>
