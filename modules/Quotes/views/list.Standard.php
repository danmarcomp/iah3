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
        	field: quote_stage
        	width: 20
        - amount
        - valid_until
        - assigned_user
