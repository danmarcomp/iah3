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

class ShowActivitiesButton extends FormButton {
	var $module;
	
	function init($params=null) {
		global $pageInstance;
		parent::init($params);
		$history = (int)array_get_default($params['params'], 'total_history');
		if (!$history)
			$this->vname = 'LBL_VIEW_SUMMARY';
		else
			$this->vname = 'LBL_VIEW_HISTORY';
		$this->vname_module = 'Activities';
		$this->icon = 'icon-changelog';
		$pmod = array_get_default($this->params, 'parent_module', '');
		$pid = array_get_default($this->params, 'parent_id', '');
		$this->perform = <<<JS
        var form_params = {module: 'Activities', action: 'ShowHistory', parent_type: '$pmod', parent_id: '$pid', total_history: $history};
        var popup = new SUGAR.ui.Dialog('activities', {modal: true, title : ''});
		popup.fetchContent('async.php', {postData : form_params}, null, true);
JS;
		$this->type = 'button';
	}
}
?>
