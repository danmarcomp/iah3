function addToKB(form) {
	if(! form || ! form.record)
		return;
	var id = form.record.value;
	SUGAR.util.loadUrl('index.php?module=KBArticles&action=EditView&case_id=' + encodeURIComponent(id));
}

function setup_booking(form) {
    if(! form || ! form.record)
        return;
    var id = form.record.value;
    var module = form.module.value;
    BookingEditor.init(module, id, 'slider');
}

function showBookingDialog(form) {
    var today = new Date();
    var date_start = today.print('%Y-%m-%d');
    var hour_start = today.print('%H');
    var minute_start = today.print('%M');
    var related = {id: form.record.value, type: 'Cases'};
    HoursEditView.showNew(null, null, null, date_start, hour_start, minute_start, 0, 30, related);
}

function doEscalate(form) {
    if(! form || ! form.record)
        return;
    var query = '';
    var boxes = document.getElementsByName('escalate_skill');
    var record = form.record.value;
    for (var i = 0; i < boxes.length; i++) {
        if (boxes[i].checked) {
            query += '&skill[]=' + boxes[i].value;
        }
    }
    if (query != '') {
        document.getElementById('escalate_error').style.display = 'none';
        SUGAR.util.loadUrl('index.php?module=Cases&action=Escalate&record=record' + query);
    }
    else document.getElementById('escalate_error').style.display = '';
}

function initCaseForm(form) {
	var contract = SUGAR.ui.getFormInput(form, 'contract');
	if (contract)
		contract.add_filters = [{param: 'main_account', field_name: 'account'}];
	var contact = SUGAR.ui.getFormInput(form, 'cust_contact');
	if (contact) {
		contact.add_filters = [{param: 'primary_account', field_name: 'account'}];
        setContactOnchange(contact, form);
    }
    setAccountOnchange(form);

    var subcon = SUGAR.ui.getFormInput(form, 'contract');
    if (subcon) {
        var ext_fields = {
            'contact': 'customer_contact',
            'contact_id': 'customer_contact_id',
            'phone_work': 'customer_contact.phone_work',
            'phone_mobile': 'customer_contact.phone_mobile',
            'account': 'main_contract.account',
            'account_id': 'main_contract.account_id'
        };
        subcon.addExtraReturnFields(ext_fields);
		SUGAR.ui.attachInputEvent(subcon, 'onchange', function(k, v) {
			if(v) {
				var act = SUGAR.ui.getFormInput(form, 'account');
				var ctc = SUGAR.ui.getFormInput(form, 'cust_contact');
				var phone_number = SUGAR.ui.getFormInput(form, 'phone_number');
				if(act && ! act.getKey())
					act.update(v.account_id, v.account);
				if(ctc && ! ctc.getKey())
					ctc.update(v.contact_id, v.contact);
				updatePhone(v, form);
			}
		});
	}
}

function setAccountOnchange(form) {
    var account = SUGAR.ui.getFormInput(form, 'account');
    if (account) {
        var acct_fields = {
            'account_popups': 'account_popups',
            'service_popup': 'service_popup'
        };
        account.addExtraReturnFields(acct_fields);

        function setAccount(result) {
            if (result.account_popups == 1 && result.service_popup) {
                setTimeout(function() {show_popup_message(result.service_popup.replace(/\r\n?/g, "<br />"), {timeout:15000, title: result['_display'], close_button: app_string('LBL_ADDITIONAL_DETAILS_CLOSE')});}, 1000);
            }
        }

        SUGAR.ui.attachInputEvent(account, 'onchange', function(k, v) {
            setAccount(v);
        });
    }
}

function updatePhone(result, form) {
	var phone = '';
	if(result) {
		if (! blank(result.phone_work)) {
			phone = result.phone_work;
		} else if (! blank(result.phone_mobile)) {
			phone = result.phone_mobile;
		}
	}
	if (phone) {
		var phone_num = SUGAR.ui.getFormInput(form, 'cust_phone_no');
		if (phone_num)
			phone_num.setValue(phone);
	}
	return phone;
}

function setContactOnchange(contact, form) {
    if (contact) {
        var fields = {
            'phone_work': 'phone_work',
            'phone_mobile': 'phone_mobile'
        };
        contact.addExtraReturnFields(fields);
        SUGAR.ui.attachInputEvent(contact, 'onchange', function(k, v) {
            updatePhone(v, form);
        });
    }
}
