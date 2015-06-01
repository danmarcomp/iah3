<?php return; /* no output */ ?>

detail
	type: editview
	title: LBL_MODULE_TITLE
layout
	sections
		--
			id: main
			elements
				- forum
				- is_sticky
				--
					name: title
					colspan: 2
					width: 80
				--
					name: description_html
					colspan: 2
	scripts
		--
			file: "modules/Threads/EditView.js"