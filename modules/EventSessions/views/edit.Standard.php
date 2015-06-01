<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
    form_buttons
        save_next
            vname: LBL_SAVE_NEXT_BUTTON_LABEL
            accesskey: LBL_BOOKING_BUTTON_KEY
            type: button
            onclick: return SUGAR.ui.sendForm(document.forms.DetailForm, {"format":"html","record_perform":"save","next_after_save":1, "no_redirect":1}, null);
            order: 1
            icon: icon-accept
    scripts
        --
            file: "modules/EventSessions/event.js"
	sections
        --
            id: event_data
            widget: EventWidget
		--
            id: session_details
            vname: LBL_SESSION_DETAILS
            elements
                - session_number
                - name
                - assigned_user
                -
                - date_start
                - date_end
                --
                    name: no_date_start
                    onchange: disableDate('start');
                -- 
                    name: no_date_end
                    onchange: disableDate('end');
                - url
                - phone
                - phone_password
                - location
                - location_url
                - location_maplink
                - website
                - tracking_code
                - breakfast
                - lunch
                - dinner
                - refreshments
                - parking
                -
                - speaker1
                - host1
                - speaker2
                - host2
                - speaker3
                - host3
                - speaker4
                - host4
                - speaker5
                - host5
                - attendee_limit
                - attendee_overflow
                - calendar_post
                - calendar_color
                --
                    name: description
                    colspan: 2
