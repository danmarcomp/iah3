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
                - account_name
                - phone_home
                - title
                - phone_other
                - department
                - phone_fax
                - assistant
                - email1
                - assistant_phone
                - email2
                - birthdate
                - invalid_email
                -
                - email_opt_out
                - assigned_user
                - do_not_call
		--
			id: address_info
			elements
				--
					name: primary_address
					copy_to: alt_address
				--
					name: alt_address
					copy_to: primary_address
		--
			id: other
			vname: LBL_DESCRIPTION_INFORMATION
			elements
				- description
