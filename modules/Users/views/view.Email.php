<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	sections
        --
            id: email_settings
            vname: LBL_MAIL_OPTIONS_TITLE
            widget: UserInfoWidget
        --
            id: outbound_email
            vname: LBL_EMAIL_OUTBOUND_TITLE
            widget: UserInfoWidget
    subpanels
        - mailboxes
