function submitForm(list_id) {
    function ready() {
        SUGAR.popups.close();
        var frm = list_id + '-ListUpdate';
        var conn_params = {};
        conn_params.resultDiv = '' + list_id + '-outer';
        return SUGAR.ui.sendForm(frm, {"record_perform": "subpanel_render"}, conn_params);
    }
    var field_params = {format:"html", record_perform:"save", no_redirect:"1"};
    return SUGAR.ui.sendForm(document.forms.DiscountForm, field_params, {receiveCallback: ready}, false, true);
}

function changeDiscountType(type) {
    setTimeout ("changeType('"+type+"')", 200);
}

function changeType(type) {
    var std_ref = SUGAR.ui.getFormInput('DiscountForm', 'discount_id');
    var val_inp = SUGAR.ui.getFormInput('DiscountForm', 'discount_value');

    switch (type) {
        case "std":
            std_disabled = false;
            val_disabled = true;
            break;
        case "percentage":
        case "fixed":
            std_disabled = true;
            val_disabled = false;
            break;
        default:
            std_disabled = true;
            val_disabled = true;
            break;
    }

    if (std_ref) {
        if (std_disabled)
            std_ref.setValue('');
        std_ref.setDisabled(std_disabled);
    }
    if (val_inp) {
        if (val_disabled)
            val_inp.setValue('');
        val_inp.setDisabled(val_disabled);
    }
}