function disableDate(type) {
    var frm = SUGAR.ui.getForm('DetailForm');
    var check_inp = null;

    if (type == 'end') {
        check_inp = frm.no_date_end;
    } else {
        check_inp = frm.no_date_start;
    }

    var date_inp = $('DetailFormdate_'+type+'-date');
    var time_inp = $('DetailFormdate_'+type+'-time');
    var date_but = $('DetailFormdate_'+type+'-date-sel');
    var status = false;

    if (check_inp.value == 1)
        status = true;

    date_inp.disabled = status;
    time_inp.disabled = status;
    date_but.disabled = status;
}

function disableNumber() {
    $('DetailFormsession_number-input').disabled = true;
}

function changeNextButtonView(hide) {
    var display = '';
    if (hide)
        display = 'none';
    $('DetailForm_save_next').style.display = display;
    $('DetailForm_save_next2').style.display = display;
}

function hideShowNextBut(num_sessions) {
    if (num_sessions > 1) {
        changeNextButtonView();
    } else {
        changeNextButtonView(true);
    }
}

function updatePanel(elm_id) {
    if ($(elm_id) != 'undefined') {
        var status_elm = $(elm_id);
        var button_elm = $(elm_id + '_sel');
        var current_val = status_elm.value;
        var new_val = '';

        if (current_val == 1) {
            new_val = 0;
        } else {
            new_val = 1;
        }

        status_elm.value = new_val;
        SUGAR.ui.addRemoveClass(button_elm, 'checked', new_val);
    }
}