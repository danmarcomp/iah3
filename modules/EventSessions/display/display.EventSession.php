<?php return; /* no output */ ?>

list
hooks
	edit
		--
			class_function: disable_dates
        --
            class_function: init_session
filters
	name
	event_format
		field: event_join.format
	event_name
		field: event_join.name
	session_number
		operator: =
	event_type_id
		operator: =
		options_function: get_search_types
		default_value: ""
		field: event_join.event_type_id
widgets
	EventWidget
		type: section
		path: modules/EventSessions/widgets/EventWidget.php
