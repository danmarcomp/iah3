<?php return; /* no output */ ?>

detail
	type: dashlet
layout
    columns
        --
            field: name
            width: 40
            add_fields
            	- status
            	- led_color
        --
            field: priority
            width: 29
        --
            field: date_due
            format: date_only
        --
            field: date_entered
            format: date_only
            hidden: true
        --
            field: assigned_user
