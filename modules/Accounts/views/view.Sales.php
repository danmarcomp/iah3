<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	form_buttons
		pdf
			vname: LBL_STATEMENT_BUTTON_LABEL
			accesskey: LBL_STATEMENT_BUTTON_KEY
			icon: icon-print
			params
				action: PDF
			async: false
			group: print
	sections
		--
			id: customer_info
			vname: LBL_SALES_INFORMATION_TITLE
			hidden: bean.is_supplier
			elements
				- first_invoice
				- last_invoice
				- balance
				- default_discount
				- credit_limit
				- default_shipper
				- tax_code
				- tax_information
				- default_terms
		--
			id: supplier_info
			vname: LBL_PURCHASING_INFORMATION_TITLE
			hidden: ! bean.is_supplier
			elements
				- balance_payable
				- default_purchase_discount
				- purchase_credit_limit
				- default_purchase_shipper
				- default_purchase_terms
    subpanels
        - opportunities
        - leads
        --
            name: quotes
            hidden: bean.is_supplier
        --
            name: salesorders
            hidden: bean.is_supplier
        --
            name: invoice
            hidden: bean.is_supplier
        --
        	name: credits
        	hidden: bean.is_supplier
        --
            name: purchaseorders
            hidden: ! bean.is_supplier
        --
            name: bills
            hidden: ! bean.is_supplier
        - expensereports
        --
            name: products
            hidden: ! bean.is_supplier
