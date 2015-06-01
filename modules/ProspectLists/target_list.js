
function swapDomainVisibility(form) {
    var list_type = SUGAR.ui.getFormInput(form, 'list_type');
    var domain_name = SUGAR.ui.getFormInput(form, 'domain_name');

    if (list_type && domain_name) {
        if (list_type.getValue() != 'exempt_domain') {
            domain_name.setValue('');
            domain_name.setDisabled(true);
        } else {
            domain_name.setDisabled(false);
        }
    }
}