<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
    buttons_hook
        class: Meeting
        file: modules/Meetings/Meeting.php
        class_function: mutateDetailButtons
    form_buttons
        show_calendar
            type: button
            order: 69
            widget: CalendarButton
            hidden: ! bean.date_start
    sections
        --
            name: recurring
            hidden: ! bean.is_recurring
        --
			id: main
			elements
                - name
                - status
                - location
                - parent
                - date_start
                - is_private
                - duration
                - assigned_user
                - reminder_time
                - email_reminder_time
                -
                - date_modified
                -
                - date_entered
                --
                    name: description
                    colspan: 2            
    subpanels
        - invitees
        - resources
        - notes
        - securitygroups
