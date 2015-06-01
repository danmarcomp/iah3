<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	form_buttons
        print_receipt
        	icon: theme-icon bean-Payment
            vname: LBL_PDF_RECEIPT
            type: button
            widget: PdfButton
			group: print
			hidden: bean.direction!=incoming
			params
				pdf_type: receipt
        print_advice
        	icon: theme-icon bean-Payment
            vname: LBL_PDF_ADVICE
            type: button
            widget: PdfButton
			group: print
			hidden: bean.direction=incoming
			params
				pdf_type: advice
		cc_refund
            vname: LBL_REFUND_BUTTON_LABEL
			perform:
                var form_params = {module: 'Payments', action: 'PopupCardRefund', payment_id: '{RECORD}'};
                var popup = new SUGAR.ui.Dialog('cc_refund', {modal: true, title : SUGAR.language.getString('LBL_CC_TITLE')});
                popup.fetchContent('async.php', {postData : form_params}, null, true);
            hidden: bean.refunded||bean.direction!=incoming||!config.company.payment_gateway||bean.payment_type!=Credit Card
	sections
		--
            id: main
            elements
                - full_number
                - account
                - amount
                - currency
                - payment_type
                - customer_reference
                - payment_date
                - assigned_user
                - direction
                - date_entered
                - total_amount
                - date_modified
				- refunded
				-
                --
                	name: notes
                	colspan: 2
        -- # PDF rendering only
            id: payment_items
            widget: PaymentItemsWidget
    subpanels
        --
        	name: credits
			hidden: bean.direction!=credit
		--
			name: credits
			title: LBL_PAYMENT_CREDITS
			hidden: bean.direction!=incoming
        --
            name: invoices
            hidden: bean.direction!=incoming
        --
            name: bills
            hidden: bean.direction!=outgoing
