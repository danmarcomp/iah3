<?php return; /* no output */ ?>

widgets
	ScheduleActivityButton
		type: form_button
		path: modules/Activities/widgets/ScheduleActivityButton.php
	ShowActivitiesButton
		type: form_button
		path: modules/Activities/widgets/ShowActivitiesButton.php
list
	layouts
		Summary
			vname: LBL_VIEW_SUMMARY
			view_name: Summary
			filter_name: Summary
			hidden: true
    field_formatter
        file: include/layout/HighlightedListFieldFormatter.php
        class: HighlightedListFieldFormatter
fields
	close_activity
		widget: CloseActivityInput
