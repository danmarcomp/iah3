<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
    form_buttons
        convert_to_lead
            vname: LBL_CONVERT_BUTTON_LABEL
            accesskey: LBL_CONVERT_BUTTON_KEY
            async: false
            onclick: return SUGAR.ui.sendForm(document.forms.DetailForm, {"module":"Leads", "action":"EditView", "record":"", "return_module":"Prospects", "return_action":"DetailView", "prospect_id":this.form.record.value, "return_record": this.form.record.value}, null);
        manage
            vname: LBL_SUBS_BUTTON_LABEL
            async: false
            onclick: return SUGAR.ui.sendForm(document.forms.DetailForm, {"module":"Campaigns", "action":"Subscriptions", "return_module":"Prospects", "return_action":"DetailView", "prospect_id":this.form.record.value, "return_record": this.form.record.value}, null);
	sections
		--
			id: main
			elements
				--
					name: name
					widget: SalutationNameWidget
				- phone_work
                - account_name
                - phone_mobile
                - title
                - phone_home
                - department
                - phone_other
                - assistant
                - phone_fax
                - assistant_phone
                - email1
                - birthdate
                - email2
                - do_not_call
                - invalid_email
                - 
                - email_opt_out
                - last_activity_date
                - date_modified
                - assigned_user
                - date_entered
                - primary_address
                - alt_address
				--
					name: description
					colspan: 2
    subpanels
        - activities
        - history
        - campaigns
        - securitygroups
