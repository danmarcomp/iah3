<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	sections
		--
            elements
                - name
                - is_available
                - purchase_name
                - date_available
                - track_inventory
                - product_category
                - all_stock
                - product_type
                - manufacturer
                - supplier
                - manufacturers_part_no
                - vendor_part_no                
                - currency
                - model
                - tax_code
                - weight
                - cost
                - support_cost
                - list_price
                - support_list_price
                - purchase_price
                - support_selling_price
                - pricing_formula
                - support_price_formula
                - eshop
                - date_entered
                -
                - date_modified
                --
                    name: url
                    colspan: 2
                --
                    name: image_url
                    vname: LBL_PRODUCT_IMAGE
                    colspan: 2
                --
                    name: thumbnail_url
                    vname: LBL_PRODUCT_THUMBNAIL
                    colspan: 2
                --
                    name: description
                    colspan: 2
                --
                    name: description_long
                    colspan: 2
    subpanels
        - warehouses
        - suppliers
        - productattributes
        - securitygroups

