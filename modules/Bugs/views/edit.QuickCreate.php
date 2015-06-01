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
                - priority
                - account
                - status
                - contact
                - type
                - product
                - source
                - product_category
                - resolution
                - found_in
                - fixed_in
                - planned_for
                --
                	name: name
                	colspan: 2
                --
                	name: description
                	colspan: 2
