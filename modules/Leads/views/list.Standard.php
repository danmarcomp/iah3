<?php return; /* no output */ ?>

detail
	type: list
layout
    columns
        --
        	field: name
            add_fields
                --
                    field: title
        - status
        - account_name
        --
            vname: LBL_LIST_EMAIL_PHONE
            add_fields: [email1, phone_work]
            width: 50
        - categories
        - assigned_user
        - social_icons
