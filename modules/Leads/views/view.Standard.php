<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	form_buttons
		subs
			vname: LBL_SUBS_BUTTON_LABEL
			url: ?module=Campaigns&action=Subscriptions&record={RECORD}&{RETURN}
			async: false
			acl: edit
		create_contact
			group: convert
			vname: LBL_CONVERTLEAD
			icon: theme-icon bean-Contact
			url: ?module=Contacts&action=EditView&lead_id={RECORD}&{RETURN}
			hidden: bean.status=Converted
	sections
		--
			id: converted
			hidden: ! bean.contact_id
			elements
				- contact
				--
					name: account
					hidden: ! bean.account_id
				--
					name: opportunity
					hidden: ! bean.opportunity_id
				-
		--
			id: main
			elements
				- lead_source
				- status
				- lead_source_description
				- status_description
				- refered_by
				- categories
				- temperature
				- phone_work
				--
					name: name
					widget: SalutationNameWidget
				- phone_mobile
				- last_activity_date
				- phone_home
				- account_name
				- phone_other
				- campaign
				- phone_fax
				- title
				- email1
				- department
				- email2
				- do_not_call
				- email_opt_out
				- website
				- invalid_email
				-
				- date_modified
				- assigned_user
				- date_entered
                - partner
                -
				- primary_address
				- alt_address
				--
					name: description
					colspan: 2
    subpanels
        - activities
        - history
        - eventsessions
        - campaigns
        - documents
        - securitygroups
