<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/*********************************************************************************
 * The contents of this file are subject to the SugarCRM Public License Version
 * 1.1.3 ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied.  See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * All copies of the Covered Code must include on each user interface screen:
 *    (i) the "Powered by SugarCRM" logo and
 *    (ii) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.  See full license for
 * requirements.
 *
 * The Original Code is: SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) 2004-2006 SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 ********************************************************************************/
/*********************************************************************************

 * Description:  TODO: To be written.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/




require_once('data/SugarBean.php');
require_once('include/upload_file.php');

// Note is used to store customer information.
class Note extends SugarBean {
	var $field_name_map;
	// Stored fields
	var $id;
	var $date_entered;
	var $date_modified;
	var $assigned_user_id;
	var $modified_user_id;
	var $created_by;
	var $assigned_user_name;
	var $created_by_name;
	var $modified_by_name;
	var $description;
	var $name;
	var $filename;
	// handle to an upload_file object
	// used in emails
	var $file;
	var $parent_type;
	var $parent_id;
	var $contact_id;
	var $portal_flag;
	
	
	// longreach - added
	var $email_attachment = 0;
	var $display_filename;


	var $parent_name;
	var $contact_name;
	var $contact_phone;
	var $contact_email;
	var $file_mime_type;
	var $module_dir = "Notes";
	var $default_note_name_dom = array('Meeting notes', 'Reminder');
	var $table_name = "notes";
	var $new_schema = true;
	var $object_name = "Note";

	
	// longreach - start added
	function get_display_filename() {
		$this->display_filename = UploadFile::get_display_filename($this->filename);
		return $this->display_filename;
	}
	// longreach - end added
	
	
    static function init_record(RowUpdate &$upd, $input) {
    	$pid = array_get_default($input, 'parent_id');
    	if(array_get_default($input, 'parent_type') == 'Contacts') {
    		$upd->set('contact_id', $pid);
    	} else if( ($cid = array_get_default($input, 'contact_id')) ) {
    		$upd->set('contact_id', $cid);
    	}
    	
    	if( ($cid = $upd->getField('contact_id')) && (! $pid || $cid == $pid) ) {
			unset($_REQUEST['parent_type']);
			unset($_REQUEST['parent_id']);
        	if( ($ctc = ListQuery::quick_fetch('Contact', $cid, array('primary_account_id'))) ) {
				$upd->set('parent_type', 'Accounts');
				$upd->set('parent_id', $ctc->getField('primary_account_id'));
			}
    	}
    }

}
?>