<?php return; /* no output */ ?>

detail
	type: popup
layout
    columns
        --
            field: name
            add_fields
                - manufacturers_part_no
            width: 30
        --
            field: product_category
            width: 30
        --
            field: product_type
            width: 30
        --
            field: is_available
            width: 15
            add_fields
                --
                    field: all_stock
                    list_format: parenth
                    list_position: suffix
        --
            field: cost
            width: 20
            display_converted: true
        --
            field: purchase_price
            width: 20
            display_converted: true
