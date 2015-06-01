<?php return; /* no output */ ?>

detail
	type: list
layout
    columns
        --
        	field: event
			add_fields
        		- event.format
        --
        	vname: LBL_LIST_SESSION_NUMBER
        	field: name
        	add_fields
        		--
        			field: session_number
                    list_format: separate
        			list_position: prefix
        - event.event_type
        - date_start
        - date_end
        - attendee_limit
        - registrant_count
