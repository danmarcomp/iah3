
function enable_change_password_button() {
    if (document.getElementById('DetailForm_change_password') != 'undefined') {
        var butt = document.getElementById('DetailForm_change_password');
        if(document.DetailForm.record.value != "" && document.DetailForm.record.value != 'undefined') {
            butt.style.display = '';
        } else {
            butt.style.display = 'none';
        }
    }
}

function open_password_form() {
	var URL = "index.php?module=Users&action=ChangePassword";
    if(document.DetailForm.record.value != "" && document.DetailForm.record.value != 'undefined') {
		URL += "&record=" + document.DetailForm.record.value;
    }
    var title = mod_string('LBL_CHANGE_PASSWORD_BUTTON_LABEL', 'Users');
    SUGAR.popups.openUrl(URL, null, {width: '320px', title_text: title, resizable: false});
}

function check_password(form) {
	if (form.is_admin.value == 1 && form.old_password.value == "") {
		alert(mod_string('ERR_ENTER_OLD_PASSWORD', 'Users'));
		return false;
	}
	if (form.new_password.value == "") {
		alert(mod_string('ERR_ENTER_NEW_PASSWORD', 'Users'));
		return false;
	}
	if (form.confirm_new_password.value == "") {
		alert(mod_string('ERR_ENTER_CONFIRMATION_PASSWORD', 'Users'));
		return false;
	}
	if (form.new_password.value != form.confirm_new_password.value) {
        alert(mod_string('ERR_REENTER_PASSWORDS', 'Users'));
        return false;
	}

    var callback = function(data) {
        var result = data.getResult();
        if(! result.failed) {
            if (result['result'] == 'ok') {
                alert(mod_string('MSG_CHANGED_PASSWORD', 'Users'));
                SUGAR.popups.hidePopup(popup_dialog);
            } else {
                alert(get_password_error_msg(result['msg']));
            }
        } else {
            SUGAR.popups.hidePopup(popup_dialog);
        }
    }

    var old_password = '';
    if (form.is_admin.value == 1)
        old_password = form.old_password.value;

    var query = {module: 'Users', values:{id: form.record.value, new_password: form.new_password.value, old_password: old_password, confirm_password: form.confirm_new_password.value}}
    var req = new SUGAR.conn.JSONRequest('change_password', {}, query);
    req.fetch(callback);

    return false;
}

function get_password_error_msg(code) {
    var msg = 'ERR_PASSWORD_CHANGE_FAILED';
    switch (code) {
        case 2:
            msg = 'ERR_PASSWORD_INCORRECT_OLD_1';
            break;
        case 3:
            msg = 'ERR_PASSWORD_MISMATCH';
            break;
    }
    return mod_string(msg, 'Users');
}

function setPasswordValidation(form) {
    var confirm_password = SUGAR.ui.getFormInput(form, 'confirm_password');
    if (confirm_password) {
        confirm_password.customValidate = function () {
            var password = SUGAR.ui.getFormInput(form, 'password');

            if (this.isBlank() || password.isBlank())
                return true;

            if (this.getValue() != password.getValue()) {
                this.invalid = true;
                this.invalidMsg = get_password_error_msg(3);
                password.invalid = true;
                password.updateDisplay();
                return false;
            }
            return true;
        };
    }
}

function setSigEditButtonVisibility() {
	var field = document.DetailForm.signature_id;
    var editButt = document.getElementById('getSignatureButtons');
	if(field.value != '') {
		editButt.style.visibility = "visible";
	} else {
		editButt.style.visibility = "hidden";
	}
}

function open_email_signature_form(record, the_user_id, title) {
	var URL = "async.php?module=Users&action=SignatureDialog";
	if(record != "")
		URL += "&record="+record;
	if(the_user_id != "")
		URL += "&the_user_id="+the_user_id;
    SUGAR.popups.openUrl(URL, null, {width: '800px', title_text: title, resizable: false});
}

function refresh_signature_list(signature_id, signature_name) {
	var field=document.DetailForm.signature_id;
	var bfound=0;
	for (var i=0; i < field.options.length; i++) {
			if (field.options[i].value == signature_id) {
				if (field.options[i].selected==false) {
					field.options[i].selected=true;
				}
				bfound=1;
			}
	}
	//add item to selection list.
	if (bfound == 0) {
		var newElement=document.createElement('option');
		newElement.text=signature_name;
		newElement.value=signature_id;
		field.options.add(newElement);
		newElement.selected=true;
	}	

	//enable the edit button.
	var field1 = document.getElementById('getSignatureButtons');
	field1.style.visibility="visible";
}

function notify_setrequired(f) {
    if(typeof document.getElementById("smtp_settings") != 'undefined') {
        document.getElementById("smtp_settings").style.display = (f.mail_sendtype.value == "SMTP") ? "inline" : "none";
        document.getElementById("smtp_settings").style.visibility = (f.mail_sendtype.value == "SMTP") ? "visible" : "hidden";
    }
    if(typeof document.getElementById("smtp_auth") != 'undefined') {
        document.getElementById("smtp_auth").style.display = (f.mail_smtpauth_req.value == 1) ? "inline" : "none";
        document.getElementById("smtp_auth").style.visibility = (f.mail_smtpauth_req.value == 1) ? "visible" : "hidden";
    }
    return true;
}

function move_tabs_left() {
	var left = SUGAR.ui.getFormInput('DetailForm', 'display_tabs');
		right = SUGAR.ui.getFormInput('DetailForm', 'hide_tabs');
	return move_tabs(right, left);
}

function move_tabs_right() {
	var left = SUGAR.ui.getFormInput('DetailForm', 'display_tabs');
		right = SUGAR.ui.getFormInput('DetailForm', 'hide_tabs');
	return move_tabs(left, right);
}

function move_tabs(left, right) {
	var sel = left.getSelectedIndexes();
	if(! sel.length) return;
	var o = left.getOptions(), o2 = right.getOptions();
	var idx;
	for(var i = 0; i < sel.length; i++) {
		o2.keys.push(o.keys[sel[i]]);
		o2.limit_keys.push(o.keys[sel[i]]);
		o2.values.push(o.values[sel[i]]);
	}
	for(var i = 0; i < sel.length; i++) {
		o.keys.splice(sel[i]-i, 1);
		o.limit_keys.splice(sel[i]-i, 1);
		o.values.splice(sel[i]-i, 1);
	}
	left.setSelected(null, true);
	right.setSelected(null, true);
	left.renderOptions();
	right.renderOptions();
}

function set_chooser() {
	var hide = SUGAR.ui.getFormInput('DetailForm', 'hide_tabs');
	if(hide) {
		var tabs = hide.getOptions().keys;
		var dtabs = createElement2('input', {type: 'hidden', name: 'hide_tabs_list', value: tabs.join(',')}, null, document.forms.DetailForm);
	}
}

function setOnSubmitEvent() {
    if ($('DetailForm_save'))
        $('DetailForm_save').onclick = submitForm;
    if ($('DetailForm_save2'))
        $('DetailForm_save2').onclick = submitForm;
    document.DetailForm.onsubmit = submitForm;
}

function submitForm() {
	set_chooser();
    return SUGAR.ui.sendForm(document.DetailForm, {'record_perform':'save'});
}

function setSignatureOnSubmitEvent() {
    if ($('SignatureForm_save'))
        $('SignatureForm_save').onclick = submitSignatureForm;
    if ($('SignatureForm_cancel'))
        $('SignatureForm_cancel').onclick = cancelSignatureForm;
    document.SignatureForm.onsubmit = submitSignatureForm;
}

function submitSignatureForm() {
    return SUGAR.ui.sendForm(document.SignatureForm, {'record_perform':'save'}, null, false, true);
}

function cancelSignatureForm() {
    SUGAR.popups.close();
}