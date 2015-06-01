function check_merge_form(form) {
    var merge_file = document.MergeForm.merge_file.value;
    if (merge_file == '') {
        alert(app_string('LBL_RTF_NO_TEMPLATE'));
        return false;
    } else if (merge_file.substring(merge_file.length - 3, merge_file.length) != 'rtf') {
        alert(app_string('LBL_RTF_INVALID_SEL'));
        return false;
    }
    SUGAR.popups.hidePopup(popup_dialog);
    return true;
}