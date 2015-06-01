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

require_once("modules/Calendar/CalendarViewer/CalendarViewer.php");

class YearCalendarViewer extends CalendarViewer {
	var $viewType = "year";
	var $templatePath = "modules/Calendar/YearCalendarView.tpl";
	var $calendarCtrl = "YearCalendarCtrl";

	function DayCalendarViewer(&$params) {
		parent::CalendarViewer($params);
	}

	function assignFields() {
		global $current_user, $app_list_strings;
	
		parent::assignFields();
		$this->assignMode();
		
		$targetDate = "";
		if(isset($this->params['target_date'])) {
			$targetDate = $this->params['target_date'];
		}
		$calDateTime = new CalendarDateTime($targetDate, true, CAL_BASE_LOCAL, true);
		
		$targetType= "user";
		if(isset($this->params['target_type']) && !empty($this->params['target_type'])) {
			$targetType = $this->params['target_type'];
		}
		$this->tpl->assign('target_type', $targetType);
		
		$targetId = $current_user->id;		
		if(isset($this->params['target_id']) && !empty($this->params['target_id'])) {
			$targetId = $this->params['target_id'];
		}
		
        $this->assignModeData($targetId);
		
		$this->tpl->assign('target_date', $calDateTime->localDate);
		$this->tpl->assign('target_year', $calDateTime->localYear);
		$this->tpl->assign('target_month', $calDateTime->localMonth);
		$this->tpl->assign('target_day', $calDateTime->localDay);
		
		$this->tpl->assign('target_weekday', $calDateTime->localWeekDay);
		$this->tpl->assign('target_weekday_index', $calDateTime->localWeekDayIndex);
		$this->tpl->assign('target_rokuyo', $calDateTime->rokuyo);
		$this->tpl->assign('target_holiday', $calDateTime->holiday);

		$this->tpl->assign('weekdayfonts', CalendarDataUtil::getWeekDayFontCss());

		$nextYearDT = $calDateTime->getNextYearDateTime(false);
		$prevYearDT = $calDateTime->getPrevYearDateTime(false);
		$this->tpl->assign('next_date', $nextYearDT->localDate);
		$this->tpl->assign('prev_date', $prevYearDT->localDate);
		$this->tpl->assign('to_day', CalendarDataUtil::getLocalToDay());
		
		list ($country, $lang) = get_holidays_params();
		$holidays_mtime = get_holidays_mtime();
		
		$js_lang_version = AppConfig::language_version();
		$cacheDir = CacheManager::get_location('modules/') . "Calendar";
		$cacheFilePath = $cacheDir."/YEARCAL_{$calDateTime->localYear}_{$country}_{$lang}.dat";
		$isUseCache = false;
		 
		if(file_exists($cacheFilePath) && ($mtime = filemtime($cacheFilePath)) !== false && $mtime >= $holidays_mtime) {
			$content = file_get_contents($cacheFilePath);
			if($content !== false) {
				$dayContentEveryMon = unserialize(base64_decode($content));
			}
			if (@$dayContentEveryMon['language_version'] == $js_lang_version) {
				$isUseCache = true;
			}
		}
		
		if(!$isUseCache) {
			$dayContentEveryMon = array();
			for($i = 1; $i <=12; $i++) {
				$month_formatted = $app_list_strings['months_long_dom'][$i];
				$d = sprintf("%d-%d-15", $calDateTime->localYear, $i);
				$firstDate = CalendarDateTime::getFirstDayInMonth($d); 
				$lastDate = CalendarDateTime::getLastDayInMonth($d);
				$lastDate_t = strtotime($lastDate);
	
				$targetDT = new CalendarDateTime($firstDate, -1);
				$dayContents = array();
				do {
					$targetDT->fillWeekday();
					$targetDT->fillHoliday();
					$shortDay = preg_replace("/^0/", "", $targetDT->localDay);
	
					$dayContents[$shortDay] = array(
						'date' => $targetDT->localDate,
						'month' => $targetDT->localMonth,
						'weekdayIndex' => date("w", $targetDT->localDateTime_t),
						'weekday' => $targetDT->localWeekDay,
						'holiday' => $targetDT->holiday,
					);
					$targetDT->moveNextDayDateTime(-1);
				} while($targetDT->localDateTime_t <= $lastDate_t);
				$dayContentEveryMon[] = array(
					'month' => $i,
					'month_formatted' => $month_formatted,
					'dayContents' => $dayContents
				);
			}
			if(!file_exists($cacheDir)) {
				mkdir($cacheDir, 0777, true);
			}

			$dayContentEveryMon['country'] = $country;
			$dayContentEveryMon['language'] = $lang;
			$dayContentEveryMon['language_version'] = $js_lang_version;
			file_put_contents($cacheFilePath, base64_encode(serialize($dayContentEveryMon)));
		}
		
		unset($dayContentEveryMon['country']);	
		unset($dayContentEveryMon['language']);
		unset($dayContentEveryMon['language_version']);
		$this->tpl->assign('month1to6', array_slice($dayContentEveryMon, 0, 6));
		$this->tpl->assign('month7to12', array_slice($dayContentEveryMon, 6));
	}
}
