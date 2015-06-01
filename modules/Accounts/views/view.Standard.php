<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	form_buttons
		pdf
			vname: LBL_STATEMENT_BUTTON_LABEL
			accesskey: LBL_STATEMENT_BUTTON_KEY
			icon: icon-print
			params
				action: PDF
			target: _blank
			group: print
	sections
		--
			id: primary_contact_info
			vname: LBL_PRIMARY_CONTACT
			vname_module: Accounts
            widget: PrimaryContactWidget
			hidden: !mode.B2C
		--
			id: main
			elements
				- name
				--
					name: phone_office
					hidden: mode.B2C
				- is_supplier
				-
				- website
				--
					name: phone_fax
					hidden: mode.B2C
				- ticker_symbol
				--
					name: phone_alternate
					hidden: mode.B2C
				- member_of
				--
					name: email1
					hidden: mode.B2C
				- employees
				--
					name: email2
					hidden: mode.B2C
				- ownership
				- rating
				- industry
				- sic_code
				- account_type
				- annual_revenue
				- main_service_contract
				- currency
				- temperature
				-
				- partner
				-
				- last_activity_date
				- date_modified
				--
					name: assigned_user
					hidden: mode.B2C
				- date_entered
				--
					name: billing_address
					hidden: mode.B2C
				--
					name: shipping_address
					hidden: mode.B2C
				--
					name: description
					colspan: 2
		--
			id: account_popups
			vname: LBL_ACCOUNT_POPUPS
			hidden: ! bean.account_popups
			columns: 1
			elements
				- account_popup
				- sales_popup
				- service_popup
    subpanels
        - activities
        - history
        - contacts
        - members
        - eventsessions
        - cases
        - documents
        - threads
        - bugs
        - project
        - assets
        - supported_assemblies
        - securitygroups
