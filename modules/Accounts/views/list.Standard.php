<?php return; /* no output */ ?>

detail
	type: list
layout
    columns
        --
            field: name
            add_fields: [account_type]
            width: 60
        --
            field: list_location
            width: 30
            add_fields: [website]
        --
            vname: LBL_LIST_EMAIL_PHONE
            add_fields: [email1, phone_office]
            width: 50
        --
            field: main_service_contract
            width: 25
        - balance
        - assigned_user
        - social_icons
