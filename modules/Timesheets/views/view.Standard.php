<?php return; /* no output */ ?>
# vim: set tabstop=4 expandtab :
detail
    type: view
    title: LBL_MODULE_TITLE
layout
    form_buttons
        pdf
            vname: LBL_PDF_BUTTON
            accesskey: LBL_PDF_BUTTON_KEY
            icon: theme-icon bean-Timesheet
            params
                action: PDF
            target: _blank
            group: print
    sections
        --
            elements
                - name
                - assigned_user
                --
                    name : status
                - total_hours
                - timesheet_period
                - 
                --
                    name: date_starting
                    onchange: TimesheetEditor.start_date_changed(this.date)
                    disable_date : TimesheetEditor.start_date_disabled(date)
                --
                    name: date_ending
                    onchange: TimesheetEditor.end_date_changed(this.date)
                    disable_date : TimesheetEditor.end_date_disabled(date)
                --
                    name: description
                    colspan: 2
        - timesheet_items
    form_hidden_fields
        approve_action :
    scripts
        --
            file: "modules/Timesheets/timesheets.js"

