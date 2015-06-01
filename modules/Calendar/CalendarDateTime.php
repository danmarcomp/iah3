<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/******************************************************************************
* The contents of this file are subject to the CareBrains Software End User
* License Agreement ('License') which can be viewed at
* http://www.sugarforum.jp/download/cbieula.shtml
* By installing or using this file, You have unconditionally agreed to the
* terms and conditions of the License, and You may not use this file except in
* compliance with the License.  Under the terms of the license, You shall not,
* among other things: 1) sublicense, resell, rent, lease, redistribute, assign
* or otherwise transfer Your rights to the Software, and 2) use the Software
* for timesharing or service bureau purposes such as hosting the Software for
* commercial gain and/or for the benefit of a third party.  Use of the Software
* may be subject to applicable fees and any use of the Software without first
* paying applicable fees is strictly prohibited.
* Your Warranty, Limitations of liability and Indemnity are expressly stated
* in the License.  Please refer to the License for the specific language
* governing these rights and limitations under the License.
*****************************************************************************/
global $current_language;
global $timedate;

require_once 'modules/Calendar/utils.php';
load_holidays();
include_once("modules/Calendar/language/lunacalendar.php");

define('CAL_BASE_LOCAL', 1);
define('CAL_BASE_GMT', 2);

define('CAL_DATETIME_FORMAT', "Y-m-d H:i:s");
define('CAL_DATE_FORMAT', 'Y-m-d');
define('CAL_TIME_FORMAT', "H:i:s");
define('CAL_HI_FORMAT', "H:i");

class CalendarDateTime {
	var $gmtDateTime;
	var $gmtDateTime_t;
	var $gmtDate;
	var $gmtTime;
	var $gmtHi;

	var $gmtDateTimeArray = array();
	var $gmtYear;
	var $gmtMonth;
	var $gmtDay;
	var $gmtHour;
	var $gmtMin;
	var $gmtSec;
	
	var $gmtNextDateTime;
	var $gmtNextDate;
	var $gmtPrevDateTime;
	var $gmtPrevDate;

	var $gmtWeekFirstDateTime;
	var $gmtWeekFirstDate;
	var $gmtWeekEndDateTime;
	var $gmtWeekEndDate;

	var $localDateTime;
	var $localDateTime_t;
	var $localDate;
	var $localTime;
	var $localHi;
	
	var $localDateTimeArray = array();
	var $localYear;
	var $localMonth;
	var $localDay;
	var $localHour;
	var $localMin;
	var $localSec;

	var $numMonth;

	var $localNextDateTime;
	var $localNextDate;
	var $localPrevDateTime;
	var $localPrevDate;
	
	var $localPrevWeekDate;
	var $localNextWeekDate;
	var $localWeekFirstDate;
	var $localWeekEndDate;
	
	var $localWeekDayIndex;
	var $localWeekDay;
	var $localWeekNumber;
	var $localFirstDateInYear;

	var $localPrevMonthDate;
	var $localNextMonthDate;

	var $formattedWeekRange;
	var $formattedDate;

	var $rokuyo;
	var $holiday;
	var $formatedLocalDate;
	var $isDetail;
	var $localOnly;
	
	function CalendarDateTime($formatedDateTime = "", $isSetDetail = false, $base = CAL_BASE_LOCAL, $isTimeCuttingOff = false) {
        global $current_user;

        if (! is_null($current_user->week_start_day)) {
            $this->systemWeekFirstDay = $current_user->week_start_day;
        } else {
            $this->systemWeekFirstDay = AppConfig::setting('company.week_start_day', 0);
        }

		$this->Init($formatedDateTime, $isSetDetail, $base, $isTimeCuttingOff);
	}
	
	function Init($formatedDateTime, $isSetDetail = false, $base = CAL_BASE_LOCAL, $isTimeCuttingOff = false) {
		global $timedate;

		if($isSetDetail < 0) {
			$isSetDetail = false;
			$this->localOnly = true;
		} else
			$this->localOnly = false;
		$this->isDetail = $isSetDetail;		

		if(empty($formatedDateTime)) {
			$this->gmtDateTime = gmdate(CAL_DATETIME_FORMAT);
			$this->localDateTime = $timedate->handle_offset(gmdate(CAL_DATETIME_FORMAT), CAL_DATETIME_FORMAT, true);
			if($isTimeCuttingOff) {
				$this->localDateTime = date(CAL_DATE_FORMAT, strtotime($this->localDateTime)) . " 00:00:00";
				$this->gmtDateTime = $timedate->handle_offset($this->localDateTime, CAL_DATETIME_FORMAT, false);	
			}
		} else {
			if(preg_match("/^(\d{4})-(\d{2})-(\d{2})$/", $formatedDateTime) > 0) {
				$calDateTime = $formatedDateTime . " 00:00:00";
			} else {
				if($isTimeCuttingOff) {
					$calDateTime = date(CAL_DATE_FORMAT, strtotime($formatedDateTime)) . " 00:00:00";
				} else {
					$calDateTime = $formatedDateTime;
				}
			}

			if($base == CAL_BASE_LOCAL) {
				$this->localDateTime = $calDateTime;
				if(! $this->localOnly) {
                    //$this->gmtDateTime = $calDateTime;
					$this->gmtDateTime = $timedate->handle_offset($calDateTime, CAL_DATETIME_FORMAT, false);
                }
			} else {
				$this->localDateTime = $timedate->handle_offset($calDateTime, CAL_DATETIME_FORMAT, true);
				$this->gmtDateTime = $calDateTime;
			}
		}
		
		if(! $this->localOnly) {
            $this->gmtDateTime_t = strtotime($this->gmtDateTime);
            $this->gmtDateTimeArray = CalendarDateTime::dateTimetoArray($this->gmtDateTime);
            $this->gmtYear = $this->gmtDateTimeArray['Y'];
            $this->gmtMonth = $this->gmtDateTimeArray['m'];
            $this->gmtDay = $this->gmtDateTimeArray['d'];
            $this->gmtHour = $this->gmtDateTimeArray['H'];
            $this->gmtMin = $this->gmtDateTimeArray['i'];
            $this->gmtSec = $this->gmtDateTimeArray['s'];
            $this->gmtDate = "{$this->gmtYear}-{$this->gmtMonth}-{$this->gmtDay}";
            $this->gmtTime = "{$this->gmtHour}:{$this->gmtMin}:{$this->gmtSec}";
            $this->gmtHi = "{$this->gmtHour}:{$this->gmtMin}";
		}

		$this->localDateTime_t = strtotime($this->localDateTime);
		$this->localDateTimeArray = CalendarDateTime::dateTimetoArray($this->localDateTime);
		$this->localYear = $this->localDateTimeArray['Y'];
		$this->localMonth = $this->localDateTimeArray['m'];
		$this->numMonth = ltrim($this->localDateTimeArray['m'], 0);
		$this->localDay = $this->localDateTimeArray['d'];
		$this->localHour = $this->localDateTimeArray['H'];
		$this->localMin = $this->localDateTimeArray['i'];
		$this->localSec = $this->localDateTimeArray['s'];
		$this->localDate = "{$this->localYear}-{$this->localMonth}-{$this->localDay}";
		$this->localTime = "{$this->localHour}:{$this->localMin}:{$this->localSec}";
		$this->localHi = "{$this->localHour}:{$this->localMin}";
		if($this->isDetail) {
			$this->fillDetail();
		}
	}
	
	function fillDetail() {
		global $timedate;
		static $lang;
		if (! isset($lang)) {
			global $current_language;
			$lang = return_module_language($current_language, 'Calendar');
		}

		if(! $this->localOnly) {
            $gmtNextDay = strtotime("+1 day", $this->gmtDateTime_t);
            $gmtPrevDay = strtotime("-1 day", $this->gmtDateTime_t);
            $this->gmtNextDateTime = date(CAL_DATETIME_FORMAT, $gmtNextDay);
            $this->gmtPrevDateTime = date(CAL_DATETIME_FORMAT, $gmtPrevDay);
            $this->gmtNextDate = date(CAL_DATE_FORMAT, $gmtNextDay);
            $this->gmtPrevDate = date(CAL_DATE_FORMAT, $gmtPrevDay);
		}
		
		$nextDayTime = strtotime("+1 day", $this->localDateTime_t);
		$prevDayTime = strtotime("-1 day", $this->localDateTime_t);
		$this->localNextDateTime = date(CAL_DATETIME_FORMAT, $nextDayTime);
		$this->localPrevDateTime = date(CAL_DATETIME_FORMAT, $prevDayTime);
		$this->localNextDate = date(CAL_DATE_FORMAT, $nextDayTime);
		$this->localPrevDate = date(CAL_DATE_FORMAT, $prevDayTime);
		
		$this->fillWeekday();
		
		$this->localFirstDateInYear = "{$this->localYear}-01-01";

		if($this->localWeekDayIndex == $this->systemWeekFirstDay) {
			$weekStartTime = $this->localDateTime_t;
			$this->localWeekFirstDate = $this->localDate;
		} else {
			$diff = ($this->localWeekDayIndex + 7 - $this->systemWeekFirstDay) % 7;
			$weekStartTime = strtotime("-{$diff} day", $this->localDateTime_t);
			$this->localWeekFirstDate = date(CAL_DATE_FORMAT, $weekStartTime);
		}
		$weekEndTime = strtotime("+6 day", $weekStartTime);
		$this->localWeekEndDate = date(CAL_DATE_FORMAT, $weekEndTime);

		//$this->localWeekNumber = CalendarDateTime::getWeekNumber($this->localFirstDateInYear, $this->localDate);
		$this->localWeekNumber = date('W', strtotime("+3 day", $weekStartTime)); // middle of week
		
		$this->localPrevWeekDate = date(CAL_DATE_FORMAT, strtotime("-7 day", $this->localDateTime_t));
		$this->localNextWeekDate = date(CAL_DATE_FORMAT, strtotime("+7 day", $this->localDateTime_t));

		if(! $this->localOnly) {
		$this->gmtWeekFirstDateTime = $timedate->handle_offset($this->localWeekFirstDate . " 00:00:00", CAL_DATETIME_FORMAT, false);
		$this->gmtWeekFirstDate = date(CAL_DATE_FORMAT, strtotime($this->gmtWeekFirstDateTime));
		}

		$this->fillHoliday();

		$this->formatedLocalDate = date(CAL_DATE_FORMAT, $this->localDateTime_t);
										
		$this->localPrevMonthDate = date(CAL_DATE_FORMAT, strtotime("-1 month", $this->localDateTime_t));
		$this->localNextMonthDate = date(CAL_DATE_FORMAT, strtotime("+1 month", $this->localDateTime_t));

		$y1 = date('Y', $weekStartTime);
		$y2 = date('Y', $weekEndTime);
		if ($y1 != $y2) {
			$this->formattedWeekRange = custom_strftime($lang['LBL_FORMAT_WEEKRANGE_OVER_YEAR'] . ' - ', $weekStartTime, false) . custom_strftime($lang['LBL_FORMAT_WEEKRANGE_OVER_YEAR'], $weekEndTime, false);
		} else {
			$m1 = date('m', $weekStartTime);
			$m2 = date('m', $weekEndTime);
			if ($m1 != $m2) {
				$this->formattedWeekRange = custom_strftime($lang['LBL_FORMAT_WEEKRANGE_OVER_MONTH1'] . ' - ', $weekStartTime, false) . custom_strftime($lang['LBL_FORMAT_WEEKRANGE_OVER_MONTH2'], $weekEndTime, false);
			} else {
				$this->formattedWeekRange = custom_strftime($lang['LBL_FORMAT_WEEKRANGE1'] . ' - ', $weekStartTime, false). custom_strftime($lang['LBL_FORMAT_WEEKRANGE2'], $weekEndTime, false);
			}
		}
		$this->formattedDate = custom_strftime($lang['LBL_FORMAT_DATE'], $this->localDateTime_t, false);
		$this->formattedMonth = custom_strftime($lang['LBL_FORMAT_MONTH'], $this->localDateTime_t, false);
	}
	
	function fillWeekday() {
		global $app_list_strings;
		$this->localWeekDayIndex = date("w", $this->localDateTime_t);
		$this->localWeekDay = $app_list_strings['weekdays_dom'][$this->localWeekDayIndex];
	}
	
	function fillHoliday() {
		if($GLOBALS['current_language'] == 'ja')
			$this->rokuyo = get_rokuyou($this->localYear, $this->localMonth, $this->localDay);
		else
			$this->rokuyo = "";
		$this->holiday = holiday($this->localYear, $this->localMonth, $this->localDay);
	}
	
	function toArrayLocalDateTime() {
		return array(
			'localDateTime' => $this->localDateTime,
			'localDate' => $this->localDate,
			'localTime' => $this->localTime,
			'localHi' => $this->localHi,
			'localDateTimeArray' => $this->localDateTimeArray,
			'localYear' => $this->localYear,
			'localMonth' => $this->localMonth,
			'numMonth' => $this->numMonth,
			'localDay' => $this->localDay,
			'localHour' => $this->localHour,
			'localMin' => $this->localMin,
			'localSec' => $this->localSec,
			
			'localNextDateTime' => $this->localNextDateTime,
			'localPrevDateTime' => $this->localPrevDateTime,
			'localNextDate' => $this->localNextDate,
			'localPrevDate' => $this->localPrevDate,
			
    		'localWeekDayIndex' => $this->localWeekDayIndex,
    		'localWeekDay' => $this->localWeekDay,
    		
			'formatedLocalDate' => $this->formatedLocalDate,
			'formattedWeekRange' => $this->formattedWeekRange,
			'formattedDate' => $this->formattedDate,
			'formattedMonth' => $this->formattedMonth,
			'rokuyo' => $this->rokuyo,
			'holiday' => $this->holiday,
		);
	}
	
	function getNextDayDateTime($isSetDetail) {
		$nextDay = date('Y-m-d', strtotime('+1 Day', $this->localDateTime_t));
		return new CalendarDateTime($nextDay, $isSetDetail);	
	}
	function moveNextDayDateTime($isSetDetail) {
		$nextDay = date('Y-m-d', strtotime('+1 Day', $this->localDateTime_t));
		$this->Init($nextDay, $isSetDetail);
	}

	function getNextWeekDateTime($isSetDetail) {
		if($this->isDetail) {
			return new CalendarDateTime($this->localNextWeekDate, $isSetDetail);	
		} else {
			$dt = new CalendarDateTime($this->localDate, true);
			return new CalendarDateTime($dt->localNextWeekDate, $isSetDetail);
		}
	}
	
	function getNextMonthDateTime($isSetDetail) {
		//真ん中の日(適当)+1ヶ月
		$localNextMonthDate = date('Y-m-01', strtotime('+1 Month', strtotime("{$this->localYear}-{$this->localMonth}-15")));
		return new CalendarDateTime($localNextMonthDate, $isSetDetail);
	}
	
	function getNextYearDateTime($isSetDetail) {
		$nextYear = date('Y-m-d', strtotime('+1 Year', $this->localDateTime_t));
		return new CalendarDateTime($nextYear, $isSetDetail);	
	}

	function getPrevYearDateTime($isSetDetail) {
		$prevYear = date('Y-m-d', strtotime('-1 Year', $this->localDateTime_t));
		return new CalendarDateTime($prevYear, $isSetDetail);	
	}
	
	//static func
	function dateTimetoArray($formatedDateTime) {
		$result = array(
			'Y' => '',
			'm' => '',
			'd' => '',
			'H' => '',
			'i' => '',
			's' => '',
		);
		
		if(preg_match('/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/', $formatedDateTime, $matches) > 0) {
			$result['Y'] = $matches[1];
			$result['m'] = $matches[2];
			$result['d'] = $matches[3];
			$result['H'] = $matches[4];
			$result['i'] = $matches[5];
			$result['s'] = $matches[6];
		}
		
		return $result;
	}
	
	function getWeekNumber($from, $to, $offset = 0) {
	    $fromT = strtotime($from);
	    $toT   = strtotime($to);

	    if ($fromT > $toT) {
	        $t     = $fromT;
	        $fromT = $toT;
	        $toT   = $fromT;
	    }
	
	    $diffD = ceil(($toT - $fromT) / 86400);
	
	    $fromW = CalendarDateTime::adjustWeekNumber($fromT, $offset);
	    $toW   = CalendarDateTime::adjustWeekNumber($toT, $offset);
	
	    if ($diffD == 0) {
	        return 1;
	    } else if ($diffD < 7) {
	        return ($fromW > $toW) ? 2 : 1;
	    } else {
	        if ($fromW == 0 and $toW == 6) {
	            return $diffD / 7;
	        } else if (($fromW != 0 and $toW == 6) or ($fromW == 0 and $toW != 6)) {
	            return ceil(($diffD + 1) / 7);
	        } else {
	            return ceil(($diffD - (7 - $fromW) - ($toW + 1)) / 7) + 2;
	        }
	    }
	}
    
	function adjustWeekNumber($time, $offset = 0) {
	    $offset = ($offset < 0 or $offset > 6) ? 0 : $offset;
	    $week   = date("w", $time);
	    return ($week < $offset) ? (6 - $week) : ($week - $offset);
	}
	
	//static func
	function getFirstDayInMonth($localDate) {
		return date('Y-m-01', strtotime($localDate));
	}

	function getLastDayInMonth($localDate) {
		$middleDate = date('Y-m-15', strtotime($localDate));
		$nextMonthFirstDate = date('Y-m-01',  strtotime('+1 month', strtotime($middleDate)));
		return date('Y-m-d',  strtotime('-1 day', strtotime($nextMonthFirstDate)));
	}

	function userDateTimeFormats($reload=false) {
		static $formats;
		if(isset($formats) && ! $reload)
			return $formats;
		global $timedate;
		
		$datef = $timedate->get_date_format();
		if(strpos($datef, '/') !== false)
			$datesep = '/';
		elseif(strpos($datef, '.') !== false)
			$datesep = '.';
		else
			$datesep = '/'; // '-';
		if($datesep == '.')
			list($day, $month) = array('d', 'm');
		else
			list($day, $month) = array('j', 'n');
		if(preg_match("~[dj]{$datesep}[mn]~", $datef))
			$short = "{$day}{$datesep}{$month}";
		else
			$short = "{$month}{$datesep}{$day}";
		
		$timef = $timedate->get_time_format();
		$nozero = str_replace(array('H','h'), array('G', 'g'), $timef);
		if(preg_match('~([aA])$~', $nozero, $m)) {
			$merid = $m[1];
			$no_merid = substr($nozero, 0, -1);
			$hour = 'g';
		}
		else {
			$merid = '';
			$no_merid = $nozero;
			$hour = 'G';
		}
		
		$formats = array(
			'year_date' => $datef,
			'date_short' => $short,
			'long_date' => '',
			'time_full' => $timef,
			'time_no_zero' => $nozero,
			'meridiem' => $merid,
			'time_no_meridiem' => $no_merid,
			'time_hour' => $hour,
		);
		return $formats;
	}
}
?>
