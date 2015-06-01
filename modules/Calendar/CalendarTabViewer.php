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
require_once("include/Sugar_Smarty.php");

class CalendarTabViewer {
	var $viewType = 'day';
	var $targetDate = '';
	var $targetUserId = '';
	var $targetTeamId = '';	
	var $forDashlet = false;
	var $tabGroups = array();
	
	function CalendarTabViewer($viewType, $targetDate = "", $targetUserId = "", $targetTeamId = "", $forDashlet=false) {
		$this->viewType = $viewType;
		$this->targetDate = $targetDate;
		$this->targetUserId = $targetUserId;
		$this->targetTeamId = $targetTeamId;
		$this->forDashlet = $forDashlet;

		$this->tabGroups = array(
			1 => array(
				'day' => array(
					'icon' => 'theme-icon ticon-CalendarDay',
					'label' => 'LBL_DAY',
					'perform' => "DayCalendarCtrl.asyncCalendarBody('{$targetDate}');",
				),
				'week' => array(
					'icon' => 'theme-icon ticon-CalendarWeek',
					'label' => 'LBL_WEEK',
					'perform' => "WeekCalendarCtrl.asyncCalendarBody('{$targetDate}');",
				),
				'month' => array(
					'icon' => 'theme-icon module-Calendar',
					'label' => 'LBL_MONTH',
					'perform' => "MonthCalendarCtrl.asyncCalendarBody('{$targetDate}');",
				),
				'year' => array(
					'icon' => 'theme-icon module-Calendar',
					'label' => 'LBL_YEAR',
					'perform' => "YearCalendarCtrl.asyncCalendarBody('{$targetDate}');",
				),
			),
		);
		if(! $this->forDashlet) {
			$this->tabGroups[2] = array(
				'resource_day' => array(
					'icon' => 'theme-icon ticon-CalendarDay',
					'label' => 'LBL_RESOURCE_DAY',
					'perform' => "ResourceDayCalendarCtrl.asyncCalendarBody('{$targetDate}');",					
				),
				'resource_week' => array(
					'icon' => 'theme-icon ticon-CalendarWeek',
					'label' => 'LBL_RESOURCE_WEEK',
					'perform' => "ResourceWeekCalendarCtrl.asyncCalendarBody('{$targetDate}');",
				),
			);
		}
	}
	
	function view() {
		$allTabs = array();
		$gidx = null;
		foreach($this->tabGroups as $idx => $tabs) {
			foreach($tabs as $viewType => $tab) {
				if($gidx != $idx) {
					if(isset($gidx))
						$allTabs[] = array('name' => 'groupsep'.$idx, 'separator' => true);
					$gidx = $idx;
				}
				$tab['name'] = $viewType;
				$tab['label'] = translate($tab['label'], 'Calendar');
				$allTabs[] = $tab;
			}
		}
		
		require_once('include/layout/forms/EditableForm.php');
		$frm = new EditableForm('tabgroup', 'form_calendar', 'form_calendar');
		$spec = array(
			'name' => 'view_type_sel',
			'tabs' => $allTabs,
		);
		$body = $frm->renderTabGroup($spec, $this->viewType);
		$ret = $body; //$frm->open() . $body . $frm->close();
		
		// FIXME - need to manage form object at a higher level
		$frm->exportIncludes();

		return $ret;
	}
	
	function execute() {
		return $this->view();
	}
}
