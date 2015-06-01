<?php return; /* no output */ ?>

detail
	type: list
layout
    columns
        --
            field: full_number
            width: 20
        --
            field: account
            add_fields: [customer_reference]
        --
        	field: payment_type
        	width: 25
        --
        	field: total_amount
        	width: 20
        --
            field: payment_date
            detail_link: true
            width: 20
        - assigned_user
