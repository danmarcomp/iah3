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


require_once('include/layout/forms/FormField.php');

class AcceptStatusWidget extends FormField {

	function getRequiredFields() {
		$req = parent::getRequiredFields();
		if($this->model->getModelName() == 'Invitees') {
			$req[] = 'id';
			$req[] = 'query_module';
			$req[] = 'accept_status';
		}
		return $req;
	}
	
	function renderListCell(ListFormatter $fmt, ListResult &$list_result, $row_id) {
		global $app_list_strings;
		$dom = $app_list_strings['dom_meeting_accept_status'];
        $row = $list_result->getRowResult($row_id);
        $model = $row->base_model;
        $id = $row->getField($row->primary_key);
        
        if($model == 'Invitees') {
        	$model = $fmt->getParentModel();
			$id = $fmt->getParentKey();
			$uid = $row->getField('id');
			$status = $row->getField('accept_status');
			$dstatus = $row->getField('accept_status', null, true);
		}
		else {
			if ($model == 'Meeting') {
				$table_prefix = 'meetings';
				$key = 'meeting_id';
			} else if($model == 'Call') {
				$table_prefix = 'calls';
				$key = 'call_id';
			} else
				return '';
	
			$lq = new ListQuery($table_prefix . '_users');

			$clauses = array(
				"user" => array(
					"value" => AppConfig::current_user_id(),
					"field" => "user_id",
				),
				"main_entry" => array(
					"value" => $id,
					"field" => $key,
				),
			);

			$lq->addFilterClauses($clauses);
			$result = $lq->runQuerySingle();

			if (! $result->failed) {
				$status = $result->getField('accept_status');
				$dstatus = array_get_default($dom, $status, $status);
				$uid = $result->getField('user_id');
			} else
				$uid = $status = $dstatus = '';		
		}
		
		if ($uid == AppConfig::current_user_id() && (! $status || $status == 'none')) {
			$value = "<div id=\"accept".$this->id."\" style=\"white-space: nowrap\"><a title=\"".
				$dom['accept'].
				"\" href=\"javascript:sListView.setInviteStatus('{$fmt->list_id}', '{$model}', '{$id}', 'accept');\">".
				get_image("accept_inline",'border="0"'). "</a>&nbsp;<a title=\"".$dom['tentative'].
				"\" href=\"javascript:sListView.setInviteStatus('{$fmt->list_id}', '{$model}', '{$id}', 'tentative');\">".
				get_image("tentative_inline",'border="0"').
				"</a>&nbsp;<a title=\"".$dom['decline'].
				"\" href=\"javascript:sListView.setInviteStatus('{$fmt->list_id}', '{$model}', '{$id}', 'decline');\">".
				get_image("decline_inline",'border="0"')."</a></div>";
		} else {
			$value = $dstatus;
		}

        return $value;
	}
}
?>