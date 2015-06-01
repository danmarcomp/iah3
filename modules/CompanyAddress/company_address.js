var company_form = null;
var company_list_form = null;

function initCompanyAddress(form) {
    if (form.in_popup && form.in_popup.value == 'true') {
        company_form = form;

        for (var i = 0; i < document.forms.length; i++) {
            var id = document.forms[i].id;
            if (typeof(id) == 'string' && id.indexOf("ListUpdate") != -1)
                company_list_form = document.forms[i];
        }

        if (form.DetailForm_save)
            form.DetailForm_save.onclick = submitForm;
        if (form.DetailForm_save2)
            form.DetailForm_save2.onclick = submitForm;
        form.onsubmit = submitForm;
    }
}

function submitForm() {
    function ready() {
        if (company_list_form && company_list_form.list_id)
            return sListView.sendMassUpdate(company_list_form.list_id.value, null, null, null, true);
    }
    try {
        var ret = SUGAR.ui.sendForm(company_form, {"record_perform":"save","close_popup":1}, {receiveCallback: ready}, false, true);
        return ret;
    } catch(e) {
        console.error(e);
        return false;
    }
}
