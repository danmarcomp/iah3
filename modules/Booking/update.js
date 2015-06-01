function updateAccountEdit() {
    var form = document.forms.EditView;
    if(form.related_id.value) {
        form.account_select.style.display = 'none';
        form.account_name.readOnly = true;
    } else {
        form.account_select.style.display = '';
        form.account_name.readOnly = false;
    }
}
function validateForm(form) {
    removeFromValidate(form, 'related_name');
    var cat_id = $('booking_category_id').value;
    if(billable_cats[cat_id])
        addToValidate(form, 'related_name', 'varchar', true, '{MOD.LBL_RELATED_TO}');
    return check_form(form);
}
function formatCurrency(val, cid) {
    if(! isset(val) || val === '') return '';
    var decimals = CurrencyDecimalPlaces(cid);
    return stdFormatNumber(parseFloat(val).toFixed(decimals), decimals, decimals);
}
function updatedQuantity() {
    HoursEditView.form = SUGAR.ui.getForm('DetailForm');
    HoursEditView.updateQuantity();
}
function updatedAmount(fld) {
    HoursEditView.form = fld.form;
    HoursEditView.updateAmount(fld);
}