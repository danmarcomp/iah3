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
require_once('modules/Campaigns/utils.php');

class EmailSetupWidget extends FormTableSection {

    const PREFERENCES_CATEGORY = 'global';

    const CAMPAIGN_EMAILS_PER_RUN = 500;

    /**
     * @var Administration
     */
    var $administration;

    function init($params, $model=null) {
        $administration = new Administration();
        $administration->retrieveSettings('notify');
        $administration->retrieveSettings('mail');
        $administration->retrieveSettings('massemailer');
        $this->administration = $administration;
        parent::init($params, $model);
    }

	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, array $context) {
		if($gen->getLayout()->getEditingLayout()) {
			$params = array();
			return $this->renderOuterTable($gen, $parents, $context, $params);		
		}

        return $this->renderSetupView($gen);
	}
	
	function getRequiredFields() {
        $fields = array();

		return $fields;
	}

    /**
     * Render Email Setup wizard
     *
     * @param HtmlFormGenerator $gen
     * @return string
     */
    function renderSetupView(HtmlFormGenerator &$gen) {
        global $mod_strings, $app_strings, $app_list_strings;

        $layout =& $gen->getLayout();
        $layout->addScriptInclude('modules/Campaigns/wizard.js');
        $layout->addScriptInclude('modules/Campaigns/email.js');
        $layout->addScriptLiteral("var LOCATION_DEFAULT='".$mod_strings['TRACKING_ENTRIES_LOCATION_DEFAULT_VALUE']."'", LOAD_PRIORITY_FOOT);

        $notify_fromaddress = $this->getPreference('notify_from_address');
        $notify_fromname = $this->getPreference('notify_from_name');
        $mail_smtpserver = $this->getPreference('mail_smtpserver');
        $mail_smtpport = $this->getPreference('mail_smtpport');
        $mail_smtpuser = $this->getPreference('mail_smtpuser');
        $mail_smtppass = $this->getPreference('mail_smtppass');

        $spec = array('name' => 'mail_sendtype', 'options' => $app_list_strings['notifymail_sendtype'],
            'onchange' => "notify_setrequired();", 'options_add_blank' => false);
        $selected_sendtype = $this->getPreference('mail_sendtype');
        if (! $selected_sendtype)
            $selected_sendtype = 'sendmail';
        $sendtype_select = $gen->form_obj->renderSelect($spec, $selected_sendtype);

        $smtp_auth_req_check = $this->renderCheckBox($gen, 'mail_smtpauth_req', $this->getPreference('mail_smtpauth_req'), "notify_setrequired();");

        $location_type = $this->getPreference('massemailer_tracking_entities_location_type');

        if (empty($location_type) || $location_type == '1') {
            $default_checked = "checked";
            $location_state = "disabled";
            $userdefined_checked = "";
            $location = $mod_strings['TRACKING_ENTRIES_LOCATION_DEFAULT_VALUE'];
        } else  {
            $default_checked = "";
            $location_state = "";
            $userdefined_checked = "checked";
            $location = $this->getPreference('massemailer_tracking_entities_location');
        }

        $email_copy = $this->getPreference('massemailer_email_copy');

        if (empty($email_copy) || $email_copy == '1') {
            $yes_checked = "checked='checked'";
            $no_checked = "";
        } else  {
            $yes_checked = "";                        
            $no_checked = "checked='checked'";
        }

        $emails_per_run = $this->getPreference('massemailer_campaign_emails_per_run');
        if (empty($emails_per_run))
            $emails_per_run = EmailSetupWidget::CAMPAIGN_EMAILS_PER_RUN;

        $user_mailboxes = $this->renderMailboxesList();
        $create_mbox_check = $this->renderCheckBox($gen, 'create_mbox', $user_mailboxes['mbox_needed'], "notify_setrequired();");

        $mailbox_edit = $this->renderMailboxEdit($gen);

        $body = <<<EOQ
            <input type='hidden' name='campaign_type' value="{$mod_strings['LBL_NEWSLETTER']}" />
            <input type="hidden" id="wiz_total_steps" name="totalsteps" value="3" />
            <input type="hidden" id="wiz_current_step" name="currentstep" value="0" />
            <input type="hidden" id="wiz_summary_step" name="wiz_summary_step" value="create_email_setup_summary" />
            <p><div id='buttons'>
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 2px">
            <tr>
            <td align="left" width='30%'>
            <table border="0" cellspacing="0" cellpadding="0" ><tr>
                <td><div id="back_button_div"><input id="wiz_back_button" type='button' title="{$app_strings['LBL_BACK']}" class="input-button" onclick="navigate('back');"  name="back" value="{$app_strings['LBL_BACK']}" />&nbsp;</div></td>
                <td><div id="cancel_button_div"><input id="wiz_cancel_button" title="{$app_strings['LBL_CANCEL_BUTTON_TITLE']}" accessKey="{$app_strings['LBL_CANCEL_BUTTON_KEY']}" class="input-button" onclick="this.form.action.value='index'; this.form.module.value='Campaigns';" type="submit" name="button" value="{$app_strings['LBL_CANCEL_BUTTON_LABEL']}" />&nbsp;</div></td>
                <td><div id="save_button_div"><input id="wiz_submit_button" title="{$app_strings['LBL_SAVE_BUTTON_TITLE']}" accessKey="{$app_strings['LBL_SAVE_BUTTON_KEY']}" class="input-button" onclick="return SUGAR.ui.sendForm(document.forms.DetailForm, {'record_perform':'save'}, null);" type="submit" name="button" value="{$app_strings['LBL_SAVE_BUTTON_LABEL']}" />&nbsp;</div></td>
                <td><div id="next_button_div"><input id="wiz_next_button" type='button' title="{$app_strings['LBL_NEXT_BUTTON_LABEL']}" class="input-button" onclick="navigate('next');create_summary();" name="button" value="{$app_strings['LBL_NEXT_BUTTON_LABEL']}" />&nbsp;</div></td>
            </tr></table>
            </td>
            <td align="right" width='40%'><div id='wiz_location_message'></td>
            </tr>
            </table>
            </div></p>
            <table class='tabDetailView'  width="100%" border="0" cellspacing="3" cellpadding="0">
            <tr>
            <td class='tabDetailViewDF'  width="10%" valign='top'>
            <div id='nav'>
            <table  border="0" cellspacing="0" cellpadding="0" width="100%" >
            <tr><td class='tabDetailViewDL2' nowrap><div id='nav_step1'>{$mod_strings['LBL_NAVIGATION_MENU_SETUP']}</div></td></tr>
            <tr><td class='tabDetailViewDL2' nowrap><div id='nav_step2'>{$mod_strings['LBL_NAVIGATION_MENU_NEW_MAILBOX']}</div></td></tr>
            <tr><td class='tabDetailViewDL2' nowrap><div id='nav_step3'>{$mod_strings['LBL_NAVIGATION_MENU_SUMMARY']}</div></td></tr>
            </table>
            </div>
            </td>
            <td  class='tabForm'  width='100%'>
            <div id="wiz_message"></div>
            <div id=wizard>
            <div id='step1'><table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
            <th colspan="2" align="left" class="dataField"><h4 class="dataLabel">{$mod_strings['LBL_NAVIGATION_MENU_SETUP']}</h4></th>
            </tr>
            <tr>
            <td class="dataLabel"><span sugar='slot1'>
            {$mod_strings['LBL_EMAIL_SETUP_DESC']}
            </span sugar='slot'></td></tr>
            <tr><td>&nbsp;</td></tr>
            <tr><td>
            <table width="100%" border="0" cellspacing="0" cellpadding="0" >
            <tr><td>
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
            <td width="20%" class="dataLabel">{$mod_strings['LBL_WIZ_FROM_NAME']} <span class="required">{$app_strings['LBL_REQUIRED_SYMBOL']}</span></td>
            <td width="30%" class="dataField"><input name="notify_from_name" tabindex='1' size='25' maxlength='128' type="text" id="notify_fromname" value="{$notify_fromname}" title="{$mod_strings['LBL_WIZ_FROM_NAME']}" /></td>
            </tr>
            <tr>
            <td class="dataLabel">{$mod_strings['LBL_MAIL_SENDTYPE']}</td>
            <td class="dataField">{$sendtype_select}</td>
            </tr>
            <tr>
            <td class="dataLabel">{$mod_strings['LBL_WIZ_FROM_ADDRESS']} <span class="required">{$app_strings['LBL_REQUIRED_SYMBOL']}</span></td>
            <td class="dataField"><input name='notify_from_address' id='notify_fromaddress' tabindex='1' size='25' maxlength='128' type="text" value="{$notify_fromaddress}" title="{$mod_strings['LBL_WIZ_FROM_ADDRESS']}" /></td></tr>
            <tr>
            <td colspan="4">
                <div id="smtp_settings">
                <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="20%" class="dataLabel">{$mod_strings['LBL_MAIL_SMTPSERVER']} <span class="required">{$app_strings['LBL_REQUIRED_SYMBOL']}</span></td>
                    <td width="30%" class="dataField"><input type="text"  title="{$mod_strings['LBL_MAIL_SMTPSERVER']}"  name="mail_smtpserver"  id='mail_smtpserver'  tabindex="1" size="25" maxlength="64" value="{$mail_smtpserver}"></td>
                    <td width="20%" class="dataLabel">{$mod_strings['LBL_MAIL_SMTPPORT']} <span class="required">{$app_strings['LBL_REQUIRED_SYMBOL']}</span></td>
                    <td width="30%" class="dataField"><input type="text" id='mail_smtpport' name="mail_smtpport" title="{$mod_strings['LBL_MAIL_SMTPPORT']}" tabindex="1" size="5" maxlength="5" value="{$mail_smtpport}"></td>
                </tr>
                <tr>
                    <td class="dataLabel">
                        {$mod_strings['LBL_MAIL_SMTPAUTH_REQ']}
                    </td>
                    <td colspan="3">{$smtp_auth_req_check}</td>
                </tr>
                <tr>
                <td colspan="4">
                <div id="smtp_auth">
                <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="20%" class="dataLabel">{$mod_strings['LBL_MAIL_SMTPUSER']} <span class="required">{$app_strings['LBL_REQUIRED_SYMBOL']}</span></td>
                    <td width="30%" class="dataField"><input type="text" name="mail_smtpuser" id='mail_smtpuser' title='{$mod_strings['LBL_MAIL_SMTPUSER']}' size="25" maxlength="64" value="{$mail_smtpuser}" tabindex='1' ></td>
                    <td width="20%" class="dataLabel">{$mod_strings['LBL_MAIL_SMTPPASS']} <span class="required">{$app_strings['LBL_REQUIRED_SYMBOL']}</span></td>
                    <td width="30%" class="dataField"><input type="password" title="{$mod_strings['LBL_MAIL_SMTPPASS']}" name="mail_smtppass" id='mail_smtppass' size="25" maxlength="64" value="{$mail_smtppass}" tabindex='1'></td>
                </tr>
                </table>
                </div>
                </td>
                </tr>
                </table>
                </div>
            </td>
            </tr>
            </table>
            </td></tr>
            </table>
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr><td>&nbsp;</td></tr>
            <tr><td>&nbsp;</td></tr>
            <tr><td><h4 class="dataLabel">{$mod_strings['LBL_NOTIFY_TITLE']}</h4></td></tr>
            <tr><td>
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
            <td width="40%" class="dataLabel">{$mod_strings['LBL_EMAILS_PER_RUN']}&nbsp;<span class="required">{$app_strings['LBL_REQUIRED_SYMBOL']}</span></td>
            <td width="50%" class="dataField"><input name='massemailer_campaign_emails_per_run' id='massemailer_campaign_emails_per_run' title="{$mod_strings['LBL_EMAILS_PER_RUN']}" tabindex='1' maxlength='128' size="5" type="text" value="{$emails_per_run}"></td>
            </tr>
            <tr>
            <td class="dataLabel">{$mod_strings['LBL_LOCATION_TRACK']}&nbsp;<span class="required">{$app_strings['LBL_REQUIRED_SYMBOL']}</span></td>
            <td class="dataField"><label><input type='radio' onclick="change_state(this);" name='massemailer_tracking_entities_location_type' id='massemailer_tracking_entities_location_type' title="{$mod_strings['LBL_DEFAULT_LOCATION']}" value="1" {$default_checked}> {$mod_strings['LBL_DEFAULT_LOCATION']}</label>&nbsp;
            <label><input type='radio' {$userdefined_checked} onclick="change_state(this);" name='massemailer_tracking_entities_location_type' id='massemailer_tracking_entities_location_type' title="{$mod_strings['LBL_CUSTOM_LOCATION']}" value="2"> {$mod_strings['LBL_CUSTOM_LOCATION']}</label>
            </tr><tr>
            <td class="dataLabel"></td>
            <td class="dataField"><input name='massemailer_tracking_entities_location' id='massemailer_tracking_entities_location' title="{$mod_strings['LBL_LOCATION_TRACK']}" {$location_state} maxlength='128' size='40' type="text" value="{$location}"></td>
            </tr>
            <tr>
            <td class="dataLabel">{$mod_strings['LBL_CAMP_MESSAGE_COPY']}&nbsp;<span class="required">{$app_strings['LBL_REQUIRED_SYMBOL']}</span></td>
            <td class="dataField"><label><input type='radio'  name='massemailer_email_copy' value="1" {$yes_checked}> {$mod_strings['LBL_YES']}</label>&nbsp;<label><input type='radio' {$no_checked} name='massemailer_email_copy' value="2"> {$mod_strings['LBL_NO']}</label>
            </tr>
            </table>
            </td></tr></table>
            </td></tr></table>
            </div>
            <div id='step2'><table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
            <td  colspan="2"><h3>{$mod_strings['LBL_NAVIGATION_MENU_NEW_MAILBOX']}</h3></td>
            <td colspan="2">&nbsp;</td>
            </tr>
            <tr>
            <td class="dataLabel" colspan ='3'>{$user_mailboxes['mailboxes']}</td></td>&nbsp;</td>
            </tr>
            <tr><td class="dataLabel"><input type='hidden' id='wiz_new_mbox' name='wiz_new_mbox' value='0'>
            {$create_mbox_check}&nbsp;{$mod_strings['LBL_CREATE_MAILBOX']}</td>
            <td colspan='3'>&nbsp;</td></tr>
            </table>
            <div id="new_mbox">{$mailbox_edit}</div>
            </div>
            <div id='step3'>
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr><td>
                <h3>{$mod_strings['LBL_NAVIGATION_MENU_SUMMARY']}</h3>
            </td></tr>
            <tr><td>
                <div id='wiz_summ'></div>
            </td></tr>
            </table>
            </div>
            </div>
            </td>
            </tr>
            </table>
EOQ;

        return $body;
    }

    /**
     * Render user mailboxes list
     *
     * @return array
     */
    function renderMailboxesList() {
        global $mod_strings, $even_bg, $odd_bg, $hilite_bg;
        $mbox = CampaignUtils::get_user_mailboxes();
        $need_mbox = 0;

        $mbox_table = "<table class='listView' width='100%' border='0' cellspacing='0' cellpadding='1'>";

        if (count($mbox) > 0) {
            $mbox_table .= "<tr><td colspan='5'><b>" .count($mbox) ." ". $mod_strings['LBL_MAILBOX_CHECK_WIZ_GOOD']." </b>.</td></tr>";
            $mbox_table .= "<tr><td width='20%' class='listViewThS1'><b>".$mod_strings['LBL_MAILBOX_NAME']."</b></td>"
                .  " <td width='20%' class='listViewThS1'><b>".$mod_strings['LBL_LOGIN']."</b></td>"
                .  " <td width='20%' class='listViewThS1'><b>".$mod_strings['LBL_MAILBOX']."</b></td>"
                .  " <td width='20%' class='listViewThS1'><b>".$mod_strings['LBL_SERVER_URL']."</b></td>"
                .  " <td width='20%' class='listViewThS1'><b>".$mod_strings['LBL_LIST_STATUS']."</b></td></tr>";

            $colorclass = ' ';

            foreach ($mbox as $details) {

                if ( $colorclass == "class='evenListRowS1' bgColor='$even_bg' ") {
                    $colorclass = "class='oddListRowS1' bgColor='$odd_bg' ";
                    $bgColor = $odd_bg;
                } else {
                    $colorclass = "class='evenListRowS1' bgColor='$even_bg' ";
                    $bgColor = $even_bg;
                }

                $mbox_table .= "<tr  onmouseover=\"setPointer(this, '', 'over', '$bgColor', '$hilite_bg', '');\" onmouseout=\"setPointer(this, '', 'out', '$bgColor', '$hilite_bg', '');\" onmousedown=\"setPointer(this, '', 'click', '$bgColor', '$hilite_bg', '');\">";
                $mbox_table .= "<td $colorclass>".$details['name']."</td>";
                $mbox_table .= "<td $colorclass>".$details['username']."</td>";
                $mbox_table .= "<td $colorclass>".$details['imap_folder']."</td>";
                $mbox_table .= "<td $colorclass>".$details['host']."</td>";
                $mbox_table .= "<td $colorclass>".array_get_default($details, 'status', '')."</td></tr>";
            }
        } else {
            $need_mbox = 1;
            $mbox_table .= "<tr><td colspan='5'><b>".$mod_strings['LBL_MAILBOX_CHECK_WIZ_BAD']." </b>.</td></tr>";
        }

        $mbox_table .= "</table>";
        
        return array('mbox_needed' => $need_mbox, 'mailboxes' => $mbox_table);
    }

    /**
     * Render block for creating new mailbox
     *
     * @param HtmlFormGenerator $gen
     * @return string
     */
    function renderMailboxEdit(HtmlFormGenerator &$gen) {
        global $mod_strings, $app_strings;

        $protocols = array('IMAP'=>'IMAP', 'POP3'=>'POP3');
        $spec = array('name' => 'protocol', 'options' => $protocols, 'onchange' => "hide_show_imap_folder();set_default_port();");
        $protocol_select = $gen->form_obj->renderSelect($spec, 'IMAP');

        $active_check = $this->renderCheckBox($gen, 'active', 1);
        $leave_check = $this->renderCheckBox($gen, 'leave_on_server', 1);
        $use_ssl_check = $this->renderCheckBox($gen, 'use_ssl', 0, "set_default_port();");

        $body = <<<EOQ
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">
            <tr>
                <td class="dataLabel"><slot>{$mod_strings['LBL_NAME']}&nbsp;<span class="required">{$app_strings['LBL_REQUIRED_SYMBOL']}</span></slot></td>
                <td class="dataField"><slot><input id="name" type="text" name='name' tabindex='1' size="30" value="" title="{$mod_strings['LBL_NAME']}"></slot></td>
                <td class="dataLabel"><slot>{$mod_strings['LBL_ACTIVE']}:</slot></td>
                <td class="dataField"><slot>{$active_check}</slot></td>
            </tr>
            <tr>
                <td class="dataLabel"><slot>{$mod_strings['LBL_FROM_NAME']}</slot></td>
                <td class="dataField"><slot><input type="text" name='from_name' tabindex='1' size="30" value=""></slot></td>
                <td class="dataLabel"><slot>{$mod_strings['LBL_EMAIL']}:&nbsp;<span class="required">{$app_strings['LBL_REQUIRED_SYMBOL']}</span></slot></td>
                <td class="dataField"><slot><input id="from_addr" type="text" name='email' tabindex='2' size="30" value="" title="{$mod_strings['LBL_EMAIL']}"></slot></td>
            </tr>
            <tr>
                <td class="dataLabel"><slot>{$mod_strings['LBL_HOST']}&nbsp;<span class="required">{$app_strings['LBL_REQUIRED_SYMBOL']}</span></slot></td>
                <td class="dataField"><slot><input id="server_url" type="text" name='host' tabindex='1' size="30" value="" title="{$mod_strings['LBL_HOST']}"></slot></td>
                <td class="dataLabel"><slot>{$mod_strings['LBL_USERNAME']}&nbsp;<span class="required">{$app_strings['LBL_REQUIRED_SYMBOL']}</span></slot></td>
                <td class="dataField"><slot><input id="email_user" type="text" name='username' tabindex='2' size="25" value="" title="{$mod_strings['LBL_USERNAME']}"></slot></td>
            </tr>
            <tr>
                <td class="dataLabel"><slot>{$mod_strings['LBL_PASSWORD']}&nbsp;<span class="required">{$app_strings['LBL_REQUIRED_SYMBOL']}</span></slot></td>
                <td class="dataField"><slot><input id="email_password" type="password" name='password' tabindex='1' size="25" value="" autocomplete="off" title="{$mod_strings['LBL_PASSWORD']}"></slot></td>
                <td class="dataLabel"><slot>{$mod_strings['LBL_LEAVE_ON_SERVER']}</slot></td>
                <td class="dataField"><slot>{$leave_check}</slot></td>
            </tr>
            <tr>
                <td class="dataLabel"><slot>{$mod_strings['LBL_PROTOCOL']}</slot></td>
                <td class="dataField"><slot>{$protocol_select}</slot></td>
                <td class="dataLabel" id="imap_folder_label"><slot>{$mod_strings['LBL_IMAP_FOLDER']}</slot></td>
                <td class="dataField" id="imap_folder_name"><slot><input id="mailbox" type="text" name='imap_folder' tabindex='2' size="25" value="INBOX"></slot></td>
            </tr>
            <tr>
                <td class="dataLabel"><slot>{$mod_strings['LBL_PORT']}</slot></td>
                <td class="dataField"><slot><input type="text" name='port' id="port" tabindex='1' size="8" value="143"></slot></td>
                <td class="dataLabel"><slot>{$mod_strings['LBL_USE_SSL']}</slot></td>
                <td class="dataField"><slot>{$use_ssl_check}</slot></td>
            </tr>
            </table>
EOQ;

        return $body;
    }

    /**
     * Get mail preference value
     *
     * @param  string $name - preference name
     * @return mixed
     */
    function getPreference($name) {
        if (isset($this->administration->settings[$name])) {
            return $this->administration->settings[$name];
        } else {
            return null;
        }
    }

    /**
     * Set new value for mail preference
     *
     * @param string $name - preference name
     * @param mixed $value
     * @return void
     */
    function setPreference($name, $value) {
		$prefix = Administration::get_config_prefix($name);
		AppConfig::set_local(join('.', $prefix), $value);
    }

    /**
     * Get users's email preferencies list
     *
     * @return array
     */
    function getPreferencesList() {
        $prefs = array('notify_from_address', 'notify_from_name', 'mail_smtpserver', 'mail_smtpport', 'mail_smtpuser',
            'mail_smtppass', 'mail_sendtype', 'mail_smtpauth_req', 'massemailer_tracking_entities_location_type',
            'massemailer_tracking_entities_location', 'massemailer_email_copy', 'massemailer_campaign_emails_per_run'
        );

        return $prefs;
    }

    /**
     * Render check box
     *
     * @param HtmlFormGenerator $gen
     * @param string $name
     * @param mixed $value
     * @param string $onchange
     * @return string
     */
    function renderCheckBox(HtmlFormGenerator &$gen, $name, $value, $onchange = null) {
        $spec = array('name' => $name);
        if ($onchange) $spec['onchange'] = $onchange;

        if (is_string($value) && $value == 'on') {
            $checked = 1;
        } elseif (is_string($value) && $value == 'off') {
            $checked = 0;
        } elseif (is_bool($value) && $value === true) {
            $checked = 1;
        } elseif (is_bool($value) && $value === false) {
            $checked = 0;
        } elseif (empty($value)) {
            $checked = 0;
        } else {
            $checked = $value;
        }

        return $gen->form_obj->renderCheck($spec, $checked);
    }

	function loadUpdateRequest(RowUpdate &$update, array $input) {
        $preferences = $this->getPreferencesList();
        $updated_prefs = array();

        for ($i = 0; $i < sizeof($preferences); $i++) {
            if (isset($input[$preferences[$i]]))
                $updated_prefs[$preferences[$i]] = $input[$preferences[$i]];
		}

        $update->setRelatedData($this->id.'_pref_rows', $updated_prefs);

        $mailbox_fields = array('name', 'active', 'from_name', 'email', 'host', 'username',
            'password', 'leave_on_server', 'protocol', 'imap_folder', 'port', 'use_ssl');

        $mailbox_data = array();

        if(isset($input['wiz_new_mbox']) && ($input['wiz_new_mbox'] == '1')) {

            for ($i = 0; $i < sizeof($mailbox_fields); $i++) {
                if (isset($input[$mailbox_fields[$i]]))
                    $mailbox_data[$mailbox_fields[$i]] = $input[$mailbox_fields[$i]]; 
            }

            if (sizeof($mailbox_data) > 0) {
                require_once('modules/EmailFolders/EmailFolder.php');
                $mailbox_data['scheduler'] = 1;
                $mailbox_data['mailbox_type'] = 'bounce';
                $mailbox_data['active'] = 1;
                $mailbox_data['user_id'] = '-1';
                $mailbox_data['email_folder_id'] = EmailFolder::get_std_folder_id('-1', STD_FOLDER_CAMPAIGN);

                $update->setRelatedData($this->id.'_mbox_rows', $mailbox_data);
            }
        }

    }
	
	function validateInput(RowUpdate &$update) {
		return true;
	}
	
	function afterUpdate(RowUpdate &$update) {
        $pref_updates = $update->getRelatedData($this->id.'_pref_rows');

        if (sizeof($pref_updates) > 0) {
            $preferences = $this->getPreferencesList();
            for ($i = 0; $i < sizeof($preferences); $i++) {
                if (isset($pref_updates[$preferences[$i]])) {
                    $this->setPreference($preferences[$i], $pref_updates[$preferences[$i]]);
				}
			}
			AppConfig::save_local();
            AppConfig::invalidate_cache('notify');
            AppConfig::invalidate_cache('mail');
            AppConfig::invalidate_cache('massemailer');
        }

        $mbox_data = $update->getRelatedData($this->id.'_mbox_rows');

        if (is_array($mbox_data) && sizeof($mbox_data) > 0) {
            require_once('modules/EmailPOP3/EmailPOP3.php');
            EmailPOP3::create_mbox($mbox_data);
        }
    }
}
?>
