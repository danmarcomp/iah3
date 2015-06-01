<?php

if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$json_supported_actions['portal_get_note_attachment'] = array('login_required' => 'portal');

function json_portal_get_note_attachment()
{
	$id = array_get_default($_REQUEST, 'record');
	if (!$id) {
    	return json_bad_request(array('error' => 'Note ID required'));
	}
	require_once('modules/Notes/Note.php');
	$note = new Note();
	$note->retrieve($id);
	if (!$note->id || $note->deleted) {
    	return json_bad_request(array('error' => 'Invalid Note ID'));
	}
	require_once('modules/Notes/NoteSoap.php');
	$ns = new NoteSoap();
	if(!isset($note->filename)){
		$note->filename = '';
	}
	$file= $ns->retrieveFile($id,$note->filename);
	if($file == -1){
		$error->set_error('no_file');
		$file = '';
	}
	$ret = array(
		'id'=>$id,
		'filename'=>$note->filename,
		'file'=>$file,
		'file_mime_type' => $note->file_mime_type
	);

	json_return_value($ret);
}

$json_supported_actions['portal_set_note_attachment'] = array('login_required' => 'portal');

function json_portal_set_note_attachment()
{
	$note = $_REQUEST['note'];
	require_once('modules/Notes/NoteSoap.php');
	$ns = new NoteSoap();
	$id = $ns->saveFile($note, true);
	json_return_value($id);
}


