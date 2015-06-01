
function init_form(form, doc_id) {
    setTimeout('set_rev_filters("'+form+'", "'+doc_id+'", false)', 400);
}

function set_rev_filters(form, doc_id, clear) {
    if (doc_id != '') {
        var revision = SUGAR.ui.getFormInput(form, 'related_doc_rev');
        if (clear)
            revision.clear();
        if (revision)
            revision.addFilter({param: 'document_id', value: doc_id});
    }
}

