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

if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$json_supported_actions['portal_get_kb_tags'] = array('login_required' => 'portal');

function json_portal_get_kb_tags()
{
	global $db;
	$ret = array();
	$query = "SELECT kb_tags.*, COUNT(kb_articles.id) AS narticles FROM kb_tags LEFT JOIN kb_articles_tags ON kb_articles_tags.tag_id = kb_tags.id AND kb_articles_tags.deleted = 0 LEFT JOIN kb_articles ON kb_articles_tags.article_id = kb_articles.id AND kb_articles.deleted = 0 AND kb_articles.portal_active GROUP BY kb_tags.id HAVING narticles > 0";
	$res = $db->query($query);
	while ($row = $db->fetchByAssoc($res)) {
		$ret[] = array(
			'id' => $row['id'],
			'name' => $row['tag'],
			'narticles' => $row['narticles'],
		);
	}
	$ret = array(
		'kb_tag_list' => $ret,
	);
	json_return_value($ret);
}

$json_supported_actions['portal_get_kb_articles'] = array('login_required' => 'portal');

function json_portal_get_kb_articles()
{
	global $db;
	$params = array(
		'include',
		'exclude',
		'order',
		'offset',
		'limit',
	);
	foreach ($params as $param) {
		$$param = array_get_default($_REQUEST, $param);
	}
	if (empty($include)) {
		$include = array();
	} else {
		$include = explode(',', $include);
	}
	if (empty($exclude)) {
		$exclude = array();
	} else {
		$exclude = explode(',', $exclude);
	}
	if (!empty($include) || !empty($exclude)) {
		require_once 'modules/KBArticles/KBArticle.php';
		$seed = new KBArticle;
		$ids = $seed->find_by_tags($include, $exclude, $order, $offset, $limit, true);
	} else {
		$ids = array();
	}
	$output_list = array();
	foreach($ids as $id) {
		$seed = new KBArticle;
		$seed->retrieve($id);
		$output_list[] = $seed;
	}
	$output_list = json_convert_bean_list($output_list, $select_fields);
	$ret = array(
		'list' => $output_list,
		'result_count' => count($output_list),
		'total_count' => $seed->count_by_tags($include, $exclude, true),
	);
	json_return_value($ret);
}


