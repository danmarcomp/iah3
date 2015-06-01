<?php return; /* no output */ ?>

detail
    type: search
    title: LBL_ADVANCED_SEARCH	
layout
	elements
		- date_entered
		- cancelled
        section
            field: shipping_stage
            default_value: Draft
            expanded: true
		filter_owner
			default_value: mine
