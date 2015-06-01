<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
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
                - model
                - date_entered
                - eshop
                - date_modified
                --
                    name: product_url
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
    subpanels
        - products
        - suppliers
