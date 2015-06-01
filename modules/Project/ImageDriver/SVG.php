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

require_once 'modules/Project/ImageDriver/ImageDriver.php';
require_once 'include/SVGCharts/impl/SVGFont.php';

class SVGDriver implements ImageDriver
{
	protected $w, $h;
	protected $font;
	protected $root;
	protected $project_id;

	public function __construct($w, $h, $project_id)
	{
		$this->project_id = $project_id;
		$this->w = $w;
		$this->h = $h;
		$this->font = new SVGFont('include/SVGCharts/impl/fonts/Verdana.php', 'Verdana');
		$impl = new DOMImplementation;
		$doctype = $impl->createDocumentType("svg", "-//W3C//DTD SVG 1.1//EN", "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd");
		$this->doc = $impl->createDocument(null, 'svg', $doctype); 
		foreach ($this->doc->childNodes as $child) {
			if ($child instanceof DomElement) {
				$this->root = $child;
				break;
			}
		}
		$this->root->setAttribute('xmlns', 'http://www.w3.org/2000/svg');
		$this->root->setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
		$this->root->setAttribute('version', '1.1');

		$path = AppConfig::site_url();

		$script = <<<SCRIPT
function navigateToTask(id) {
	var w = window.top.opener;
	if (!w || w.closed) return;
	w.location.href="{$path}/index.php?module=ProjectTask&action=DetailView&record=" + id;
	w.focus();
	return false;
}

function moveTask(id, dir)
{
	window.top.location.href='{$path}/index.php?module=Project&action=gantt&project_id={$this->project_id}&image=1&to_pdf=true&move='+id+'&dir=' + dir;
	return false;
}
SCRIPT;
		$scriptEL = $this->doc->createElement('script');
		$scriptEL->setAttribute('type', 'text/javascript');
		$scriptEL->appendChild($this->doc->createCDATASection($script));
		$this->root->appendChild($scriptEL);
	}

	public function allocateColor($r, $g, $b)
	{
		return sprintf('#%02X%02X%02X', $r, $g, $b);
	}

	public function stringBox($str, $size)
	{
		$ret = $this->font->measure($str, $size);
		$ret['w'] *= 1.2;
		return $ret;
	}

	public function makeLineStyle($width, $color, $pattern = array())
	{
		if (empty($pattern)) {
			$style = array(
				'stroke-width' => $width,
				'stroke' => $color,
			);
		} else {
			$style = array(
				'stroke-width' => $width,
				'stroke-dasharray' => join(',', $pattern),
				'stroke' => $color,
			);
		}
		return $style;
	}

	public function line($x1, $y1, $x2, $y2, $style)
	{
		$line = $this->doc->createElement('line');
		$line->setAttribute('x1', $x1);
		$line->setAttribute('x2', $x2);
		$line->setAttribute('y1', $y1);
		$line->setAttribute('y2', $y2);
		foreach ($style as $attr => $value) {
			$line->setAttribute($attr, $value);
		}
		$this->root->appendChild($line);
	}

	public function text($size, $x, $y, $color, $str)
	{
		$text = $this->doc->createElement('text', $str);
		$text->setAttribute('font-family', $this->font->getName());
		$text->setAttribute('font-size', $size);
		$text->setAttribute('fill', $color);
		$text->setAttribute('x', $x);
		$text->setAttribute('y', $y);
		$this->root->appendChild($text);
	}

	public function filledRect($x1, $y1, $x2, $y2, $color)
	{
		$rect = $this->doc->createElement('rect');
		$rect->setAttribute('x', $x1);
		$rect->setAttribute('y', $y1);
		$rect->setAttribute('width', $x2 - $x1);
		$rect->setAttribute('height', $y2 - $y1);
		$rect->setAttribute('fill', $color);
		$this->root->appendChild($rect);
	}

	public function rect($x1, $y1, $x2, $y2, $style)
	{
		$rect = $this->doc->createElement('rect');
		$rect->setAttribute('x', $x1);
		$rect->setAttribute('y', $y1);
		$rect->setAttribute('width', $x2 - $x1);
		$rect->setAttribute('height', $y2 - $y1);
		$rect->setAttribute('fill', 'none');
		foreach ($style as $attr => $value) {
			$rect->setAttribute($attr, $value);
		}
		$this->root->appendChild($rect);
	}

	public function filledPoly($points, $color)
	{
		$path = sprintf('M%f %f', $points[0], $points[1]);
		$count = count($points);
		for ($i = 2; $i < $count; $i += 2) {
			$path .= sprintf('L%f %f', $points[$i], $points[$i+1]);
		}
		$pathEL = $this->doc->createElement('path');
		$pathEL->setAttribute('stroke', 'none');
		$pathEL->setAttribute('fill', $color);
		$pathEL->setAttribute('d', $path);
		$this->root->appendChild($pathEL);
	}
	
	public function transparentColor()
	{
		return '#FFF';
	}

	public function render($filename)
	{
		$f = fopen($filename, 'w');
		fwrite($f, $this->doc->saveXML());
		fclose($f);
	}

	public function setImageMap($map)
	{
		foreach ($map as $area) {
			$a = $this->doc->createElement('a');
			if ($area['type'] == 'ProjectTask') {
				$a->setAttribute('onclick', "navigateToTask('{$area['data']['id']}')");
				$a->setAttribute('title', $area['data']['name']);
			} else {
				$a->setAttribute('onclick', "moveTask('{$area['data']['id']}', '{$area['direction']}')");
			}
			$a->setAttribute('xlink:href', '#');
			$rect = $this->doc->createElement('rect');
			$rect->setAttribute('stroke', 'none');
			$rect->setAttribute('fill', '#FFFFFF');
			$rect->setAttribute('fill-opacity', '0.05');
			$rect->setAttribute('x', $area['x1']);
			$rect->setAttribute('y', $area['y1']);
			$rect->setAttribute('width', $area['x2'] - $area['x1']);
			$rect->setAttribute('height', $area['y2'] - $area['y1']);
			$a->appendChild($rect);
			$this->root->appendChild($a);
		}
	}

	public function renderHTML($filename)
	{
		return '<object data="' . $filename . '" type="image/svg+xml" width="' . $this->w
			.'" height="' . $this->h . '" title=""></object>';
	}
	
	public function fileExt()
	{
		return 'svg';
	}

}

