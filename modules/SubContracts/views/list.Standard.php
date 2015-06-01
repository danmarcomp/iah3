<?php return; /* no output */ ?>

detail
	type: list
layout
    columns
        --
            field: name
            add_fields
                --
                    field: status
                    list_position: prefix
        - contract_type
        - date_start
        - date_expire
        - date_billed
        - total_purchase
        - total_support_cost
        - employee_contact
