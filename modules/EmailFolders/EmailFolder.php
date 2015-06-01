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


 /*********************************************************************************
 * $Id: $
 ********************************************************************************/

require_once('data/SugarBean.php');
require_once('modules/Emails/Email.php');
require_once 'include/database/ListQuery.php';


define ('STD_FOLDER_INBOX',     1);
define ('STD_FOLDER_SENT',      2);
define ('STD_FOLDER_DRAFTS',    3);
define ('STD_FOLDER_TRASH',     4);
define ('STD_FOLDER_CAMPAIGN',  5);
define ('STD_FOLDER_SUPPORT',   6);

class EmailFolder extends SugarBean {
	var $field_name_map;

	// Stored fields
	var $id;
	var $user_id;
	var $description;
	var $name;

	var $table_name = "emails_folders";
	var $module_dir = 'EmailFolders';
	var $object_name = "EmailFolder";

	function Email() {
        parent::SugarBean();
	}

	var $new_schema = true;

	function get_summary_text() {
		return "$this->name";
	}

	function create_list_count_query($where) {
		return '';
	}

	function create_export_query(&$order_by, &$where) {
        $query = 'SELECT emails_folders.*';
        $query .= ' FROM emails_folders ';
        $where_auto = "emails_folders.deleted=0";
		if($where != "") {
            $query .= "where $where AND ".$where_auto;
		} else {
            $query .= "where ".$where_auto;
		}
        if($order_by != "") {
            $query .= " ORDER BY $order_by";
        } else {
            $query .= " ORDER BY emails_folders.name";
        }

                return $query;
    }

	// create or repair a user's standard email folders
	function create_default_folders_for_user($user) {
		if($user) {
			$folders = array(
				STD_FOLDER_INBOX => 'Inbox',
				STD_FOLDER_SENT => 'Sent',
				STD_FOLDER_DRAFTS => 'Drafts',
				STD_FOLDER_TRASH => 'Trash'
			);
			$uid = $user->getField('id');
			$uname = $user->getField('name');
		}
		else {
			$folders = array(
				STD_FOLDER_INBOX => 'Inbox',
				STD_FOLDER_CAMPAIGN => 'Campaign Responses',
				STD_FOLDER_SUPPORT => 'Customer Support',
			);
			$uid = '-1';
			$uname = 'Group';
		}
		$log = array();
		$lq = new ListQuery('EmailFolder', array('id', 'reserved', 'deleted'), array('filter_deleted' => false));
		$lq->addSimpleFilter('user_id', $uid);
		$result = $lq->fetchAll();
		$exist = array();
		$undelete = array();
		if($result && ! $result->failed) {
			foreach($result->getRows() as $idx => $row) {
				if(array_key_exists($row['reserved'], $folders)) {
					$exist[$row['reserved']] = $row['id'];
					if($row['deleted'])
						$undelete[$row['reserved']] = $result->getRowResult($idx);
				}
			}
		}
		foreach ($folders as $type => $name) {
			if(empty($exist[$type])) {
				$folder = RowUpdate::blank_for_model('EmailFolder');
				$folder->set(array(
					'reserved' => $type,
					'name' => EmailFolder::translate_name($type, $name, $uid),
					'user_id' => $uid,
				));
				$folder->save();
				$name = $folder->getField('name');
				$log[] = "Created folder {$name} for {$uname}.";
			}
		}
		foreach($undelete as $type => $row) {
			$upd = RowUpdate::for_result($row);
			$upd->markDeleted(false);
			$name = $upd->getField('name');
			$log[] = "Restored folder {$name} for {$uname}.";
		}
		return $log;
	}

	function create_default_group_folders() {
		return EmailFolder::create_default_folders_for_user($u = null);
	}

	// move any stranded emails with no folder to the inbox
	function repair_unfiled_emails($user=null) {
		global $db;
		$reassigned = 0;
		$uid = $user ? $user->getField('id') : '-1';
		$inbox = EmailFolder::get_std_folder_id($uid, STD_FOLDER_INBOX);
		if(!$inbox)
			return -1;
		$query = "SELECT id FROM emails WHERE assigned_user_id='".$db->quote($uid)."' AND (folder IS NULL OR folder='')";
		$result = $db->query($query, true, "Error retrieving email list");
		while(1) {
			$ids = array();
			$c = 0;
			while(($row = $db->fetchByAssoc($result)) && $c++ < 50)
				$ids[] = $row['id'];
			if(!count($ids))
				break;
			$query = "UPDATE emails SET folder='$inbox' WHERE id IN ('".implode("','", $ids)."')";
			$upd_result = $db->query($query, true, "Error assigning emails to folders");
			$reassigned += $c;
		}
		return $reassigned;
	}

    // We do not want to delete reserved folders
    function mark_deleted($id)
	{
		$query = "SELECT user_id, reserved FROM emails_folders WHERE id='$id'";
		$result =$this->db->query($query,true," Error : retrieve folder details");
		$row = $this->db->fetchByAssoc($result);
        
        $trash_folder = EmailFolder::get_std_folder_id($row['user_id'], STD_FOLDER_TRASH);

		$query = "UPDATE $this->table_name set deleted=1 where id='$id' AND NOT reserved";
		$this->db->query($query, true,"Error marking record deleted: ");

        if (!$row['reserved']) {
        	$email = new Email();        	
			$where = "folder='{$id}'";        	
			$emails_list = $email->get_full_list("", $where, true);

			for($i = 0; $i < count($emails_list); $i++) {
				$the_email = $emails_list[$i];
				$the_email->mark_deleted($the_email->id, true);
			}        	        	
        }
    }

    static function get_std_folder_id($user_id, $type) {
        global $db;
        if(!is_object($db)) {
            $db =& PearDatabase::getInstance();
        }
		$query  = "SELECT id FROM emails_folders WHERE user_id='" . $db->quote($user_id) . "' AND !deleted AND reserved = " . (int)$type;
		$result =$db->query($query,true," Error retrieving standard folder ID: ");

		$row = $db->fetchByAssoc($result);

		if($row != null)
		{
            return $row['id'];
        } else {
            return '';
        }

    }

    function get_folders_list($user_id)
	{
		static $cache = array();
		if (isset($cache[$user_id])) 
			return $cache[$user_id];

		global $db;
		$folders = array();
		$lq = new ListQuery('EmailFolder');
		$lq->addFields(array('id', 'reserved', 'name', 'user_id'));
		$lq->addSimpleFilter('user_id', $user_id);
		$lq->setOrderBy(array(
			array(
				'literal' => 'IF(emails_folders.reserved, emails_folders.reserved, 99999)',
			),
			array(
				'field' => 'name',
			),
		));
		$result = $lq->runQuery();
        $ids = array();
        foreach ($result->getRows() as $row) {
            $row['newmessages'] = '0';
            $row['totalmessages'] = '0';
            $row['name'] = EmailFolder::translate_name($row['reserved'], $row['name'], $row['user_id']);
            $folders[$row['id']] = $row;
            $ids[] = $row['id'];
        }
		
		if(count($ids)) {
			$fields = array(
				array(
					'name' => 'newmessages',
					'type' => 'int',
					'source' => array (
						'type' => 'literal',
						'value' => 'COUNT(IF(isread, NULL, 1))',
					),
				),
				array(
					'name' => 'totalmessages',
					'type' => 'int',
					'source' => array (
						'type' => 'literal',
						'value' => 'COUNT(*)',
					),
				),
			);		
			$lq = new ListQuery('Email');
			$lq->addSimpleFilter('folder', $ids);
			$lq->addFields($fields);
			$lq->addFields(array('folder'));
			$lq->setGroupBy('folder');
			$result = $lq->runQuery();
			foreach ($result->getRows() as $row2) {
				$folders[$row2['folder']]['newmessages'] = $row2['newmessages'];
				$folders[$row2['folder']]['totalmessages'] = $row2['totalmessages'];
			}
		}
		return $cache[$user_id] = $folders;
    }

    static function get_filter_options($section='personal') {
    	/*if(isset($form->override_filters['section']))
    		$section = $form->override_filters['section']['value'];
    	else
			$section = array_get_default($form->filter, 'section', 'personal');*/
		if($section == 'group')
			$list = EmailFolder::get_group_folders_list();
		else
			$list = EmailFolder::get_folders_list(AppConfig::current_user_id());
		$ret = array();
		foreach($list as $f)
			$ret[$f['id']] = $f['name'];
		return $ret;
    }

    static function get_search_options() {
        global $current_user;
        $list = self::get_folders_select_list($current_user->id, true, true);
        return $list[0];
    }

	static function get_folder_options() {
		global $current_user;
		$list = self::get_folders_select_list($current_user->id, false, true);
		return $list[0];
	}

	function get_group_folders_list() {
		return EmailFolder::get_folders_list('-1');
	}
    
    function get_folders_select_list($user_id, $add_blank=true, $add_group_folders=false) {
        global $mod_strings;
    	$ret = $add_blank ? array('' => $mod_strings['LNK_ALL_EMAIL_LIST']) : array();
    	$reserve_map = array();
    	foreach(EmailFolder::get_folders_list($user_id) as $f) {
    		if(! empty($f['reserved']))
    			$reserve_map[$f['reserved']] = $f['id'];
    		$ret[$f['id']] = $f['name'];
    	}
    	$ret_vals = array($ret, $reserve_map);
    	if($add_group_folders) {
    		$reserve_grp = array();
			foreach(EmailFolder::get_group_folders_list() as $f) {
				if(! empty($f['reserved']))
					$reserve_grp[$f['reserved']] = $f['id'];
				$ret[$f['id']] = $f['name'];
			}
			$ret_vals[0] = $ret;
			$ret_vals[] = $reserve_grp;
		}
    	return $ret_vals;
    }
    
    function get_group_folders_select_list($add_blank=true) {
    	$ret = $add_blank ? array(''=>'') : array();
    	$reserve_map = array();
    	foreach(EmailFolder::get_group_folders_list() as $f) {
    		if(! empty($f['reserved']))
    			$reserve_map[$f['reserved']] = $f['id'];
			$ret[$f['id']] = $f['name'];
		}
    	return array($ret, $reserve_map);
    }

    static function get_user_options($section = null) {
        if ($section == 'group') {
            $options = array(
                '-1' => 'group',
            );
        } else {
            $options = array(
                AppConfig::current_user_id() => 'personal',
            );
        }
		return $options;
    }

    function get_name() {
    	return EmailFolder::translate_name($this->reserved, $this->name, $this->user_id);
    }
    
    // static
    function translate_name($folder_reserved_type, $folder_name, $user_id=null) {
    	static $mod_strings;
    	global $current_language;
    	if(! isset($mod_strings))
    		$mod_strings = return_module_language($current_language, 'EmailFolders');
    	$tr = '';
    	switch($folder_reserved_type) {
    		case STD_FOLDER_INBOX:
    			if($user_id == -1)
					$tr = 'LBL_FOLDER_GROUP_INBOX';
				else
					$tr = 'LBL_FOLDER_INBOX';
				break;
    		case STD_FOLDER_SENT:
    			$tr = 'LBL_FOLDER_SENT'; break;
    		case STD_FOLDER_DRAFTS:
    			$tr = 'LBL_FOLDER_DRAFTS'; break;
    		case STD_FOLDER_TRASH:
    			$tr = 'LBL_FOLDER_TRASH'; break;
    		case STD_FOLDER_CAMPAIGN:
    			$tr = 'LBL_FOLDER_CAMPAIGN'; break;
    		case STD_FOLDER_SUPPORT:
    			$tr = 'LBL_FOLDER_SUPPORT'; break;
    	}
    	if($tr && isset($mod_strings[$tr]))
    		$folder_name = $mod_strings[$tr];
    	return $folder_name;
    }

    /**
     * Get number of messages in folder
     *
     * @static
     * @param string $id - folder ID
     * @param bool $total:
     * true - total messages, false - new messages
     * @return int
     */
    static function get_message_counts($id, $total = true) {
        global $db;

        if ($total) {
            $count_sql = "COUNT(emails.id) AS msg_count ";
        } else {
            $count_sql = "COUNT(IF(emails.isread OR emails.deleted,NULL,emails.id)) as msg_count ";
        }

        $query = "SELECT ".
            $count_sql.
            "FROM emails ".
            "WHERE emails.folder = '$id' AND NOT emails.deleted";

        $result = $db->query($query,false);
        $msg_count = 0;

        if( ($row = $db->fetchByAssoc($result)) ) {
            $msg_count = $row['msg_count'];
        }

        return $msg_count;
    }

    static function calc_total_msg($spec) {
        $total = 0;

        if (isset($spec['raw_values']['id'])) {
            $total = self::get_message_counts($spec['raw_values']['id'], true);
        }

        return $total;
    }

    static function calc_new_msg($spec) {
        $new = 0;

        if (isset($spec['raw_values']['id'])) {
            $new = self::get_message_counts($spec['raw_values']['id'], false);
        }

        return $new;
    }

    static function clear(RowUpdate $upd) {
        $lq = new ListQuery('Email');
        $lq->addSimpleFilter('folder', $upd->getPrimaryKeyValue());
        $result = $lq->fetchAll();

        if (! $result->failed) {
            foreach ($result->rows as $id => $details) {
                $email = ListQuery::quick_fetch('Email', $id);
                $email_upd = RowUpdate::for_result($email);
                $email_upd->markDeleted();
            }
        }
    }

    static function init_record(RowUpdate &$upd, $input) {
        $user_id = AppConfig::current_user_id();
        if ($upd->new_record && (isset($input['list_layout_name']) && $input['list_layout_name'] == 'GroupFolders'))
            $user_id = "-1";

        $upd->set(array('user_id' => $user_id));
    }

    static function set_user(DetailManager &$mgr) {
        global $pageInstance;
        $user_id = $mgr->record->getField('user_id');
        $pageInstance->add_js_literal('document.DetailForm.user_id.value="'.$user_id.'"', null, LOAD_PRIORITY_FOOT);
    }
}
?>
