<?php

	function calculate_holiday($year, $month, $day) {

		$weekday = date('w', mktime(0, 0, 0, $month, $day, $year));
		// +
		if ($month == 1 && $day == 1) {
			return "LBL_HDY_NEWYEAR";
		}

		// +
		if ($month == 2 && $day == 25) {
			return "LBL_HDY_EDSA_DAY";
		}

		// +
		if ($month == 4 && $day == 9) {
			return "LBL_HDY_VALOR_DAY";
		}		
		
		// +
		if ($month == 5 && $day == 1) {
			return "LBL_HDY_LABOR_DAY";
		}

		// +
		if ($month == 6 && $day == 12) {
			return "LBL_HDY_INDEPENDENCE_DAY";
		}		

		// +
		if ($month == 8 && $day == 21) {
			return "LBL_HDY_NINOY_DAY";
		}		

		// +
		if ($month == 11 && $day == 1) {
			return "LBL_HDY_SAINTS_DAY";
		}

		// +
		if ($month == 11 && $day == 30) {
			return "LBL_HDY_BONIFACIO_DAY";
		}		
		
		// +
		if ($month == 12 && $day == 25) {
			return "LBL_HDY_CHRISTMAS";
		}

		// +
		if ($month == 12 && $day == 30) {
			return "LBL_HDY_RIZAL_DAY";
		}

		// +
		if ($month == 12 && $day == 31) {
			return "LBL_HDY_NEW_YEAR";
		}			

		// +
		if  (easter_relative($year, $month, $day, -3)) {
			return 'LBL_HDY_MAUNDY_THURSDAY';
		}

		// +
		if  (easter_relative($year, $month, $day, -2)) {
			return 'LBL_HDY_GOOD_FRIDAY';
		}
	}

	function easter_relative($year, $month, $day, $diff)
	{
		static $easter = array(
			1982 => array(4, 11),
			1983 => array(4, 3),
			1984 => array(4, 22),
			1985 => array(4, 7),
			1986 => array(3, 30),
			1987 => array(4, 19),
			1988 => array(4, 3),
			1989 => array(3, 26),
			1990 => array(4, 15),
			1991 => array(3, 31),
			1992 => array(4, 19),
			1993 => array(4, 11),
			1994 => array(4, 3),
			1995 => array(4, 16),
			1996 => array(4, 7),
			1997 => array(3, 30),
			1998 => array(4, 12),
			1999 => array(4, 4),
			2000 => array(4, 23),
			2001 => array(4, 15),
			2002 => array(3, 31),
			2003 => array(4, 20),
			2004 => array(4, 11),
			2005 => array(3, 27),
			2006 => array(4, 16),
			2007 => array(4, 8),
			2008 => array(3, 23),
			2009 => array(4, 12),
			2010 => array(4, 4),
			2011 => array(4, 24),
			2012 => array(4, 8),
			2013 => array(3, 31),
			2014 => array(4, 20),
			2015 => array(4, 5),
			2016 => array(3, 27),
			2017 => array(4, 16),
			2018 => array(4, 1),
			2019 => array(4, 21),
			2020 => array(4, 12),
			2021 => array(4, 4),
			2022 => array(4, 17),
		);

		$easter_time = mktime(0,0,0, $month, $day - $diff, $year);
		$easter_year = date('Y', $easter_time);
		$easter_month = date('m', $easter_time);
		$easter_day = date('d', $easter_time);

		if (!isset($easter[$easter_year])) {
			return false;
		}

		return $easter[$easter_year][0] == $easter_month && $easter[$easter_year][1] == $easter_day;
	}
	
