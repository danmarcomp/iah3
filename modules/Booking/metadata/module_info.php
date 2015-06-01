<?php return; /* no output */ ?>

detail
	primary_bean: BookedHours
	submodule_of: Timesheets
	tab_visibility: normal
	default_group: LBL_TABGROUP_PROJECT_MANAGEMENT
acl
	defaults
		view: owner
		approve: disabled
web_actions
    SelectBookingObject
        acl: create
	bookingEditView
		acl: create
