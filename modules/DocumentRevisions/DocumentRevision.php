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

 * Description: TODO:  To be written.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/



require_once('data/SugarBean.php');
require_once('include/upload_file.php');

// User is used to store Forecast information.
class DocumentRevision extends SugarBean {

	var $id;
	var $document_id;
	var $date_entered;
	var $created_by;
	var $filename;
	var $file_mime_type;
	var $revision;
	var $change_log;
	var $document_name;
	var $latest_revision;
	var $file_url;
	var $file_ext;
	var $created_by_name;

	var $img_name;
	var $img_name_bare;
	
	var $table_name = "document_revisions";	
	var $object_name = "DocumentRevision";
	var $module_dir = 'DocumentRevisions';
	var $new_schema = true;
	var $latest_revision_id;
	
	/*var $column_fields = Array("id"
		,"document_id"
		,"date_entered"
		,"created_by"
		,"filename"	
		,"file_mime_type"
		,"revision"
		,"change_log"
		,"file_ext"
		);
*/
	var $encodeFields = Array();

	// This is used to retrieve related fields from form posts.
	var $additional_column_fields = Array('');

	// This is the list of fields that are in the lists.
	var $list_fields = Array("id"
		,"document_id"
		,"date_entered"
		,"created_by"
		,"filename"	
		,"file_mime_type"
		,"revision"
		,"file_url"
		,"change_log"
		,"file_ext"
		,"created_by_name"
		);
		
	var $required_fields = Array("revision");
	
	

	function DocumentRevision() {
		parent::SugarBean();
		$this->disable_row_level_security =true; //no direct access to this module. 
	}

	function retrieve($id, $encode=false){
		$ret = parent::retrieve($id, $encode);	
		
		return $ret;
	}

	function is_authenticated()
	{
		return $this->authenticated;
	}
	
	
	static function new_record(RowUpdate &$upd) {
		$doc_id = array_get_default($_REQUEST, 'document_id');
		$doc = ListQuery::quick_fetch('Document', $doc_id, array('revision_number'));
		if($doc) {
			$rev = $doc->getField('revision_number');
			$upd->set('document_revision', $rev);
			$i = floor($rev);
			if($i > 0)
				$upd->set('revision', sprintf('%0.1f', 1.0 + $i));
		}
	}
	
	static function after_save(RowUpdate &$upd) {
		if($upd->new_record) {
			$doc = ListQuery::quick_fetch('Document', $upd->getField('document_id'));
			if($doc) {
				$doc_upd = RowUpdate::for_result($doc);
				$doc_upd->set('document_revision_id', $upd->getPrimaryKeyValue());
				$doc_upd->save();
			}
		}
	}

}
?>
