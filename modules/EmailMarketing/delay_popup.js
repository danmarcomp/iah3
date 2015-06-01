function submitForm(list_id) {
    function ready() {
        popup_dialog.close();
        var frm = list_id + '-ListUpdate';
        var conn_params = {};
        conn_params.resultDiv = '' + list_id + '-outer';
        return SUGAR.ui.sendForm(frm, {"record_perform": "subpanel_render"}, conn_params);
    }
    var field_params = {format:"html", record_perform:"save", no_redirect:"1"};
    return SUGAR.ui.sendForm(document.forms.DelayForm, field_params, {receiveCallback: ready}, false, true);
}