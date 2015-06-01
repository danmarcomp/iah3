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


require_once('data/SugarBean.php');
require_once('include/utils.php');

class KBArticle extends SugarBean {	

	// Stored fields
  	var $id;
	var $name;
	var $summary;
	var $description;
	var $deleted;
	var $portal_active;

	var $table_name = 'kb_articles';
	var $object_name = 'KBArticle';
	var $module_dir = 'KBArticles';
	var $new_schema = true;


	// This is used to retrieve related fields from form posts.
	var $additional_column_fields = Array(
		'case_id',
	);
	var $relationship_fields = array(
		'case_id' => 'cases',
	);

	
	function KBArticle() {
		parent::SugarBean();
	}


	function get_summary_text()
	{
		return "$this->name";
	}

	/// This function fills in data for the list view only.
	function fill_in_additional_list_fields()
	{
	}


	/// This function fills in data for the detail view only.
	function fill_in_additional_detail_fields()
	{
	}


	function get_list_view_data()
	{
		$temp_array = $this->get_list_view_array();
		return $temp_array;
	}


	function save($check_notify = FALSE) {
	
		return parent::save($check_notify);
	}

	function bean_implements($interface) {
		switch($interface) {
			case 'ACL':return true;
		}
		return false;
	}

	function get_tags()
	{
		$ret = array();
		$query = "SELECT kb_tags.* FROM kb_tags LEFT JOIN kb_articles_tags ON kb_articles_tags.tag_id = kb_tags.id WHERE kb_articles_tags.article_id='" . PearDatabase::quote($this->id) . "' AND kb_articles_tags.deleted = 0";
		$res = $this->db->query($query, true);
		while($row = $this->db->fetchByAssoc($res)) {
			$ret[$row['id']] = $row;
		}
		return $ret;
	}

	function search_by_tag_dummy()
	{
		return '1';
	}

	function mark_deleted($id)
	{
		global $db;
		parent::mark_deleted($id);
		$db->query("UPDATE kb_articles_tags SET deleted=1 WHERE article_id='" . PearDatabase::quote($id) . "'", true);
	}

	function search_by_tag()
	{
		$include = array();
		if (!empty($_REQUEST['plus_tag'])) {
			$include = explode(',', $_REQUEST['plus_tag']);
		}
		$exclude = array();
		if (!empty($_REQUEST['minus_tag'])) {
			$exclude = explode(',', $_REQUEST['minus_tag']);
		}
		if (!empty($include) || !empty($exclude)) {
			$ids = $this->find_by_tags($include, $exclude);
			if (!empty($ids)) {
				return "(kb_articles.id IN ('" . join("', '", $ids) . "'))";
			} else {
				return ' 0 ';
			}
		} else {
			return '1';
		}
	}

	function find_by_tags($include = array(), $exclude = array(), $sort = '', $offset = 0, $limit=10000000, $for_portal = false)
	{
		$query = $this->create_tag_query($include, $exclude, $sort, $for_portal);
		$query .= " LIMIT " . (int)$offset . ", " . (int)$limit;
		$res = $this->db->query($query, true);
		$ret = array();
		while($row = $this->db->fetchByAssoc($res)) {
			$ret[] = $row['article_id'];
		}
		return $ret;
	}

	function count_by_tags($include = array(), $exclude = array(), $for_portal = false)
	{
		$query = $this->create_tag_query($include, $exclude, $for_portal);
		$res = $this->db->query($query, true);
		return mysql_num_rows($res);
	}
	
	function create_tag_query($include = array(), $exclude = array(), $sort = '', $for_portal = false)
	{
		$where = '';
		$count1 = count($include);
		$having = "cnt1 = " . $count1 . " AND cnt2 = 0 ";
		if (!empty($include) || !empty($exclude)) {
			$where .= " AND tag_id IN('" . join("', '", array_merge($include, $exclude)) . "')";
		}
		if ($for_portal) {
			$where .= ' AND kb_articles.portal_active ';
		}
		$query = " SELECT kb_articles_tags.article_id , COUNT(IF(tag_id IN('" . join("', '", $include) . "'), 1, NULL)) AS cnt1, COUNT(IF(tag_id IN('" . join("', '", $exclude) . "'), 1, NULL)) AS cnt2 FROM kb_articles_tags LEFT JOIN kb_articles ON kb_articles.id=kb_articles_tags.article_id WHERE kb_articles_tags.deleted = 0 $where  GROUP BY kb_articles_tags.article_id " ;
		$query .= ' HAVING ' . $having;
		if ($sort) {
			$query .= " ORDER BY $sort ";
		}
		return $query;
	}

	function create_export_query(&$order_by, &$where) {
		$q = $this->_get_default_export_query($order_by, $where);
		return $q;
	}

    static function init_record(RowUpdate &$upd, $input) {
        $update = array();

        if (! empty($input['case_id'])) {
            $fields = array('name' => 'name', 'description' => 'summary', 'resolution' => 'description');
            $case = ListQuery::quick_fetch_row('aCase', $input['case_id'], array_keys($fields));

            if ($case != null) {
                foreach ($fields as $case_field => $kb_field) {
                    if (isset($case[$case_field]))
                        $update[$kb_field] = $case[$case_field];
                }
            }
        }

        $upd->set($update);
    }

    static function add_additional_hiddens(DetailManager $mgr) {
        if (! empty($_REQUEST['case_id']))
            $mgr->layout->addFormHiddenFields(array('case_id' => $_REQUEST['case_id']), false);
    }

    static function set_related(RowUpdate $upd) {
        if (! empty($_REQUEST['case_id']))
            $upd->addUpdateLink('cases', $_REQUEST['case_id']);
    }
}
?>