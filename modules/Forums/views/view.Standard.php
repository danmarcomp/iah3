<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	sections
		--
			id: main
			elements
				- title
				- created_by_user
				- category
				- date_entered
				- category.list_order
				- date_modified
				--
					name: description
					colspan: 2
	subpanels
		--
			name: threads
			default_order_by: is_sticky
			default_sort_order: DESC

