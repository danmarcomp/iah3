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

class DateTimeUtil
{
		var $timezone;
		var $sec;
		var $min;
		var $hour;
		var $zhour;
		var $day;
		var $zday;
		var $day_of_week;
		var $day_of_week_short;
		var $day_of_week_long;
		var $day_of_year;
		var $week;
		var $month;
		var $zmonth;
		var $month_short;
		var $month_long;
		var $year;
		var $am_pm;
		var $tz_offset;

		// unix epoch time
		var $ts;
		
		
		// longreach - added
		var $week_start_day = 0;
		
		
	static function get_time_start( $date_start, $time_start)
	{	
		$match=array();
		
		preg_match("/([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})/",$date_start,$match);
		$time_arr = array();
		$time_arr['year'] = (int)$match[1];
		$time_arr['month'] = (int)$match[2];
		$time_arr['day'] = (int)$match[3];

		if ( empty( $time_start) )
		{
			$time_arr['hour'] = 0;
			$time_arr['min'] = 0;
		}
		else
		{
			if (preg_match("/^(\d\d*):(\d\d*):(\d\d*)$/",$time_start,$match))
			{
				$time_arr['hour'] = (int)$match[1];
				$time_arr['min'] = (int)$match[2];
			}
			else if ( preg_match("/^(\d\d*):(\d\d*)$/",$time_start,$match))
			{
				$time_arr['hour'] = (int)$match[1];
				$time_arr['min'] = (int)$match[2];
			}
		}
		return new DateTimeUtil($time_arr,true);

	}
	static function get_time_end( $start_time, $duration_hours,$duration_minutes)
	{
		if ( empty($duration_hours))
		{
			$duration_hours = "00";
		}
		if ( empty($duration_minutes))
		{
			$duration_minutes = "00";
		}

		$added_seconds = ($duration_hours * 60 * 60 + $duration_minutes * 60 ) - 1; 

		$time_arr = array();
		$time_arr['year'] = $start_time->year;
		$time_arr['month'] = $start_time->month;
		$time_arr['day'] = $start_time->day;
		$time_arr['hour'] = $start_time->hour;
		$time_arr['min'] = $start_time->min;
		$time_arr['sec'] = $added_seconds;
		return new DateTimeUtil($time_arr,true);

	}

	function get_date_str()
	{
		
		$arr = array();
		if ( isset( $this->hour))
		{
		 array_push( $arr, "hour=".$this->hour);
		}
		if ( isset( $this->day))
		{
		 array_push( $arr, "day=".$this->day);
		}
		if ( isset( $this->month))
		{
		 array_push( $arr, "month=".$this->month);
		}
		if ( isset( $this->year))
		{
		 array_push( $arr, "year=".$this->year);
		}
		return  ("&".implode('&',$arr));
	}

	function get_tomorrow()
	{
			$date_arr = array('day'=>($this->day + 1),		
			'month'=>$this->month,
			'year'=>$this->year);

		return new DateTimeUtil($date_arr,true);
	}
	function get_yesterday()
	{
			$date_arr = array('day'=>($this->day - 1),		
			'month'=>$this->month,
			'year'=>$this->year);

		return new DateTimeUtil($date_arr,true);
	}

	// longreach - added params
	function get_mysql_date($userTimeZone = false, $end = false)
	{
		// longreach - added
		if (!$userTimeZone) {
		return $this->year."-".$this->zmonth."-".$this->zday;
		// longreach - start added
		} else {
			global $timedate;
			$offset = $timedate->adjustmentForUserTimeZone();
			$date = $this->year."-".$this->zmonth."-".$this->zday;
			if ($end) $date .= ' + 1 day';
				return gmdate('Y-m-d', strtotime($date) - $offset * 60);
		}
		// longreach - end added
	}
		
	function get_mysql_time()
	{
		return $this->hour.":".$this->min;
	}

  function parse_utc_date_time($str)
  {
    preg_match('/(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})(\d{2})Z/',$str,$matches);

    $date_arr = array(
      'year'=>$matches[1],
      'month'=>$matches[2],
      'day'=>$matches[3],
      'hour'=>$matches[4],
      'min'=>$matches[5]);

      $date_time = new DateTimeUtil($date_arr,true);

      $date_arr = array('ts'=>$date_time->ts + $date_time->tz_offset);

      return new DateTimeUtil($date_arr,true);
  }
  
    static function parse_date_time($str)
  {
    preg_match('/(\d{4})\-(\d{1,2})\-(\d{1,2}) (\d{1,2}):(\d{2}):(\d{2})/',$str,$matches);

    $date_arr = array(
      'year'=>$matches[1],
      'month'=>$matches[2],
      'day'=>$matches[3],
      'hour'=>$matches[4],
      'min'=>$matches[5]);

      $date_time = new DateTimeUtil($date_arr,true);

      $date_arr = array('ts'=>$date_time->ts + $date_time->tz_offset);

      return new DateTimeUtil($date_arr,true);
  }

	function get_utc_date_time()
	{
		return $this->year.$this->zmonth.$this->zday. "T".$this->zhour.$this->min."00Z";
	}

	function get_first_day_of_last_year()
	{
			$date_arr = array('day'=>1,		
			'month'=>1,
			'year'=>($this->year - 1));

		return new DateTimeUtil($date_arr,true);

	}
	function get_first_day_of_next_year()
	{
			$date_arr = array('day'=>1,		
			'month'=>1,
			'year'=>($this->year + 1));

		return new DateTimeUtil($date_arr,true);

	}

	function get_first_day_of_next_week()
	{
		$first_day = $this->get_day_by_index_this_week($this->week_start_day); // longreach - modified
			$date_arr = array('day'=>($first_day->day + 7),		
			'month'=>$first_day->month,
			'year'=>$first_day->year);

		return new DateTimeUtil($date_arr,true);

	}
	function get_first_day_of_last_week()
	{
		$first_day = $this->get_day_by_index_this_week($this->week_start_day); // longreach - modified
			$date_arr = array('day'=>($first_day->day - 7),		
			'month'=>$first_day->month,
			'year'=>$first_day->year);

		return new DateTimeUtil($date_arr,true);
	}
	function get_first_day_of_last_month()
	{
		if ($this->month == 1)
		{
			$month = 12;
			$year = $this->year - 1;
		}
		else
		{
			$month = $this->month - 1;
			$year = $this->year ;
		}
			$date_arr = array('day'=>1,		
			'month'=>$month,
			'year'=>$year);

		return new DateTimeUtil($date_arr,true);

	}
	function get_first_day_of_next_month()
	{
		$date_arr = array('day'=>1,		
			'month'=>($this->month + 1),
			'year'=>$this->year);
		return new DateTimeUtil($date_arr,true);
	}


	function fill_in_details()
	{
		global $mod_strings;
		$hour = 0;
		$min = 0;
		$sec = 0;
		$day = 1;
		$month = 1;
		$year = 1970;

		if ( isset($this->sec))
		{
			$sec = $this->sec;
		}
		if ( isset($this->min))
		{
			$min = $this->min;
		}
		if ( isset($this->hour))
		{
			$hour = $this->hour;
		}
		if ( isset($this->day))
		{
			$day= $this->day;
		}
		if ( isset($this->month))
		{
			$month = $this->month;
		}
		if ( isset($this->year))
		{
			$year = $this->year;
		}
		else
		{
			sugar_die ("fill_in_details: year was not set");
		}
		$this->ts = mktime($hour,$min,$sec,$month,$day,$year);
		$this->load_ts($this->ts);

	}

	function load_ts($timestamp)
	{
	//	global $mod_list_strings;
		global $current_language, $app_list_strings;
		if ( empty($timestamp))
		{
		
			$timestamp = time();
		}

		$this->ts = $timestamp;
   		global $timedate;

		$date_str = date('i:G:H:j:d:t:w:z:L:W:n:m:Y:Z',$timestamp);
		$tdiff = $timedate->adjustmentForUserTimeZone();
		list(
		$this->min,
		$this->hour,
		$this->zhour,
		$this->day,
		$this->zday,
		$this->days_in_month,
		$this->day_of_week,
		$this->day_of_year,
		$is_leap,
		$this->week,
		$this->month,
		$this->zmonth,
		$this->year,
		$this->tz_offset)
		 = explode(':',$date_str);
		$this->tz_offset = date('Z') - $tdiff * 60;

        if (isset($app_list_strings['weekdays_dom']))
		    $this->day_of_week_short = $app_list_strings['weekdays_dom'][$this->day_of_week];
        if (isset($app_list_strings['weekdays_long_dom']))
            $this->day_of_week_long = $app_list_strings['weekdays_long_dom'][$this->day_of_week];
        if (isset($app_list_strings['months_dom']))
            $this->month_short = $app_list_strings['months_dom'][$this->month];
        if (isset($app_list_strings['months_long_dom']))
            $this->month_long = $app_list_strings['months_long_dom'][$this->month];

        $this->days_in_year = 365;

		if ($is_leap == 1)
		{
			$this->days_in_year += 1;
		}


	}

	function DateTimeUtil(&$time,$fill_in_details)
	{	
	
	
		// longreach - start added
		global $current_user;
		$this->week_start_day = (int)$current_user->week_start_day;
		// longreach - end added
		
	
		if (! isset( $time) || count($time) == 0 )
		{
			$this->load_ts(null);
		}
		else if ( isset( $time['ts']))
		{
			$this->load_ts($time['ts']);
		}
		else if ( isset( $time['date_str']))
		{
			list($this->year,$this->month,$this->day)= 
				explode("-",$time['date_str']);
			if ($fill_in_details)
			{
				$this->fill_in_details();
			}
		}
		else
		{
			if ( isset($time['sec']))
			{
        			$this->sec = $time['sec'];
			}
			if ( isset($time['min']))
			{
        			$this->min = $time['min'];
			}
			if ( isset($time['hour']))
			{
        			$this->hour = $time['hour'];
			}
			if ( isset($time['day']))
			{
        			$this->day = $time['day'];
			}
			if ( isset($time['week']))
			{
        			$this->week = $time['week'];
			}
			if ( isset($time['month']))
			{
        			$this->month = $time['month'];
			}
			if ( isset($time['year']) && $time['year'] >= 1970)
			{
        			$this->year = $time['year'];
			}
			else
			{
				return null;
			}

			if ($fill_in_details)
			{
				$this->fill_in_details();
			}

		}
	}

	function dump_date_info()
	{
		echo "min:".$this->min."<br>\n";
		echo "hour:".$this->hour."<br>\n";
		echo "day:".$this->day."<br>\n";
		echo "month:".$this->month."<br>\n";
		echo "year:".$this->year."<br>\n";
	}

	function get_hour()
	{
		$hour = $this->hour;
		if ($this->hour > 12)
		{
			$hour -= 12;
		}
		else if ($this->hour == 0)
		{
			$hour = 12;
		}
		return $hour;
	}
	
	function get_24_hour()
	{
		return $this->hour;
	}
	
	function get_am_pm()
	{
		if ($this->hour >=12)
		{
			return "PM";
		}
		return "AM";
	}
	
	function get_day()
	{
		return $this->day;
	}

	function get_month()
	{
		return $this->month;
	}

	function get_day_of_week_short()
	{
		return $this->day_of_week_short;
	}
	function get_day_of_week()
	{
		return $this->day_of_week_long;
	}


	function get_month_name()
	{
		return $this->month_long;
	}

	function get_datetime_by_index_today($hour_index, $minutes=0)
	{
		$arr = array();

		if ( $hour_index < 0 || $hour_index > 23  )
		{
			sugar_die("hour is outside of range");
		}

		$arr['hour'] = $hour_index;
		$arr['min'] = $minutes;
		$arr['day'] = $this->day;

		$arr['month'] = $this->month;
		$arr['year'] = $this->year;

		return new DateTimeUtil($arr,true);
	}

	function get_hour_end_time()
	{
		$arr = array();
		$arr['hour'] = $this->hour;
		$arr['min'] = 59;
		$arr['sec'] = 59;
		$arr['day'] = $this->day;

		$arr['month'] = $this->month;
		$arr['year'] = $this->year;

		return new DateTimeUtil($arr,true);
	}
	
	function get_subhour_end_time($mins)
	{
		$arr = array();
		$arr['hour'] = $this->hour;
		$arr['min'] = $this->min + $mins - 1;
		$arr['sec'] = 59;
		$arr['day'] = $this->day;

		$arr['month'] = $this->month;
		$arr['year'] = $this->year;

		return new DateTimeUtil($arr,true);
	}

	function get_day_end_time()
	{
		$arr = array();
		$arr['hour'] = 23;
		$arr['min'] = 59;
		$arr['sec'] = 59;
		$arr['day'] = $this->day;

		$arr['month'] = $this->month;
		$arr['year'] = $this->year;

		return new DateTimeUtil($arr,true);
	}

	function get_day_by_index_this_week($day_index)
	{
		$arr = array();

		if ( $day_index < 0 || $day_index > 6  )
		{
			sugar_die("day is outside of week range");
		}
		
		// longreach - added - changing week start (3aCRM)
		$day_index += $this->week_start_day;

		$arr['day'] = $this->day + 
			($day_index - $this->day_of_week);

		$arr['month'] = $this->month;
		$arr['year'] = $this->year;

		return new DateTimeUtil($arr,true);
	}
	function get_day_by_index_this_year($month_index)
	{
		$arr = array();
		$arr['day'] = 1;
		$arr['month'] = $month_index+1;
		$arr['year'] = $this->year;

		return new DateTimeUtil($arr,true);
	}

	function get_day_by_index_this_month($day_index)
	{
		$arr = array();
		$arr['day'] = $day_index + 1;
		$arr['month'] = $this->month;
		$arr['year'] = $this->year;

		return new DateTimeUtil($arr,true);
	}

	function getHashList($view, &$start_time,&$end_time, $minBreak=60)
	{
		$hash_list = array();
        
        if (version_compare(phpversion(), '5.0') < 0) 
            $new_time = $start_time;
        else 
            $new_time = clone($start_time);
        
		$arr = array();

		if ( $view != 'day')
		{
		  $end_time = $end_time->get_day_end_time();
		}


		if (empty($new_time->ts))
		{
			return;
		}

		if ( $new_time->ts == $end_time->ts)
		{
			$end_time->ts+=1;
		}
		
		$new_time->min = str_pad((string)floor($new_time->min / $minBreak) * $minBreak, 2, '0', STR_PAD_LEFT);

		 while( $new_time->ts < $end_time->ts)
		 {
		     
		  $arr['month'] = $new_time->month;
		  $arr['year'] = $new_time->year;
		  $arr['day'] = $new_time->day;
		  $arr['hour'] = $new_time->hour;
		  $arr['min'] = $new_time->min;
		  if ( $view == 'day')
		  {
		   $hash_list[] = $new_time->get_mysql_date().":".$new_time->hour.":".$new_time->min;
			$arr['min'] += $minBreak;
			if($arr['min'] >= 60) {
				$arr['min'] -= 60;
				$arr['hour'] += 1;
			}
		  }
		  else
		  {
		   $hash_list[] = $new_time->get_mysql_date();
		   $arr['day'] += 1;
		  }
		  $new_time = new DateTimeUtil($arr,true);
    }
		return $hash_list;
	}

	/*
	 * Conversion DateTime Local in Datetime WET (GMT+0)
     * Developed by Aymeric de Pas  (SII) - 2006-07-27 
	 */
	function convert_local_to_wet(){
		$date_local=$this->year."-".$this->zmonth."-".$this->zday." ".$this->zhour.":".$this->min.":00";
		$date_gmt=TimeDate::convert_to_gmt_datetime($date_local);
		
		if (preg_match("/^([0-9]{1,4}):([0-9]{1,2}):([0-9]{1,2}) ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})$/", strtr($date_gmt,"-",":"), $match))
			{
			$date_arr = array(
				'year' => $match[1],
				'month' => $match[2],
				'day' => $match[3],
				'hour' => $match[4],
				'min' => $match[5],
				'sec'=>$match[6] );
			}
		else
			sugar_die("error conversion of date in WET");
		
		$out= new DateTimeUtil($date_arr,true);
		$out->timezone='WET';
		return $out;
	}
}

?>
