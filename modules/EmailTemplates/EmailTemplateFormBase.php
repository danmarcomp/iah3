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
 * $Header$
 * Description:  Base Form For Notes
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/


class EmailTemplateFormBase {

	function getFormBody($prefix, $mod='',$formname='', $size='30') {
		require_once('include/javascript/javascript.php');
		require_once('modules/EmailTemplates/EmailTemplate.php');
		global $mod_strings;

		$temp_strings = $mod_strings;

		if(!empty($mod)) {
			global $current_language;
			$mod_strings = return_module_language($current_language, $mod);
		}
					global $app_strings;
					global $app_list_strings;
		
				$lbl_required_symbol = $app_strings['LBL_REQUIRED_SYMBOL'];
				$lbl_subject = $mod_strings['LBL_NOTE_SUBJECT'];
				$lbl_description = $mod_strings['LBL_NOTE'];
				$default_parent_type= $app_list_strings['record_type_default_key'];
	
$form = <<<EOF
				<input type="hidden" name="${prefix}record" value="">
				<input type="hidden" name="${prefix}parent_type" value="${default_parent_type}">
				<p>
				<table cellspacing="0" cellpadding="0" border="0">
				<tr>
				    <td class="dataLabel">$lbl_subject <span class="required">$lbl_required_symbol</span></td>
				</tr>
				<tr>
				    <td class="dataField"><input name='${prefix}name' size='${size}' maxlength='255' type="text" value=""></td>
				</tr>
				<tr>
				    <td class="dataLabel">$lbl_description</td>
				</tr>
				<tr>
				    <td class="dataField"><textarea name='${prefix}description' cols='${size}' rows='4' ></textarea></td>
				</tr>
				</table></p>
EOF;

	$javascript = new javascript();
	$javascript->setFormName($formname);
	$javascript->setSugarBean(new EmailTemplate());
	$javascript->addRequiredFields($prefix);
	$form .=$javascript->getScript();
	$mod_strings = $temp_strings;
	return $form;
	}
	
	function getForm($prefix, $mod='') {
		if(!empty($mod)) {
		global $current_language;
		$mod_strings = return_module_language($current_language, $mod);
	}else global $mod_strings;
		global $app_strings;
		global $app_list_strings;
	
		$lbl_save_button_title = $app_strings['LBL_SAVE_BUTTON_TITLE'];
		$lbl_save_button_key = $app_strings['LBL_SAVE_BUTTON_KEY'];
		$lbl_save_button_label = $app_strings['LBL_SAVE_BUTTON_LABEL'];
	
	
		$the_form = get_left_form_header($mod_strings['LBL_NEW_FORM_TITLE']);
$the_form .= <<<EOQ
	
				<form name="${prefix}EmailTemplateSave" onSubmit="return check_form('${prefix}EmailTemplateSave')" method="POST" action="index.php">
					<input type="hidden" name="${prefix}module" value="EmailTemplates">
					<input type="hidden" name="${prefix}action" value="Save">
EOQ;
		$the_form .= $this->getFormBody($prefix, $mod, "${prefix}EmailTemplateSave", "20");
$the_form .= <<<EOQ
				<p><input title="$lbl_save_button_title" accessKey="$lbl_save_button_key" class="button" type="submit" name="button" value="  $lbl_save_button_label  " ></p>
				</form>
	
EOQ;
	
		$the_form .= get_left_form_footer();
		$the_form .= get_validate_record_js();
	
		
		return $the_form;
	}
	
	
	function handleSave($prefix,$redirect=true, $useRequired=false) {
		require_once('modules/EmailTemplates/EmailTemplate.php');
		require_once('modules/Documents/Document.php');
		require_once('modules/DocumentRevisions/DocumentRevision.php');
		require_once('modules/Notes/Note.php');
		
		require_once('include/formbase.php');
		require_once('include/upload_file.php');
		global $upload_maxsize, $upload_dir;
		global $mod_strings;
	
		
		$focus = new EmailTemplate();
		if($useRequired && !checkRequired($prefix, array_keys($focus->required_fields))) {
			return null;
		}
		$focus = populateFromPost($prefix, $focus);
		if(!$focus->ACLAccess('Save')) {
			ACLController::displayNoAccess(true);
			sugar_cleanup(true);
		}
		if(!isset($_REQUEST['published'])) $focus->published = 'off';
		
		$return_id = $focus->save();
	
		///////////////////////////////////////////////////////////////////////////////
		////	ATTACHMENT HANDLING
		
		///////////////////////////////////////////////////////////////////////////
		////	ADDING NEW ATTACHMENTS
		$max_files_upload = 10;
		if(!empty($focus->id)) {
			$note = new Note();
			$where = "notes.parent_id='{$focus->id}'";
			if(!empty($_REQUEST['old_id'])) { // to support duplication of email templates
				$where .= " OR notes.parent_id='".$_REQUEST['old_id']."'";
			}
			$notes_list = $note->get_full_list("", $where, true);
		}
	
		if(!isset($notes_list)) {
			$notes_list = array();
		}

		if(!is_array($focus->attachments)) { // PHP5 does not auto-create arrays(). Need to initialize it here. 
			$focus->attachments = array();
		}
		$focus->attachments = array_merge($focus->attachments, $notes_list);
	
		for($i = 0; $i < $max_files_upload; $i++) {
			$note = new Note();
			$upload_file = new UploadFile('email_attachment'.$i);
	
			if(isset($_FILES['email_attachment'.$i]) && $upload_file->confirm_upload(true)) {
				$note->filename = $upload_file->get_stored_file_name();
				$note->file = $upload_file;
				$note->name = $mod_strings['LBL_EMAIL_ATTACHMENT'].': '.$note->file->original_file_name;



	
				array_push($focus->attachments, $note);
			}
		}
	
		$focus->saved_attachments = array();
		foreach($focus->attachments as $note) {
			if(!empty($note->id)) {
				if(empty($_REQUEST['old_id'])) {  // to support duplication of email templates
					array_push($focus->saved_attachments, $note);
				} else {
					// we're duplicating a template with attachments
					// dupe the file, create a new note, assign the note to the new template
					$newNote = new Note();
					$newNote->retrieve($note->id);
					$newNote->id = create_guid();
					$newNote->parent_id = $focus->id;
					$newNote->new_with_id = true;
					$newNoteId = $newNote->save();
					
					$dupeFile = new UploadFile('duplicate');
					$dupeFile->duplicate_file($note->id, $newNoteId, $note->filename);
				}
				continue;
			}
			$note->parent_id = $focus->id;
			$note->parent_type = 'Emails';
			$note->file_mime_type = $note->file->mime_type;
			$note_id = $note->save();
			array_push($focus->saved_attachments, $note);
			$note->id = $note_id;
			$note->file->final_move($note->id);
		}
	
		////	END NEW ATTACHMENTS
		///////////////////////////////////////////////////////////////////////////

	///////////////////////////////////////////////////////////////////////////
	////	ATTACHMENTS FROM DOCUMENTS
	for($i=0; $i<10; $i++) {
		if(isset($_REQUEST['documentId'.$i]) && !empty($_REQUEST['documentId'.$i])) {
			$doc = new Document();
			$docRev = new DocumentRevision();
			$docNote = new Note();
			$noteFile = new UploadFile('none');
			
			$doc->retrieve($_REQUEST['documentId'.$i]);
			$docRev->retrieve($doc->document_revision_id);


			$docNote->name = $doc->document_name;
			$docNote->filename = $docRev->full_filename;
			$docNote->description = $doc->description;
			$docNote->parent_id = $focus->id;
			$docNote->parent_type = 'Emails';
			
			$source = UploadFile::get_file_path($docNote->filename, $note->id);
			$fl = new UploadFile('');
			$fl->use_soap = true;
			$fl->stored_file_name = basename($docNote->filename);
			$docNote->filename = $fl->create_stored_filename();
			$upload_dir = AppConfig::upload_dir();
			copy($source, $upload_dir . $docNote->filename);
			$docNote->file_mime_type = $docRev->file_mime_type;
			if (empty($docNote->file_mime_type)) {
				$docNote->file_mime_type = 'application/octet-stream';
			}
			$docId = $docNote->save();
			array_push($focus->attachments, $docNote);

		}
		
	}
	
	////	END ATTACHMENTS FROM DOCUMENTS
	///////////////////////////////////////////////////////////////////////////
		
		///////////////////////////////////////////////////////////////////////////
		////	REMOVE ATTACHMENTS
	
		if(isset($_REQUEST['remove_attachment']) && !empty($_REQUEST['remove_attachment'])) {
			foreach($_REQUEST['remove_attachment'] as $noteId) {
				$q = 'UPDATE notes SET deleted = 1 WHERE id = \''.$noteId.'\'';
				$focus->db->query($q);
			}
	
		}
	
		////	END REMOVE ATTACHMENTS
		///////////////////////////////////////////////////////////////////////////
	////	END ATTACHMENT HANDLING
	///////////////////////////////////////////////////////////////////////////////
	
		if($redirect) {
		$GLOBALS['log']->debug("Saved record with id of ".$return_id);
			handleRedirect($return_id, "EmailTemplates");
		}else{
			return $focus;
		}
	}
	







}
?>
