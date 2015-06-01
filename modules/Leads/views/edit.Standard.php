<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
	sections
		--
			elements
                - lead_source
                - status
                - temperature
                -
                - lead_source_description
                - status_description
                - refered_by
                - categories
				--
					name: first_name
					widget: SalutationNameWidget
                - phone_work
                - last_name
                - phone_mobile
                -
                - phone_home
                - account_name
                - phone_other
                - website
                - phone_fax
                - title
                - email1
                - department
                - email2
                - do_not_call
                - email_opt_out
                - assigned_user
                - invalid_email
                - partner
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
