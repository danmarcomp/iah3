var acl_editing_id = null;
var acl_edit_popup = null;
var acl_edit_table = null;

function set_focus(){
	$('name').focus();
}

function start_acl_edit(action_id, options) {
	if(acl_editing_id)
		end_acl_edit(acl_editing_id);
	make_acl_popup(action_id, options);
	acl_editing_id = action_id;
}

function end_acl_edit(action_id){
	if(acl_editing_id != action_id)
		return;
	acl_editing_id = null;
	SUGAR.popups.hidePopup();
}

function set_acl(action_id, value, label) {
	var div = $(action_id);
	var input = $('acl-'+action_id);
	if(! div || ! input) return;
    if (value != '') {
        var opts = value.split('_');
        input.value = opts[0];
       	div.innerHTML = label;
       	div.className = 'acl' + opts[1];
    }
}

function make_acl_popup(action_id, options) {
    var labels = [];
    var values = [];
	for(var i = 0; i < options.length; i++) {
        labels[i] = options[i].label;
        values[i] = options[i].value +'_'+ options[i].className;
	}

    var opts = {keys: values, values: labels};
    var select_opts = new SUGAR.ui.SelectOptions(opts);
    var input = $('acl-'+action_id);
    var div = $(action_id);
    var selected = input.value +'_'+ div.className.replace('acl', '');
    var quickEdit = new SUGAR.ui.QuickSelect({'options': select_opts, 'elt': div});
    quickEdit.init(selected, function() { set_acl(action_id, this.getValue(), this.getOptionValue()); });
    quickEdit.showPopup();
}
