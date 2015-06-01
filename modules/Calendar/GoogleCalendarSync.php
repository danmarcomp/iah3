<?php

require_once 'include/upload_file.php';
require_once 'include/GoogleSync.php';
require_once 'modules/Documents/Document.php';
require_once 'modules/DocumentRevisions/DocumentRevision.php';

class GoogleCalendarSync extends GoogleSync
{

	protected function getConfigKey()
	{
		return 'google_calendar';
	}

	protected function getDirection()
	{
		return $this->user->getPreference('google_calendar_direction');
	}

	protected function getService()
	{
		return 'cl';
	}

	protected function getFeedSource()
	{
		return new Zend_Gdata_Calendar($this->client);
	}

	protected function getFeed($query)
	{
		return $this->feedSource->getCalendarEventFeed($query);
	}

	protected function getQuery()
	{
		$endDate = gmdate('Y-m-d\\TH:i:s+00:00', time() + 30 * 24 * 3600);
		$query = $this->feedSource->newEventQuery();
		$id = $this->user->getPreference('google_calendar_id');
		if (empty($id)) {
			$id = 'default';
		}
  		$query->setUser($id);
		$query->setVisibility('private');
		$query->setProjection('full');
		return $query;
	}

	protected function createIAHItem($sql_row = null)
	{
		$id = null;
		if (is_array($sql_row))
			$id = array_get_default($sql_row, 'id');
		if (!$id)
			return RowUpdate::blank_for_model('Meeting');

		switch (array_get_default($sql_row, 'module')) {
			case 'Calls':
				$model = 'Call';
				break;
			case 'Tasks':
				$model = 'Task';
				break;
			default:
				$model = 'Meeting';
				break;
		}
		$row = ListQuery::quick_fetch($model, $id, true, array('filter_deleted' => false));
		if ($row)
			return RowUpdate::for_result($row);
		
		return RowUpdate::blank_for_model($model);

	}

	protected function setIAHItemFields($iahItem, $item)
	{
		global $db;
		$iahItem->set('name', $item->title->text);
		if (!strlen($iahItem->getField('name'))) {
			$iahItem->set('name', translate('LBL_EMPTY_MEETING_TITLE', 'Calendar'));
		}
		$iahItem->set('description', $item->content->text);

		$times = $this->format_itime($iahItem, $item);
		
		$iahItem->set('date_start', $times['start_primary'] . ' ' . $times['start_secondary']);
		$iahItem->set('is_private', substr($item->visibility->value, -7) == 'private' ? 1 : 0);

		switch($iahItem->getModelName())
		{
			case "Task":
				$iahItem->set('date_due',  $times['end_primary'] . ' ' . $times['end_secondary']);
				if ($ahItem->new_record) {
					$iahItem->set('priority', "P1");
					$iahItem->set('status', "Not Started");
				}
				break;

			case "Meeting":
				foreach ($item->where as $where)
				{
					$iahItem->set('location', $where);
				}
				if ($iahItem->new_record) {
					$iahItem->set('parent_type', "Accounts");
					$iahItem->set('status',  "Planned");
				}
				$iahItem->set('duration', $times['end_primary'] * 60 + $times['end_secondary']);
				$iahItem->set('is_daylong', $times['is_daylong']);
				$iahItem->set('reminder_time', $times['reminder_time']);
				$iahItem->set('date_end', $times['date_end']);

				if (!empty($times['rule'])) {
					$rules = $times['rule'];
					$until = null;
					if (isset($rules['UNTIL'])) {
						$until = gmdate('Y-m-d H:i:s', strtotime(str_replace("Z", "", $rules['UNTIL']) . ' GMT'));
					}


					$r = RowUpdate::blank_for_model('RecurrenceRule');
					if ($iahItem->new_record) {
						if (!strlen($iahItem->getField('id'))) {
							$iahItem->set('id', create_guid());
						}
					} else {
						$lq = new ListQuery('RecurrenceRule');
						$lq->addSimpleFilter('parent_type', 'Meetings');
						$lq->addSimpleFilter('parent_id', $iahItem->getField('id'));
						$existingRules = $lq->runQuery();
						if (count($existingRules->rows) > 1) {
							$query = "DELETE FROM recurrence_rules WHERE parent_type='Meetings' AND parent_id='" 
								. $iahItem->getField('id') ."'";
							$db->query($query, true);
						} elseif (count($existingRules->rows) == 1) {
							$idx = $existingRules->getRowIndexes();
							$r = RowUpdate::for_result($existingRules->getRowResult($idx[0]));
						}

						$iahRules = $this->parseRule($r->getField('rule'));
						if (!empty($until)) {
							$iahRules['UNTIL'] = gmdate('Ymd\\THis\\Z', strtotime($until . ' GMT'));
						}
						$iahRules['FREQ'] = $r->getField('freq');
						$iahRules['INTERVAL'] = $r->getField('freq_interval');
						if ($iahRules != $rules) {
							$query = "DELETE FROM meetings WHERE recurrence_of_id='"
								. $iahItem->getField('id') . "'";
							$db->query($query);
						}
					}

					$r->set('freq', $rules['FREQ']);
					$r->set('freq_interval', array_get_default($rules, 'INTERVAL', 1));
					$r->set('until',  $until);

					$limit_count = array_get_default($rules, 'COUNT', 0);
					unset($rules['INTERVAL']);
					unset($rules['FREQ']);
					unset($rules['UNTIL']);
					unset($rules['COUNT']);


					$ruleStr = '';
					foreach ($rules as $k => $v) {
						if (!empty($ruleStr)) $ruleStr .= ';';
						$ruleStr .= "$k=$v";
					}
					$r->set('rule',  $ruleStr);

					$r->set('forward_times',  '');
					$r->set('date_last_instance',  '');
					$r->set('limit_count',  $limit_count);
					$r->set('instance_count', 0);
					$r->set('parent_type', 'Meetings');
					$r->set('parent_id', $iahItem->getField('id'));
					$r->save();
				}

				break;
									
			default:
				$iahItem->set('duration',  $times['end_primary'] * 60 + $times['end_secondary']);
				$iahItem->set('is_daylong', $times['is_daylong']);
				$iahItem->set('reminder_time', $times['reminder_time']);
				$iahItem->set('date_end', $times['date_end']);
				break;
		}


		$iahItem->set('assigned_user_id', $this->user->id);
	}

	protected function getSQLQuery($lastSyncTime)
	{
		$limit = (int)((int)array_get_default($this->params, 'max_items', 500)/3);
		$limit = 200;
		if ($limit < 1) {
			$limit = 1;
		}
		$tables = array('meetings' => 'Meetings', 'calls' => 'Calls', 'tasks' => 'Tasks');
		$queries = array();
		foreach ($tables as $table => $module) {
			if (empty($this->options['sync'][$module])) continue;
			$query = "SELECT id, '{$module}' as module FROM {$table} c "
				." LEFT JOIN google_sync s ON s.related_id=c.id AND s.user_id='".$this->user->id . "' AND s.related_type='{$module}'";
			$query .= " WHERE (IFNULL(s.google_id,'')!='' OR NOT c.deleted) ";
			$query .= " AND (c.date_modified > s.google_last_sync OR s.google_last_sync IS NULL)";
			$query .= " AND assigned_user_id = '{$this->user->id}' ";
			if ($module == 'Meetings') {
				$query .= " AND (recurrence_of_id = '' OR recurrence_of_id IS NULL) ";
			}
			$query .= ' ORDER BY date_modified ';
			$query .= ' LIMIT ' . $limit;
			$queries[] = $query;
		}
		if (!empty($queries)) {
			return '(' . join(') UNION (', $queries) . ')';
		} else {
			return	"SELECT id FROM meetings WHERE 0";
		}
	}

	protected function setGoogleItemFields($iahItem, $item)
	{

		$item->title = $this->feedSource->newTitle($iahItem->getField('name'));
		$item->content = $this->feedSource->newContent($iahItem->getField('description'));
		$item->visibility = $this->feedSource->newVisibility('http://schemas.google.com/g/2005#event.' . ($iahItem->getField('is_private')? 'private' : 'public'));
  
		$gtime = $this->format_gtime($iahItem);
		if ($gtime['start'] > $gtime['end']) {
			$gtime['end'] = $gtime['start'];
		}
		
		$when = $this->feedSource->newWhen();
		$when->startTime = $gtime['start'];
		$when->endTime = $gtime['end'];
		
		$rem_alert = $this->feedSource->newReminder();
		$rem_email = $this->feedSource->newReminder();
		$rem_array = array();
		
		if ($iahItem->getField('reminder_time') > 0) {
			$rem_alert->method = "alert";
			$rem_alert->minutes = ceil($iahItem->getField('reminder_time') / 60);
			$rem_array[] = $rem_alert;
		}

		if ($iahItem->getField('email_reminder_time') > 0) {
			$rem_email->method = "email";
			$rem_email->minutes = ceil($iahItem->getField('email_reminder_time') / 60);
			$rem_array[] = $rem_email;
		}
		
		$when->reminders = $rem_array;

		$item->when = array($when);

		if ($iahItem->getModelName() == "Meeting") {
			$item->where = array($this->feedSource->newWhere($iahItem->getField('location')));
			
			$lq = new ListQuery('RecurrenceRule', true);
			$lq->addSimpleFilter('parent_type', 'Meetings');
			$lq->addSimpleFilter('parent_id', $iahItem->getField('id'));
			$rules = $lq->runQuery();
			
			if (count($rules->rows) == 1) {
				$idx = $rules->getRowIndexes();
				$r = $rules->getRowResult($idx[0]);
				$parsedRule = $this->parseRule($r->getField('rule'));
				$parsedRule['FREQ'] = $r->getField('freq');
				$parsedRule['INTERVAL'] = $r->getField('freq_interval');
				$parsedRule['COUNT'] = $r->getField('limit_count');
				if ($r->getField('until')) {
					$parsedRule['UNTIL'] = gmdate('Ymd\\THis\\Z', strtotime($r->getField('until')));
				}
				$start = gmdate('Ymd\\THis\\Z', strtotime($iahItem->getField('date_start')));
				$m = (int)$iahItem->getField('duration');

				$end = gmdate('Ymd\\THis\\Z', strtotime($start . " + $m minutes" ));
				if ($iahItem->getField('is_daylong')) {
					$ruleText = 'DTSTART;VALUE=DATE:' . $start . "\n";
					$ruleText .= 'DTEND;VALUE=DATE:' . $end . "\n";
				} else {
					$ruleText = 'DTSTART:' . $start . "\n";
					$ruleText .= 'DTEND:' . $end . "\n";
				}
				if (isset($parsedRule['UNTIL']) && $parsedRule['UNTIL'] < $start) {
					unset($parsedRule['UNTIL']);
				}
				if (!empty($parsedRule['COUNT'])) {
					unset($parsedRule['UNTIL']);
				} else {
					unset($parsedRule['COUNT']);
				}
				$ruleText .= "RRULE:" ;
				$i = 0;
				foreach ($parsedRule as $k => $v) {
					if ($i) $ruleText .= ';';
					$ruleText .= "$k=$v";
					$i++;
				}
				$item->recurrence = $this->feedSource->newRecurrence($ruleText);
                unset($item->when);
			}
		}

	}

	protected function realCreateGoogleItem($iahItem)
	{
		$id = $this->user->getPreference('google_calendar_id');
		if (empty($id)) {
			$id = 'default';
		}
		$uri = Zend_Gdata_Calendar::CALENDAR_FEED_URI;
		$uri .= '/' . $id  .'/private/full';
		$item = $this->feedSource->newEventEntry();
		try	{
			$this->setGoogleItemFields($iahItem, $item);
			$item = $this->feedSource->insertEvent($item, $uri);
		} catch (Zend_Gdata_App_Exception $e) {
			$GLOBALS['log']->fatal('Failed to create new Google event: '.$e->getMessage());
			$item = null;
		}
		return $item;
	}

	protected function realUpdateGoogleItem($item, $iahItem)
	{
		$this->setGoogleItemFields($iahItem, $item);
		return $item;
	}

	protected function getListEntry($googleId)
	{
		return $this->feedSource->getEntry($googleId, 'Zend_Gdata_Calendar_EventEntry');
	}

	function format_itime($focus, $event)
	{	
		$allday = 0;
		$reminder = 0;
		$rule = null;

		if (!empty($event->recurrence)) {
			$m = array();
			if (preg_match('/^DTSTART;VALUE=DATE:(.*)$/m', $event->recurrence->text, $m)) {
				$startTime = $m[1];
				preg_match('/^DTEND;VALUE=DATE:(.*)$/m', $event->recurrence->text, $m);
				$endTime = $m[1];
				$allday = 1;
			} else {
				preg_match('/^DTSTART:(.*)$/m', $event->recurrence->text, $m);
				$startTime = $m[1];
				preg_match('/^DTEND:(.*)$/m', $event->recurrence->text, $m);
				$endTime = $m[1];
				$allday = (!strpos($endTime,"T")) ? 1 : 0;
			}
			preg_match('/^RRULE:(.*)$/m', $event->recurrence->text, $m);
			$rule = $m[1];
			$start_epoch = strtotime(str_replace("Z", "", $startTime) . ' GMT');
			$end_epoch = strtotime(str_replace("Z", "", $endTime) . ' GMT');
		} else foreach ($event->when as $when) {
			$allday = (!strpos($when->endTime,"T")) ? 1 : 0;
			$start_epoch = strtotime(str_replace("Z", "", $when->startTime) . ' GMT');
			$end_epoch = strtotime(str_replace("Z", "", $when->endTime) . ' GMT');

			if (is_array($when->reminders) && $focus->getField('reminder_time'))
			{
				foreach($when->reminders as $rem)
				{
					$reminder = $rem->minutes * 60;
				}
			}
		}

		$rule = $this->parseRule($rule);

		$start_date = gmdate("Y-m-d", $start_epoch);
		$start_time = gmdate("H:i:s", $start_epoch);
		$date_end = gmdate("Y-m-d", $end_epoch);

		switch ($focus->getModelName())
		{
			case "Task":
				$end_primary = gmdate("Y-m-d", $end_epoch);
				$end_secondary = gmdate("H:i:s", $end_epoch);	
				break;

			default:
				$duration = $end_epoch - $start_epoch;
				$end_primary = floor($duration / 3600);
				$end_secondary = floor(($duration % 3600) / 60);
				break;
		}
		
		

		return	array(
			"start_primary" => $start_date,
			"start_secondary" => $start_time,
			"end_primary" => $end_primary,
			"end_secondary" => $end_secondary,
			"date_end" => $date_end,
			"is_daylong" => $allday,
			"reminder_time" => $reminder,
			"rule" => $rule,
		);
	}

	function format_gtime($focus)
	{	
		$start_epoch = strtotime($focus->getField('date_start') . " GMT ");
		$start_date = gmdate("Y-m-d", $start_epoch);
		$start_time = gmdate("H:i:s", $start_epoch);
		
		switch ($focus->getModelName())
		{
			case "Task":
				$end_epoch = strtotime($focus->getField('date_due'));
				if (!$focus->getField('date_due')) {
					$end_date = $start_date;
					$end_time = $start_time;		
				} else {
					$end_date = $start_date = gmdate("Y-m-d", $end_epoch);
					$end_time = $start_time = gmdate("H:i:s", $end_epoch);		
				}
				break;

			default:
				$end_epoch =  $start_epoch + ($focus->getField('duration') * 60);
				$end_date = gmdate("Y-m-d", $end_epoch);
				$end_time = gmdate("H:i:s", $end_epoch);
				break;
		}
		
		if ($focus->getField('is_daylong'))
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
	
	protected function canCreateInIAH($item, $iahItem)
	{
		return $iahItem->getModelName() == 'Meeting';
	}

	protected function parseRule($rule)
	{
		$ret = array();
		$m = array();
		if (preg_match_all('/([^=;]*)=([^=;]*)($|;)/u', $rule, $m)) {
			foreach($m[1] as $i => $k) {
				$ret[$k] = $m[2][$i];
			}
		}
		return $ret;
	}

	/*
	protected function loadIahItemByGoogleId($iahItem, $googleId)
	{
		$query = "SELECT related_id, related_type FROM google_sync WHERE google_id = '" . $googleId . "' AND user_id='" . $this->user->id . "'";
		$res = $iahItem->db->query($query, true);
		if ($row = $iahItem->db->fetchByAssoc($res)) {
			if ($row['related_type'] == 'Tasks') {
				$iahItem = new Task;
			} elseif ($row['related_type'] == 'Calls') {
				$iahItem = new Call;
			} else {
				$iahItem = new Meeting;
			}
			$GLOBALS['disable_date_format'] = true;
			$iahItem->retrieve($row['related_id']);
			$GLOBALS['disable_date_format'] = false;
		}
		return $iahItem;
	}
	 */
	
	protected function canSyncToGoogle($iahItem)
	{
		if ($iahItem->getModelName() == 'Task'
			&& $iahItem->getField('date_start') == null
			&& $iahItem->getField('date_due') == null) {
				return false;
		}
		return true;
	}

	protected function isItemDeleted($item)
	{
		return $item->eventStatus->value == 'http://schemas.google.com/g/2005#event.canceled';
	}


}

/*
 *
 * FREQ=WEEKLY;WKST=MO;BYDAY=TU,TH
 *
 * FREQ=WEEKLY;WKST=MO;INTERVAL=3;BYDAY=TU,TH
 * FREQ=MONTHLY;WKST=MO;INTERVAL=4;BYDAY=4TU
 * FREQ=MONTHLY;WKST=MO;INTERVAL=4;BYMONTHDAY=23
 *
 */
