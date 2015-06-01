<?php return; /* no output */ ?>

detail
	type: list
layout
    columns
		- close_activity
        --
        	field: name
        	add_fields
        		- status
        --
        	field: contact
        	add_fields
				- contact_phone
        - parent
        --
            field: priority
            width: 20
        - date_due
        - assigned_user
