<?php return; /* no output */ ?>

detail
	type: list
layout
    columns
        --
            field: case_number
            width: 40
            add_fields
                --
                    field: name
                    list_format: separate
                    list_position: suffix
        --
            field: account
            add_fields: [cust_contact]
        --
            field: priority
            width: 15
        --
            field: status
            width: 30
        - age
        - assigned_user
