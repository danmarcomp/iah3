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
require_once('include/ListView/ListFormatter.php');
require_once('include/layout/forms/FilterForm.php');

$query = array_get_default($_POST, 'query');
$clear = array_get_default($_POST, 'filter_clear');
$search = false;

if ($query == 1 && $clear != 1)
    $search = true;

$list_id = ListFormatter::createId();

$fq = new ListQuery('Forum');
$ff = new FilterForm($fq, 'FilterForm', $list_id . '-FilterForm');
$ff->loadFilterLayout('Standard');

if ($search) {
	$ff->loadArrayFilter($_POST);
	$clauses = $ff->getFilterClauses();
	$search = !empty($clauses);
}

echo '<div class="listViewOuter listViewPrimary " style="position: relative; " id="'.$list_id.'-outer">';

if (!$search) {
	echo get_module_title('Forums', $mod_strings['LBL_LIST_FORM_TITLE'], false);
} else {
	echo get_module_title('Forums', $mod_strings['LBL_SEARCH_RESULTS_TITLE'], false);
}

echo '<div class="listViewTitle standalone"><div class="listViewSearchDiv">';
echo $ff->formatSearch($list_id, 'Forums', 'ListView');
echo '</div></div><br />';


if (!$search) {
	$lq = new ListQuery('ForumTopic', array('name'));
	$cats = $lq->fetchAll('list_order', 'ASC');
	foreach ($cats->getRowIndexes() as $idx) {
		$cat = $cats->getRowResult($idx);

		$fmt = new ListFormatter('ForumTopic', 'forums');
		$fmt->setParentKey($cat->getField('id'));

		$fmt->setTitle($cat->getField('name'));
		$fmt->setFormat('html', 'subpanel');

		$fmt->loadLayout('Standard');

		$result = $fmt->getFormattedResult();

		$fmt->show_mass_update = false;
		$fmt->show_filter = false;
		$fmt->show_checks = false;
		$fmt->can_shrink = true;
		$fmt->show_navigation = false;
		$fmt->no_sort = true;

		$fmt->outputHtml($result);
		echo '<br/>';
	}
} else {
	$found = false;

	$fmt = new ListFormatter('Thread');
	$fmt->layout_module = 'Threads';
	$fmt->setTitle(translate('LBL_SEARCH_THREADS'));
	$fmt->setFormat('html');
	$fmt->loadLayout('Standard');

	$fmt->show_mass_update = false;
	$fmt->show_filter = false;
	$fmt->show_checks = false;
	$fmt->can_shrink = true;
	$fmt->show_navigation = false;
	$fmt->no_sort = true;
	
	if (isset($clauses['title'])) {
		$title = $clauses['title']['value'];
		$fmt->list_query->addSimpleFilter('title', $title, 'LIKE');
	}
	if (isset($clauses['body'])) {
		$body = $clauses['body']['value'];
		$fmt->list_query->addSimpleFilter('description_html', $body, 'LIKE');
	}
    if (isset($clauses['created_by_user'])) {
        $created_by = $clauses['created_by_user']['value'];
        $fmt->list_query->addSimpleFilter('created_by', $created_by);
    }

	$result = $fmt->getFormattedResult(0, -1);

	if ($result->getResultCount()) {
		$found = true;
		$fmt->outputHtml($result);
		echo '<br/>';
	}

	$fmt = new ListFormatter('Post');
	$fmt->layout_module = 'Posts';
	$fmt->setTitle(translate('LBL_SEARCH_POSTS'));
	$fmt->setFormat('html');
	$fmt->loadLayout('Search');

	$fmt->show_mass_update = false;
	$fmt->show_filter = false;
	$fmt->show_checks = false;
	$fmt->can_shrink = true;
	$fmt->show_navigation = false;
	$fmt->no_sort = true;
	
	if (isset($clauses['title'])) {
		$title = $clauses['title']['value'];
		$fmt->list_query->addSimpleFilter('title', $title, 'LIKE');
	}
	if (isset($clauses['body'])) {
		$body = $clauses['body']['value'];
		$fmt->list_query->addSimpleFilter('description_html', $body, 'LIKE');
	}

	$result = $fmt->getFormattedResult(0, -1);
	if ($result->getResultCount()) {
		$found = true;
		$fmt->outputHtml($result);
		echo '<br/>';
	}
}

global $pageInstance;
$pageInstance->add_js_literal("sListView.init('{$list_id}', {});", null, LOAD_PRIORITY_FOOT);
echo '</div>';
?>