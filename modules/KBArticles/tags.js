KBTagsFilter = new function()
{
	this.cache = {};

	this.displayTagsFilter = function(form)
	{
		this.form = form;
		this.dialog = new SUGAR.ui.Dialog('kbTagsDlg', {title: SUGAR.language.get('KBArticles', 'LBL_TAGS_FILTER'), width:'400px'});
		var vars = {module: 'KBArticles', action: 'TagsFilter'};
		vars.include_tags = form.include_tags.value;
		vars.exclude_tags = form.exclude_tags.value;
		this.dialog.fetchFormContent(form, vars, {url: 'async.php'}, null, true);
		return false;
	};

	this.applyFilter = function(srcForm)
	{
		var incl_input = SUGAR.ui.getFormInput(srcForm, 'filter_include_tags');
		var include = incl_input.field.value;
		var excl_input = SUGAR.ui.getFormInput(srcForm, 'filter_exclude_tags');
		var exclude = excl_input.field.value;
		this.form.include_tags.value = include;
		this.form.exclude_tags.value = exclude;
		this.dialog.hide();
		this.dialog.destroy();
		SUGAR.ui.submitForm(this.form);
	};

	this.cancel = function()
	{
		this.dialog.hide();
		this.dialog.destroy();
	};

	this.cachePopupContent = function()
	{
		KBTagsFilter.cache.popupContent = KBTagsFilter.dlg.getContentElement().innerHTML;
	};

	this.cacheValid = function(form)
	{
		var ret =  
				this.cache.include_tags == form.include_tags.value
			&&	this.cache.exclude_tags == form.exclude_tags.value
			&&	this.cache.popupContent != undefined
		;
		return ret;
	};

}();

KBTagsAutocomplete = new function()
{
	this.init = function(allow_new)
	{
		var ac_server = "./async.php"; 
		var ac_schema = ["\n", "\t"];
		var ac_source = new YAHOO.widget.DS_XHR(ac_server, ac_schema);
		ac_source.responseType = YAHOO.widget.DS_XHR.TYPE_FLAT;
		ac_source.scriptQueryParam = "tag";
		ac_source.scriptQueryAppend = "module=KBArticles&action=autocomplete";
		var tags_ac = new YAHOO.widget.AutoComplete("tags_text", "tags_container", ac_source); 
		tags_ac.delimChar = ",";
		tags_ac.maxResultsDisplayed = 20;
		tags_ac.typeAhead = true;
		tags_ac.formatResult = function(aResultItem, sQuery) { 
			return aResultItem[0];
		};
		this.allow_new = allow_new;
	};

	this.insert_tag = function(tag)
	{
		var value = $('tags').value.replace(/\s*,\s*$/, '');
		if (value.replace(/^\s+/, '').replace(/\s+$/, '') != '') {
			value += ', ';
		}
		$('tags').value = value + tag;
	};

	this.validate_tags = function()
	{
		if (this.allow_new) {
			document.forms.EditView.submit();
			return;
		}
		var tags = $('tags').value;

		var responseSuccess = function(oResp) {
			if(! oResp) return;
			oResp = oResp.responseText;
			if (oResp == '') {
				document.forms.EditView.submit();
			} else {
				alert(oResp);
			}
		}

		$('validate_form_tags').value = tags;

    	SUGAR.conn.sendForm('validate_form', null, responseSuccess);
	};

	this.suggest_tags = function()
	{
		var text = $('name').value + ' ';
		text += $('summary').value + ' ';
		var html = CKEDITOR.instances.description.getData();

		var responseSuccess = function(oResp) {
			if(! oResp) return;
			oResp = oResp.responseText;
			$('suggested_tags').innerHTML = oResp;
		}

		$('suggest_form_text').value = text;
		$('suggest_form_html').value = html;

    	SUGAR.conn.sendForm('suggest_form', null, responseSuccess);
	};

}();
