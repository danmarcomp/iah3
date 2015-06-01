<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
	sections
		--
			id: main
			required_fields
				- user_id
            elements
                - name
                - active
                - from_name
                - scheduler
                - host
                - use_ssl
				--
					name: protocol
					options_function: get_protocol_options
                - port
				- username
                - email
                - password
                -
                --
                	id: imap_opts
                	section: true
					toggle_display
						name: protocol
						value: IMAP
					elements
						- imap_folder
						-
						- restrict_since
						- mark_read
                - leave_on_server
                - mailbox_type
                - email_folder
                - template
                - filter_domain
                - ooo_template
