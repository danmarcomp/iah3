<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
    form_buttons
        show_calendar
            type: button
            order: 69
            widget: CalendarButton
            hidden: ! bean.date_start
	sections
		--
			id: main
			elements    
                --
                    name: name
                    colspan: 2
                - date_start
                - quantity
                - booking_category
                - status
                - booking_class
                -
                - related
                - invoice
                - account
                - timesheet
                - billing_currency
                - paid_currency
                - billing_rate
                - paid_rate
                - billing_total
                - paid_total
                - tax_code
                - date_entered
                - assigned_user
                - date_modified
