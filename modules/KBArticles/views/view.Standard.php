<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	sections
		--
			id: main
			elements
                --
                    name: name
                    colspan: 2
                --
                    name: summary
                    colspan: 2
                --
                    name: portal_active
                    colspan: 2
                --
                    name: description
                    colspan: 2

		- kb_tags
    subpanels
		- cases
		- documents
		- notes
