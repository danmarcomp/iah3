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
require_once('modules/Meetings/ActivityInvite.php');

class SchedulerWidget extends FormTableSection {

	var $model_name;

	function init($params, $model=null) {
		parent::init($params, $model);
		if(! $this->id)
			$this->id = 'event_scheduler';
		if($model) $this->model_name = $model->name;
	}
	
	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, array $context) {
		if($gen->getLayout()->getEditingLayout()) {
			$params = array();
			return $this->renderOuterTable($gen, $parents, $context, $params);		
		}

		$lstyle = $gen->getLayout()->getType();
		if($lstyle == 'editview') {
			return $this->renderHtmlEdit($gen, $row_result);
		}
		return $this->renderHtmlView($gen, $row_result);
	}
	
	function getRequiredFields() {
		return array();
	}
	
	function renderHtmlView(HtmlFormGenerator &$gen, RowResult &$row_result) {
        return "";
	}

	function renderHtmlEdit(HtmlFormGenerator &$gen, RowResult &$row_result, $dialog = false) {
        global $mod_strings;

        $json = getJSONobj();
        $form_name = $gen->getFormName();

		$user_request_data = $this->getPopupRequestData($form_name);
		$add_user_label = translate('LBL_ADD_USER_BUTTON', 'Meetings');
		$contact_request_data = $this->getPopupRequestData($form_name);
		$add_contact_label = translate('LBL_ADD_CONTACT_BUTTON', 'Meetings');

		if ($this->model_name == 'Meeting') {
			$resource_request_data = $this->getPopupRequestData($form_name);
            $add_resource = <<<EOQ
            	<button name="addResource" type="button" class="input-button input-outer"
            	onclick='open_popup("Resources", 600, 400, "&hide_clear_button=true", true, false, {$resource_request_data});'><div class="input-icon icon-add left"></div><span class="input-label">{$mod_strings['LBL_ADD_RESOURCE_BUTTON']}</span></button>
EOQ;
		} else {
			$add_resource = '';
        }

        $send_check_spec = array('name' => 'send_invites');
        $send_default = AppConfig::setting('notify.send_invites_by_default');
        $send_checked = array_get_default($_REQUEST, 'send_invites', $send_default && ! $row_result->getField('id'));
        $send_check = $gen->form_obj->renderCheck($send_check_spec, $send_checked);
        $send_check_lbl = to_html(translate('LBL_SEND_INVITATIONS', 'Meetings'));

        if ($dialog) {
        	$outerCls = 'schedulerDialog';
            $button_block_style = 'style="padding: 0 0 5px 0;"';
            $titleHeader = '';
            $send_check_row = '<tr><td>' .$send_check. '&nbsp;<span class="input-label">'.$send_check_lbl.'</span></td></tr>';
        } else {
        	$outerCls = 'schedulerForm';
            $button_block_style = 'class="button-bar form-mid opaque"';
            $title = $send_check . '&nbsp;<span class="input-label" style="font-weight:bold;">'.$send_check_lbl.'</span>';
            $titleHeader = str_replace("</p><p>", "", get_form_header($title, "", false));
            $send_check_row = '';
        }

        $body = <<<EOQ
        	<div class="$outerCls">
            <input type="hidden" name="user_invitees" value="" />
            <input type="hidden" name="contact_invitees" value="" />
            <input type="hidden" name="resource_invitees" value="" />
            {$titleHeader}
            <div {$button_block_style}>
			<table width="100%" cellpadding="0" cellspacing="0" border="0">
			{$send_check_row}
			<tr><td>
				<button name="addUser" type="button" class="input-button input-outer"
				onclick='open_popup("Users", 600, 400, "&hide_clear_button=true", true, false, {$user_request_data});'><div class="input-icon icon-add left"></div><span class="input-label">{$add_user_label}</span></button>
				<button name="addContact" type="button" class="input-button input-outer"
				onclick='ScheduleViewer.showContacts({$contact_request_data});'><div class="input-icon icon-add left"></div><span class="input-label">{$add_contact_label}</span></button>
				{$add_resource}
			</td></tr>
			</table>
			</div>
            <div width="100%" id="{$this->id}" class="schedulerDiv"></div>
            </div>
EOQ;

        $activityInvite = new ActivityInvite($this->model_name, $_REQUEST);
        $invitees = $activityInvite->getList($row_result);
        $invitees_data = count($invitees) ? $json->encode($invitees) : '[]';
        $params = $json->encode(array('dialog' => (int)$dialog));

        $init_js = <<<EOS
        	ScheduleViewer.init('{$this->id}', {$invitees_data}, {$params});
            SUGAR.ui.registerInput('$form_name', ScheduleViewer);
EOS;

        $layout =& $gen->getLayout();
        $layout->addScriptInclude('modules/Meetings/scheduler.js');
        $layout->addScriptLiteral($init_js);

        return $body;
	}

	function loadUpdateRequest(RowUpdate &$update, array $input) {
        $upd_invites = array();
        $this->addInviteesIdsFromInput($input, $upd_invites);
        $update->setRelatedData('event_scheduler_rows', $upd_invites);
        $update->setRelatedData('send_invites', array_get_default($input, 'send_invites'));
    }

	function validateInput(RowUpdate &$update) {
		return true;
	}
	
	function afterUpdate(RowUpdate &$update) {}

    /**
     * Add invitee participants IDs to array
     *
     * @param array $input
     * @param array $invitees
     */
    function addInviteesIdsFromInput($input, &$invitees) {
        $names = array('user', 'contact', 'resource');

        for ($i = 0; $i < sizeof($names); $i++) {
            if(! empty($input[$names[$i] . '_invitees'])) {
                $invitees[$names[$i] . '_invitees'] = $input[$names[$i] . '_invitees'];
            }
        }
    }

    /**
     * Get popup data for open button
     *
     * @param string $form_name
     * @return array|string
     */
    function getPopupRequestData($form_name) {
        $json = getJSONobj();

		$request_data = array(
			'call_back_function' => 'scheduleReturnRow',
			'form_name' => $form_name,
			'field_to_name_array' => array(
				'id' => 'id',
				'_display' => '_display'
			),
			'passthru_data' => array(
				'field_id' => $this->id,
			),
		);

        $request_data = $json->encode($request_data);
        
        return $request_data;
    }
}
?>