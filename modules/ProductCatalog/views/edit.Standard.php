<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
    scripts
        --
            file: "modules/ProductCatalog/products.js"
        --
            file: "modules/ProductCatalog/product_formulas.js"
    form_hooks
    	oninit: "initProductForm({FORM})"
	sections
		--
			elements
                - name
                - is_available
                - purchase_name
                - date_available
                - track_inventory
                - product_category
                - model
                - product_type
                - manufacturer
                - supplier
                - manufacturers_part_no
                - vendor_part_no
                - currency
                - weight_1
                - tax_code
                - weight_2
                - cost
                - support_cost
                - list_price
                - support_list_price
                - purchase_price
                - support_selling_price
                - pricing_formula
                - support_price_formula
                - ppf_perc
                - support_ppf_perc
                - eshop
                - 
                --
                    name: url
                    width: 80
                    len: 255
                    colspan: 2
                --
                    name: image_url
                    width: 80
                    len: 255
                    colspan: 2
                --
                    name: image_file
                    colspan: 2
                --
                    name: thumbnail_url
                    width: 80
                    len: 255
                    colspan: 2
                --
                    name: thumbnail_file
                    colspan: 2
                --
                    name: description
                    colspan: 2
                --
                    name: description_long
                    colspan: 2
