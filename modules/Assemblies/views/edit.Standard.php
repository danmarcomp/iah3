<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
    scripts
        --
            file: "modules/ProductCatalog/products.js"
    form_hooks
    	oninit: "initAssemblyForm({FORM})"
	sections
		--
			elements
                - name
                - product_category
                - purchase_name
                - product_type
                - manufacturer
                - supplier
                - manufacturers_part_no
                - vendor_part_no
                - eshop
                - model
                --
                    name: product_url
                    width: 80
                    len: 255
                    colspan: 2
                --
                    name: image_url
                    width: 80
                    colspan: 2
                --
                    name: image_file
                    colspan: 2
                --
                    name: thumbnail_url
                    width: 80
                    colspan: 2
                --
                    name: thumbnail_file
                    colspan: 2
                --
                    name: description
                    colspan: 2
