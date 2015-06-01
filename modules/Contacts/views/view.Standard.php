<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	form_buttons
		subs
			type: button
			vname: LBL_SUBS_BUTTON_LABEL
			url: ?module=Campaigns&action=Subscriptions&record={RECORD}&{RETURN}
			async: false
		vcard
			type: button
			vname: vCard
			onclick: "load_vcard(this.form);"
			icon: icon-export
			acl: export
	sections
		--
			id: google_sync_status
			#vname: LBL_GOOGLE_SYNC_STATUS
			#vname_module: Contacts
            widget: GoogleSyncStatusWidget
		--
			id: main
			elements
				--
					name: name
					widget: SalutationNameWidget
				- phone_work
				--
					name: primary_account
					hidden: mode.B2C
				- phone_mobile
				- title
				- phone_home
				- lead_source
				- phone_other
				- campaign
				- phone_fax
				- department
				- email1
				- birthdate
				- email2
				- reports_to
				- assistant
				- partner
				- assistant_phone
				- do_not_call
				- email_opt_out
				- email_accounts  
				- last_activity_date
				- categories
				- invalid_email
				- business_role
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
        - accounts
        - leads
        - eventsessions
        - opportunities
        - documents
        - cases
        - bugs
        - contacts
        - project
        - campaigns
        - securitygroups
