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
            vname: LBL_LIST_LOCATION
            type: location_city
            fields: [billing_address_city, billing_address_state, billing_address_country]
            width: 30
            add_fields: [website]
        --
            vname: LBL_LIST_EMAIL_PHONE
            add_fields: [email1, phone_office]
            width: 50
        - balance_payable
        - assigned_user
        - social_icons
