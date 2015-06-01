<?php
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
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

require_once('include/upload_file.php');

class DocumentSoap {
	
	function fetchDocument($id, $fields=true, $limit_related=null, $acl_filters=null) {
		$params = array();
		if($acl_filters)
			$params['acl_filters'] = $acl_filters;
		$doc = ListQuery::quick_fetch('Document', $id, $fields, $params);
		if(! $doc || $doc->getField('section') == 'hr')
			return;
		if(is_array($limit_related)) {
			$check = new ListQuery('document_relations', array('document_id', 'relation_id'));
			$check->addSimpleFilter('document_id', $id);
			$check->addSimpleFilter('relation_id', $limit_related);
			$result = $check->runQuery(0, 1);
			if(! $result || ! $result->getResultCount())
				return;
		}
		return $doc;
	}

	function saveFile($document, $limit_related=null) {
		if(! is_array($document) || empty($document['id']) || empty($document['file']) || empty($document['filename']))
			return;
		$filters = isset($limit_related) ? null : 'edit';
		$doc = $this->fetchDocument($document['id'], true, $limit_related, $filters);
		if(! $doc)
			return;
		$rev_num = array_get_default($document, 'revision', '1.0');

		$decodedFile = base64_decode($document['file']);
		$uf = new UploadFile('');
		$uf->set_for_soap($document['filename'], $decodedFile);
		$uf->create_stored_filename();
		$filename = $uf->get_stored_file_name();
		
		$rev = RowUpdate::blank_for_model('DocumentRevision');
		$rev->set(array(
			'filename' => $filename,
			'file_mime_type' => $uf->getMimeSoap($filename),
			'file_ext' => $uf->file_ext,
			'revision' => $rev_num,
			'document_id' => $document['id'],
		));
		$rev->related_files['filename'] = $uf;
		if($rev->validate() && $rev->save()) {
			$rev_id = $rev->getPrimaryKeyValue();
			return $rev_id;
		}
	}
	
	function expandResult($id, $doc) {
		$upload = new UploadFile($doc->getFieldDefinition('filename'));
		$path = $doc->getField('filename');
		$file_mime_type = $doc->getField('file_mime_type');
		$name = $doc->getField('name');
		$revision = $doc->getField('revision');
		$file_path = $path ? $upload->get_file_path($path) : '';
		$file_name = $upload->get_display_filename($path);
		if($file_path && is_readable($file_path))
			$file = base64_encode(file_get_contents($file_path));
		else
			$file = '';

		return compact('id', 'name', 'file_name', 'file', 'file_mime_type', 'revision');
	}

	function retrieveRevisionFile($id) {
		$fields = array(
			'name' => 'document_ref.name', 'section' => 'document_ref.section',
			'filename', 'file_mime_type', 'revision');
		$doc = ListQuery::quick_fetch('DocumentRevision', $id, $fields, array('acl_filters' => 'view'));
		if(! $doc) return;
		return $this->expandResult($id, $doc);
	}

	function retrieveDocumentFile($id, $limit_related=null) {
		$doc = $this->fetchDocument(
			$id,
			array(
				'name', 'section',
				'filename' => 'document_revision.filename',
				'file_mime_type' => 'document_revision.file_mime_type',
				'revision' => 'document_revision.revision'),
			$limit_related);
		if(! $doc) return;
		return $this->expandResult($id, $doc);
	}
	// longreach - end added

}

?>
