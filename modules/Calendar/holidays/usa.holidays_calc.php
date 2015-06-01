<?php


	function calculate_holiday($year, $month, $day) {
	  $weekday = date('w', mktime(0, 0, 0, (int)$month, (int)$day, (int)$year));
	  if ($month == 1) {
		if ($day == 1) {
		  return "LBL_HDY_NEWYEAR";
		}
		if (($day >= 15 && $day <= 21) && $weekday == '1') {
		  return 'LBL_HDY_MLK';
		}
	  } else if ($month == 2) {
		if (($day >= 15 && $day <= 21) && $weekday == '1') {
		  return "LBL_HDY_WASHINGTONS";
		}
	  } else if ($month == 3) {
	  } else if ($month == 4) {
	  } else if ($month == 5) {
		if ($day >= 25 && $weekday == '1') {
		  return 'LBL_HDY_MEMORIAL';
		}
	  } else if ($month == 6) {
	  } else if ($month == 7) {
		if ($day == 4) {
		  return 'LBL_HDY_INDEPENDENCE';
		}
	  } else if ($month == 8) {
	  } else if ($month == 9) {
		if (($day >= 1 && $day <= 7) && $weekday == '1') {
		  return 'LBL_HDY_LABOR';
		}
	  } else if ($month == 10) {
		if (($day >= 8 && $day <= 14) && $weekday == '1') {
		  return 'LBL_HDY_COLUMBUS';
		}
	  } else if ($month == 11) {
		if ($day == 11) {
		  return 'LBL_HDY_VETERANS';
		}
		if (($day >= 22 && $day <= 28) && $weekday == '4') {
		  return 'LBL_HDY_THANKSGIVING';
		}
	  } else if ($month == 12) {
		if ($day == 25) {
		  return 'LBL_HDY_CHRISTMAS';
		}
	  }
	  return '';
	}
