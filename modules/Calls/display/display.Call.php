<?php return; /* no output */ ?>

list
    field_formatter
        file: include/layout/HighlightedListFieldFormatter.php
        class: HighlightedListFieldFormatter
hooks
	view
		--
			class_function: set_phone_number
			required_fields: [phone_number]
basic_filters
	view_closed
filters
	view_closed
		default_value: false
		type: flag
		negate_flag: true
		vname: LBL_VIEW_CLOSED_ITEMS
		field: status
        operator: not_eq
        value
            - Held
            - Not Held
fields
	invitees_list
		vname: LBL_INVITEE
		widget: InviteesList
	event_scheduler
		vname: LBL_SCHEDULING_FORM_TITLE
		widget: SchedulerWidget
	any_contact_id
		vname: LBL_ANY_CONTACT_ID
		type: id
		source
			type: field
			field: contacts~join.contact_id
			subselect_filter: true
		reportable: false
    any_user_id
        vname: LBL_INVITED
        widget: InviteesFilter
        reportable: false
    close_activity
		widget: CloseActivityInput
    accept_status
        vname: LBL_ACCEPT_THIS
        widget: AcceptStatusWidget
widgets
	CallButton
		type: form_button
		path: modules/Calls/widgets/CallButton.php
