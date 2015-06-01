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
                - assembly_name
                --
                    name: account
                    onchange: set_contract_filters(this.form, this.getKey());
                - quantity
                - service_subcontract
                - supplier
                --
                    name: product_category
                    onchange: set_cat_filter(this.field.form, 1)
                - manufacturer
                - product_type
                - manufacturers_part_no
                - model
                - vendor_part_no
                -
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
