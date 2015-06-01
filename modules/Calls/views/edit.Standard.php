<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
	form_buttons
        save_new
            vname: LBL_SAVE_NEW_BUTTON_LABEL
            type: button
            onclick: return SUGAR.ui.sendForm(this.form, {"format":"html","record_perform":"save","new_after_save":1}, null);
            order: 15
            icon: icon-accept
        call_int
            icon: theme-icon bean-Call
            order: 80
            type: button
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
                - email_reminder_time
                --
                	name: description
                	colspan: 2
        - event_scheduler
