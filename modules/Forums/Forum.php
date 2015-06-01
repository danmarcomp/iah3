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

/* Include all other system or application files that you need to reference here.*/


require_once('data/SugarBean.php'); /*Include this file since we are extending SugarBean*/
require_once('include/utils.php'); /* Include this file if you want access to Utility methods such as return_module_language,return_mod_list_strings_language, etc ..*/
require_once('modules/ForumTopics/ForumTopic.php');

class Forum extends SugarBean {
	/* Foreach instance of the bean you will need to access the fields in the table.
	 * So define a variable for each one of them, the varaible name should be same as the field name
	 * Use this module's vardef file as a reference to create these variables.
	 */
	var $id;

	var $date_entered;
	var $category;
	var $created_by;
	var $date_modified;
	var $modified_user_id;
	var $deleted;
	var $title;
	var $description;

	var $category_ranking;

	/* End field definitions*/

	/* variable $table_name is used by SugarBean and methods in this file to constructs queries
	 * set this variables value to the table associated with this bean.
	 */
	var $table_name = 'forums';

	/*This  variable overrides the object_name variable in SugarBean, wher it has a value of null.*/
	var $object_name = 'Forum';

	/**/
	var $module_dir = 'Forums';

	/* This is a legacy variable, set its value to true for new modules*/
	var $new_schema = true;

	/* $column_fields holds a list of columns that exist in this bean's table. This list is referenced
	 * when fetching or saving data for the bean. As you modify a table you need to keep this up to date.
	 */
	var $column_fields = array(
		'id',

		'date_entered',
		'category',
		'created_by',
		'date_modified',
		'modified_user_id',
		'created_by_user_name',
		'deleted',
		'title',
		'description',
	);

	/*This bean's constructor*/
	function Forum() {
		/*Call the parent's constructor which will setup a database connection, logger and other settings.*/
		parent::SugarBean();
	}

	/* This method should return the summary text which is used to build the bread crumb navigation*/
	/* Generally from this method you would return value of a field that is required and is of type string*/
	function get_summary_text() {
		return "$this->title";
	}

	function bean_implements($interface) {
		switch ($interface) {
			case 'ACL':
				return true;
		}
		return false;
	}

	public static function get_latest_thread($forum_id) {
		$bean_name = AppConfig::module_primary_bean('Threads');
		$lq = new ListQuery($bean_name, array('title'));
		$lq->addFilterClause(array('field' => 'forum_id', 'value' => $forum_id));
		$row = $lq->runQuerySingle();

		if ($row->failed) {
			$out = '---';
		} else {
			// TODO: display as link
			//$out = '<a href="index.php?action=DetailView&amp;module=Threads&amp;record=' . $row->getField('id') . '&amp;">' . $row->getField('title') . '</a>';
			$out = $row->getField('title');
		}
		return $out;
	}

	public static function get_latest_post_by($forum_id) {
		$f = new Forum();

		$result = $f->db->fetchByAssoc($f->db->query(
				"SELECT p.created_by, p.date_entered
				FROM threads t
				INNER JOIN posts p ON p.thread_id = t.id AND p.deleted = 0
				WHERE t.forum_id = '" . $f->db->quote($forum_id) . "'
				AND t.deleted = 0
				ORDER BY p.date_entered
				LIMIT 1"
			)
		);

		if (empty($result)) {
			$out = '---';
		} else {
			$out = get_assigned_user_name($result['created_by']);
		}

		return $out;
	}

	public static function get_latest_post_time($forum_id) {
		$f = new Forum();

		$result = $f->db->fetchByAssoc($f->db->query(
				"SELECT p.created_by, p.date_entered
				FROM threads t
				INNER JOIN posts p ON p.thread_id = t.id AND p.deleted = 0
				WHERE t.forum_id = '" . $f->db->quote($forum_id) . "'
				AND t.deleted = 0
				ORDER BY p.date_entered
				LIMIT 1"
			)
		);

		if (empty($result)) {
			$out = '---';
		} else {
			$out = $GLOBALS['timedate']->to_display_date_time($result['date_entered']);
		}

		return $out;
	}
}

?>
