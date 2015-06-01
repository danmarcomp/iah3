<?php
require_once('modules/Accounts/EmailStepsTemplate.php');
require_once('modules/Emails/Email.php');
require_once('include/layout/forms/EditableForm.php');
require_once 'modules/Accounts/EmailStatement.php';
require_once 'modules/Invoice/Invoice.php';
require_once 'modules/Invoice/InvoicePDF.php';

class EmailPDF {

    /**
     * @var EditableForm
     */
    var $from_obj;

    /**
     * @var array - user input ($_REQUEST)
     */
    var $input;

    /**
     * @var int - current step
     */
    var $step;

    const SESSION_ACCOUNT_IDS = 'account_ids';
    const SESSION_INVOICE_IDS = 'invoice_ids';
    const SESSION_TEMPLATE_ID = 'template_id';
    const SESSION_INCLUDE_INVOICES = 'include_invoices';

    /**
     * @param array $input - user input ($_REQUEST)
     */
    function __construct($input) {
        $this->input = $input;
        $this->step = (int)array_get_default($this->input, 'pdf_next_step', '1');
        $this->form_obj = new EditableForm('editview', 'EmailPDFStatement');
    }

    /**
     * Load current step page
     *
     * @return string
     */
    function loadStep() {
        $step_page = '';

        $this->initStatementData();

        switch ($this->step) {
            case 1:
                $step_page = $this->initStep1();
                break;
            case 2:
                $step_page = $this->initStep2();
                break;
            case 3:
                $step_page = $this->initStep3();
                break;
            default:
                $this->destroyStatementData();
                header('Location: async.php?action=index&module=Accounts');
                break;
        }

        $this->form_obj->exportIncludes();
        return $step_page;
    }

    /**
     * Initialize and render step 1
     *
     * @return string
     */
    function initStep1() {
        if (! Email::check_email_settings()) {
            $str = return_module_language($GLOBALS['current_language'], 'Emails');
            return '<span class="error">'.$str['WARNING_SETTINGS_NOT_CONF'].'</span>';
        }

        $selected_template = $_SESSION['email_pdf_statement'][EmailPDF::SESSION_TEMPLATE_ID];
        $include_invoices = $_SESSION['email_pdf_statement'][EmailPDF::SESSION_INCLUDE_INVOICES];

        return EmailStepsTemplate::step1($this->form_obj, $selected_template, $include_invoices);
    }

    /**
     * Initialize and render step 2
     *
     * @return string
     */
    function initStep2() {
        $objEmailStatement = new CEmailStatement();
        $created_emails = array();
        $errors = array();

        foreach ($_SESSION['email_pdf_statement'][EmailPDF::SESSION_ACCOUNT_IDS] as $strId) {
            $objAccount = new Account();
            $objAccount->retrieve($strId);

            if ($arContactIds = $objAccount->getAccountingContacts()) {
                $arNotes = array();
                $objStatementPDF = new StatementPDF();
                // Generate the PDF
                $objStatementPDF->do_print($objAccount);
                $arNotes[] = $objStatementPDF->create_pdf_attachment($objAccount);
                $arInvoiceIds = array();

                if (!empty($_SESSION['email_pdf_statement'][EmailPDF::SESSION_INVOICE_IDS][$strId])) {
                    $arInvoiceIds = $_SESSION['email_pdf_statement'][EmailPDF::SESSION_INVOICE_IDS][$strId];                    
                } elseif ($_SESSION['email_pdf_statement'][EmailPDF::SESSION_INCLUDE_INVOICES]) {
                    $arInvoiceIds = $objAccount->getUnpaidInvoiceIds();
                }


                if (is_array($arInvoiceIds) && sizeof($arInvoiceIds) > 0) {
                    foreach ($arInvoiceIds as $invoiceId) {
                        $objInvoice = new Invoice();
                        $objInvoice->retrieve($invoiceId);
                        $objInvoicePDF = new InvoicePDF();
                        $objInvoicePDF->do_print($objInvoice);
                        $arNotes[] = $objInvoicePDF->create_pdf_attachment($objInvoice);
                    }
                }

                $arEmails = $objEmailStatement->createEmailDrafts($objAccount->id, $arContactIds, $arNotes, $_SESSION['email_pdf_statement'][EmailPDF::SESSION_TEMPLATE_ID]);

                foreach ($arEmails as $strEmailId => $arEmailInfo) {
                    $format = translate('LBL_MASS_PDF_DRAFT_EMAIL', 'Accounts');

                    $string = sprintf(
                        $format,
                        "<a href='index.php?module=Emails&action=DetailView&record={$strEmailId}' class='tabDetailViewDFLink' target='_blank'>{$arEmailInfo['subject']}</a>",
                        "<a href='index.php?module=Accounts&action=DetailView&record={$objAccount->id}' class='tabDetailViewDFLink' target='_blank'>{$objAccount->name}</a>",
                        "<a href='index.php?module=Contacts&action=DetailView&record={$arEmailInfo['contact_id']}' class='tabDetailViewDFLink' target='_blank'>{$arEmailInfo['contact_name']}</a>"
                    );

                    $created_emails[] = array('ID' => $strEmailId, 'DESCRIPTION' => $string);
                }

            } else {
                $format = translate('LBL_MASS_PDF_NO_CONTACT', 'Accounts');

                $string = sprintf(
                    $format,
                    "<a href='index.php?module=Accounts&action=DetailView&record={$objAccount->id}' class='tabDetailViewDFLink' target='_blank'>{$objAccount->name}</a>"
                );

                $errors[] = $string;
            }
        }

        return EmailStepsTemplate::step2($this->form_obj, $created_emails, $errors);
    }

    /**
     * Initialize and render step 3
     *
     * @return string
     */
    function initStep3() {
        global $timedate;
        $emails = array();
        $errors = array();

        if (isset($this->input['email_ids'])) {
            foreach ($this->input['email_ids'] as $strId => $cheked) {
                $objEmail = new Email();
                $objEmail->retrieve($strId);
                // Parse the address arrays
                $objEmail->regenerate_email_arrays();
                // Regenerate the attachment array
                $objEmail->regenerate_attachment_array();

                if ($objEmail->send2($errors)) {
                    $objEmail->folder = EmailFolder::get_std_folder_id($objEmail->assigned_user_id, STD_FOLDER_SENT);
                    $objEmail->type = 'out';
                    $objEmail->status = 'sent';
                    $objEmail->date_start = $timedate->to_display_date_time(date('Y-m-d H:i:s'), true, false);
                    $objEmail->save();

                    $format = translate('LBL_MASS_PDF_SENT_EMAIL', 'Accounts');
                    $string = sprintf(
                        $format,
                        "<a href='index.php?module=Emails&action=DetailView&record={$objEmail->id}' class='tabDetailViewDFLink' target='_blank'>{$objEmail->name}</a>",
                        "{$objEmail->to_addrs}"
                    );
                    $emails[] = $string;
                } else {
                    $format = translate('LBL_MASS_PDF_ERROR_EMAIL', 'Accounts');
                    $string = sprintf(
                        $format,
                        "<a href='index.php?module=Emails&action=DetailView&record={$objEmail->id}' class='tabDetailViewDFLink' target='_blank'>{$objEmail->name}</a>",
                        "{$objEmail->to_addrs}"
                    );
                    $errors[] =  $string;
                }
            }
        }

        return EmailStepsTemplate::step3($emails, $errors);
    }

    /**
     * Initialize the PDF statement temp data
     *
     * @return void
     */
    function initStatementData() {
        if (!array_key_exists('email_pdf_statement', $_SESSION)) {
            $_SESSION['email_pdf_statement'] = array (
                EmailPDF::SESSION_ACCOUNT_IDS => array(),
                EmailPDF::SESSION_TEMPLATE_ID => '',
                EmailPDF::SESSION_INCLUDE_INVOICES => false,
            );
        }

        if ($this->step == 1) {

            if (! empty($_SESSION['send_account_ids'])) {
                $_SESSION['email_pdf_statement'][EmailPDF::SESSION_ACCOUNT_IDS] = $_SESSION['send_account_ids'];
                unset($_SESSION['send_account_ids']);

                if (! empty($_SESSION['send_invoice_ids'])) {
                    $_SESSION['email_pdf_statement'][EmailPDF::SESSION_INVOICE_IDS] = $_SESSION['send_invoice_ids'];
                    unset($_SESSION['send_invoice_ids']);
                }

            } elseif (isset($this->input['list_uids'])) {
                $_SESSION['email_pdf_statement'][EmailPDF::SESSION_ACCOUNT_IDS] = explode(';', $this->input['list_uids']);
            }

        } else if ($this->step == 2) {
            if (isset($this->input['template_id'])) {
                $_SESSION['email_pdf_statement'][EmailPDF::SESSION_TEMPLATE_ID] = $this->input['template_id'];
                $include_invoices = false;

                if (array_key_exists('include_invoices', $this->input) && ($this->input['include_invoices'] == 1)) {
                    $include_invoices = true;
                }

                $_SESSION['email_pdf_statement'][EmailPDF::SESSION_INCLUDE_INVOICES] = $include_invoices;
            }
        }
    }

    /**
     * Destroy PDF statement temp data
     *
     * @return void
     */
    function destroyStatementData() {
        unset($_SESSION['email_pdf_statement']);
    }
}
?>