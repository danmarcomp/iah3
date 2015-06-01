<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	form_buttons
        #online_shipping
            #vname: LBL_ONLINE_SHIPPING
            #type: button
            #onclick: "window.open('index.php?module=Shipping&action=OnlineStub&record='+this.form.record.value+'&to_pdf=true', '', 'width=400,height=200,resizable=0,scrollbars=0');"
        print_ship
        	icon: theme-icon bean-Shipping
            vname: LBL_PDF_PACKING_SLIP
            type: button
            widget: PdfButton
            group: print
	sections
		--
			id: main
			elements
				- name
				- full_number
                - assigned_user
				- shipping_stage
				- purchase_order_num
				- date_entered
				- invoice
				- date_modified
				- so
                -
				- weight_1
                - weight_2
				- shipping_cost
				- warehouse
				- tracking_number
				- date_shipped
				- shipping_address
                -
				--
					name: description
					colspan: 2
		- line_items
    subpanels
        - history
        - securitygroups
