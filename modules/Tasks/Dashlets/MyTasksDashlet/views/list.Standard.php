<?php return; /* no output */ ?>

detail
	type: dashlet
layout
    default_order_by: date_due
    columns
        - close_activity
        --
            field: name
            width: 40
        --
            field: priority
            width: 10
        --
            field: date_start
            width: 20
            format: date_only
        --
            field: date_due
            width: 20
        --
            field: assigned_user
            width: 15
