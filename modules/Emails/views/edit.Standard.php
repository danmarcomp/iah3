<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
    form_hooks
        focus: "set_email_focus({FORM})"
    form_buttons
        send
            vname: LBL_SEND_BUTTON_LABEL
            accesskey: LBL_SEND_BUTTON_KEY
            type: submit
            icon: icon-send
			params
				record_perform: save
            	type: out
            	send: 1
            order: 1
            hidden: bean.status=archived||bean.status=received
        save_draft
            vname: LBL_SAVE_AS_DRAFT_BUTTON_LABEL
            type: submit
            icon: icon-accept
            params
				record_perform: save
                type: draft
            	send: 0
            hidden: bean.type!=out
        save
            vname: LBL_SAVE_BUTTON_LABEL
            type: submit
            params
                send: 0
            hidden: bean.type=out
        preview
            vname: LBL_PREVIEW
            type: button
            params
            	action: Preview
            async: false
            target: _blank
            hidden: bean.type!=out
	sections
		--
			id: main
			elements
                - parent
                --
                	name: folder_ref
                	hidden: mode.new_record
                --
                	name: date_start
                	hidden: mode.new_record
                -
        --
            id: email_form
            widget: EmailWidget
