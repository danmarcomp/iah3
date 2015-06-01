<?php return; /* no output */ ?>

detail
	type: list
layout
    columns
        - full_name
        - user_name
        - department
        --
            vname: LBL_LIST_EMAIL_PHONE
            add_fields: [email1, phone_work]
            width: 50
        - status
        - is_admin
        - portal_only
