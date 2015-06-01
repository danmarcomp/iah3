<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	sections
		--
			id: main
			required_fields
				- thread_id
			elements
				--
					name: title
					colspan: 2
				- created_by_user
				- date_entered
				-- 
					name: thread
					colspan: 2
				--
					name: description_html
					colspan: 2

