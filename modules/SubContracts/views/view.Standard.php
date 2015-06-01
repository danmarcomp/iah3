<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
    scripts
        --
            file: "modules/Service/contract.js"
    form_buttons
        pdf
            vname: LBL_PRINT_BUTTON_LABEL
            accesskey: LBL_PRINT_BUTTON_LABEL_KEY
            icon: icon-print
            params
                action: PDF
            async: false
        set_warranty_dates
            vname: SET_WARRANTY_DATES_BUTTON_LABEL
            accesskey: SET_WARRANTY_DATES_BUTTON_KEY
            onclick: "this.form.action.value='SetWarrantyDatesSub';return warranty_conf();"
            async: false
	sections
		--
			id: main
			required_fields
                - main_contract.account_id
			elements
                - name
                - contract_type
                - date_start
                - main_contract
                - date_expire
                - employee_contact
                - date_billed
                - customer_contact
                - total_purchase
                - vendor_contract
                - total_support_cost
                -
                - prepaid_balance
                - date_entered
                - is_active
                - date_modified
                --
                    name: description
                    colspan: 2
    subpanels
        - assets
        - supportedassemblies
        - cases
        - prepaid_amounts
