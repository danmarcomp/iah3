<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
	scripts
		--
			file: modules/Bugs/bugs.js
	form_hooks
		oninit: "BugsForm.init({FORM})"
	sections
		--
			elements
                - bug_number
                - 
                - priority
                - account
                - type
                - contact
                - status
                - is_private
                - resolution
                - product
                - source
                - product_category
                - found_in
                - fixed_in
                - planned_for
                - assigned_user
                --
                	name: name
                	colspan: 2
                	width: 80
                --
                    name: description
                    colspan: 2
                    width: 80
                    height: 12
                --
                    name: work_log
                    colspan: 2
                    width: 80
					height: 12
