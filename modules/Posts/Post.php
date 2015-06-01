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

require_once('modules/Threads/Thread.php'); // for pulling parent forum

class Post extends SugarBean {
	/* Foreach instance of the bean you will need to access the fields in the table.
	 * So define a variable for each one of them, the varaible name should be same as the field name
	 * Use this module's vardef file as a reference to create these variables.
	 */
	var $name;
	var $id;
	var $date_entered;
	var $created_by;
	var $date_modified;
	var $modified_user_id;
	var $deleted;
	var $title;
	var $description_html;
	var $thread_id;

	// non-db
	var $created_by_user;
	var $modified_by_user;
	var $thread_name;

	/* End field definitions*/

	/* variable $table_name is used by SugarBean and methods in this file to constructs queries
	 * set this variables value to the table associated with this bean.
	 */
	var $table_name = 'posts';

	/*This  variable overrides the object_name variable in SugarBean, wher it has a value of null.*/
	var $object_name = 'Post';

	/**/
	var $module_dir = 'Posts';

	/* This is a legacy variable, set its value to true for new modules*/
	var $new_schema = true;

	/* $column_fields holds a list of columns that exist in this bean's table. This list is referenced
	 * when fetching or saving data for the bean. As you modify a table you need to keep this up to date.
	 */
	var $column_fields = Array(
		'id',
		'title',
		'description_html',
		'thread_id',
	);

	// This is used to retrieve related fields from form posts.
//	var $additional_column_fields = Array('account_id','bug_id');
//	var $relationship_fields = Array('account_id'=>'parent_id', 'bug_id'=>'parent_id', );

	/* This is the list of required fields, It is used by some of the utils methods to build the required fields validation JavaScript */
	/* The script is only generated for the Edit View*/
	var $required_fields = array('title' => 1);

	/*This bean's constructor*/
	function Post() {
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
		$where = "deleted=0 ";
		$where .= "and title like ('%" . $GLOBALS['db']->quote($title) . "%') ";
		if (!empty($params['body']))
			$where .= " and description_html like ('%" . $GLOBALS['db']->quote($params['body']) . "%') ";

		$user = array_get_default($params, 'user');
		if (isset($user) && is_array($user) && !(count($user) == 1 && $user[0] == "")) {
			$count = count($user);
			if ($count > 0) {
				$where .= " AND ";
				$where .= "created_by IN(";
				foreach ($user as $key => $val) {
					$where .= "'$val'";
					$where .= ($key == $count - 1) ? ")" : ", ";
				}
			}
		}

		return $where;
	}

	static function send_notification(RowUpdate $upd) {
		if ($upd->new_record) {
			$thread = ListQuery::quick_fetch('Thread', $upd->getField('thread_id'));

			if ($thread) {
				$vars = array(
					'THREAD_SUBJECT' => array('field' => 'title', 'in_subject' => true),
					'REPLIED_BY' => array('value' => get_assigned_user_name($upd->getField('created_by'))),
					'REPLY_SUBJECT' => array('value' => $upd->getField('title'))
				);

				$thread_upd = new RowUpdate($thread);
				$manager = new NotificationManager($thread_upd, 'ThreadReply', $vars);
				$manager->sendMails();
			}
		}
	}

	public static function counters_inc_created(RowUpdate $upd) {
		if ($upd->new_record) {
			$GLOBALS['db']->query(
				"UPDATE threads
				SET postcount = postcount + 1
				WHERE id = '" . $GLOBALS['db']->quote($upd->saved['thread_id']) . "'"
			);

			$bean_name = AppConfig::module_primary_bean('Threads');
			$row = ListQuery::quick_fetch($bean_name, $upd->saved['thread_id'], array('forum_id'));

			$GLOBALS['db']->query(
				"UPDATE forums
				SET threadandpostcount = threadandpostcount + 1
				WHERE id = '" . $GLOBALS['db']->quote($upd->getField('forum_id')) . "'"
			);
		}
	}

	public static function counters_dec_deleted(RowUpdate $upd) {
		$GLOBALS['db']->query(
			"UPDATE threads
				SET postcount = postcount - 1
				WHERE id = '" . $GLOBALS['db']->quote($upd->orig_row['thread_id']) . "'"
		);

		$bean_name = AppConfig::module_primary_bean('Threads');
		$row = ListQuery::quick_fetch($bean_name, $upd->orig_row['thread_id'], array('forum_id'));

		$GLOBALS['db']->query(
			"UPDATE forums
				SET threadandpostcount = threadandpostcount - 1
				WHERE id = '" . $GLOBALS['db']->quote($row->getField('forum_id')) . "'"
		);
	}

	public static function display_edit(DetailManager $mgr) {
		$hiddens = array(
			'thread_id' => $_REQUEST['thread_id'],
		);
		$mgr->getLayout()->addFormHiddenFields($hiddens);
		if (empty($_REQUEST['record'])) {
			$reply = array_get_default($_REQUEST, 'reply');
			$quote = array_get_default($_REQUEST, 'quote');
			if (!$reply && !$quote) return;
			$original_id = $reply ? $reply : $quote;
			$row = ListQuery::quick_fetch_row('Post', $original_id);
			$description_html = '';
			$title = "Re: ".$row['title'];
			if ($quote) {
				$description_html = "<b><i>" . translate('LBL_TEXT_USER', 'Posts') ." '". get_assigned_user_name($row['created_by'])."' " . translate('LBL_TEXT_SAID', 'Posts') . ":</i></b>
			<br />
			<table width=\"85%\" cellspacing=\"1\" cellpadding=\"1\" border=\"1\" align=\"center\" summary=\"\" style=\"font-style: italic;\">
			  <tbody>
				<tr>
				  <td>".$row['description_html']."</td>
				</tr>
			  </tbody>
			</table>
			<br /><br />
			";
			}
			$mgr->record->assign('title', $title, true);
			$mgr->record->assign('description_html', $description_html, true);
		}
	}

	public static function get_post_detail_view($id, $title, $created_by_user, $modified_user,
		$date_entered, $date_modified, $description_html, $thread_id) {

		global $current_user;
		static $photos = array();
		require_once 'XTemplate/xtpl.php';
		$xtpl = new XTemplate(dirname(__FILE__) . '/DetailView.html');

		if (!isset($photos[$created_by_user])) {
			$user = new User;
			$uid = $created_by_user;
			$user->retrieve($uid);
			$image_url = "include/images/iah/unknown.png";
			if (!empty($user->photo_filename)) {
				$image_url = CacheManager::get_location('images/directory/', true);
				$image_url .= $user->photo_filename;
			}
			$photos[$created_by_user] = array($image_url, $uid);
		}
		$image_url = $photos[$created_by_user][0];
		

		$xtpl->assign('MOD', return_module_language('', 'Posts'));
		$xtpl->assign('APP', return_application_language(''));
		$xtpl->assign('POST',
			compact(
				'id', 'title', 'created_by_user', 'modified_user', 'date_entered',
				'description_html', 'image_url', 'date_modified', 'thread_id'));

		if($current_user->is_admin || $current_user->id == $photos[$created_by_user][1]) {
			$xtpl->parse('main.owner_or_admin');
		}
		if($date_modified != $date_entered || $modified_user != $created_by_user) {
			$xtpl->parse("main.modified");
		}
		$xtpl->parse('main');
		return $xtpl->text('main');
	}
}

?>
