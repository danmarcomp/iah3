<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
    scripts
        --
            file: "modules/Assets/supported.js"
	sections
		--
			elements
                - product_name
                - date_available
                - quantity
                --
                    name: account
                    onchange: set_contract_filters(this.form, this.getKey());
                - supplier
                - service_subcontract
                - manufacturer
                - model
                - manufacturers_part_no
                - vendor_part_no
                - weight_1
                --
                    name: product_category
                    onchange: set_cat_filter(this.field.form, 1)
                - weight_2
                - product_type
                - tax_code
                - currency
                - purchase_price
                - warranty_start_date
                - unit_support_price
                - warranty_expiry_date
                - support_cost
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
