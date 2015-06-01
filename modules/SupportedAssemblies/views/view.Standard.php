<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	sections
		--
            elements
                - name
                - account
                - quantity
                - service_subcontract
                - supplier
                - product_category
                - manufacturer
                - model
                - manufacturers_part_no
                - product_type
                - vendor_part_no
                - date_entered
                - project
                - date_modified
                --
                    name: product_url
                    colspan: 2
                --
                    name: description
                    colspan: 2
        --
            id: serial_numbers
            vname: LBL_SERIAL_NUMBERS
            widget: SerialNumberWidget
    subpanels
        - assets
        - suppliers
