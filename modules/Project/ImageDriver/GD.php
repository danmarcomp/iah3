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

class GDDriver implements ImageDriver
{
	protected $w, $h;
	protected $font;

	public function __construct($w, $h, $project_id)
	{
		$this->w = $w;
		$this->h = $h;
		$this->font = realpath('include/fonts/dejavusans.ttf');
		$this->image = imageCreateTrueColor($w, $h);
	}

	public function allocateColor($r, $g, $b)
	{
		return imageColorAllocate($this->image, $r, $g, $b);
	}

	public function stringBox($str, $size)
	{
		$box = imageFTBBox($size, 0, $this->font, $str);
		return array(
			'w' => $box[2] - $box[0],
			'h' => $box[1] - $box[7],
		);
	}

	public function makeLineStyle($width, $color, $pattern = array())
	{
		return array(
			'width' => $width,
			'color' => $color,
			'pattern' => $pattern,
		);
	}

	public function line($x1, $y1, $x2, $y2, $style)
	{
		imageSetThickness($this->image, $style['width']);
		if (!empty($style['pattern'])) {
			$gdStyle = array();
			$colors = array($style['color'], $this->transparentColor());
			$i = 0;
			foreach ($style['pattern'] as $n) {
				while ($n) {
					$gdStyle[] = $colors[$i];
					$n--;
				}
				$i ^= 1;
			}
			imageSetStyle($this->image, $gdStyle);
			imageLine($this->image, $x1, $y1, $x2, $y2, IMG_COLOR_STYLED);
		} else {
			imageLine($this->image, $x1, $y1, $x2, $y2, $style['color']);
		}
	}

	public function text($size, $x, $y, $color, $str)
	{
		imageFTText($this->image, $size, 0, $x, $y, $color, $this->font, $str);
	}

	public function filledRect($x1, $y1, $x2, $y2, $color)
	{
		imageFilledRectangle($this->image, $x1, $y1, $x2, $y2, $color);
	}

	public function rect($x1, $y1, $x2, $y2, $style)
	{

		imageSetThickness($this->image, $style['width']);
		if (!empty($style['pattern'])) {
			$colors = array($style['color'], $this->transparentColor());
			$gdStyle = array();
			$i = 0;
			foreach ($style['pattern'] as $n) {
				while ($n) {
					$gdStyle[] = $colors[$i];
					$n--;
				}
				$i ^= 1;
			}
			imageSetStyle($this->image, $gdStyle);
			imageRectangle($this->image, $x1, $y1, $x2, $y2, IMG_COLOR_STYLED);
		} else {
			imageRectangle($this->image, $x1, $y1, $x2, $y2, $style['color']);
		}
	}

	public function filledPoly($points, $color)
	{
		
		imageFilledPolygon($this->image, $points, count($points)/2, $color);
	}
	
	public function transparentColor()
	{
		return imageColorTransparent($this->image);
	}

	public function render($filename)
	{
		imagePNG($this->image, $filename);
	}

	public function setImageMap($map)
	{
	}

	public function renderHTML($filename)
	{
		return '<img src="' . $filename . '" usemap="#gantt" border="0">';
	}

	public function fileExt()
	{
		return 'png';
	}
}

