<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
    scripts
        --
            file: "modules/CompanyAddress/company_address.js"
    form_hooks
        oninit: "initCompanyAddress({FORM})"
	sections
		--
			id: main
			elements
                - name
                - main
                - address
                - address_format
                - phone
                - is_warehouse
                - fax
                - main_warehouse
        --
        	id: logo
        	columns: 1
        	show_descriptions: true
        	elements
                - logo
				- logo_clear
