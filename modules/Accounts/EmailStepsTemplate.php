<?php
class EmailStepsTemplate {

    /**
     * Private constructor because class includes only static methods
     *
     */
    private function __construct() {}

    /**
     * Render template for Email PDF Statement step 1
     *
     * @static
     * @param EditableForm $form_obj
     * @param string $template
     * @param bool $include_invoices
     * @return string
     */
    static function step1(EditableForm $form_obj, $template = '', $include_invoices = false) {
        global $mod_strings, $app_strings;


        $spec = array('name' => 'template_id', 'options_function' => array('EmailTemplate', 'get_folder_options'), 'required' => true);
        $template_select = $form_obj->renderSelect($spec, $template);

        $spec = array('name' => 'include_invoices');
        $include_check = $form_obj->renderCheck($spec, $include_invoices);
        $header = EmailStepsTemplate::get_form_header(1);
        $footer = EmailStepsTemplate::get_form_footer(1);

        $html = <<<EOQ
            {$header}
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">
            <tr><td>
            <table border="0" cellspacing="0" cellpadding="0">
            <tr><th align="left" class="dataLabel" colspan="4"><h4>{$mod_strings['SELECT_OPTIONS']}</h4></th></tr>
            <tr>
            <td class="dataLabel">{$mod_strings['LBL_TEMPLATE']} <span class="required">{$app_strings['LBL_REQUIRED_SYMBOL']}</span></td>
            <td class="dataField">{$template_select}</td>
            </tr><tr>
            <td class="dataLabel">{$mod_strings['LBL_INCLUDE_INVOICES']}</td>
            <td class="dataField">{$include_check}</td>
            </tr>
            </table>
            </td></tr>
            </table>
            {$footer}
EOQ;

        return $html;
    }

    /**
     * Render template for Email PDF Statement step 2
     *
     * @static
     * @param EditableForm $form_obj
     * @param array $emails
     * @param array $errors
     * @return string
     */
    static function step2(EditableForm $form_obj, $emails = array(), $errors = array()) {
        global $mod_strings;

        $header = EmailStepsTemplate::get_form_header(2);
        $onsubmit = "return checkEmails();";
        $footer = EmailStepsTemplate::get_form_footer(2, $onsubmit);

        $html = <<<EOQ
            {$header}
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">
            <tr><td>
            <table border="0" cellspacing="0" cellpadding="0">
            <tr><th align="left" class="dataLabel" colspan="2"><h4>{$mod_strings['EMAIL_PDF_RESULT']}</h4></th></tr>
EOQ;

        if (sizeof($emails) > 0) {
            $html .= '<tr><td class="dataLabel" colspan="2"><span>'.$mod_strings['LBL_PDF_STATEMENTS_CREATED'].'</span></td></tr>';

            foreach ($emails as $index => $details) {
                $spec = array('name' => 'email_ids['.$details['ID'].']', 'onchange' => "updateCounter(this.getValue());");
                $email_check = $form_obj->renderCheck($spec, 1);
                $html .= <<<EOQ
                    <tr>
                    <td class="dataField" style="text-align: right; padding-left: 10px;"><span>{$email_check}</span></td>
                    <td class="dataLabel"><span>{$details['DESCRIPTION']}</span></td>
                    </tr>
EOQ;

            }
        }

        if (sizeof($errors) > 0) {
            $html .= '<tr><td class="dataLabel" colspan="2"><span>'.$mod_strings['LBL_PDF_STATEMENTS_ERROR'].'</span></td></tr>';

            foreach ($errors as $index => $value) {
                $html .= <<<EOQ
                    <tr>
                    <td class="dataLabel" colspan="2">
                    <span>
                        {$value}
                    </span>
                    </td>
                    </tr>
EOQ;

            }
        }

        $html .= "</table></td></tr></table>{$footer}";
        global $pageInstance;
        $pageInstance->add_js_include('modules/Accounts/email_pdf.js', null, LOAD_PRIORITY_FOOT);
        $pageInstance->add_js_literal("setCounter(".sizeof($emails).")", null, LOAD_PRIORITY_FOOT);


        return $html;
    }

    /**
     * Render template for Email PDF Statement step 3
     *
     * @static
     * @param array $emails
     * @param array $errors
     * @return string
     */
    static function step3($emails = array(), $errors = array()) {
        global $mod_strings;

        $header = EmailStepsTemplate::get_form_header(3);
        $footer = EmailStepsTemplate::get_form_footer(3);

        $html = <<<EOQ
            {$header}
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">
            <tr><td>
            <table border="0" cellspacing="0" cellpadding="0">
            <tr><th align="left" class="dataLabel" colspan="2"><h4>{$mod_strings['EMAIL_PDF_RESULT']}</h4></th></tr>
EOQ;

        if (sizeof($emails) > 0) {
            $html .= '<tr><td class="dataLabel" colspan="2"><span>'.$mod_strings['EMAIL_SENT_RESULT'].'</span></td></tr>';

            for ($i = 0; $i < sizeof($emails); $i++) {
                $html .= '<tr><td class="dataLabel" colspan="2"><span>'.$emails[$i].'</span></td></tr>';
            }
        }

        if (sizeof($errors) > 0) {
            $html .= '<tr><td class="dataLabel" colspan="2"><span>'.$mod_strings['LBL_PDF_STATEMENTS_ERROR'].'</span></td></tr>';

            for ($i = 0; $i < sizeof($errors); $i++) {
                $html .= '<tr><td class="dataLabel" colspan="2"><span>'.$errors[$i].'</span></td></tr>';
            }
        }

        $html .= '</table></td></tr></table>' . $footer;
        return $html;
    }

    /**
     * Get HTML form header
     *
     * @static
     * @param int $current_step
     * @return string
     */
    static function get_form_header($current_step) {
        $next_step = $current_step + 1;

        $header = <<<EOQ
            <form name="EmailPDFStatement" method="POST" onsubmit="return SUGAR.ui.sendForm(this);">
            <input type="hidden" name="module" value="Accounts" />
            <input type="hidden" name="pdf_next_step" value="{$next_step}" />
            <input type="hidden" name="action" value="SendPDFStatements" />
            <input type="hidden" name="email_counter" value="0" />
            <p>
EOQ;

        return $header;
    }

    /**
     * Get HTML form footer
     *
     * @static
     * @param int $current_step
     * @param string $onsubmit - form onsubmit
     * @return string
     */
    static function get_form_footer($current_step, $onsubmit = '') {
        global $mod_strings;
        $previous_step = $current_step - 1;
        if ($previous_step <= 0)
            $previous_step = 1;

        if ($current_step == 2) {
            $next_button_lbl = $mod_strings['LBL_SEND_SELECTED_EMAILS'];
        } elseif ($current_step == 3) {
            $next_button_lbl = $mod_strings['LBL_FINISH'];
        } else {
            $next_button_lbl = $mod_strings['LBL_NEXT'];
        }

        $previous_button = '<input title="'.$mod_strings['LBL_PREVIOUS'].'" accessKey="" class="input-button" type="submit" name="button" value="  '.$mod_strings['LBL_PREVIOUS'].'  " onclick="this.form.pdf_next_step.value = \''.$previous_step.'\';">';
        if ($current_step == 1)
            $previous_button = '';

        $form_onsubmit = '';
        if ($onsubmit != '')
            $form_onsubmit = 'onclick="'.$onsubmit.'"';
        
        $footer = <<<EOQ
            </p>
            <p>
            <table width="100%" cellpadding="2" cellspacing="0" border="0"><tr>
            <td align="right">
                {$previous_button}
                <input title="{$next_button_lbl}" accessKey="" class="input-button" type="submit" name="button" {$form_onsubmit} value="  {$next_button_lbl}  ">
            </td>
            </tr>
            </table>
            </p>
            </form>
EOQ;

        return $footer;
    }
}
?>