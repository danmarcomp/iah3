<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
	sections
		--
			elements
                - first_name
                    # need to add salutation
                - last_name
                - title
                - department
                - primary_address
                -
                - phone_work
                - phone_mobile
                - phone_home
                - phone_fax
                - phone_other
                -
                - email1
                - email2
                - lead_source
                --
                    name: description
                    colspan: 2
        --
            id: related_records
            vname: LBL_RELATED_RECORDS
            widget: RelatedRecordsWidget

