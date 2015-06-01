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
        --
        	field: terms
        	width: 22
        - so_stage
        - amount
        - due_date
        - assigned_user
