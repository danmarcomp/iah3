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

class KBTagsManager
{
	var $js = array();
	var $js_controls = array();

	public function getTagsList()
	{
		$lq = new ListQuery('kb_tags');
		$fields = array(
			'id',
			'tag',
			'articles.id',
			'narticles' => array(
				'name' => 'narticles',
				'type' => 'int',
				'source' => array (
					'type' => 'literal',
					'value' => 'count(articles.id)',
				),
			),
		);		
		$lq->addFields($fields);
		$lq->setGroupBy('kb_tags.id');
		$lq->setOrderBy('narticles DESC, tag');
		$lr = $lq->runQuery();
		return $lr->rows;
	}

	public function printTagsCloud()
	{
		$tags = $this->getTagsList();
		$total = 0;
		foreach ($tags as $tag) {
			$total += $tag['narticles'];
		}
		$min_size = 10;
		$max_size = 30;
		$size_diff = $max_size - $min_size;

		foreach ($tags as $tag) {
			if (!$total) {
				$size = $min_size;
			} else {
				$size = $min_size + $tag['narticles'] / $total * $size_diff;
			}
			printf('<a class="tabDetailViewDFLink" style="text-decoration:none" href="index.php?module=KBArticles&action=index&query=true&include_tags=%s"><span style="font-size:%2.3fpt">%s</span></a> ', $tag['id'], $size, $tag['tag']);
		}
	}

	function renderTagsFilter($include, $exclude)
	{
		global $app_strings, $mod_strings;
		$tags = $this->getTagsList();
		$opts = array(
			'maxlen' => 12,
			'display_name' => 'name',
			'keys' => array(),
			'values' => array(),
		);
		foreach ($tags as $tag) {
			$opts['keys'][] = $tag['id'];
			$opts['values'][] = $tag['tag'];
		}
		
		// FIXME - use FormGenerator - do not duplicate input control rendering code
		
		echo '<form id="tags_filter_form">';

		echo <<<HEADER
<div class="button-bar form-top opaque">
	<button class="form-button" onclick="KBTagsFilter.applyFilter(this.form); return false;">
		<div class="input-icon left icon-accept"></div><span
			class="input-label">{$app_strings['LBL_APPLY_FILTER_BUTTON_LABEL']}</span>
	</button>
	<button class="form-button" onclick="KBTagsFilter.cancel(); return false;">
		<div class="input-icon left icon-cancel"></div><span
			class="input-label">{$app_strings['LBL_CANCEL_BUTTON_LABEL']}</span>
	</button>
</div>
HEADER;

		echo '<table width="100%"><tr>';
		echo '<td><p class="topLabel">' . $mod_strings['LBL_INCLUDE_TAGS'] . '</p></td><td><p class="topLabel">' . $mod_strings['LBL_EXCLUDE_TAGS'] . '</p></td></tr>';
		echo '<tr><td>';
		echo $this->renderTagsSelector('filter_include_tags', $opts, $include);
		echo '</td>';
		echo '<td width="50%">';
		echo $this->renderTagsSelector('filter_exclude_tags', $opts, $exclude);
		echo '</td>';
		echo '</tr>';
		echo '</table>';
		echo '</form>';
	
		if($this->js_controls) {
			foreach($this->js_controls as $k => $v)
				$c[] = "var input = new SUGAR.ui.$v[0]('$k', $v[1]); input.setup(); SUGAR.ui.registerInput('tags_filter_form', input);";
		}
		global $pageInstance;
		$pageInstance->add_js_literal(implode('', $this->js));
		$pageInstance->add_js_literal(implode('', $c));
	}

	function renderTagsSelector($name, $opts, $selected) {
		$attrs = array('name' => $name);

		$ret = '<input type="hidden" id="' . $name . '" name="' . $name . '" value="' . $selected . '">';
		$sel_id = $name . '-input';
		$rows = 10;
		
		$attrs = array('class' => 'input-select-multi input-outer', 'id' => $sel_id);
		foreach(array('style') as $f)
			if(isset($spec[$f]))
				$attrs[$f] = $spec[$f];
		$attrs['tabindex'] = 0;
		
		$w = 25;
		if($w) {
			if(is_numeric($w)) {
				$w += 1.5; // account for scrollbar
				$w = round(($w + 2) * 0.5, 2);
				$w .= 'em';
			}
			$attrs['style'] = "width: $w";
		}
		
		$ret .= self::get_tag('div', $attrs);
		$ret .= self::get_tag('div', array('id' => $sel_id.'-scroll', 'class' => 'input-scroll select-inner'), true);
		$ret .= '</div>';

		$dom = 'kb_tags_list';
		$attribs = array(
			'name' => $name,
			'options' => $dom,
			'multi_select' => true,
			'rows' => $rows,
			'field_id' => $name,
		);
		$json = getJSONobj();
		$opts_jso = $json->encode($opts);
		$this->js[] = "SUGAR.ui.registerSelectOptions('$dom', $opts_jso);";
		$this->addControl('SelectList', $sel_id, $attribs);

		return $ret;
	}
	
	function addControl($cls, $id, $params) {
		if(is_array($params))
			$params = getJSONobj()->encode($params);
		$this->js_controls[$id] = array($cls, $params);
	}
	
	function renderFilterContent($include, $exclude)
	{
		$tags = $this->getTagsList();
		if ($include) $include = array_unique(explode('^,^', $include));
		else $include = array();
		if ($exclude) $exclude = array_unique(explode('^,^', $exclude));
		else $exclude = array();

		$sections = array(
			array(
				'image' => 'plus_inline',
				'tags' => $include,
			),
			array(
				'image' => 'minus_inline',
				'tags' => $exclude,
			),
		);

		echo '<table width="100%"><tr>';
		echo '<td width="50%">Included tags</td>';
		echo '<td width="50%">Excluded tags</td>';
		echo '</td>';
		echo '</tr><tr>';
		echo '<table width="100%"><tr>';
		foreach ($sections as $sec) {
			echo '<td style="width:50%; vertical-align:top">';
			$icon = get_image($sec['image'], '');
			foreach ($sec['tags'] as $id) {
				echo $icon;
				echo '&nbsp;';
				echo $tags[$id]['tag'];
				echo '<br>';
			}
			echo '</td>';
		}
		echo '</tr></table>';

	}

	static function get_tag($name, $attribs, $close=false, $no_enc=false) {
		$ret = "<$name";
		foreach($attribs as $k => $v) {
			if(! $no_enc) $v = to_html($v);
			$ret .= " $k=\"$v\"";
		}
		if($close && ($name == 'div' || $name == 'script' || $name == 'table'))
			return "$ret></$name>";
		else if($close)
			return "$ret/>";
		return "$ret>";
	}

}

