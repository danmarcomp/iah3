<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

if(! is_admin($current_user))
	return;

AppConfig::cache_reset();
AppConfig::invalidate_cache('ext');
AppConfig::invalidate_cache('model');
AppConfig::invalidate_cache('modinfo');
AppConfig::invalidate_cache('display');
AppConfig::invalidate_cache('acl');
AppConfig::invalidate_cache('views');
AppConfig::invalidate_cache('lang');
AppConfig::invalidate_cache('notification');
$tm = sprintf('%0.0f', constant('EXTERNAL_CACHE_INTERVAL_SECONDS') / 60.0);
$lbl = str_replace('{TIME}', $tm, translate('LBL_CACHE_NOW_RESET', 'Administration'));
echo $lbl;

?>
