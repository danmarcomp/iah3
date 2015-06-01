<?php return; /* no output */ ?>

detail
	type: list
layout
    columns
        - full_name
        - estim_hours
        - ~join.estim_cost
        - ~join.actual_hours
        - ~join.actual_cost
        - ~join.utilization
        - hourly_cost
        --
        	field: ~join.booking_status
        	widget: QuickEditListElt

