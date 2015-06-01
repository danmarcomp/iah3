<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE	
layout
	form_buttons
		recurrence
			widget: RecurrenceButton
			module: Meetings
            hidden: !mode.new_record&&!bean.can_edit_recurrence
        save_new
            vname: LBL_SAVE_NEW_BUTTON_LABEL
            type: button
            onclick: return SUGAR.ui.sendForm(this.form, {"format":"html","record_perform":"save","new_after_save":1}, null);
            order: 15
            icon: icon-accept
	sections
        --
            name: recurring
            hidden: mode.new_record || !bean.is_recurring
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
                --
                	name: description
                	colspan: 2
        - event_scheduler
