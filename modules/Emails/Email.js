/**
 * EditView javascript for Email
 *
 * The contents of this file are subject to the SugarCRM Public License Version
 * 1.1.3 ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied.  See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * All copies of the Covered Code must include on each user interface screen:
 *    (i) the "Powered by SugarCRM" logo and
 *    (ii) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.  See full license for
 * requirements.
 *
 * The Original Code is: SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) 2004-2006 SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 */

// $Id: Email.js,v 1.5 2006/03/23 02:06:31 chris Exp $
var append = false; // FCKEd has method InsertHTML which inserts at cursor point - plain does not
MAXFILES = 10;

function addFile() {
	for(var i=0; i<MAXFILES; i++) {
		var elem = document.getElementById('file'+i);
		if(! elem) {
			var inp = new SUGAR.ui.FileInput('file'+i, {name: 'email_attachment'+i});
			SUGAR.ui.registerInput('DetailForm', inp);
			var remove = createElement2('div', {className: 'input-icon icon-delete active-icon'});
			remove.onclick = function() {
				SUGAR.ui.domRemoveElt($('file-outer'+i));
			}
			createElement2('div', {id: 'file-outer'+i, style: 'padding-bottom: 0.3em'}, [inp.render(),nbsp(),remove], $('uploads_div'));
			inp.focus();
		  	break;
		}
	}
}

function addDocument() {
	for(var i=0; i<MAXFILES; i++) {
		var elem = document.getElementById('document'+i);
		if(! elem) {
			var inp = new SUGAR.ui.RefInput('document'+i, {module: 'Documents'});
			inp.onchange = function() {
				updateNewDocuments();
			}
			SUGAR.ui.registerInput('DetailForm', inp);
			var remove = createElement2('div', {className: 'input-icon icon-delete active-icon'});
			remove.onclick = function() {
				SUGAR.ui.domRemoveElt($('document-outer'+i));
			}
			createElement2('div', {id: 'document-outer'+i, style: 'padding-bottom: 0.3em'}, [inp.render(),nbsp(),remove], $('documents_div'));
			inp.focus();
		  	break;
		}
	}
}

function removeAttachment(control, id) {
	if(confirm(mod_string('NTC_REMOVE_ATTACHMENT', 'Emails'))) {
		SUGAR.ui.domRemoveElt(control.parentNode);
		var form = SUGAR.ui.getForm('DetailForm');
		if(form && form.remove_notes)
			form.remove_notes.value += ','+id;
	}
}

function updateNewDocuments() {
	var i, k, docs = [], form = SUGAR.ui.getForm('DetailForm'), hidden = form.new_documents;
	for(i = 0; i < MAXFILES; i++) {
		var inp = SUGAR.ui.getFormInput(form, 'document'+i);
		if(inp && (k = inp.getKey()))
			docs.push(k);
	}
	if(hidden) hidden.value = docs.join(',');
}

////	END DOCUMENT HANDLING HELPERS
///////////////////////////////////////////////////////////////////////////////



///////////////////////////////////////////////////////////////////////////////
////	HTML/PLAIN EDITOR FUNCTIONS
function setEditor() {
	if(document.getElementById('setEditor').checked == false) {
		toggle_textonly();
	}
}

function toggle_textonly() {
	var altText = document.getElementById('alt_text_div');
	var plain = document.getElementById('text_div');
	var html = document.getElementById('html_div');
	
	if(html.style.display == 'none') {
		html.style.display = 'block';
		if(document.getElementById('toggle_textarea_elem').checked == false) {
			plain.style.display = 'none';
		}
		altText.style.display = 'block';
	} else {
		html.style.display = 'none';
		plain.style.display = 'block';
		altText.style.display = 'none';
	}
}

function toggle_textarea() {
	var checkbox = document.getElementById('toggle_textarea_elem');
	var plain = document.getElementById('text_div');

	if (checkbox.checked == false) {
		plain.style.display = 'none';
	} else {
		plain.style.display = 'block';
	}
}
////	END HTML/PLAIN EDITOR FUNCTIONS
///////////////////////////////////////////////////////////////////////////////




///////////////////////////////////////////////////////////////////////////////
////	EMAIL TEMPLATE CODE
function fill_email(id) {
	if(id == '') return;
	var req = new SUGAR.conn.JSONRequest('retrieve', null, {module: 'EmailTemplates', record: id});
	req.fetch(appendEmailTemplateJSON);
}


appendGuard = false;
function appendEmailTemplateJSON() {
	var result = this.getResult();
	if(! result || ! result.fields) return;
	var template = result.fields;
	
	if (appendGuard) return;
	appendGuard = true;
	var form = document.DetailForm;
	
	if(typeof form.description_html != 'undefined') {
		var html_textarea = form.description_html;
	} else {
		var html_textarea = form.body_html;
	}

	// query based on template, contact_id0,related_to
	if(append) {
		form.name.value += decodeURI(encodeURI(template['subject']));
		//document.DetailForm.description.value += decodeURI(encodeURI(template['body'])).replace(/<BR>/ig, '\n');
		html_textarea.value += decodeURI(encodeURI(template['body_html'])).replace(/<BR>/ig, '\n');
	
		if(typeof(window.html_editor) != 'undefined') {
			html_editor.insertHTML(decodeURI(encodeURI(template['body_html'])).replace(/<BR>/ig, '\n'));
		} else if(typeof(window.CKEDITOR) != 'undefined') {
			var oEditor = CKEDITOR.instances.description_html;
			if(navigator.userAgent.toLowerCase().indexOf("webkit") > -1)
				oEditor.insertHtml(''); // work around safari issue
			oEditor.insertHtml(decodeURI(encodeURI(template['body_html'])).replace(/<BR>/ig, '\n'));
		}
	} else {
		form.name.value = decodeURI(encodeURI(template['subject']));
		//document.DetailForm.description.value = decodeURI(encodeURI(template['body'])).replace(/<BR>/ig, '\n');
		html_textarea.value = decodeURI(encodeURI(template['body_html'])).replace(/<BR>/ig, '\n');

		if(typeof(window.html_editor) != 'undefined') {
			html_editor.insertHTML(decodeURI(encodeURI(template['body_html'])).replace(/<BR>/ig, '\n'));
		} else if(typeof(window.CKEDITOR) != 'undefined') {
			var oEditor = CKEDITOR.instances.description_html;
			if(navigator.userAgent.toLowerCase().indexOf("webkit") > -1)
				oEditor.insertHtml(''); // work around safari issue
			oEditor.insertHtml(decodeURI(encodeURI(template['body_html'])).replace(/<BR>/ig, '\n'));
		}
	}

	if(form.description && form.description.value != '') {
		form.toggle_html.checked = true;
		toggle_textarea(form.toggle_html);
	}
	
	var chk = SUGAR.ui.getFormInput(form, 'replace_fields');
	if(chk && ! chk.getValue()) {
        chk.setValue(1);
    }
	appendGuard = false;
	
	
	var req = new SUGAR.conn.JSONRequest('fetch_template_attachments', null, {module: 'Emails', record: template.id});
	req.fetch(setTemplateAttachments);
}


function setTemplateAttachments() {
	var result = this.getResult();
	if(result) {
		var form = SUGAR.ui.getForm('DetailForm');
		if(result.note_ids)
			form.new_notes.value = result.note_ids.join(',');
		$('template_attachments').innerHTML = result.body;
	}
}

////	END EMAIL TEMPLATE CODE
///////////////////////////////////////////////////////////////////////////////


///////////////////////////////////////////////////////////////////////////////
////	SIGNATURE CODE
function insertSignature(text) {
	if(typeof window.CKEDITOR != undefined) {
	 	var oEditor = CKEDITOR.instances.description_html;
	  	oEditor.insertHtml(text);
	} else if (typeof window.html_editor != undefined) {
   		html_editor.focusEditor();
    
    	if(HTMLArea.is_ie) {
	        html_editor.insertHTML(text + " " );
	    } else {
    	    html_editor.insertNodeAtSelection(document.createTextNode(text + " "));
    	}
	}    
}
////	END SIGNATURE CODE
///////////////////////////////////////////////////////////////////////////////

SUGAR.ui.EmailAttachments = function(id, params) {
	this.id = id || 'attachments';
	this.params = params;
}
SUGAR.ui.EmailAttachments.prototype = {
	beforeSubmitForm: function(form) {
		var boxes = document.getElementsByName('keep_attachments[]');
		var remove = document.getElementById('remove_attachments');
		if (!remove) return;
		remove.value = '';
		for (var i = 0; i< boxes.length; i++) {
			if (!boxes[i].checked) {
				if (remove.value != '') remove.value += ',';
				remove.value += boxes[i].value;
			}
		}
	}
};

function focus_body(form) {
	var html_input = SUGAR.ui.getFormInput(form, 'description_html'),
		plain_inp = SUGAR.ui.getFormInput(form, 'description');
	if(html_input)
		html_input.focus();
	else if(plain_inp)
		plain_inp.focus();
}
	
function set_email_focus(form) {
	var addr_inp = SUGAR.ui.getFormInput(form, 'to_addrs'),
		subj_inp = SUGAR.ui.getFormInput(form, 'name');
	if(addr_inp && addr_inp.isBlank())
		addr_inp.focus();
	else if (subj_inp && subj_inp.isBlank())
		subj_inp.focus();
	else
		focus_body(form);
}
