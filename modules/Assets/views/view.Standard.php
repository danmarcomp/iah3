<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	sections
		--
            elements
                - name
                - date_available
                - quantity
                - account
                - supplier
                - service_subcontract
                - manufacturer
                - model
                - manufacturers_part_no
                - weight
                - vendor_part_no
                - product_category
                - tax_code
                - product_type
                - currency
                - warranty_start_date
                - purchase_price
                - warranty_expiry_date
                - unit_support_price
                - date_entered
                - support_cost
                - date_modified
                - supported_assembly
                - project
                --
                    name: url
                    colspan: 2
                --
                    name: description
                    colspan: 2
        --
            id: serial_numbers
            vname: LBL_SERIAL_NUMBERS
            widget: SerialNumberWidget
    subpanels
        - cases
        - suppliers
        - securitygroups

