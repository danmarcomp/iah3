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
?><?php 
if(empty($_REQUEST['image'])) { ?>
<style type="text/css">

.main_container_gantt .task_status_not_started{
	background-color:#c6c6c6;
	color: #000;
	font-weight: bold;
}
.main_container_gantt .task_status_in_progress{
	background-color:#ff7600;
	color: #000;
	font-weight: bold;
}
.main_container_gantt .task_status_completed{
	background-color:#00ff00;
	color: #000;
	font-weight: bold;
}
.main_container_gantt .task_status_pending_input{
	background-color:#944400;
	color: #FFF;
	font-weight: bold;
}
.main_container_gantt .task_status_deferred{
	background-color:#575757;
	color: #FFF;
	font-weight: bold;
}

.main_container_gantt h1{
	margin: 10px;
}

.main_container_gantt .table_small  {
width:400px;
}

.main_container_gantt table  {

width:100%;

border-collapse: collapse;

font-size:12px;

margin-top:10px;

margin-bottom:10px;

background: white;

}

.main_container_gantt td  { 

/*background-color: #fff; */

padding: 2px; 
padding-left: 2px !important; 
padding-right: 2px !important; 
margin: 0px !important;

border: solid 1px #dcdcdc;

vertical-align: top;

}

.main_container_gantt th  { 

background-color: #eeeeee; 

padding: 4px; 

border: solid 1px #dcdcdc;

vertical-align: top;

}

</style>

<div class="main_container_gantt" id="main_container_gantt">
<?php
}
$html = '';

if(! AppConfig::setting('layout.svg_charts_enabled')) {
	if(! function_exists('gd_info'))
		sugar_die('Please install the GD PHP module (with FreeType support) to enable this functionality');
	if(! function_exists('imageftbbox'))
		sugar_die('PHP must be compiled with FreeType support enabled (--with-freetype-dir=DIR)');
}

require_once ('modules/Project/Project.php');
require_once("gantt.class.php");
require_once("gantt_image.php");
require_once("gantt.xmlgenerator.php");


$json = getJSONObj();


$html .= "<form action=\"\" method=\"POST\" name=\"Filter\" target=\"_blank\">
<input type=\"hidden\" name=\"image\" value=\"1\">
<input type=\"hidden\" name=\"to_pdf\" value=\"true\">
<table class=\"table_small\">";





/**
 * se l'utente può vedere anche gli altri progetti gli mostro i campi di selezione
 */
if(ACLController::checkAccess('ProjectTask', 'list', false)){

$html .= "<tr>";
$html .= "<th>".$mod_strings['LBL_KUMBEGANTT_FILTER_OWNER']."</th>";
$html .= "<td>";
$html .= "<select name=\"owner[]\" size=\"5\" multiple=\"multiple\">";

$users =  get_user_array(false);

$html .= get_select_options_with_id($users, (array)@$_POST['owner']);


$html .= "</select>";
$html .= "</td>";
$html .= "</tr>";

$html .= "<tr>";
$html .= "<th>".$mod_strings['LBL_KUMBEGANTT_FILTER_PROJECT']."</th>";
$html .= "<td>";



$popup_request_data = array(
	'call_back_function' => 'set_return',
	'form_name' => 'Filter',
	'field_to_name_array' => array(
		'id' => 'project_id',
		'name' => 'project_name',
	),
);

$proj_id = $proj_name = '';

if (!empty($_REQUEST['project_id'])) {
	$proj = new Project;
	$proj->retrieve($_REQUEST['project_id']);
	$proj_id = $proj->id;
	$proj_name = $proj->name;
}

$encoded_parent_popup_request_data = $json->encode($popup_request_data);

$html .= <<<EOQ
<input type="text" readonly="readonly" name="project_name" id="project_name"
	value="{$proj_name}" tabindex="2" /><input type="hidden" name="project_id" id="project_id"
	value="{$proj_id}" />&nbsp;<input
	title="{$app_strings['LBL_SELECT_BUTTON_TITLE']}"
	accessKey="{$app_strings['LBL_SELECT_BUTTON_KEY']}" type="button" class="button"
	value="{$app_strings['LBL_SELECT_BUTTON_LABEL']}" name="btn1" tabindex="2"
    onclick='open_popup("Project", 600, 400, "", true, false, $encoded_parent_popup_request_data);'
EOQ;

$html .= "</td>";
$html .= "</tr>";


}

if (!empty($_POST)) {
	$per_project = $_POST['task_per_progetto'] ? 'checked="checked"' : '';
	$per_resource = $_POST['task_per_risorsa'] ? 'checked="checked"' : '';
} else {
	$per_project = $per_resource = 'checked="checked"';
}

$html .= "
<tr>
<th>".$mod_strings['LBL_KUMBEGANTT_TASK_BY_PROJECT']."</th>
<td><input type=\"checkbox\" name=\"task_per_progetto\" value=\"on\" $per_project /></td>
</tr>
<tr>
<th>".$mod_strings['LBL_KUMBEGANTT_TASK_BY_RESOURCE']."</th>
<td><input type=\"checkbox\" name=\"task_per_risorsa\" value=\"on\" $per_resource  /></td>
</tr>
<tr>
<td colspan=\"2\"><input type=\"submit\" value=\"".$mod_strings['LBL_KUMBEGANTT_VIEW']."\" /></td>
</tr>
</table>
</form>
";

if(!empty($proj_id)){

$sugarfields=array();
// fields used in sugar 4.x
$sugarfields["4"]["task-inizio"]	="date_start";
$sugarfields["4"]["task-fine"]		="date_due";
$sugarfields["4"]["task-parent"]	="parent_id";

// fields used in sugar 5.x
$sugarfields["5"]["task-inizio"]	="date_start";
$sugarfields["5"]["task-fine"]		="date_finish";
$sugarfields["5"]["task-parent"]	="project_id";

//$g = new Gantt(file_get_contents("task.xml"));
$array_conditions = array();

/**
 * se l'utente può vedere anche gli altri progetti applico le condizioni di filtro
 * altrimenti gli consento di vedere solo i suoi progetti / task
 */
if(ACLController::checkAccess('ProjectTask', 'list', false)){

	if(isset($_POST["owner"])){
		$array_conditions["task-users-id"]=$_POST["owner"];
	}

	if(!empty($proj_id)){
		$array_conditions["project-id"]=$proj_id;
	}

}else{
	// forzo il limite ai progetti miei
	$array_conditions["task-users-id"]=$current_user->id;
}


$xml = get_xml_sugar($db,$sugarfields,array("SUGARVERSION"=>"5","DEBUG"=>false,"CONDITIONS"=>$array_conditions));
$parameters = array("DEBUG"=>false,"LABELS"=>$mod_strings);


if (empty($_REQUEST['image'])) {
	$g = new Gantt($xml,$parameters);
	$html .= $g->get_HTML_legenda();
	if($per_resource){
		$html .= $g->get_HTML_riepilogo_risorse();
	}
	if($per_project){
			$html .= $g->get_HTML_riepilogo_progetti();
	}
	echo $html;
} else {

	if (!empty($_REQUEST['move'])) {
		require_once 'modules/ProjectTask/ProjectTask.php';
		$task = new ProjectTask;
		if ($task->retrieve($_REQUEST['move']) && $task->parent_id == $proj_id) {
			$tasks = array();
			$query = "SELECT id FROM project_task WHERE parent_id='$proj_id' AND deleted = 0 ORDER BY order_number";
			$res = $task->db->query($query);
			while ($row = $task->db->fetchByAssoc($res)) {
				$tasks[] = $row['id'];
			}
			$n = array_search($task->id, $tasks);
			if ($_REQUEST['dir'] == 'up') {
				$k = $n - 1;
			} else {
				$k = $n + 1;
			}
			if (isset($tasks[$k])) {
				$tmp = $tasks[$n];
				$tasks[$n] = $tasks[$k];
				$tasks[$k] = $tmp;
				$i = 1;
				foreach ($tasks as $task_id) {
					$query = "UPDATE project_task SET order_number = $i WHERE id='$task_id'";
					$task->db->query($query);
					$i++;
				}
				header('Location:index.php?module=Project&action=gantt&image=1&to_pdf=true&project_id=' . $proj_id);
				exit;
			}
		}
	}

	global $theme;
	require_once('themes/'.$theme.'/layout_utils.php');
	require_once 'XTemplate/xtpl.php';
	$tpl = new XTemplate('modules/Project/gantt.html');
	$tpl->assign('APP', $app_strings);
	$tpl->assign('PROJECT_ID', $proj_id);
	insert_popup_header($theme);

	$backend = AppConfig::setting('layout.svg_charts_enabled') ? 'SVG' : 'GD';

	$g = new GanttImage($xml, $backend, $parameters);
	
	$path = 'gantt/' . substr($proj_id, 0,1) . '/' . substr($proj_id, 1, 1) . '/' .  $proj_id . '.' . $g->image->fileExt(); 
	create_cache_directory($path);
	$filename = AppConfig::cache_dir() . $path;
	$g->draw();

	$g->image->render($filename);
	$tpl->assign('IMAGE_HTML', $g->image->renderHTML($filename));
	foreach ($g->imageMap as $area) {
		$tpl->assign('area', $area);
		$tpl->parse('main.GD.' . $area['type']);
	}
	$tpl->parse('main.GD');
	$tpl->parse('main');
	$tpl->out('main');
	insert_popup_footer();
	return;
}

} else {
	echo $html;
}

if (empty($_REQUEST['image'])) {
?>
</div>
<?php 
}

if (empty($proj_id) && !empty($_REQUEST['image'])) {
?>
	<script type="text/javascript">
	window.close();
	</script>
<?php
}

