<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	sections
		--
			id: main
			elements
                - instance_number
                - address
                - booking_category
                - start_date
                - account
                - end_date
                - quote
                - paid_until
                - frequency
                - assigned_user
                - invoice_terms
                - billing_day
                - invoice_value
                - purchase_order_num
                - total_sales
                - contact
                - balance_due
                - cc_user
    subpanels
        - invoices
