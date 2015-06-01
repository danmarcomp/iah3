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
require_once('modules/Invoice/Invoice.php');
require_once('modules/Invoice/InvoicePDF.php');

require_once 'include/database/RowUpdate.php';
require_once 'include/database/ListQuery.php';

class MonthlyServiceUtil {
		
	/**
	 * Constructor
	 * 
	 * private because class has only static methods
	 */	
	private function __construct() {}


	/**
	 * Create Invoice for Recurring Service
	 * 
	 * @param array $serviceData
	 * @return string
	 */
	public static function createInvoice($serviceData) {
		global $timedate;
		$invoiceId = null;
		$inv = RowUpdate::blank_for_model('Invoice', $serviceData['assigned_user_id']);
		Invoice::init_record($inv, array('quote_id' => $serviceData['quote_id']));
		$inv->set('terms', $serviceData["invoice_terms"]);
		$inv->set('due_date', Invoice::calc_due_date($serviceData['invoice_terms']));
		$inv->set('purchase_order_num', $serviceData["purchase_order_num"]);
		$inv->set('description', translate('LBL_RECURRING_SERVICE', 'MonthlyServices') .': '.$serviceData["booking_category_name"]. "\n".
				translate('LBL_START_DATE', 'MonthlyServices') .': '. $timedate->to_display_date($serviceData["invoice_start_date"], false). ', '.
				translate('LBL_END_DATE', 'MonthlyServices') .': '. $timedate->to_display_date($serviceData["invoice_end_date"], false));
		if(! $inv->validate() || ! $inv->save()) {
			$GLOBALS['log']->error("Error saving recurring invoice: " . $inv->getErrors(true));
		}
		$invoiceId = $inv->getPrimaryKeyValue();
	    return $invoiceId;
	}

	/**
	 * Create PDF by Invoice
	 *
	 * @param string $invoiceId
     * @param string $addressId
	 * @return string
	 */
	public static function createPdf($invoiceId, $addressId) {
		$pdfId = null;

        $pdf = new InvoicePDF();
		$pdf->useCompanyAddress($addressId);
        $lq = $pdf->createListQuery($invoiceId);
        $invoice = $lq->runQuerySingle();

        if (! $invoice->failed) {
            global $timedate;
            $invoice->row['invoice_date'] = $timedate->to_display_date($invoice->row['invoice_date'], false);
            $invoice->row['due_date'] = $timedate->to_display_date($invoice->row['due_date'], false);
            $pdf->setLayout($pdf->system_layout());
	        $pdf->do_print($invoice);
	        $pdfId = $pdf->create_pdf_attachment($invoice);
		}
		
		return $pdfId;
	}
	
	/**
	 * Send Invoice to Contact and CC User
	 * 
	 * @param array $serviceData
	 * @param string $invoiceId
	 * @param string $pdfId
	 */
	public static function sendInvoice($serviceData, $invoiceId, $pdfId) {
        require_once('include/layout/NotificationManager.php');
        require_once ('include/SugarPHPMailer.php');
		global $current_language, $current_user, $app_strings;

		$subjectFormat = translate('LBL_INVOICE_EMAIL_SUBJECT', 'MonthlyServices', '%s %s to %s');
		$dateFormat = array_get_default($app_strings, 'LBL_DATE_SHORT_FORMAT', '%b %e, %Y');
		$subjectText = sprintf($subjectFormat,
            $serviceData['booking_category_name'] .", ". $serviceData['account_name'] . ", ",
            custom_strftime($dateFormat, strtotime($serviceData['invoice_start_date'])),
            custom_strftime($dateFormat, strtotime($serviceData['invoice_end_date']))
        );

        $template_vars = array(
            'BOOKING_CATEGORY_NAME' => array('value' => $serviceData['booking_category_name']),
            'SERVICE_SUBJECT' => array('value' => $subjectText, 'in_subject' => true)
        );
        $mail_template = NotificationManager::loadCustomMessage('MonthlyServices', 'MonthlyServiceInvoice', $template_vars);

		$subject = $mail_template['subject'];
		$body = $mail_template['body'];
	
		$mail = new SugarPHPMailer();
		$mail->InitForSend(true);
		$mail->ClearAllRecipients();
		$mail->ClearReplyTos();
		if($serviceData['contact_email']) {
			$mail->AddAddress($serviceData['contact_email'], $serviceData['contact_name']);
			if($serviceData['cc_user_email'])
				$mail->AddCC($serviceData['cc_user_email'], $serviceData['cc_user_name']);
		} else if($serviceData['cc_user_email'])
			$mail->AddAddress($serviceData['cc_user_email'], $serviceData['cc_user_name']);
		else
			return;
		$mail->Subject =  $subject;
		$mail->Body = wordwrap($body, 900);
				
		$note = new Note();
		
		if ($note->retrieve($pdfId)) {
		
			if (!empty($note->file->temp_file_location) && is_file($note->file->temp_file_location)) {
				$fileLocation = $note->file->temp_file_location;
				$filename = $note->file->original_file_name;
				$mimeType = $note->file->mime_type;
			} else {
				$fileLocation = rawurldecode(UploadFile::get_file_path($note->filename, $note->id));
				$filename = $note->id.$note->filename;
				$mimeType = $note->file_mime_type;
			}
	
			$filename = substr($filename, 36, strlen($filename));
			$fileLocation = from_html($fileLocation);
			$filename = from_html($filename);
			
			$mail->AddAttachment($fileLocation, $filename, 'base64', $mimeType);
			
		}	
		
		$mail->preppedForOutbound = false;
		$mail->prepForOutbound();
		$mail->send();
		$mail->FinishedSend();		
	}
	
	/**
	 * Send Recurring Service Expire Remainder to Contact and CC User
	 * 
	 * @param array $serviceData
	 */
	public static function sendServiceExpiryRemainder($serviceData) {
        require_once('include/layout/NotificationManager.php');
		require_once ('include/SugarPHPMailer.php');
		global $current_language, $current_user;
		
        $template_vars = array(
            'BOOKING_CATEGORY_NAME' => array('value' =>  $serviceData['booking_category_name'], 'in_subject' => true),
        );
        $mail_template = NotificationManager::loadCustomMessage('MonthlyServices', 'MonthlyServiceExpiryRemainder', $template_vars);

		$subject = $mail_template['subject'];
		$body = $mail_template['body'];
	
		$mail = new SugarPHPMailer();
		$mail->InitForSend(true);
		$mail->ClearAllRecipients();
		$mail->ClearReplyTos();
		if($serviceData['contact_email']) {
			$mail->AddAddress($serviceData['contact_email'], $serviceData['contact_name']);
			if($serviceData['cc_user_email'])
				$mail->AddCC($serviceData['cc_user_email'], $serviceData['cc_user_name']);
		} else if($serviceData['cc_user_email'])
			$mail->AddAddress($serviceData['cc_user_email'], $serviceData['cc_user_name']);
		else
			return;
		$mail->Subject =  $subject;
		$mail->Body = wordwrap($body, 900);
		$mail->preppedForOutbound = false;
		$mail->prepForOutbound();
		$mail->send();
		$mail->FinishedSend();		
	}

	public static function isFinished($id)
	{
		$service = ListQuery::quick_fetch('MonthlyService', $id);
		$res = true;
		if ($service) {
			if ($service->getField('next_invoice') <= $service->getField('end_date'))
				$res = false;
		}
		return $res;
	}	
}
?>
