<?php return; /* no output */ ?>

detail
	type: subpanel
layout
    columns
        --
            field: name
            add_fields: [account_type]
            width: 60
        --
            field: list_location
            width: 40
            add_fields: [website]
        --
            vname: LBL_LIST_EMAIL_PHONE
            add_fields: [email1, phone_office]
            width: 50
        - assigned_user
        - social_icons
