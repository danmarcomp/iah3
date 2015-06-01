<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
	sections
		--
			id: main
			elements
				- document_ref
				- document_revision
				- revision
				-
				--
					name: filename
					colspan: 2
				--
					name: change_log
					colspan: 2
