
function initOppConvert(form) {
	var btn = $(form.name + '_conv_prj');
	if(! btn) return;
	btn.onclick = function() {
		var p = open_popup_window({module: 'Project', inline: true,
			title: '<div class="input-icon theme-icon module-Project"></div>&nbsp;' + mod_string('LBL_SELECT_TEMPLATE_PROJECT'),
			request_data: {
				call_back_function: 'oppConvProject',
				form_name: form.name,
				field_to_name_array: {id: 'id'}
			} } );
	}
}

function oppConvProject(args) {
	var id = args.name_to_value_array.id;
	var opp_id = SUGAR.ui.getForm(args.form_name).record.value;
	var layout = SUGAR.ui.getForm(args.form_name).layout.value;
	SUGAR.util.loadUrl('index.php?module=Project&action=Duplicate&record='+id+
		'&opportunity_id='+opp_id+'&return_module=Opportunities&return_action=DetailView&return_record='+opp_id+'&return_layout='+layout+'&return_panel=project');
}
