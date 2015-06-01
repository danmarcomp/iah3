function mailbox_init_form(form) {
    fetch_folder_list(form);
	SUGAR.ui.attachFormInputEvent(form, 'use_ssl', 'onchange', set_default_port);
	SUGAR.ui.attachFormInputEvent(form, 'protocol', 'onchange', set_default_port);
	SUGAR.ui.attachFormInputEvent(form, 'host', 'onchange', guess_address);
	SUGAR.ui.attachFormInputEvent(form, 'username', 'onchange', guess_address);
	set_default_port();
}

function set_default_port() {
    var imap = document.DetailForm.protocol.value == 'IMAP';
    var ssl = document.DetailForm.use_ssl.value == 1;
    if (imap) {
        document.DetailForm.port.value = ssl ? '993' : '143';
    } else {
        document.DetailForm.port.value = ssl ? '995' : '110';
    }
}

function guess_address() {
	var email = SUGAR.ui.getFormInput(this.form, 'email'),
		host = SUGAR.ui.getFormInput(this.form, 'host'),
		user = SUGAR.ui.getFormInput(this.form, 'username'),
		h, u;
	if(! email.getValue() && (h = host.getValue()) && (u = user.getValue())) {
		email.setValue(u + '@' + h.replace(/^((imap|mail|pop)[^\.]*|m)\./, ''));
	}
}

function fetch_folder_list(form) {
	var uid = form.user_id.value,
		sel = SUGAR.ui.getFormInput(form, 'email_folder_id'),
		req = new SUGAR.conn.JSONRequest('get_email_folder_list', null, {module: 'EmailFolders', user_id: uid});
	if(uid && sel && req)
	req.fetch(function() {
		var result = this.getResult();
		result.width = Math.max(result.maxlen/2, 10) + 'em';
		var fid = sel.getValue();
		sel.setOptions(new SUGAR.ui.SelectOptions(result));
        if(! fid || ! result.keys.indexOf(fid)) {
            for(var i = 0; i < result.values.length; i++) {
				if(result.values[i].reserved == 1) {
					sel.setValue(result.keys[i]);
                    sel.updateDelay();
					break;
				}
			}
		} else {
            sel.setValue(fid);
        }
	});
}
