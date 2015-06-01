<?php return; /* no output */ ?>

detail
	type: list
layout
    columns
        --
            field: ~join.quantity
            width: 5
			widget: QuickEditListElt
        --
            field: discount_name
            width: 25
        --
            field: name
            add_fields: [manufacturers_part_no]
        --
            field: product_category
            add_fields
                --
                    field: product_type
                    list_format: hyphen
        --
            field: is_available
            width: 10
            add_fields
                --
                    field: all_stock
                    list_format: parenth
                    list_position: suffix
        - cost
        - list_price
        - purchase_price
