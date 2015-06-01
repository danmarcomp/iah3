function send_merge_form(message) {
    ret = false;
    if (confirm(message)) {
        try {
            document.MergeDuplicates.action.value = 'SaveMerge';
            var ret = SUGAR.ui.sendForm(document.forms.MergeDuplicates, {"record_perform":"save"}, null);
        } catch(e) {
            console.error(e);
            return false;
        }
    }
    return ret;
}

function copy_value(json_array) {
    var target_element = SUGAR.ui.getFormInput('MergeDuplicates', json_array['field_name']);
    if (target_element) {
        var val = json_array['field_value'];
        if (val == null)
            val = '';
        if (json_array['field_type'] == 'bool') {
            val = parseInt(json_array['field_value']);
        }
        if (json_array.hasOwnProperty('id_value')) {
            target_element.update(json_array['id_value'], val);
        } else if (json_array['field_type'] == 'multienum') {
            val = val.split('^,^');
            target_element.setSelectedKey(val);
        } else {
            target_element.setValue(val);
        }
    }
}

function change_primary(new_id) {
    document.MergeDuplicates.change_parent.value = '1';
    document.MergeDuplicates.change_parent_id.value = new_id;
    document.MergeDuplicates.action.value = 'index';
    document.MergeDuplicates.submit();
}

function remove_me(new_id) {
    document.MergeDuplicates.remove.value = '1';
    document.MergeDuplicates.remove_id.value = new_id;
    document.MergeDuplicates.action.value = 'index';
    document.MergeDuplicates.submit();
}
