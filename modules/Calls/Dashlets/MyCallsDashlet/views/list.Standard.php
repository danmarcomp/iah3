<?php return; /* no output */ ?>

detail
	type: dashlet
layout
    default_order_by: date_start
    columns
		- close_activity
        --
            field: status
            width: 5
        --
            field: name
        --
            field: date_start
        	add_fields
            	- duration
        - accept_status
        --
            field: assigned_user
			hidden: true
