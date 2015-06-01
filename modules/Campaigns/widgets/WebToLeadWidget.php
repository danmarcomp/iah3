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

class WebToLeadWidget extends FormTableSection {

    /**
     * Form required fields
     *
     * @var array
     */
    var $required_fields;

    const CLASSNAME = 'SUGAR_GRID';

    function init($params, $model=null) {
        parent::init($params, $model);
    }

	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, array $context) {
		if($gen->getLayout()->getEditingLayout()) {
			$params = array();
			return $this->renderOuterTable($gen, $parents, $context, $params);		
		}

        if ($this->id == 'webtolead_generation') {
            return $this->renderFormEditor($gen);
        } elseif ($this->id == 'webtolead_save') {
            return $this->renderDownloadView();
        } else {
            return $this->renderSelectListView($gen, $row_result);
        }
	}
	
	function getRequiredFields() {
        $fields = array('assigned_user', 'campaign', 'name');

		return $fields;
	}
	
    /**
     * Render Track chart
     *
     * @param HtmlFormGenerator $gen
     * @param RowResult $row_result
     * @return string
     */
    function renderSelectListView(HtmlFormGenerator &$gen, RowResult &$row_result) {
        global $mod_strings, $app_strings;

        $fields = $this->getLeadFields();
        $chooser = $this->constructDDWebToLeadFields($fields, WebToLeadWidget::CLASSNAME);

        $body = <<<EOQ
            <div id='grid_Div'>
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
            <td><p><b>{$mod_strings['LBL_DRAG_DROP_COLUMNS']}</b></p></td>
            </tr>
            <tr><td></td></tr>
            <tr><td>
            <table width="555" border="0" cellspacing="0" cellpadding="0">
            <tr>
            <td>
            <table align="center" border="0" cellpadding="0" cellspacing="0" width='350'>
            <tbody><tr><td align="center">
            {$chooser}
            </td></tr></tbody>
            </table>
            </td>
            </tr>
            <tr>
            <td colspan='3'>
            <div id='webformfields'></div>
            </td>
            </tr>
            </table>
            <table width="595" border="0" cellspacing="0" cellpadding="2">
            <td align="left">
            <input id="lead_add_remove_button" type='button' title="{$app_strings['LBL_ADD_ALL_LEAD_FIELDS']}" class="input-button" onclick="javascript:dragDropAllFields('{$app_strings['LBL_ADD_ALL_LEAD_FIELDS']}','{$app_strings['LBL_REMOVE_ALL_LEAD_FIELDS']}');" name="addAllFields" value="{$app_strings['LBL_ADD_ALL_LEAD_FIELDS']}" />
            </td>
            <td align="right" style="padding-bottom: 2px;">
            <input title="{$app_strings['LBL_CANCEL_BUTTON_TITLE']}" accessKey="{$app_strings['LBL_CANCEL_BUTTON_KEY']}" class="input-button" onclick="return SUGAR.ui.cancelEdit(document.forms.DetailForm);" type="submit" name="button" value="{$app_strings['LBL_CANCEL_BUTTON_LABEL']}" />
            <input id="lead_next_button" type='button' title="{$app_strings['LBL_NEXT_BUTTON_LABEL']}" class="input-button" onclick="javascript:askLeadQ('next','{$mod_strings['LBL_SELECT_REQUIRED_LEAD_FIELDS']}','{$mod_strings['LBL_SELECT_LEAD_FIELDS']}');" name="nextButton" value="{$app_strings['LBL_NEXT_BUTTON_LABEL']}" />
            </td>
            </table>
            </tr>
            </td>
            </tr>
            </table>
            </div>
EOQ;

        $body .= $this->renderFormProperties($gen, $row_result);

        $layout =& $gen->getLayout();
        $layout->addScriptInclude('modules/Campaigns/WebToLead.js');

        return $body;
    }

    /**
     * Render Lead form properties block
     *
     * @param HtmlFormGenerator $gen
     * @param RowResult $row_result
     * @return string
     */
    function renderFormProperties(HtmlFormGenerator &$gen, RowResult &$row_result) {
        global $mod_strings, $app_strings;

        $campaign = $gen->form_obj->renderField($row_result, 'campaign');
        $assigned_user = $gen->form_obj->renderField($row_result, 'assigned_user');
        $spec = array('name' => 'chk_edit_url', 'onchange' => 'editUrl();');
        $edit_url_check = $gen->form_obj->renderCheck($spec, 0);

        $web_post_url = AppConfig::site_url() . 'WebToLeadCapture.php';
        $redirect_url = 'http://';

        $body = <<<EOQ
            <div id='lead_queries_Div' style="display: none">
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr><td>&nbsp;</td></tr>
            </table>
            <table width="100%" cellpadding="0" cellspacing="0" border="0" class="tabForm">
            <tr><td>
            <table border="0" cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td class="dataLabel" width="20%">{$mod_strings['LBL_DEFINE_LEAD_HEADER']}</td>
                <td class="dataField" width="80%"><input id="web_header" name="web_header" title="Name" size="60" value="{$mod_strings['LBL_LEAD_DEFAULT_HEADER']}" type="text" /></td>
            </tr>
            <tr>
                <td class="dataLabel"><slot>{$mod_strings['LBL_DESCRIPTION_LEAD_FORM']}</slot></td>
                <td class="dataField"><slot><textarea tabindex='1' name='web_description' rows='2' cols='55'>{$mod_strings['LBL_DESCRIPTION_TEXT_LEAD_FORM']}</textarea></span sugar='slot' /></td>
            </tr>
            <tr>
                <td class="dataLabel">{$mod_strings['LBL_DEFINE_LEAD_SUBMIT']}</td>
                <td class="dataField"><input id="web_submit" name="web_submit" title="Name" size="60" value="{$mod_strings['LBL_DEFAULT_LEAD_SUBMIT']}" type="text" /></td>
            </tr>
            <tr>
                <td class="dataLabel">{$mod_strings['LBL_DEFINE_LEAD_DUP_ERROR']}</td>
                <td class="dataField"><input id="dup_message" name="dup_message" size="60" value="{$mod_strings['LBL_DEFAULT_LEAD_DUP_ERROR']}" type="text" /></td>
            </tr>
            <tr>
                <td class="dataLabel"><slot>{$mod_strings['LBL_DEFINE_LEAD_POST_URL']}</slot></td>
                <td class="dataField"><slot><input id="post_url" name="post_url" size="60" disabled='true' value="{$web_post_url}" type="text"></slot>
                {$edit_url_check}&nbsp;{$mod_strings['LBL_EDIT_LEAD_POST_URL']}
                </td>
            </tr>
            <tr>
                <td class="dataLabel"><slot>{$mod_strings['LBL_DEFINE_LEAD_REDIRECT_URL']}</slot></td>
                <td class="dataField"><slot><input id="redirect_url" name="redirect_url" size="60" value="{$redirect_url}" type="text"></slot></td>
            </tr>
            <tr>
                <td class="dataLabel"><span sugar='slot40'>{$mod_strings['LBL_LEAD_NOTIFY_CAMPAIGN']}</span sugar='slot'><span class="required">{$app_strings['LBL_REQUIRED_SYMBOL']}</span></td>
                <td class="dataField"><span sugar='slot40b'>
                {$campaign}
                </span sugar='slot'></td>
            </tr>
            <tr>
                <td class="dataLabel"><span sugar='slot45'>{$app_strings['LBL_ASSIGNED_TO']}</span sugar='slot'><span class="required">{$app_strings['LBL_REQUIRED_SYMBOL']}</span></td>
                <td  class="dataField"><span sugar='slot45b'>
                {$assigned_user}
                </span sugar='slot'></td>
            </tr>
            <tr>
                <td class="dataLabel"><slot>{$mod_strings['LBL_LEAD_FOOTER']}</slot></td>
                <td class="dataField"><slot><textarea tabindex='1' name='web_footer' rows='2' cols='55'></textarea></span sugar='slot'></td>
            </tr>
            </table>
            </td></tr>
            </table>
            <table  width="100%" border="0" cellspacing="0" cellpadding="2">
            <tr>
            <td align="right" style="padding-bottom: 2px;">
            <input title="{$app_strings['LBL_BACK']}" class="input-button" onclick="askLeadQ('back')" type="button" name="button" value="{$app_strings['LBL_BACK']}" />
            <input title="{$app_strings['LBL_CANCEL_BUTTON_TITLE']}" accessKey="{$app_strings['LBL_CANCEL_BUTTON_KEY']}" class="input-button" onclick="return SUGAR.ui.cancelEdit(document.forms.DetailForm);" type="submit" name="button" value="{$app_strings['LBL_CANCEL_BUTTON_LABEL']}" />
            <input title="{$app_strings['LBL_GENERATE_WEB_TO_LEAD_FORM']}" class="input-button" onclick="this.form.layout.value='WebToLeadGen';return addGrids('DetailForm');" type="submit" name="button" value="{$app_strings['LBL_GENERATE_WEB_TO_LEAD_FORM']}" />
            </td>
            </tr>
            </table>
            </div>
EOQ;
        return $body;
    }

    /**
     * Render Web-to-Lead form editor
     *
     * @param HtmlFormGenerator $gen
     * @return string
     */
    function renderFormEditor(HtmlFormGenerator &$gen) {
        global $mod_strings, $app_strings;

        $web_form_header = $mod_strings['LBL_LEAD_DEFAULT_HEADER'];
        $web_form_description = $mod_strings['LBL_DESCRIPTION_TEXT_LEAD_FORM'];
        $web_form_submit_label = $mod_strings['LBL_DEFAULT_LEAD_SUBMIT'];
        $web_post_url = AppConfig::site_url() .'/WebToLeadCapture.php';
        $web_redirect_url = '';
        $web_assigned_user = '';
        $web_form_footer = '';
        $dup_message = '';

        if(! empty($_REQUEST['web_header']))
            $web_form_header = $_REQUEST['web_header'];
        if(! empty($_REQUEST['web_description']))
            $web_form_description = $_REQUEST['web_description'];
        if(! empty($_REQUEST['web_submit']))
            $web_form_submit_label = to_html($_REQUEST['web_submit']);
        if(! empty($_REQUEST['post_url']))
            $web_post_url = $_REQUEST['post_url'];
        if(! empty($_REQUEST['redirect_url']) && $_REQUEST['redirect_url'] != "http://")
            $web_redirect_url = $_REQUEST['redirect_url'];
        if(! empty($_REQUEST['web_footer']))
            $web_form_footer = $_REQUEST['web_footer'];
        if(! empty($_REQUEST['campaign_id']))
            $web_form_campaign = $_REQUEST['campaign_id'];
        if(! empty($_REQUEST['assigned_user_id']))
            $web_assigned_user = $_REQUEST['assigned_user_id'];
        if(! empty($_REQUEST['dup_message']))
            $dup_message = $_REQUEST['dup_message'];

        $Web_To_Lead_Form_html = "<form action='$web_post_url' name='WebToLeadForm' method='POST'>";
        $Web_To_Lead_Form_html .= "<table width='100%' style='border-top: 1px solid; border-bottom: 1px solid; padding: 10px 6px 12px 10px; background-color: rgb(233, 243, 255); font-size: 12px; background-repeat: repeat-x; background-position: center top;'>";
        $Web_To_Lead_Form_html .= "<tr align='center' style='color: rgb(0, 105, 225); font-family: Arial,Verdana,Helvetica,sans-serif; font-size: 18px; font-weight: bold; margin-bottom: 0px; margin-top: 0px;'><td COLSPAN='4'><b><h2>$web_form_header</h2></b></td></tr>";
        $Web_To_Lead_Form_html .= "<tr align='center' style='color: rgb(0, 105, 225); font-family: Arial,Verdana,Helvetica,sans-serif; font-size: 2px; font-weight: normal; margin-bottom: 0px; margin-top: 0px;'><td COLSPAN='4'>&nbsp</td></tr>";
        $Web_To_Lead_Form_html .= "<tr id='error_message_row' align='center' style='display: none;color: rgb(255, 0, 0); font-family: Arial,Verdana,Helvetica,sans-serif; font-size: 12px; font-weight: bold; margin-bottom: 0px; margin-top: 0px;'><td id='error_message' COLSPAN='4'></td></tr>";
        $Web_To_Lead_Form_html .= "<tr align='center' style='color: rgb(0, 105, 225); font-family: Arial,Verdana,Helvetica,sans-serif; font-size: 2px; font-weight: normal; margin-bottom: 0px; margin-top: 0px;'><td COLSPAN='4'>&nbsp</td></tr>";
        $Web_To_Lead_Form_html .= "<tr align='left' style='color: rgb(0, 105, 225); font-family: Arial,Verdana,Helvetica,sans-serif; font-size: 12px; font-weight: normal; margin-bottom: 0px; margin-top: 0px;'><td COLSPAN='4'>$web_form_description</td></tr>";
        $Web_To_Lead_Form_html .= "<tr align='center' style='color: rgb(0, 105, 225); font-family: Arial,Verdana,Helvetica,sans-serif; font-size: 8px; font-weight: normal; margin-bottom: 0px; margin-top: 0px;'><td COLSPAN='4'>&nbsp</td></tr>";

		$statusAdded = false;
        $Web_To_Lead_Form_html .= $this->generateForm($statusAdded);

        $Web_To_Lead_Form_html .= "<tr align='center' style='color: rgb(0, 105, 225); font-family: Arial,Verdana,Helvetica,sans-serif; font-size: 18px; font-weight: bold; margin-bottom: 0px; margin-top: 0px;'><td COLSPAN='4'>&nbsp</td></tr>";

        if(! empty($web_form_footer)) {
            $Web_To_Lead_Form_html .= "<tr align='center' style='color: rgb(0, 105, 225); font-family: Arial,Verdana,Helvetica,sans-serif; font-size: 18px; font-weight: bold; margin-bottom: 0px; margin-top: 0px;'><td COLSPAN='4'>&nbsp</td></tr>";
            $Web_To_Lead_Form_html .= "<tr align='left' style='color: rgb(0, 105, 225); font-family: Arial,Verdana,Helvetica,sans-serif; font-size: 12px; font-weight: normal; margin-bottom: 0px; margin-top: 0px;'><td COLSPAN='4'>$web_form_footer</td></tr>";
        }

        $Web_To_Lead_Form_html .= "<tr align='center'><td colspan='10'><input type='button' onclick='check_webtolead_fields(this.form);' class='button' name='Submit' value='$web_form_submit_label'/></td></tr>";

        if (!$statusAdded)
           $Web_To_Lead_Form_html .= "<tr><td style='display: none'><input type='hidden' id='status' name='status' value='New'></td></tr>";
        if(! empty($web_form_campaign))
           $Web_To_Lead_Form_html .= "<tr><td style='display: none'><input type='hidden' id='campaign_id' name='campaign_id' value='$web_form_campaign'></td></tr>";

        if(! empty($web_redirect_url))
            $Web_To_Lead_Form_html .= "<tr><td style='display: none'><input type='hidden' id='redirect_url' name='redirect_url' value='$web_redirect_url'></td></tr>";

        if(! empty($web_assigned_user))
            $Web_To_Lead_Form_html .= "<tr><td style='display: none'><input type='hidden' id='assigned_user_id' name='assigned_user_id' value='$web_assigned_user'></td></tr>";

        $Web_To_Lead_Form_html .= "<tr><td style='display: none'><input type='hidden' id='error_message_text' name='error_message_text' value='{$dup_message}'></td></tr>";

        $req_fields = '';

        if($this->required_fields != null) {
            foreach($this->required_fields as $req) {
                 $req_fields = $req_fields . $req . ';';
            }
        }

        if(! empty($req_fields)) {
            $Web_To_Lead_Form_html .= "<tr><td style='display: none'><input type='hidden' id='req_id' name='req_id' value='$req_fields'></td></tr>";
        }

        $Web_To_Lead_Form_html .= "</table >";
        $Web_To_Lead_Form_html .= "</form>";
        $Web_To_Lead_Form_html .= $this->getFormJs();

        $spec = array('name' => 'body_html');
        $editor = $gen->form_obj->renderHtmlEditor($spec, $Web_To_Lead_Form_html);        

        $body = <<<EOQ
            <style>#subjectfield { height: 1.6em; }</style>
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td style="padding-bottom: 2px;">
                <input title="{$app_strings['LBL_SAVE_WEB_TO_LEAD_FORM']}" accessKey="{$app_strings['LBL_SAVE_WEB_TO_LEAD_FORM']}" class="input-button" onclick="this.form.layout.value='WebToLeadSave';" type="submit" name="button" value="{$app_strings['LBL_SAVE_WEB_TO_LEAD_FORM']}" />
                <input title="{$app_strings['LBL_CANCEL_BUTTON_TITLE']}" accessKey="{$app_strings['LBL_CANCEL_BUTTON_KEY']}" class="input-button" onclick="return SUGAR.ui.cancelEdit(document.forms.DetailForm);" type="submit" name="button" value="{$app_strings['LBL_CANCEL_BUTTON_LABEL']}" />
                </td>
                <td align="right" nowrap><span class="required">{$app_strings['LBL_REQUIRED_SYMBOL']}</span> {$app_strings['NTC_REQUIRED']}</td>
                <td align='right'>&nbsp;</td>
            </tr>
            </table>
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">
            <tr>
                <td>
                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td valign="top" class="dataLabel">&nbsp;</td>
                    <td colspan="4" class="dataField"><slot>
                    <div id="html_div">{$editor}</div>
                    </slot></td>
                </tr>
                </table>
                </td>
            </tr>
            </table>
EOQ;

        return $body;
    }

    /**
     * Generate Web-to-Lead form for Campaign
     *
     * @return void
     */
    function generateForm(&$statusAdded) {
        $columns = 0;

        if(! empty($_REQUEST['colsFirst']) && ! empty($_REQUEST['colsSecond'])) {
            if(count($_REQUEST['colsFirst']) < count($_REQUEST['colsSecond'])) {
                $columns = count($_REQUEST['colsSecond']);
            }
            if(count($_REQUEST['colsFirst']) > count($_REQUEST['colsSecond']) || count($_REQUEST['colsFirst']) == count($_REQUEST['colsSecond'])){
                $columns = count($_REQUEST['colsFirst']);
            }
        } else if(! empty($_REQUEST['colsFirst'])){
            $columns = count($_REQUEST['colsFirst']);
        } else if(! empty($_REQUEST['colsSecond'])){
            $columns = count($_REQUEST['colsSecond']);
        }

        $Web_To_Lead_Form_html = '';
        $required_fields = array();

        for ($i = 0; $i < $columns; $i++) {
            $colsFirstField = '';
            $colsSecondField = '';

            if (! empty($_REQUEST['colsFirst'][$i]))
                $colsFirstField = $_REQUEST['colsFirst'][$i];
            if (! empty($_REQUEST['colsSecond'][$i]))
                $colsSecondField = $_REQUEST['colsSecond'][$i];

            $Web_To_Lead_Form_html .= "<tr>";
            $Web_To_Lead_Form_html .= $this->renderFormCol($colsFirstField, $required_fields);
            $Web_To_Lead_Form_html .= $this->renderFormCol($colsSecondField, $required_fields);
            $Web_To_Lead_Form_html .= "</tr>";
        }

		$statusAdded = in_array('status', $required_fields);
		if (!$statusAdded)
			$required_fields[] = 'status';

		$this->required_fields = $required_fields;

        return $Web_To_Lead_Form_html;
    }

    /**
     * Render Web-to-Lead form columns
     *
     * @param string $colsField
     * @param array $required_fields
     * @return string
     */
    function renderFormCol($colsField, &$required_fields) {
        global $app_strings, $app_list_strings;

        $web_required_symbol = $app_strings['LBL_REQUIRED_SYMBOL'];
        $field_type = null;
        $field_name = null;
        $field_options = null;
        $field_required = '';

        $lead = new ModelDef('Lead');
        $field_defs = $lead->getFieldDefinitions();

        if (isset($field_defs[$colsField]) && $field_defs[$colsField] != null) {

            $field_vname = preg_replace('/:$/','',translate($field_defs[$colsField]['vname'], 'Leads'));
            $field_name  = $colsField;
            $field_label = $field_vname . ": ";

            if (isset($field_defs[$colsField]['custom_type']) && $field_defs[$colsField]['custom_type'] != null) {
                $field_type = $field_defs[$colsField]['custom_type'];
            } else {
                $field_type = $field_defs[$colsField]['type'];
            }

            if (isset($field_defs[$colsField]['required']) && $field_defs[$colsField]['required'] != null) {
                $field_required = $field_defs[$colsField]['required'];

                if (! in_array($field_defs[$colsField]['name'], $required_fields)) {
                    array_push($required_fields, $field_defs[$colsField]['name']);
                }
            }

            if ($field_defs[$colsField]['name'] == 'last_name') {
                if (! in_array($field_defs[$colsField]['name'], $required_fields)) {
                    array_push($required_fields, $field_defs[$colsField]['name']);
                }
            }

            if ($field_type == 'enum' || $field_type == 'multienum') $field_options = $field_defs[$colsField]['options'];
        }

        $Web_To_Lead_Form_html = '';

        if (isset($field_defs[$colsField]) && $field_defs[$colsField] != null) {
            if ($field_type=='enum' || $field_type == 'multienum') {

                if (isset($field_defs[$colsField]['default'])) {
                    $lead_options = get_select_options_with_id($app_list_strings[$field_options], $field_defs[$colsField]['default']);
                } else {
                    $lead_options = get_select_options_with_id($app_list_strings[$field_options], '');
                }
                if ($field_required) {
                    $Web_To_Lead_Form_html .= "<td width='15%' style='text-align: left; font-size: 12px; font-weight: normal;'><span sugar='slot'>$field_label</span sugar='slot'><span class='required' style='color: rgb(255, 0, 0);'>$web_required_symbol</span></td>";
                } else {
                    $Web_To_Lead_Form_html .= "<td width='15%' style='text-align: left; font-size: 12px; font-weight: normal;'><span sugar='slot'>$field_label</span sugar='slot'></td>";
                }

                if (isset($field_defs[$colsField]['isMultiSelect']) && $field_defs[$colsField]['isMultiSelect'] == 1) {
                    $Web_To_Lead_Form_html .= "<td width='35%' style='font-size: 12px; font-weight: normal;'><span sugar='slot'><select id=$field_name multiple='true' name=$field_name tabindex='1'>$lead_options</select></span sugar='slot'></td>";
                } elseif ($this->ifRadioButton($field_defs[$colsField]['name'])) {
                    $Web_To_Lead_Form_html .="<td width='35%' style='font-size: 12px; font-weight: normal;'><span sugar='slot'>";

                    foreach($app_list_strings[$field_options] as $field_option){
                        $Web_To_Lead_Form_html .="<input id='$colsField"."_$field_option' name='radio_grp_$colsField' value='$field_option' type='radio'>";
                        $Web_To_Lead_Form_html .="<span ='document.getElementById('".$field_defs[$colsField]."_$field_option').checked =true style='cursor:default'; onmousedown='return false;'>$field_option</span><br>";
                    }
                    $Web_To_Lead_Form_html .="</span sugar='slot'></td>";
                } else {
                    $Web_To_Lead_Form_html .= "<td width='35%' style='font-size: 12px; font-weight: normal;'><span sugar='slot'><select id=$field_name name=$field_name tabindex='1'>$lead_options</select></span sugar='slot'></td>";
                }
            }

            if ($field_type == 'bool') {
                if ($field_required){
                    $Web_To_Lead_Form_html .= "<td width='15%' style='text-align: left; font-size: 12px; font-weight: normal;'><span sugar='slot'>$field_label</span sugar='slot'><span class='required' style='color: rgb(255, 0, 0);'>$web_required_symbol</span></td>";
                } else {
                    $Web_To_Lead_Form_html .= "<td width='15%' style='text-align: left; font-size: 12px; font-weight: normal;'><span sugar='slot'>$field_label</span sugar='slot'></td>";
                }

                $Web_To_Lead_Form_html .= "<td width='35%' style='font-size: 12px; font-weight: normal;'><span sugar='slot'><input type='checkbox' id=$field_name name=$field_name></span sugar='slot'></td>";
            }

            if ( $field_type=='text' ||  $field_type=='varchar' ||  $field_type=='name' ||  $field_type=='phone' || $field_type=='email') {
                if ($field_name=='last_name' ||   $field_required){
                    $Web_To_Lead_Form_html .= "<td width='15%' style='text-align: left; font-size: 12px; font-weight: normal;'><span sugar='slot'>$field_label</span sugar='slot'><span class='required' style='color: rgb(255, 0, 0);'>$web_required_symbol</span></td>";
                } else {
                    $Web_To_Lead_Form_html .= "<td width='15%' style='text-align: left; font-size: 12px; font-weight: normal;'><span sugar='slot'>$field_label</span sugar='slot'></td>";
                }

                $Web_To_Lead_Form_html .= "<td width='35%' style='font-size: 12px; font-weight: normal;'><span sugar='slot'><input id=$field_name name=$field_name type='text'></span sugar='slot'></td>";
            }

        } else {
            $Web_To_Lead_Form_html .= "<td width='15%' style='text-align: left; font-size: 12px; font-weight: normal;'><span sugar='slot'>&nbsp</span sugar='slot'></td>";
            $Web_To_Lead_Form_html .= "<td width='35%' style='font-size: 12px; font-weight: normal;'><span sugar='slot'>&nbsp</span sugar='slot'></td>";
        }

        return $Web_To_Lead_Form_html;
    }

    /**
     * Save generated form and render Download Web-to-Lead form page
     *
     * @return string
     */
    function renderDownloadView() {
        global $app_strings, $mod_strings;

        $savetime = time();

        if(! empty($_REQUEST['body_html'])) {
            $dir_path = CacheManager::get_location("generated_forms/", true);

            if(!file_exists($dir_path)) {
                mkdir($dir_path, 0777);
            }

            $file = $dir_path .'WebToLeadForm_'. $savetime .'.html';
            $fp = fopen($file, 'wb');
            fwrite($fp, from_html($_REQUEST['body_html']));
            fclose($fp);
        }

        $webformlink = "<b>{$mod_strings['LBL_DOWNLOAD_TEXT_WEB_TO_LEAD_FORM']}</b><br/><br />";
        $webformlink .= "<a href='{$dir_path}WebToLeadForm_{$savetime}.html' target='_blank'>{$mod_strings['LBL_DOWNLOAD_WEB_TO_LEAD_FORM']}</a>";

        $body = <<<EOQ
            <style>#subjectfield { height: 1.6em; }</style>
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">
            <tr><td>
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr><td colspan=4>&nbsp;</td></tr>
            <tr>{$webformlink}</tr>
            </table>
            </td></tr>
            </table>
EOQ;

        return $body;
    }

    /**
     * Get Web-to-Lead Form javascript
     *
     * @return string
     */
    function getFormJs() {
        global $mod_strings;
        $web_form_required_fileds_msg = $mod_strings['LBL_PROVIDE_WEB_TO_LEAD_FORM_FIELDS'];

        $js = "<script type='text/javascript'>
			function check_webtolead_fields(form) {
                if(document.getElementById('req_id') != null) {
                    var reqs = document.getElementById('req_id').value;
                    reqs = reqs.substring(0,reqs.lastIndexOf(';'));
                    var req_fields = reqs.split(';');
                    nbr_fields = req_fields.length;
                    var req = true;
                    for(var i=0;i<nbr_fields;i++){
                        if (document.getElementById(req_fields[i]).value.length <= 0) {
                            req = false;
                            break;
                        }
                    }
                    if(req) {
                        form.submit();
                        return true;
                    } else {
                        alert('$web_form_required_fileds_msg');
                        return false;
                    }
                } else {
                    form.submit();
                }
            }

            function assignFormFields() {
                var params = window.location.search.substr(1).split('&');
                for (var i in params) {
                    var param = params[i].split('=');
                    var name = decodeURIComponent(param[0]);
                    var value = decodeURIComponent(param[1]).replace(/\+/g, ' ');
                    if (name === 'isDuplicate') {
                        document.getElementById('error_message_row').style.display = '';
                        document.getElementById('error_message').innerHTML = document.getElementById('error_message_text').value;
                    }
                    var input = document.getElementsByName(name)[0];
                    if (!input) continue;
                    input.value = value;
                }
            };
            assignFormFields();
        </script>";

        return $js;
    }

    /**
     * Constructs Drag and Drop multiselect box of Lead fields
     *
     * @param array $fields
     * @param string $classname
     * @return string
     */
	function constructDDWebToLeadFields($fields, $classname){
        require_once("include/templates/TemplateDragDropChooser.php");
        global $mod_strings;
        $d2 = array();

        $dd_chooser = new TemplateDragDropChooser();
        $dd_chooser->args['classname'] = $classname;
        $dd_chooser->args['left_header'] = $mod_strings['LBL_AVALAIBLE_FIELDS_HEADER'];
        $dd_chooser->args['mid_header'] = $mod_strings['LBL_LEAD_FORM_FIRST_HEADER'];
        $dd_chooser->args['right_header'] = $mod_strings['LBL_LEAD_FORM_SECOND_HEADER'];
        $dd_chooser->args['left_data'] = $fields;
        $dd_chooser->args['mid_data'] = $d2;
        $dd_chooser->args['right_data'] = $d2;
        $dd_chooser->args['title'] = ' ';
        $dd_chooser->args['left_div_name'] = 'ddgrid2';
        $dd_chooser->args['mid_div_name'] = 'ddgrid3';
        $dd_chooser->args['right_div_name'] = 'ddgrid4';
        $dd_chooser->args['gridcount'] = 'three';
        $str = $dd_chooser->displayScriptTags();
        $str .= $dd_chooser->displayDefinitionScript();
        $str .= $dd_chooser->display();
        $str .= "<script type='text/javascript'>
                   //function post rows
                   function postMoveRows(){
                        //Call other function when this is called
                   }
                </script>";
        $str .= "<script>
                   function displayAddRemoveDragButtons(Add_All_Fields,Remove_All_Fields){
                        var addRemove = document.getElementById('lead_add_remove_button');
                        if(" . $dd_chooser->args['classname'] . "_grid0.getDataModel().getTotalRowCount() ==0) {
                         addRemove.setAttribute('value',Remove_All_Fields);
                         addRemove.setAttribute('title',Remove_All_Fields);
                        }
                        else if(" . $dd_chooser->args['classname'] . "_grid1.getDataModel().getTotalRowCount() ==0 && " . $dd_chooser->args['classname'] . "_grid2.getDataModel().getTotalRowCount() ==0){
                         addRemove.setAttribute('value',Add_All_Fields);
                         addRemove.setAttribute('title',Add_All_Fields);
                        }
                  }
                </script>";

        return $str;
    }

    /**
     * Get Lead fields list
     *
     * @return array
     */
    function getLeadFields() {
        global $app_strings;
        
        $lead = new ModelDef('Lead');
        $field_defs = $lead->getFieldDefinitions();
        $fields = array();

        foreach ($field_defs as $field_def) {

            if (preg_match('/category(\d+)/', $field_def['name']) || $field_def['name'] == 'category') {
                continue;
            }

            if( ( $field_def['type'] == 'ref' && empty($field_def['custom_type']) )
                || $field_def['type'] == 'assigned_user' || $field_def['type'] == 'link'
                || (isset($field_def['source'])  && $field_def['source'] == 'non-db') || $field_def['type'] == 'id') {

                continue;
            }

            if($field_def['name'] == 'deleted' || $field_def['name'] == 'converted' || $field_def['name'] == 'date_entered'
                || $field_def['name'] == 'date_modified' || $field_def['name'] == 'modified_user_id'
                || $field_def['name'] == 'assigned_user_id' || $field_def['name'] == 'created_by'
                || $field_def['name'] == 'team_id' || $field_def['name'] == 'name') {

                continue;
            }

            $field_def['vname'] = preg_replace('/:$/','',translate($field_def['vname'],'Leads'));

            $col_arr = array();
            if((isset($field_def['required']) && $field_def['required'] != null && $field_def['name'] != 'status') || $field_def['name'] == 'last_name') {
                $cols_name = $field_def['vname'] .' '. $app_strings['LBL_REQUIRED_SYMBOL'];
                $col_arr[0] = $field_def['name'];
                $col_arr[1] = $cols_name;
                $col_arr[2] = true;
            } else {
                $cols_name = $field_def['vname'];
                $col_arr[0] = $field_def['name'];
                $col_arr[1] = $cols_name;
            }

            if (! in_array($cols_name, $fields))
                array_push($fields, $col_arr);
        }

        return $fields;
    }

    /**
     * Check if custom field radiobutton or not
     *
     * @param string $custom_field_name
     * @return null|array
     */
    function ifRadioButton($custom_field_name) {
        $cust_row = null;
        $lq = new ListQuery('DynField', array('id', 'data_type'));
        $lq->addSimpleFilter('name', $custom_field_name);
        $result = $lq->runQuerySingle();

        if (! $result->failed) {
            if($result->row != null && $result->row['data_type'] == 'radioenum') {
                $cust_row = $result->row;
            }
        }

        return $cust_row;
    }

	function loadUpdateRequest(RowUpdate &$update, array $input) {}
	
	function validateInput(RowUpdate &$update) {
		return true;
	}
	
	function afterUpdate(RowUpdate &$update) {}
}
?>
