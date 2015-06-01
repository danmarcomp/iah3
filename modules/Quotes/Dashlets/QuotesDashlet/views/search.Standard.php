<?php return; /* no output */ ?>

detail
    type: search
    title: LBL_ADVANCED_SEARCH
layout
	elements
		date_entered
		date_modified
        --
        	field: valid_until
        	operator: before_date
        quote_stage
        	expanded: true
		filter_owner
			default_value: mine
