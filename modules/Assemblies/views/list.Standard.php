<?php return; /* no output */ ?>

detail
	type: list
layout
    columns
        - name
        --
            field: manufacturer
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
        - eshop
