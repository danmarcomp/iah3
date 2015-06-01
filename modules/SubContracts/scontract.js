function init_form(form, account_id) {
    setTimeout('init("'+form+'", "'+account_id+'")', 200);
}
function init(form, account_id) {
    var main = SUGAR.ui.getFormInput(form, 'main_contract');
    if (main)
        main.setDisabled();

    var contact = SUGAR.ui.getFormInput(form, 'customer_contact');
    if (contact) {
        var filter = [];
        filter[0] = {param: 'primary_account_id', value: account_id};
        contact.add_filters = filter;
    }
}
