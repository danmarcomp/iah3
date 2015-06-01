<?php

if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$json_supported_actions['portal_get_document'] = array('login_required' => 'portal');

function json_portal_get_document()
{
	if($_SESSION['type'] == 'lead')
		return json_bad_request(array('error' => 'no_access'));
	$id = array_get_default($_REQUEST, 'id');
	if(! $id)
		return json_bad_request(array('error' => 'no_record'));
	
	$checkIds = $_SESSION['viewable']['Accounts'];
	$checkIds[] = $_SESSION['contact_id'];
	
	require_once('modules/Documents/DocumentSoap.php');
	$dr = new DocumentSoap();
	$attach = $dr->retrieveDocumentFile($id, $checkIds);
	if(! $attach)
		return json_bad_request(array('error' => 'no_access'));

	$ret = array('note_attachment' => $attach);
	json_return_value($ret);
}


$json_supported_actions['portal_set_document_revision'] = array('login_required' => 'portal');

function json_portal_set_document_revision()
{
	$checkIds = $_SESSION['viewable']['Accounts'];
	$checkIds[] = $_SESSION['contact_id'];

	$revision = array_get_default($_REQUEST, 'note');
	if($_SESSION['type'] == 'lead' || ! is_array($revision))
		return json_bad_request(array('error' => 'no_access'));

	require_once('modules/Documents/DocumentSoap.php');
	$dr = new DocumentSoap();
	$ret = $dr->saveFile($revision, $checkIds);
	if(! $ret)
		return json_bad_request(array('error' => 'save_error'));
	json_return_value($ret);
}


