<?php return; /* no output */ ?>

detail
	type: dashlet
layout
    columns
        --
            field: name
            width: 40
            add_fields
            	--
            		field: bug_number
            		list_format: separate
            		list_position: prefix
        --
            field: priority
            width: 10
        --
            field: status
            width: 10
        --
            field: assigned_user
            width: 15
