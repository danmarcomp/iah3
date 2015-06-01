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

error_reporting(E_ALL);

class GanttImage{
	
	private $xml = null;
	private $min_date = null;
	private $max_date = null;
	private $debug;

	private $imWidth = 850;
	private $imHeight = 600;

	private $timeLineWidth = 550;

	private $fonts;

	/***
	 * 
	 * @return 
	 * @param $xmldata Object
	 * @param $parameters array(
	 * "DEBUG"
	 * "LABELS"
	 * )
	 */
	function __construct($data, $backend, $parameters=array()){

		$this->data = $data;
		if(!isset($parameters["DEBUG"])){
			$this->debug=false;
		}else{
			$this->debug=$parameters["DEBUG"];
		}
		
		$this->app_strings = $parameters["LABELS"];
		
		$this->min_date = $this->get_min_date();
		
		if($this->debug){
			echo "Data minore = ".date("d/m/Y",$this->min_date)."<br>";
		}

		$this->max_date = $this->get_max_date();

		if($this->debug){
			echo "Data maggiore = ".date("d/m/Y",$this->max_date)."<br>";
		}

		$this->fonts = array(
			'' => realpath('include/fonts/dejavusans.ttf'),
			'b' => realpath('include/fonts/dejavusans.ttf'),
			'i' => realpath('include/fonts/dejavusans.ttf'),
			'z' => realpath('include/fonts/dejavusans.ttf'),
		);

		$this->timeLineHeight = 24;
		$this->titleHeight = 32;
		$this->legendHeight = 60;

		$nTasks = 0;
		foreach ($this->data as $project) {
			$nTasks += count($project['tasks']);
		}

		$this->imHeight = $this->timeLineHeight  + $this->titleHeight + $this->legendHeight + $nTasks * 60;


		$this->legendY = $this->titleHeight;
		$this->timeLineY = $this->legendY + $this->legendHeight;
		$this->tasksY = $this->timeLineY + $this->timeLineHeight;

		$this->arrowSegments = array();
		$this->imageMap = array();

		$project_id = '';
		foreach ($data as $project_id => $unused) {
			break;
		}
		
		require_once 'modules/Project/ImageDriver/' . $backend . '.php';
		$className = $backend . 'Driver';
		$this->image = new $className($this->imWidth + 100, $this->imHeight, $project_id);
		
		$this->allocateColors();
		$this->image->filledRect(0, 0, $this->imWidth + 100, $this->imHeight, $this->colors['background']);
	
	}

	function allocateColors()
	{
		$this->colors = array(
			'background' => $this->image->allocateColor(255, 255, 255),
			'timeline'   => $this->image->allocateColor(0  ,   0,   0),
			'today'      => $this->image->allocateColor(0  ,   0,   0),
			'grid'       => $this->image->allocateColor(223 , 223, 223),
			'taskbg'     => $this->image->allocateColor(200 , 255, 255),
			'done'       => $this->image->allocateColor(100 , 100, 100),
			'ndone'      => $this->image->allocateColor(191 , 191, 255),
			'done_ms'    => $this->image->allocateColor(  0 ,   0,   0),
			'ndone_ms'   => $this->image->allocateColor(  0 ,   0, 255),
			'transparent'=> $this->image->transparentColor(),
			'status'=> array(
				'Not Started'   => $this->image->allocateColor(0xc6, 0xc6, 0xc6),
				'In Progress'   => $this->image->allocateColor(0xff, 0x76, 0x00),
				'Completed'     => $this->image->allocateColor(0x00, 0xff, 0x00),
				'Pending Input' => $this->image->allocateColor(0x94, 0x44, 0x00),
				'Deferred'      => $this->image->allocateColor(0x57, 0x57, 0x57),
			),
			'dependency' => $this->image->allocateColor(0 , 200,   0),
		);
	}


	function getStringBBox($str, $size)
	{
		return $this->image->stringBox($str, $size);
	}

	function getStringWidth($str, $size)
	{
		$box = $this->getStringBBox($str, $size);
		return $box['w'];
	}

	function getStringHeight($str, $size)
	{
		$box = $this->getStringBBox($str, $size);
		return $box['h'];
	}

	function drawTimeLine()
	{
		$days = array_keys($this->get_array_giorni());
		$nDays = count($days);
		$period = 1;
		do {
			$dayWidth = floor($this->timeLineWidth / $period);
			$textWidth = 0;
			for ($i = 0; $i < $nDays; $i += $period) {
				$date = date('d/m', $days[$i]);
				$textWidth += $this->getStringWidth($date, 10) + 5;
				if ($textWidth > $this->timeLineWidth) {
					$period++;
					continue 2;
				}
			}
			break;
		} while (true);
		$step = $nDays ? $this->timeLineWidth / $nDays * $period : 1;
		if ($period > 1) {
			$period2 = 0;
			do {
				$period2++;
				$step2 = $this->timeLineWidth / $nDays * $period2;
			} while ($step2 < 3);
			if ($period2 < $period) {
				$this->gridPeriodSmall = $period2;
				$x = $this->imWidth - $this->timeLineWidth;
				$style = $this->image->makeLineStyle(1, $this->colors['grid'], array(4,4));
				$height = $this->getStringHeight('0', 10);
				while ($x < $this->imWidth) {
					$this->image->line($x, $this->timeLineY + $height, $x, $this->imHeight, $style);
					$x += $step2;
				}
			} else {
				$this->gridPeriodSmall = $period2;
			}
		}
		else {
			$this->gridPeriodSmall = $period;
		}
		$this->gridPeriod = $period;

		$x = $this->imWidth - $this->timeLineWidth;
		for ($i = 0; $i < $nDays; $i += $period) {
			$date = date('d/m', $days[$i]);
			$box = $this->getStringBBox($date,  10);
			$this->image->text(10, $x, $this->timeLineY + $box['h'], $this->colors['timeline'], $date);
			$style = $this->image->makeLineStyle(2, $this->colors['grid']);
			$this->image->line($x, $this->timeLineY + $box['h'], $x, $this->imHeight, $style);
			$x += $step;
		}

	}

	function calcLegendHeight()
	{
		global $app_list_strings;
		$status = $app_list_strings['project_task_status_options'];
	}


	function drawToday()
	{
		$style = $this->image->makeLineStyle(2, $this->colors['today'], array(11, 6));
		$x = $this->getTaskX(strtotime('TODAY 00:00:00'));
		$this->image->line($x, $this->tasksY, $x, $this->imHeight, $style);
	}

	function utf8ToEntities($str)
	{
		//return mb_convert_encoding($str, 'HTML-ENTITIES', 'UTF-8');
		if (!is_string($str))
			return('');
		$i = 0;
		$output = '';
		while($i<strlen($str)) {
			$char = $str{$i};
			if ((ord($char) & 0x80)==0) {
				$output .= $char;
				$i++;
			} else {
				$num = 0;
				if ((ord($char) & 0xFC)==0xFC) {
					$num = (ord($str{$i+5}) & 0x3F) |
						  ((ord($str{$i+4}) & 0x3F) << 6 ) |
						  ((ord($str{$i+3}) & 0x3F) << 12) |
						  ((ord($str{$i+2}) & 0x3F) << 18) |
						  ((ord($str{$i+1}) & 0x3F) << 24) |
						  ((ord($str{$i+0}) & 0x01) << 30);
					$i += 6;
				} elseif ((ord($char) & 0xF8)==0xF8) {
						$num = (ord($str{$i+4}) & 0x3F) |
							  ((ord($str{$i+3}) & 0x3F) << 6 ) |
							  ((ord($str{$i+2}) & 0x3F) << 12) |
							  ((ord($str{$i+1}) & 0x3F) << 18) |
							  ((ord($str{$i+0}) & 0x03) << 24);
						$i += 5;
					} elseif ((ord($char) & 0xF0)==0xF0) {
						$num = (ord($str{$i+3}) & 0x3F) |
							  ((ord($str{$i+2}) & 0x3F) << 6 ) |
							  ((ord($str{$i+1}) & 0x3F) << 12) |
							  ((ord($str{$i+0}) & 0x07) << 18);
						$i += 4;
					} elseif ((ord($char) & 0xE0)==0xE0) {
						$num = (ord($str{$i+2}) & 0x3F) |
							  ((ord($str{$i+1}) & 0x3F) << 6 ) |
							  ((ord($str{$i+0}) & 0x0F) << 12);
						$i += 3;
					} elseif ((ord($char) & 0xC0)==0xC0) {
						$num = (ord($str{$i+1}) & 0x3F) |
							  ((ord($str{$i+0}) & 0x1F) << 6 );
						$i += 2;
					} else {
						$num = ord($char);
						$i++;
					};
					$output .= '&#'.$num.';';
				};
		};
		return($output);
	}

	function drawTask($task, $y, $pos = '')
	{
		$height = 30;

		$size = ($task['milestone_flag'] == 'on') ? 13 : 10;
		$exclude = -1;
		$len = mb_strlen($task['name']);
		do {
			$exclude++;
			$l = floor(($len - $exclude)/2);
			if ($exclude) {
				$text = mb_substr($task['name'], 0, $l) . '...' . mb_substr($task['name'], -($len - $exclude - $l));
			} else {
				$text = $task['name'];
			}
			$box = $this->getStringBBox($text, $size);
		} while ($box['w'] > $this->imWidth - $this->timeLineWidth - 20);

		//$text = $this->utf8ToEntities($text);
		$percent = sprintf('%d%%', $task['percent_complete']);

		$style = $this->image->makeLineStyle(1, $this->colors['taskbg']);

		//imageFilledRectangle($this->image, $this->imWidth - $this->timeLineWidth, $y, $this->imWidth, $y + 30, $this->colors['taskbg']);
		
		$this->image->line($this->imWidth - $this->timeLineWidth, $y, $this->imWidth, $y, $style);
		$this->image->line($this->imWidth - $this->timeLineWidth, $y + 30, $this->imWidth, $y + 30, $style);
	


		$this->image->text($size, 20, $y + $height/2 + $box['h']/2, $this->colors['timeline'], $text);

		$x1 = $this->getTaskStartX($task);
		$x3 = $this->getTaskEndX($task);
		$x2 =  ($x3 - $x1) * $task['percent_complete'] /100 + $x1;

		$c1 = ($task['milestone_flag'] == 'on') ? 'done_ms' : 'done';
		$c2 = ($task['milestone_flag'] == 'on') ? 'ndone_ms' : 'ndone';

		if (isset($this->colors['status'][$task['status']])){
			$frameColor = $this->colors['status'][$task['status']];
		} else {
			$frameColor = $this->colors['done'];
		}

		$this->image->filledRect($x1, $y, $x2, $y + $height, $this->colors[$c1]);
		$this->image->filledRect($x2, $y, $x3, $y + $height, $this->colors[$c2]);

		if ($task['milestone_flag'] != 'on') {
			$style = $this->image->makeLineStyle(3, $frameColor);
			$this->image->rect($x1, $y, $x3, $y + $height, $style);
			$style = $this->image->makeLineStyle(1, $this->colors['background']);
			$this->image->rect($x1+3, $y+3, $x3-3, $y + $height - 3, $style);
		} else {
			$style = $this->image->makeLineStyle(3, $frameColor);
			$this->image->line($x1, $y, $x3, $y, $style);
			$this->image->line($x3, $y, $x3, $y + $height + 5, $style);
			$this->image->line($x3, $y + $height + 5, $x3 - 5, $y + $height, $style);
			$this->image->line($x3-5, $y + $height, $x1 + 5, $y + $height, $style);
			$this->image->line($x1 + 5, $y + $height, $x1, $y + $height + 5, $style);
			$this->image->line($x1, $y + $height + 5, $x1, $y, $style);
			$style = $this->image->makeLineStyle(1, $this->colors['background']);
			$this->image->rect($x1+3, $y+3, $x3-3, $y + $height - 3, $style);
		}

		$this->imageMap[] = array(
			'type' => 'ProjectTask',
			'x1' => 20,
			'y1' => $y,
			'x2' => $this->imWidth,
			'y2' => $y + $height,
			'data' => $task,
		);


		$pcBox = $this->getStringBBox($percent,  10);
		$center = ($x3 + $x1)  / 2;
		if ($pcBox['w'] + 6 < $x3 - $x1) {
			$this->image->text(10,($center - $pcBox['w']/2), $y +20, $this->colors['background'], $percent);
		} else {
			if ($this->imWidth - $this->timeLineWidth <= $x1 - $pcBox['w'] - 10) {
				$this->image->text(10,$x1 - $pcBox['w'] - 5, $y +20, $this->colors['timeline'], $percent);
			} else {
				$this->image->text(10, $x3 + 5, $y +20, $this->colors['timeline'], $percent);
			}
		}

		if ($pos != 'first') {
			$points = array(
				0, $y+8,
				16, $y+8,
				8, $y
			);
			$this->image->filledPoly($points, $this->colors['timeline']);

			$this->imageMap[] = array(
				'type' => 'MoveTask',
				'x1' => 0,
				'y1' => $y,
				'x2' => 16,
				'y2' => $y + 8,
				'data' => $task,
				'direction' => 'up',
			);
		}

		if ($pos != 'last') {
			$points = array(
				0, $y+ $height - 8,
				16, $y+ $height - 8,
				8, $y + $height
			);
			$this->image->filledPoly($points, $this->colors['timeline']);

			$this->imageMap[] = array(
				'type' => 'MoveTask',
				'x1' => 0,
				'y1' => $y + $height - 8,
				'x2' => 16,
				'y2' => $y + $height,
				'data' => $task,
				'direction' => 'down',
			);
		}

		return $height * 2;
	}

	function drawTasks()
	{
		$y = $this->tasksY;

		$projects = $this->get_progetti();

		$nTasks = 0;
		foreach ($this->data as $project) {
			$nTasks += count($project['tasks']);
		}

		$i = 0;
		foreach($projects as $project){
			$tasks = $this->get_tasks_progetto($project["id"]);
			foreach ($tasks as $task) {
				$pos = !$i ? 'first' : ($i == $nTasks - 1 ? 'last' : '');
				$y += $this->drawTask($task, $y, $pos);
				$i ++;
			}
		}
	}

	function getTaskX($timestamp)
	{
		if ($this->max_date == $this->min_date) return 0;
		$ppd  = $this->timeLineWidth / ($this->max_date - $this->min_date);
		$x = ($timestamp - $this->min_date) * $ppd + $this->imWidth - $this->timeLineWidth;
		return $x;
	}

	function getTaskEndX($task)
	{
		return $this->getTaskX(strtotime($task['date_due']));
	}

	function getTaskStartX($task)
	{
		return $this->getTaskX(strtotime($task['date_start']));
	}

	function getTaskY($aTask)
	{
		$i = 0;
		foreach ($this->data as $project) {
			foreach ($project['tasks'] as $task) {
				if ($task['id'] == $aTask['id']) {
					return $this->tasksY + $i * 60;
				}
				$i++;
			}
		}
	}

	function segmentCrossesBar($x, $minY, $maxY)
	{
		foreach ($this->data as $project) {
			foreach ($project['tasks'] as $task) {
				$taskStartX = $this->getTaskStartX($task) - 12;
				$taskEndX = $this->getTaskEndX($task) + 12;
				$taskY = $this->getTaskY($task);
				if (
					$minY <= $taskY && $maxY >= $taskY
					&& $taskStartX <= $x && $taskEndX >= $x) {
						return true;
				}
			}
		}
	}

	function findFreeSegmentSlot($seekFrom, $dir, $minY, $maxY)
	{
		if ($minY > $maxY) {
			$tmp = $maxY;
			$maxY = $minY;
			$minY = $tmp;
		}

		$current = $seekFrom;
		$best = $fallback = null;

		while (true) {
			if ($current >= $this->imWidth - 12) {
				break;
			}
			if (isset($this->arrowSegments[$current])) foreach ($this->arrowSegments[$current] as $segment) {
				if (
					($segment[0] >= $minY && $segment[0] <= $maxY)
					|| ($segment[1] >= $minY && $segment[1] <= $maxY)
					|| ($minY >= $segment[0] && $minY <= $segment[1])
					|| ($maxY >= $segment[0] && $maxY <= $segment[1]) ) {
						$current += $dir * 8;
						continue 2;
				}
			}

			if (!$fallback) {
				$fallback = $current;
			}
			if (!$this->segmentCrossesBar($current, $minY, $maxY)) {
				$best = $current;
				break;
			}
			$current += $dir * 8;
		}
		if (!$fallback) {
			$fallback = $seekFrom;
		}
		$target = $fallback;
		if ($best) {
			$target = $best;
		}
		$this->arrowSegments[$target][] = array($minY, $maxY);
		return $target;
	}

	function drawArrow($from, $to, $type)
	{
		$fromTopY = $this->getTaskY($from);
		$toTopY = $this->getTaskY($to);
		switch ($type) {
			case 'STS':
				$fromX = $this->getTaskStartX($from);
				$toX = $this->getTaskStartX($to);
				$fromY = $fromTopY + 5;
				$toY = $toTopY + 5;
				$connY = $fromTopY + 30 + 4;;
				$fromDir = -1;
				$toDir = -1;
				break;
			case 'STF':
				$fromX = $this->getTaskStartX($from);
				$toX = $this->getTaskEndX($to);
				$fromY = $fromTopY + 5;
				$toY = $toTopY + 25;
				$connY = $fromTopY + 30 + 8;;
				$fromDir = -1;
				$toDir = 1;
				break;
			case 'FTF':
				$fromX = $this->getTaskEndX($from);
				$toX = $this->getTaskEndX($to);
				$fromY = $fromTopY + 25;
				$toY = $toTopY + 5;
				$connY = $fromTopY + 30 + 12;;
				$fromDir = 1;
				$toDir = 1;
				break;
			default:
				$fromX = $this->getTaskEndX($from);
				$toX = $this->getTaskStartX($to);
				$fromY = $fromTopY + 25;
				$toY = $toTopY + 25;
				$connY = $fromTopY + 30 + 16;;
				$fromDir = 1;
				$toDir = -1;
				break;
		}
		
		$color = $this->colors['dependency'];
		$style = $this->image->makeLineStyle(2, $color);

		if ($fromDir == $toDir) {
			if ($fromDir > 0) {
				$seekFrom = max($fromX, $toX) + 15;
				$seekFrom -= $seekFrom % 4;
				$seekFrom += 4;
			} else {
				$seekFrom = min($fromX, $toX) - 15;
				$seekFrom += $seekFrom % 4;
				$seekFrom -= 4;
			}

			$vx = $this->findFreeSegmentSlot($seekFrom, $fromDir, $fromY, $toY);
			$this->image->line($fromX, $fromY, $vx, $fromY, $style);
			$this->image->line($vx, $fromY, $vx, $toY, $style);
			$this->image->line($vx, $toY, $toX, $toY, $style);

			$points = array(
				$toX + 12 * $fromDir, $toY - 4, 
				$toX, $toY,
				$toX + 12 * $fromDir, $toY + 4
			);
			$this->image->filledPoly($points, $color);

		} else {
			if ($fromDir > 0) {
				$seekFrom = $fromX + 15;
				$seekFrom -= $seekFrom % 4;
				$seekFrom += 4;
			} else {
				$seekFrom = $fromX - 15;
				$seekFrom += $seekFrom % 4;
				$seekFrom -= 4;
			}
			$vx1 = $this->findFreeSegmentSlot($seekFrom, $fromDir, $fromY, $connY);

			if ($toDir > 0) {
				$seekFrom = $toX + 15;
				$seekFrom -= $seekFrom % 4;
				$seekFrom += 4;
			} else {
				$seekFrom = $toX - 15;
				$seekFrom += $seekFrom % 4;
				$seekFrom -= 4;
			}
			$vx2 = $this->findFreeSegmentSlot($seekFrom, $toDir, $toY, $connY);


			$this->image->line($fromX, $fromY, $vx1, $fromY, $style);
			$this->image->line($vx1, $fromY, $vx1, $connY, $style);
			$this->image->line($vx1, $connY, $vx2, $connY, $style);
			$this->image->line($vx2, $connY, $vx2, $toY, $style);
			$this->image->line($vx2, $toY, $toX, $toY, $style);

			$points = array(
				$toX - 12 * $fromDir, $toY - 4, 
				$toX, $toY,
				$toX - 12 * $fromDir, $toY + 4
			);
			$this->image->filledPoly($points, $color);
		}
	}

	function drawDependencies()
	{
		global $app_list_strings;
		$texts = array();
		foreach ($this->data as $project) {
			$i = -1;
			foreach ($project['tasks'] as $task) {
				$i++;
				$parent = null;
				if (!empty($task['depends_on_id'])) {
					$j = -1;
					foreach ($project['tasks'] as $task2) {
						$j++;
						if ($task2['id'] ==  $task['depends_on_id']) {
							$parent = $project['tasks'][$task['depends_on_id']];
							break;
						}
					}
				}
				if (empty($parent)) {
					continue;
				}

				/*
				switch ($task['dependency_type']) {
					case 'STS':
						$fromX = $this->getTaskStartX($parent);
						$toX = $this->getTaskStartX($task);
						break;
					case 'STF':
						$fromX = $this->getTaskStartX($parent);
						$toX = $this->getTaskEndX($task);
						break;
					default:
						$fromX = $this->getTaskEndX($parent);
						$toX = $this->getTaskStartX($task);
				}

				$fromY = $j * 60 + 30 + $this->tasksY;
				$toY = $i * 60  + $this->tasksY;

				$sign = $fromY < $toY ? 1 : -1;
				if ($sign < 0) {
					$fromY -= 30;
					$toY -= 30;
				}

				$color = $this->colors['dependency'];

				imageLine($this->image, $fromX, $fromY, $fromX, $toY, $color);
				imageLine($this->image, $fromX - 4, $toY - 12 * $sign, $fromX, $toY, $color);
				imageLine($this->image, $fromX + 4, $toY - 12 * $sign, $fromX, $toY, $color);

				$text = array_get_default($app_list_strings['project_task_dependency_type'], $task['dependency_type'], '');;
				$box = $this->getStringBBox($text, 'b', 10);

				if ($sign > 0) {
					$textY = $fromY + $box['height'] + 8;
				} else {
					$textY = $fromY  - 8;
				}

				if (($this->imWidth - $fromX) < $this->timeLineWidth / 2) {
					// text left from arrow
					$texts[] = array($fromX - 10 - $box['width'], $textY, $text);
				} else {
					// text right from arrow
					$texts[] = array($fromX + 10 , $textY, $text);
				}
				 */
				$this->drawArrow($parent, $task, $task['dependency_type']);


			}
		}
		foreach ($texts as $def) {
			imageFTText($this->image, 10, 0, $def[0], $def[1], $color, $this->fonts['b'], $def[2]);
		}
	}

	function drawTitle()
	{
		foreach ($this->data as $project) {
			//$text = $this->utf8ToEntities($project['name']);
			$text = $project['name'];
			$height = $this->getStringHeight($text, 14);
			$this->image->text(14, 0, 0, $height + 3, $this->colors['timeline'], $this->fonts['z'], $text);
		}
	}

	function drawLegend()
	{
		global $app_list_strings;
		$status = $app_list_strings['project_task_status_options'];
		$x = 3;
		$height = $this->getStringHeight('$$$',  10);
		foreach ($status as $key => $value) {
			$text = $this->utf8ToEntities($value);
			$color = array_get_default($this->colors['status'], $key);

			$style = $this->image->makeLineStyle(3, $color);
			$box = $this->getStringBBox($text, 10);
			$this->image->rect($x, $this->legendY, $x + $box['w'] + 8, $this->legendY + $height + 8, $style);
			$this->image->text(10, $x + 4, $height + 1+ $this->legendY, $this->colors['timeline'],  $text);
			$x += $box['w'] + 16;
		}

		$y = $this->legendY + $height + 30;

		$style = $this->image->makeLineStyle(2, $this->colors['grid']);
		$this->image->line(0, $y, 100, $y, $style);
		//$text = $this->utf8ToEntities(sprintf($this->app_strings['LBL_LEGEND_NDAYS'], $this->gridPeriod));
		$text = sprintf($this->app_strings['LBL_LEGEND_NDAYS'], $this->gridPeriod);
		$this->image->text(10, 110, $y + $height / 2 , $this->colors['timeline'], $text);

		if ($this->gridPeriodSmall != $this->gridPeriod) {
			$style = $this->image->makeLineStyle(1, $this->colors['today'], array(4,4));
			$this->image->line(300, $y, 400, $y, $style);
			//$text = $this->utf8ToEntities(sprintf($this->app_strings['LBL_LEGEND_NDAYS'], $this->gridPeriodSmall));
			$text = sprintf($this->app_strings['LBL_LEGEND_NDAYS'], $this->gridPeriodSmall);
			$this->image->text(10, 410, $y + $height / 2 , $this->colors['timeline'], $text);
		}

		$style = $this->image->makeLineStyle(2,  $this->colors['today'], array(11, 6));
		$this->image->line(600, $y, 700, $y, $style);
		//$text = $this->utf8ToEntities($this->app_strings['LBL_LEGEND_TODAY']);
		$text = $this->app_strings['LBL_LEGEND_TODAY'];
		$this->image->text(10, 710, $y + $height / 2 , $this->colors['timeline'], $text);
	}

	function draw()
	{
		$this->drawTitle();
		$this->drawTimeLine();
		$this->drawLegend();
		$this->drawToday();
		$this->drawTasks();
		$this->drawDependencies();
		$this->image->setImageMap($this->imageMap);
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
		return $this->data;
				
	}
	
	function get_tasks_risorsa($risorsa){

		$xpath = new DOMXPath($this->xml);
		
		$tasks = $xpath->query("//progetti/progetto/tasks/task[./risorsa=\"$risorsa\"]");
		
		
		$array_task = array();

		foreach($tasks as $task){
						
			$attivita = array();
			$attivita["id"]=$task->getElementsByTagName("id")->item(0)->nodeValue;
			$attivita["name"]=$task->getElementsByTagName("name")->item(0)->nodeValue;
			$attivita["progetto_nome"]=$task->parentNode->parentNode->getElementsByTagName("name")->item(0)->nodeValue;
			$attivita["progetto_id"]=$task->parentNode->parentNode->getElementsByTagName("id")->item(0)->nodeValue;
			$attivita["date_start"]=strtotime($task->getElementsByTagName("date_start")->item(0)->nodeValue);
			$attivita["date_due"]=strtotime($task->getElementsByTagName("date_due")->item(0)->nodeValue);
			$attivita["status"]=$task->getElementsByTagName("status")->item(0)->nodeValue;
			$attivita["percentuale_utilizzo"]=$task->getElementsByTagName("percentuale_utilizzo")->item(0)->nodeValue;
			$attivita["milestone_flag"]=$task->getElementsByTagName("milestone_flag")->item(0)->nodeValue;
			array_push($array_task,$attivita);
		}
		
		return $array_task;
				
	}

	function get_tasks_progetto($project){
		return $this->data[$project]['tasks'];
				
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
		
		$progetti = $this->get_progetti();
		foreach($progetti as $progetto){
			
			
			$tasks = $this->get_tasks_progetto($progetto["id"]);
			
			if(sizeof($tasks)>0){
				
			$result.="<tr><td colspan=\"6\"><a class=\"listViewTdLinkS1\" href=\"index.php?module=Project&action=DetailView&record={$progetto['id']}\">".$progetto["name"]."</a></td><td colspan=\"".(sizeof($giorni)*2)."\">&nbsp;</td></tr>";

			foreach($tasks as $task){
				
				$result.="<tr>
						<td><a class=\"listViewTdLinkS1\" href=\"index.php?module=ProjectTask&action=DetailView&record={$task['id']}\">".$task["name"]."</a></td>
						<td>".$task["risorsa"]."</td>
						<td class=\"".$this->get_CLASS_by_stato($task["status"])."\">".$this->app_strings['LBL_KUMBEGANTT_'.$task["status"]]."</td>
						<td>".$task["percent_complete"]."%</td>
						<td>".$this->data($task["date_start"])."</td>
						<td>".$this->data($task["date_due"])."</td>
						";
				foreach($giorni as $k=>$v){
					$date = date("d/m", $k);
		
					if($this->debug){
						echo "verifico se ".$task["name"]." che dal ".$this->data($task["date_start"])." al ".$this->data($task["date_due"])." al $k e >= di ".$task["date_start"]." e <= di ".$task["date_due"]." === ";
					}
					if($k>=$task["date_start"]&&$k<=$task["date_due"]){
						if (date("Y-m-d", $k) == date("Y-m-d")) {
							$result.="<td style=\"".$this->get_STYLE_by_percentuale_completamento($task["percent_complete"])."; border-right: dotted 2px black;\" title=\"$date\" >$blank2</td>";
							$result.="<td style=\"".$this->get_STYLE_by_percentuale_completamento($task["percent_complete"])."; \"  title=\"$date\">$blank2</td>";
						} else {
							$result.="<td colspan=\"2\" style=\"".$this->get_STYLE_by_percentuale_completamento($task["percent_complete"])."; width:10px;\"  title=\"$date\">$blank</td>";
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
				$result.="<td><a class=\"listViewTdLinkS1\" href=\"index.php?module=ProjectTask&action=DetailView&record={$task['id']}\">".$task["name"]."</a></td>";
				$result.="<td class=\"".$this->get_CLASS_by_stato($task["status"])."\">".$this->app_strings['LBL_KUMBEGANTT_'.$task["status"]]."</td>";
				$result.="<td>".$this->data($task["date_start"])."</td>";
				$result.="<td>".$this->data($task["date_due"])."</td>";
				
				foreach($giorni as $k=>$v){
					$date = date("d/m", $k);
					if($k>=$task["date_start"]&&$k<=$task["date_due"]){
						if (date("Y-m-d", $k) == date("Y-m-d")) {
							$result.="<td class=\"".$this->get_CLASS_by_stato($task["status"])."\" style=\"border-right: dotted 2px black;\" title=\"$date\">$blank2</td>";
							$result.="<td class=\"".$this->get_CLASS_by_stato($task["status"])."\" title=\"$date\">$blank2</td>";
						} else {
							$result.="<td colspan=\"2\" class=\"".$this->get_CLASS_by_stato($task["status"])."\" title=\"$date\">$blank</td>";
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
		$tmp = null;
		foreach($this->data as $project){
			foreach ($project['tasks'] as $task) {
				$ds = substr($task['date_start'], 0, 10);
				if(strlen($ds)==strlen("YYYY-MM-DD")){
					if($tmp==null){
						$tmp = strtotime($ds);
					}
					if($tmp>strtotime($ds)){
						$tmp = strtotime($ds);
					}				
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
		$tmp = null;
		foreach($this->data as $project){
			foreach ($project['tasks'] as $task) {
				$dd = substr($task['date_due'], 0, 10);
				if(strlen($dd)==strlen("YYYY-MM-DD")){
					if($tmp==null){
						$tmp = strtotime($dd);
					}
					if($tmp<strtotime($dd)){
						$tmp = strtotime($dd);
					}				
				}
			}
		}
		return $tmp;
	}
	
	function __destruct(){
		
	}
}
?>
