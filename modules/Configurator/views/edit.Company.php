<?php return; /* no output */ ?>

detail
    type: edit
    title: LBL_COMPANY_INFO_TITLE
    icon: CompanyAddress
layout
	form_buttons
    scripts
        --
            file: "modules/Configurator/ConfigSettings.js"
    sections
		--
			id: general
			vname: LBL_GENERAL_INFO_TITLE
			columns: 2
			label_widths: 25%
			widths: 25%
			elements
				- company_business_model
				--
					id: locale_edit
					section: true
					toggle_display
						name: company_business_model
						value: B2C
					elements
						- company_b2c_name_format
				- company_fiscal_year_start
				- company_hours_in_work_day
				- company_timesheet_period
				- company_pdf_paper_size
				--
                    name: company_forecast_period
                    onchange: "notify_forecasts();"
				- company_pdf_font_face
				- company_retain_forecast_periods
				- layout_pdf_footer_link_title				
				- company_retain_history_periods
				- layout_pdf_footer_link_url
				- company_day_begin_hour
				- company_week_start_day
				- company_day_end_hour
				-
		--
			id: service
			vname: LBL_SERVICE_INFO_TITLE
			elements
				- company_case_queue_user_ref
				- company_web_lead_user_ref
				- company_case_number_sequence
				- company_bug_number_sequence
				--
					name: company_std_case_terms
					colspan: 2
				
		--
			id: invoice
			vname: LBL_QUOTE_INVOICE_INFO_TITLE
			elements
				- company_quote_layout
				- company_quote_add_comments
				- company_print_statement_layout
				- company_email_statement_layout
				- company_envelope_window_position
				- company_add_booked_hours
				--
					section: true
					show_descriptions: true
					elements
						--
							name: company_tax_information
							colspan: 2
				- company_tax_shipping
				- company_shipping_tax_code_ref
				- company_approve_quotes
				-
				- company_quote_threshold
				- company_margin_threshold
				- company_approve_booking_Cases
				- company_approve_booking_ProjectTask
				- company_quotes_show_list
				- company_show_pretax_totals
				--
					id: sequences
					section: true
					elements
						- company_quotes_prefix
						- company_quotes_number_sequence
						- company_invoice_prefix
						- company_invoice_number_sequence
						- company_credit_prefix
						- company_credit_number_sequence
						- company_purchase_order_prefix
						- company_purchase_order_sequence
						- company_bill_prefix
						- company_bill_number_sequence
						- company_sales_order_prefix
						- company_sales_order_sequence
						- company_payment_prefix
						- company_payment_number_sequence
						- company_shipping_prefix
						- company_shipping_sequence
						- company_receiving_prefix
						- company_receiving_sequence
						- company_expense_prefix
						- company_expense_number_sequence
				- company_payment_gateway
				--
					id: merchant
					section: true
					toggle_display
						name: company_payment_gateway
					elements
						- company_merchant_currency_ref
						- company_merchant_login
						- company_merchant_password
		--
			id: logos
			vname: LBL_LOGO_SUBFORM
			columns: 1
			show_descriptions: true
			elements
				- site_top_logo_primary
				- company_logo_file
		--
			id: login
			vname: LBL_LOGIN_PAGE_TITLE
			columns: 1
			label_position: top
			elements
				- company_use_custom_login_message
				- company_login_message
		--
			id: terms
			vname: LBL_TERMS_TITLE
			columns: 1
			label_position: top
			elements
				- company_std_quote_terms
				- company_std_invoice_terms
				- company_std_so_terms
				- company_std_po_terms
