function show_plain() {
    var show_inp = SUGAR.ui.getFormInput('DetailForm', 'show_plain');
    var check = show_inp.getValue();

    if(check == 1) {
        $('plain_text_div').style.display = 'block';
    } else {
        $('plain_text_div').style.display = 'none';
    }
}

function show_variable() {
    var var_name_inp = SUGAR.ui.getFormInput('DetailForm', 'variable_name');
    var var_text_inp = SUGAR.ui.getFormInput('DetailForm', 'variable_text');
    var_text_inp.field.value = var_name_inp.getValue();
}

function add_variables() {
    var var_module_inp = SUGAR.ui.getFormInput('DetailForm', 'variable_module');
    var module = var_module_inp.getValue();
    var opts = {keys:[], values:[], width:"21em", add_blank: true};

    for(var i = 0; i < field_defs[module].length; i++) {
        opts.keys.push("$"+field_defs[module][i].name);
        opts.values.push(field_defs[module][i].value);
    }

    var var_name_inp = SUGAR.ui.getFormInput('DetailForm', 'variable_name');
    var_name_inp.menu = new SUGAR.ui.SelectList(var_name_inp.menu_id, {options: opts, popup: true, show_checks: var_name_inp.show_checks, className: var_name_inp.popup_class_name});
    var_name_inp.menu.setup();
    var_name_inp.setValue('');
}

function fill_tracker() 
{
	var tracker_id = SUGAR.ui.getFormInput('DetailForm', 'tracker_id').getValue();
	var tracker_url = SUGAR.ui.getFormInput('DetailForm', 'tracker_url');
	var tracker_name = SUGAR.ui.getFormInput('DetailForm', 'tracker_name');
	var tracker = trackers[tracker_id];
	if (tracker) {
		tracker_url.setValue(tracker.tracker_url);
		tracker_name.setValue(tracker.tracker_name);
	} else {
		tracker_url.setValue('');
		tracker_name.setValue('');
	}
}

function insert_variable() {
    var var_text_inp = SUGAR.ui.getFormInput('DetailForm', 'variable_text');
    var oEditor =  CKEDITOR.instances.body_html.insertHtml(var_text_inp.getValue());
}


function insert_tracker() {
	var tracker_id = SUGAR.ui.getFormInput('DetailForm', 'tracker_id').getValue();
	var tracker_url = SUGAR.ui.getFormInput('DetailForm', 'tracker_url').getValue();
	var tracker_name = SUGAR.ui.getFormInput('DetailForm', 'tracker_name').getValue();
	var span = document.createElement('span');
	span.appendChild(document.createTextNode(tracker_name));
    CKEDITOR.instances.body_html.insertHtml('<a href="{tracker_' + tracker_id + '_tracker_' + tracker_url + '}">' + span.innerHTML + '</a>');
}


