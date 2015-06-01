<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	sections
		--
            elements
                - code
                - name
                - status
                - position
                - taxation_scheme
                -
                --
                    name: description
                    colspan: 2
	subpanels
		--
			name: taxrates
			hidden: "bean.taxation_scheme!=list"
