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


require_once('modules/Emails/widgets/EmailWidget.php');
require_once 'include/database/ListQuery.php';

class EmailTemplateWidget extends EmailWidget {

	const DEFAULT_INSERT_MODULE = 'Accounts';

	var $campaign_id;

	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, array $context) {
		$lstyle = $gen->getLayout()->getType();

        if($lstyle == 'editview')
            return $this->renderHtmlEdit($gen, $row_result);

        return $this->renderHtmlView($gen, $row_result);
	}
	
	function getRequiredFields() {
        $fields = array('body', 'body_html', 'subject', 'campaign_id');

		return $fields;
	}
	
    /**
     * Render HTML for Email Template plain text body and attachments
     *
     * @param HtmlFormGenerator $gen
     * @param RowResult $row_result
     * @return string
     */
    function renderHtmlView(HtmlFormGenerator &$gen, RowResult &$row_result) {
        global $mod_strings;

        $attachments = '&nbsp;';
        $record = $row_result->getField('id');

        if ($record)
            $attachments = $this->renderAttachmentsView($row_result->getModuleDir(), $record, $mod_strings['LBL_ATTACHMENT_VIEW']);

        $body = <<<EOQ
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabDetailView" style="margin-top: 0.5em">
            {$this->renderPlainRow($gen, $row_result)}
            <tr>
            <td class="tabDetailViewDL" width="20%">{$mod_strings['LBL_ATTACHMENTS']}</td>
            <td class="tabDetailViewDF" colspan="3" width="80%">{$attachments}</td>
            </tr>
            </table>
EOQ;

        $layout =& $gen->getLayout();
        $layout->addScriptInclude('modules/EmailTemplates/template.js', LOAD_PRIORITY_FOOT);

        return $body;
    }

	function renderHtmlEdit(HtmlFormGenerator &$gen, RowResult &$row_result) {
        global $mod_strings, $app_list_strings, $app_strings;

        $record = $row_result->getField('id');

        $subject = $gen->form_obj->renderField($row_result, 'subject');
        $template_body_html = $gen->form_obj->renderField($row_result, 'body_html');
        $attachments = $this->renderAttachmentsEdit($record, array(), 'draft');
        $upload = $this->renderAttachmentUpload($gen);
        $body = "";

        $modules_opts = array(
            'Accounts' => $app_list_strings['moduleList']['Accounts'],
            'Contacts' => $mod_strings['LBL_CONTACT_AND_OTHERS']
        );
        $spec = array('name' => 'variable_module', 'options' => $modules_opts, 'options_add_blank' => false,  'onchange' => 'add_variables();');
        $modules_select = $gen->form_obj->renderSelect($spec, EmailTemplateWidget::DEFAULT_INSERT_MODULE);

        $modules_fields = $this->getModulesFields();
        $default_fields = $modules_fields[EmailTemplateWidget::DEFAULT_INSERT_MODULE];
        $fields_opts = array();
        
        for ($i = 0; $i < sizeof($default_fields); $i++) {
            $key = '$' . $default_fields[$i]['name'];
            $value = $default_fields[$i]['value'];
            $fields_opts[$key] = $value;
		}

        $spec = array('name' => 'variable_name', 'options' => $fields_opts, 'onchange' => 'show_variable();', 'width' => '12em');
        $fields_select = $gen->form_obj->renderSelect($spec, '');

        $spec = array('name' => 'variable_text', 'width' => 30);
		$variable_input = $gen->form_obj->renderText($spec, '');

        $tracker_urls = '';
        if (! empty($this->campaign_id))
            $tracker_urls = $this->renderTrackerUrls($gen);

        $body .= <<<EOQ
            <input type="hidden" name="remove_attachments" value="" id="remove_attachments" />
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm" style="margin-top: 0.5em">
            <tr>
            <td class="dataLabel" width="20%">{$mod_strings['LBL_SUBJECT']}<span class="requiredStar">*</span></td>
            <td class="dataField" colspan="3" width="80%">{$subject}</td>
            </tr>
            <tr>
                <td class="dataLabel" align="left">{$mod_strings['LBL_INSERT_VARIABLE']}</td>
                <td class="dataField" colspan="3">
                    {$modules_select}
                    {$fields_select}
                    <span class="dataLabel">:</span>
                    {$variable_input}
                    <button type='button' tabindex="70" onclick='insert_variable();' class='input-button input-outer'><span class="input-label">{$mod_strings['LBL_INSERT']}</span></button>
                </td>
            </tr>
            {$tracker_urls}
            <tr>
            <td class="dataLabel" width="20%">{$mod_strings['LBL_BODY']}</td>
            <td class="dataField" colspan="3" width="80%">{$template_body_html}</td>
            </tr>
            {$this->renderPlainRow($gen, $row_result, true)}
            <tr>
            <td class="dataLabel" width="20%">{$mod_strings['LBL_ATTACHMENTS']}</td>
            <td class="dataField" colspan="3" width="80%">{$attachments}{$upload}</td>
            </tr>
            </table>
EOQ;

        $layout =& $gen->getLayout();
        $layout->addScriptInclude('modules/Emails/Email.js');
		$layout->addJSLanguage('Emails');
        $this->addAttachmentsJs($layout);

        $json = getJSONObj();
        $fields_js = "var field_defs = " . $json->encode($modules_fields) . ';';
        $layout->addScriptLiteral($fields_js, LOAD_PRIORITY_FOOT);

        $layout->addScriptInclude('modules/EmailTemplates/template.js', LOAD_PRIORITY_FOOT);

        return $body;
	}


    /**
     * Render Tracker URLs row
     *
     * @param HtmlFormGenerator $gen
     * @return string
     */
    function renderTrackerUrls(HtmlFormGenerator &$gen) {
        global $app_strings, $mod_strings;

        $trackers = $this->getTrackerURLs();
        $tracker_opts = array('' => $app_strings['LBL_NONE']);
        foreach ($trackers as $tracker) {
            $tracker_opts[$tracker['id']] = $tracker['tracker_name'];
        }

        $spec = array('name' => 'tracker_id', 'options' => $tracker_opts, 'width' => '25em', 'onchange' => 'fill_tracker();');
        $tracker_select = $gen->form_obj->renderSelect($spec, '');

        $spec = array('name' => 'tracker_url', 'width' => 30);
        $tracker_url = $gen->form_obj->renderText($spec, '');

        $spec = array('name' => 'tracker_name', 'width' => 30);
        $tracker_name = $gen->form_obj->renderText($spec, '');

        $html =<<<EOQ
            <tr>
                <td class="dataLabel" align="left">{$mod_strings['LBL_INSERT_TRACKER_URL']}</td>
                <td class="dataField" colspan="3">
                    {$tracker_select}
                    {$tracker_url}
					{$tracker_name}
                    <button type='button' tabindex="70" onclick='insert_tracker();' class='input-button input-outer'><span class="input-label">{$mod_strings['LBL_INSERT_URL_REF']}</span></button>
                </td>
            </tr>
EOQ;
        $layout =& $gen->getLayout();
        $json = getJSONObj();
        $trackers_js = "var trackers = " . $json->encode($trackers) . ';';
        $layout->addScriptLiteral($trackers_js, LOAD_PRIORITY_FOOT);

        return $html;
    }

    /**
     * Render html for view / edit plain text row
     * 
     * @param HtmlFormGenerator $gen
     * @param RowResult $row_result
     * @param bool $edit
     * @return string
     */
    function renderPlainRow(HtmlFormGenerator &$gen, RowResult &$row_result, $edit = false) {
        global $mod_strings;

        $checked = 0;
        $style = 'style = "display: none;padding: 5px;"';

        if (! $edit) {
            $body = $row_result->getField('body');

            if (! empty($body)) {
                $checked = 1;
                $style = 'style = "padding: 5px;"';
            }

            $row_value = $body;
            $label_class = 'tabDetailViewDL';
            $value_class = 'tabDetailViewDF';
            $label = $mod_strings['LBL_SHOW_ALT_TEXT'];
        } else {
            $row_value =  $gen->form_obj->renderField($row_result, 'body');
            $label_class = 'dataLabel';
            $value_class = 'dataField';
            $label = $mod_strings['LBL_EDIT_ALT_TEXT'];
        }

        $spec = array('name' => 'show_plain', 'onchange' => 'show_plain();');
        $show_plain = $gen->form_obj->renderCheck($spec, $checked);

        $row = <<<EOQ
            <tr>
            <td valign="top" valign="top" class="{$label_class}">&nbsp;</td>
            <td colspan="3" class="{$value_class}">
                <label>{$show_plain}&nbsp;&nbsp;{$label}</label><br>
                <div id="plain_text_div" {$style}><pre>{$row_value}</pre></div></slot>
            </tr>
EOQ;

        return $row;
    }

    /**
     * Get modules fields list for Insert variable feature
     *
     * @return array
     */
    function getModulesFields() {
        $contact = new ModelDef('Contact');
        $lead = new ModelDef('Lead');
        $prospect = new ModelDef('Prospect');
        $account = new ModelDef('Account');

        // add fields here that would not make sense in an email template
        $badFields = array(
        	'id_c',
            'account_description',
            'contact_id',
            'lead_id',
            'opportunity_amount',
            'opportunity_id',
            'opportunity_name',
            'opportunity_role_id',
            'opportunity_role_fields',
            'opportunity_role',
            'campaign_id',
        );

        $field_defs = array(
            'Contacts' => array($contact->getFieldDefinitions(), 'contact_'),
            'Leads' => array($lead->getFieldDefinitions(), 'contact_'),
            'Prospects' => array($prospect->getFieldDefinitions(), 'contact_'),
            'Accounts' => array($account->getFieldDefinitions(), 'account_')
        );

        $field_defs_array = array();

        foreach ($field_defs as $module => $data) {
            $fields = array();
            foreach($data[0] as $field_def) {
                if(	($field_def['type'] == 'ref' && empty($field_def['custom_type'])) ||
                    ($field_def['type'] == 'assigned_user' || $field_def['type'] =='link') ||
                    ($field_def['type'] == 'bool') ||
                    (in_array($field_def['name'], $badFields)) ) {

                    continue;
                }
                if( $field_def['type'] == 'enum' && ! empty($field_def['multi_select_group']))
                	continue; // skip category fields

                $lang_module = $module;
                if (isset($field_def['original_module'])) {
                    $lang_module = $field_def['original_module'];
                }

                $field_def['vname'] = preg_replace('/:$/','',translate($field_def['vname'], $lang_module));
                $temp_Value = array('name' => $data[1] . $field_def['name'], 'value' => $field_def['vname']);

				$field_defs_array[$module][$temp_Value['name']] = $temp_Value;
            }
        }

        $field_defs_array['Contacts'] += $field_defs_array['Leads'];
        $field_defs_array['Contacts'] += $field_defs_array['Prospects'];
        unset($field_defs_array['Prospects']);
        unset($field_defs_array['Leads']);

		foreach($field_defs_array as $k => $v)
			$field_defs_array[$k] = array_values(array_csort($v, 'value'));

        return $field_defs_array;
	}

	function getTrackerURLs()
	{
		$lq = new ListQuery('Campaign', null, array('link_name' => 'tracked_urls'));
		$lq->setParentKey($this->campaign_id);
		return $lq->fetchAllRows();
	}

	function loadUpdateRequest(RowUpdate &$update, array $input) {
        $related_update = array();
        $related_update['attachments'] = $related_update['attachments'] = $this->loadUpdateAttachments($input);

		$update->setRelatedData($this->id.'_rows', $related_update);

		$this->campaign_id = array_get_default($input, 'campaign_id');

	}
	
	function validateInput(RowUpdate &$update) {
		return true;
	}
	
	function afterUpdate(RowUpdate &$update) {
        global $mod_strings, $timedate;

		$row_updates = $update->getRelatedData($this->id.'_rows');
		if(! $row_updates)
			return;

        $this->updateAttachments($update, $row_updates);
    }
}
?>
