<?php return; /* no output */ ?>

detail
	type: list
layout
    columns
        --
            field: full_number
            add_fields: [name]
            width: 40
        - supplier
        - amount
        --
            field: amount_due
            add_fields
                --
                    field: terms
                    require_primary: true
        - due_date
        - assigned_user
