<?php return; /* no output */ ?>

detail
	type: subpanel
layout
    columns
        --
            field: name
            add_fields: [full_number]
            width: 25
        --
            field: amount
            width: 20
        --
            field: ~join.amount
            width: 20
        --
            field: amount_due
            add_fields
                --
                    field: terms
                    require_primary: true
