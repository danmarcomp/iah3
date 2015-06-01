<?php return; /* no output */ ?>

detail
	type: list
layout
    columns
        --
            field: full_number
            add_fields: [name]
            width: 40
        --
            field: supplier
            add_fields: [supplier_contact]
        - amount
        - terms
        --
            field: date_entered
            format: date_only
        - shipping_stage
        - assigned_user
