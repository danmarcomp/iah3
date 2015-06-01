<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
    scripts
        --
            file: "modules/Booking/booking_dialog/bookingEditView.js"
    form_hooks
    	oninit: "HoursEditView.initEditView({FORM})"
	sections
		--
			id: main
			elements
				--
					name: name
					colspan: 2
					width: 80
				- date_start
				- quantity
				- booking_category
				- status
				- related
				-
				- account
				- assigned_user
				--
					id: financials
					type: section
					vname: LBL_ADDITIONAL_DETAILS
					elements
						- billing_currency
						- paid_currency
						- billing_rate
						- paid_rate
						- billing_total
						- paid_total
						- tax_code
