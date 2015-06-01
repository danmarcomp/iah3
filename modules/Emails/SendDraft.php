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
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once 'modules/Emails/Email.php';
require_once 'modules/Emails/widgets/EmailWidget.php';
require_once 'modules/Notes/Note.php';

$record = array_get_default($_REQUEST, 'record');

if ($record) {
	$lq = new ListQuery('Email', true);
	$lq->addFields(array('description', 'description_html'));
    $lq->addFilterPrimaryKey($record);
    $email = $lq->runQuerySingle();

    if ($email != null) {
        $upd = RowUpdate::for_result($email);
        $upd_after_send = array();
        $send_as = 'html';
        if (! empty($_REQUEST['send_as']))
            $send_as = $_REQUEST['send_as'];

        $to = normalize_emails_array(parse_addrs($upd->getField('to_addrs')), null, true, true);
        $cc = normalize_emails_array(parse_addrs($upd->getField('cc_addrs')), null, true, true);
        $bcc = normalize_emails_array(parse_addrs($upd->getField('bcc_addrs')), null, true, true);
        $recipients = array('to' => $to, 'cc' => $cc, 'bcc'=> $bcc);

        $descriptions = array(
        	'plain' => $upd->getField('description'),
        	'html' => $upd->getField('description_html'),
        );

        $after_replace = EmailWidget::replaceFields($upd, $descriptions['plain'], $descriptions['html']);
        $upd->set(array('name' => $after_replace['name']));
        $description = $after_replace['plain'];
        $description_html = $after_replace['html'];
        $descriptions = array('html' => $description_html, 'plain' => $description);

        $attachments = array();
        $attachments_result = EmailWidget::fetchAttachments('Emails', $record);

        if ($attachments_result && ! $attachments_result->failed)
            $attachments = $attachments_result->getRows();

        if (EmailWidget::send($upd, $recipients, $descriptions, $attachments, $send_as)) {
            global $timedate;
            $send_error = false;
            $upd_after_send['status'] = 'sent';
            $upd_after_send['date_start'] = gmdate('Y-m-d H:i:s');
        } else {
            $send_error = true;
            $upd_after_send['status'] = 'send_error';
        }

        $upd->set($upd_after_send);
        $upd->save();

        global $mod_strings;

        if ($send_error) {
            $errmsg = empty($GLOBALS['send_error_msg']) ? '' : '<br>&nbsp;&nbsp;&nbsp;'.htmlentities($GLOBALS['send_error_msg']);
            add_flash_message($mod_strings['LBL_ERROR_SENDING_EMAIL'].$errmsg, 'error');
        } else {
            add_flash_message($mod_strings['LBL_MESSAGE_SENT']);
        }
    }
}

return array('redirect', array('module' => 'Emails', 'action' => 'DetailView', 'record' => $record, 'record_perform' => 'view'));

?>