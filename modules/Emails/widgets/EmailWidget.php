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
require_once('modules/Emails/Email.php');
require_once('modules/EmailTemplates/EmailTemplate.php');
require_once('modules/EmailTemplates/TemplateParser.php');
require_once('modules/EmailFolders/EmailFolder.php');
require_once('modules/Emails/Forms.php');
require_once('modules/Emails/utils.php');
require_once('include/utils/html_utils.php');

class EmailWidget extends FormTableSection {
    const MAX_ATTACHMENTS = 10;

	function init($params, $model=null) {
		parent::init($params, $model);
		if(! $this->id)
			$this->id = 'email_form';
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
        $fields = array('from_name', 'from_addr', 'to_addrs', 'to_addrs_names',
            'cc_addrs', 'cc_addrs_names', 'bcc_addrs', 'bcc_addrs_names', 'name', 'type',
            'mailbox', 'message_id', 'thread_id', 'reply_to_addr', 'reply_to_name', 'date_start',
            'contact', 'replace_fields', 'in_reply_to', 'isread', 'status', 'assigned_user_id',
            'description', 'description_html');

		return $fields;
	}
	
	function renderHtmlView(HtmlFormGenerator &$gen, RowResult &$row_result) {
        global $mod_strings;
        $input = $_REQUEST;

        $from = '&nbsp;';
        $to = '&nbsp;';
        $cc = '&nbsp;';
        $bcc = '&nbsp;';
        $subject = '&nbsp;';
        $attachments = '&nbsp;';
        $email_body = '&nbsp;';
        $record = $row_result->getField('id');

        if ($record) {
            $from = format_email_link($row_result->getField('from_addr'), $row_result->getField('from_name'), '', 'tabDetailViewDFLink', true);
            $to = display_format_addresses($row_result->row, 'to_addrs');
            $cc = display_format_addresses($row_result->row, 'cc_addrs');
            $bcc = display_format_addresses($row_result->row, 'bcc_addrs');
            $subject = $row_result->formatted['name'];
            $attachments = $this->renderAttachmentsView($row_result->getModuleDir(), $record, $mod_strings['LBL_ATTACHMENT_VIEW']);
            $email_body = $this->renderBody($input, $row_result);
        }

        $body = <<<EOQ
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabDetailView" style="margin-top: 0.5em">
            <tr>
            <td class="tabDetailViewDL" width="20%">{$mod_strings['LBL_FROM']}</td>
            <td class="tabDetailViewDF" colspan="3" width="80%">{$from}</td>
            </tr>
            <tr>
            <td class="tabDetailViewDL" width="20%">{$mod_strings['LBL_TO']}</td>
            <td class="tabDetailViewDF" colspan="3" width="80%">{$to}</td>
            </tr>
            <tr>
            <td class="tabDetailViewDL" width="20%">{$mod_strings['LBL_CC']}</td>
            <td class="tabDetailViewDF" colspan="3" width="80%">{$cc}</td>
            </tr>
            <tr>
            <td class="tabDetailViewDL" width="20%">{$mod_strings['LBL_BCC']}</td>
            <td class="tabDetailViewDF" colspan="3" width="80%">{$bcc}</td>
            </tr>
            <tr>
            <td class="tabDetailViewDL" width="20%">{$mod_strings['LBL_SUBJECT']}</td>
            <td class="tabDetailViewDF" colspan="3" width="80%">{$subject}</td>
            </tr>
            <tr>
            <td class="tabDetailViewDL" width="20%">{$mod_strings['LBL_BODY']}</td>
            <td class="tabDetailViewDF" colspan="3" width="80%">{$email_body}</td>
            </tr>
            <tr>
            <td class="tabDetailViewDL" width="20%">{$mod_strings['LBL_ATTACHMENTS']}</td>
            <td class="tabDetailViewDF" colspan="3" width="80%">{$attachments}</td>
            </tr>
            </table>
EOQ;

        return $body;
	}

	function renderHtmlEdit(HtmlFormGenerator &$gen, RowResult &$row_result) {
        global $current_user, $mod_strings, $app_strings;
        $input = $_REQUEST;
        $sep = $app_strings['LBL_SEPARATOR'];

        $record = $row_result->getField('id');
        $reply = '';
        $forward = array_get_default($input, 'forward', '');

        $descriptions = array(
        	'plain' => $row_result->getField('description'),
        	'html' => $row_result->getField('description_html'),
        );

        $from_name = $gen->form_obj->renderField($row_result, 'from_name');
        $from_addr = $gen->form_obj->renderField($row_result, 'from_addr');
        $to = $gen->form_obj->renderField($row_result, 'to_addrs');
        $cc = $gen->form_obj->renderField($row_result, 'cc_addrs');

        $all_bcc = $this->addAdditionalBcc($input, $row_result->getField('bcc_addrs', '', false));
        $row_result->assign('bcc_addrs', $all_bcc);
        $bcc = $gen->form_obj->renderField($row_result, 'bcc_addrs');

        $subject = $gen->form_obj->renderField($row_result, 'name');
        $type = $row_result->getField('type');

        if (! $row_result->new_record) {
            $descriptions['html'] = show_inline_images(cleanHTML($descriptions['html'], true, true));
        } else {
            $descriptions['html'] = $this->addSignature($descriptions['html'], $type);
        }

        $email_body = $this->renderHtmlEditor($gen, $descriptions);
        $attachments = $this->renderAttachmentsEdit($record, $input);
        $upload = $this->renderAttachmentUpload($gen);
        $body = "";

        if ((!Email::check_email_settings(false) || empty($from_addr) || empty($from_name))  && ($type == 'out' || $type == 'draft')) {
            $body .= "<font color='red'>".$mod_strings['WARNING_SETTINGS_NOT_CONF']." <a href='index.php?module=Users&action=EditView&record=".$current_user->id."'>".$mod_strings['LBL_EDIT_MY_SETTINGS']."</a></font>";
        }

        $return_module_hidden = '';
        $return_action_hidden = '';
        if (empty($input['return_module']))
            $return_module_hidden = '<input type="hidden" name="return_module" value="Emails" />';
        if (empty($input['return_action']))
            $return_action_hidden = '<input type="hidden" name="return_action" value="DetailView" />';

        $body .= <<<EOQ
            {$return_module_hidden}
            {$return_action_hidden}
            <input type="hidden" name="contact_id" value="{$row_result->getField('contact_id')}" />
            <input type="hidden" name="user_id" value="{$current_user->id}" />
            <input type="hidden" name="send" value="" />
            <input type="hidden" name="type" value="{$type}" />
            <input type="hidden" name="reply" value="{$reply}" />
            <input type="hidden" name="forward" value="{$forward}" />
            <input type="hidden" name="message_id" value="{$row_result->getField('message_id')}" />
            <input type="hidden" name="thread_id" value="{$row_result->getField('thread_id')}" />
            <input type="hidden" name="in_reply_to" value="{$row_result->getField('in_reply_to')}" />
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm" style="margin-top: 0.5em">
            <tr>
            <td class="dataLabel" width="20%">{$mod_strings['LBL_FROM_NAME']}</td>
            <td class="dataField" width="30%">{$from_name}</td>
            <td class="dataLabel" width="20%">{$mod_strings['LBL_FROM_EMAIL']}</td>
            <td class="dataField" width="30%">{$from_addr}</td>
            </tr>
            <tr>
            <td class="dataLabel" width="20%">&nbsp;</td>
            <td class="dataLabel" width="80%" colspan="3">{$mod_strings['LBL_NOTE_SEMICOLON']}</td>
            </tr>
            <tr>
            <td class="dataLabel" width="20%">{$mod_strings['LBL_TO']}</td>
            <td class="dataField" colspan="3" width="80%"><div id="to-input">{$to}&nbsp;</div></td>
            </tr>
            <tr>
            <td class="dataLabel" width="20%">{$mod_strings['LBL_CC']}</td>
            <td class="dataField" colspan="3" width="80%">{$cc}&nbsp;</td>
            </tr>
            <tr>
            <td class="dataLabel" width="20%">{$mod_strings['LBL_BCC']}</td>
            <td class="dataField" colspan="3" width="80%">{$bcc}&nbsp;</td>
            </tr>
            <tr>
            <td class="dataLabel" width="20%">{$mod_strings['LBL_SUBJECT']}<span class="requiredStar">*</span></td>
            <td class="dataField" colspan="3" width="80%">{$subject}</td>
            </tr>
EOQ;

        if ($type != 'archived') {
            $replace_fields = $gen->form_obj->renderField($row_result, 'replace_fields');
            $compose_format = $this->getCheckedComposeFormat();

            $body .= <<<EOQ
                <tr>
                <td class="dataLabel" width="20%">{$mod_strings['LBL_SEND_AS']}</td>
                <td class="dataField" colspan="3">
                <table border="0" width="100%"><tr><td width="25%">
                    <label><input type="radio" class="radio" name="send_as" value="plain" {$compose_format['plain']} /> {$mod_strings['LBL_SEND_AS_PLAIN']}</label>
                    &nbsp;
                    <label><input type="radio" class="radio" name="send_as" value="html" {$compose_format['html']} /> {$mod_strings['LBL_SEND_AS_HTML']}</label>
                </td>
                <td class="dataLabel" width="40%" style="text-align: left">{$mod_strings['LBL_USE_TEMPLATE']}{$sep}&nbsp;{$this->renderTemplateSelect($gen)}</td>
                <td class="dataLabel" width="35%">{$mod_strings['LBL_REPLACE_TEMPLATE_FIELDS']}{$sep}&nbsp;{$replace_fields}</td>
                </tr></table></td>
                </tr>
EOQ;
        }
            
        $body .= <<<EOQ
            <tr>
            <td class="dataLabel" width="20%">{$mod_strings['LBL_BODY']}</td>
            <td class="dataField" colspan="3" width="80%">{$email_body}</td>
            </tr>
            <tr>
            <td class="dataLabel" width="20%">{$mod_strings['LBL_ATTACHMENTS']}</td>
            <td class="dataField" colspan="3" width="80%">{$attachments}{$upload}</td>
            </tr>
            </table>
EOQ;

        $layout =& $gen->getLayout();
        $layout->addJSLanguage('Emails');
        $layout->addScriptInclude('modules/Emails/Email.js');
        $this->addAttachmentsJs($layout);

        return $body;
	}

    
    /**
     * Render email body for DetailView
     *
     * @param  array $input - user input ($_REQUEST)
     * @param  RowResult $row_result
     * @return string
     */
    function renderBody($input, $row_result) {
        $format = $this->getShowFormat($input);
        $body = '';
        $links = '';
        $body_format = 'html';
        $has_images = false;

        $description_html = $row_result->getField('description_html');
        $description = $row_result->getField('description');
        if ($description_html != '' && $format['show_html'] == true)  {
            $html = cleanHTML($description_html);
            if (has_images($html)) $has_images = true;

            if ($format['show_images'] == true) {
                $html = show_inline_images($html);
            } else {
                $html = remove_images($html);
            }

            $body .= $html;
        } else {
            if ($description != '') {
                $desc = nl2br(url2html(to_html($description)));
                $desc = preg_replace('~\t~', '    ', $desc);
                $desc = preg_replace('~  ~', '&nbsp; ', $desc);
                if($description_html != '')
                	$body_format = 'plain_from_html';
                else
					$body_format = 'plain';
            } else {
                $desc = nl2br(html2plaintext(to_html($description_html), false));
                $body_format = 'plain_from_html';
            }
            if ($desc != '')
                $body .= '<div style="font-family: monospace" id="email_body">' . $desc . '</div>';
        }

        if ($body != '')
            $links = $this->renderBodyLinks($row_result->getField('id'), $body_format, $format['show_images'], $has_images);

        return $links . $body;
    }

    /**
     * Get email body show format
     *
     * @param  array $input - user input ($_REQUEST)
     * @return array - ['show_html'] => true/false, ['show_images'] => true/false 
     */
    function getShowFormat($input) {
        global $current_user;
        
        $show_html = $current_user->getPreference('email_display_format') == 'html';

        if (! empty($input['display_format']) && $input['display_format'] == 'html') {
            $show_html = true;
        } elseif (! empty($input['display_format']) && $input['display_format'] == 'plain') {
            $show_html = false;
        }

        $show_images = $current_user->getPreference('show_images');

        if (! empty($input['show_images'])) {
            $show_images = true;
        } elseif (! empty($input['hide_images'])) {
            $show_images = false;
        }

        return array('show_html' => $show_html, 'show_images' => $show_images);
    }

    /**
     * Render additional email body links for DetailView
     *
     * @param string $record
     * @param string $format
     * @param bool $show_images
     * @param bool $has_images - has email images in body or not
     * @return string
     */
    function renderBodyLinks($record, $format = 'html', $show_images = true, $has_images = false) {
        global $mod_strings;

        $links = array();
		$lbl = array_get_default($mod_strings, 'LNK_OPEN_IN_NEW_WINDOW', 'Open in New Window');
		if (empty($_REQUEST['hide_theme_headers'])) {
	        $links[] = '<div class="input-icon icon-tlink"></div>&nbsp;<a href="index.php?module=Emails&action=DetailView&record='.$record.'&hide_theme_headers=1" class="tabDetailViewDFLink" target="_blank">'.$lbl.'</a>';
		}
		
        if ($format == 'html')  {

            if ($has_images) {
                $url = preg_replace('~&?(hide_images|show_images)=[a-z]*~i', '', getCurrentURL());
                if ($show_images) {
                    $lbl = array_get_default($mod_strings, 'LNK_HIDE_IMAGES', 'Hide Images');
                    $links[] = '<a href="'.$url.'&hide_images=true" class="tabDetailViewDFLink">'.$lbl.'</a>';
                } else {
                    $lbl = array_get_default($mod_strings, 'LNK_SHOW_IMAGES', 'Show Images');
                    $links[] = '<a href="'.$url.'&show_images=true" class="tabDetailViewDFLink">'.$lbl.'</a>';
                }
            }

            $url = preg_replace('~&?display_format=[a-z]*~i', '', getCurrentURL());
            $lbl = array_get_default($mod_strings, 'LNK_PLAIN_TEXT', 'Plain Text');
            $links[] = '<a href="'.$url.'&display_format=plain" class="tabDetailViewDFLink">'.$lbl.'</a>';

        } else {
            $url = preg_replace('~&?display_format=[a-z]*~i', '', getCurrentURL());

            if ($format == 'plain_from_html') {
                $lbl = array_get_default($mod_strings, 'LNK_HTML_FORMAT', 'HTML Format');
                $links[] = '<a href="'.$url.'&display_format=html" class="tabDetailViewDFLink">'.$lbl.'</a>';
            }

            $lbl = array_get_default($mod_strings, 'LNK_WRAP_DISABLE', 'Disable Text Wrapping');
            $links[] = '<a href="#" onclick="toggle_email_wrap(); return false;" id="email_wrap_link" class="tabDetailViewDFLink">'.$lbl.'</a>';
            
            $GLOBALS['pageInstance']->add_js_literal(nowrap_email_js(), null, LOAD_PRIORITY_BODY);
        }
        
        $links = implode('&nbsp;&nbsp;&nbsp;', $links) . '<br><br>';

        return $links;
    }

    /**
     * Render email attachments html for DetailView
     *
     * @param string $module
     * @param string $record
     * @param string $label
     * @return string
     */
    function renderAttachmentsView($module, $record, $label) {
        $fields = array('name', 'filename', 'file_mime_type');
        $lq = new ListQuery('Note', $fields);
        $lq->addSimpleFilter('parent_type', $module, '=');
        $lq->addSimpleFilter('parent_id', $record, '=');
        $notes_list = $lq->fetchAllRows('name');
        $include_simple_dialog = false;

		if($notes_list) {
            $attachments = '';

            foreach($notes_list as $id => $note) {
                $dl_link = "download.php?type=Notes&id={$id}";
                $dl_title = javascript_escape($note['name']);
                $attachments .= "<a href=\"{$dl_link}&inline=0\" target=\"_blank\" class=\"tabDetailViewDFLink\">". $note['name'] ."</a>";

                if(preg_match('/\.(jpe?g|gif|png|bmp|tiff?|txt|pdf|svg)$/i', $note['filename'])) {
                    $mime_type = explode('/', $note['file_mime_type']);

                    if (isset($mime_type[0]) && $mime_type[0] == 'image') {
                        $include_simple_dialog = true;
                        $href = "javascript:SUGAR.ui.Lightbox.show('{$dl_link}', '{$dl_title}');";
                        $target = "";
                    } else {
                        $href = "{$dl_link}&inline=1";
                        $target = "target=\"_blank\"";
                    }

                    $attachments .= "&nbsp; <div class=\"input-icon icon-tlink\"></div>&nbsp;<a //href=\"{$href}\" {$target} class=\"tabDetailViewDFLink\">". $label ."</a>";
                }

                $attachments .= '<br />';
            }

        } else {
            $attachments = '&nbsp;';
        }

        if ($include_simple_dialog)
            simpledialog_javascript_header();

        return $attachments;
    }

    /**
     * Render email attachments hmtl for EditView
     *
     * @param  string $record
     * @param  array $input - user input ($_REQUEST)
     * @return string
     */
    function renderAttachmentsEdit($record, $input) {
        $forward = array_get_default($input, 'forward');
        $reattach_note_id = array_get_default($input, 'reattach_note_id');
        $relabel_note = array_get_default($input, 'relabel_note');

        $attachments = '';
        $hidden = array('new_notes' => array(), 'new_documents' => array(), 'remove_notes' => '');
        
        require_once('include/layout/FieldFormatter.php');
        $fmt = new FieldFormatter('html', 'view');
        $fmt->module_dirs = array('Notes');

        if ( ! empty($record) || $forward || ! empty($reattach_note_id) ) {
            $fields = array('id', 'name', 'filename');
            $lq = new ListQuery('Note', $fields);

            if ($forward) {
                $lq->addSimpleFilter('parent_id', $forward, '=');
            } elseif (! empty($reattach_note_id)) {
                $hidden['reattach_note_id'] = $reattach_note_id;
                $hidden['relabel_note'] = $relabel_note;
                $lq->addFilterPrimaryKey($reattach_note_id);
            } else {
                $lq->addSimpleFilter('parent_id', $record, '=');
            }
            $notes_list = $lq->fetchAllRows('name');
            
            $render = $this->renderAttachmentsEditList($notes_list);
            $attachments .= $render['body'];
            if ($forward)
				array_extend($hidden['new_notes'], $render['note_ids']);
        }
        
        $hidden_str = '';
        foreach($hidden as $k => $v) {
        	if(is_array($v)) $v = implode(',', $v);
        	$v = to_html($v);
        	$hidden_str .= "<input type=\"hidden\" name=\"$k\" value=\"$v\" />";
        }
        
        return $hidden_str . $attachments;
    }
    
    // used by json method
    function renderAttachmentsEditList($notes_list, $prefix='oldfile') {
		 require_once('include/layout/FieldFormatter.php');
        $fmt = new FieldFormatter('html', 'view');
        $fmt->module_dirs = array('Notes');

		$note_ids = array();
		$body = '';
		if ($notes_list) {
			$file_spec = array(
				'type' => 'file_ref',
			);

			$idx = 0;
			foreach($notes_list as $id => $note) {
				if (empty($note['filename']) || substr($note['filename'], 0, 6) == 'email/')
					continue;

				$note_ids[] = $id;
				$cancel = "&nbsp;<div class=\"input-icon icon-delete active-icon\" onclick=\"removeAttachment(this, '$id'); return false;\"></div>";
				
				$file_spec['name'] = $prefix.$idx;
				$display = $fmt->formatRowValue($file_spec, $note, $note['filename']);
				if($display) {
					$body .= '<div id="'.$prefix.'-outer'.$idx.'" style="padding-bottom: 0.3em">' . $display . $cancel . '</div>';
				}
				$idx ++;
			}
		}
		return compact('note_ids', 'body');
    }
    

    /**
     * Add attachments init jsavascript
     * 
     * @param  FormLayout $layout
     * @return void
     */
    function addAttachmentsJs(&$layout) {
        $attJs = 'var file_path = "'.AppConfig::site_url().'/download.php?type=Notes&id=";';
        $attJs .= 'var lnk_remove = "'.translate('LNK_REMOVE', 'app').'";';
        $attJs .= 'var ERR_NOT_ADDRESSED = "'.translate('ERR_NOT_ADDRESSED', 'Emails').'";';
        $layout->addScriptLiteral($attJs, LOAD_PRIORITY_FOOT);
    }

    /**
     * Render html for attachments upload
     *
     * @param HtmlFormGenerator $gen
     * @return string
     */
    function renderAttachmentUpload(HtmlFormGenerator &$gen) {
        global $mod_strings;

        $html = '<div id="template_attachments"></div>';
        $html .= '<div id="uploads_div"></div>';
        $html .= '<div id="documents_div"></div>';
        $html .= '<button type="button" name="add_file_button" onclick="addFile();" class="input-button input-outer"><div class="input-icon icon-add left"></div><span class="input-label">'.$mod_strings['LBL_ADD_FILE'].'</span></button>';
        $html .= ' <button type="button" name="add_document_button" onclick="addDocument();" class="input-button input-outer"><div class="input-icon icon-add left"></div><span class="input-label">'.$mod_strings['LBL_ADD_DOCUMENT'].'</span></button>';
        
        $gen->getFormObject()->addControl('EmailAttachments', 'attachments');

        return $html;
    }

    /**
     * Render html for Select Contact button
     *
     * @param  string $type - to, cc, bcc
     * @return string
     */
    function renderContactsButton($type) {
        global $app_strings, $mod_strings;

        $name = $type . '_button';

        $spec = array(
            'name' => $name,
            'title' => $app_strings['LBL_SELECT_BUTTON_TITLE'],
            'accesskey' => $app_strings['LBL_SELECT_BUTTON_KEY'],
            'label' => $mod_strings['LBL_EMAIL_SELECTOR'],
            'onclick' => "button_change_onclick(this);",
        );

        if(!ACLController::checkAccess('Contacts', 'list', true))
            $spec['disabled'] = 'disabled';
        $button = EditableForm::renderButton($spec);

        return $button;
    }

    /**
     * Render HTMLEditor for editing email body
     *
     * @param HtmlFormGenerator $gen
     * @param  array $descriptions: ['plain'], ['html']
     * @return string
     */
    function renderHtmlEditor(HtmlFormGenerator &$gen, $descriptions) {
        if ($descriptions['html'] != '') {
            $value = $descriptions['html'];
        } else {
            $value = $descriptions['plain'];
        }

        $spec = array('name' => 'description_html');
        $editor = $gen->form_obj->renderHtmlEditor($spec, $value);

        return $editor;
    }

    /**
     * Add standard signature to email body
     *
     * @param  string $body
     * @param  string $type - email type
     * @return string
     */
    function addSignature($body, $type) {
        global $current_user;
        $signed_body = $body;
        $sig = $current_user->getDefaultSignature();

        if($sig && $type != 'draft') {
            if($current_user->getPreference('signature_prepend')) {
                $signed_body = from_html($sig['signature_html']).'<p>&nbsp;</p>' . $signed_body;
            } else {
                $signed_body .= '<p>&nbsp;</p>'.from_html($sig['signature_html']);
            }
        }

        return $signed_body;
    }

    /**
     * Render Email Templates select box
     *
     * @param HtmlFormGenerator $gen
     * @return string
     */
    function renderTemplateSelect(HtmlFormGenerator &$gen) {
        $onchange = 'fill_email(document.DetailForm.email_template.value);';
        $spec = array('name' => 'email_template', 'options_function' => array('EmailTemplate', 'get_folder_options'), 'onchange' => $onchange);
        $select = $gen->form_obj->renderSelect($spec, null);

        return $select;
    }

    /**
     * Get checked format for Format radio buttons
     *
     * @return array
     */
    function getCheckedComposeFormat() {
        global $current_user;

        $send_as = $current_user->getPreference('email_compose_format');
        $checked = array('plain' => '', 'html' => '');

        if ($send_as == 'html') {
            $checked['html'] = 'checked';
        } else {
            $checked['plain'] = 'checked';
        }

        return $checked;
    }

    /**
     * Add additional bcc addresses
     *
     * @param  array $input - user input($_REQUEST)
     * @param  string $bcc
     * @return string
     */
	function addAdditionalBcc($input, $bcc) {
        $additional_addr = $bcc;
        if (!empty($input['email_multi']) && !empty($input['list_uids'])) {
            $mass_mod = $input['action_module'];
            $model = AppConfig::module_primary_bean($mass_mod);

			if($model)
			foreach (explode(';', $input['list_uids']) as $lead_id) {
                $obj = ListQuery::quick_fetch_row($model, $lead_id, array('_display', 'email_opt_out', 'invalid_email', 'email1', 'email2'));
                if ($obj && !empty($obj['email1']) && empty($obj['email_opt_out']) && empty($obj['invalid_email'])) {
                    $name = '';
                    if (! empty($obj['_display']))
                        $name = $obj['_display'];
					$additional_addr .= $name . ' <' . $obj['email1'] . '>; ';
                }

                /** @noinspection PhpUnusedLocalVariableInspection */
                $obj = null;
            }
        }

        return $additional_addr;
    }

    /**
     * Replace template variables on values
     *
     * @static
     * @param RowUpdate $update
     * @param string $description
     * @param string $description_html
     * @return array
     */
    static function replaceFields(RowUpdate &$update, $description, $description_html) {
        $parent_id = $update->getField('parent_id');
        $parent_type = $update->getField('parent_type');
        $parent = array();
		$params = array();

        if (! empty($parent_id)) {
            $parent[$parent_type] = $parent_id;
			$params['primary'] = $parent_type;
        }

        $replace_fields = $update->getField('replace_fields');
		$name = $update->getField('name');

        if ($replace_fields) {

			if (!isset($parent['Contacts'])) {
		        $to = normalize_emails_array(parse_addrs($update->getField('to_addrs')), null, true, true);
			    $cc = normalize_emails_array(parse_addrs($update->getField('cc_addrs')), null, true, true);
			    $bcc = normalize_emails_array(parse_addrs($update->getField('bcc_addrs')), null, true, true);
				$all_contacts = array_merge($to, $cc, $bcc);
				$num_contacts = 0;
				foreach ($all_contacts as $contact) {
					$num_contacts++;
					if (isset($contact['contact_id'])) {
						if ($num_contacts < 2) {
							$contact_id = $contact['contact_id'];
						}
						else
							break;
					}
					else
						break;
				}
				if ($num_contacts == 1 && isset($contact_id)) {
					$parent['Contacts'] = $contact_id;
					if (empty($parent_id)) 
						$params['primary'] = 'Contacts';
				}
			}
		
			$params['aliases'] = array(
				'Leads'  => 'Contacts',
				'Prospects'  => 'Contacts',
			);

			list($name, $description, $description_html) = TemplateParser::parse_generic(
				array($name, $description, $description_html),
				$parent,
				$params
			);
            $update->set(array('replace_fields' => 0));
        }
        
        return array('name' => $name, 'html' => $description_html, 'plain' => $description);
    }
    
    
    function loadUpdateAttachments(array $input) {
		return array(
        	'new_notes' => array_filter(explode(',', array_get_default($input, 'new_notes', ''))),
        	'new_documents' => array_filter(explode(',', array_get_default($input, 'new_documents', ''))),
        	'remove_notes' => array_filter(explode(',', array_get_default($input, 'remove_notes', ''))),
            'reattach_note_id' => array_get_default($input, 'reattach_note_id', ''),
            'relabel_note' => array_get_default($input, 'relabel_note', ''),
        );
    }


	function loadUpdateRequest(RowUpdate &$update, array $input) {
        $related_update = array();
        $main_update = array();
        if (isset($input['description_html'])) {
            $description_html = $input['description_html'];
			// temporary fix for html output from CKeditor 3.6.3
			$description_html = preg_replace('~<body id="cke_pastebin".*?</body>~s', '', $description_html);
			$description = html2plaintext($description_html);
        } else if (isset($input['description']))
        	$description = $input['description'];

        $send = '0';
        if (isset($input['send'])) $send = $input['send'];
        $related_update['send'] = $send;
        $send_as = 'html';
        if (isset($input['send_as'])) $send_as = $input['send_as'];
        $related_update['send_as'] = $send_as;
		$related_update['attachments'] = $this->loadUpdateAttachments($input);

        $type = $update->getField('type');
        $parent_id = $update->getField('parent_id');
        $parent_type = $update->getField('parent_type');

        if ($parent_type == 'Contacts')
            $main_update['contact_id'] = $parent_id;

        if ($type != 'draft' && isset($description_html)) {
            $after_replace = EmailWidget::replaceFields($update, $description, $description_html);
            $main_update['name'] = $after_replace['name'];
            $description = $after_replace['plain'];
            $description_html = $after_replace['html'];
        }

		if(isset($description_html))
			$main_update['description_html'] = $description_html;
		if(isset($description))
			$main_update['description'] = $description;

        $main_update['isread'] = 1;

		if(! $update->getField('status') || $type == 'archived') {
			$status = 'draft';
			if ($type == 'archived') $status = 'archived';
			$main_update['status'] = $status;
		}

        $update->set($main_update);
        $update->setRelatedData($this->id.'_rows', $related_update);
	}
	
	function validateInput(RowUpdate &$update) {
		return true;
	}
	
	function afterUpdate(RowUpdate &$update) {
        global $mod_strings;

		$row_updates = $update->getRelatedData($this->id.'_rows');
		if(! $row_updates)
			return;

		// set by Email before_save hook
		$recipients = $update->getRelatedData('recipients');
		$upd_after_send = array();
		$attachments = $this->updateAttachments($update, $row_updates);

        //Start SEND
        if ($recipients && $update->getField('type') == 'out' && $row_updates['send'] == '1') {
            $descriptions = array('html' => $update->getField('description_html'), 'plain' => $update->getField('description'));

            if (EmailWidget::send($update, $recipients, $descriptions, $attachments, $row_updates['send_as'])) {
                $send_error = false;
                $upd_after_send['status'] = 'sent';
                $upd_after_send['date_start'] = gmdate('Y-m-d H:i:s');
            } else {
                $send_error = true;
                $upd_after_send['status'] = 'send_error';
			}
            $update->set($upd_after_send);
            $update->save();

            if ($send_error) {
                $errmsg = empty($GLOBALS['send_error_msg']) ? '' : '<br>&nbsp;&nbsp;&nbsp;'.htmlentities($GLOBALS['send_error_msg']);
                add_flash_message($mod_strings['LBL_ERROR_SENDING_EMAIL'].$errmsg, 'error');
            } else {
                add_flash_message($mod_strings['LBL_MESSAGE_SENT']);
            }
        }
        //End SEND
    }

    function updateAttachments(RowUpdate &$update, $row_updates) {
    	$email_id = $update->getPrimaryKeyValue();
		$module = $update->getModuleDir();
		$remove_notes = $row_updates['attachments']['remove_notes'];
    	
        if ($email_id && $remove_notes) {
        	$result = self::fetchAttachments($module, $email_id);
            if($result && ! $result->failed) {
            	foreach($result->getRowIndexes() as $idx) {
					$note = $result->getRowResult($idx);
					if(in_array($note->getField('id'), $remove_notes)) {
						$upd = RowUpdate::for_result($note);
						$upd->markDeleted();
					}
				}
			}
        }

        $new_note = RowUpdate::blank_for_model('Note');
        for ($i = 0; $i < EmailWidget::MAX_ATTACHMENTS; $i++) {
        	if( ($filename = $new_note->loadInputFile('filename', 'email_attachment'.$i)) ) {
        		$new_note->set(array(
        			'name' => $new_note->related_files['filename']->get_display_filename($filename),
        			'email_attachment' => 1,
        			'assigned_user_id' => $update->getField('assigned_user_id'),
        			'parent_type' => $module,
        			'parent_id' => $email_id,
        		));
        		$new_note->save();
        		$new_note = RowUpdate::blank_for_model('Note');
        	}
        }

        $new_documents = $row_updates['attachments']['new_documents'];
        foreach ($new_documents as $did) {
        	$doc = ListQuery::quick_fetch('Document', $did);
        	if($doc && ($rev_id = $doc->getField('document_revision_id')) ) {
        		$rev = ListQuery::quick_fetch('DocumentRevision', $rev_id);
        		$new_note->set(array(
        			'name' => $doc->getField('document_name'),
        			'email_attachment' => 1,
        			'assigned_user_id' => $update->getField('assigned_user_id'),
        			'parent_type' => $module,
        			'parent_id' => $email_id,
        			'filename' => UploadFile::duplicate_file2($rev->getField('filename')),
        			'file_mime_type' => $rev->getField('file_mime_type'),
        		));
        		$new_note->save();
        		$new_note = RowUpdate::blank_for_model('Note');
        	}
        }


        $new_notes = $row_updates['attachments']['new_notes'];
        foreach ($new_notes as $did) {
        	$note = ListQuery::quick_fetch_row('Note', $did);
        	if($note) {
        		$dupe_data = $note;
        		unset($dupe_data['id']);
        		$new_note->set($dupe_data);
        		$new_note->set('filename', UploadFile::duplicate_file2($note['filename']));
        		$new_note->set(array(
        			'email_attachment' => 1,
	        		'assigned_user_id' => $update->getField('assigned_user_id'),
        			'parent_type' => $module,
        			'parent_id' => $email_id,
				));
				$new_note->save();
				$new_note = RowUpdate::blank_for_model('Note');
			}
		}


		$reattach_id = $row_updates['attachments']['reattach_note_id'];
		$relabel_note = $row_updates['attachments']['relabel_note'];
        if($reattach_id) {
            $note = ListQuery::quick_fetch('Note', $reattach_id);
            if($note) {
            	$new_note = RowUpdate::for_result($note);
                $new_note->set(array(
                	'parent_type' => $module,
                	'parent_id' => $email_id,
                ));
                if($relabel_note)
                	$new_note->set('name', $relabel_note);
                $new_note->save();
            }
        }
        
        $result = self::fetchAttachments($module, $email_id);
        if($result && ! $result->failed)
			return $result->getRows();
		return array();
    }

    static function fetchAttachments($module, $email_id) {
        $lq = new ListQuery('Note', array('id', 'name', 'filename', 'file_mime_type', 'deleted'));
        $lq->addSimpleFilter('parent_type', $module, '=');
        $lq->addSimpleFilter('parent_id', $email_id, '=');
        return $lq->fetchAll('name');
    }

    /**
     * Replace Email->send() method for now, maybe later move this one
     *
     * @param RowUpdate $update
     * @param array $recipients - ['to'],['cc'],['bcc']
     * @param array $descriptions - ['html'], ['plain']
     * @param array $attachments - Notes array
     * @param string $send_as - html or plain
     * @return bool
     */
    static function send(RowUpdate &$update, $recipients, $descriptions, $attachments, $send_as = 'html') {
        global $current_user;

        $updated_fields = array();
        $email = new Email();
        $message_id = $update->getField('message_id');
        $in_reply_to = $update->getField('in_reply_to');
        $new = false;

        if (empty($message_id)) {
            $updated_fields['message_id'] = $email->create_message_id();
            $message_id = $updated_fields['message_id'];
            $new = true;
        }

        if ( $new || (isset($update->saved['message_id']) || isset($update->saved['in_reply_to'])) ) {
            $email->message_id = $message_id;
            $email->thread_id = $update->getField('thread_id');
            $email->in_reply_to = $in_reply_to;
            $email->assign_thread_id();
            $updated_fields['thread_id'] = $email->thread_id;
        }

        require_once 'include/SugarPHPMailer.php';
        $mail = new SugarPHPMailer();

        if (is_array($recipients['to'])) {
            foreach ($recipients['to'] as $addr_arr) {
                if ( empty($addr_arr['display'])) {
                    $mail->AddAddress($addr_arr['email'], "");
                } else {
                    $mail->AddAddress($addr_arr['email'], $addr_arr['display']);
                }
            }
        }
        if (is_array($recipients['cc'])) {
            foreach ($recipients['cc'] as $addr_arr) {
                if ( empty($addr_arr['display'])) {
                    $mail->AddCC($addr_arr['email'], "");
                } else {
                    $mail->AddCC($addr_arr['email'], $addr_arr['display']);
                }
            }
        }
        if (is_array($recipients['bcc'])) {
            foreach ($recipients['bcc'] as $addr_arr) {
                if ( empty($addr_arr['display'])) {
                    $mail->AddBCC($addr_arr['email'], "");
                } else {
                    $mail->AddBCC($addr_arr['email'], $addr_arr['display']);
                }
            }
		}

        if (defined('inScheduler')) {
            $mail->InitForSend(true);
        } else {

            if ($current_user->getPreference('mail_sendtype') == "SMTP") {
                $mail->Mailer = "smtp";
                $mail->Host = $current_user->getPreference('mail_smtpserver');
                $mail->Port = $current_user->getPreference('mail_smtpport');

                if ($current_user->getPreference('mail_smtpauth_req')) {
                    $mail->SMTPAuth = TRUE;
                    $mail->Username = $current_user->getPreference('mail_smtpuser');
                    $mail->Password = $current_user->getPreference('mail_smtppass');
                }
            } elseif ($current_user->getPreference('mail_sendtype') == 'sendmail') {
                $mail->Mailer = "sendmail";
            }

        }

        $from_addr = $update->getField('from_addr');
        $from_name = $update->getField('from_name');
        if(empty($from_addr)) {
            $mail->From = $current_user->getPreference('mail_fromaddress');
            $updated_fields['from_addr'] = $mail->From;
            $mail->FromName =  $current_user->getPreference('mail_fromname');
            $updated_fields['from_name'] = $mail->FromName;
            $from_name = $mail->FromName;
        } else {
            $mail->From = $from_addr;
        }

        $mail->Sender = $mail->From;
        $mail->FromName = from_html($from_name);
        $mail->AddReplyTo($mail->From, $mail->FromName);
        $mail->Subject = from_html($update->getField('name'));

        //Start ADD ATTACHMENTS
        foreach ($attachments as $note) {
			$file_location = rawurldecode(UploadFile::get_file_path($note['filename'], $note['id']));
			$filename = basename($note['filename']);
			$mime_type = array_get_default($note, 'file_mime_type', 'text/plain');
            $mail->AddAttachment($file_location, $filename, 'base64', $mime_type);
        }
        //End ADD ATTACHMENTS

        if (! empty($descriptions['html']) && $send_as == 'html') {
            // wp: if body is html, then insert new lines at 996 characters. no effect on client side
            // due to RFC 2822 which limits email lines to 998
            $cids = array();
            $html= show_inline_images($descriptions['html']);
            $html = make_inline_images($html, $cids);

            foreach ($cids as $cid => $filename) {
                $type = 'application/octet-stream';
                $m = array();

                if (preg_match('~\.([a-z]+)$~', $filename, $m)) {
                    if (in_array(strtolower($m[1]), array('gif', 'jpeg', 'png', 'tiff'))) {
                        $type = 'image/' . strtolower($m[1]);
                    }
                }

                $mail->AddEmbeddedImage($filename, $cid, '', 'base64', $type);
            }

            $mail->Body = $html;
            $mail->AltBody = from_html($descriptions['plain']);
            $mail->IsHTML(true);
        } else {
            $mail->Body = from_html($descriptions['plain']);
        }

        $mail->prepForOutbound();
        $mail->MessageID = $message_id;
        $mail->require_body = false;

        if (! empty($in_reply_to)) {
            $mail->AddCustomHeader('In-Reply-To: ' . from_html($in_reply_to));
        }

        $references = $email->get_references();
        if (! empty($references)) {
            $mail->AddCustomHeader('References: ' . from_html($references));
        }

        if ($mail->Send()) {
            $updated_fields['folder'] = EmailFolder::get_std_folder_id($update->getField('assigned_user_id'), STD_FOLDER_SENT);
            $updated_fields['type'] = 'out';
            $updated_fields['send_error'] = 0;

            $status = true;
        } else {

            $GLOBALS['log']->warn("Error emailing:".$mail->ErrorInfo);
            $GLOBALS['send_error_msg'] = $mail->ErrorInfo;

            $updated_fields['folder'] = EmailFolder::get_std_folder_id($update->getField('assigned_user_id'), STD_FOLDER_DRAFTS);
            $updated_fields['type'] = 'draft';

            $status = false;
        }

        $update->set($updated_fields);
        
        return $status;
    }
	
	function renderPdf(PdfFormGenerator &$gen, RowResult &$row_result, array $parents, array $context) {
		global $mod_strings;
		$backgrounds = array(
			array(
				array(245, 245, 245),
				array(255, 255, 255),
			),
			array(
				array(240, 240, 240),
				array(250, 250, 250),
			),
		);
		$opts = array(
			'border' => 0,
		);
		$cols = array(
			'label' => array(
				'width' => '20%',
				'background-color' => $backgrounds[0][0],
				'alt-background-color' => $backgrounds[1][0],
			),
			'text' => array(
				'width' => '80%',
				'background-color' => $backgrounds[0][1],
				'alt-background-color' => $backgrounds[1][1],
			),
		);
		$data = array();
		$address_fields = array(
			'to_addrs' => 'LBL_TO',
			'cc_addrs' => 'LBL_CC',
			'bcc_addrs' => 'LBL_BCC',
		);
		$str = '';
		if (($s = $row_result->getField('from_name')) !== '') $str .= $s. ' ';
		$str .= '<' . $row_result->getField('from_addr') . ">\n";
		$data[] = array(
			'label' => $mod_strings['LBL_FROM'],
			'text' => $str,
		);
		foreach ($address_fields as $f => $label) {
			$str = '';
            $arr = parse_addrs($row_result->row[$f]);
			$arr = merge_email_names($arr, $row_result->row[$f . '_names']);
			foreach($arr as $addr) {
				if ($addr['display'] !== '') $str .= $addr['display'] . ' ';
				$str .= '<' . $addr['email'] . ">\n";
			}
			$data[] = array(
				'label' => $mod_strings[$label],
				'text' => $str,
			);
		}
		$data[] = array(
			'label' => $mod_strings['LBL_SUBJECT'],
			'text' => array_get_default($row_result->formatted, 'name', ''),
		);

        $description_html = $row_result->getField('description_html');
        $description = $row_result->getField('description');
		if ($description != '') {
			$desc = $description;
			$desc = preg_replace('~\t~', '    ', $desc);
		} else {
			$desc = html2plaintext(to_html($description_html), false);
		}

		$data[] = array(
			'label' => $mod_strings['LBL_BODY'],
			'text' => $desc,
		);
		$gen->pdf->DrawTable($data, $cols, '   ', false, $opts);
	}
}
?>
