contract_action_change = function(input) {
    var main_visibility = 'none';
    var type_visibility = 'none';
    var type_lbl_visibility = 'none';
    var name_visibility = 'none';
    var name_lbl_visibility = 'none';

    var prefix = 'contract';
    if (input.field.id == 'project_action')
        prefix = 'project';

    if (input.getValue() == 'create_new') {
        type_visibility = '';
        type_lbl_visibility = '';
        name_visibility = '';
        name_lbl_visibility = '';
    } else if (input.getValue() == 'use_existing') {
        $(prefix + '-cell').style.display = '';
        main_visibility = '';
    }

    $(prefix + '-cell').style.display = main_visibility;
    $(prefix + '_name_label-cell').style.display = name_lbl_visibility;
    $(prefix + '_name-cell').style.display = name_visibility;

    if (prefix == 'contract') {
        $('contract_type-cell').style.display = type_visibility;
        $('contract_type_label-cell').style.display = type_lbl_visibility;
    }
};

create_contracts = function(list_id, parent_type, parent_id, account_id) {
    var errors = [];
    var inputs = ['contract_type-input', 'contract_name', 'contract-input', 'project_name', 'project-input'];
    var params = {
        action: 'CreateProducts',
        contract_action: $('contract_action').value,
        contract_type: $('contract_type').value,
        contract_name: $('contract_name').value,
        contract_id: $('contract').value,
        project_action: $('project_action').value,
        project_name: $('project_name').value,
        project_id: $('project').value,
        account_id: account_id,
        parent_type: parent_type,
        parent_id: parent_id,
        no_redirect: 1
    };
    for (var i = 0; i < inputs.length; i ++) {
        SUGAR.ui.addRemoveClass(inputs[i], 'error', false);
        SUGAR.ui.addRemoveClass(inputs[i], 'invalid', false);
    }
    if (params.contract_action == 'create_new') {
        if (! params.contract_type.length) {
            errors.push('contract_type-input');
        }
        if (! params.contract_name.length) {
            errors.push('contract_name');
        }
    }
    if (params.project_action == 'create_new') {
        if (! params.project_name.length) {
            errors.push('project_name');
        }
    }
    if (errors.length) {
        for (i =0; i < errors.length; i++) {
            SUGAR.ui.addRemoveClass(errors[i], 'error', true);
            SUGAR.ui.addRemoveClass(errors[i], 'invalid', true);
        }
        return false;
    }

    var list = sListView.getListView(list_id);
    if(! list || ! list.form) {
        console.log("missing listview objects: "+list_id);
        return false;
    }
    var uidList = sListView.getUids(list_id);
    if(! uidList || ! uidList.getCount()) {
        alert(app_string('LBL_LISTVIEW_NO_SELECTED'));
        return false;
    }
    uidList.save();

    var success = function(data) { SUGAR.ui.PopupManager.close(); };

    try {
        return SUGAR.ui.sendForm(list.form, params, {receiveCallback: success}, false, true);
    } catch(e) {
        console.error(e); return false;
    }
};
