<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE	
layout
	form_buttons
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
    scripts
        --
            file: "modules/KBArticles/tags.js"

