<?php return; /* no output */ ?>

detail
	type: list
layout
    columns
        - full_number
        - name
        - type
        - assigned_user
        - total_amount
        - status
        --
            field: date_submitted
            format: date_only
        --
            field: date_approved
            format: date_only
        --
            field: date_paid
            format: date_only
