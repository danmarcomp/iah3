<?php return; /* no output */ ?>

detail
	type: list
layout
    columns
		-- 
			field: user
			show_icon: false
		--
			field: opportunities
            sum_total: true
        --
            field: total
            sum_total: true
        --
            field: weighted
            sum_total: true
        --
            field: best_case
            sum_total: true
        --
            field: commit
            sum_total: true
        --
            field: actual
            sum_total: true
        --
            field: forecast
            sum_total: true
        --
            field: quota
            sum_total: true
        - quota_percent
        
