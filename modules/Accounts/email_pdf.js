function updateCounter(val) {
    var counter = document.EmailPDFStatement.email_counter;
    if (val == 1) {
        counter.value ++;
    } else {
        counter.value --;
    }
    if (counter.value < 0) counter.value = 0;
}

function setCounter(val) {
    var counter = document.EmailPDFStatement.email_counter;
    if (val < 0) val = 0;
    counter.value = val;
}

function checkEmails() {
    var counter = document.EmailPDFStatement.email_counter;
    if (counter.value > 0) {
        return SUGAR.ui.sendForm(this);
    } else {
        alert(mod_string('LBL_MASS_PDF_NO_EMAILS', 'Accounts'));
        return false;
    }
}
