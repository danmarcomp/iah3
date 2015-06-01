<?php return; /* no output */ ?>

detail
	type: list
layout
    columns
        - name
        --
            field: primary_module_link
            width: 35
        - last_run
        --
        	field: run_method
        	width: 25
        --
            field: date_modified
            format: date_only
        - assigned_user
		- shared_with