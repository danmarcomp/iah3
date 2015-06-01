<?php return; /* no output */ ?>

detail
	type: list
layout
    columns
        - full_name
        - description
    	--
    		field: date_entered
        --
            field: date_modified
            format: date_only
        - assigned_user
