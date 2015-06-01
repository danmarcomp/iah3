<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	buttons_hook
		class: Email
		file: modules/Emails/Email.php
		class_function: mutateDetailButtons
	
    form_buttons
    	duplicate
    		group: tools
        send
            vname: LBL_SEND_BUTTON_LABEL
            accesskey: LBL_SEND_BUTTON_KEY
            icon: icon-send
            params
            	type: out
            	action: SendDraft
            hidden: bean.status=archived||bean.status=received
        reply
            vname: LBL_REPLY_BUTTON
            accesskey: LBL_REPLY_BUTTON_KEY
            url: ?module=Emails&action=EditView&reply_to={RECORD}&{RETURN}
            hidden: bean.status=draft
        reply_all
            vname: LBL_REPLY_ALL_BUTTON
            accesskey: LBL_REPLY_ALL_BUTTON_KEY
            url: ?module=Emails&action=EditView&reply_to={RECORD}&reply_all=1&{RETURN}
            hidden: bean.status=draft
        forward
            vname: LBL_FORWARD_BUTTON
            accesskey: LBL_FORWARD_BUTTON_KEY
            url: ?module=Emails&action=EditView&forward={RECORD}&{RETURN}
        mark_unread
            vname: LBL_MARK_UNREAD_BUTTON
            accesskey: LBL_UNREAD_BUTTON_KEY
            params
            	action: MarkUnread
            hidden: bean.type=out||bean.status=draft
	sections
		--
			id: main
			required_fields
                - folder_ref.reserved
			elements
                - parent
                - date_start
                - in_reply_to
                - date_modified
                - status
                - date_entered
                - assigned_user
                -
        --
            id: email_form
            widget: EmailWidget
    subpanels
        - contacts
        - users
        - related_emails
        - opportunities
        - cases
        - bugs
        - accounts
        - leads
        - tasks
        - securitygroups
