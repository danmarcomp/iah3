<?php return; /* no output */ ?>

detail
	type: list
layout
    columns
        --
            field: show_type
            add_fields
                --
                    field: status_color
                    list_position: prefix
            width: 50
		- last_run
		- interval_text
		- show_description

	hooks
		post_title
			file: modules/Scheduler/Hooks.php
			class: ScheduleHooks
			class_function: pre_title

