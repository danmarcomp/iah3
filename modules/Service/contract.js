function warranty_conf() {
    var msg = SUGAR.language.get('mod_strings', 'SET_WARRANTY_DATES_CONFIRMATION');
    return confirm(msg);
}

function initServiceForm(form) {
    lockNumber(form);
    setAccountOnchange(form);
}

function lockNumber(form) {
    var contract_no = SUGAR.ui.getFormInput(form, 'contract_no');
    if (contract_no) {
        contract_no.setValue(mod_string('LBL_CONTRACT_NAME_NOTE'));
        contract_no.setDisabled(true);
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
