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
require_once('modules/EventSessions/EventSession.php');

class EventWidget extends FormTableSection {

    /**
     * @var array
     */
    var $mod_strings;

    /**
     * @var RowResult
     */
    var $record;

    /**
     * @var array
     */
    var $record_fields;

    /**
     * @var array
     */
    var $form_fields;

	function init($params, $model=null) {
		parent::init($params, $model);
		if(! $this->id)
			$this->id = 'event_data';

        $this->mod_strings = return_module_language(AppConfig::language(), 'Events');

        $this->record_fields = array('name', 'num_sessions', 'product', 'assigned_user',
            'event_type', 'format', 'description');

        $this->form_fields = array('_event_name', 'num_sessions', 'product_id', '_event_assigned_user_id',
            'event_type_id', '_event_format', '_event_description');

	}
	
	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, $context) {
		if($gen->getLayout()->getEditingLayout()) {
			$params = array();
			return $this->renderOuterTable($gen, $parents, $context, $params);		
		}

        $this->record = $this->loadRecord($row_result);
		$lstyle = $gen->getLayout()->getType();

		if($lstyle == 'editview') {
			return $this->renderHtmlEdit($gen, $row_result);
		}

        return $this->renderHtmlView($gen, $row_result);
    }
	
	function getRequiredFields() {
		return array('event');
	}

    function renderHtmlView(HtmlFormGenerator &$gen, RowResult $row_result) {
        global $image_path;

        $next = EventSession::getRelatedSession($row_result->getField('event_id'), $row_result->getField('session_number'), 'next');
        $previous = EventSession::getRelatedSession($row_result->getField('event_id'), $row_result->getField('session_number'), 'previous');

        $show_link = false;
        $prev_link = '';
        $next_link = '';

        if ($previous) {
            $show_link = true;
            $label = $this->mod_strings['LNK_LIST_PREVIOUS'];
            $prev_link = '<a href="index.php?module=EventSessions&action=DetailView&record='.$previous.'" class="listViewPaginationLinkS1" style="padding-right: 10px;">&lt;&lt;&nbsp;'.$label.'</a>';
        }

        if ($next) {
            $show_link = true;
            $label = $this->mod_strings['LNK_LIST_NEXT'];
            $next_link = '<a href="index.php?module=EventSessions&action=DetailView&record='.$next.'" class="listViewPaginationLinkS1">'.$label.'&nbsp;&gt;&gt;</a>';
        }

        $body = '';

        if ($show_link) {
            $body .= <<<EOQ
                <table width="100%" border="0" cellpadding="0"  class="tabDetailView" style="margin-top: 0.5em">
                <tr><td colspan="10" class="listViewPaginationTdS1" style="padding: 5px">{$prev_link}{$next_link}</td></tr>
                </table>
EOQ;
        }

        return $body;
    }

	function renderHtmlEdit(HtmlFormGenerator &$gen, RowResult $row_result) {
        global $app_strings;
        
        $form = $gen->getFormObject();

        $name_def = $this->record->fields['name'];
        $name_def['name'] = '_event_name';
        $event_name = $form->renderText($name_def, $this->record->getField('name', '', true));

		$sess_def = $this->record->fields['num_sessions'];
		$sess_def['onchange'] = "hideShowNextBut(this.getValue());";
        $sessions = $form->renderText($sess_def, $this->record->getField('num_sessions', '', true));

        $product = $form->renderField($this->record, 'product');

        $user_def = $this->record->fields['assigned_user'];
        $user_def['id_name'] = '_event_assigned_user_id';
        $user_def['name'] = '_event_assigned_user';

        $type = $form->renderField($this->record, 'event_type');
        
        $format_def = $this->record->fields['format'];
        $format_def['name'] = '_event_format';
        $format = $form->renderSelect($format_def, $this->record->getField('format', '', false));
        
        $assigned_user = $form->renderRef($this->record, $user_def);

        $body = <<<EOQ
            <input type="hidden" name="event_id" value="{$row_result->getField('event_id')}">
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">
            <tr>
            <td class="dataLabel" width="20%">{$this->mod_strings[$this->record->fields['name']['vname']]}<span class="requiredStar">{$app_strings['LBL_REQUIRED_SYMBOL']}</span></td>
            <td class="dataField" width="30%">{$event_name}</td>
            <td class="dataLabel" width="20%">{$this->mod_strings[$this->record->fields['num_sessions']['vname']]}<span class="requiredStar">{$app_strings['LBL_REQUIRED_SYMBOL']}</span></td>
            <td class="dataField" width="30%">{$sessions}</td>
            </tr>
            <tr>
            <td class="dataLabel" width="20%">{$this->mod_strings[$this->record->fields['event_type']['vname']]}<span class="requiredStar">{$app_strings['LBL_REQUIRED_SYMBOL']}</span></td>
            <td class="dataField" width="30%">{$type}</td>
            <td class="dataLabel" width="20%">{$this->mod_strings[$this->record->fields['format']['vname']]}<span class="requiredStar">{$app_strings['LBL_REQUIRED_SYMBOL']}</span></td>
            <td class="dataField" width="30%">{$format}</td>
            </tr>
            <tr>
            <td class="dataLabel" width="20%">{$this->mod_strings[$this->record->fields['product']['vname']]}<span class="requiredStar">{$app_strings['LBL_REQUIRED_SYMBOL']}</span></td>
            <td class="dataField" width="30%">{$product}</td>
            <td class="dataLabel" width="20%">{$this->mod_strings[$this->record->fields['assigned_user']['vname']]}<span class="requiredStar">{$app_strings['LBL_REQUIRED_SYMBOL']}</span></td>
            <td class="dataField" width="30%">{$assigned_user}</td>
            </tr>
            <tr>
            <td class="dataLabel" width="20%">{$this->mod_strings[$this->record->fields['description']['vname']]}</td>
            <td class="dataField" colspan="3"><textarea cols="60" rows="4" class="input-textarea input-outer" name="_event_description">{$this->record->formatted['description']}</textarea></td>
            </tr>
            </table>
EOQ;

        return $body;
	}

    /**
     * Load event record
     *
     * @param RowResult $row_result
     * @return RowResult
     */
    function loadRecord(RowResult $row_result) {
        $id = $row_result->getField('event_id');

        $lq = new ListQuery('Event', $this->record_fields);
        $lq->addPrimaryKey();
        $lq->addAclFilter('list');

        if (! empty($id)) {
            $lq->addFilterPrimaryKey($id);
            $result = $lq->runQuerySingle();
        } else {
            $result = $lq->getBlankResult();
        }

        //Hardcoded for compatibility with Event Session
        $result->row['_event_assigned_user_id'] = $row_result->row['assigned_user_id'];
        $result->row['_event_assigned_user'] = get_assigned_user_name($result->row['_event_assigned_user_id']);

        $this->formatRecord($result);

        return $result;
    }

    /**
     * Format record data
     *
     * @param RowResult $result
     */
    function formatRecord(RowResult &$result) {
        $fmt = new FieldFormatter('html', 'editview');
        $fmt->primary_key = $result->primary_key;
        $fmt->module_dirs = $result->module_dirs;
        $row = array();
        if (is_array($result->row))
            $row = $result->row;
        $result->formatted = $fmt->formatRow($result->fields, $row);
    }

	function loadUpdateRequest(RowUpdate &$update, array $input) {
        $upd_event = array();
        $event_data = array();

        for ($i = 0; $i < sizeof($this->form_fields); $i++) {

            if (isset( $input[$this->form_fields[$i]] )) {
                $name = str_replace('_event_', '', $this->form_fields[$i]);
                $event_data[$name] = $input[$this->form_fields[$i]];
            }

        }

        if (sizeof($event_data) > 0)
            $upd_event['data'] = $event_data;

        if (! empty($input['event_id']))
            $upd_event['event_id'] = $input['event_id'];

        $update->setRelatedData($this->id.'_rows', $upd_event);
	}
	
	function validateInput(RowUpdate &$update) {
		return true;
	}
	
	function afterUpdate(RowUpdate &$update) {
		$row_updates = $update->getRelatedData($this->id.'_rows');
		if(! $row_updates)
			return;

        if (isset($row_updates['data'])) {
            $model = new ModelDef('Event');
            $upd = new RowUpdate($model);
            $upd->limitFields($this->record_fields);
            $lq = new ListQuery($model, $this->record_fields);

            if (isset($row_updates['event_id'])) {
                $lq->addFilterPrimaryKey($row_updates['event_id']);
                $result = $lq->runQuerySingle();
            } else {
                $upd->new_record = true;
                $result = $lq->getBlankResult();
            }

            if($upd->setOriginal($result)) {
                $upd->set($row_updates['data']);
                $upd->save();

                //link event with session
                $update->set(array('event_id' => $upd->getPrimaryKeyValue()));
                $update->save('update');

            }
        }
	}

}
?>
