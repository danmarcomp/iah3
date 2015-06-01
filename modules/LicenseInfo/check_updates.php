<?php
if(!defined('sugarEntry'))define('sugarEntry', true);

function check_for_updates() {
    require_once('modules/LicenseInfo/SystemUpdateManager.php');

    if (! SystemUpdateManager::is_enabled())
        return null;

    $updates = SystemUpdateManager::check_for_updates();
    $new_updates = 0;
    AppConfig::set_local('scheduler.updates_last_checked', gmdate('Y-m-d H:i:s'));
    SystemUpdateManager::reset_updates();

    if (is_array($updates)) {
        SystemUpdateManager::add_updates($updates);
        $new_updates = 1;
    }

    AppConfig::set_local('scheduler.new_updates', $new_updates);
    AppConfig::save_local();

    return $updates;
}