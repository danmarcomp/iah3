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

require_once 'include/layout/forms/FormButton.php';
require_once 'include/layout/forms/FilterElement.php';

class KBTagsFilterButton extends FormButton implements FilterElement
{
	public function getFilterClause($filter)
	{
		$include = array_get_default($filter, 'include_tags');
		if (empty($include)) $include = array();
		else $include = explode('^,^', $include);
		
		$exclude = array_get_default($filter, 'exclude_tags');
		if (empty($exclude)) $exclude = array();
		else $exclude = explode('^,^', $exclude);

		if (empty($include) && empty($exclude)) return 1;

		$cntExpr1 = " COUNT(IF(tag_id IN('" . join("', '", $include) . "'), 1, NULL))";
		$cntExpr2 = " COUNT(IF(tag_id IN('" . join("', '", $exclude) . "'), 1, NULL))";
		$where = '';
		$count1 = count($include);
		$having = "cnt1 = $count1  AND cnt2  = 0 ";
		//$where .= " AND tag_id IN('" . join("', '", array_merge($include, $exclude)) . "')";
		$query = "SELECT article_id FROM (SELECT kb_articles_tags.article_id, $cntExpr1 cnt1, $cntExpr2 cnt2 FROM kb_articles_tags LEFT JOIN kb_articles ON kb_articles.id=kb_articles_tags.article_id WHERE kb_articles_tags.deleted = 0 $where  GROUP BY kb_articles_tags.article_id " ;
		$query .= ' HAVING ' . $having;
		$query .= ' ) sc';
		
		$ret = "(id IN ($query))";
		return $ret;
	}
	
	public function loadFilter(&$filter, $input, $prefix)
	{
		$filter['include_tags'] = array_get_default($input, 'include_tags');
		$filter['exclude_tags'] = array_get_default($input, 'exclude_tags');
	}

	public function render($form, $result)
	{
		$prefix = $form->getFieldNamePrefix();
		$button = array(
			'label' => translate('LBL_FILTER_TAGS', 'KBArticles'),
			'type' => 'button',
			'onclick' => "KBTagsFilter.displayTagsFilter(this.form)",
		);
		$include = array_get_default($_REQUEST, $prefix . 'include_tags');
		$exclude = array_get_default($_REQUEST, $prefix . 'exclude_tags');
		$html = $form->renderField($result, $button);
		$html .= "<input type=\"hidden\" name=\"{$prefix}include_tags\" value=\"{$include}\">";
		$html .= "<input type=\"hidden\" name=\"{$prefix}exclude_tags\" value=\"{$exclude}\">";
		return $html;
	}

}

