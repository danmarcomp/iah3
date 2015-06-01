<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
	form_buttons
    scripts
        --
            file: "modules/SubContracts/scontract.js"
	sections
		--
			id: main
			elements
                - name
                - contract_type
                - date_start
                - main_contract
                - date_expire
                - employee_contact
                - date_billed
                - customer_contact
                - is_active
                - vendor_contract
                --
                    name: description
                    colspan: 2
