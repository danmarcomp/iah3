<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE	
layout
	form_buttons
	sections
		--
			id: main
			elements
				- name
				- supplier
				- full_number
				- supplier_contact
				- invoice_reference
				- cancelled
				--
                    name: bill_date
                    onchange: setDateFromTerm(this.form);
				- amount_due
				- from_purchase_order
                --
                    name: terms
                    onchange: setDateFromTerm(this.form);
                - assigned_user
                - due_date
		--
			id: price_ship
			column_titles
				- LBL_PRICING_INFO
				- LBL_SHIPPING_INFO
			elements
				- currency
				- shipping_provider
				- tax_information
				-
		- line_items
		--
			id: other
			vname: LBL_DESCRIPTION_INFORMATION
			elements
				--
					name: description
					colspan: 2
