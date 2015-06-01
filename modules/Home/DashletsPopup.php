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

class DashletsPopup {
	function label($t, $strings) {
		if(is_array($t))
			return translate($t[0], $t[1]);
		else if(isset($strings[$t]))
			return $strings[$t];
		return $t;
	}
	
	function display() {
		global $app_strings, $current_language, $mod_strings, $image_path;
		require_once('include/layout/DashletManager.php');

		$theCats = array(
        	'Module Views',
			'Charts',
			'External',
			'Tools',
			'Miscellaneous',
		);
		$default_category = 'Miscellaneous';
		
		$dashlets = array();
		$script = array();
		foreach($theCats as $cat) {
			$script[$cat] = '';
			$dashlets[$cat] = array();
		}
		
		$dashletStrings = array();
		$dashlets_info = DashletManager::get_all_dashlet_info();
		foreach($dashlets_info as $className => $info) {
			$categ = '';
			if(!empty($info['meta']) && is_file($info['meta'])) {
				require_once($info['meta']); // get meta file
				
				$directory = substr($info['meta'], 0, strrpos($info['meta'], '/') + 1);
				if(is_file($directory . $info['class'] . '.' . $current_language . '.lang.php')) 
					require_once($directory . $info['class'] . '.' . $current_language . '.lang.php');
				elseif(is_file($directory . $info['class'] . '.en_us.lang.php')) 
					require_once($directory . $info['class'] . '.en_us.lang.php');

				$lang = isset($dashletStrings[$info['class']]) ? $dashletStrings[$info['class']] : array();
				$title = $this->label($dashletMeta[$info['class']]['title'], $lang);
				$description = $this->label($dashletMeta[$info['class']]['description'], $lang);

				if(empty($dashletMeta[$info['class']]['icon'])) { // no icon defined in meta data
					if(empty($info['icon']))
						$icon = '';
					else // use one found in directory
						$icon = get_image($info['icon'], 'border="0" alt=""');
				}
				else { // use one defined in meta data
					$icon = get_image($dashletMeta[$info['class']]['icon'], 'border="0" alt=""');
					if(! $icon)
						$icon = get_image($image_path.$dashletMeta[$info['class']]['icon'], 'border="0" alt=""');
				}
								
				$categ = $dashletMeta[$info['class']]['category'];
			}
			else if($info['type'] == 'chart') {
				$icon = get_image($image_path.$info['icon'], 'border="0" alt=""');
				$description = '';
				$title = $info['title'];
				$categ = $info['category'];
			}
			else
				continue;
			if(! $categ || ! isset($dashlets[$categ]))
				$categ = $default_category;
			
			$dlet_desc = addcslashes($description, "'");
			$dashlets[$categ][$title] = array($className, $icon, $title, $dlet_desc);		
		}
		
		
		require_once('modules/Reports/Report.php');
		$charts = Report::get_chartable_reports();
		$chart_cat = 'Charts';
		foreach($charts as $chart_id => $chart_info) {
			if(isset($dashlets[$chart_cat][$chart_id]))
				continue;
			$icon = get_image($image_path.'Reports', 'border="0" alt=""');
			$title = $chart_info['name'];
			$desc = '';
			$dashlets[$chart_cat][$chart_id] = array('report_'.$chart_info['id'], $icon, $title, $desc);
		}

		
		$tabs = array();
		$default_tab = '';
		$divs = array();
		foreach($theCats as $cat) {
			if(! count($dashlets[$cat]))
				continue;
			if(! $default_tab) $default_tab = $cat;
			$tabs[$cat] = array(
				'title' => $mod_strings['dashlet_categories_dom'][$cat],
				'link' => $cat,
				'key' => $cat,
			);
			$div = "<div id='{$cat}_div' class='tabForm' style='border-top: none; font-size: 14px; height: 400px;";
			if($cat != $default_tab)
				$div .= ' display: none';
			$div .= "'>";
			ksort($dashlets[$cat]);
			$left = $right = '';
			$c = round(count($dashlets[$cat]) / 2);
			$i = 0;
			foreach($dashlets[$cat] as $dlet) {
				list($className, $icon, $title, $desc) = $dlet;
				$elt_id = 'dashlet_'.$className;
				$link = '<a id="'.$elt_id.'" href="#" onclick="return SUGAR.sugarHome.addDashlet(\'' . $className . '\');" style="display: block; padding: 5px; text-decoration: none">'
					. $icon . '&nbsp;' . $title . '</a>';
				if($desc)
					$script[$cat] .= "var t = new SUGAR.ui.Tooltip('{$elt_id}_tt', {target_id: '$elt_id', target_offset: {left: 40}, text: '$desc', container_id: 'dashlet-add' } );";

				if($i >= $c)
					$right .= $link;
				else
					$left .= $link;
				$i ++;
			}
			$div .= '<table border="0" cellpadding="2" cellspacing="0" width="100%"><tr>';
			$div .= '<td width="50%" valign="top">' . $left . '</td>';
			$div .= '<td valign="top">' . $right . '</td></tr></table>';
			$div .= '</div>';
			$divs[$cat] = $div;
		}
		require_once('include/tabs.php');
		$tabPanel = new SugarWidgetTabs($tabs, $default_tab);
		$str = $tabPanel->display();
		$str .= implode('', $divs);
		
		$script = implode('', $script);
		$str .= '<script type="text/javascript">'.$script.'</script>';
		return $str;
	}
}

$popup = new DashletsPopup();
$body = $popup->display();
//$html = '<button class="button" onclick="return SUGAR.sugarHome.doneAddDashlets();" style="margin-top: 3px">' .$mod_strings['LBL_CLOSE_DASHLETS'] . '</button><br><br><h2>' . $mod_strings['LBL_ADD_DASHLETS'] . '</h2><div id="Dashlets"></div>';

echo $body;

?>
