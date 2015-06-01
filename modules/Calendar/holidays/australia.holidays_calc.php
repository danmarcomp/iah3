<?php

	function calculate_holiday($year, $month, $day) {
	  $weekday = date('w', mktime(0, 0, 0, (int)$month, (int)$day, (int)$year));
	  if ($month == 1) {
		if ($day == 1) {
			// New year's day
			return "LBL_HDY_NEWYEAR";
		} elseif ($day == 26) {
			// National day
			return "LBL_HDY_AUSTRALIA_DAY";
		}
	  } else if (($month == date('n', easter_date($year))) && ($day == date('j', easter_date($year)))) {
		// Easter
	  	return "LBL_HDY_EASTER_SUNDAY";
	  } else if (($month == date('n', easter_date($year) - (2 * 24 * 60 * 60))) && ($day == date('j', easter_date($year) - (2 * 24 * 60 * 60)))) {
		// Good Friday
	  	return "LBL_HDY_GOOD_FRIDAY";
	  } else if (($month == date('n', easter_date($year) + (24 * 60 * 60))) && ($day == date('j', easter_date($year) + (24 * 60 * 60)))) {
		// Easter Monday
	  	return "LBL_HDY_EASTER_MONDAY";
	  } elseif ($month == 3) {
		if (($day <= 7) && ($weekday == 1)) {
			// New year's day
			return "LBL_HDY_LABOUR_DAY_WA";
		} elseif (($day <= 14) && ($weekday == 1)) {
			// National day
			return "LBL_HDY_LABOUR_DAY_TAS_VIC";
		}
	  } elseif (($month == 4) && ($day == 25)) {
		// National day
		return "LBL_HDY_ANZAC_DAY";
	  } elseif ($month == 5) {
		if (($day <= 7) && ($weekday == 1)) {
			// New year's day
			return "LBL_HDY_LABOUR_DAY_QLD";
		}
	  } elseif ($month == 6) {
		if (($day > 7) && ($day <= 14) && ($weekday == 1)) {
			// New year's day
			return "LBL_HDY_QUEENS_BIRTHDAY";
		}
	  } elseif ($month == 10) {
		if (($day <= 7) && ($weekday == 1)) {
			// New year's day
			return "LBL_HDY_LABOUR_DAY_ACT_NSW_SA";
		}
	  } elseif ($month == 12) {
		if ($day == 25) {
			// Christmas
			return "LBL_HDY_CHRISTMAS";
		} elseif ($day == 25) {
			// Boxing day
			return "LBL_HDY_BOXING";
		}
	  }
	  
	  
	  return '';
	}

