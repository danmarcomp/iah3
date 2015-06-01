<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
    form_buttons
        escalate
            vname: LBL_ESCALATE_BUTTON_TITLE
            type: button
            widget: EscalateButton
            perform: "showSlider('escalate');"
        booking
            type: button
            widget: BookingButton
            order: 55
        kb
            vname: LBL_KB_BUTTON_TITLE
            type: button
            order: 60
            perform: "addToKB(this.form);"
        print_wo
        	icon: theme-icon bean-aCase
            vname: LBL_PRINT_WO_BUTTON_LABEL
            type: button
            widget: PdfButton
            group: print
    scripts
        --
            file: "modules/Cases/cases.js"
        --
            file: "include/javascript/slider.js"
        --
            file: "modules/Booking/booking_dialog/bookingEditView.js"
	sections
		--
			id: main
			require_fields
                - effort_actual
                - effort_actual_unit
                - travel_time_unit
			elements    
                - case_number
                - 
                - priority
                - account
                - status
                - cust_contact
                - category
                - cust_phone_no
                - type
                - cust_req_no
                - contract
                - date_billed
                - asset
                - effort_actual_display
                - asset_serial_no
                - travel_time
                - vendor_rma_no
                - arrival_time
                - vendor_svcreq_no
                - activity_time
                - date_closed
                - date_modified
                - assigned_user
                - date_entered
        --
        	id: text
        	elements
                --
                    name: name
                    colspan: 2
                --
                    name: description
                    colspan: 2
                --
                    name: resolution
                    colspan: 2
        - required_skills
    subpanels
        - kb_articles
        - contacts
        - accounts
        - activities
        - history
        - bugs
        - documents
        - booked_hours
        - prepaid_amounts
        - invoices
        - threads
		products
			title: LBL_PRODUCTS_SUBPANEL_TITLE
        - expensereports
        - securitygroups
