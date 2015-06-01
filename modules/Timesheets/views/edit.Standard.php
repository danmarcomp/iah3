<?php return; /* no output */ ?>
# vim: set tabstop=4 expandtab :
detail
    type: editview
    title: LBL_MODULE_TITLE
layout
    form_buttons
        submit
            vname: LBL_SUBMIT_BUTTON_LABEL
            accesskey: LBL_SUBMIT_BUTTON_KEY
            type: button
            onclick : return TimesheetEditor.submit_unsubmit()
    sections
        --
            elements
                - name
                - assigned_user
                --
                    name : status
                - total_hours
                --
                    name: date_starting
                    onchange: TimesheetEditor.start_date_changed(this.date)
                    disable_date : TimesheetEditor.start_date_disabled(date)
                --
                    name: date_ending
                    onchange: TimesheetEditor.end_date_changed(this.date)
                    disable_date : TimesheetEditor.end_date_disabled(date)
                - timesheet_period
                -
                --
                    name: description
                    colspan: 2
        - timesheet_items
    form_hidden_fields
	    line_items :
        hours_data_updated :
        submit_timesheet :
    scripts
        --
            file: "modules/Timesheets/timesheets.js"

