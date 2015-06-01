<?php return; /* no output */ ?>

detail
	type: subpanel
layout
    columns
        --
            field: name
            add_fields: [full_number]
            width: 30
        --
            field: amount
            width: 20
        --
            field: ~join.amount
            width: 20
        --
            field: amt_due
