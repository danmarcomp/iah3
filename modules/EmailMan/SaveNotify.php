<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
require_once('include/utils/html_utils.php');

global $current_user, $current_language;
if (!is_admin($current_user)) sugar_die("Unauthorized access to administration.");

$id = array_get_default($_REQUEST, 'id');
$module = array_get_default($_REQUEST, 'mod');
$content = array_get_default($_REQUEST, 'content');
$subject = array_get_default($_REQUEST, 'subject');
$is_html = array_get_default($_REQUEST, 'is_html');

if ($id != null && $module != null && $content != null) {
	$edited_subject = '';

	if (! $is_html) {
		$edited_content = html2plaintext($content);
		if ($subject != null) $edited_subject = html2plaintext(html2plaintext($subject));
	} else {
		$edited_content = htmlspecialchars_decode($content);
		if ($subject != null) $edited_subject = htmlspecialchars_decode($subject);
	}

    $data = array('module' => $module, 'subject' => $edited_subject, 'body' => $edited_content);
    AppConfig::set_local("notification.{$id}", $data);

    $path = AppConfig::custom_dir() . 'include/language';
    $filepath = $path . "/lang.{$current_language}.notify.php";

    AppConfig::save_local('notification', false, $filepath);
    AppConfig::invalidate_cache('notification');
}

header("Location: index.php?action={$_POST['return_action']}&module={$_POST['return_module']}");
?>