<?php return; /* no output */ ?>

widgets
    ActivityContactInput
        type: column
        path: modules/Activities/widgets/ActivityContactInput.php
list
	layouts
		Summary
			view_name: Summary
			filter_name: Summary
    field_formatter
        file: include/layout/HighlightedListFieldFormatter.php
        class: HighlightedListFieldFormatter
basic_filters
	- activity_date
fields
    rel_contact
        vname: LBL_CONTACT
		widget: ActivityContactInput
	has_attach
		vname: LBL_HAS_ATTACHMENTS
		type: attach_icon
