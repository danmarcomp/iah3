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
        call_int
            icon: theme-icon bean-Call
            type: button
            order: 70
            widget: CallButton
            align: right
            hidden: ! bean.phone_number
	sections
		--
			id: main
			elements
                - name
                - status
                - direction
                - parent
                - date_start
                - is_private
                - duration
                - assigned_user
                - phone_number
                -
                - reminder_time
                - date_modified
                - email_reminder_time
                - date_entered
                --
                    name: description
                    colspan: 2            
    subpanels
    	- invitees
        - notes
        - securitygroups
