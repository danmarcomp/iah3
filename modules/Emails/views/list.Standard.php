<?php return; /* no output */ ?>

detail
	type: list
layout
    columns
        --
        	field: name
        	add_fields
        		--
        			field: has_attach
        			list_position: prefix
        		--
        			field: isread
        			hidden: true
        - folder_ref
        - contact
        - parent
        - date_start
		- assigned_user
