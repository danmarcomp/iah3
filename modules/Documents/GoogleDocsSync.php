<?php

require_once 'include/upload_file.php';
require_once 'include/database/ListQuery.php';
require_once 'include/database/RowUpdate.php';
require_once 'include/GoogleSync.php';

class GoogleDocsSync extends GoogleSync
{

	protected function createClient2()
	{
		$username = $this->getUsername();;
		$password = $this->getPassword();

		if (empty($username) || empty($password)) return false;
		try {
			$this->ssClient =
				$this->login->getHttpClient($username, $password, 'wise');
			$this->ss = new Zend_Gdata_Docs($this->ssClient);
		} catch (Exception $e) {
			$this->logException($e);
			return false;
		}
		return true;
	}

	protected function createIAHRevision($doc, $iahDoc)
	{
		global $db;
		$url = $this->getDownloadURL($doc);
		$cl = $this->getDownloadClient($doc);
		if (!$cl || !$url) {
			return false;
		}
		$response = $cl->performHttpRequest('GET', $url);

		$disposition = $response->getHeader('Content-Disposition');
		list($dummy, $filename) = explode('="', $disposition);
		$filename = substr($filename, 0, -1);
		
		$contentType = $response->getHeader('Content-Type');
		list($contentType) = explode(';', $contentType);

		$fl = new UploadFile('');
		$fl->use_soap = true;
		$fl->stored_file_name = $filename;
		$fl->create_stored_filename();
		$upload_dir = AppConfig::upload_dir();
		$dst = $fl->stored_file_name;
		$f = fopen($upload_dir . $dst, 'w');
		fwrite($f, $response->getBody());
		fclose($f);

		$revNo = 1;
		if ($iahDoc->new_record) {
			$iahDoc->set('id', create_guid());
		} else {
			$query = "SELECT MAX(revision) as rev FROM document_revisions WHERE document_id = '" . $db->quote($iahDoc->getField('id')) . "'";
			$res = $db->query($query);
			$row = $db->fetchByAssoc($res);
			$revNo = ((int)$row['rev']) + 1;
		}
		$revision = RowUpdate::blank_for_model('DocumentRevision');
		$revision->set('revision', $revNo);
		$revision->set('filename', $fl->get_stored_file_name());
		$revision->set('file_mime_type', $contentType);
		$revision->set('file_ext', $fl->file_ext);
		$revision->set('document_id', $iahDoc->getField('id'));
		$revision->set('date_modified', gmdate('Y-m-d H:i:s', strtotime($doc->updated->text)));
		$revision->set('created_by', $this->user->id);
		$revision->set('date_entered', gmdate('Y-m-d H:i:s', strtotime($doc->updated->text)));
		$revision->save();

		$iahDoc->set('document_revision_id', $revision->getPrimaryKeyValue());
	}



	protected function getResourceId($doc)
	{
		$resourceId = ':';
		foreach ($doc->extensionElements as $el) {
			if ($el->rootElement == 'resourceId') {
				$resourceId = $el->text;
				break;
			}
		}
		return $resourceId;
	}
	protected function getDownloadURL($doc)
	{
		$resourceId = $this->getResourceId($doc);
		list($type, $id) = explode(':', $resourceId);
		if ($type == 'pdf') {
			$url = $doc->content->src;
		} elseif ($type == 'spreadsheet') {
			$url = $this->getSpreadsheetDownloadURL($id);
		} elseif ($type == 'presentation') {
			$url = $this->getPresentationDownloadURL($id);
		} else {
			$url = $this->getDocDownloadURL($id);
		}
		return $url;
	}

	protected function getDocDownloadURL($id)
	{
		$format = $this->user->getPreference('google_docs_doc');
		if ($format != 'odt' && $format != 'rtf') {
			$format = 'doc';
		}
		$url = 'http://docs.google.com/feeds/download/documents/Export?docID=' . $id . '&exportFormat=' . $format;
		return $url;
	}

	protected function getSpreadsheetDownloadURL($id)
	{
		$format = $this->user->getPreference('google_docs_spreadsheet');
		if ($format == 'ods') {
			$format = 13;
		} elseif ($format == 'csv') {
			$format = 5;
		} else {
			$format = 4;
		}
		$url = 'http://spreadsheets.google.com/feeds/download/spreadsheets/Export?key=' . $id . '&fmcmd=' . $format;
		return $url;
	}

	protected function getPresentationDownloadURL($id)
	{
		$format = $this->user->getPreference('google_docs_presentation');
		if ($format != 'pdf') {
			$format = 'ppt';
		}
		$url = 'http://docs.google.com/feeds/download/presentations/Export?docID=' . $id . '&exportFormat=' . $format;
		return $url;
	}

	protected function getDownloadClient($doc)
	{
		$cl = $this->feedSource;
		$resourceId = $this->getResourceId($doc);
		list($type, $id) = explode(':', $resourceId);
		if ($type == 'spreadsheet') {
			if (empty($this->ss)) {
				if (!$this->createClient2()) {
					return false;
				}
			}
			$cl = $this->ss;
		}
		return $cl;
	}

	protected function getConfigKey()
	{
		return 'google_docs';
	}

	protected function getService()
	{
		return 'writely';
	}

	protected function getFeedSource()
	{
		return new Zend_Gdata_Docs($this->client);
	}

	protected function getFeed($query)
	{
		return $this->feedSource->getDocumentListFeed($query);
	}

	protected function getQuery()
	{
		$query = new Zend_Gdata_Docs_Query;
		$query->setProjection('full');
		$query->setVisibility('private');
		return $query;
	}

	protected function createIAHItem($sql_row = null)
	{
		$id = null;
		if (is_array($sql_row))
			$id = array_get_default($sql_row, 'id');
		if (!$id)
			return RowUpdate::blank_for_model('Document');
		$row = ListQuery::quick_fetch('Document', $id, true, array('filter_deleted' => false));
		if ($row)
			return RowUpdate::for_result($row);
		
		return RowUpdate::blank_for_model('Document');
	}

	protected function setIAHItemFields($iahItem, $item)
	{
		$iahItem->set('document_name', $item->title->text);
		$iahItem->set('status_id', 'Archived');
		$this->createIAHRevision($item, $iahItem);
	}

	protected function getSQLQuery($lastSyncTime)
	{
		$query = "SELECT d.id, s.* "
			." FROM documents d "
			." LEFT JOIN google_sync s ON s.related_id=d.id AND s.user_id='".$this->user->id . "' AND s.related_type='Documents'"
			." WHERE (IFNULL(s.google_id,'')!='' OR NOT d.deleted) AND IFNULL(d.section, '') != 'hr' ";
		$query .= " AND (d.date_modified > s.google_last_sync OR s.google_last_sync IS NULL)";
		$query .= ' ORDER BY d.date_modified ';
		$query .= ' LIMIT ' . (int)array_get_default($this->params, 'max_items', 50);
		return $query;
	}

	protected function realCreateGoogleItem($iahItem)
	{
		$revision = ListQuery::quick_fetch('DocumentRevision', $iahItem->getField('document_revision_id'), true);
		if ($revision) {
			$filename = strlen($revision->getField('full_filename')) ? $revision->getField('full_filename') : $revision->getField('filename');
			$upload_dir = AppConfig::upload_dir();
			$filenameParts = explode('.', $filename);
			$fileExtension = strtolower(end($filenameParts));
			$mimeType = @Zend_Gdata_Docs::lookupMimeType($fileExtension);
			if (empty($mimeType)) {
				return true;
			}
			$docName = $iahItem->getField('document_name');
			try {
				$doc = $this->feedSource->uploadFile($upload_dir . $filename, $docName, $mimeType);
			} catch (Zend_Gdata_App_IOException $e) {
				$iahItem->_google_fields['google_sync_error'] = 1;
				$iahItem->_google_fields['google_last_sync'] = '1970-01-01 00:00:00';
				return true;
			}

		}
		return $doc;
	}

	protected function realUpdateGoogleItem($item, $iahItem)
	{
		$item->title = $this->feedSource->newTitle($iahItem->getField('document_name'));
		$revision = ListQuery::quick_fetch('DocumentRevision', $iahItem->getField('document_revision_id'));
		if ($revision) {
			$filename = strlen($revision->getField('full_filename')) ? $revision->getField('full_filename') : $revision->getField('filename');
			$upload_dir = AppConfig::upload_dir();
			$content = $this->feedSource->newMediaFileSource($upload_dir .$filename);
			
			$filenameParts = explode('.', $filename);
			$fileExtension = strtolower(end($filenameParts));
			$mimeType = @Zend_Gdata_Docs::lookupMimeType($fileExtension);
			if (!$mimeType) {
				return;
				}
        		$content->setContentType($mimeType);
			$item->setMediaSource($content);
		}
		return $item;
	}

	protected function getListEntry($googleId)
	{
		return $this->feedSource->getDocumentListEntry($googleId);
	}

	protected function findSpreadsheet($tag)
	{
		$query = new Zend_Gdata_Docs_Query;
		$query->setProjection('full');
		$query->setVisibility('private');
		$query->setCategory('spreadsheet');
		$query->setTitle($tag);
		$query->setMaxResults(1);
		$feed =  $this->feedSource->getDocumentListFeed($query);
		if ($feed->totalResults->text) {
			return $feed[0];
		}
	}
	
	protected function saveItem($item, $iahItem)
	{
		try {
			$item->save();
		} catch (Zend_Gdata_App_IOException $e) {
			$iahItem->_google_fields['google_sync_error'] = 1;
			$iahItem->_google_fields['google_last_sync'] = '1970-01-01 00:00:00';
			return true;
		}
	}
}

