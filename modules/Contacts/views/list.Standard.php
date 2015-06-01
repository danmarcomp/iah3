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
        - primary_account
        --
            vname: LBL_LIST_EMAIL_PHONE
            add_fields: [email1, phone_work]
            width: 50
        - categories
        --
            field: assigned_user
            width: 20
        --
            field: social_icons
