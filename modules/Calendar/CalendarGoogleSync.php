<?php
require_once('Zend/Loader.php');
require_once('modules/Calls/Call.php');
require_once('modules/Meetings/Meeting.php');
require_once('modules/Tasks/Task.php');


class GoogleCalendar
{
	var $id; // The id of the event
	var $bean; // Any other bean that is currently being worked with.  Can be a bean list.
	var $zend; // Zend Loader entry point
	var $user; // User SugarBean
	var $service; // Authentication service type.  "cl" seems to be default, should study the other kinds.
	var $conn; // Connection object
	var $connect_status; // Status text for User Settings
	var $connected; // Currently connected to google
	var $client; // Client object
	var $open_receive; // Allow google to update Info@Hand
	var $mass_update; // Mass update all entries instead of single entries.  Must send a bean list to $bean.
	var $sync; // User sync type
	var $last_sync_date_to_google; // Date that the last sync to google occured
	var $last_sync_date_to_iah; // Date that the last sync to iah occured

	var $test; // Just a variable I use for spitting out data on the screen
	


	function GoogleCalendar($user, $service = "cl")
	{
		$this->zend = new Zend_Loader;
		$this->user = $user;
		$this->service = $service;
		$this->open_receive = false;
		$this->mass_update = false;
		$this->sync = $this->user->getPreference('google_calendar_sync');
		$this->last_sync_date_to_google = $this->user->getPreference('google_calendar_last_sync_date_to_google');
		$this->last_sync_date_to_iah = $this->user->getPreference('google_calendar_last_sync_date_to_iah');
		

		$this->zend->loadClass('Zend_Gdata');
		$this->zend->loadClass('Zend_Gdata_AuthSub');
		$this->zend->loadClass('Zend_Gdata_ClientLogin');
		$this->zend->loadClass('Zend_Gdata_Calendar');
		$this->zend->loadClass('Zend_Gdata_Calendar_Extension_Timezone');

		$this->conn = new Zend_Gdata_ClientLogin;


		$this->google_connect();
	}
	
	
	
	function update_last_sync_date_to_iah()
	{
		$this->user->setPreference('google_calendar_last_sync_date_to_iah',gmdate("Y-d-m H:i:s", time()), 0, 'global', $this->user);
		$this->last_sync_date_to_iah = $this->user->getPreference('google_calendar_last_sync_date_to_iah');
	}
	


	function update_last_sync_date_to_google()
	{
		$this->user->setPreference('google_calendar_last_sync_date_to_google',gmdate("Y-m-d H:i:s", time()), 0, 'global', $this->user);
		$this->last_sync_date_to_google = $this->user->getPreference('google_calendar_last_sync_date_to_google');
	}
	


	function google_connect()
	{
		global $mod_strings;
	
		$guser = $this->user->getPreference('google_calendar_user');
		$gpass = $this->user->getPreference('google_calendar_pass');
		
		if (! empty($guser) && !empty($gpass))
		{
			$this->connect_status = "<font color='#008800'><b>".$mod_strings['LBL_GOOGLE_CALENDAR_CONNECT_STATUS']['SUCCESS']."</b></font>";
			$this->connected = true;
			try
			{
				$this->client = $this->conn->getHttpClient($guser, $gpass, $this->service);
			}
			catch (Zend_Gdata_App_Exception $e)
			{
				$GLOBALS['log']->debug('Failed to log in to Google: '.$e->getMessage());
				$this->connect_status = "<font color='#880000'><b>".$mod_strings['LBL_GOOGLE_CALENDAR_CONNECT_STATUS']['FAILED']."</b></font>";
				$this->connected = false;
				return null;
			}
		}
		else
		{
			$this->connect_status = "<font color='#888800'><b>".$mod_strings['LBL_GOOGLE_CALENDAR_CONNECT_STATUS']['INVALID']."</b></font>";
			$this->connected = false;
		}
		
		return true;
	}
	
	
	
	function google_sync(&$focus, $verbose = false)
	{
		$this->bean = $focus;
		
		if ($this->connected)
		{
			switch($this->sync)
			{
				case "to_iah":
					$this->to_iah();
					break;
				
				case "to_google":
					if ($this->mass_update)
					{
						if ($this->bean)
						{
							foreach ($this->bean as $b)
							{
								$this->to_google($b);
							}
						}
						else
						{
							// There are no records to send to google
						}
					}
					else
						$this->to_google($this->bean);
					break;
				
				case "two_way":
					$this->two_way();
					break;
				
				default:
					// Sync is disabled, do nothing
					break;
			}
		
		}
	}



	function format_itime($focus, $event)
	{	
		$allday = 0;
		$reminder = 0;
		
		foreach ($event->when as $when)
		{
			$allday = (!strpos($when->endTime,"T")) ? 1 : 0;
			$start_epoch = strtotime(str_replace("Z", "", $when->startTime));
			$end_epoch = strtotime(str_replace("Z", "", $when->endTime));

			if (is_array($when->reminders) && isset($focus->reminder_time))
			{
				foreach($when->reminders as $rem)
				{
					$reminder = $rem->minutes * 60;
				}
			}
		}

		$start_date = date("Y-m-d", $start_epoch);
		$start_time = date("H:i:s", $start_epoch);
		$date_end = date("Y-m-d", $end_epoch);

		switch ($focus->object_name)
		{
			case "Task":
				$end_primary = date("Y-m-d", $end_epoch);
				$end_secondary = date("H:i:s", $end_epoch);	
				break;

			default:
				$duration = $end_epoch - $start_epoch;
				$end_primary = floor($duration / 3600);
				$end_secondary = floor(($duration % 3600) / 60);
				break;
		}
		
		

		return	array(
							"start_primary" => $start_date, "start_secondary" => $start_time,
							"end_primary" => $end_primary, "end_secondary" => $end_secondary,
							"date_end" => $date_end,
							"is_daylong" => $allday, "reminder_time" => $reminder,
						);
	}



	function format_gtime($focus)
	{	
		$start_epoch = strtotime($focus->date_start." ".$focus->time_start);
		$start_date = date("Y-m-d", $start_epoch);
		$start_time = date("H:i:s", $start_epoch);
		
		switch ($focus->object_name)
		{
			case "Task":
				$end_epoch = strtotime($focus->date_due." ".$focus->time_due);
				$end_date = date("Y-m-d", $end_epoch);
				$end_time = date("H:i:s", $end_epoch);		
				break;

			default:
				$end_epoch =  $start_epoch + ($focus->duration_hours * 3600) + ($focus->duration_minutes * 60);
				$end_date = date("Y-m-d", $end_epoch);
				$end_time = date("H:i:s", $end_epoch);
				break;
		}
		
		if (isset($focus->is_daylong) && ($focus->is_daylong == 1))
		{
			$gstart = "{$start_date}";
			$gend = "";
		}
		else
		{
			$gstart = "{$start_date}T{$start_time}.000+00:00";
			$gend = "{$end_date}T{$end_time}.000+00:00";
		}
		
		return array("start" => $gstart, "end" => $gend);
	}
	
	
	///////////////////////////////////////////////////////////////////////////////
	// Begin Private Internal Sync Processing.
	// These functions assume many things have been initialized before executing, 
	// there for they are not allowed to be accessed from outside the class.

	private function query_iah_id($class, $gid)
	{
		$focus = new $class;
		
		$query = "SELECT id FROM ".$focus->table_name." WHERE google_id='".$gid."' LIMIT 1";
		$result = $focus->db->query($query, true, "Failed to query matching google event.");
		
		if (mysql_num_rows($result) > 0)
		{
			$row = $focus->db->fetchByAssoc($result);
			$focus->retrieve($row['id']);
			
			return $focus;
		}
		else
			return false;
	}



	private function update_iah($focus, $event)
	{
		$times = $this->format_itime($focus, $event);

		if (strtotime($focus->date_modified) < strtotime(substr($event->updated->text, 0, (strlen($event->updated->text) - 5))))
		{
			$focus->name = $event->title->text;
			$focus->description = $event->content->text;

			
			$focus->date_start = $times['start_primary'];
			$focus->time_start = $times['start_secondary'];
			
			switch($focus->object_name)
			{
				case "Task":
					$focus->date_due = $times['end_primary'];
					$focus->time_due = $times['end_secondary'];
					break;
					
				case "Meeting":
					foreach ($event->where as $where)
					{
						$focus->location = $where;
					}				
					$focus->duration_hours = $times['end_primary'];
					$focus->duration_minutes = $times['end_secondary'];
					$focus->is_daylong = $times['is_daylong'];
					$focus->reminder_time = $times['reminder_time'];
					$focus->date_end = $times['date_end'];
					break;
					
				default:
					$focus->duration_hours = $times['end_primary'];
					$focus->duration_minutes = $times['end_secondary'];
					$focus->is_daylong = $times['is_daylong'];
					$focus->reminder_time = $times['reminder_time'];
					$focus->date_end = $times['date_end'];
					break;
			}
			
			$focus->save();

			$query = "UPDATE `".$focus->table_name."` SET `date_start`='".$times['start_primary']."', `time_start`='".$times['start_secondary']."', `date_end`='".$times['date_end']."' WHERE `id`='".$focus->id."'";
			$focus->db->query($query);
		}
	}
	
	
	
	private function new_iah($class, $event)
	{
		$focus = new $class;
		
		$focus->name = $event->title->text;
		$focus->description = $event->content->text;

		$times = $this->format_itime($focus, $event);
		
		$focus->date_start = $times['start_primary'];
		$focus->time_start = $times['start_secondary'];

		switch($focus->object_name)
		{
			case "Task":
				$focus->date_due = $times['end_primary'];
				$focus->time_due = $times['end_secondary'];
				$focus->date_due_flag = "off";
				$focus->date_start_flag = "off";
				$focus->priority = "P1";
				$focus->status = "Not Started";
				break;

			case "Meeting":
				foreach ($event->where as $where)
				{
					$focus->location = $where;
				}
				$focus->parent_type = "Accounts";
				$focus->status = "Planned";
				$focus->duration_hours = $times['end_primary'];
				$focus->duration_minutes = $times['end_secondary'];
				$focus->is_daylong = $times['is_daylong'];
				$focus->reminder_time = $times['reminder_time'];
				$focus->date_end = $times['date_end'];
				break;
									
			default:
				$focus->duration_hours = $times['end_primary'];
				$focus->duration_minutes = $times['end_secondary'];
				$focus->is_daylong = $times['is_daylong'];
				$focus->reminder_time = $times['reminder_time'];
				$focus->date_end = $times['date_end'];
				break;
		}


		$focus->assigned_user_id = $this->user->id;
		$focus->google_id = $event->id->text;
		
		$focus->save();
		
		$query = "UPDATE `".$focus->table_name."` SET `date_start`='".$times['start_primary']."', `time_start`='".$times['start_secondary']."', `date_end`='".$times['date_end']."' WHERE `id`='".$focus->id."'";
		$focus->db->query($query);
	}



	private function query_google_id($calendar, $gid)
	{		
		try
		{
			$event = $calendar->getEntry($gid, 'Zend_Gdata_Calendar_EventEntry');
		}
		catch (Zend_Gdata_App_Exception $e)
		{
			$GLOBALS['log']->debug('Failed to query Google for id ['.$gid.']: '.$e->getMessage());
			return false;
		}
		
		return $event;
	}



	private function update_google(&$focus, $calendar, $event)
	{
		$event->title = $calendar->newTitle($focus->name);
		$event->content = $calendar->newContent($focus->description);

		$gtime = $this->format_gtime($focus);
		
		$when = $calendar->newWhen();
		$when->startTime = $gtime['start'];
		$when->endTime = $gtime['end'];
		$rem_alert = $calendar->newReminder();
		$rem_email = $calendar->newReminder();
		$rem_array = array();
		
		if (isset($focus->reminder_time) && ($focus->reminder_time > 0))
		{
			$rem_alert->method = "alert";
			$rem_alert->minutes = ceil($focus->reminder_time / 60);
			$rem_array[] = $rem_alert;
		}

		if (isset($focus->email_reminder_time) && ($focus->email_reminder_time > 0))
		{
			$rem_email->method = "email";
			$rem_email->minutes = ceil($focus->email_reminder_time / 60);
			$rem_array[] = $rem_email;
		}
		
		$when->reminders = $rem_array;
		
		$event->when = array($when);

		if ($focus->object_name == "Meeting")
			$event->where = array($calendar->newWhere($focus->location));

		try {
			$event->save();
		} catch (Zend_Gdata_App_Exception $e) {
			$GLOBALS['log']->debug('Failed to update Google event: '.$e->getMessage());
			return null;
		}

		return $event;
	}
	
	
	
	private function new_google(&$focus, $calendar)
	{
		$newEvent = $calendar->newEventEntry();
  
		$newEvent->title = $calendar->newTitle($focus->name);
		$newEvent->content = $calendar->newContent($focus->description);
  
		$gtime = $this->format_gtime($focus);
		
		$when = $calendar->newWhen();
		$when->startTime = $gtime['start'];
		$when->endTime = $gtime['end'];
		
		$rem_alert = $calendar->newReminder();
		$rem_email = $calendar->newReminder();
		$rem_array = array();
		
		if (isset($focus->reminder_time) && ($focus->reminder_time > 0))
		{
			$rem_alert->method = "alert";
			$rem_alert->minutes = ceil($focus->reminder_time / 60);
			$rem_array[] = $rem_alert;
		}

		if (isset($focus->email_reminder_time) && ($focus->email_reminder_time > 0))
		{
			$rem_email->method = "email";
			$rem_email->minutes = ceil($focus->email_reminder_time / 60);
			$rem_array[] = $rem_email;
		}
		
		$when->reminders = $rem_array;

		$newEvent->when = array($when);

		if ($focus->object_name == "Meeting")
			$newEvent->where = array($calendar->newWhere($focus->location));

		try
		{
			$createdEvent = $calendar->insertEvent($newEvent);
		}
		catch (Zend_Gdata_App_Exception $e)
		{
			$GLOBALS['log']->debug('Failed to create new Google event: '.$e->getMessage());
		}
		
		$focus->retrieve($focus->id);
		$focus->google_id = $createdEvent->id->text;
		$focus->save();

		return $createdEvent->id->text;
	}



	private function to_iah()
	{
		$timezone = new Zend_Gdata_Calendar_Extension_Timezone();
		
		if ($this->open_receive)
		{
			$calendar = new Zend_Gdata_Calendar($this->client);

			$last_sync = explode(" ", $this->last_sync_date_to_iah);
			$sync_date = explode("-", $last_sync[0]);
			$sync_time = explode(":", $last_sync[1]);
			
			if (empty($this->last_sync_date_to_iah))
				$sync_epoch = 0;
			else
				$sync_epoch = gmmktime($sync_time[0],$sync_time[1],$sync_time[2],$sync_date[2],$sync_date[1],$sync_date[0]);

			$query = $calendar->newEventQuery();
	  		$query->setUser('default');
			$query->setVisibility('private');
			$query->setProjection('full');
			$query->setUpdatedMin(gmdate("D, j M Y H:i:s", $sync_epoch)." GMT");
			$query->setParam("ctz", "UTC");

			$cal_feed = $calendar->getCalendarEventFeed($query);
			
			foreach ($cal_feed as $cal) 
			{
				$match = false;
				$bean_list = array("Call", "Meeting", "Task");
				
				foreach ($bean_list as $bean)
				{
					$mod = $this->query_iah_id($bean, $cal->id->text);
					
					if ($mod)
					{
						$this->update_iah($mod, $cal);
						$match = true;						
					}
				}
				
				if (!$match)
					$this->new_iah($bean_list[1], $cal);
				
			}
		}
	}
	


	private function to_google($focus)
	{
		$calendar = new Zend_Gdata_Calendar($this->client);

		$ret = null;
		
		if (!empty($focus->google_id))
		{
			if ($event = $this->query_google_id($calendar, $focus->google_id))
			{
				if (strtotime($focus->date_modified) > strtotime(substr($event->updated->text, 0, (strlen($event->updated->text) - 5))))
					$ret = $this->update_google($focus, $calendar, $event);
			}
		}
		else
		{
			// Need to add scanning for possible matches on existing entries before creating a whole new one.
			$ret = $this->new_google($focus, $calendar);
		}

		return $ret;
	}
	


	private function two_way()
	{
		if ($this->mass_update)
		{
			if ($this->bean)
			{
				foreach ($this->bean as $b)
				{
					$this->to_google($b);
				}
			}
			else
			{
				// There are no records to send to google
			}
		}
		else
		{
			$this->to_google($this->bean);
		}

		$this->to_iah();	
	}

	// End Private Sync
	///////////////////////////////////////////////////////////////////////////////////////////////
}
?>
