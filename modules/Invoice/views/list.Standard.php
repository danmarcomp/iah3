<?php return; /* no output */ ?>

detail
	type: list
layout
    columns
        --
            field: full_number
            add_fields: [name]
            width: 40
        - shipping_account
        - amount
        --
            field: amount_due
            add_fields
                --
                    field: terms
                    require_primary: true
        --
        	field: due_date
        	width: 16
        - assigned_user
