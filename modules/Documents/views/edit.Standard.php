<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
    scripts
        --
            file: "modules/Documents/documents.js"
    sections
		--
			id: main
			required_fields
                - section
			elements
				- document_name
				- revision_number
				- department
				- status_id
				- category_id
				- keywords
				- subcategory_id
				-
                --
                    name: related_doc
                    onchange: set_rev_filters(this.form, this.getKey(), true);
				- revision_date
                - related_doc_rev
				- active_date
                -
				- exp_date
		--
			id: info
			elements
                --
                	name: revision_filename
                	colspan: 2
                --
                    name: description
                    colspan: 2
