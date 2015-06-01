<?php
if(!defined('sugarEntry') || !sugarEntry)
	die('Not A Valid Entry Point');
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

require_once ('data/SugarBean.php');
require_once ('include/upload_file.php');


// User is used to store Forecast information.
class Document extends SugarBean {

	var $id;
	var $document_name;
	var $description;
	var $category_id;
	var $subcategory_id;
	var $status_id;
	var $created_by;
	var $date_entered;
	var $date_modified;
	var $modified_user_id;


	// longreach - start added - new stored fields
	var $department;
	var $keywords;
	var $section;
	// longreach - added - relations
	var $account_id;
	var $contact_id;
	var $opportunity_id;
	var $project_id;
	var $lead_id;
	var $bug_id;
	var $acase_id;
    var $employee_id;
    var $parent_id;
    var $parent_name;

	var $relationship_fields = array(
		'account_id' => 'accounts',
		'contact_id' => 'contacts',
		'opportunity_id' => 'opportunities',
		'project_id' => 'projects',
		'lead_id' => 'leads',
		'bug_id' => 'bugs',
		'acase_id' => 'cases',
		'employee_id' => 'employees',
	);
	var $relation_table_name = 'document_relations';

	// longreach - end added
	

	var $active_date;
	var $exp_date;
	var $document_revision_id;
	var $filename;

	var $img_name;
	var $img_name_bare;
	var $related_doc_id;
	var $related_doc_rev_id;
	var $is_template;
	var $template_type;

	//additional fields.
	var $revision;
	var $last_rev_create_date;
	var $last_rev_created_by;
	var $last_rev_created_name;
	var $latest_revision;
	var $file_url;
	var $file_url_noimage;

	var $table_name = "documents";
	var $object_name = "Document";
	var $user_preferences;

	var $encodeFields = Array ();

	// This is used to retrieve related fields from form posts.
	var $additional_column_fields = Array('revision',
		
		// longreach - added
		'section',
		'latest_revision',
	);

	var $default_order_by = "document_name";

	var $new_schema = true;
	var $module_dir = 'Documents';

	function Document() {
		parent :: SugarBean();
		$this->disable_row_level_security = false;
	}

	function retrieve($id, $encode = false) {
		$ret = parent :: retrieve($id, $encode);
		return $ret;
	}

	function is_authenticated() {
		return $this->authenticated;
	}

	function mark_relationships_deleted($id) {
		//do nothing, this call is here to avoid default delete processing since
		//delete.php handles deletion of document revisions.
	}

	
	//static function.
	function get_document_name($doc_id){
		if (empty($doc_id)) return null;
		
		$db = & PearDatabase::getInstance();				
		$query="select document_name from documents where id='$doc_id'";
		$result=$db->query($query);
		if (!empty($result)) {
			$row=$db->fetchByAssoc($result);
			if (!empty($row)) {
				return $row['document_name'];
			}
		}
		return null;
	}

	// longreach - start added
	function keyword_where_clause($keywords) {
		$keywords = array_filter(array_map('trim', explode(',', $keywords)));
		$opts = Array();
		foreach($keywords as $kw)
			$opts[] = "{$this->table_name}.keywords like '%".PearDatabase::quote($kw)."%'";
		if($opts)
			return "(".join(' OR ', $opts).")";
		return '';
	}

	function get_keyword_where($param)
	{
		$where = $this->keyword_where_clause($param['value']);
		return $where ? $where : '1';
	}
	
	function googleIdToUrl($googleId = null)
	{
		global $current_user;
		$url = '';
		if (empty($googleId)) {
			$query = sprintf(
				"SELECT google_id FROM google_sync WHERE related_type='Documents' AND related_id='%s' AND user_id = '{$current_user->id}'",
				$this->db->quote($this->id)
			);
			$res = $this->db->query($query, true);
			if ($row = $this->db->fetchByAssoc($res)) {
				$googleId = $row['google_id'];
			}
		}
		$googleDomain = $current_user->getPreference('google_domain');
		if (empty($googleDomain)) {
			$domainPart = '';
		} else {
			$domainPart = 'a/' . $googleDomain . '/';
		}
		if (!empty($googleId)) {
			$parts = parse_url($googleId);
			if (array_get_default($parts, 'host') == 'docs.google.com') {
				$path = array_get_default($parts, 'path');
				$m = array();
				if(preg_match('~/(spreadsheet|document|presentation)%3A(.*)$~', $path, $m)) {
					if($m[1] == 'spreadsheet') {
						$url = 'http://spreadsheets.google.com/' . $domainPart . 'ccc?key=' . $m[2];
					}
					if($m[1] == 'presentation') {
						$url = 'http://docs.google.com/'. $domainPart . 'Presentation?id=' . $m[2];
					}
					if($m[1] == 'document') {
						$url = 'http://docs.google.com/' . $domainPart . 'Doc?id=' . $m[2];
					}
				}
			}
		}
		return $url;
	}

    static function init_record(RowUpdate &$upd, $input) {
        $update = array();

        if (! empty($input['section']))
            $update['section'] = $input['section'];

        if ((isset($input['load_signed_id']) and ! empty($inpput['load_signed_id']))) {
            if (isset($input['record']))
                $update['related_doc_id'] = $input['record'];

            if (isset($input['selected_revision_id']))
                $update['related_doc_rev_id'] = $input['selected_revision_id'];
        }

        if (! empty($input['parent_id']) && ! empty($input['parent_type']) && $input['parent_type'] == 'Documents')
            $update['related_doc_id'] = $input['parent_id'];

        if (isset($input['parent_id']))
            $update['parent_id'] = $input['parent_id'];

        if (isset($input['parent_name']))
            $update['parent_name'] = $input['parent_name'];

        $upd->set($update);
    }

    static function set_revision(RowUpdate &$upd) {

        if (! $upd->getField('document_revision_id') && (isset($upd->related_files['revision_filename'])
            && $upd->related_files['revision_filename'] instanceof UploadFile) ) {

            global $mod_strings;
            $file = $upd->related_files['revision_filename'];
            $rev_update = RowUpdate::blank_for_model('DocumentRevision');

            $updated = array(
                'filename' => $file->stored_file_name,
                'file_mime_type' => $file->mime_type,
                'file_ext' => $file->file_ext,
                'change_log' => $mod_strings['DEF_CREATE_LOG'],
                'revision' => array_get_default($_REQUEST, 'revision_number', 1),
                'document_id' => $upd->getPrimaryKeyValue()
            );

            $rev_update->set($updated);
            $rev_update->save();
        }

    }

    static function init_form(DetailManager $mgr) {
        $list_layout = array_get_default($_REQUEST, 'list_layout_name');
        $return_module = array_get_default($_REQUEST, 'return_module');
        $section = array_get_default($_REQUEST, 'section');
        if ( empty($section)) {
            if ($list_layout == 'HR' || $return_module == 'HR') {
                $section = 'hr';
            } else if ($mgr->record->getField('section')) {
                $section = $mgr->record->getField('section');
            }
        }
        if (! empty($section)) {
            $hidden = array('section' => $section);
            $mgr->form_gen->form_obj->addHiddenFields($hidden);
        }

        global $pageInstance;
        $document_id = $mgr->record->getField('related_doc_id');

        if ($document_id) {
            $pageInstance->add_js_literal("init_form('".$mgr->form_gen->form_obj->form_name."', '".$document_id."');", null, LOAD_PRIORITY_FOOT);
        }
    }
}
?>