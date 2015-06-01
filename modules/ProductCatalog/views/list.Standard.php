<?php return; /* no output */ ?>

detail
	type: list
layout
    columns
        --
            field: name
            add_fields: [manufacturers_part_no]
        --
            field: supplier
            add_fields: [vendor_part_no]
        --
            field: product_category
            add_fields
                --
                    field: product_type
                    list_format: hyphen
        --
            field: is_available
            add_fields
                --
                    field: all_stock
                    list_format: parenth
                    list_position: suffix
        - cost
        - list_price
        - purchase_price
        - support_selling_price
