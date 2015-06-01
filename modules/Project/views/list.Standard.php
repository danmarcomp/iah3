<?php return; /* no output */ ?>

detail
	type: list
layout
    columns
        --
            field: name
            add_fields
                --
                    field: led_color
                    list_position: prefix
                    exportable: false
        - account
        - project_phase
        - amount
        --
            field: this_month.expected_revenue
            dynamic_label
                class: Project
                function: this_month_label
            label_nowrap: true
        --
            field: next_month.expected_revenue
            dynamic_label
                class: Project
                function: next_month_label
            label_nowrap: true
        - assigned_user
