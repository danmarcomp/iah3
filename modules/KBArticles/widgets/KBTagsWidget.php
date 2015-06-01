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


require_once 'include/layout/forms/FormTableSection.php';

class KBTagsWidget extends FormTableSection {

	var $tags_text = null;
	var $tags_list = array();

	function init($params, $model=null) {
		parent::init($params, $model);
		if(! $this->id)
			$this->id = 'kb_tags_widget';
	}
	
	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, array $context)
	{
		if($gen->getLayout()->getEditingLayout()) {
			$params = array();
			return $this->renderOuterTable($gen, $parents, $context, $params);		
		}

		$lstyle = $gen->getLayout()->getType();
		if ($lstyle == 'editview') return $this->renderHtmlEdit($gen, $row_result, $parents, $context);
		else return $this->renderHtmlView($gen, $row_result, $parents, $context);
	}
	
	function renderHtmlView(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, array $context)
	{
		global $mod_strings;
		$tagIds = array();
		$tags = array();
		$id = $row_result->getField('id');
		$lq = new ListQuery('kb_articles_tags');
		$lq->addSimpleFilter('article_id', $id);
		$lr = $lq->runQuery();
		foreach ($lr->getRowIndexes() as $idx) {
			$rr = $lr->getRowResult($idx);
			$tagIds[] = $rr->getField('tag_id');
		}
		if (!empty($tagIds)) {
			$lq = new ListQuery('kb_tags');
			$lq->addSimpleFilter('id', $tagIds);
			$lr = $lq->runQuery();
			foreach ($lr->getRowIndexes() as $idx) {
				$rr = $lr->getRowResult($idx);
				$tags[] = array($rr->getField('id'), $rr->getField('tag'));
			}
		}
		$html = <<<EOH
<table id="kb_tags_block" style="width:100%; margin-top:0.5em;" class="tabForm">
<tr>
	<th class="dataLabel" align="left">
		<h4 class="dataLabel">{$mod_strings['LBL_TAGS']}</h4>
	</th>
</tr>
<tr><td>
EOH;
		foreach ($tags as $i => $tag) {
			if ($i) $html .= ', ';
			$html .= '<a href="index.php?module=KBArticles&action=index&query=true&include_tags=' . $tag[0] . '">' . $tag[1] . '</a>';
		}
		$html .= <<<EOH
</td></tr>
</table>
EOH;
		return $html;
	}

	function renderHtmlEdit(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, array $context)
	{
		global $mod_strings;
		global $pageInstance;
		if (!is_null($this->tags_text)) $this->loadTagsText($row_result);
		$html = <<<EOH
<table id="kb_tags_block" style="width:100%; margin-top:0.5em;" class="tabForm">
<tr>
	<th class="dataLabel" align="left">
		<h4 class="dataLabel">{$mod_strings['LBL_TAGS']}</h4>
	</th>
</tr>
<tr><td class="yui-skin-sam">
	<div class="yui-ac" style="width:40em">
	<input id="tags_values" name='tags_values' type="hidden">
	<input id="tags_text" name='tags_text' type="text" tabindex='1' size='30' value="{$this->tags_text}" style="width:30em"> <br >
	<div id="tags_container"></div> 
	</div>
</td></tr>
</table>
EOH;
		$js = 'KBTagsAutocomplete.init(false);';
        $pageInstance->add_include_group('yui_autocomplete', null, LOAD_PRIORITY_FOOT);
		$pageInstance->add_js_literal($js, null, LOAD_PRIORITY_FOOT);
		return $html;
	}

	function loadTagsText(RowResult $row_result)
	{
		$tagIds = array();
		$id = $row_result->getField('id');
		$lq = new ListQuery('kb_articles_tags');
		$lq->addSimpleFilter('article_id', $id);
		$lr = $lq->runQuery();
		foreach ($lr->getRowIndexes() as $idx) {
			$rr = $lr->getRowResult($idx);
			$tagIds[] = $rr->getField('tag_id');
		}
		if (empty($tagIds)) {
			$this->tags_text = '';
			return;
		}
		$tags = array();
		$lq = new ListQuery('kb_tags');
		$lq->addSimpleFilter('id', $tagIds);
		$lr = $lq->runQuery();
		foreach ($lr->getRowIndexes() as $idx) {
			$rr = $lr->getRowResult($idx);
			$tags[] = $rr->getField('tag');
		}
		$this->tags_text = join(', ', $tags);
	}

	function loadUpdateRequest(RowUpdate &$update, array $input) {
		$tagsNames = explode(',', array_get_default($input, 'tags_text', ''));
        $tagsNames = array_map('trim', $tagsNames);
        $lq = new ListQuery('kb_tags');
		$lq->addSimpleFilter('tag', $tagsNames);
		$lr = $lq->runQuery();

        $existentTags = array();
		foreach ($lr->getRowIndexes() as $idx) {
			$rr = $lr->getRowResult($idx);
			$existentTags[] = $rr->getField('tag');
			$this->tags_list[] = $rr->getField('id');
		}

		$this->tags_text = join(', ', $existentTags);
        $update->setRelatedData($this->id.'_rows', $this->extractNewTags($tagsNames, $existentTags));
    }

	function validateInput(RowUpdate &$update) {
		return true;
	}
	
	function afterUpdate(RowUpdate &$update) {
        $this->addNewTags($update);
        $id = $update->getPrimaryKeyValue();

		$lq = new ListQuery('kb_articles_tags');
		$lq->addSimpleFilter('article_id', $id);
		$lq->filter_deleted = false;
		$lr = $lq->runQuery();
		$add_tags = $this->tags_list;

		foreach ($lr->getRowIndexes() as $idx) {
			$rr = $lr->getRowResult($idx);
			$tid = $rr->getField('tag_id');
			if (($idx = array_search($tid, $add_tags)) !== false) {
				unset($add_tags[$idx]);
				if ($rr->getField('deleted')) {
					$ru = new RowUpdate($rr);
					$ru->markDeleted(false);
				}
			} else {
				$ru = new RowUpdate($rr);
				$ru->markDeleted(true);
			}
		}

		foreach ($add_tags as $tid) {
			$ru = new RowUpdate('kb_articles_tags');
			$ru->request = array(
				'article_id' => $id,
				'tag_id' => $tid,
			);
			$ru->loadRequest();
			$ru->insertRow();
		}

	}

    function addNewTags(RowUpdate &$update) {
        $new_tags = $update->getRelatedData($this->id.'_rows');

        if (sizeof($new_tags) > 0) {
            for ($i = 0; $i < sizeof($new_tags); $i++) {
                $tag_update = RowUpdate::blank_for_model('kb_tags');
                $tag_update->set('tag', $new_tags[$i]);
                if ($tag_update->save())
                    $this->tags_list[] = $tag_update->getPrimaryKeyValue();
            }
        }
    }

    function extractNewTags($inputTags, $existentTags) {
        $result = array();
        if (sizeof($inputTags) > 0) {
            for ($i = 0; $i < sizeof($inputTags); $i++) {
                if (! empty($inputTags[$i]) && ! in_array($inputTags[$i], $existentTags))
                    $result[] = htmlspecialchars($inputTags[$i]);
            }
        }
        return $result;
    }
}
?>