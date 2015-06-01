<?php return; /* no output */ ?>

detail
	type: list
layout
    columns
        --
            field: order_number
            width: 10
        - name
        - parent
        --
            field: date_due
            format: date_only
        - status
        - assigned_user
