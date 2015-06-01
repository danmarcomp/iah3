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

/**
 * 
 * @return an XML string containing projects and task
 * @param $db Object
 * @param $sugarfields Object
 * @param $parameters array(
 * "SUGARVERSION"=>5
 * "DEBUG"
 * "CONDITIONS"
 * )
 */
function get_xml_sugar($db,$sugarfields,$parameters=array()){

if(isset($parameters["SUGARVERSION"])){
	$sugarversion = $parameters["SUGARVERSION"];
}else{
	$sugarversion = "5";
}
$sugarversion = '4';

if(!isset($parameters["DEBUG"])){
	$parameters["DEBUG"]=false;
}

$query = "select users.user_name,project.*
 from project,users
 where users.id=project.assigned_user_id
 and project.deleted=0";

if(isset($parameters["CONDITIONS"])){
	if(isset($parameters["CONDITIONS"]["project-name"])){
		$query.=" and project.name like '%".$parameters["CONDITIONS"]["project-name"]."%'";
	}
	
	if(isset($parameters["CONDITIONS"]["project-id"])){
		if(is_array($parameters["CONDITIONS"]["project-id"])){
			$project_id_in="(";
			foreach ($parameters["CONDITIONS"]["project-id"] as $v){
				$project_id_in.="'".addslashes($v)."',";
			}
			$project_id_in=substr($project_id_in,0,-1);
			$project_id_in.=")";
			$query.=" and project.id in ".$project_id_in." ";
		}else{
			$query.=" and project.id = '".addslashes($parameters["CONDITIONS"]["project-id"])."'";
		}
	}

	if(isset($parameters["CONDITIONS"]["users-id"])){
		if(is_array($parameters["CONDITIONS"]["users-id"])){
			$users_id_in="(";
			foreach ($parameters["CONDITIONS"]["users-id"] as $v){
				$users_id_in.="'".addslashes($v)."',";
			}
			$users_id_in=substr($users_id_in,0,-1);
			$users_id_in.=")";
			$query.=" and users.id in ".$users_id_in." ";
		}else{
			$query.=" and users.id = '".addslashes($parameters["CONDITIONS"]["users-id"])."'";
		}
	}
}

if($parameters["DEBUG"]){
	echo $query."<br>";
}

$result = array();

$res = $db->query($query);

while($rs=$db->fetchByAssoc($res)){

	$rs['tasks'] = array();
$result[$rs['id']] = $rs;

$query_tasks = "select *
 from project_task
 where project_task.parent_id = '".$rs["id"]."'
 and project_task.deleted=0
 ORDER BY order_number, milestone_flag DESC
 ";
 
if($parameters["DEBUG"]){
	echo $query_tasks."<br>";
}

$res_tasks = $db->query($query_tasks);

while($rs_tasks=$db->fetchByAssoc($res_tasks)){
	$result[$rs['id']]['tasks'][$rs_tasks['id']] = $rs_tasks;
}


}

//header("Content-type:text/xml");

if($parameters["DEBUG"]){
	echo "<pre>".print_r(htmlentities($result),true)."</pre>";
}
return $result;
}
?>
