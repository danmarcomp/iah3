<?php return; /* no output */ ?>

detail
	type: list
layout
    columns
        --
            field: bug_number
            width: 40
            add_fields
                --
                    field: name
                    list_format: separate
                    list_position: suffix
        - product
        --
            field: priority
        	add_fields
        		- type
            width: 20
        --
        	field: status
        	add_fields
        		- resolution
        	width: 20
        --
        	field: found_in
        	width: 20
        --
        	field: planned_for
        	width: 20
        --
        	field: fixed_in
        	width: 20
        - assigned_user
