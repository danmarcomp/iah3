<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
    form_buttons
        booking
            type: button
            widget: BookingButton
            order: 40
        show_calendar
            type: button
            order: 69
            widget: CalendarButton
            hidden: ! date_start!date_only
            date_field: date_start!date_only
    scripts
        --
            file: "modules/ProjectTask/ptask.js"
        --
            file: "modules/Booking/booking_dialog/bookingEditView.js"
        --
            file: "modules/Users/pt_popup.js"
	sections
		--
			id: main
			elements
                --
                	name: name
                	colspan: 2
                - parent
                - status
                - date_start
                - priority
                - date_due
                - percent_complete
                - currency                
                - depends_on
                - task_number
                - dependency_type
                - include_weekends
                - order_number
                - portal_flag
                - milestone_flag
                -
                - assigned_user
                --
                    name: description
                    colspan: 2
        --
            id: task_costs
            vname: LBL_FINANCIAL_INFORMATION
            widget: CostsWidget
    subpanels
        - booking_users
        - booked_hours
        - activities
        - history
        - securitygroups
