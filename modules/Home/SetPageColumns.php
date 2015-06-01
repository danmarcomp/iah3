<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

global $current_user, $pageInstance;

$locked = AppConfig::setting('layout.lock_homepage');

$ret = 0;
if(! $locked) {
	$dboard = $pageInstance->get_dashboard();
	if($dboard && $dboard->user_can_edit()) {
		if(! empty($_REQUEST['columns'])) {
			if($dboard->set_column_count($_REQUEST['columns']))
				$ret = 1;
			$dboard->save();
		}
		else if(isset($_REQUEST['widths'])) {
			$widths = explode(',', $_REQUEST['widths']);
			if($dboard->set_column_widths($widths))
				$ret = 1;
			$dboard->save();
		}
	}
}
echo $ret;

?>
