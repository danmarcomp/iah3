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


require_once('include/layout/forms/FormTableSection.php');
require_once('modules/UserPreferences/UserPreference.php');

class GoogleSyncStatusWidget extends FormTableSection {

	var $sync_error;

	function init($params, $model=null) {
		parent::init($params, $model);		
	}
	
	function loadResult(RowResult &$row_result, array $context) {
		if(AppConfig::current_user_id() && UserPreference::getPreference('google_calendar_user', 'global')) {
			$lq = new ListQuery('google_sync');
			$lq->addSimpleFilter('related_type', 'Contacts');
			$lq->addSimpleFilter('related_id', $row_result->getField('id'));
			$lq->addSimpleFilter('user_id', AppConfig::current_user_id());
			$ret = $lq->runQuerySingle();
			if ($ret)
				$this->sync_error = $ret->getField('google_sync_error');
			else
				$this->sync_error = false;
		}
		if(! $this->sync_error)
			$this->hidden = true;
	}
	
	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, $context) {
		if ($gen->layout->getEditingLayout()) {
			$params = array();
			$params['body'] =  '<tr><td>' . translate('LBL_GOOGLE_SYNC_DESCRIPTION', 'Contacts')  . '</td></tr>';
			return $this->renderOuterTable($gen, $parents, $context, $params);
		}
		$params = array();
		$icon = get_image('include/images/iah/warning', 'align="absmiddle"');
		$params['body'] =  '<tr><td style="padding: 4px"><div style="max-width: 600px; text-align: justify">' . $icon . '&nbsp;' .translate('LBL_GOOGLE_SYNC_ERROR_DESC', 'Contacts') . '</div></td></tr>';
		return $this->renderOuterTable($gen, $parents, $context, $params);
    }
	
	function getRequiredFields() {
		return array('id');
	}

	function validateInput(RowUpdate &$update) {
		return true;
	}
}

