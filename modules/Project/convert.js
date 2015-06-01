
function showProjConvert(form, panel_name, param_name) {
	open_popup_window({module: 'Project', inline: true,
		title: '<div class="input-icon theme-icon module-Project"></div>&nbsp;' + app_string('LBL_SELECT_TEMPLATE_PROJECT'),
		request_data: {
			call_back_function: 'convToProject',
			form_name: form.name,
			field_to_name_array: {id: 'id'},
			passthru_data: {
				param_name: param_name,
				panel_name: panel_name
			}
		} } );
}

function convToProject(data) {
	if(data) {
		SUGAR.ui.createDuplicateFrom(data.form_name, 'Project', data.name_to_value_array.id,
			data.passthru_data.link_name, data.passthru_data.param_name);
	}
}
