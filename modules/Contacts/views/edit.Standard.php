<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
	sections
		--
			id: main
			elements
                --
                	name: first_name
                	widget: SalutationNameWidget
                - phone_work
                - last_name
				- phone_mobile
				-
				-
				--
					name: primary_account
					hidden: mode.B2C
                - phone_home
                - lead_source
                - phone_other
                - title
                - phone_fax
                - department
                - email1
                - birthdate
                - email2
                - reports_to
                - assistant
                - #sync_contact
                - assistant_phone
                - do_not_call
                - email_opt_out
                - email_accounts
                - partner
                - categories
                - business_role
                - assigned_user
                - invalid_email
		--
			id: address_info
			vname: LBL_ADDRESS_INFORMATION
			elements
				--
					name: primary_address
					copy_to: alt_address
				--
					name: alt_address
					copy_to: primary_address
        --
            id: social_accounts
            widget: SocialAccountsWidget
		--
			id: convert_lead
			vname: LBL_RELATED_RECORDS
			widget: RelatedRecordsWidget
			no_sel_account: true
            hidden: ! mode.new_record
		--
			id: description_info
			columns: 1
			vname: LBL_DESCRIPTION_INFORMATION
			elements
				- description
        --
            id: portal_info
            vname: LBL_PORTAL_INFORMATION
            elements
                - portal_name
                - portal_active
