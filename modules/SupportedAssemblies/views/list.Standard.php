<?php return; /* no output */ ?>

detail
	type: list
layout
    columns
        --
            field: name
            add_fields: [manufacturers_part_no]
        --
            field: account
        - quantity
        --
            field: product_category
            add_fields
                --
                    field: product_type
                    list_format: hyphen
