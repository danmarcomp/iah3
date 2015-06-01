function quickEdit(params) {
    var quickEdit = null;
    var elem_id = params.field +'-'+ params.user_id;

    if (params.type == 'input') {
        quickEdit = new SUGAR.ui.QuickText({});
        quickEdit.init('', function() {
                var query_params = {list_id: params.list_id, link_name: 'booking_users', user_id: params.user_id,
                    task_id: params.task_id, field: params.field, field_value: this.getValue()};
                var req = new SUGAR.conn.JSONRequest('save_assigned_resource_data',
                    {status_msg: app_string('LBL_SAVING')}, query_params);
                req.fetch(ready);
            });

        quickEdit.showPopup(null, $(elem_id));
        quickEdit.field.format = params.format;
        if (params.format == 'float')
            quickEdit.field.decimals = 2;
    } else if (params.type == 'list') {
        var opts = {'keys': params.options.keys, 'values': params.options.values};
        var select_opts = new SUGAR.ui.SelectOptions(opts);
        quickEdit = new SUGAR.ui.QuickSelect({'options': select_opts, 'default': params.default_val, 'elt': $(elem_id)});
        quickEdit.init(params.selected, function() {
                var query_params = {list_id: params.list_id, link_name: 'booking_users', user_id: params.user_id,
                    task_id: params.task_id, field: params.field, field_value: this.getValue()};
                var req = new SUGAR.conn.JSONRequest('save_assigned_resource_data',
                    {status_msg: app_string('LBL_SAVING')}, query_params);
                req.fetch();
            });
        quickEdit.showPopup();

    }
}

function ready() {
    var row = this.getResult();
    if(row.result == 'ok') {
        var frm = row.list_id + '-ListUpdate';
        var conn_params = {};
        conn_params.resultDiv = '' + row.list_id + '-outer';
        return SUGAR.ui.sendForm(frm, {"record_perform": "subpanel_render"}, conn_params);
    }
    return false;
}
