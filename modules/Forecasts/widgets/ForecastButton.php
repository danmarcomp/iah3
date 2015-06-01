<?php
/*
 *
 * The contents of this file are subject to the info@hand Software License Agreement Version 1.3
 *
 * ("License"); You may not use this file except in compliance with the License.
 * You may obtain a copy of the License at <http://1crm.com/pdf/swlicense.pdf>.
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the
 * specific language governing rights and limitations under the License,
 *
 * All copies of the Covered Code must include on each user interface screen:
 * (i) the 1CRM copyright notice,
 * (ii) the "Powered by the 1CRM Engine" logo, 
 *
 * (iii) the "Powered by SugarCRM" logo, and
 * (iv) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.
 * See full license for requirements.
 *
 * The Original Code is : 1CRM Engine proprietary commercial code.
 * The Initial Developer of this Original Code is 1CRM Corp.
 * and it is Copyright (C) 2004-2012 by 1CRM Corp.
 *
 * All Rights Reserved.
 * Portions created by SugarCRM are Copyright (C) 2004-2008 SugarCRM, Inc.;
 * All Rights Reserved.
 *
 */


require_once('include/layout/forms/FormButton.php');
require_once('modules/Forecasts/Forecast.php');

class ForecastButton extends FormButton {

    var $date_field = 'date_closed';
    var $date_value;

	function init($params) {
		parent::init($params);
        $this->icon = 'theme-icon module-Forecasts';
        $this->align = 'right';
        $this->type = 'group';
        if(! $this->vname) {
        	$this->vname = 'LBL_SHOW_FORECAST';
        	$this->vname_module = 'Forecasts';
        }
        if (! empty($params['date_field']))
            $this->date_field = $params['date_field'];
	}
	
	function loadResult(RowResult &$row_result, array $context) {
        $this->date_value = $row_result->getField($this->date_field);
        $user = $row_result->getField('assigned_user_id');
        if(! $this->date_value || $user != AppConfig::current_user_id())
        	$this->hidden = $this->hidden_flag = true;
        else {
        	$info = Forecast::getPeriodInfo($this->date_value);
        	if(! Forecast::retrieve_by_period($user, $info['id']))
        		$this->hidden = $this->hidden_flag = true;
			else
				$this->initGroupOptions();		
		}
	}
	
	function initGroupOptions() {
		$dom = $GLOBALS['app_list_strings']['forecast_type_dom'];
		foreach(array('personal', 'team_individual', 'team_rollup') as $f)
			$this->addGroupOption($f, array(
				'icon' => $this->icon,
				'label' => $dom[$f],
				'url' => '?module=Forecasts&action=WorksheetView&type=' . $f . '&period_start=' . $this->date_value,
			));
	}
}
?>