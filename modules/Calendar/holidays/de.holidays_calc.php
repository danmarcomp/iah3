<?php

	function calculate_holiday($year, $month, $day) {

		$weekday = date('w', mktime(0, 0, 0, $month, $day, $year));
		// +
		if ($month == 1 && $day == 1) {
			return "LBL_HDY_DE_NEUJAHR";
		} # Neujahrstag

		// +
		if ($month == 1 && $day == 6) {
			return "LBL_HDY_DE_DREIKOENIG";
		} # Heilige drei Kšnige

		// +
		if ($month == 2 && $day == 14) {
			return "LBL_HDY_DE_VALENTINE";
		} # Valentinstag

		// +
		if  (easter_relative($year, $month, $day, -7)) {
			return 'LBL_HDY_DE_PALMSONNTAG';
		} # Palmsonntag

		// +
		if  (easter_relative($year, $month, $day, -2)) {
			return 'LBL_HDY_DE_KARFREITAG';
		} # Karfreitag

		// +
		if  (easter_relative($year, $month, $day, 0)) {
			return 'LBL_HDY_DE_OSTERN';
		} # Ostern

		// +
		if  (easter_relative($year, $month, $day, 1)) {
			return 'LBL_HDY_DE_OSTERMONTAG';
		} # Ostermontag

		// +
		if ($month == 5 && $day == 1) {
			return "LBL_HDY_DE_TAGDERARBEIT";
		} # Tag der Arbeit

		// +
		if ($month == 5 && $day > 7 && $day <= 14 && $weekday == 0) {
			return "LBL_HDY_DE_MUTTERTAG";
		} # Muttertag

		// +
		if  (easter_relative($year, $month, $day, 39)) {
			return 'LBL_HDY_DE_AUFFAHRT';
		} # Christi Himmelfahrt

		// +
		if  (easter_relative($year, $month, $day, 49)) {
			return 'LBL_HDY_DE_PFINGSTEN';
		} # Pfingsten

		// +
		if  (easter_relative($year, $month, $day, 50)) {
			return 'LBL_HDY_DE_PFINGSTMONTAG';
		} # Pfingstmontag

		// +
		if  (easter_relative($year, $month, $day, 60)) {
			return 'LBL_HDY_DE_FRONLEICHNAM';
		} # Fronleichnam

		//+
		if ($month == 8 && $day == 15) {
			return "LBL_HDY_DE_MARIAHIMMELFAHRT";
		} # MariŠ Himmelfahrt

		// +
		if ($month == 10 && $day == 3) {
			return "LBL_HDY_DE_NATIONALFEIERTAG";
		} # Tag der Deutschen Einheit

		// +
		if ($month == 10 && $day == 31) {
			return "LBL_HDY_DE_REFORMATIONSTAG";
		} # Reformationstag

		//+
		if ($month == 11 && $day == 1) {
			return "LBL_HDY_DE_ALLERHEILIGEN";
		} # Allerheiligen

		//+
		if (advent4_relative($year, $month, $day, -35)) {
			return "LBL_HDY_DE_VOLKSTRAUERTAG";
		} # Volkstrauertag  Zweiter Sonntag vor dem ersten Advent

		// +
		if (advent4_relative($year, $month, $day, -32)) {
			return "LBL_HDY_DE_BUSSUNDBETTAG";
		} # Bu§- und Bettag  11 Tage vor dem ersten Advent

		// +
		if (advent4_relative($year, $month, $day, -28)) {
			return "LBL_HDY_DE_TOTENSONNTAG";
		} # Totensonntag  Letzter Sonntag vor dem 1. Advent

		//+
		if ($month == 12 && $day == 6) {
			return "LBL_HDY_DE_NIKOLAUS";
		} # St. Nikolaus

		// +
		if (advent4_relative($year, $month, $day, -21)) {
			return "LBL_HDY_DE_ADVENT1";
		} # Erster Advent Vierter Sonntag vor Weihnachten

		// +
		if (advent4_relative($year, $month, $day, -14)) {
			return "LBL_HDY_DE_ADVENT2";
		} # Zweiter Advent Dritter Sonntag vor Weihnachten

		// +
		if (advent4_relative($year, $month, $day, -7)) {
			return "LBL_HDY_DE_ADVENT3";
		} # Dritter Advent  Zweiter Sonntag vor Weihnachten

		// +
		if (advent4_relative($year, $month, $day, 0)) {
			return "LBL_HDY_DE_ADVENT4";
		} # Vierter Advent  Sonntag vor Weihnachten

		// +
		if ($month == 12 && $day == 24) {
			return "LBL_HDY_DE_HEILIGABEND";
		} # Heilig Abend

		// +
		if ($month == 12 && $day == 25) {
			return "LBL_HDY_DE_WEIHNACHTEN";
		} # Weihnachten

		// +
		if ($month == 12 && $day == 26) {
			return "LBL_HDY_DE_WEIHNACHT2";
		} # 2. Weihnachtsfeiertag

		// +
		if ($month == 12 && $day == 31) {
			return "LBL_HDY_DE_SILVESTER";
		} # Silvester
		
	}

	function easter_relative($year, $month, $day, $diff)
	{
		static $easter = array(
			1980 => array(4, 6),
			1981 => array(4, 26),
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
			2023 => array(4, 9),
			2024 => array(3, 31),
			2025 => array(4, 20),
			2026 => array(4, 5),
			2027 => array(3, 28),
			2028 => array(4, 16),
			2029 => array(4, 1),
			2030 => array(4, 21),
			2031 => array(4, 13),
			2032 => array(3, 28),
			2033 => array(4, 17),
			2034 => array(4, 9),
			2035 => array(3, 25),
			2036 => array(4, 13),
			2037 => array(4, 5),
			2038 => array(4, 25),
			2039 => array(4, 10),
			2040 => array(4, 1),
			2041 => array(4, 21),
			2042 => array(4, 6),
			2043 => array(3, 29),
			2044 => array(4, 17),
			2045 => array(4, 9),
			2046 => array(3, 25),
			2047 => array(4, 14),
			2048 => array(4, 5),
			2049 => array(4, 18),
			2050 => array(4, 10),

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


	function advent4_relative($year, $month, $day, $diff)
	{
		static $advent = array(
			1980 => array(12, 21),
			1981 => array(12, 20),
			1982 => array(12, 19),
			1983 => array(12, 18),
			1984 => array(12, 23),
			1985 => array(12, 22),
			1986 => array(12, 21),
			1987 => array(12, 20),
			1988 => array(12, 18),
			1989 => array(12, 24),
			1990 => array(12, 23),
			1991 => array(12, 22),
			1992 => array(12, 20),
			1993 => array(12, 19),
			1994 => array(12, 18),
			1995 => array(12, 24),
			1996 => array(12, 22),
			1997 => array(12, 21),
			1998 => array(12, 20),
			1999 => array(12, 19),
			2000 => array(12, 24),
			2001 => array(12, 23),
			2002 => array(12, 22),
			2003 => array(12, 21),
			2004 => array(12, 19),
			2005 => array(12, 18),
			2006 => array(12, 24),
			2007 => array(12, 23),
			2008 => array(12, 21),
			2009 => array(12, 20),
			2010 => array(12, 19),
			2011 => array(12, 18),
			2012 => array(12, 23),
			2013 => array(12, 22),
			2014 => array(12, 21),
			2015 => array(12, 20),
			2016 => array(12, 18),
			2017 => array(12, 24),
			2018 => array(12, 23),
			2019 => array(12, 22),
			2020 => array(12, 20),
			2021 => array(12, 19),
			2022 => array(12, 18),
			2023 => array(12, 24),
			2024 => array(12, 22),
			2025 => array(12, 21),
			2026 => array(12, 20),
			2027 => array(12, 19),
			2028 => array(12, 24),
			2029 => array(12, 23),
			2030 => array(12, 22),
			2031 => array(12, 21),
			2032 => array(12, 19),
			2033 => array(12, 18),
			2034 => array(12, 24),
			2035 => array(12, 23),
			2036 => array(12, 21),
			2037 => array(12, 20),
			2038 => array(12, 19),
			2039 => array(12, 18),
			2040 => array(12, 23),
			2041 => array(12, 22),
			2042 => array(12, 21),
			2043 => array(12, 20),
			2044 => array(12, 18),
			2045 => array(12, 24),
			2046 => array(12, 23),
			2047 => array(12, 22),
			2048 => array(12, 20),
			2049 => array(12, 19),
			2050 => array(12, 18),

		);

		$advent_time = mktime(0,0,0, $month, $day - $diff, $year);
		$advent_year = date('Y', $advent_time);
		$advent_month = date('m', $advent_time);
		$advent_day = date('d', $advent_time);

		if (!isset($advent[$advent_year])) {
			return false;
		}

		return $advent[$advent_year][0] == $advent_month && $advent[$advent_year][1] == $advent_day;
	}
	
