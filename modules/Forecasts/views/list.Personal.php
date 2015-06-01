<?php return; /* no output */ ?>

detail
	type: list
layout
    columns
        - name
        - account
        --
            field: amount
            sum_total: true
        --
            field: probability
            width: 8
        --
            field: forecast_category
            width: 15
        --
            field: weighted_amount
            sum_total: true