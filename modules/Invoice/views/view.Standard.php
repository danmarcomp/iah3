<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	form_buttons
        print_invoice
        	icon: theme-icon bean-Invoice
            vname: LBL_PDF_INVOICE
            type: button
            widget: PdfButton
            group: print
        print_statement
        	icon: theme-icon bean-Invoice
            vname: LBL_PDF_STATEMENT
            type: button
            widget: PdfButton
            group: print
            params
            	pdf_type: statement
        credit
        	icon: theme-icon bean-CreditNote
            vname: LBL_CREDIT_BUTTON_LABEL
            accesskey: LBL_CREDIT_BUTTON_KEY
            url: ?module=CreditNotes&action=EditView&invoice_id={RECORD}&{RETURN}&return_panel=creditnotes
            group: convert
        cc
            vname: LBL_CC_BUTTON_LABEL
			perform:
                var form_params = {module: 'Invoice', action: 'PopupCardPayment', invoice_id: '{RECORD}'};
                var popup = new SUGAR.ui.Dialog('cc_payment', {modal: true, title : SUGAR.language.getString('LBL_CC_TITLE')});
                popup.fetchContent('async.php', {postData : form_params}, null, true);
            hidden: ! config.company.payment_gateway
        ship
            vname: LBL_SHIP_BUTTON_LABEL
            accesskey: LBL_SHIP_KEY
            url: ?module=Shipping&action=EditView&invoice_id={RECORD}&{RETURN}&return_panel=shipping
        create_product
        	icon: theme-icon create-Asset
            vname: LBL_PRODUCTS_BUTTON_LABEL
            perform:
                var form_params = {module: 'Quotes', action: 'PopupCreateProducts', invoice_id: '{RECORD}'};
                var popup = new SUGAR.ui.Dialog('support_product', {modal: true, title : SUGAR.language.getString('LBL_PRODUCTS_BUTTON_LABEL')});
                popup.fetchContent('async.php', {postData : form_params}, null, true);
            group: convert
        create_payment
        	icon: theme-icon create-Payment
        	vname: LNK_NEW_PAYMENT
        	url: ?module=Payments&action=EditView&invoice_id={RECORD}&{RETURN}&return_panel=payments
        	group: convert
	sections
		--
			id: main
			elements
				- name
                - opportunity
                - full_number
				- cancelled
				- event
				- shipping_stage
				- from_quote
				- partner
				- from_so
				- terms
				- purchase_order_num
                - invoice_date
                - assigned_user
				- due_date
				- amount_due
				- date_modified
				- billing_address
				- shipping_address
                - tax_information
                - show_components
				--
					name: description
					colspan: 2
		- line_items
    subpanels
        - activities
        - history
        - payments
        - creditnotes
        - shipping
        - projects
        - monthlyservices
        - cases
        - securitygroups
