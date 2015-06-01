<?php return; /* no output */ ?>

detail
	type: list
layout
    columns
        - full_name
        --
        	field: ~join.report_notify_format
        	widget: QuickEditListElt
        --
        	field: ~join.report_notify_enabled
        	widget: QuickEditListElt
