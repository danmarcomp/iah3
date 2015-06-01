<?php

require_once('include/charts/BaseChart.php');

class ReportChartDashlet extends BaseChart {
	var $is_sidebar = false;
	var $modules = array('Reports');
	var $default_title = '';
	var $chart_type = '';
	var $dashletIcon = 'Reports';
	var $title = '';

	var $report_id = '';
	var $report;
	var $chart_body = '';
	var $ext_text = '';
	
	function set_report_id($id) {
		$report = ListQuery::quick_fetch('Report', $id);
		if($report && ! $report->failed)
			$this->set_report($report);
		return true;
	}
	
	function set_report(RowResult &$report) {
		$this->report = $report;
		$this->report_id = $report->getField('id');
		if(! $this->title)
			$this->title = $report->getField('name');
	}
	
	function process() {
		if(!$this->forceRefresh) return 'pending';

		require_once('modules/Reports/Report.php');
		if($this->report) {
			global $timedate;
			$mod = $this->report->getField('primary_module');
			$ext_text = '<a href="index.php?module='.$mod.'&layout=Reports&report_id='.$this->report_id.'" class="chartToolsLink">'.get_image("view_inline",'border="0" align="absmiddle" alt="'.translate('LBL_VIEW_REPORT', 'Dashboard').'"').'&nbsp;'.translate('LBL_VIEW_REPORT', 'Dashboard').'</a>';
			$t = '';
			
			$data = Report::get_last_data($this->report_id);
			if(! $data || ! $data->getField('chart_type')) {
				$t .= '<p align="center"><i>'.translate('LBL_NO_REPORT_DATA', 'Dashboard').'</i></p><br>';
			} else {
				if (!$this->layoutChanged) {
					require_once('modules/ReportData/Chart.php');
					ob_start();
					$ok = display_report_chart($data, 'center', false, $this->requestedWidth, $this->forceRefresh, 'dashlet');
					$content = ob_get_contents();
					ob_end_clean();
					if(! $ok)
						$content = '<p align="center"><i>'.translate('LBL_NO_REPORT_CHART', 'Dashboard').'</i></p><br>';
					$t .= $content;
				}
				$dt = $data->getField('date_entered');
				$ext_text = translate('LBL_CREATED_ON_REL', 'Charts') . '&nbsp'. $timedate->to_relative_date_time($dt) . ' &nbsp; ' . $ext_text;
			}
			$this->ext_text = $ext_text;
			$this->chart_body = $t;
		}
	}
	
	function display_chart() {
		return $this->chart_body;
	}
	
	function display() {
		return parent::display($this->ext_text);
	}
	
	function displayPending($text = '')
	{
		return parent::displayPending($this->ext_text);
	}
}

?>
