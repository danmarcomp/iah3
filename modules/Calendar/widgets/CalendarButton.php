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

class CalendarButton extends FormButton {

    var $date_field = 'date_start';
    var $date_value;
    var $view_mode;
    var $assigned_user;

	function init($params) {
		parent::init($params);
        $this->type = 'group';
        $this->icon = 'theme-icon module-Calendar';
        $this->align = 'right';
        if (! $this->vname) {
        	$this->vname = 'LBL_SHOW_ON_CALENDAR';
        	$this->vname_module = 'Calendar';
        }
        if (! empty($params['date_field']))
            $this->date_field = $params['date_field'];
	}
	
	function loadResult(RowResult &$result, array $context) {
        $this->date_value = $result->getField($this->date_field);
        $this->view_mode = $this->getCalViewMode($result->base_model);
        $this->assigned_user = $result->getField('assigned_user_id');
        if(! $this->date_value || ! $this->view_mode)
        	$this->hidden = true;
        else
        	$this->initGroupOptions();
	}
	
	function initGroupOptions() {
		global $timedate;
		$dt = $this->date_value;
		if(strpos($dt, ' ') !== false) {
			$dt = $timedate->handle_offset($dt, $timedate->get_db_date_time_format(), true);
		}
	
		$base_url = 'index.php?module=Calendar&target_date=' . $dt . '&view_mode=' . $this->view_mode;
		if($this->assigned_user)
			$base_url .= '&user_id=' . $this->assigned_user;
		$base_url .= '&view_type=';
		$this->addGroupOption('day', array(
			'icon' => 'theme-icon ticon-CalendarDay',
			'label' => translate('LBL_DAY', 'Calendar'),
			'url' => $base_url . 'day',
		));
		$this->addGroupOption('week', array(
			'icon' => 'theme-icon ticon-CalendarWeek',
			'label' => translate('LBL_WEEK', 'Calendar'),
			'url' => $base_url . 'week',
		));
		$this->addGroupOption('month', array(
			'icon' => 'theme-icon module-Calendar',
			'label' => translate('LBL_MONTH', 'Calendar'),
			'url' => $base_url . 'month',
		));
	}

    /**
     * Get Calendar view mode by model
     *
     * @param string $model_name
     * @return string
     */
    function getCalViewMode($model_name) {
        switch ($model_name) {
            case "Project":
            case "ProjectTask":
                $view_mode = 'projects';
                break;
            case "BookedHours":
                $view_mode = 'timesheets';
                break;
            default:
                $view_mode = 'activities';
                break;
        }

        return $view_mode;
    }
}
?>