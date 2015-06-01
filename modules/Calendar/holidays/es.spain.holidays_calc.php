<?php

	function calculate_holiday($year, $month, $day) {
		$weekday = date('w', mktime(0, 0, 0, $month, $day, $year));
    // Especiales 2009
		if ($year==2009 && $month == 11 && $day == 2) {return "LBL_HDY2009_TODOSLOSSANTOS";
    }
  	if ($year==2009 && $month == 12 && $day == 7) {return "LBL_HDY2009_CONSTITUCION";
		}
	
		// Enero
		if ($month == 1 && $day == 1) {return "LBL_HDY_NEWYEAR";
		}
		if ($month == 1 && $day == 6) {return "LBL_HDY_REYES";
		}

    // Febrero
    if ($month == 2 && $day == 28) {return "LBL_HDY_AND";
		}

		// Marzo
		if ($month == 3 && $day == 19) {return "LBL_HDY_SANJOSE";
		}
		
		// Abril
		if ($month == 4 && $day == 9) {return "LBL_HDY_JUEVESSANTO";
		}
		if ($month == 4 && $day == 10) {return "LBL_HDY_VIERNESSANTO";
		}
		if  (easter_relative($year, $month, $day, +1)) {return 'LBL_HDY_LUNESPASCUA';
		}
		if ($month == 4 && $day == 23) {return "LBL_HDY_CASTL";
		}
		if ($month == 4 && $day == 23) {return "LBL_HDY_ARA";
		}
		
		// Mayo
		if ($month == 5 && $day == 1) {return "LBL_HDY_DIATRABAJO";
		}
		if ($month == 5 && $day == 2) {return "LBL_HDY_MAD";
		}
		if ($month == 5 && $day == 30) {return "LBL_HDY_CAN";
		}
		
		// Junio
		if ($month == 6 && $day == 1) {return "LBL_HDY_CASTM";
		}
		if ($month == 6 && $day == 9) {return "LBL_HDY_MUR";
		}
		if ($month == 6 && $day == 9) {return "LBL_HDY_RIOJ";
		}
		if ($month == 6 && $day == 11) {return "LBL_HDY_CORPUSCHRISTI";
		}
		if ($month == 6 && $day == 24) {return "LBL_HDY_SANTIAGO";
		}
		
		// Julio
		if ($month == 7 && $day == 25) {return "LBL_HDY_SANTIAGO";
		}
		if ($month == 7 && $day == 28) {return "LBL_HDY_CANT_1";
		}
		
		// Agosto
		if ($month == 8 && $day == 15) {return "LBL_HDY_VIRGEN";
		}
		
		// Septiembre
		if ($month == 9 && $day == 2) {return "LBL_HDY_CEU";
		}
		if ($month == 9 && $day == 8) {return "LBL_HDY_AST";
		}
		if ($month == 9 && $day == 8) {return "LBL_HDY_EXT";
		}
		if ($month == 9 && $day == 11) {return "LBL_HDY_CAT";
		}
		if ($month == 9 && $day == 15) {return "LBL_HDY_CANT_2";
		}
		
		// Octubre
    if ($month == 10 && $day == 9) {return "LBL_HDY_VAL";
		}
		if ($month == 10 && $day == 12) {return "LBL_HDY_FIESTANACIONAL";
		}
		
		// Noviembre
		if ($month == 11 && $day == 1) {return "LBL_HDY_TODOSLOSSANTOS";
		}
		
		// Diciembre
		if ($month == 12 && $day == 6) {return "LBL_HDY_CONSTITUCION";
		}
		if ($month == 12 && $day == 8) {return "LBL_HDY_INMACULADA";
		}
    if ($month == 12 && $day == 25) {return "LBL_HDY_NAVIDAD";
		}
		if ($month == 12 && $day == 26) {return "LBL_HDY_SANESTEBAN";
		}
		
		// +
		//if  (easter_relative($year, $month, $day, +39)) {
		//	return 'LBL_HDY_JEUDI_ASCENSION';
		//}
		//if  (easter_relative($year, $month, $day, +50)) {
		//	return 'LBL_HDY_LUNDI_PENTECOTE';
		//}
		//if ($month == 2 && $day > 14 && $day <= 21 && $weekday == 1) {
		//	return "LBL_HDY_FAMILY";
		//}
		//if ($month == 5 && $day > 17 && $day <= 24 && $weekday == 1) {
	  //	return "LBL_HDY_VICTORIA";
		//}
		//if ($month == 8 && $day > 0 && $day <= 7 && $weekday == 1) {
		//	return "LBL_HDY_AUGUST_CIVIC";
		//}
		//if ($month == 10 && $day > 7 && $day <= 14 && $weekday == 1) {
		//	return "LBL_HDY_THANKSGIVING";
		//}
		//if ($month == 12 && $day == 26) {
		//	return "LBL_HDY_BOXING";
		//}
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
	
