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
/*Include this file if you want to access sugar specific settings*/

require_once('data/SugarBean.php'); /*Include this file since we are extending SugarBean*/
require_once('include/utils.php'); /* Include this file if you want access to Utility methods such as return_module_language,return_mod_list_strings_language, etc ..*/

require_once('modules/Forums/Forum.php'); /* included to retrieve the forum from the forum_id */

class Thread extends SugarBean {
	/* Foreach instance of the bean you will need to access the fields in the table.
			 * So define a variable for each one of them, the varaible name should be same as the field name
			 * Use this module's vardef file as a reference to create these variables.
			 */
	var $id;
	var $date_entered;
	var $created_by;
	var $date_modified;
	var $modified_user_id;
	var $created_by_user;
	var $deleted;
	var $title;
	var $body;
	var $forum_id;
	var $is_sticky;
	var $view_count;
	var $description_html;
	var $postcount;

	/* relationship fields */
	var $account_id;
	var $bug_id;
	var $case_id;
	var $opportunity_id;
	var $project_id;
	/* end relationship fields */

	/* relationship tables */
	var $rel_accounts_table = "accounts_threads";
	var $rel_bugs_table = "bugs_threads";
	var $rel_cases_table = "cases_threads";
	var $rel_opportunities_table = "opportunities_threads";
	var $rel_project_table = "project_threads";
	/* end relationship tables */

	// This is used to retrieve related fields from form posts.
	var $additional_column_fields = Array('account_id', 'bug_id', 'case_id', 'opportunity_id', 'project_id');

	var $relationship_fields = Array('account_id' => 'accounts', 'bug_id' => 'bugs',
		'case_id' => 'cases', 'opportunity_id' => 'opportunities',
		'project_id' => 'project',
	);

	/* End field definitions*/

	/* variable $table_name is used by SugarBean and methods in this file to constructs queries
			 * set this variables value to the table associated with this bean.
			 */
	var $table_name = 'threads';

	/*This  variable overrides the object_name variable in SugarBean, wher it has a value of null.*/
	var $object_name = 'Thread';

	/**/
	var $module_dir = 'Threads';

	/* This is a legacy variable, set its value to true for new modules*/
	var $new_schema = true;

	/* $column_fields holds a list of columns that exist in this bean's table. This list is referenced
			 * when fetching or saving data for the bean. As you modify a table you need to keep this up to date.
			 */
	var $column_fields = Array(
		'id',
		'date_entered',
		'created_by',
		'date_modified',
		'modified_user_id',
		'deleted',
		'title',
		'body',
		'forum_id',
		'is_sticky',
		'recent_post_title',
		'recent_post_id',
		'recent_post_modified_id',
		'recent_post_modified_name',
		'postcount',
		'view_count',
		'description_html',
	);

	/* This is the list of required fields, It is used by some of the utils methods to build the required fields validation JavaScript */
	/* The script is only generated for the Edit View*/
	var $required_fields = array('title' => 1);

	/*This bean's constructor*/
	function Thread() {
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

	function build_generic_where_clause($title, $params = array()) {
		$where = "threads.deleted=0 ";
		$where .= "and threads.title like ('%" . $GLOBALS['db']->quote($title) . "%') ";
		if (!empty($params['body']))
			$where .= " and threads.description_html like ('%" . $GLOBALS['db']->quote($params['body']) . "%') ";

		$user = array_get_default($params, 'user');
		if (isset($user) && is_array($user) && !(count($user) == 1 && $user[0] == "")) {
			$count = count($user);
			if ($count > 0) {
				$where .= " AND ";
				$where .= "threads.created_by IN(";
				foreach ($user as $key => $val) {
					$where .= "'$val'";
					$where .= ($key == $count - 1) ? ")" : ", ";
				}
			}
		}

		return $where;
	}

	function getRelated() {
		require_once 'modules/Project/Project.php';
		require_once 'modules/Accounts/Account.php';
		require_once 'modules/Cases/Case.php';
		require_once 'modules/Bugs/Bug.php';
		$ret = array();
		$this->load_relationship('accounts');
		$this->load_relationship('bugs');
		$this->load_relationship('cases');
		$this->load_relationship('project');
		foreach ($this->accounts->getBeans(new Account) as $account) {
			$ret[] = $account;
		}
		foreach ($this->cases->getBeans(new aCase) as $case) {
			$ret[] = $case;
		}
		foreach ($this->bugs->getBeans(new Bug) as $bug) {
			$ret[] = $bug;
		}
		foreach ($this->project->getBeans(new Project) as $project) {
			$ret[] = $project;
		}
		return $ret;
	}

	static function get_notification_recipients(NotificationManager $manager) {
		global $db;
		require_once 'include/database/ListQuery.php';
		$fields = array('first_name', 'last_name', 'id', 'receive_notifications', 'email1', 'email2', 'user_name');
		$id = $manager->updated_bean->getField('id');
		$lq = new ListQuery('Thread', null, array('link_name' => 'users'));
		$lq->setParentKey($id);
		$lq->addSimpleFilter('id', $manager->updated_bean->getField('modified_user_id'), '!=');
		$lq->addFields($fields);
		$result = $lq->runQuery();
		foreach ($result->getRowIndexes() as $idx) {
			$row = $result->getRowResult($idx);
			$user = array(
				'full_name' => $row->getField('first_name') . ' ' . $row->getField('last_name'),
			);
			foreach ($fields as $f) {
				$user[$f] = $row->getField($f);
			}
			$list[] = $user;
		}
		return $list;
	}

    public static function render_created_user($user_id, $user_name) {
        $file_url = "include/images/iah/unknown.png";
        $result = ListQuery::quick_fetch('User', $user_id, array('name', 'photo_filename'));

        if ($result) {
            if ($result->getField('photo_filename')) {
                require_once('include/upload_file.php');
                $upload = new UploadFile($result->fields['photo_filename']);
                $file_path = $result->getField('photo_filename') ? $upload->get_file_path($result->getField('photo_filename')) : '';

                if(file_exists($file_path)) {
                    $url = $upload->get_url($result->getField('photo_filename'));
                    if (! empty($url))
                        $file_url = $url;
                }

            }
        }

        $photo = '<div class="image-ref" style="padding: 3px 20px;">'.EditableForm::get_tag('img', array('src' => $file_url, 'style' => 'max-width: 75px; max-height: 90px;'), true).'</div>';

        if (! AppConfig::is_mobile() || AppConfig::is_mobile_module('Users')) {
            $link = '<a class="tabDetailViewDFLink" href="'.get_detail_link($user_id, 'Users').'">'.$user_name.'</a>';
        } else {
            $link = $user_name;
        }

        global $image_path;
        $link = get_image($image_path . 'Users', 'style="vertical-align: text-top"') . '&nbsp;' . $link;

        return $photo . $link;
    }

	public static function get_latest_post($thread_id) {
		$bean_name = AppConfig::module_primary_bean('Posts');
		$lq = new ListQuery($bean_name, array('title'));
		$lq->addFilterClause(array('field' => 'thread_id', 'value' => $thread_id));
		$row = $lq->runQuerySingle();

		if ($row->failed) {
			$out = '---';
		} else {
			// TODO: display as link
			//$out = '<a href="index.php?action=DetailView&amp;module=Threads&amp;record=' . $thread_id . '&amp;">' . $row->getField('title') . '</a>';
			$out = $row->getField('title');
		}
		return $out;
	}

	public static function get_latest_post_by($thread_id) {
		$bean_name = AppConfig::module_primary_bean('Posts');
		$lq = new ListQuery($bean_name, array('created_by'));
		$lq->addFilterClause(array('field' => 'thread_id', 'value' => $thread_id));
		$row = $lq->runQuerySingle();

		if ($row->failed) {
			$out = '---';
		} else {
			$out = get_assigned_user_name($row->getField('created_by'));
		}

		return $out;
	}

	public static function counters_inc_created(RowUpdate $upd) {
		if ($upd->new_record) {
			$GLOBALS['db']->query(
				"UPDATE forums
				SET threadcount = threadcount + 1, threadandpostcount = threadandpostcount + 1
				WHERE id = '" . $GLOBALS['db']->quote($upd->saved['forum_id']) . "'"
			);
		}
	}

	public static function counters_dec_deleted(RowUpdate $upd) {
		// 1 for thread and some count for posts that will not be available any more
		$dec = 1 + $upd->orig_row['postcount'];
		$GLOBALS['db']->query(
			"UPDATE forums
				SET threadcount = threadcount - 1, threadandpostcount = threadandpostcount - {$dec}
				WHERE id = '" . $GLOBALS['db']->quote($upd->orig_row['forum_id']) . "'"
		);
	}

	public static function counters_inc_view(DetailManager $mgr) {
		$GLOBALS['db']->query(
			"UPDATE threads
				SET view_count = view_count + 1
				WHERE id = '" . $GLOBALS['db']->quote($mgr->getRecord()->getField('id')) . "'"
		);
	}

	public static function current_user_subscribed($id)
	{
		global $current_user;
		$lq = new ListQuery('Thread', null, array('link_name' => 'users'));
		$lq->addFields(array('id'));
		$lq->setParentKey($id);
		$lq->addFilterPrimaryKey($current_user->id);
		$result = $lq->runQuery();
		return count( $lq->getResultIds($result));
	}
}

?>
