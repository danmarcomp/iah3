<?php return; /* no output */ ?>

detail
	type: editview
	title: LBL_MODULE_TITLE
layout
	sections
		--
			id: main
			elements
				- parent
				- assigned_user
				- contact
				- portal_flag
		--
			id: info
			elements
				--
					name: name
					colspan: 2
					required: true
				--
					name: filename
					colspan: 2
				--
					name: description
					colspan: 2
					height: 15
