<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once("modules/Calendar/GoogleCalendarSync.php");
require_once("modules/Users/User.php");
require_once("modules/Calls/Call.php");
require_once("modules/Meetings/Meeting.php");
require_once("modules/Tasks/Task.php");


$user = new User;

$query = "SELECT id FROM users WHERE status='Active'";
$result = $user->db->query($query, true, "Failed to list active users.");

while ($row = $user->db->fetchByAssoc($result)) {
	$user->retrieve($row['id']);

	$direction = $user->getPreference("google_calendar_direction");
	$gcall = $user->getPreference("google_calendar_call");
	$gmeeting = $user->getPreference("google_calendar_meeting");
	$gtask = $user->getPreference("google_calendar_task");

	$options = array();

	if ($gcall && $gcall != "off") {
		$options['sync']['Calls'] = true;
	}
		
	if ($gmeeting && $gmeeting != "off") {
		$options['sync']['Meetings'] = true;
	}
			
	if ($gtask && $gtask != "off") {
		$options['sync']['Tasks'] = true;
	}

	if (!empty($direction) && !empty($options['sync'])) {	
		$gsync = new GoogleCalendarSync($user, $options);
		$gsync->sync();
	}

}


