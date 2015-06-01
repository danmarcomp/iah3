<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/*********************************************************************************
 * The contents of this file are subject to the SugarCRM Public License Version
 * 1.1.3 ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied.  See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * All copies of the Covered Code must include on each user interface screen:
 *    (i) the "Powered by SugarCRM" logo and
 *    (ii) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.  See full license for
 * requirements.
 *
 * The Original Code is: SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) 2004-2006 SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 ********************************************************************************/
/*********************************************************************************

 ********************************************************************************/
require_once('modules/Meetings/Meeting.php');
require_once('modules/Calls/Call.php');
require_once('modules/Calendar/DateTimeUtil.php');
require_once('modules/ACL/ACLController.php');
require_once('include/utils/activity_utils.php');
		
function sort_func_by_act_date($act0,$act1)
{
	if ($act0->start_time->ts == $act1->start_time->ts)
	{
		return 0;
	}

	return ($act0->start_time->ts < $act1->start_time->ts) ? -1 : 1;
}

class Calendar
{
	var $view = 'month';
	var $date_time;
	var $slices_arr = array();
        // for monthly calendar view, if you want to see all the
        // days in the grid, otherwise you only see that months
	var $show_only_current_slice = false;
	var $show_activities = true;
	var $show_tasks = true;
	// longreach - added
	var $show_assignments = false;
	var $show_events;
	
	var $activity_focus;
        var $show_week_on_month_view = true;
	var $use_24 = 1;
	var $toggle_appt = true;
	var $slice_hash = array();
	var $shared_users_arr = array();
	
	var $minBreak = 30;
	var $timeFormat = '';
	
	
	// longreach - start added
	var $week_start_day = 0;
	var $day_begin_hour = 8;
	var $day_end_hour = 18;
	// longreach - end added
	

	function Calendar($view,$time_arr=array())
	{
		global $current_user, $locale;

		$this->timeFormat = $locale->getPrecedentPreference('default_time_format');
		
		if( substr_count($this->timeFormat, 'h') > 0)
		{
			$this->use_24 = 0;
			// remove leading zeros from 12-hour times
			$this->timeFormat = strtr($this->timeFormat, 'h', 'g');
		}
		// end modified - to properly display the user's time format
		
		
		// longreach - start added
		$this->week_start_day = (int)$current_user->week_start_day;
		if(isset($current_user->day_begin_hour))
			$this->day_begin_hour = floor($current_user->day_begin_hour);
		if(isset($current_user->day_end_hour))
			$this->day_end_hour = ceil($current_user->day_end_hour);
		// longreach - end added


		$this->view = $view;

		if ( isset($time_arr['activity_focus']))
		{
			$this->activity_focus =  new CalendarActivity($time_arr['activity_focus']);
			$this->date_time =  $this->activity_focus->start_time;
		}
		else
		{
			$this->date_time = new DateTimeUtil($time_arr,true);
		}


		// longreach - start removed
		/*
		if (!( $view == 'day' || $view == 'month' || $view == 'year' || $view == 'week' || $view == 'shared') )
		{
			sugar_die ("view needs to be one of: day, week, month, shared, or year");
		}
		*/
		// longreach - end removed
		// longreach - start added
		if( !(
			$view == 'day' || $view == 'month' || $view == 'year' || $view == 'week' || $view == 'sharedbymonth' ||
			$view == 'sday' || $view == 'rweek' || $view == 'rday' || $view == 'shared'
		))
			sugar_die ("view needs to be one of: day, week, month, year, rweek, rday, sday, or sharedbymonth");
		// longreach - end added



		if ( empty($this->date_time->year))
		{
			sugar_die ("all views: year was not set");
		}
		else if ( ($this->view == 'month' || $this->view == 'sharedbymonth')&&  empty($this->date_time->month))
		{
			sugar_die ("month view: month was not set");
		}
		else if ( $this->view == 'week' && empty($this->date_time->week))
		{
			sugar_die ("week view: week was not set");
		}
		else if ( $this->view == 'shared' && empty($this->date_time->week))
		{
			sugar_die ("shared view: shared was not set");
		}


		// longreach - start added
		else if ( $this->view == 'rweek' && empty($this->date_time->week))
		{
			sugar_die ("rweek view: week was not set");
		}
		else if ( $this->view == 'rday' &&  empty($this->date_time->day) && empty($this->date_time->month))
		{
			sugar_die ("rday view: day and month was not set");
		}
		else if ( $this->view == 'sday' &&  empty($this->date_time->day) && empty($this->date_time->month))
		{
			sugar_die ("sday view: day and month was not set");
		}
		// longreach - end added


		else if ( $this->view == 'day' &&  empty($this->date_time->day) && empty($this->date_time->month))
		{
			sugar_die ("day view: day and month was not set");
		}

		$this->create_slices();

	}
	function add_shared_users(&$shared_users_arr)
	{
		$this->shared_users_arr = $shared_users_arr;
	}

	function get_view_name($view)
	{
		if ($view == 'month')
		{
			return "MONTH";
		}
		else if ($view == 'week')
		{
			return "WEEK";
		}
		else if ($view == 'day')
		{
			return "DAY";
		}
		else if ($view == 'year')
		{
			return "YEAR";
		}
		else if ($view == 'shared')
		{
			return "SHARED";
		}

		// longreach - start added
		else if ($view == 'sharedbymonth')
		{
			return "SHARED_BY_MONTH";
		}

		else if ($view == 'rweek')
			return "RWEEK";
		else if ($view == 'rday')
			return "RDAY";
		else if ($view == 'sday')
			return "SDAY";
		else if ($view == 'events')
			return "EVENTS";
		// longreach - end added


		else
		{
			sugar_die ("get_view_name: view ".$this->view." not supported");
		}
	}

	function get_slices_arr()
	{
		return $this->slices_arr;
	}


	function create_slices()
	{

		global $current_user;


		if ( $this->view == 'month' || $this->view == 'sharedbymonth')
		{
			$days_in_month = $this->date_time->days_in_month;


			$first_day_of_month = $this->date_time->get_day_by_index_this_month(0);
			$num_of_prev_days = $first_day_of_month->day_of_week;


			// longreach - start added - changing week start (contributed by 3aCRM)
			$num_of_prev_days -= $this->week_start_day;
			if($num_of_prev_days < 0)
				$num_of_prev_days += 7;
			// longreach - end added


			// do 42 slices (6x7 grid)

			for($i=0;$i < 42;$i++)
			{
				$slice = new Slice('day',$this->date_time->get_day_by_index_this_month($i-$num_of_prev_days));
				$this->slice_hash[$slice->start_time->get_mysql_date()] = $slice;
				array_push($this->slices_arr,  $slice->start_time->get_mysql_date());
			}

		}
		
		
		// longreach - removed
		/*
		else if ( $this->view == 'week' || $this->view == 'shared')
		*/
		// longreach - start added
		else if ( $this->view == 'week' || $this->view == 'shared' || $this->view == 'rweek' || $this->view == 'sharedbymonth')
		// longreach - end added
		

		{
			$days_in_week = 7;

			for($i=0;$i<$days_in_week;$i++)
			{
				$slice = new Slice('day',$this->date_time->get_day_by_index_this_week($i));
				$this->slice_hash[$slice->start_time->get_mysql_date()] = $slice;
				array_push($this->slices_arr,  $slice->start_time->get_mysql_date());
			}
		}
		
		
		// longreach - removed
		/*
		else if ( $this->view == 'day')
		*/
		// longreach - start added
		else if ( $this->view == 'day' || $this->view == 'rday')
		
		
		{
			$hours_in_day = 24;

			for($i=0;$i<$hours_in_day;$i++)
			{
				for($min = 0; $min < 60; $min += $this->minBreak) {
					$slice = new Slice('subhour',$this->date_time->get_datetime_by_index_today($i, $min), $this->minBreak);
					$hash = $slice->start_time->get_mysql_date().":".$slice->start_time->hour.":".$slice->start_time->min;
					$this->slice_hash[$hash] = $slice;
					array_push($this->slices_arr, $hash);
				}
			}
		}
		else if ( $this->view == 'year')
		{

			for($i=0;$i<12;$i++)
			{
				$slice = new Slice('month',$this->date_time->get_day_by_index_this_year($i));
				$this->slice_hash[$slice->start_time->get_mysql_date()] = $slice;
				array_push($this->slices_arr,  $slice->start_time->get_mysql_date());
			}
		}


		// longreach - start added
		else if ( $this->view == 'sday') {
			$slice = new Slice('day',$this->date_time->get_datetime_by_index_today(0));
			$this->slice_hash[$slice->start_time->get_mysql_date()] = $slice;
			array_push($this->slices_arr,  $slice->start_time->get_mysql_date());
		}
		// longreach - end added


		else
		{
			sugar_die("not a valid view:".$this->view);
		}

	}

	function add_activities($user,$type='sugar') {
		if ( $this->view == 'week' || $this->view == 'shared') {
			$end_date_time = $this->date_time->get_first_day_of_next_week();
		} else {
			$end_date_time = $this->date_time;
		}

		$acts_arr = array();

    	if($type == 'vfb') {
			$acts_arr = CalendarActivity::get_freebusy_activities($user, $this->date_time, $end_date_time);
	// longreach - start added
		} elseif( $type == 'resources' ) {
			$acts_arr = CalendarActivity::get_resource_activities(
				$user, // actually, resource where clause
				$this->date_time,
				$end_date_time
			);
		} elseif($this->show_assignments) {
			$acts_arr = CalendarActivity::get_assignments(
				$user->id,
				$this->date_time,
				$end_date_time
			);
	// longreach - end added
    	} else {
			// longreach - added
			if (!$this->show_events)
			$acts_arr = CalendarActivity::get_activities($user->id, $this->show_tasks, $this->date_time, $end_date_time	);
			// longreach - added
			else $acts_arr = CalendarActivity::get_events($this->date_time, $end_date_time);
    	}

    
    // Copy array
    $this->activities = $acts_arr;

    // loop thru each activity for this user
		for ($i = 0;$i < count($acts_arr);$i++) {
			$act = $acts_arr[$i];
			// get "hashed" time slots for the current activity we are looping through
	
			// longreach - start added
			if($this->view == 'rday')
				$hash_list = DateTimeUtil::getHashList('day',$act->start_time,$act->end_time, $this->minBreak);
			else
			// longreach - end added 
			/*
			$hash_list = DateTime::getHashList($this->view,$act->start_time,$act->end_time);
			 */
			$hash_list = DateTimeUtil::getHashList($this->view,$act->start_time,$act->end_time, $this->minBreak);

			for($j=0;$j < count($hash_list); $j++) {
				if(!isset($this->slice_hash[$hash_list[$j]]) || !isset($this->slice_hash[$hash_list[$j]]->acts_arr[$user->id])) {
					$this->slice_hash[$hash_list[$j]]->acts_arr[$user->id] = array();
				}
				array_push($this->slice_hash[$hash_list[$j]]->acts_arr[$user->id],$act);
			}
		}
	}

	function occurs_within_slice(&$slice,&$act)
	{
		// if activity starts within this slice
		// OR activity ends within this slice
		// OR activity starts before and ends after this slice
		if ( ( $act->start_time->ts >= $slice->start_time->ts &&
			 $act->start_time->ts <= $slice->end_time->ts )
			||
			( $act->end_time->ts >= $slice->start_time->ts &&
			$act->end_time->ts <= $slice->end_time->ts )
			||
			( $act->start_time->ts <= $slice->start_time->ts &&
			$act->end_time->ts >= $slice->end_time->ts )
		)
		{
			//print "act_start:{$act->start_time->ts}<BR>";
			//print "act_end:{$act->end_time->ts}<BR>";
			//print "slice_start:{$slice->start_time->ts}<BR>";
			//print "slice_end:{$slice->end_time->ts}<BR>";
			return true;
		}

		return false;

	}

	function get_previous_date_str()
	{
		if ($this->view == 'month' || $this->view == 'sharedbymonth')
		{
			$day = $this->date_time->get_first_day_of_last_month();
		}
		else if ($this->view == 'week' || $this->view == 'shared')
		{
			$day = $this->date_time->get_first_day_of_last_week();
		}

		// longreach - start added
		else if ($this->view == 'rweek')
			$day = $this->date_time->get_first_day_of_last_week();
		else if ($this->view == 'rday' || $this->view == 'sday')
			$day = $this->date_time->get_yesterday();
		// longreach - end added

		else if ($this->view == 'day')
		{
			$day = $this->date_time->get_yesterday();
		}
		else if ($this->view == 'year')
		{
			$day = $this->date_time->get_first_day_of_last_year();
		}
		else
		{
			return "get_previous_date_str: notdefined for this view";
		}
		// longreach - start added
		$str = $day->get_date_str();
		$str = $this->compose_url($str);
		$str .= ($this->show_assignments ? '&show_tasks=true' : '')	. (isset($_REQUEST['month_view']) ? ('&month_view=' .$_REQUEST['month_view']) : '');
		return $str;
		// longreach - end   added
		return $day->get_date_str();
	}

	function get_next_date_str()
	{
		if ($this->view == 'month' || $this->view == 'sharedbymonth')
		{
			$day = $this->date_time->get_first_day_of_next_month();
		}
		else
		if ($this->view == 'week' || $this->view == 'shared' )
		{
			$day = $this->date_time->get_first_day_of_next_week();
		}

		// longreach - start added
		else if ($this->view == 'rweek')
			$day = $this->date_time->get_first_day_of_next_week();
		else if ($this->view == 'rday' || $this->view == 'sday')
			$day = $this->date_time->get_tomorrow();
		// longreach - end added

		else
		if ($this->view == 'day')
		{
			$day = $this->date_time->get_tomorrow();
		}
		else
		if ($this->view == 'year')
		{
			$day = $this->date_time->get_first_day_of_next_year();
		}
		else
		{
			sugar_die("get_next_date_str: not defined for view");
		}
		
		// longreach - start added
		$str = $day->get_date_str();
		$str = $this->compose_url($str);
		$str .= ($this->show_assignments ? '&show_tasks=true' : '')	. (isset($_REQUEST['month_view']) ? ('&month_view=' .$_REQUEST['month_view']) : '');
		return $str;
		// longreach - end   added
		return $day->get_date_str();
	}

	function get_start_slice_idx()
	{

		// longreach - removed
		/*
		if ( $this->view == 'day' )
		*/
		// longreach - added
		if ( $this->view == 'day' || $this->view == 'rday' )
		
		
		{
		
			// longreach - start modified - make day start configurable
			$start_at = $this->day_begin_hour * (int)(60 / $this->minBreak);
			for($i=0; $i < $start_at; $i++)
			// longreach - end modified
			
			
			{
				if (count($this->slice_hash[$this->slices_arr[$i]]->acts_arr) > 0)
				{
					$start_at = $i;
					break;
				}
			}
			return $start_at;
		}
		else
		{
			return 0;
		}
	}
	function get_end_slice_idx()
	{
		if ( $this->view == 'month' || $this->view == 'sharedbymonth')
		{
			return $this->date_time->days_in_month - 1;
		}
		else if ( $this->view == 'week' || $this->view == 'shared')
		{
			return 6;
		}
		
		
		// longreach - start added
		else if ($this->view == 'rweek')
			return 6;
		else if ( $this->view == 'sday' )
			return $this->get_start_slice_idx();
		// longreach - end added
		
		
		// longreach - removed
		/*
		else if ( $this->view == 'day' )
		*/
		// longreach - added
		else if ( $this->view == 'day' || $this->view == 'rday' )
		
		
		{
			// longreach - modified - made starting hour configurable
			$end_at = $this->day_end_hour * (int)(60 / $this->minBreak);

			for($i=$end_at;$i < count($this->slices_arr)-1; $i++)
			{
				if (count($this->slice_hash[$this->slices_arr[$i+1]]->acts_arr) > 0)
				{
					$end_at = $i + 1;
				}
			}


			return $end_at;

		}
		else
		{
			return 1;
		}
	}

	// longreach - start added
	function compose_url($str)
	{
		static $ban = array(
			'module' => 1,
			'action' => 1,
			'view' => 1,
		);

		$parts = array();
		parse_str($str, $parts);
		foreach ($_GET as $k => $v) {
			if (!isset($parts[$k]) && !isset($ban[$k])) {
				$parts[$k] = $v;
			}
		}
		$a = array();
		foreach ($parts as $k => $v) {
			if (is_array($v)) {
				foreach ($v as $kk => $vv) {
					$a[] = $k . '[' . urlencode($kk) . ']=' . urlencode($vv);
				}
			} else {
				$a[] = $k . '=' . urlencode($v);
			}
		}
		$str = join('&', $a);
		return $str;
	}
	
	// longreach - end   adedd

}

class Slice
{
	var $view = 'day';
	var $start_time;
	var $end_time;
	var $acts_arr = array();

	function Slice($view,$time, $minBreak=null)
	{
		$this->view = $view;
		$this->start_time = $time;

		if ( $view == 'day')
		{
			$this->end_time = $this->start_time->get_day_end_time();
		}
		if ( $view == 'hour')
		{
			$this->end_time = $this->start_time->get_hour_end_time();
		}
		if( $view == 'subhour')
		{
			$this->end_time = $this->start_time->get_subhour_end_time($minBreak);
		}
	}
	function get_view()
	{
		return $this->view;
	}

}

// global to switch on the offet

$DO_USER_TIME_OFFSET = false;

class CalendarActivity
{
	var $sugar_bean;
	var $start_time;
	var $end_time;
	
	
	// longreach - added
	var $resources;
	
	

	function CalendarActivity($args)
	{
    // if we've passed in an array, then this is a free/busy slot
    // and does not have a sugarbean associated to it
		global $DO_USER_TIME_OFFSET;

    if ( is_array ( $args ))
    {
       $this->start_time = $args[0];     
       $this->end_time = $args[1];     
       $this->sugar_bean = null;
       return;
    }
 
    // else do regular constructor..

    	$sugar_bean = $args;
		global $timedate;
		$this->sugar_bean = $sugar_bean;


	// longreach - start replaced
        if (method_exists($this->sugar_bean, 'fill_in_additional_parent_fields')) {
            $this->sugar_bean->fill_in_additional_parent_fields();
        }
	// longreach - end replaced
	
	
        $this->sugar_bean->fill_in_additional_detail_fields();

		if ($sugar_bean->object_name == 'Task')
		{

			$newdate = $timedate->merge_date_time($this->sugar_bean->date_due, $this->sugar_bean->time_due);
			$tempdate  = $timedate->to_db_date($newdate,$DO_USER_TIME_OFFSET);

			if($newdate != $tempdate){
				$this->sugar_bean->date_due = $tempdate;
			}
			$temptime = $timedate->to_db_time($newdate, $DO_USER_TIME_OFFSET);
			if($newdate != $temptime){
				$this->sugar_bean->time_due = $temptime;
			}
			$this->start_time = DateTimeUtil::get_time_start(
				$this->sugar_bean->date_due,
				$this->sugar_bean->time_due
			);
			if ( empty($this->start_time))
			{
				return null;
			}

			$this->end_time = $this->start_time;
		}
		
		
		// longreach - start added
		elseif ($sugar_bean->object_name == 'ProjectTask')
		{

			$newdate = $timedate->merge_date_time($this->sugar_bean->date_due, $this->sugar_bean->time_due);
			$tempdate  = $timedate->to_db_date($newdate,$DO_USER_TIME_OFFSET);

			if($newdate != $tempdate){
				$this->sugar_bean->date_due = $tempdate;
			}
			$temptime = $timedate->to_db_time($newdate, $DO_USER_TIME_OFFSET);
			if($newdate != $temptime){
				$this->sugar_bean->time_due = $temptime;
			}
			$this->end_time = DateTimeUtil::get_time_start(
				$this->sugar_bean->date_due,
				$this->sugar_bean->time_due
			);

			$newdate = $timedate->merge_date_time($this->sugar_bean->date_start, $this->sugar_bean->time_start);
			$tempdate  = $timedate->to_db_date($newdate,$DO_USER_TIME_OFFSET);

			if($newdate != $tempdate){
				$this->sugar_bean->date_start = $tempdate;
			}
			$temptime = $timedate->to_db_time($newdate, $DO_USER_TIME_OFFSET);
			if($newdate != $temptime){
				$this->sugar_bean->time_start = $temptime;
			}
			$this->start_time = DateTimeUtil::get_time_start(
				$this->sugar_bean->date_start,
				$this->sugar_bean->time_start
			);
		}
		elseif ($sugar_bean->object_name == 'EventSession')
		{
			if (!empty($this->sugar_bean->date_start)) {
				$newdate = $this->sugar_bean->date_start;
				list($this->sugar_bean->date_start, $this->sugar_bean->time_start) = explode(' ', $newdate, 2);
				$tempdate  = $timedate->to_db_date($newdate,$DO_USER_TIME_OFFSET);
				$temptime = $timedate->to_db_time($newdate, $DO_USER_TIME_OFFSET);
				$this->start_time = DateTimeUtil::get_time_start(
					$tempdate,
					$temptime
				);
			}

			if (!empty($this->sugar_bean->date_end)) {
				$newdate = $this->sugar_bean->date_end;
				list($this->sugar_bean->date_end, $this->sugar_bean->time_end) = explode(' ', $newdate, 2);
				$tempdate  = $timedate->to_db_date($newdate,$DO_USER_TIME_OFFSET);
				$temptime = $timedate->to_db_time($newdate, $DO_USER_TIME_OFFSET);
				$this->end_time = DateTimeUtil::get_time_start(
					$tempdate,
					$temptime
				);
				if (empty($this->sugar_bean->date_start)) {
					$this->start_time = $this->end_time;
				}
			} else {
				$this->end_time = $this->start_time;
			}
		}
		// longreach - end added

		else
		{

			if ($sugar_bean->object_name == 'Meeting' || $sugar_bean->object_name == 'Call' ) 
			{
				$this->sugar_bean->load_relationship('contacts');
				$this->sugar_bean->contacts_arr = $this->sugar_bean->contacts->getBeans(new Contact());
			}

			$newdate = $this->sugar_bean->date_start;
			$tempdate  = $timedate->to_db_date($newdate,$DO_USER_TIME_OFFSET);

			if($newdate != $tempdate){
				$this->sugar_bean->date_start = $tempdate;
			}
			$temptime = $timedate->to_db_time($newdate,$DO_USER_TIME_OFFSET);
			if($newdate != $temptime){
				$this->sugar_bean->time_start = $temptime;
			}
			$this->start_time = DateTimeUtil::get_time_start(
			$this->sugar_bean->date_start,
			$this->sugar_bean->time_start
			);

		$this->end_time = DateTimeUtil::get_time_end(
			$this->start_time,
         		0,
        		$this->sugar_bean->duration
			);
		}

	}

	function get_occurs_within_where_clause($table_name, $rel_table, $start_ts, $end_ts, $field_name='date_start') {
		global $timedate;
		
		$start_mysql_date = explode('-', $start_ts->get_mysql_date());
		
		if($start_mysql_date[1] == 1) 
			$start_mysql_date_time = explode(' ', $timedate->handle_offset(($start_mysql_date[0] - 1) . '-' . '12-1 0:00', $timedate->get_db_date_time_format())); // handle DST offset
		else
			$start_mysql_date_time = explode(' ', $timedate->handle_offset($start_mysql_date[0] . '-' . ($start_mysql_date[1] - 1) . '-1 0:00', $timedate->get_db_date_time_format())); // handle DST offset
		
		//	get the last day of the month
		$end_mysql_date = explode('-', $end_ts->get_mysql_date());
		if($end_mysql_date[1] == 12) // december
			$end_mysql_date_time = explode(' ', $timedate->handle_offset(date('Y-m-d H:i:s', mktime(23, 59, 59, 1, 0, $end_mysql_date[0] + 1)), $timedate->get_db_date_time_format())); 
		else
			$end_mysql_date_time = explode(' ', $timedate->handle_offset(date('Y-m-d H:i:s', mktime(23, 59, 59, $end_mysql_date[1] + 1, 0, $end_mysql_date[0])), $timedate->get_db_date_time_format()));
		 		
		$where =  "(". db_convert($table_name.'.'.$field_name,'date_format',array("'%Y-%m-%d'"),array("'YYYY-MM-DD'")) ." >= '{$start_mysql_date_time[0]}' AND ";
		$where .= db_convert($table_name.'.'.$field_name,'date_format',array("'%Y-%m-%d'"),array("'YYYY-MM-DD'")) ." <= '{$end_mysql_date_time[0]}')";
			
		if($rel_table != '') {
			$where .= ' AND '.$rel_table.'.accept_status != \'decline\'';
		} 

		return $where;
	}

  function get_freebusy_activities(&$user_focus,&$start_date_time,&$end_date_time)
  {
 
      require_once('modules/vCals/vCal.php');
		  $act_list = array();
      $vcal_focus = new vCal();
      $vcal_str = $vcal_focus->get_vcal_freebusy($user_focus);

      $lines = explode("\n",$vcal_str);

      foreach ($lines as $line)
      {
        $dates_arr = array();
        if ( preg_match('/^FREEBUSY.*?:([^\/]+)\/([^\/]+)/i',$line,$matches))
        {
          $dates_arr[] = DateTimeUtil::parse_utc_date_time($matches[1]);
          $dates_arr[] = DateTimeUtil::parse_utc_date_time($matches[2]);
          $act_list[] = new CalendarActivity($dates_arr); 
        }
      }
		  usort($act_list,'sort_func_by_act_date');
      return $act_list;
  }


 	function get_activities($user_id, $show_tasks, &$view_start_time, &$view_end_time) {
		global $current_user;
		$act_list = array();
		$seen_ids = array();

		// get all upcoming meetings, tasks due, and calls for a user
		if(ACLController::checkAccess('Meetings', 'list', $user_id)) {
			$meeting = new Meeting();

			if($current_user->id  == $user_id) {
				$meeting->disable_row_level_security = true;
			}

			$where = CalendarActivity::get_occurs_within_where_clause($meeting->table_name, $meeting->rel_users_table, $view_start_time, $view_end_time);
			// longreach - added - hide private meetings
			if($user_id != $current_user->id) $where .= ' AND NOT is_private';
			$focus_meetings_list = array();
			$focus_meetings_list += build_related_list_by_user_id($meeting,$user_id,$where);
			foreach($focus_meetings_list as $meeting) {
				if(isset($seen_ids[$meeting->id])) {
					continue;
				}
				
				$seen_ids[$meeting->id] = 1;
				$act = new CalendarActivity($meeting);
	
				if(!empty($act)) {
					$act_list[] = $act;
				}
			}
		}
		
		if(ACLController::checkAccess('Calls', 'list', $user_id)) {
			$call = new Call();
	
			if($current_user->id  == $user_id) {
				$call->disable_row_level_security = true;
			}
	
			$where = CalendarActivity::get_occurs_within_where_clause($call->table_name, $call->rel_users_table, $view_start_time, $view_end_time);
			// longreach - added - hide private calls
			if($user_id != $current_user->id) $where .= ' AND NOT is_private';
			$focus_calls_list = array();
			$focus_calls_list += build_related_list_by_user_id($call,$user_id,$where);
	
			foreach($focus_calls_list as $call) {
				if(isset($seen_ids[$call->id])) {
					continue;
				}
				$seen_ids[$call->id] = 1;
	
				$act = new CalendarActivity($call);
				if(!empty($act)) {
					$act_list[] = $act;
				}
			}
		}


		if($show_tasks) {
			if(ACLController::checkAccess('Tasks', 'list', $user_id)) {
				$task = new Task();
	
				$where = CalendarActivity::get_occurs_within_where_clause('tasks', '', $view_start_time, $view_end_time, 'date_due');
				$where .= " AND tasks.assigned_user_id='$user_id' ";
	
			// longreach - added - doesn't matter because tasks aren't shown on the shared calendar (currently)
			if($user_id != $current_user->id) $where .= ' AND NOT is_private ';
			$where .= " AND tasks.status != 'Completed' ";

				$focus_tasks_list = $task->get_full_list("", $where,true);
	
				if(!isset($focus_tasks_list)) {
					$focus_tasks_list = array();
				}

				foreach($focus_tasks_list as $task) {
					$act = new CalendarActivity($task);
					if(!empty($act)) {
						$act_list[] = $act;
					}
				}
			}
		}

		usort($act_list,'sort_func_by_act_date');
		return $act_list;
	}


	// longreach - start added
	function &get_assignments($user_id,&$view_start_time,&$view_end_time) {
		require_once 'modules/ProjectTask/ProjectTask.php';
		$task = new ProjectTask();
		$act_list = array();

		$where = CalendarActivity::get_occurs_within_where_clause('project_task', '', $view_start_time,$view_end_time,'date_due');
		$where .= " AND ptu.user_id='$user_id' ";
		
		$focus_tasks_list = $task->get_full_list("", $where,true);

		if(empty($focus_tasks_list))
		{
			$focus_tasks_list = array();
		}

		foreach($focus_tasks_list as $task)
		{
			$act = new CalendarActivity($task);
			if ( ! empty($act))
			{
				$act_list[] = $act;
			}
		}
		
		usort($act_list,'sort_func_by_act_date');
		return $act_list;
		
	}
	
	function &get_events(&$view_start_time,&$view_end_time) {
		require_once 'modules/EventSessions/EventSession.php';
		$seedEvent = new EventSession;
		$act_list = array();
		$where1 = CalendarActivity::get_occurs_within_where_clause('event_sessions', '', $view_start_time,$view_end_time,'date_start');
		$where2 = CalendarActivity::get_occurs_within_where_clause('event_sessions', '', $view_start_time,$view_end_time,'date_end');
		$query = "SELECT DISTINCT(event_sessions.id) FROM event_sessions LEFT JOIN events ON events.id=event_sessions.event_id WHERE ( ($where1) OR ($where2)) AND event_sessions.deleted = 0";
		if (!empty($_REQUEST['event_type'])) $query .= ' AND events.event_type_id = \'' . $_REQUEST['event_type'] . '\'';


		$res = $seedEvent->db->query($query, true);

		while ($row = $seedEvent->db->fetchByAssoc($res)) {
			$event = new EventSession;
			$event->retrieve($row['id']);
			$act = new CalendarActivity($event);
			if ( ! empty($act))
			{
				$act_list[] = $act;
			}
		}
		usort($act_list,'sort_func_by_act_date');
		return $act_list;
	}
	// longreach - end added
	

 	// longreach - start added
 	function &get_resource_activities($arg_where, &$view_start_time, &$view_end_time)
	{
		global $current_user;
		$resources = array();
		$act_list = array();

		// get all upcoming meetings for a resource
		require_once('modules/Resources/Resource.php');
		$seed = new Resource();

		$where = CalendarActivity::get_occurs_within_where_clause('meetings', '', $view_start_time, $view_end_time);

		$query = "SELECT DISTINCT meetings.id meeting_id, resources.* FROM meetings ".
			"RIGHT JOIN meetings_resources lnk ON (lnk.meeting_id = meetings.id AND NOT lnk.deleted) ".
			"LEFT JOIN resources ON lnk.resource_id = resources.id ".
			"WHERE NOT meetings.deleted AND NOT resources.deleted AND $where ".
			"ORDER BY resources.name";
		if($arg_where)
			$query .= " AND $arg_where";
		
		$result = $seed->db->query($query, true, "Error retrieving resource list");
		
		while($row = $seed->db->fetchByAssoc($result))
		{
			$meeting_id = $row['meeting_id'];
			$resource_id = $row['id'];
			
			if(! isset($act_list[$meeting_id])) {
				$meeting = new Meeting();
				$meeting->retrieve($meeting_id);
				$act_list[$meeting_id] = new CalendarActivity($meeting);
			}
			$act =& $act_list[$meeting_id];
			
			if(isset($resources[$resource_id]))
				$resource = $resources[$resource_id];
			else {
				$resource = new Resource();
				foreach($resource->column_fields as $f)
					$resource->$f = $row[$f];
			}

			$act->resources[] = $resource;
		}

		usort($act_list,'sort_func_by_act_date');

		return $act_list;
	}	
	// longreach - end added method


}

/*
 * Import Meetings for shared calendar by month
 * Specific function to performance, only piece of information:
 * 		- id
 * 		- name
 *		- location
 *		- date_start
 *		- time_start
 *		- duration_hours
 *		- duration_minutes
 *
 * Developed by Aymeric de Pas (SII)  - 2006-07-27 
 */
class SharedActivity extends SugarBean {
	
	/*
	 * Add list of meeting for a day in slice
	 * For meeting numero $i :
     * 		slice->acts_arr[$i]['record'] //id of meeting
	 *						   ['name']
	 *						   ['location']
	 *						   ['time_start'] //in seconde for a local day
	 *						   ['time_end'] //in seconde for a local day
	 * Developed by Aymeric de Pas (SII)  - 2006-07-27 
	 */
	function SharedActivity(&$slice, $user, $seed, $resource, $clear = false) {
		global $current_user;
		
		parent::SugarBean(); //permet de se connecter � la bdd
		
		if($slice->view!='day')
		{
			sugar_die ("only for day view");
		}
		
		if ($clear) $slice->acts_arr = array();
		
		//on v�rifie que l'utisateur en cours � le droit de voir les meetings'
		if(ACLController::checkAccess($seed->module_dir, 'list', $current_user->id)) {

			//jour & heure en local 
			$date_local=$slice->start_time->year."-".$slice->start_time->zmonth."-".$slice->start_time->zday." ".$slice->start_time->zhour;
			
			//conversion en WET
			$date_wet_start=$slice->start_time->convert_local_to_wet();
			$date_wet_end=$slice->end_time->convert_local_to_wet();
			
			//les noms des tables
			$table=$seed->table_name; //meetings
			$table_rel = isset($seed->rel_users_table) ? $seed->rel_users_table : '';
			
			//on suppose que la dur�e d'un RDV est <= � 1 mois
			$month=$date_wet_start->month-1; //$date_wet_start moins 1 mois 	
			if($month<10)
				$month="0".$month;
			$start_search_date = $date_wet_start->year."-".$month."-".$date_wet_start->zday;
				
					 
			//construction de la requete
			$sql_ds = db_convert($table.".date_start",'date_format',array("'%Y-%m-%d'"),array("'YYYY-MM-DD'"));
			if ($seed->module_dir == 'Tasks') {
				$where = " ( IF(tasks.effort_estim_unit='days', DATE_ADD($sql_ds, INTERVAL effort_estim DAY), IF (tasks.effort_estim_unit='hours', DATE_ADD($sql_ds, INTERVAL effort_estim HOUR), DATE_ADD($sql_ds, INTERVAL effort_estim MINUTE))) >= '" . $date_wet_end->get_mysql_date()."' AND ".
							db_convert($table.".date_start",'date_format',array("'%Y-%m-%d'"),array("'YYYY-MM-DD'")). ">='".$start_search_date."' " .
							")" ;
			} elseif ($seed->module_dir == 'ProjectTask') {
				$sql_de = db_convert($table.".date_due",'date_format',array("'%Y-%m-%d'"),array("'YYYY-MM-DD'"));
				$where = " (" .
							$sql_ds . ">='".$start_search_date."' " .
							"AND ".$sql_de.">='".$date_wet_end->get_mysql_date()."' ".
							")" ;
			} elseif ($seed->module_dir == 'EventSession') {
				$sql_de = db_convert($table.".date_end",'date_format',array("'%Y-%m-%d'"),array("'YYYY-MM-DD'"));
				$where = " (" .
							$sql_ds . ">='".$start_search_date."' " .
							"AND ".$sql_de.">='".$date_wet_end->get_mysql_date()."' ".
							")" ;
			} else {
				$where = " (" .
							db_convert($table.".date_start",'date_format',array("'%Y-%m-%d'"),array("'YYYY-MM-DD'")). ">='".$start_search_date."' " .
							"AND ".db_convert($table.".date_start",'date_format',array("'%Y-%m-%d'"),array("'YYYY-MM-DD'"))."<='".$date_wet_end->get_mysql_date()."' ".
							")" ;
			}
			if ($resource) {
			} elseif ($table_rel) {
				$where .= ' AND '  .$table_rel.".user_id='".$user->id."' "
					. "AND ".$table_rel.".accept_status != 'decline'";
				if ($user->id != $current_user->id && ($seed->module_dir == 'Calls' || $seed->module_dir=='Meetings'))  $where .= " AND ! `{$seed->table_name}`.is_private ";
			} else {
				$where .= ' AND '  .$table.".assigned_user_id='".$user->id."' ";
				if ($user->id != $current_user->id && ($seed->module_dir == 'Calls' || $seed->module_dir=='Meetings'))  $where .= " AND ! `{$seed->table_name}`.is_private ";
			}

			if (!$resource) {
				$query = $seed->create_list_query("",$where);
			} else {
				$query = "SELECT DISTINCT meetings.* FROM meetings ".
					"RIGHT JOIN meetings_resources lnk ON (lnk.meeting_id = meetings.id AND NOT lnk.deleted) ".
					"LEFT JOIN resources ON lnk.resource_id = resources.id ".
					"WHERE NOT meetings.deleted AND NOT resources.deleted AND lnk.resource_id = '"
					. $resource->id . '\' AND ' . $where . " GROUP BY meetings.id";
			}
			$result = $this->db->query($query, true);
			
			$acts_arr =& $slice->acts_arr;
			$i=count($acts_arr);
			
			//calcul date debut et date de fin en format unix (timestamp) en GMT+0 (=WET)
			$date_day_start=gmmktime($date_wet_start->hour,$date_wet_start->min,$date_wet_start->sec,$date_wet_start->month,$date_wet_start->day,$date_wet_start->year);//mktime(int heure, int minute, int seconde, int mois, int jour, int ann�e, [int heure_hiver]); 
			$date_day_end=gmmktime($date_wet_end->hour,$date_wet_end->min,$date_wet_end->sec,$date_wet_end->month,$date_wet_end->day,$date_wet_end->year);
			
			while($row = $this->db->fetchByAssoc($result)) {
				//calcul date debut et date fin en format unix (timestamp)
				if (array_key_exists('time_start', $row)) {
					$gmDate = $row['date_start'] . ' ' . $row['time_start'];
				} else {
					$gmDate = $row['date_start'];
				}
				global $timedate;
				$localDate = explode(' ', $timedate->handle_offset($gmDate, 'Y-m-d H:i:s'));
				$date_start=$this->convert_ts($localDate[0], $localDate[1]);
				if (isset($row['duration_hours'])) {
					$delta = $row['duration_hours']*60*60+$row['duration_minutes']*60;
				} elseif (isset($row['effort_estim'])) {
					if ($row['effort_estim_unit'] == 'days') {
						$delta = $row['effort_estim'] * 24 * 3600;
					} elseif ($row['effort_estim_unit'] == 'hours') {
						$delta = $row['effort_estim'] * 3600;
					} else {
						$delta = $row['effort_estim'] * 60;
					}
				} else {
					$delta = 0;
				}
				$date_end = $date_start + $delta;
				if ($seed->module_dir == 'ProjectTask') {
					$date_end=$this->convert_ts($row['date_due'], '23:59:59');
				}
				if ($seed->module_dir == 'EventSessions') {
					$gmDate = $row['date_end'];
					if (!empty($row['date_end'])) {
						$localDate = explode(' ', $timedate->handle_offset($gmDate, 'Y-m-d H:i:s'));
						$date_end=$this->convert_ts($localDate[0], $localDate[1]);
					} else {
						if ($date_start >= $date_day_start) {
							$date_end = $date_day_start + 24*60*60-1;
						} else {
							$date_end =0;
						}
					}
				}
				if($date_end>$date_day_start)
					{
						$time_start=$date_start-$date_day_start;
						if($time_start!=60*60*24)
							{
							// l'id
							$acts_arr[$i]['record']=$row['id'];
							// le nom du RDV
							$acts_arr[$i]['name']=$row['name'];
							if (isset($row['location'])) {
								$acts_arr[$i]['location']=$row['location'];
							}
							$acts_arr[$i]['module'] = $seed->module_dir;
							
							// l'heure du d�but de RDV pour ce jour local (en secondes)
							$acts_arr[$i]['time_start'] = max(0, $time_start);
							

							$acts_arr[$i]['time_end'] = $date_start + $delta - $date_day_start;
							if ($seed->module_dir == 'ProjectTask') {
								$acts_arr[$i]['time_end'] = $date_end;
							}
							if ($seed->module_dir == 'EventSessions') {
								$acts_arr[$i]['time_end'] = $date_end;
							}
							//cas particuliers
							
							if($acts_arr[$i]['time_end']>=(24*60*60)) {
								$dt = $acts_arr[$i]['time_end'] - $date_day_start;
								if ($dt > 0 && $dt < (24*60*60)) {
									$acts_arr[$i]['time_end'] = $dt;
								} else {
									$acts_arr[$i]['time_end']=(24*60*60)-1;
								}
							}
							if($time_start<0)
								$acts_arr[$i]['time_start']=0;				

							if (isset($row['calendar_color'])) {
								$acts_arr[$i]['calendar_color'] = $row['calendar_color'];
							}
							if (isset($row['session_number'])) {
								$acts_arr[$i]['session_number'] = $row['session_number'];
							}
							
							$i++;}
					}
			}
		}
	}
	
	/*
	 * Conversion :
	 * 	- input $date //AAAA-MM-DD
	 * 			$time //JJ:MM:SS
	 *  - output timestamp
	 * Developed by Aymeric de Pas (SII)  - 2006-07-27 
	 */
	function convert_ts($date,$time)
	{
		$date=explode("-",$date);
		$ts=array();
		$ts['year']=$date[0];
		$ts['month']=$date[1];
		$ts['day']=$date[2];
		
		if (preg_match("/^(\d\d*):(\d\d*):(\d\d*)$/",$time,$match)){
			$ts['hour']=$match[1];
			$ts['min']=$match[2];
			$ts['sec']=$match[3];
			}
		else if(preg_match("/^(\d\d*):(\d\d*)$/",$time,$match))
			{
			$ts['hour']=$match[1];
			$ts['min']=$match[2];
			$ts['sec']=0;
			}
		else{
			$ts['hour']=0;
			$ts['min']=0;
			$ts['sec']=0;
		}
		return mktime($ts['hour'],$ts['min'],$ts['sec'],$ts['month'],$ts['day'],$ts['year']);
	}
}  
?>
