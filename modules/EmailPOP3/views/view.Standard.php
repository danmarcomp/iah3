<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	form_buttons
		conn_test
			vname: LBL_CONNECTION_TEST
			perform:
				SUGAR.popups.openUrl('async.php?module=EmailPOP3&action=ConnectionTest&record={RECORD}'); return false;
	sections
		--
            id: main
            elements
                - name
                - active
                - from_name
                - email
                - host
                - username
                - protocol
                - password
                - port
                - use_ssl
                - leave_on_server
                -
                - mailbox_type
                - email_folder
                - template
                - filter_domain
                - ooo_template
                - scheduler
