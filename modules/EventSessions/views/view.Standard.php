<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
    scripts
        --
            file: "modules/EventSessions/event.js"
	sections
		--
			id: main
			elements
				- event_name
                - event_num_sessions
                - event_type
                - event_format
                - event_product
                - event_assigned_user
				--
					name: event_description
					colspan: 2
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
    subpanels
        --
        	name: accounts
        	hidden: ! mode.B2C
        --
        	name: contacts
			hidden: mode.B2C
        - leads
        - eventreminders
        - invoice
        - securitygroups
