BugsForm = new (function() {
	this.init = function(form) {
		if(form) this.form = form;
        this.setFilters();
		SUGAR.ui.attachFormInputEvent(this.form, 'resolution', 'onchange', this.updateResolution);
		var rels = ['found_in', 'fixed_in', 'planned_for'], input;
		for(var i = 0; i < rels.length; i++) {
			input = SUGAR.ui.getFormInput(form, rels[i]);
			if(! input) console.error(rels[i]);
			input.add_filters = [{param: 'product', field_name: 'product'}];
		}
	};
	
	this.updateResolution = function() {
		var status = SUGAR.ui.getFormInput(this.form, 'status');
		if(! status) return;
		var val = this.getValue(),
			statval = status.getValue();
		if(statval != 'Closed') {
			if(val == 'Duplicate' || val == 'Out of Date' || val == 'Invalid')
				status.setValue('Rejected');
			else if(val == 'Fixed')
				status.setValue('Closed');
			else if(val == 'Later')
				status.setValue('Pending');
		}
	};

    this.setFilters = function () {
        if (this.form) {
            var contact = SUGAR.ui.getFormInput(this.form, 'contact');
            if (contact)
                contact.add_filters = [{param: 'primary_account', field_name: 'account'}];
        }
    };

	return this;
})();
