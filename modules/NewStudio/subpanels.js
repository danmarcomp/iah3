function subpanelSaveLayout(form)
{
	var list = sListView.getListView(form.list_id.value);
	var ret = SUGAR.ui.sendForm(form, {module: 'NewStudio', wizard:'Subpanel', save: 1},
		{resultDiv: 'content-main', receiveCallback: function() { document.close_popup(); }});
	if(! isset(ret)) { // possible validation failed
		var nm = SUGAR.ui.getFormInput(form, 'name'),
			tabs = SUGAR.ui.getFormInput(form, 'form-tabs');
		if(nm && nm.invalid && tabs)
			tabs.setValue('general');
	}
	return ret;
}

