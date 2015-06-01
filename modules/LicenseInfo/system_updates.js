function check_for_updates() {
    function ready() {
        var row = this.getResult();

        if (row) {
            if ($('updates_table') && row.result)
                $('updates_table').innerHTML = row.result;
            if (row.error == 1) {
                var msg = app_string('LBL_ASYNC_JS_ERROR');
                if (row.error_message)
                    msg = row.error_message;
                alert(msg);
            }
        }
    }

    var req = new SUGAR.conn.JSONRequest('check_for_updates', {status_msg: app_string('LBL_LOADING')});
    req.fetch(ready);
}