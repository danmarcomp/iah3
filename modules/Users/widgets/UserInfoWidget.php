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
require_once('modules/Calendar/CalendarGoogleSync.php');
require_once 'include/config/format/ConfigParser.php';

define('HOLYDAYS_SCAN', true);

class UserInfoWidget extends FormTableSection {

    /**
     * @var string - 'view' or 'edit'
     */
    var $context;

    /**
     * @var User
     */
    var $user;

    /**
     * @var Localization
     */
    var $locale;

    /**
     * @var FieldFormatter
     */
	var $formatter;

	var $pdfMode = false;

	var $pdf;

    const PREFERENCES_CATEGORY = 'global';

    const DEFAULT_MAIL_SENDTYPE = 'sendmail';

    const DEFAULT_SMTP_PORT = 25;

	function init($params, $model=null) {
        $this->user = new User();
        $this->setUserId(array_get_default($_REQUEST, 'record'));
		parent::init($params, $model);
	}
	
    /**
     *
     * @param  string $id
     * @return void
     */
    function setUserId($id) {
        if ($this->user instanceof User)
            $this->user->id = $id;
    }

    /**
     *
     * @param  User $user
     * @return void
     */
    function setLocale($user) {
        $this->locale = new Localization($user);
    }

    /**
     * @param HtmlFormGenerator $gen
     * @return void
     */
    function setFormatter(FormGenerator $gen) {
        $this->formatter = new FieldFormatter($gen->format, $gen->getLayout()->getType());
    }

	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, array $context) {
		if($gen->getLayout()->getEditingLayout()) {
			$params = array();
			return $this->renderOuterTable($gen, $parents, $context, $params);		
		}

        $this->setUserId($row_result->getField('id'));
        $this->setLocale($this->user);
        $this->setFormatter($gen);
		$lstyle = $gen->getLayout()->getType();

		if($lstyle == 'editview') {
            $this->context = 'edit';
			if (!$this->isAdmin() && !$this->isCurrent())
				$this->showError();
			return $this->renderHtmlEdit($gen, $row_result);
		} else {
            $this->context = 'view';
            return $this->renderHtmlView($gen, $row_result);
        }
	}
	
	function getRequiredFields() {
        $fields = array('in_directory', 'receive_notifications', 'receive_case_notifications',
            'week_start_day', 'day_begin_hour', 'day_end_hour', 'email1', 'email2',
            'is_admin', 'portal_only', 'photo_filename', 'user_name');

		return $fields;
	}
	
	function renderHtmlView(HtmlFormGenerator &$gen, RowResult &$row_result) {
        $style = '';
        $top_sections = array('locale_settings', 'email_settings', 'calendar_settings', 'acl_settings');

        if (! in_array($this->id, $top_sections))
            $style = 'style="margin-top: 0.5em"';

		$title = to_html($this->getLabel());
        $body = '<table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabDetailView" '.$style.'>';
        $body .= '<tr><th class="tabDetailViewDL" colspan="4" align="left"><h4 class="tabDetailViewDL">'.$title.'</h4></th></tr>';

        $body .= $this->getBody($gen, $row_result);

        $body .= "</table>";

        return $body;
	}

	function renderHtmlEdit(HtmlFormGenerator &$gen, RowResult &$row_result) {
        $layout = array_get_default($_REQUEST, 'layout', 'Standard');
        if ($row_result->new_record && $layout != 'Standard')
            $this->showError();

        $form_layout =& $gen->getLayout();
        $form_layout->addScriptInclude('modules/Users/User.js', LOAD_PRIORITY_BODY);
        if ($this->id == 'user_settings')
            $form_layout->addScriptLiteral("enable_change_password_button();", LOAD_PRIORITY_FOOT);

        $style = '';
        $top_sections = array('locale_settings', 'email_settings', 'calendar_settings', 'acl_settings');

        if (! in_array($this->id, $top_sections))
            $style = 'style="margin-top: 0.5em"';

		$title = to_html($this->getLabel());
        $body = '<table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm" '.$style.'>';
        $body .= '<tr><th class="dataLabel" colspan="4" width="100%" align="left"><h4 class="dataLabel">'.$title.'</h4></th></tr>';

        $body .= $this->getBody($gen, $row_result);

        $body .= "</table>";

        return $body;
	}

    function getBody(FormGenerator &$gen, RowResult &$row_result) {
        $body = '';
        
        if($this->id != 'user_settings' && $this->id != 'user_photo' && ! $this->isAdmin() && ! $this->isCurrent()) {
			$this->showError();
		}

        switch ($this->id) {
            case "user_settings":
                $body .= $this->renderSettings($gen, $row_result);
                break;
            case "user_password":
                $body .= $this->renderPassword($gen);
                break;
            case "locale_settings":
                $body .= $this->renderLocale($gen, $row_result);
                break;
            case "layout_settings":
                $body .= $this->renderLayout($gen, $row_result);
                break;
            case "email_settings":
                $body .= $this->renderEmail($gen, $row_result);
                break;
            case "outbound_email":
                $body .= $this->renderOutboundEmail($gen);
                break;
            case "calendar_settings":
                $body .= $this->renderCalendar($gen, $row_result);
                break;
            case "google_account_settings":
                $body .= $this->renderGoogleAccount($gen);
                break;
            case "google_calendar_settings":
                $body .= $this->renderGoogleCalendar($gen);
                break;
            case "google_documents_settings":
                $body .= $this->renderGoogleDocuments($gen);
                break;
            case "google_contacts_settings":
                $body .= $this->renderGoogleContacts($gen);
                break;
            case "acl_settings":
                if ($this->isAdmin()) {
                    $body .= $this->renderAcl($gen, $row_result);
                } else {
                    $this->showError();
                }
                break;
            default:
                break;
        }

        return $body;
    }

    /**
     * Render html for 'User Settings' view / edit section
     *
     * @param FormGenerator $gen
     * @param RowResult $row_result
     * @return string
     */
    function renderSettings(FormGenerator &$gen, RowResult &$row_result) {
        global $app_list_strings, $mod_strings;

        $empty_row = array();
        $spec = array('type' => 'bool', 'name' => '');

        $reminder_time = $this->getPreference('reminder_time');
        if ($reminder_time == -1) $reminder_time = '';
        $reminder_msg = empty($reminder_time) ? '' : translate('reminder_time_options', '', $reminder_time);
        $display_reminder = ($reminder_msg != '') ? 1 : 0;

        $default_export_format = $this->locale->getPrecedentPreference('default_export_format');
        $export_formats = $this->locale->getExportFormatSelect();
        $export_charset = $this->locale->getExportCharset(false);
		$charsets = $this->locale->getCharsetSelect();
		$charsets = array('' =>$mod_strings['LBL_DEFAULT_CHARSET']) + $charsets;

        $use_real_name = $this->getPreference('use_real_names') ? 'on' : 'off';
        
		$user_mode = $this->getPreference('file_download_mode');
		$user_mode = ($user_mode != null) ? $user_mode : 'save';
		$download_mods = array('save' => $mod_strings['LBL_DOWNLOAD_SAVE'], 'open' => $mod_strings['LBL_DOWNLOAD_OPEN']);        

        if ($this->context == 'edit') {
            $in_directory = $gen->form_obj->renderField($row_result, 'in_directory');
            $notifications = $gen->form_obj->renderField($row_result, 'receive_notifications');
            $case_notifications = $gen->form_obj->renderField($row_result, 'receive_case_notifications');

            $display_style = 'none;';
            if ($display_reminder) $display_style = 'inline;';
            if ($reminder_time == '') $reminder_time = -1;
            $reminder_time_select = $this->renderSelect($gen, 'reminder_time', 'reminder_time_options', $reminder_time);
            $reminder = $this->renderCheckBox($gen, 'should_remind', $display_reminder, 'toggleDisplay("should_remind_list");') . '&nbsp;<span id="should_remind_list" style="display: '.$display_style.'">'. $reminder_time_select . '</span>';

            $default_export_format = $this->renderSelect($gen, array('name' => 'default_export_format'), $export_formats, $default_export_format, null, null, false);
            $export_charset = $this->renderSelect($gen, array('name' => 'default_export_charset', 'width' => 24), $charsets, $export_charset, null, null, false);

            $show_real_name = $this->renderCheckBox($gen, 'use_real_names', $use_real_name);

            $download_mode = $this->renderSelect($gen, 'file_download_mode', $download_mods, $user_mode, null, null, false);
        } else {
            $in_directory = $row_result->getField('in_directory', '', true);
            $notifications = $row_result->getField('receive_notifications', '', true);
            $case_notifications = $row_result->getField('receive_case_notifications', '', true);

            $spec['name'] = 'should_remind';
            $reminder = $this->formatter->formatRowValue($spec, $empty_row, $display_reminder) .'&nbsp;'. $reminder_msg;

            $show_real_name = $app_list_strings['dom_switch_bool'][$use_real_name];
            $default_export_format = array_get_default($export_formats, $default_export_format, $default_export_format);
            $export_charset = array_get_default($charsets, $export_charset, $export_charset);
            
            $download_mode = $download_mods[$user_mode];
        }

        $row_data = array();
        array_push($row_data, array('label' => 'LBL_IN_DIRECTORY', 'value' => $in_directory, 'description' => 'LBL_IN_DIRECTORY_TEXT'));
        array_push($row_data, array('label' => 'LBL_RECEIVE_NOTIFICATIONS', 'value' => $notifications, 'description' => 'LBL_RECEIVE_NOTIFICATIONS_TEXT'));
        array_push($row_data, array('label' => 'LBL_RECEIVE_CASE_NOTIFICATIONS', 'value' => $case_notifications, 'description' => 'LBL_RECEIVE_CASE_NOTIFICATIONS_TEXT'));
        array_push($row_data, array('label' => 'LBL_REMINDER', 'value' => $reminder, 'description' => 'LBL_REMINDER_TEXT'));
        array_push($row_data, array('label' => 'LBL_EXPORT_FORMAT', 'value' => $default_export_format, 'description' => 'LBL_EXPORT_FORMAT_DESC'));
        array_push($row_data, array('label' => 'LBL_EXPORT_CHARSET', 'value' => $export_charset, 'description' => 'LBL_EXPORT_CHARSET_DESC'));
        array_push($row_data, array('label' => 'LBL_USE_REAL_NAMES', 'value' => $show_real_name, 'description' => 'LBL_USE_REAL_NAMES_DESC'));
		array_push($row_data, array('label' => 'LBL_DOWNLOAD_MODE', 'value' => $download_mode, 'description' => 'LBL_DOWNLOAD_MODE_DESC'));

        return $this->renderRowWithDesc($row_data);
    }


    /**
     * Render html for 'User Password' view / edit section
     *
     * @param FormGenerator $gen
     * @return string
     */
    function renderPassword(FormGenerator &$gen) {
        global $mod_strings;
        $row_data = array();

        $spec = array('type' => 'password', 'required' => true, 'len' => 30, 'customValidate' => 'alert(1);', 'label' => $mod_strings['LBL_NEW_PASSWORD1']);
        array_push($row_data, array('label' => 'LBL_NEW_PASSWORD1', 'value' => $this->renderTextInput($gen, 'password', '', $spec), 'required' => true));

        $spec['label'] = $mod_strings['LBL_NEW_PASSWORD2'];
        array_push($row_data, array('label' => 'LBL_NEW_PASSWORD2', 'value' => $this->renderTextInput($gen, 'confirm_password', '', $spec), 'required' => true));

        return $this->renderRow($row_data);
    }

    /**
     * Render html for 'Locale Settings' view / edit section
     *
     * @param FormGenerator $gen
     * @param RowResult $row_result
     * @return string
     */
    function renderLocale(FormGenerator &$gen, RowResult &$row_result) {
        global $timedate, $mod_strings, $app_list_strings;

        $default_date_format = $this->getPreference('default_date_format');
        $default_time_format = $this->getPreference('default_time_format');
        $date_formats = AppConfig::setting('locale.date_formats');
        $time_formats = AppConfig::setting('locale.time_formats');
        $user_timezone = $this->getPreference('timezone');

        $current_addr_format = '';
        $holiday_selected =  $this->getPreference('user_holidays');
        $holidays_list = $this->getHolidays();

        if ($this->context == 'edit') {
            $user_date_format = $this->renderSelect($gen, 'dateformat', AppConfig::setting('locale.date_formats'), $default_date_format, null, null, false);
            $user_time_format = $this->renderSelect($gen, 'timeformat', AppConfig::setting('locale.time_formats'), $default_time_format, null, null, false);
            $timezone = $this->renderSelect($gen, 'timezone', $timedate->getTimeZones(true, false), $user_timezone, null, true);

            require_once('modules/Currencies/ListCurrency.php');
            $list_currency = new ListCurrency();
            $user_currency = ($this->getPreference('currency')) ? $this->getPreference('currency') : '-99';
            $currencies = $this->renderSelect($gen, 'currency', $list_currency->getCurrenciesNames(), $user_currency);

            $number_format = $this->renderSelect($gen, 'default_number_format', $this->locale->getLocaleNumberFormatOptions(), $this->locale->getLocaleNumberFormatName(), null, null, false);
            $name_format = '<input onkeyup="setPreview();" onkeydown="setPreview();" id="default_locale_name_format" type="text" name="default_locale_name_format" value="' .$this->locale->getLocaleFormatMacro(). '" />';

            $current_addr_format = $this->locale->getLocaleAddressFormatName();
            $addr_options = $this->locale->getLocaleAddressFormatInfo('display');
            $addr_options = $addr_options['options'];
            $address_format = $this->renderSelect($gen, 'default_address_format', $addr_options, $current_addr_format, 'update_address_format(this.value, "sampleAddress");', null, false);

            $week_start_day = $this->renderSelect($gen, 'week_start_day', $app_list_strings['weekdays_long_dom'], $row_result->getField('week_start_day'), null, null, false);
            $hours = $this->getHoursList();
            $day_begin_hour = $this->renderSelect($gen, 'day_begin_hour', $hours, $row_result->getField('day_begin_hour'), null, null, false);
            $day_end_hour = $this->renderSelect($gen, 'day_end_hour', $hours, $row_result->getField('day_end_hour'), null, null, false);
            $user_holidays = $this->renderSelect($gen, 'user_holidays', $holidays_list, $holiday_selected);

        } else {

            $user_date_format = $date_formats[$default_date_format];
            $user_time_format = $time_formats[$default_time_format];

            $timezone = $timedate->formatTimeZoneId($user_timezone);
            $currencies = $this->getDefaultCurrency();

            $number_format = $this->locale->sampleFormatNumber();
            $name_format = User::getLocaleFormatDesc($this->pdfMode);
            $address_format = $this->getAddressFormat();
            
            $week_start_day = $app_list_strings['weekdays_long_dom'][$row_result->getField('week_start_day', '', true)];
            $day_begin_hour = format_decimal_time($row_result->getField('day_begin_hour'));
            $day_end_hour = format_decimal_time($row_result->getField('day_end_hour'));
            $user_holidays = array_get_default($holidays_list, $holiday_selected, $mod_strings['LBL_DEFAULT_HOLIDAYS']);
        }

        $row_data = array();
        array_push($row_data, array('label' => 'LBL_DATE_FORMAT', 'value' => $user_date_format, 'description' => 'LBL_DATE_FORMAT_TEXT'));
        array_push($row_data, array('label' => 'LBL_TIME_FORMAT', 'value' => $user_time_format, 'description' => 'LBL_TIME_FORMAT_TEXT'));
        array_push($row_data, array('label' => 'LBL_TIMEZONE', 'value' => $timezone, 'description' => 'LBL_TIMEZONE_TEXT'));

        if ($this->context == 'edit' && $this->isAdmin() && !$this->isCurrent()) {
            $ut = (! $this->getPreference('ut')) ? 1 : 0;
            $row_data[3] = array('label' => 'LBL_PROMPT_TIMEZONE', 'value' => $this->renderCheckBox($gen, 'ut', $ut), 'description' => 'LBL_PROMPT_TIMEZONE_TEXT');
        }

        array_push($row_data, array('label' => 'LBL_CURRENCY', 'value' => $currencies, 'description' => 'LBL_CURRENCY_TEXT'));
        array_push($row_data, array('label' => 'LBL_NUMBER_FORMAT', 'value' => $number_format, 'description' => 'LBL_NUMBER_FORMAT_TEXT'));

        if ($this->context == 'edit') {
            $description = $mod_strings['LBL_LOCALE_NAME_FORMAT_DESC'] .'<br />' .$mod_strings['LBL_LOCALE_NAME_FORMAT_DESC_2'];
            array_push($row_data, array('label' => 'LBL_LOCALE_DEFAULT_NAME_FORMAT', 'value' => $name_format, 'description' => $description, 'desc_rowspan' => 2));
            $name_example = '<input tabindex="4" type="text" name="no_value" id="nameTarget" value="" disabled />';
            array_push($row_data, array('label' => 'LBL_LOCALE_EXAMPLE_NAME_FORMAT', 'value' => $name_example, 'description' => ''));
        } else {
            array_push($row_data, array('label' => 'LBL_LOCALE_DEFAULT_NAME_FORMAT', 'value' => $name_format, 'description' => 'LBL_LOCALE_NAME_FORMAT_DESC'));
        }

        array_push($row_data, array('label' => 'LBL_ADDRESS_FORMAT', 'value' => $address_format, 'description' => 'LBL_ADDRESS_FORMAT_DESC'));

        if ($this->context == 'edit') {
            $addr_format_data = $this->locale->getLocaleAddressFormatSelector($current_addr_format);
            $layout =& $gen->getLayout();
            $layout->addScriptLiteral($addr_format_data['JAVASCRIPT'], LOAD_PRIORITY_FOOT);
            $sample = '<span id="sampleAddress" style="font-style: italic">' .$addr_format_data['SAMPLE']. '</span>';
            array_push($row_data, array('label' => 'LBL_ADDRESS_FORMAT_EXAMPLE', 'value' => $sample, 'description' => '&nbsp;'));
        }

        array_push($row_data, array('label' => 'LBL_WEEK_START_DAY', 'value' => $week_start_day, 'description' => 'LBL_WEEK_START_DAY_DESC'));
        array_push($row_data, array('label' => 'LBL_DAY_BEGIN_HOUR', 'value' => $day_begin_hour, 'description' => 'LBL_DAY_BEGIN_HOUR_DESC'));
        array_push($row_data, array('label' => 'LBL_DAY_END_HOUR', 'value' => $day_end_hour, 'description' => 'LBL_DAY_END_HOUR_DESC'));
        array_push($row_data, array('label' => 'LBL_HOLIDAYS', 'value' => $user_holidays, 'description' => '&nbsp;'));

        if ($this->context == 'edit') {
            $layout =& $gen->getLayout();
            $layout->addScriptLiteral($this->locale->getNameJs(), LOAD_PRIORITY_FOOT);
        }

        return $this->renderRowWithDesc($row_data);
    }

    /**
     * Render html for 'Layout Options' view / edit section
     *
     * @param FormGenerator $gen
     * @return string
     */
    function renderLayout(FormGenerator &$gen) {
        global $app_list_strings, $mod_strings;

        $user_max_tabs = intval($this->getPreference('max_tabs'));
        $user_max_subtabs = intval($this->getPreference('max_subtabs'));
        $user_navigation_paradigm = $this->getPreference('navigation_paradigm');

        if ($this->context == 'edit') {
            $max_tab = $this->renderTextInput($gen, 'user_max_tabs', $user_max_tabs, array('width' => '3', 'len' => '2'));
            $max_subtab = $this->renderTextInput($gen, 'user_max_subtabs', $user_max_subtabs, array('width' => '3', 'len' => '2'));

            $swap_last_viewed = $this->renderCheckBox($gen, 'user_swap_last_viewed', $this->getPreference('swap_last_viewed'));
            $swap_shortcuts = $this->renderCheckBox($gen, 'user_swap_shortcuts', $this->getPreference('swap_shortcuts'));
            $show_footer = $this->renderCheckBox($gen, 'user_show_footer_module_links', $this->getPreference('show_footer_module_links'));

            $navigation_paradigm = $this->renderSelect($gen, 'user_navigation_paradigm', $app_list_strings['navigation_paradigms'], $user_navigation_paradigm, null, null, false);

        } else {
            
            if(isset($user_max_tabs) && $user_max_tabs > 0) {
                $max_tab = $user_max_tabs;
            } elseif(isset($max_tabs) && $max_tabs > 0) {
                $max_tab = $max_tabs;
            } else {
                $max_tab = AppConfig::setting('layout.defaults.max_tabs');
            }

            if(isset($user_max_subtabs) && $user_max_subtabs > 0) {
                $max_subtab = $user_max_subtabs;
            } else {
                $max_subtab = AppConfig::setting('layout.defaults.max_subtabs');
            }

            $swap_last_viewed = $this->getBoolSettings('swap_last_viewed');
            $swap_shortcuts = $this->getBoolSettings('swap_shortcuts');
            $show_footer = $this->getBoolSettings('show_footer_module_links');

            if(isset($user_navigation_paradigm)) {
                $navigation_paradigm = $app_list_strings['navigation_paradigms'][$user_navigation_paradigm];
            } else {
                $navigation_paradigm = $app_list_strings['navigation_paradigms'][AppConfig::setting('layout.defaults.navigation_paradigm')];
            }
        }

        $row_data = array();
        array_push($row_data, array('label' => 'LBL_MAX_TAB', 'value' => $max_tab, 'description' => 'LBL_MAX_TAB_DESCRIPTION'));
        array_push($row_data, array('label' => 'LBL_MAX_SUBTAB', 'value' => $max_subtab, 'description' => 'LBL_MAX_SUBTAB_DESCRIPTION'));

        $swap_last_desc = $mod_strings['LBL_SWAP_LAST_VIEWED_DESCRIPTION'] .'&nbsp; <i>'. $mod_strings['LBL_SUPPORTED_THEME_ONLY'] .'</i>';
        array_push($row_data, array('label' => 'LBL_SWAP_LAST_VIEWED_POSITION', 'value' => $swap_last_viewed, 'description' => $swap_last_desc));

        $swap_shortcut_desc = $mod_strings['LBL_SWAP_SHORTCUT_DESCRIPTION'] .'&nbsp; <i>'. $mod_strings['LBL_SUPPORTED_THEME_ONLY'] .'</i>';
        array_push($row_data, array('label' => 'LBL_SWAP_SHORTCUT_POSITION', 'value' => $swap_shortcuts, 'description' => $swap_shortcut_desc));

        $navigation_desc = $mod_strings['LBL_NAVIGATION_PARADIGM_DESCRIPTION'] .'&nbsp; <i>'. $mod_strings['LBL_SUPPORTED_THEME_ONLY'] .'</i>';
        array_push($row_data, array('label' => 'LBL_NAVIGATION_PARADIGM', 'value' => $navigation_paradigm, 'description' => $navigation_desc));

        $footer_links_desc = $mod_strings['LBL_SHOW_FOOTER_LINKS_DESCRIPTION'] .'&nbsp; <i>'. $mod_strings['LBL_SUPPORTED_THEME_ONLY'] .'</i>';
        array_push($row_data, array('label' => 'LBL_SHOW_FOOTER_LINKS', 'value' => $show_footer, 'description' => $footer_links_desc));

        $layout_html = $this->renderRowWithDesc($row_data);
        if ($this->context == 'edit')
            $layout_html = $this->renderTabChooser($gen) . $layout_html;

        return $layout_html;
    }

    /**
     * Render html for 'Email Options' view / edit section
     *
     * @param FormGenerator $gen
     * @param RowResult $row_result
     * @return string
     */
    function renderEmail(FormGenerator &$gen, RowResult &$row_result) {
        global $app_list_strings, $mod_strings;

        $email_charset = $this->locale->getOutboundEmailCharset(false);
        $charset_options = $this->locale->getCharsetSelect();
		$charset_options = array('' =>$mod_strings['LBL_DEFAULT_CHARSET']) + $charset_options;

        if ($this->context == 'edit') {
            $email1 = $this->renderTextInput($gen, 'email1', $row_result->getField('email1'), array('width' => '35'));
            $email2 = $this->renderTextInput($gen, 'email2', $row_result->getField('email2'), array('width' => '35'));
            $from_name = $this->renderTextInput($gen, 'mail_fromname', $this->getPreference('mail_fromname'), array('width' => '25'));
            $from_addr = $this->renderTextInput($gen, 'mail_fromaddress', $this->getPreference('mail_fromaddress'), array('width' => '35'));
            $auto_bcc = $this->renderTextInput($gen, 'mail_autobcc_address', $this->getPreference('mail_autobcc_address'), array('width' => '35'));

            $signatures = $this->renderSelect($gen, 'signature_id', $this->getSignatures(), $this->getPreference('signature_default'), 'setSigEditButtonVisibility();');
            $signature_buttons = $this->user->getSignatureButtons();
            $default_signature = '<input type="hidden" name="signatureDefault" id="signatureDefault" value="' .$this->getPreference('signature_default'). '" />';
            $signature = $signatures .'&nbsp;'. $signature_buttons . $default_signature;
            $signature_prepend = $this->renderCheckBox($gen, 'signature_prepend', $this->getPreference('signature_prepend'));

            $email_charset = $this->renderSelect($gen, 'default_email_charset', $charset_options, $email_charset);

        } else {

            $email1 = $row_result->getField('email1', '', true);
            $email2 = $row_result->getField('email2', '', true);
            $from_name = $this->getPreference('mail_fromname');
            $from_addr = $this->getPreference('mail_fromaddress');
            $auto_bcc = $this->getPreference('mail_autobcc_address');
            $signature = $this->getDefaultSignature();
            $signature_prepend = $this->getPreference('signature_prepend');
            if ($signature_prepend == 1) {
                $signature_prepend = 'on';
            } else {
                $signature_prepend = 'off';
            }
            $signature_prepend = $app_list_strings['dom_switch_bool'][$signature_prepend];
            $email_charset = array_get_default($charset_options, $email_charset, $email_charset);
        }

        $row_data = array();
        array_push($row_data, array('label' => 'LBL_EMAIL', 'value' => $email1));
        array_push($row_data, array('label' => 'LBL_MAIL_FROMNAME', 'value' => $from_name));
        array_push($row_data, array('label' => 'LBL_OTHER_EMAIL', 'value' => $email2));
        array_push($row_data, array('label' => 'LBL_MAIL_FROMADDRESS', 'value' => $from_addr));
        array_push($row_data, array('label' => '&nbsp;', 'value' => '&nbsp;'));
        array_push($row_data, array('label' => 'LBL_EMAIL_AUTO_BCC', 'value' => $auto_bcc));
        array_push($row_data, array('label' => 'LBL_SIGNATURE', 'value' => $signature));
        array_push($row_data, array('label' => 'LBL_SIGNATURE_PREPEND', 'value' => $signature_prepend));

        
        if ($this->context == 'edit') {
            $email_client = $this->renderSelect($gen, 'email_link_type', $app_list_strings['dom_email_link_type'], $this->getPreference('email_link_type'));
            array_push($row_data, array('label' => 'LBL_EMAIL_DEFAULT_CLIENT', 'value' => $email_client));
        } else {
            $editor_option = $app_list_strings['dom_email_editor_option'][$this->getPreference('email_editor_option')];
            array_push($row_data, array('label' => 'LBL_EMAIL_EDITOR_OPTION', 'value' => $editor_option));
        }

        array_push($row_data, array('label' => 'LBL_EMAIL_CHARSET', 'value' => $email_charset));

        if ($this->context == 'edit') {
            array_push($row_data, array('label' => 'LBL_OUT_OF_OFFICE', 'value' => $this->renderCheckBox($gen, 'out_of_office', $this->getPreference('out_of_office'))));
            array_push($row_data, array('label' => '&nbsp;', 'value' => '&nbsp;'));

            $display_html = $this->getPreference('email_display_format');
            $html_checked = ($display_html == 'html') ? 'checked' : '';
            $plain_checked = ($display_html == 'html') ? '' : 'checked';
            $display_format = '<input type="radio" class="radio" name="email_display_format" value="html" ' .$html_checked. '> ' . $mod_strings['LBL_VIEW_FORMAT_HTML'];
            $display_format .= '&nbsp; <input type="radio" class="radio" name="email_display_format" value="plain" ' .$plain_checked. '> ' . $mod_strings['LBL_VIEW_FORMAT_PLAIN'];
            array_push($row_data, array('label' => 'LBL_MAIL_DISPLAY_FORMAT', 'value' => $display_format));

            $compose_html = $this->getPreference('email_compose_format');
            $html_checked = ($compose_html == 'html') ? 'checked' : '';
            $plain_checked = ($compose_html == 'html') ? '' : 'checked';
            $compose_format = '<input type="radio" class="radio" name="email_compose_format" value="html" ' .$html_checked. '> ' . $mod_strings['LBL_VIEW_FORMAT_HTML'];
            $compose_format .= '&nbsp; <input type="radio" class="radio" name="email_compose_format" value="plain" ' .$plain_checked. '> ' . $mod_strings['LBL_VIEW_FORMAT_PLAIN'];
            array_push($row_data, array('label' => 'LBL_MAIL_COMPOSE_FORMAT', 'value' => $compose_format));

            $show_images = $this->getPreference('show_images');
            $show = ($show_images) ? 'checked' : '';
            $hide = ($show_images) ? '' : 'checked';
            $show_mail_images = '<input type="radio" class="radio" name="show_images" value="display" ' .$show. '> ' . $mod_strings['LBL_SHOW_IMAGES'];
            $show_mail_images .= '&nbsp; <input type="radio" class="radio" name="show_images" value="" ' .$hide. '> ' . $mod_strings['LBL_HIDE_IMAGES'];
            array_push($row_data, array('label' => 'LBL_MAIL_DISPLAY_IMAGES', 'value' => $show_mail_images));
            
            array_push($row_data, array('label' => 'LBL_EMAIL_SOUND_DISABLE', 'value' => $this->renderCheckBox($gen, 'email_sound_disable', $this->getPreference('email_sound_disable'))));            

            $cleanup_ages = array('trash_cleanup_age' => 'LBL_CLEAN_TRASH', 'inbox_cleanup_age' => 'LBL_CLEAN_INBOX', 'inbox_assoc_cleanup_age' => 'LBL_CLEAN_INBOX_ASSOC',
                'sent_cleanup_age' => 'LBL_CLEAN_SENT', 'sent_assoc_cleanup_age' => 'LBL_CLEAN_SENT_ASSOC');

            foreach ($cleanup_ages as $name => $label) {
                $age = $this->getPreference($name);
                $selected_age = $age ? $age : 0;
                array_push($row_data, array('label' => $label, 'value' => $this->renderSelect($gen, $name, $mod_strings['email_cleanup_options'], $selected_age)));
                array_push($row_data, array('label' => '&nbsp;', 'value' => '&nbsp;'));
            }
        }

        if ($this->context == 'edit') {
            $layout =& $gen->getLayout();
            $layout->addScriptLiteral('setSigEditButtonVisibility();', LOAD_PRIORITY_FOOT);
        }

        return $this->renderRow($row_data);
    }

    /**
     * Render html for 'Outbound Email Settings' view / edit section
     *
     * @param FormGenerator $gen
     * @return string
     */
    function renderOutboundEmail(FormGenerator &$gen) {
        global $app_list_strings, $mod_strings;

        $mail_sendtype = $this->getPreference('mail_sendtype');
        if (!$mail_sendtype)
            $mail_sendtype = UserInfoWidget::DEFAULT_MAIL_SENDTYPE;

        $smtp_server = $this->getPreference('mail_smtpserver');
        $smtp_port = $this->getPreference('mail_smtpport');
        if (!$smtp_port)
            $smtp_port = UserInfoWidget::DEFAULT_SMTP_PORT;

        $mail_smtpauth = $this->getPreference('mail_smtpauth_req');
        $smtp_user = $this->getPreference('mail_smtpuser');
        $smtp_password = $this->getPreference('mail_smtppass');

        if ($this->context == 'edit') {
            $mail_sendtype = $this->renderSelect($gen, 'mail_sendtype', $app_list_strings['notifymail_sendtype'], $mail_sendtype, 'notify_setrequired(document.DetailForm);');
            $smtp_server = $this->renderTextInput($gen, 'mail_smtpserver', $smtp_server);
            $smtp_port = $this->renderTextInput($gen, 'mail_smtpport', $smtp_port, array('width' => 5, 'len' => 5));
            $smtpauth_req = $this->renderCheckBox($gen, 'mail_smtpauth_req', $mail_smtpauth, 'notify_setrequired(document.DetailForm);');
            $smtp_user = $this->renderTextInput($gen, 'mail_smtpuser', $smtp_user);
            $smtp_password = $this->renderTextInput($gen, 'mail_smtppass', $smtp_password, array('type' => 'password'));
        } else {
            $empty_row = array();
            $spec['type'] = 'bool';
            $spec['name'] = 'mail_smtpauth_req';
            $smtpauth_req = $this->formatter->formatRowValue($spec, $empty_row, $mail_smtpauth);
            $smtp_password = '********';
        }

        $additional_rows = '';
        $smtp_rows = '';
        $smtp_auth_rows = '';
        $row_data = array();
        array_push($row_data, array('label' => 'LBL_MAIL_SENDTYPE', 'value' => $mail_sendtype));

        if ($this->context == 'edit'){
            array_push($row_data, array('label' => '&nbsp;', 'value' => '&nbsp;'));
        } else {
            array_push($row_data, array('label' => 'LBL_EMAIL_DEFAULT_CLIENT', 'value' => $app_list_strings['dom_email_link_type'][$this->getPreference('email_link_type')]));
        }

        $main_rows = $this->renderRow($row_data);

        if($mail_sendtype == 'SMTP' || $this->context == 'edit') {
            $row_data = array();
            array_push($row_data, array('label' => 'LBL_MAIL_SMTPSERVER', 'value' => $smtp_server));
            array_push($row_data, array('label' => 'LBL_MAIL_SMTPPORT', 'value' => $smtp_port));

            array_push($row_data, array('label' => 'LBL_MAIL_SMTPAUTH_REQ', 'value' => $smtpauth_req));
            array_push($row_data, array('label' => '&nbsp;', 'value' => '&nbsp;'));

            $smtp_rows = $this->renderRow($row_data);

            if ($mail_smtpauth || $this->context == 'edit') {
                $row_data = array();
                array_push($row_data, array('label' => 'LBL_MAIL_SMTPUSER', 'value' => $smtp_user));
                array_push($row_data, array('label' => 'LBL_MAIL_SMTPPASS', 'value' => $smtp_password));
                $smtp_auth_rows = $this->renderRow($row_data);
            }

            if ($this->context == 'edit') {
                $smtp_auth_rows = '<tr><td colspan="4"><div id="smtp_auth"><table width="100%" cellpadding="0" cellspacing="0">' .$smtp_auth_rows. '</table></div></td></tr>';
                $smtp_rows = '<tr><td colspan="4"><div id="smtp_settings"><table width="100%" cellpadding="0" cellspacing="0">' .$smtp_rows . $smtp_auth_rows. '</table></div></td></tr>';
            } else {
                $smtp_rows = $smtp_rows . $smtp_auth_rows;
            }
        }

        if ($this->context != 'edit') {
            $row_data = array();
            $display_html = $this->getPreference('email_display_format');
            $view_format = ($display_html == 'html') ? $mod_strings['LBL_VIEW_FORMAT_HTML'] : $mod_strings['LBL_VIEW_FORMAT_PLAIN'];
            $compose_html = $this->getPreference('email_compose_format');
            $compose_format = $compose_html == 'html' ? $mod_strings['LBL_VIEW_FORMAT_HTML'] : $mod_strings['LBL_VIEW_FORMAT_PLAIN'];
            $show_images = $this->getPreference('show_images');
            $display_images = $show_images ? $mod_strings['LBL_SHOW_IMAGES'] : $mod_strings['LBL_HIDE_IMAGES'];
            $disable_sound = $this->getPreference('email_sound_disable');
            $disable_sound = $app_list_strings['dom_int_bool'][$disable_sound ? 1 : 0];

            array_push($row_data, array('label' => 'LBL_MAIL_DISPLAY_FORMAT', 'value' => $view_format));
            array_push($row_data, array('label' => 'LBL_MAIL_COMPOSE_FORMAT', 'value' => $compose_format));
            array_push($row_data, array('label' => 'LBL_MAIL_DISPLAY_IMAGES', 'value' => $display_images));
            array_push($row_data, array('label' => 'LBL_EMAIL_SOUND_DISABLE', 'value' => $disable_sound));

            $additional_rows = $this->renderRow($row_data);
        }

        if ($this->context == 'edit') {
            $layout =& $gen->getLayout();
            $layout->addScriptLiteral('notify_setrequired(document.DetailForm);', LOAD_PRIORITY_FOOT);
        }

        return $main_rows . $smtp_rows . $additional_rows;
    }

    /**
     * Render html for 'Calendar Options' view / edit section
     *
     * @param FormGenerator $gen
     * @param RowResult $row_result
     * @return string
     */
    function renderCalendar(FormGenerator &$gen, RowResult &$row_result) {
        $main_email = $row_result->getField('email1');

        $publich_key = $this->getPreference('calendar_publish_key');
        $publish_url = '';
        $search_url = '';
        $subscribe_url = '';

        if ($this->context == 'edit') {
            $publich_key = $this->renderTextInput($gen, 'calendar_publish_key', $publich_key);
        } else {
            if (! empty($main_email)) {
                $publish_url = AppConfig::site_url().'vcal_server.php';
                $token = "/";

                //determine if the web server is running IIS
                //if so then change the publish url
                if(isset($_SERVER) && ! empty($_SERVER['SERVER_SOFTWARE'])){
                    $position = strpos(strtolower($_SERVER['SERVER_SOFTWARE']), 'iis');
                    if($position !== false) $token = '?parms=';
                }

                $publish_url .= $token.'type=vfb&email='.$main_email.'&source=outlook&key=' . $publich_key;
            } else {
                $publish_url = AppConfig::site_url().'vcal_server.php/type=vfb&user_name=' .$row_result->getField('user_name'). '&source=outlook&key=' . $publich_key;
            }

            $search_url = AppConfig::site_url().'vcal_server.php/type=vfb&email=%NAME%@%SERVER%';

            $webcal_url = str_replace('http:', 'webcal:', AppConfig::site_url());
            $ical_subscribe = $webcal_url.'vcal_server.php/type=ics&user_name=' .$row_result->getField('user_name'). '&key=' . $publich_key;
            $subscribe_url = '<a href="' .$ical_subscribe. '" target="_blank" class="tabDetailViewDFLink">' .$ical_subscribe. '</a>';
        }

        $row_data = array();
        array_push($row_data, array('label' => 'LBL_PUBLISH_KEY', 'value' => $publich_key, 'description' => 'LBL_CHOOSE_A_KEY'));

        if ($this->context != 'edit') {
            array_push($row_data, array('label' => 'LBL_YOUR_PUBLISH_URL', 'value' => $publish_url, 'colspan' => 3));
            array_push($row_data, array('label' => 'LBL_SEARCH_URL', 'value' => $search_url, 'colspan' => 3));
            array_push($row_data, array('label' => 'LBL_ICAL_SUBSCRIBE', 'value' => $subscribe_url, 'colspan' => 3));
        }

        return $this->renderRowWithDesc($row_data);
    }

    /**
     * Render html for 'Google Account Settings' view / edit section
     *
     * @param FormGenerator $gen
     * @return string
     */
    function renderGoogleAccount(FormGenerator &$gen) {
        $google_cal_user = $this->getPreference('google_calendar_user');
        $google_domain = $this->getPreference('google_domain' );
        $google_pass = $this->getPreference('google_calendar_pass');

        if ($this->context == 'edit') {
            $google_cal_user = $this->renderTextInput($gen, 'google_calendar_user', $google_cal_user);
            $google_domain = $this->renderTextInput($gen, 'google_domain', $google_domain);
        } else {

        }

        $row_data = array();
        array_push($row_data, array('label' => 'LBL_GOOGLE_CALENDAR_USER', 'value' => $google_cal_user));

        if ($this->context != 'edit') {
            $google_cal = new GoogleCalendar($this->user);
            array_push($row_data, array('label' => 'LBL_GOOGLE_CALENDAR_CONNECT', 'value' => $google_cal->connect_status));
        }

        array_push($row_data, array('label' => 'LBL_GOOGLE_DOMAIN', 'value' => $google_domain));

        if ($this->context != 'edit') {
            array_push($row_data, array('label' => '&nbsp;', 'value' => '&nbsp;'));
            $html = $this->renderRow($row_data);
        } else {
            global $mod_strings;
            $ssl_row = '<tr><td colspan="4" class="dataLabel"><b>' .$mod_strings['LBL_GOOGLE_SSL']. '</b></td></tr>';

            $google_password = $this->renderTextInput($gen, 'google_calendar_pass', $google_pass, array('type' => 'password'));
            $password_row = '<tr><td class="dataLabel">'.$mod_strings['LBL_GOOGLE_PASS'].'</td><td class="dataField">' .$google_password. '</td>';
            $password_row .= '<td colspan="2" class="dataLabel"><small>' .$mod_strings['LBL_GOOGLE_DOMAIN_EXPLANATION']. '</small></td></tr>';

            $html = $ssl_row . $this->renderRow($row_data) . $password_row;
        }

        return $html;
    }

    /**
     * Render html for 'Google Calendar Settings' view / edit section
     *
     * @param FormGenerator $gen
     * @return string
     */
    function renderGoogleCalendar(FormGenerator &$gen) {
        global $app_list_strings;

        $calendar_id = $this->getPreference('google_calendar_id' );
        $calendar_direction = $this->getPreference('google_calendar_direction');

        $calendar_call_on = $this->getPreference('google_calendar_call');
        if ($calendar_call_on == 'on') {
            $calendar_call_on = 1;
        } elseif ($calendar_call_on == 'off') {
            $calendar_call_on = 0;
        }
        $calendar_meetings_on = $this->getPreference('google_calendar_meeting');
        if ($calendar_meetings_on == 'on') {
            $calendar_meetings_on = 1;
        } elseif ($calendar_meetings_on == 'off') {
            $calendar_meetings_on = 0;
        }
        $calendar_task_on = $this->getPreference('google_calendar_task');
        if ($calendar_task_on == 'on') {
            $calendar_task_on = 1;
        } elseif ($calendar_task_on == 'off') {
            $calendar_task_on = 0;
        }

        if ($this->context == 'edit') {
            $calendar_id = $this->renderTextInput($gen, 'google_calendar_id', $calendar_id);
            $calendar_sync = $this->renderSelect($gen, 'google_calendar_direction', $app_list_strings['google_calendar_sync_dom'], $calendar_direction);

            $calendar_call = $this->renderCheckBox($gen, 'google_calendar_call', $calendar_call_on);
            $calendar_meeting = $this->renderCheckBox($gen, 'google_calendar_meeting', $calendar_meetings_on);
            $calendar_task = $this->renderCheckBox($gen, 'google_calendar_task', $calendar_task_on);

        } else {

            $calendar_sync = $app_list_strings['google_calendar_sync_dom'][$calendar_direction];

            $empty_row = array();
            $spec = array('type' => 'bool', 'name' => '');

            $spec['name'] = 'calendar_call';
            $calendar_call = $this->formatter->formatRowValue($spec, $empty_row, $calendar_call_on);

            $spec['name'] = 'calendar_meeting';
            $calendar_meeting = $this->formatter->formatRowValue($spec, $empty_row, $calendar_meetings_on);

            $spec['name'] = 'calendar_task';
            $calendar_task = $this->formatter->formatRowValue($spec, $empty_row, $calendar_task_on);
        }

        $row_data = array();
        array_push($row_data, array('label' => 'LBL_GOOGLE_CALENDAR_ID', 'value' => $calendar_id));
        array_push($row_data, array('label' => '&nbsp;', 'value' => '&nbsp;'));
        $cal_id_row = $this->renderRow($row_data);

        $id_explanation = '';
        if ($this->context == 'edit') {
            global $current_language, $mod_strings;

            $google_id_image = "include/language/images/$current_language/google-cal-id.gif";
            if (! file_exists($google_id_image))
                $google_id_image = "include/language/images/en_us/google-cal-id.gif";

            $id_explanation .= '<tr><td colspan="4" class="dataLabel">' .$mod_strings['LBL_GOOGLE_CALENDAR_ID_EXPLANATION'];
            $id_explanation .= ' <a href="' .$google_id_image. '" target="_blank" class="tabDetailViewDFLink">' .$mod_strings['LBL_GOOGLE_CALENDAR_ID_SHOW']. '</a></td></tr>';
        }

        $row_data = array();
        array_push($row_data, array('label' => 'LBL_GOOGLE_CALENDAR_SYNC', 'value' => $calendar_sync));
        array_push($row_data, array('label' => '&nbsp;', 'value' => '&nbsp;'));
        array_push($row_data, array('label' => 'LBL_GOOGLE_CALENDAR_CALL', 'value' => $calendar_call));
        array_push($row_data, array('label' => 'LBL_GOOGLE_CALENDAR_MEETING', 'value' => $calendar_meeting));
        array_push($row_data, array('label' => 'LBL_GOOGLE_CALENDAR_TASK', 'value' => $calendar_task));
        array_push($row_data, array('label' => '&nbsp;', 'value' => '&nbsp;'));
        $cal_data_row = $this->renderRow($row_data);

        return $cal_id_row . $id_explanation . $cal_data_row;
    }

    /**
     * Render html for 'Google Documents Settings' view / edit section
     *
     * @param FormGenerator $gen
     * @return string
     */
    function renderGoogleDocuments(FormGenerator &$gen) {
        global $app_list_strings;

        $docs_direction = $this->getPreference('google_docs_direction');

        $doc_options = array(
            '' => 'Microsoft Word',
            'odt' => 'Open Document',
            'rtf' => 'RTF',
        );
        $doc_selected = $this->getPreference('google_docs_doc');

        $spreadsheet_options = array(
            '' => 'Microsoft Excel',
            'ods' => 'Open Document',
            'csv' => 'CSV',
        );
        $spreadsheet_selected = $this->getPreference('google_docs_spreadsheet');

        $powerpoint_options = array(
            '' => 'Microsoft PowerPoint',
            'pdf' => 'PDF',
        );
        $powerpoint_selected = $this->getPreference('google_docs_presentation');

        if ($this->context == 'edit') {
            $docs_sync = $this->renderSelect($gen, 'google_docs_direction', $app_list_strings['google_docs_sync_dom'], $docs_direction);
            $doc = $this->renderSelect($gen, 'google_docs_doc', $doc_options, $doc_selected);
            $spreadsheet = $this->renderSelect($gen, 'google_docs_spreadsheet', $spreadsheet_options, $spreadsheet_selected);
            $powerpoint = $this->renderSelect($gen, 'google_docs_presentation', $powerpoint_options, $powerpoint_selected);
        } else {
            $docs_sync = array_get_default($app_list_strings['google_docs_sync_dom'], $docs_direction, $app_list_strings['google_docs_sync_dom']['']);
            $doc = array_get_default($doc_options, $doc_selected, 'Microsoft Word');
            $spreadsheet = array_get_default($spreadsheet_options, $spreadsheet_selected, 'Microsoft Excel');
            $powerpoint = array_get_default($powerpoint_options, $powerpoint_selected, 'Microsoft PowerPoint');
        }

        $row_data = array();
        array_push($row_data, array('label' => 'LBL_GOOGLE_DOCS_SYNC', 'value' => $docs_sync));
        array_push($row_data, array('label' => '&nbsp;', 'value' => '&nbsp;'));
        array_push($row_data, array('label' => 'LBL_GOOGLE_DOCS_DOC', 'value' => $doc));
        array_push($row_data, array('label' => 'LBL_GOOGLE_DOCS_SPREADSHEET', 'value' => $spreadsheet));
        array_push($row_data, array('label' => 'LBL_GOOGLE_DOCS_PRESENTATION', 'value' => $powerpoint));
        array_push($row_data, array('label' => '&nbsp;', 'value' => '&nbsp;'));

        return $this->renderRow($row_data);
    }

    /**
     * Render html for 'Google Contacts Settings' view / edit section
     *
     * @param FormGenerator $gen
     * @return string
     */
    function renderGoogleContacts(FormGenerator &$gen) {
        global $mod_strings;

        $empty_row = array();
        $spec = array('type' => 'bool', 'name' => 'calendar_call');
        $sync_on = $this->getPreference('google_contacts_direction') ? 1 : 0;

        $contacts_for_sync = $this->getPreference('google_contacts_which');

        if ($this->context == 'edit') {
            $sync = $this->renderCheckBox($gen, 'google_contacts_direction', $sync_on);

            $options = array(
                '' => $mod_strings['LBL_ASSIGNED_TO_ME'],
                'visible' => $mod_strings['LBL_ALL_VISIBLE'],
            );
            $contacts_which = $this->renderSelect($gen, 'google_contacts_which', $options, $contacts_for_sync);
        } else {
            $sync = $this->formatter->formatRowValue($spec, $empty_row, $sync_on);
            $contacts_which = $contacts_for_sync  ? $mod_strings['LBL_ALL_VISIBLE'] : $mod_strings['LBL_ASSIGNED_TO_ME'];
        }

        $row_data = array();
        array_push($row_data, array('label' => 'LBL_GOOGLE_CONTACTS_SYNC', 'value' => $sync));
        array_push($row_data, array('label' => 'LBL_GOOGLE_CONTACTS_WHICH', 'value' => $contacts_which));

        return $this->renderRow($row_data);
    }


    /**
     * Render html for 'Access Control Settings' view / edit section
     *
     * @param FormGenerator $gen
     * @param RowResult $row_result
     * @return string
     */
    function renderAcl(FormGenerator &$gen, RowResult &$row_result) {
        $empty_row = array();
        $spec = array('type' => 'bool', 'name' => '');

        $financial_on = $this->getPreference('financial_information') ? 1 : 0;

        if ($this->context == 'edit') {
            $is_admin = $this->renderCheckBox($gen, 'is_admin', $row_result->getField('is_admin'));
            $portal_only = $this->renderCheckBox($gen, 'portal_only', $row_result->getField('portal_only'));
            $financial_info = $this->renderCheckBox($gen, 'financial_information', $financial_on);
        } else {
            $is_admin = $row_result->getField('is_admin', '', true);
            $portal_only = $row_result->getField('portal_only', '', true);

            $spec['name'] = 'financial_information';
            $financial_info = $this->formatter->formatRowValue($spec, $empty_row, $financial_on);
        }

        $row_data = array();
        array_push($row_data, array('label' => 'LBL_ADMIN', 'value' => $is_admin, 'description' => 'LBL_ADMIN_TEXT'));
        array_push($row_data, array('label' => 'LBL_PORTAL_ONLY', 'value' => $portal_only, 'description' => 'LBL_PORTAL_ONLY_TEXT'));

        $quote_prefs = array('noncatalog_products' => 'LBL_NONCATALOG_RPODUCTS', 'nonstandard_prices' => 'LBL_NONSTANDARD_PRICES',
            'manual_discounts' => 'LBL_MANUAL_DISCOUNTS', 'standard_discounts' => 'LBL_STANDARD_DISCOUNTS',
            'product_costs' => 'LBL_PRODUCT_COSTS');
        $quote_rows = array();
        $quote_desc = array();

        foreach($quote_prefs as $pref => $desc) {
            $spec['name'] = $pref;
            $pref = $this->getPreference($pref) ? 1 : 0;

            if ($this->context == 'edit') {
                $pref_formatted = $this->renderCheckBox($gen, $spec['name'], $pref);
            } else {
                $pref_formatted = $this->formatter->formatRowValue($spec, $empty_row, $pref);
            }

            $quote_rows[] = $pref_formatted;
            $quote_desc[] = $desc;
        }

        array_push($row_data, array('label' => 'LBL_QUOTE_CATALOG_MODE', 'value' => $quote_rows, 'description' => $quote_desc));
        array_push($row_data, array('label' => 'LBL_PROJECT_CATALOG_MODE', 'value' => $financial_info, 'description' => 'LBL_PROJECT_FINANCIAL'));


        return $this->renderRowWithDesc($row_data);
    }

    /**
     * Render select box
     *
     * @param HtmlFormGenerator $gen
     * @param string $name
     * @param mixed $options: array|string
     * @param string $selected
     * @param string $onchange
     * @param bool $classic
     * @param bool $add_blank
     * @return string
     */
    function renderSelect(HtmlFormGenerator &$gen, $name, $options, $selected = '', $onchange = null, $classic = false, $add_blank=null) {
    	if(is_array($name)) {
    		$spec = $name;
    		if(isset($options)) $spec['options'] = $options;
    	}
    	else $spec = compact('name', 'options');
        if ($onchange) $spec['onchange'] = $onchange;
		if (isset($add_blank)) $spec['options_add_blank'] = $add_blank;

        if ($classic)
            $gen->form_obj->classic = true;
        $select = $gen->form_obj->renderSelect($spec, $selected);
        if ($classic)
            $gen->form_obj->classic = false;

        return $select;
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

    /**
     * Render text input
     *
     * @param HtmlFormGenerator $gen
     * @param string $name
     * @param mixed $value
     * @param array $params
     * @return string
     */
    function renderTextInput(HtmlFormGenerator &$gen, $name, $value, $params = array()) {
        $spec['name'] = $name;
        $spec = $spec + $params;
        
        return $gen->form_obj->renderText($spec, $value);
    }

    /**
     * Render tab chooser for rearranging visible system menu tabs
     *
     * @param HtmlFormGenerator $gen
     * @return string
     */
    function renderTabChooser(HtmlFormGenerator &$gen) {
        require_once('modules/MySettings/TabController.php');
        global $mod_strings;
        
        $controller = new TabController();

        $layout =& $gen->getLayout();

		$form = $gen->getFormObject();

        /*if(AppConfig::is_admin() || $controller->get_users_can_edit())
            $chooser->display_hide_tabs = true;
        else
            $chooser->display_hide_tabs = false;*/

		$tabs = $controller->get_tabs($this->user, true);
		$all_tabs = array();
        foreach($tabs as $idx => $split_tabs) {
        	foreach($split_tabs as $mod => $_) {
        		$all_tabs[$mod] = AppConfig::setting("lang.strings.current.$mod.LBL_MODULE_TITLE");
        	}
        }
        $show_tabs = array_keys($tabs[0]);
        $hide_tabs = array_keys($tabs[1]);
		$spec = array(
			'type' => 'multi_select',
			'display_rows' => 6,
			'width' => 30,
			'options' => $all_tabs,
		);
        
        $dspec = $spec;
        $dspec['name'] = 'display_tabs';
        $dspec['options_limit_keys'] = $show_tabs;

        $html = '<table border="0" cellpadding="0" cellspacing="3"><tr><td>';
        $html .= $mod_strings['LBL_DISPLAY_TABS'] . '</td><td>&nbsp;</td><td>';
        $html .= $mod_strings['LBL_HIDE_TABS'] . '</td></tr>';
        $html .= '<tr><td>';
        $html .= $form->renderMultiSelect($dspec, '');

        $html .= '</td><td align="center">';
        $html .= '<button type="button" class="input-button input-outer" style="width: 2em" onclick="move_tabs_right()"><div class="input-icon icon-next"></div></button><br />';
        $html .= '<button type="button" class="input-button input-outer" style="width: 2em" onclick="move_tabs_left()"><div class="input-icon icon-prev"></div></button>';

        $dspec = $spec;
        $dspec['name'] = 'hide_tabs';
        $dspec['options_limit_keys'] = $hide_tabs;
        $html .= '</td><td>';
        $html .= $form->renderMultiSelect($dspec, '');
        $html .= '</td></tr></table>';
        
        $html = <<<EOQ
            <tr>
                <td colspan="3">
                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                    <td class="dataLabel" align="left" style="padding-bottom: 2em; padding-right: 1em">{$html}</td>
                    <td width="90%" valign="top"><br><br><br>{$mod_strings['LBL_CHOOSE_WHICH']}</td>
                    </tr>
                </table>
                </td>
            </tr>
EOQ;

        return $html;
    }

    /**
     * Render table row
     *
     * @param  array $row_data:
     * ['label'] - field label (module language constant)
     * ['value'] - field value
     * @return string
     */
    function renderRow($row_data) {
		if ($this->pdfMode) {
			$this->renderRowPdf($row_data);
			return;
		}
        global $mod_strings;

        $html = '';

        if (sizeof($row_data) > 0) {

            $classes = $this->getCellCssClasses();
            $required_star = '<span class="requiredStar">*</span>';

            for ($i = 0; $i < sizeof($row_data); $i++) {
                $label1 = isset($mod_strings[$row_data[$i]['label']]) ? $mod_strings[$row_data[$i]['label']] : $row_data[$i]['label'];
                if (isset($row_data[$i]['required']))
                    $label1 .= $required_star ;
                $label2 = isset($mod_strings[$row_data[$i+1]['label']]) ? $mod_strings[$row_data[$i+1]['label']] : $row_data[$i+1]['label'];
                if (isset($row_data[$i+1]['required']))
                    $label2 .= $required_star;

                $html .= <<<EOQ
                    <tr>
                    <td class="{$classes['label']}" width="20%">{$label1}</td>
                    <td class="{$classes['field']}" width="30%">{$row_data[$i]['value']}</td>
                    <td class="{$classes['label']}" width="20%">{$label2}</td>
                    <td class="{$classes['field']}" width="30%">{$row_data[$i+1]['value']}</td>
                    </tr>
EOQ;
                $i += 1;
            }
        }

        return $html;
    }

    /**
     * Render table row (for fields with descriptions)
     *
     * @param  array $row_data:
     * ['label'] - field label (module language constant)
     * ['value'] - field value
     * ['description'] - field description (module language constant)
     * @return string
     */
	function renderRowWithDesc($row_data) {
		if ($this->pdfMode) {
			$this->renderRowWithDescPdf($row_data);
			return;
		}
        global $mod_strings;

        $html = '';

        if (sizeof($row_data) > 0) {

            $classes = $this->getCellCssClasses();

            for ($i = 0; $i < sizeof($row_data); $i++) {
                $label = isset($mod_strings[$row_data[$i]['label']]) ? $mod_strings[$row_data[$i]['label']] : $row_data[$i]['label'];

                if (! is_array($row_data[$i]['value'])) {
                    $html .= '<tr><td class="' .$classes['label']. '" width="20%">' .$label. '</td>';

                    if (isset($row_data[$i]['colspan'])) {
                        $colspan = 'colspan="'.$row_data[$i]['colspan'].'"';
                        $html .= '<td class="' .$classes['field']. '" '.$colspan.'>' .$row_data[$i]['value']. '</td>';
                    } else {
                        $description = isset($mod_strings[$row_data[$i]['description']]) ? $mod_strings[$row_data[$i]['description']] : $row_data[$i]['description'];
                        $html .= '<td class="' .$classes['field']. '" width="25%">' .$row_data[$i]['value']. '</td>';
                        $desc_rowspan = isset($row_data[$i]['desc_rowspan']) ? 'rowspan="' .$row_data[$i]['desc_rowspan']. '"' : '';
                        $html .= '<td class="' .$classes['field']. '" colspan="2" ' .$desc_rowspan. ' style="padding-left: 1em">' .$description. '</td>';
                    }

                    $html .= '</tr>';
				} else {
                    $html .= $this->renderMultiRows($row_data[$i], $label);
                }
            }

        }

        return $html;
    }

    /**
     * Render table rows with one label
     *
     * @param  array $rows:
     * ['value'] => array(), ['descriptions'] => array()
     * @param  string $label
     * @return string
     */
    function renderMultiRows($rows, $label) {
        global $mod_strings;

        $html = '';
        if (! is_array($rows['value']) || sizeof($rows['value']) == 0)
            return $html;

        $classes = $this->getCellCssClasses();

        for ($i = 0; $i < sizeof($rows['value']); $i++) {
            $html .= '<tr>';

            if ($i == 0) {
                $html .= '<td class="' .$classes['label']. '" width="20%" rowspan="' .sizeof($rows['value']). '">' .$label. '</td>';
            }

            $description = isset($mod_strings[$rows['description'][$i]]) ? $mod_strings[$rows['description'][$i]] : $rows['description'][$i];
            $html .= '<td class="' .$classes['field']. '" width="25%">' .$rows['value'][$i]. '</td>';
            $html .= '<td class="' .$classes['field']. '" colspan="2" style="padding-left: 1em">' .$description. '</td>';

            $html .= '</tr>';
        }

        return $html;
    }

    /**
     * Get <td> css classes (depends on context)
     *
     * @return array:
     * ['label'], ['field']
     *
     */
    function getCellCssClasses() {
        $classes = array();

        if ($this->context == 'edit') {
            $classes['label'] = 'dataLabel';
            $classes['field'] = 'dataField';
        } else {
            $classes['label'] = 'tabDetailViewDL';
            $classes['field'] = 'tabDetailViewDF';
        }

        return $classes;
    }

    /**
     * Get User preference value
     *
     * @param  string $name - preference name
     * @return mixed
     */
    function getPreference($name) {
        return  UserPreference::getPreference($name, UserInfoWidget::PREFERENCES_CATEGORY, $this->user);
    }

    /**
     * Set new value for user preference
     *
     * @param string $name - preference name
     * @param mixed $value
     * @return void
     */
    function setPreference($name, $value) {
        UserPreference::setPreference($name, $value, 0, UserInfoWidget::PREFERENCES_CATEGORY, $this->user);
    }

    /**
     * Checking is admin current user or not
     *
     * @return bool
     */
    function isAdmin() {
        if (!AppConfig::is_admin()) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Checking is viewing user current or not
     * 
     * @return bool
     */
    function isCurrent() {
        if ($this->user->id != AppConfig::current_user_id()) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Show unauthorized access error message
     *
     * @return void
     */
    function showError() {
        sugar_die("Unauthorized access to administration.");
    }

    /**
     * @return string
     */
    function getDefaultCurrency() {
        require_once('modules/Currencies/Currency.php');
        $currency  = new Currency();
        $def_currency = $currency->getDefaultISO4217() .' '.$currency->getDefaultCurrencySymbol();
        $def_currency_id = $this->getPreference('currency');

        if($def_currency_id) {
            $user_currency = ListQuery::quick_fetch_row('Currency', $def_currency_id, array('symbol', 'iso4217'));
            if ($user_currency != null)
                $def_currency = $user_currency['iso4217'] .' '. $user_currency['symbol'];
        }

        return $def_currency;
    }

    /**
     * Get User address format
     *
     * @return string
     */
	function getAddressFormat() {
		$format = $this->pdfMode ? 'pdf' : 'display';
        $sample_address = $this->locale->getLocaleSampleAddress();
        $address_tpl = $this->locale->getLocaleAddressTemplate('display');
        $address_format_name = $this->locale->translateAddressFormatName();
		$formatted_address = $this->locale->getLocaleFormattedAddress($sample_address, '', $format, $address_tpl);
		if ($this->pdfMode) {
    	   $user_address_format = '  ' .$address_format_name. "\n" . $formatted_address;
		} else {
	       $formatted_address = nl2br($formatted_address);
    	   $user_address_format = '&nbsp;&nbsp;&nbsp;' .$address_format_name. '<br><em>' .$formatted_address. '</em>';
		}

        return $user_address_format;
    }

    /**
     * Get Holidays list
     *
     * @return array
     */
    function getHolidays() {
        global $mod_strings;

        $holidays = array('' => $mod_strings['LBL_DEFAULT_HOLIDAYS']);
        $files = glob('modules/Calendar/holidays/*.holidays.php');
        foreach ($files as $file) {
			$HOLIDAYS = ConfigParser::load_file($file);
            $m = array();
            preg_match('~[/\\\\]([^/\\\\]+)\.holidays\.php$~', $file, $m);
            if (isset($HOLIDAYS['lang']['LBL_NAME'])) {
                $holidays[$m[1]] =  $HOLIDAYS['lang']['LBL_NAME'];
            } else {
                $holidays[$m[1]] =  $HOLIDAYS['name_default'];
            }
        }

        return $holidays;
    }

    /**
     * Get formatted checkbox(bool) settings value
     *
     * @param  $name - settings name
     * @return mixed|null|string
     */
    function getBoolSettings($name) {
        $user_settings = $this->getPreference($name);

        if(isset($user_settings)) {
            $settings_value = $user_settings ? 1 : 0;
        } else {
            $settings_value = AppConfig::setting('layout.defaults.' . $name) ? 1 : 0;
        }

        $empty_row = array();
        $spec = array('type' => 'bool', 'name' => $name);

        return $this->formatter->formatRowValue($spec, $empty_row, $settings_value);
    }

    /**
     * Get User's default email signature
     *
     * @return string
     */
    function getDefaultSignature() {
        $sig_def = $this->getPreference('signature_default');
        $default_signature = '';

        if ($sig_def) {
            $signature = ListQuery::quick_fetch('UserSignature', $sig_def, array('name', 'signature', 'signature_html'));
            if ($signature != null)
                $default_signature = $signature->getField('name');
        }

        return $default_signature;
    }

    /**
     * Get users's email signatures
     *
     * @return array
     */
    function getSignatures() {
        global $app_strings;
        
        $fields = array('id', 'name');
        $lq = new ListQuery('UserSignature', $fields);
        $lq->addSimpleFilter('user_id', $this->user->id);
        $result = $lq->fetchAll('name');
        $signatures = array('' => $app_strings['LBL_NONE']);

        if (! $result->failed) {
            $signatures_list = $result->rows;

            foreach($signatures_list as $id => $data) {
                $signatures[$id] = $data['name'];
            }

        }

        return $signatures;
    }

    /**
     * @return array
     */
    function getHoursList() {
        $hours_list = array();

        for($h = 0; $h < 24; $h += 0.5)
            $hours_list[(string)$h] = format_decimal_time($h);

        return $hours_list;
    }

	function loadUpdateRequest(RowUpdate &$update, array $input) {
        if (!$this->isAdmin() && !$this->isCurrent())
            $this->showError();

        $section_with_user_data = array('user_settings', 'locale_settings', 'email_settings', 'acl_settings');

        if (in_array($this->id, $section_with_user_data)) {
            
            if ($update->new_record) {
                $password = '';
                if (isset($input['password'])) {
                    $password = $input['password'];
                } elseif (isset($input['user_hash'])) {
                    $password = $input['user_hash'];
                }
                if ($password)
                    $update->set(array('user_hash' => $this->encryptPassword($password)));
            }

            if (isset($input['is_admin']) && $input['is_admin'] == 1) {
                $access_upd = array('is_group' => 0, 'portal_only' => 0);
                $update->set($access_upd);
            }
        }

        $related_data = $update->getRelatedData('settings_rows');

        if (! $related_data) {
            $upd_user = array('data' => $input);
            $update->setRelatedData('settings_rows', $upd_user);
        }
	}
	
	function validateInput(RowUpdate &$update) {
        if ($update->new_record) {
            $password = $_REQUEST['password'];
            $confirm_password = $_REQUEST['confirm_password'];
            $message = '';

            if (empty($password)) {
                $message = 'ERR_ENTER_NEW_PASSWORD';
            } elseif (empty($confirm_password)) {
                $message = 'ERR_ENTER_CONFIRMATION_PASSWORD';
            } elseif ($password != $confirm_password) {
                $message = 'ERR_PASSWORD_MISMATCH';
            }

            if ($message)
                throw new IAHError(translate($message, 'Users'));
        }
        return true;
    }
	
	function afterUpdate(RowUpdate &$update) {
        global $app_list_strings;

		$row_updates = $update->getRelatedData('settings_rows');
		if(! $row_updates)
			return;

        $update->setRelatedData('settings_rows', null);

        if (! $update->new_record) {
		    $user_id = $update->getField('id', null, true);
            $user_name = $update->getField('user_name', null, true);
            $name = $update->getField('name', null, true);
        } else {
            $user_id = $update->saved['id'];
            $user_name = $update->saved['user_name'];
            $name = $update->saved['name'];
        }
        $this->setUserId($user_id);
        $this->user->user_name = $user_name;
        $this->user->name = $name;

        $data = $row_updates['data'];
		$preferences = $this->getPreferencesList();

		if (!empty($data['google_contacts_direction']))
			$data['google_contacts_direction'] = 'to_google';

        foreach($preferences as $key => $pref) {

            if(is_int($key)) $key = $pref;

            if(isset($data[$key])) {
                $this->setPreference($pref, $data[$key]);
            }
        }
        
        if(! empty($data['ut'])) {
        	// prompt for timezone
        	$this->setPreference('ut', 0);
        }

        if(! empty($data['should_remind']) && isset($data['reminder_time'])) {
            $this->setPreference('reminder_time', $data['reminder_time']);
        } else if(isset($data['should_remind'])) {
            $this->setPreference('reminder_time', -1);
        }

        if( isset($data['email_link_type']) && isset($app_list_strings['dom_email_link_type'][$data['email_link_type']]) ) {
            $this->setPreference('email_link_type', $data['email_link_type']);
        }

        $this->updateTabs($data);
        $this->user->savePreferencesToDB();
	}

    /**
     * Encrypt user's password
     *
     * @param string $password
     * @return string
     */
    function encryptPassword($password) {
        if (! empty($password)) {
            return strtolower(md5($password));
        } else {
            return $password;
        }
    }

    /**
     * Update display tabs
     *
     * @param  array $input - user input ($_REQUEST)
     * @return void
     */
    function updateTabs($input) {
        require_once('modules/MySettings/TabController.php');
        $tabs = new TabController();

        if(isset($input['hide_tabs_list'])) {
            $hide_tabs = array_filter(explode(',', $input['hide_tabs_list']));
            $GLOBALS['log']->fatal($hide_tabs);
			$tabs->set_user_tabs($hide_tabs, $this->user, 'hide');
        }
    }

    function getPreferencesList() {
        $prefs = array(
            'timezone',
            'currency', 'default_currency_significant_digits',
            'dateformat' => 'default_date_format',
            'timeformat' => 'default_time_format',
            'default_export_format', 'default_export_charset',
            'use_real_names', 'default_number_format', 'default_address_format',
            'default_locale_name_format',
            'user_holidays',

            'mail_fromname', 'mail_fromaddress', 'mail_sendtype', 'mail_smtpserver',
            'mail_smtpport', 'mail_smtpuser', 'mail_smtppass', 'mail_smtpauth_req',
            'mail_autobcc_address',
            'trash_cleanup_age', 'inbox_cleanup_age', 'inbox_assoc_cleanup_age',
            'sent_cleanup_age', 'sent_assoc_cleanup_age',
            'file_download_mode', 'email_display_format', 'email_compose_format',
            'show_images', 'out_of_office', 'email_sound_disable',

            'signature_id' => 'signature_default',
            'signature_prepend', 'default_email_charset',

            'calendar_publish_key',

            'google_domain',
            'google_calendar_id',
            'google_calendar_user',
            'google_calendar_pass',
            'google_calendar_direction',
            'google_calendar_call',
            'google_calendar_meeting',
            'google_calendar_task',
            'google_contacts_direction',
            'google_contacts_which',
            'google_docs_direction',
            'google_docs_doc',
            'google_docs_spreadsheet',
            'google_docs_presentation',

            'user_max_tabs' => 'max_tabs',
            'user_max_subtabs' => 'max_subtabs',
            'user_swap_last_viewed' => 'swap_last_viewed',
            'user_swap_shortcuts' => 'swap_shortcuts',
            'user_navigation_paradigm' => 'navigation_paradigm',
            'user_show_footer_module_links' => 'show_footer_module_links',
        );

        if ($this->isAdmin()) {
            $prefs = array_merge($prefs, array(
                'noncatalog_products',
                'nonstandard_prices',
                'manual_discounts',
                'standard_discounts',
                'product_costs',
                'financial_information',
            ));
        }

        return $prefs;
    }

    /**
     * Render and show email signatures popup
     *
     * @static
     * @param DetailManager $mgr
     * @param string $record
     * @param string $user_id
     * @return bool
     */
    static function renderSignaturePopup(DetailManager &$mgr, $record = '', $user_id = null) {
        $mgr->module = 'Users';
        $mgr->action = 'SaveSignature';
        $mgr->layout_name = 'Signature';
        $mgr->form_name = 'SignatureForm';
        $mgr->in_popup = true;
        $mgr->perform = 'edit';
        $mgr->model = new ModelDef('UserSignature');
        $mgr->record_id = $record;
        $mgr->editLayout = false;

		$mgr->standardInit();
		$buttons = $mgr->getStandardButtons();
		$buttons['save']['params'] = array('no_redirect' => true, 'close_popup' => true);
		$mgr->layout->setFormButtons($buttons);

        if ($user_id) {
            $sign_user_id = $user_id;
        } elseif ($mgr->record->getField('user_id')) {
            $sign_user_id = $mgr->record->getField('user_id');
        } else {
            $sign_user_id = AppConfig::current_user_id();
        }

        $hidden = array('return_module' => 'Users', 'return_action' => 'EditView', 'layout'=> 'Email',
            'return_record' => $sign_user_id, 'user_id' => $sign_user_id);
        $mgr->layout->addFormHiddenFields($hidden, false);
        $mgr->form_gen->form_obj->addHiddenFields($hidden);

        $nextAction = $mgr->performUpdate();
        if($nextAction)
            return $nextAction;

        global $pageInstance;
        $t = $mgr->getPageTitle();
        if(strlen($t))
            $pageInstance->set_title($t);

        echo $mgr->renderLayout();
    }
		
	function renderPdf(PdfFormGenerator &$gen, RowResult &$row_result, array $parents, array $context)
	{
        $this->setUserId($row_result->getField('id'));
        $this->setLocale($this->user);
        $this->setFormatter($gen);
		$lstyle = $gen->getLayout()->getType();

        if (!$this->isAdmin() && !$this->isCurrent())
			$this->showError();

		$this->context = 'view';
		$this->pdfMode = true;
		$this->pdf = $gen->pdf;
		$this->getBody($gen, $row_result);
	}
	
	function renderRowWithDescPdf($row_data)
	{
		global $mod_strings;
		$cols = array(
			'label' => array('width' => '20%'),
			'value' => array('width' => '15%'),
			'description' => array('width' => '65%'),
		);
		$data = array();
		foreach ($row_data as $row) {
			$label = isset($mod_strings[$row['label']]) ? $mod_strings[$row['label']] : $row['label'];
			$desc  = isset($mod_strings[$row['description']]) ? $mod_strings[$row['description']] : $row['description'];
			$desc = strip_tags($desc);
			$desc = str_replace('&nbsp;', ' ', $desc);
			$value = $row['value'];
			$data[] = array(
				'label' => $label,
				'value' => $value,
				'description' => $desc,
			);
		}
		$this->pdf->DrawTable($data, $cols, $this->getLabel(), false);
	}
	
	function renderRowPdf($row_data)
	{
		global $mod_strings;
		$cols = array(
			'label1' => array('width' => '20%'),
			'value1' => array('width' => '30%'),
			'label2' => array('width' => '20%'),
			'value2' => array('width' => '30%'),
		);
		$data = array();
		for ($i = 0, $size = count($row_data); $i < $size; $i++ ) {
			$row = $row_data[$i];
			$label1 = isset($mod_strings[$row['label']]) ? $mod_strings[$row['label']] : $row['label'];
			$value1 = $row['value'];
			$value1 = strip_tags($value1);
			$value1 = str_replace('&nbsp;', ' ', $value1);
			$label1 = str_replace('&nbsp;', ' ', $label1);

			$i++;
			if ($i < $size) {
				$row = $row_data[$i];
				$label2 = isset($mod_strings[$row['label']]) ? $mod_strings[$row['label']] : $row['label'];
				$value2 = $row['value'];
				$value2 = strip_tags($value2);
				$value2 = str_replace('&nbsp;', ' ', $value2);
				$label2 = str_replace('&nbsp;', ' ', $label2);
			} else {
				$label2 = $value2 = '';
			}

			$data[] = array(
				'label1' => $label1,
				'value1' => $value1,
				'label2' => $label2,
				'value2' => $value2,
			);
		}
		$this->pdf->DrawTable($data, $cols, $this->getLabel(), false);
	}

}
?>
