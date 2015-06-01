<?php return; /* no output */ ?>

detail
	type: subpanel
layout
	buttons
		add_contact
			vname: LBL_INVITEE_ADD_CONTACT
			template: add_existing
			module: Contacts
			passthru_data
				update_link: contacts
			params
				toplevel: true
		add_user
			vname: LBL_INVITEE_ADD_USER
			template: add_existing
			module: Users
			passthru_data
				update_link: users
			params
				toplevel: true
    columns
    	- accept_status
        - name
        - email1
        - phone_work
