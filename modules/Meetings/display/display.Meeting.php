<?php return; /* no output */ ?>

list
    field_formatter
        file: include/layout/HighlightedListFieldFormatter.php
        class: HighlightedListFieldFormatter
basic_filters
	view_closed
filters
	view_closed
		default_value: false
		type: flag
		negate_flag: true
		vname: LBL_VIEW_CLOSED_ITEMS
		field: status
		value: Planned
fields
	invitees_list
		vname: LBL_INVITEE
		widget: InviteesList
	resources_list
		vname: LBL_RESOURCES
		widget: ResourcesList
	event_scheduler
		vname: LBL_SCHEDULING_FORM_TITLE
		widget: SchedulerWidget
		reportable: false
    recurring
        widget: RecurringWidget
        reportable: false
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
    is_recurring
        type: bool
        source
            type: function
            value_function: [Meeting, is_recurring]
            fields: [id, recurrence_of_id]
        reportable: false
    can_edit_recurrence
        type: bool
        source
            type: function
            value_function: [Meeting, can_edit_recurrence]
            fields: [id, recurrence_of_id]
        reportable: false
widgets
	SchedulerWidget
		type: section
		path: modules/Meetings/widgets/SchedulerWidget.php
	ResourcesList
		type: field
		path: modules/Meetings/widgets/ResourcesList.php
    MeetingDialogWidget
        type: section
        path: modules/Meetings/scheduler_dialog/MeetingDialogWidget.php
    AcceptStatusWidget
        type: column
        path: modules/Meetings/widgets/AcceptStatusWidget.php
    RecurringWidget
        type: section
        path: modules/Meetings/widgets/RecurringWidget.php
