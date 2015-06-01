<?php return; /* no output */ ?>

detail
	type: dashlet
layout
    columns
        --
            field: name
            width: 30
            add_fields
                --
                    field: led_color
                    list_position: prefix
        --
            field: account
            width: 25
            add_fields
            	- project_type
        --
            field: date_ending
            width: 15
        --
            field: percent_complete
            width: 5
            hidden: true
        --
            field: assigned_user
            width: 15
