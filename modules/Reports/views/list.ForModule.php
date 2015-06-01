<?php return; /* no output */ ?>

detail
	type: list
layout
    columns
        --
        	field: name
        	show_icon: true
        	icon: Reports
        - last_run
        - run_method
        --
            field: date_modified
            format: date_only
        - assigned_user
		- shared_with
