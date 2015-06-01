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
class Gantt{
	
	private $xml = null;
	private $min_date = null;
	private $max_date = null;
	private $debug;
	/***
	 * 
	 * @return 
	 * @param $xmldata Object
	 * @param $parameters array(
	 * "DEBUG"
	 * "LABELS"
	 * )
	 */
	function __construct($xmldata,$parameters=array()){
		
		if(!isset($parameters["DEBUG"])){
			$this->debug=false;
		}else{
			$this->debug=$parameters["DEBUG"];
		}
		
		$this->app_strings = $parameters["LABELS"];
		
		$this->xml = new DOMDocument();
		$this->xml->loadXML($xmldata);
		
		$this->min_date = $this->get_min_date();
		
		if($this->debug){
			echo "Data minore = ".date("d/m/Y",$this->min_date)."<br>";
		}

		$this->max_date = $this->get_max_date();

		if($this->debug){
			echo "Data maggiore = ".date("d/m/Y",$this->max_date)."<br>";
		}
	
	}
	
	function data($datadaformattare,$formattazione="d/m/Y"){

		return date($formattazione,$datadaformattare);
	}
	
	function get_array_giorni(){
		$result = array();
		
		for($i=$this->min_date;$i<$this->max_date;$i+=86400){
			$result[$i]=$this->data($i,"d");
		}
		
		return $result;
	}
	
	function get_risorse(){

		$xpath = new DOMXPath($this->xml);
		
		$risorse = $xpath->query("//progetti/progetto/tasks/task/risorsa");
		
		$array_risorse = array();

		foreach($risorse as $risorsa){
			if(!isset($array_risorse[$risorsa->nodeValue])){
				$array_risorse[$risorsa->nodeValue] = $risorsa->nodeValue;
			}
		}
		
		return $array_risorse;
				
	}

	function get_progetti(){

		$xpath = new DOMXPath($this->xml);
		
		$progetti = $xpath->query("//progetti/progetto");
		
		
		$array_progetti = array();

		foreach($progetti as $progetto){
						
			$prog = array();
			$prog["id"]=$progetto->getElementsByTagName("id")->item(0)->nodeValue;
			$prog["nome"]=$progetto->getElementsByTagName("nome")->item(0)->nodeValue;
			$prog["descrizione"]=$progetto->getElementsByTagName("descrizione")->item(0)->nodeValue;
			$prog["inizio"]=strtotime($progetto->getElementsByTagName("inizio")->item(0)->nodeValue);
			$prog["fine"]=strtotime($progetto->getElementsByTagName("fine")->item(0)->nodeValue);
			$prog["stato"]=$progetto->getElementsByTagName("stato")->item(0)->nodeValue;
			array_push($array_progetti,$prog);
		}
		
		return $array_progetti;
				
	}
	
	function get_tasks_risorsa($risorsa){

		$xpath = new DOMXPath($this->xml);
		
		$tasks = $xpath->query("//progetti/progetto/tasks/task[./risorsa=\"$risorsa\"]");
		
		
		$array_task = array();

		foreach($tasks as $task){
						
			$attivita = array();
			$attivita["id"]=$task->getElementsByTagName("id")->item(0)->nodeValue;
			$attivita["nome"]=$task->getElementsByTagName("nome")->item(0)->nodeValue;
			$attivita["progetto_nome"]=$task->parentNode->parentNode->getElementsByTagName("nome")->item(0)->nodeValue;
			$attivita["progetto_id"]=$task->parentNode->parentNode->getElementsByTagName("id")->item(0)->nodeValue;
			$attivita["inizio"]=strtotime($task->getElementsByTagName("inizio")->item(0)->nodeValue);
			$attivita["fine"]=strtotime($task->getElementsByTagName("fine")->item(0)->nodeValue);
			$attivita["stato"]=$task->getElementsByTagName("stato")->item(0)->nodeValue;
			$attivita["percentuale_utilizzo"]=$task->getElementsByTagName("percentuale_utilizzo")->item(0)->nodeValue;
			array_push($array_task,$attivita);
		}
		
		return $array_task;
				
	}

	function get_tasks_progetto($progetto){

		$xpath = new DOMXPath($this->xml);
		
		$tasks = $xpath->query("//progetti/progetto[./id=\"$progetto\"]/tasks/task");
		
		
		$array_task = array();

		foreach($tasks as $task){
						
			$attivita = array();
			$attivita["nome"]=$task->getElementsByTagName("nome")->item(0)->nodeValue;
			$attivita["risorsa"]=$task->getElementsByTagName("risorsa")->item(0)->nodeValue;
			$attivita["progetto_nome"]=$task->parentNode->parentNode->getElementsByTagName("nome")->item(0)->nodeValue;
			$attivita["inizio"]=strtotime($task->getElementsByTagName("inizio")->item(0)->nodeValue);
			$attivita["fine"]=strtotime($task->getElementsByTagName("fine")->item(0)->nodeValue);
			$attivita["stato"]=$task->getElementsByTagName("stato")->item(0)->nodeValue;
			$attivita["percentuale_utilizzo"]=$task->getElementsByTagName("percentuale_utilizzo")->item(0)->nodeValue;
			$attivita["percentuale_completamento"]=$task->getElementsByTagName("percentuale_completamento")->item(0)->nodeValue;
			array_push($array_task,$attivita);
		}
		
		if($this->debug){
			echo "risorse per il progetto $progetto<br>";
			echo "<pre>".print_r($array_task,true)."</pre>";
		}
		
		return $array_task;
				
	}	
	
	/**
	 * quando $perc_completamento è 0 stampo il rosso
	 * quando $perc_completamento è 1 stampo il verde
	 * @return 
	 * @param $perc_completamento Object
	 */
	function get_STYLE_by_percentuale_completamento($perc_completamento){
		if($perc_completamento>100){
			$perc_completamento=100;
		}
		if($perc_completamento<0){
			$perc_completamento=0;
		}
		$perc_completamento = $perc_completamento/100;
				
		$verde = (int)(255*$perc_completamento);
		$rosso = (int)(255*(1-$perc_completamento));
		
		$style = "background-color:rgb($rosso,$verde,0);";
		
		if($this->debug){
			echo $style."<br>";
		}
		return $style;
	}

	function get_STYLE_by_percentuale_utilizzo($perc_utilizzo){
		
		if($perc_utilizzo>100){
			$perc_utilizzo=100;
		}
		if($perc_utilizzo<0){
			$perc_utilizzo=0;
		}
		
		$perc_utilizzo = $perc_utilizzo/100;
		
		$rosso = (int)(255*$perc_utilizzo);
		$verde = (int)(255*(1-$perc_utilizzo));
		
		$style = "background-color:rgb($rosso,$verde,0);";
		
		if($this->debug){
			echo $style."<br>";
		}
		return $style;
	}
	
	function get_CLASS_by_stato($stato){
		$class = "";
		switch($stato){
			case "Not Started":$class="task_status_not_started";break;
			case "In Progress":$class="task_status_in_progress";break;
			case "Completed":$class="task_status_completed";break;
			case "Pending Input":$class="task_status_pending_input";break;
			case "Deferred":$class="task_status_deferred";break;
		}
		return $class;
	}
	
	function get_HTML_legenda(){
		return "<table class=\"table_small\">
		<tr>
		<th>".$this->app_strings['LBL_KUMBEGANTT_Not Started']."</th><td class=\"".$this->get_CLASS_by_stato("Not Started")."\">&nbsp;</td>
		</tr>
		<tr>
		<th>".$this->app_strings['LBL_KUMBEGANTT_In Progress']."</th><td class=\"".$this->get_CLASS_by_stato("In Progress")."\">&nbsp;</td>
		</tr>
		<tr>
		<th>".$this->app_strings['LBL_KUMBEGANTT_Completed']."</th><td class=\"".$this->get_CLASS_by_stato("Completed")."\">&nbsp;</td>
		</tr>
		<tr>
		<th>".$this->app_strings['LBL_KUMBEGANTT_Pending Input']."</th><td class=\"".$this->get_CLASS_by_stato("Pending Input")."\">&nbsp;</td>
		</tr>
		<tr>
		<th>".$this->app_strings['LBL_KUMBEGANTT_Deferred']."</th><td class=\"".$this->get_CLASS_by_stato("Deferred")."\">&nbsp;</td>
		</tr>
		</table>";
		
	}
	
	function get_HTML_riepilogo_progetti(){

		global $theme;

		$blank = "<img src=\"themes/$theme/images/blank.gif\" style=\"width:14px;\">";
		$blank2 = "<img src=\"themes/$theme/images/blank.gif\" style=\"width:1px;\">";

		$giorni = $this->get_array_giorni();
		$progetti = $this->get_progetti();
		$result = "<h1>".$this->app_strings['LBL_KUMBEGANTT_TASK_BY_PROJECT']."</h1>";		
		
		$result .= "<table border=1>
		<tr>
		<th>".$this->app_strings['LBL_KUMBEGANTT_TASK']."</th>
		<th>".$this->app_strings['LBL_KUMBEGANTT_RESOURCE']."</th>
		<th>".$this->app_strings['LBL_KUMBEGANTT_STATUS']."</th>
		<th>".$this->app_strings['LBL_KUMBEGANTT_PERC_COMPLETE']."</th>
		<th>".$this->app_strings['LBL_KUMBEGANTT_START']."</th>
		<th>".$this->app_strings['LBL_KUMBEGANTT_FINISH']."</th>
		";

		$count = count($giorni);
		$cs = ($vcount == 1) ? 2 : $count;

		$i = 0;
		foreach($giorni as $k=>$v){
			$date = date("d/m", $k);
			if (!$i) {
				$result.="<th style=\"text-align:left\"colspan=\"$cs\" >$date</th>";
			} elseif ($i == $count - 1) {
				$result.="<th style=\"text-align:right\" colspan=\"$cs\">$date</th>";
			}
			$i ++;
		}
		$result.="</tr>";
		
		foreach($progetti as $progetto){
			
			
			$tasks = $this->get_tasks_progetto($progetto["id"]);
			
			if(sizeof($tasks)>0){
				
			$result.="<tr><td colspan=\"6\"><a class=\"listViewTdLinkS1\" href=\"index.php?module=Project&action=DetailView&record={$progetto['id']}\">".$progetto["nome"]."</a></td><td colspan=\"".(sizeof($giorni)*2)."\">&nbsp;</td></tr>";

			foreach($tasks as $task){
				
				$result.="<tr>
						<td><a class=\"listViewTdLinkS1\" href=\"index.php?module=ProjectTask&action=DetailView&record={$task['id']}\">".$task["nome"]."</a></td>
						<td>".$task["risorsa"]."</td>
						<td class=\"".$this->get_CLASS_by_stato($task["stato"])."\">".$this->app_strings['LBL_KUMBEGANTT_'.$task["stato"]]."</td>
						<td>".$task["percentuale_completamento"]."%</td>
						<td>".$this->data($task["inizio"])."</td>
						<td>".$this->data($task["fine"])."</td>
						";
				foreach($giorni as $k=>$v){
					$date = date("d/m", $k);
		
					if($this->debug){
						echo "verifico se ".$task["nome"]." che dal ".$this->data($task["inizio"])." al ".$this->data($task["fine"])." al $k e >= di ".$task["inizio"]." e <= di ".$task["fine"]." === ";
					}
					if($k>=$task["inizio"]&&$k<=$task["fine"]){
						if (date("Y-m-d", $k) == date("Y-m-d")) {
							$result.="<td style=\"".$this->get_STYLE_by_percentuale_completamento($task["percentuale_completamento"])."; border-right: dotted 2px black;\" title=\"$date\" >$blank2</td>";
							$result.="<td style=\"".$this->get_STYLE_by_percentuale_completamento($task["percentuale_completamento"])."; \"  title=\"$date\">$blank2</td>";
						} else {
							$result.="<td colspan=\"2\" style=\"".$this->get_STYLE_by_percentuale_completamento($task["percentuale_completamento"])."; width:10px;\"  title=\"$date\">$blank</td>";
						}

						if($this->debug){
							echo "OK";
						}

					}else{
						if (date("Y-m-d", $k) == date("Y-m-d")) {
							$result.="<td style=\"border-right: dotted 2px black;\"  title=\"$date\">$blank2</td>";
							$result.="<td  title=\"$date\" >$blank2</td>";
						} else {
							$result.="<td colspan=\"2\" style=\"width:10px;\" title=\"$date\" >$blank</td>";
						}

						if($this->debug){
							echo "FUORI";
						}
					}
					if($this->debug){
						echo "<br/>";
					}
				}
						
				$result.="</tr>";				
				
			}
				
			}

						
		}
		
		
		$result .= "</table>";
		
		return $result;
	}
	
	/**
	 * 
	 * @return 
	 * @param $parameters array(
	 * 
	 * )
	 */
	function get_HTML_riepilogo_risorse($parameters=array()){

		global $theme;
		$result="";
		$risorse = $this->get_risorse();

		$blank = "<img src=\"themes/$theme/images/blank.gif\" style=\"width:14px;\">";
		$blank2 = "<img src=\"themes/$theme/images/blank.gif\" style=\"width:1px;\">";

		$giorni = $this->get_array_giorni();

		$result = "<h1>".$this->app_strings['LBL_KUMBEGANTT_TASK_BY_RESOURCE']."</h1>";		
		
		$result.="<table border=1>
		<tr>
		<th>".$this->app_strings['LBL_KUMBEGANTT_PROJECT']."</th>
		<th>".$this->app_strings['LBL_KUMBEGANTT_TASK']."</th>
		<th>".$this->app_strings['LBL_KUMBEGANTT_STATUS']."</th>
		<th>".$this->app_strings['LBL_KUMBEGANTT_START']."</th>
		<th>".$this->app_strings['LBL_KUMBEGANTT_FINISH']."</th>
		";


		$count = count($giorni);
		$cs = ($vcount == 1) ? 2 : $count;

		$i = 0;
		foreach($giorni as $k=>$v){
			$date = date("d/m", $k);
			if (!$i) {
				$result.="<th style=\"text-align:left\"colspan=\"$cs\" >$date</th>";
			} elseif ($i == $count - 1) {
				$result.="<th style=\"text-align:right\" colspan=\"$cs\">$date</th>";
			}
			$i ++;
		}
		$result.="</tr>";


		$result.="</tr>";
		
		foreach($risorse as $risorsa){
			$result.="<tr>";
			$result.="<td colspan=\"".(5+sizeof($giorni)*2)."\">".$this->app_strings['LBL_KUMBEGANTT_TASK_FOR']." $risorsa</td>";
			$result.="</tr>";
			
			$percentuale_utilizzo_giornaliera=array();
			
			$tasks = $this->get_tasks_risorsa($risorsa);
			
			foreach($tasks as $task){
				$result.="<tr>";
				$result.="<td><a class=\"listViewTdLinkS1\" href=\"index.php?module=Project&action=DetailView&record={$task['progetto_id']}\">".$task["progetto_nome"]."</a></td>";
				$result.="<td><a class=\"listViewTdLinkS1\" href=\"index.php?module=ProjectTask&action=DetailView&record={$task['id']}\">".$task["nome"]."</a></td>";
				$result.="<td class=\"".$this->get_CLASS_by_stato($task["stato"])."\">".$this->app_strings['LBL_KUMBEGANTT_'.$task["stato"]]."</td>";
				$result.="<td>".$this->data($task["inizio"])."</td>";
				$result.="<td>".$this->data($task["fine"])."</td>";
				
				foreach($giorni as $k=>$v){
					$date = date("d/m", $k);
					if($k>=$task["inizio"]&&$k<=$task["fine"]){
						if (date("Y-m-d", $k) == date("Y-m-d")) {
							$result.="<td class=\"".$this->get_CLASS_by_stato($task["stato"])."\" style=\"border-right: dotted 2px black;\" title=\"$date\">$blank2</td>";
							$result.="<td class=\"".$this->get_CLASS_by_stato($task["stato"])."\" title=\"$date\">$blank2</td>";
						} else {
							$result.="<td colspan=\"2\" class=\"".$this->get_CLASS_by_stato($task["stato"])."\" title=\"$date\">$blank</td>";
						}
						$percentuale_utilizzo_giornaliera[$k]+=$task["percentuale_utilizzo"];
					}else{
						if (date("Y-m-d", $k) == date("Y-m-d")) {
							$result.="<td style=\"border-right: dotted 2px black\" title=\"$date\">$blank2</td>";
							$result.="<td title=\"$date\">$blank2</td>";
						} else {
							$result.="<td style=\"width:10px;\" colspan=\"2\" title=\"$date\">$blank</td>";
						}
					}
				}
						
				$result.="</tr>";
			}

			$result.="<tr>";
			$result.="<td colspan=\"5\">Impegno % $risorsa</td>";

				foreach($giorni as $k=>$v){
					if(isset($percentuale_utilizzo_giornaliera[$k])){
						$result.="<td colspan=\"2\" ".$this->get_STYLE_by_percentuale_utilizzo($percentuale_utilizzo_giornaliera[$k]).">".$percentuale_utilizzo_giornaliera[$k]."%</td>";
					}else{
						$result.="<td colspan=\"2\">&nbsp;</td>";
					}
				}

			$result.="</tr>";
			
			
		}

		$result.="</table>";
		
		return $result;
	}
	
	/**
	 * processano l'xml del gantt determina la data minima
	 * @return 
	 */
	function get_min_date(){
		$inizi = $this->xml->getElementsByTagName("inizio");
		
		$tmp = null;
		foreach($inizi as $inizio){

			if(strlen($inizio->nodeValue)==strlen("YYYYMMDD")){

				if($tmp==null){
					$tmp = strtotime($inizio->nodeValue);
				}
				if($tmp>strtotime($inizio->nodeValue)){
					$tmp = strtotime($inizio->nodeValue);
				}				
			}
		}
			return $tmp;		
	}

	/**
	 * processano l'xml del gantt determina la data massima
	 * @return 
	 */
	function get_max_date(){
		$fini = $this->xml->getElementsByTagName("fine");
		
		$tmp = null;
		foreach($fini as $fine){
			
			if(strlen($fine->nodeValue)==strlen("YYYYMMDD")){
			if($tmp==null){
				$tmp = strtotime($fine->nodeValue);
			}
			if($tmp<strtotime($fine->nodeValue)){
				$tmp = strtotime($fine->nodeValue);
			}
			}
		}
			return $tmp;	
	}
	
	function __destruct(){
		
	}
}
?>
