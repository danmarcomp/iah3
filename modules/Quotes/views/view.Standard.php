<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	form_buttons
        convert_so
        	icon: theme-icon create-SalesOrder
			vname: LBL_CREATE_SALES_ORDER
			url: ?module=SalesOrders&action=EditView&related_quote_id={RECORD}&return_panel=salesorders
			group: convert
			hidden
				type: hook
				hook
					file: modules/Quotes/Quote.php
					class: Quote
					class_function: needsApproval
		convert_inv
        	icon: theme-icon create-Invoice
			vname: LBL_CREATE_INVOICE
			url: ?module=Invoice&action=EditView&quote_id={RECORD}&return_panel=invoice
			group: convert
			hidden
				type: hook
				hook
					file: modules/Quotes/Quote.php
					class: Quote
					class_function: needsApproval
        convert_product
            icon: theme-icon create-Asset
            vname: LBL_CREATE_PRODUCTS
            perform:
                var form_params = {module: 'Quotes', action: 'PopupCreateProducts', quote_id: '{RECORD}'};
                var popup = new SUGAR.ui.Dialog('support_product', {modal: true, title : SUGAR.language.getString('LBL_CREATE_PRODUCTS')});
                popup.fetchContent('async.php', {postData : form_params}, null, true);
            group: convert
			hidden
				type: hook
				hook
					file: modules/Quotes/Quote.php
					class: Quote
					class_function: needsApproval
        convert_opp
            icon: theme-icon create-Opportunity
            vname: LBL_CREATE_OPPORTUNITY
            url: ?module=Opportunities&action=EditView&quote_id={RECORD}&return_module=Quotes&return_action=DetailView&return_record={RECORD}
            group: convert
        print_quote
        	icon: theme-icon bean-Quote
            vname: LBL_PDF_QUOTE
            type: button
            widget: PdfButton
            group: print
			hidden
				type: hook
				hook
					file: modules/Quotes/Quote.php
					class: Quote
					class_function: needsApproval
        print_proforma
        	icon: theme-icon bean-Invoice
            vname: LBL_PDF_PROFORMA
            type: button
            widget: PdfButton
            group: print
            params
            	pdf_type: proforma
			hidden
				type: hook
				hook
					file: modules/Quotes/Quote.php
					class: Quote
					class_function: needsApproval
        submit
            vname: LBL_SUBMIT_BUTTON_LABEL
            type: button
			module: Quotes
			perform : return SUGAR.ui.sendForm(document.forms.DetailForm, {action: 'Submit'}, null);
			hidden
				type: hook
				hook
					file: modules/Quotes/Quote.php
					class: Quote
					class_function: submitEnabled
	sections
		--
			id: main
			required_fields
				- amount_usdollar
			elements
				- name
				- opportunity
				- full_number
				- quote_stage
				- purchase_order_num
				- valid_until
				- budgetary_quote
				- partner
				- sales_order
				-
				- assigned_user
				- date_modified
				- terms
				- date_entered
				- billing_address
				- shipping_address
				--
					name: description
					colspan: 2

		--
			id: approval
			hidden
				type: config
				name: company.approve_quotes
				op: not
			elements
				- approval
				- approved_user
				--
					name: approval_problem
					colspan: 2
		- line_items
		--
			id: pdf_print
			vname: LBL_PDF_OUTPUT_OPTIONS
			elements
				- show_components
				- show_list_prices
    subpanels
        - activities
        - history
        - salesorders
        - invoice
        - securitygroups
