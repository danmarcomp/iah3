<?php return; /* no output */ ?>

detail
    type: edit
    title: LBL_NOTIFY_TITLE
    icon: EmailMan
layout
	form_buttons
	sections
		--
			id: main
			columns: 2
			show_descriptions: true
			widths: 5%
			label_widths: 20%
			desc_widths: 25%
			elements
				- notify_enabled
				-
				- notify_send_by_default
				- notify_send_from_assigning_user
				- notify_cases_user_create
				- notify_cases_user_update
				- notify_cases_contact_create
				- notify_cases_contact_update
				- notify_send_invites_by_default
				-
		--
			id: outbound
			vname: LBL_OUTBOUND_EMAIL_TITLE
			vname_module: EmailMan
			elements
				- notify_from_name
				- notify_from_address
				- email_send_type
				--
					id: smtp_settings
					type: section
					toggle_display
						name: email_send_type
						value: SMTP
					elements
						- email_smtp_server
						- email_smtp_port
						- email_smtp_auth_req
						-
						- email_smtp_user
						- email_smtp_password
				- massemailer_campaign_emails_per_run
		--
			id: relations
			vname: LBL_INBOUND_EMAIL_TITLE
			vname_module: EmailMan
			elements
				- email_relate_inbound
				- email_relate_skipdomains
				- email_import_max_session_messages
		--
			id: user_email
			vname: LBL_EMAIL_USER_TITLE
			vname_module: EmailMan
			columns: 1
			elements
				- email_default_client
				- email_default_editor
				- email_default_charset
		--
			id: security
			vname: LBL_SECURITY_TITLE
			vdesc: LBL_SECURITY_DESC
			vname_module: EmailMan
			show_descriptions: true
			elements
				- email_keep_raw
