<?php return; /* no output */ ?>

detail
	type: dashlet
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
            width: 30
        --
            field: contact
            width: 18
        --
            field: date_start
            width: 15
