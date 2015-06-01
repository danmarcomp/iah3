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
                - name
                - status
                - date_due
                - parent
                - date_start
                - contact
                - priority
                - contact_email
                - effort_estim
                - contact_phone
                - effort_actual
                - date_modified
                - assigned_user
                - date_entered
                - is_private
                -
                --
                    name: description
                    colspan: 2
    subpanels
        - emails
        - calls
        - securitygroups
