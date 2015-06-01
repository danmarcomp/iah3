<?php


$wizard = preg_replace('~[^a-z]~i', '', array_get_default($_REQUEST, 'wizard', 'Studio'));

if (!file_exists("modules/NewStudio/wizards/{$wizard}Wizard.php")) $wizard = 'Studio';

$wizard .= 'Wizard';

require_once "modules/NewStudio/wizards/$wizard.php";
$wiz = new $wizard($_REQUEST);
$result = $wiz->process();
if (is_array($result)) {
	if (!isset($result['external'])) {
		$result['module'] = 'NewStudio';
		$result['action'] = 'index';
		indexRedirect($result);
	} else {
		unset($result['external']);
		indexRedirect($result, false);
	}
	exit;
}
$wiz->render();

