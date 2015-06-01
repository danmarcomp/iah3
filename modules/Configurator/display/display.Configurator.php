<?php return; /* no output */ ?>

fields
	inspector_enabled
		config: inspector.enabled
		vname: LBL_INSPECTOR_ENABLED
		vdesc: LBL_INSPECTOR_ENABLED_DESC
		type: bool
	inspector_limit_ip
		config: inspector.limit_ip
		vname: LBL_INSPECTOR_LIMIT_IP
		vdesc: LBL_INSPECTOR_LIMIT_IP_DESC
		len: 20
	inspector_new_password
		config: inspector.new_password
		vname: LBL_INSPECTOR_NEW_PASSWORD
		vdesc: LBL_INSPECTOR_NEW_PASSWORD_DESC
		type: password
		len: 20
	layout_list_default_display_rows
		config: layout.list.default_display_rows
		vname: LIST_ENTRIES_PER_LISTVIEW
		type: number
		min: 5
		max: 100
	layout_list_subpanel_display_rows
		config: layout.list.subpanel_display_rows
		vname: LIST_ENTRIES_PER_SUBPANEL
		type: number
		min: 2
		max: 100
	layout_lock_subpanels
		config: layout.lock_subpanels
		vname: LOCK_SUBPANELS
		type: bool
	layout_max_dashlets_homepage
		config: layout.max_dashlets_homepage
		vname: MAX_DASHLETS
		type: number
		min: 2
		max: 20
	layout_mobile_columns
		config: layout.mobile_columns
		vname: LBL_MOBILE_COLUMNS
		type: enum
		options_function: get_mobile_columns_options
		options_add_blank: false
	layout_jsmin_enabled
		config: layout.jsmin_enabled
		vname: LBL_ENABLE_JSMIN
		type: bool
	layout_svg_charts_enabled
		config: layout.svg_charts_enabled
		vname: LBL_ENABLE_SVG_CHARTS
		type: bool
	layout_pdf_footer_link_title
		config: layout.pdf.footer_link_title
		vname: LBL_PDF_FOOTER_LINK_TITLE
		width: 30
		type: varchar
	layout_pdf_footer_link_url
		config: layout.pdf.footer_link_url
		vname: LBL_PDF_FOOTER_LINK_URL
		width: 30
		type: url
	layout_scayt_enabled
		config: layout.scayt_enabled
		vname: LBL_SCAYT_ENABLED
		type: bool
	site_security_verify_client_ip
		config: site.security.verify_client_ip
		vname: VERIFY_CLIENT_IP
		type: bool
	site_upload_maxsize
		config: site.upload.maxsize
		vname: UPLOAD_MAX_SIZE
		type: int
		len: 10
	site_log_stack_trace_errors
		config: site.log.stack_trace_errors
		vname: STACK_TRACE_ERRORS
		type: bool
	site_log_file
		config: site.log.file
		vname: LOG_FILE
		type: varchar
		width: 25
	site_log_dir
		config: site.log.dir
		vname: LOG_DIR
		type: varchar
		width: 30
	site_log_level
		config: site.log.level
		vname: LOG_LEVEL
		type: enum
		options: log_level_dom
		required: true
	site_log_file_size
		config: site.log.file_size
		vname: LOG_FILE_SIZE
		type: varchar
		len: 10
	site_log_backup_count
		config: site.log.backup_count
		vname: LOG_BACKUP_COUNT
		type: int
    site_log_clear_activities_interval
        config: site.log.clear_activities_interval
        vname: LOG_CLEAR_ACTIVITY_INTERVAL
        type: enum
        options: log_clear_activities_interval_dom
        options_add_blank: false
    site_performance_calculate_response_time
		config: site.performance.calculate_response_time
		vname: DISPLAY_RESPONSE_TIME
		type: bool
	site_performance_dump_slow_queries
		config: site.performance.dump_slow_queries
		vname: LOG_SLOW_QUERIES
		type: bool
	site_performance_slow_query_time_msec
		config: site.performance.slow_query_time_msec
		vname: SLOW_QUERY_TIME_MSEC
		type: int
	site_performance_log_memory_usage
		config: site.performance.log_memory_usage
		vname: LOG_MEMORY_USAGE
		type: bool
	company_create_invoice_products
		config: company.create_invoice_products
		vname: LBL_ADD_PRODUCTS_FROM_INVOICE
		type: bool
	company_invoice_line_events
		config: company.invoice_line_events
		vname: LBL_INVOICE_LINE_ITEM_EVENTS
		type: bool
	company_invoice_line_serials
		config: company.invoice_line_serials
		vname: LBL_INVOICE_LINE_ITEM_SERIALS
		type: bool
	company_invoice_default_terms
		config: company.invoice_default_terms
		vname: LBL_INVOICE_DEFAULT_TERMS
		type: enum
		options: terms_dom
	company_bill_default_terms
		config: company.bill_default_terms
		vname: LBL_BILL_DEFAULT_TERMS
		type: enum
		options: terms_dom
	company_kb_allow_new_tags
		config: company.kb_allow_new_tags
		vname: LBL_ALLOW_NEW_TAGS
		type: bool
	company_business_model
		config: company.business_model
		vname: LBL_BUSINESS_MODEL
		type: enum
		options: business_model_dom
		required: true
	company_b2c_name_format
		config: company.b2c_name_format
		vname: LBL_ACCOUNT_NAME_FORMAT
		vdesc: LBL_LOCALE_NAME_FORMAT_DESC
		type: varchar
		widget: NameFormatInput
	company_fiscal_year_start
		config: company.fiscal_year_start
		vname: LBL_FISCAL_YEAR_START
		type: enum
		options_function: get_fiscal_year_options
		required: true
	company_hours_in_work_day
		config: company.hours_in_work_day
		vname: LBL_HOURS_IN_WORK_DAY
		type: number
		min: 1
		max: 24
	company_timesheet_period
		config: company.timesheet_period
		vname: LBL_TIMESHEET_PERIOD
		type: enum
		options: timesheet_period_dom
		options_add_blank: false
	company_pdf_paper_size
		config: company.pdf_paper_size
		vname: LBL_PDF_PAPER_SIZE
		type: enum
		options: paper_size_dom
		options_add_blank: false
	company_forecast_period
		config: company.forecast_period
		vname: LBL_FORECAST_PERIOD
		type: enum
		options: forecast_period_dom
		options_add_blank: false
	company_pdf_font_face
		config: company.pdf_font_face
		vname: LBL_PDF_FONT_FACE
		type: enum
		options: pdf_font_names_dom
		options_add_blank: false
	company_retain_forecast_periods
		config: company.retain_forecast_periods
		vname: LBL_RETAIN_FORECAST_PERIODS
		type: number
		min: 0
		max: 100
	company_retain_history_periods
		config: company.retain_history_periods
		vname: LBL_RETAIN_HISTORY_PERIODS
		type: number
		min: 0
		max: 100
	company_week_start_day
		config: company.week_start_day
		vname: LBL_DEFAULT_WEEK_START_DAY
		type: enum
		options_function: get_week_day_options
		options_add_blank: false
	company_day_begin_hour
		config: company.day_begin_hour
		vname: LBL_DAY_BEGIN_HOUR
		type: enum
		options_function: get_hour_options
		options_add_blank: false
	company_day_end_hour
		config: company.day_end_hour
		vname: LBL_DAY_END_HOUR
		type: enum
		options_function: get_hour_options
		options_add_blank: false
	company_case_queue_user
		config: company.case_queue_user
		vname: LBL_QUEUE_USER
		type: ref_id
	company_case_queue_user_ref
		vname: LBL_QUEUE_USER
		type: ref
		bean_name: User
		id_name: company_case_queue_user
	company_web_lead_user
		config: company.web_lead_user
		vname: LBL_LEAD_USER
		type: ref_id
	company_web_lead_user_ref
		vname: LBL_LEAD_USER
		type: ref
		bean_name: User
		id_name: company_web_lead_user
	company_case_number_sequence
		config: company.case_number_sequence
		vname: LBL_CASE_START_FROM
		min: 1
		type: int
		disable_num_format: true
	company_bug_number_sequence
		config: company.bug_number_sequence
		vname: LBL_BUG_START_FROM
		min: 1
		type: int
		disable_num_format: true
	company_std_case_terms
		config: company.std_case_terms
		vname: LBL_STD_CASE_TERMS
		type: text
	company_quote_layout
		config: company.quote_layout
		vname: LBL_QUOTE_LAYOUT
		type: enum
		options_function: get_quote_layout_options
		options_add_blank: false
	company_quote_add_comments
		config: company.quote_add_comments
		vname: LBL_QUOTE_ADD_COMMENT_LINES
		type: enum
		options: quote_add_comment_options
		options_add_blank: false
	company_print_statement_layout
		config: company.print_statement_layout
		vname: LBL_PRINT_STATEMENT_LAYOUT
		type: enum
		options_function: get_quote_layout_options
		options_add_blank: false
	company_email_statement_layout
		config: company.email_statement_layout
		vname: LBL_EMAIL_STATEMENT_LAYOUT
		type: enum
		options_function: get_quote_layout_options
		options_add_blank: false
	company_envelope_window_position
		config: company.envelope_window_position
		vname: LBL_ENVELOPE_WINDOW_POSITION
		type: enum
		options: envelope_window_position_dom
		options_add_blank: false
	company_add_booked_hours
		config: company.add_booked_hours
		vname: LBL_QUOTE_ADD_BOOKED_HOURS_COMMENT_LINES
		type: bool
	company_tax_information
		config: company.tax_information
		vname: LBL_TAX_INFORMATION
		vdesc: LBL_TAX_INFORMATION_DESC
		type: varchar
		width: 35
		len: 150
	company_approve_quotes
		config: company.approve_quotes
		vname: LBL_APPROVE_QUOTES
		type: bool
	company_tax_shipping
		config: company.tax_shipping
		vname: LBL_TAX_SHIPPING
		type: bool
	company_shipping_tax_code
		config: company.shipping_tax_code
		vname: LBL_SHIPPING_TAX_CODE
		type: ref_id
	company_shipping_tax_code_ref
		vname: LBL_SHIPPING_TAX_CODE
		type: ref
		bean_name: TaxCode
		id_name: company_shipping_tax_code
	company_approve_booking_Cases
		config: company.approve_booking_Cases
		vname: LBL_APPROVE_BOOKING_CASES
		type: bool
	company_approve_booking_ProjectTask
		config: company.approve_booking_ProjectTask
		vname: LBL_APPROVE_BOOKING_PROJECTS
		type: bool
	company_quote_threshold
		config: company.quote_threshold
		vname: LBL_QUOTE_APPROVAL_THRESHOLD
		type: base_currency
	company_margin_threshold
		config: company.margin_threshold
		vname: LBL_MARGIN_THRESHOLD
		type: float
		min: 0
	company_quotes_show_list
		config: company.quotes_show_list
		vname: LBL_QUOTES_SHOW_LIST
		type: bool
	company_show_pretax_totals
		config: company.show_pretax_totals
		vname: LBL_SHOW_PRETAX_TOTALS
		type: bool
	company_quotes_prefix
		config: company.quotes_prefix
		vname: LBL_QUOTE_PREFIX
		type: varchar
	company_quotes_number_sequence
		config: company.quotes_number_sequence
		vname: LBL_QUOTE_START_FROM
		type: int
		min: 1
		disable_num_format: true
	company_invoice_prefix
		config: company.invoice_prefix
		vname: LBL_INVOICE_PREFIX
		type: varchar
	company_invoice_number_sequence
		config: company.invoice_number_sequence
		vname: LBL_INVOICE_START_FROM
		type: int
		min: 1
		disable_num_format: true
	company_credit_prefix
		config: company.credit_prefix
		vname: LBL_CREDIT_PREFIX
		type: varchar
	company_credit_number_sequence
		config: company.credit_number_sequence
		vname: LBL_CREDIT_START_FROM
		type: int
		min: 1
		disable_num_format: true
	company_purchase_order_prefix
		config: company.purchase_order_prefix
		vname: LBL_PURCHASE_ORDER_PREFIX
		type: varchar
	company_purchase_order_sequence
		config: company.purchase_order_sequence
		vname: LBL_PURCHASE_ORDER_START_FROM
		type: int
		min: 1
		disable_num_format: true
	company_bill_prefix
		config: company.bill_prefix
		vname: LBL_BILL_PREFIX
		type: varchar
	company_bill_number_sequence
		config: company.bill_number_sequence
		vname: LBL_BILL_START_FROM
		type: int
		min: 1
		disable_num_format: true
	company_sales_order_prefix
		config: company.sales_order_prefix
		vname: LBL_SALES_ORDER_PREFIX
		type: varchar
	company_sales_order_sequence
		config: company.sales_order_sequence
		vname: LBL_SALES_ORDER_START_FROM
		type: int
		min: 1
		disable_num_format: true
	company_payment_prefix
		config: company.payment_prefix
		vname: LBL_PAYMENT_PREFIX
		type: varchar
	company_payment_number_sequence
		config: company.payment_number_sequence
		vname: LBL_PAYMENT_START_FROM
		type: int
		min: 1
		disable_num_format: true
	company_shipping_prefix
		config: company.shipping_prefix
		vname: LBL_SHIPPING_PREFIX
		type: varchar
	company_shipping_sequence
		config: company.shipping_sequence
		vname: LBL_SHIPPING_START_FROM
		type: int
		min: 1
		disable_num_format: true
	company_receiving_prefix
		config: company.receiving_prefix
		vname: LBL_RECEIVING_PREFIX
		type: varchar
	company_receiving_sequence
		config: company.receiving_sequence
		vname: LBL_RECEIVING_START_FROM
		type: int
		min: 1
		disable_num_format: true
	company_expense_prefix
		config: company.expense_prefix
		vname: LBL_EXPENSE_PREFIX
		type: varchar
	company_expense_number_sequence
		config: company.expense_number_sequence
		vname: LBL_EXPENSE_START_FROM
		type: int
		min: 1
		disable_num_format: true
	company_payment_gateway
		config: company.payment_gateway
		vname: LBL_PAYMENT_GATEWAY
		type: enum
		options_function: get_payment_gateway_options
	company_merchant_currency_ref
		vname: LBL_MERCHANT_CURRENCY
		type: ref
		bean_name: Currency
		id_name: company_merchant_currency
	company_merchant_currency
		config: company.merchant_currency
		vname: LBL_MERCHANT_CURRENCY
		type: ref_id
	company_merchant_login
		config: company.merchant_login
		vname: LBL_MERCHANT_LOGIN
		type: varchar
	company_merchant_password
		config: company.merchant_password
		vname: LBL_MERCHANT_PASSWORD
		type: password
	company_use_custom_login_message
		config: company.use_custom_login_message
		vname: LBL_USE_CUSTOM_LOGIN_MESSAGE
		type: bool
	company_login_message
		config: company.login_message
		vname: LBL_CUSTOM_LOGIN_MESSAGE
		type: html
		height: 8
	company_std_quote_terms
		config: company.std_quote_terms
		vname: LBL_STD_QUOTE_TERMS
		type: text
	company_std_invoice_terms
		config: company.std_invoice_terms
		vname: LBL_STD_INVOICE_TERMS
		type: text
	company_std_so_terms
		config: company.std_so_terms
		vname: LBL_STD_SO_TERMS
		type: text
	company_std_po_terms
		config: company.std_po_terms
		vname: LBL_STD_PO_TERMS
		type: text
	company_google_maps_key
		config: company.google_maps_key
		vname: LBL_GMAPS_KEY
		type: varchar
		width: 25
	company_logo_file
		config: company.logo_file
		vname: LBL_QUOTE_LOGO
		vdesc: LBL_QUOTE_LOGO_DESC
		type: image_ref
		upload_dir: {FILES}/images/logos/
		show_reset: true
	site_top_logo_primary
		config: site.top_logo.primary
		auto_height_config: site.top_logo.auto_height
		vname: LBL_THEME_LOGO
		vdesc: LBL_THEME_LOGO_DESC
		type: image_ref
		upload_dir: {FILES}/images/logos/
		show_reset: true
	export_enabled
		config: export.enabled
		vname: EXPORT_ENABLED
		type: bool
	export_charset
		config: export.charset
		vname: EXPORT_CHARSET
		type: enum
		options_function: get_export_charset_options
		options_add_blank: false
	export_admin_only
		config: export.admin_only
		vname: ADMIN_EXPORT_ONLY
		type: bool
	export_list_format
		config: export.list_format
		vname: EXPORT_LIST_FORMAT
		type: enum
		options_function: get_export_format_options
		options_add_blank: false
	ldap_enabled
		config: ldap.enabled
		vname: LBL_LDAP_ENABLE
		vdesc: LBL_LDAP_DESC
		type: bool
	ldap_host_name
		config: ldap.host_name
		vname: LBL_LDAP_SERVER_HOSTNAME
		vdesc: LBL_LDAP_SERVER_HOSTNAME_DESC
		type: varchar
		width: 25
	ldap_port
		config: ldap.port
		vname: LBL_LDAP_SERVER_PORT
		vdesc: LBL_LDAP_SERVER_PORT_DESC
		len: 6
	ldap_base_dn
		config: ldap.base_dn
		vname: LBL_LDAP_BASE_DN
		vdesc: LBL_LDAP_BASE_DN_DESC
		type: varchar
		width: 25
	ldap_bind_attr
		config: ldap.bind_attr
		vname: LBL_LDAP_BIND_ATTRIBUTE
		vdesc: LBL_LDAP_BIND_ATTRIBUTE_DESC
		type: varchar
		width: 15
	ldap_login_attr
		config: ldap.login_attr
		vname: LBL_LDAP_LOGIN_ATTRIBUTE
		vdesc: LBL_LDAP_LOGIN_ATTRIBUTE_DESC
		type: varchar
		width: 15
	ldap_admin_user
		config: ldap.admin_user
		vname: LBL_LDAP_ADMIN_USER
		vdesc: LBL_LDAP_ADMIN_USER_DESC
		type: varchar
		width: 20
	ldap_admin_password
		config: ldap.admin_password
		vname: LBL_LDAP_ADMIN_PASSWORD
		type: password
		width: 20
	ldap_auto_create_users
		config: ldap.auto_create_users
		vname: LBL_LDAP_AUTO_CREATE_USERS
		vdesc: LBL_LDAP_AUTO_CREATE_USERS_DESC
		type: bool
	ldap_enc_key
		config: ldap.enc_key
		vname: LBL_LDAP_ENC_KEY
		vdesc: LBL_LDAP_ENC_KEY_DESC
		type: varchar
		len: 20
	portal_enabled
		config: portal.enabled
		vname: LBL_PORTAL_ON
		vdesc: LBL_PORTAL_ON_DESC
		type: bool
	portal_conversion_email
		config: portal.conversion_email
		vname: LBL_PORTAL_CONVERSION_EMAIL
		vdesc: LBL_PORTAL_CONVERSION_EMAIL_DESC
		type: bool
	portal_url
		config: portal.url
		vname: LBL_PORTAL_URL
		vdesc: LBL_PORTAL_URL_DESC
		type: url
		width: 40
	portal_perm_lead_quotes
		config: portal.perm_lead_quotes
		type: bool
		vname: LBL_PORTAL_PERM_QUOTES
	portal_perm_lead_bugs
		config: portal.perm_lead_bugs
		type: bool
		vname: LBL_PORTAL_PERM_BUGS
	portal_perm_lead_cases
		config: portal.perm_lead_cases
		type: bool
		vname: LBL_PORTAL_PERM_CASES
	portal_perm_lead_projects
		config: portal.perm_lead_projects
		type: bool
		vname: LBL_PORTAL_PERM_PROJECTS
	portal_perm_lead_events
		config: portal.perm_lead_events
		type: bool
		vname: LBL_PORTAL_PERM_EVENTS
	portal_perm_lead_kb
		config: portal.perm_lead_kb
		type: bool
		vname: LBL_PORTAL_PERM_KB
	portal_perm_lead_faq
		config: portal.perm_lead_faq
		type: bool
		vname: LBL_PORTAL_PERM_FAQ
	portal_perm_lead_forum
		config: portal.perm_lead_forum
		type: bool
		vname: LBL_PORTAL_PERM_FORUM
	portal_perm_lead_contactus
		config: portal.perm_lead_contactus
		type: bool
		vname: LBL_PORTAL_PERM_CONTACTUS
	portal_perm_lead_mycontact
		config: portal.perm_lead_mycontact
		type: bool
		vname: LBL_PORTAL_PERM_MYCONTACT
	portal_perm_lead_password
		config: portal.perm_lead_password
		type: bool
		vname: LBL_PORTAL_PERM_PASSWORD
	portal_perm_lead_request
		config: portal.perm_lead_request
		type: bool
		vname: LBL_PORTAL_PERM_REQUEST
	portal_perm_lead_subscribe
		config: portal.perm_lead_subscribe
		type: bool
		vname: LBL_PORTAL_PERM_SUBSCRIBE
	portal_perm_contact_quotes
		config: portal.perm_contact_quotes
		type: bool
		vname: LBL_PORTAL_PERM_QUOTES
	portal_perm_contact_bugs
		config: portal.perm_contact_bugs
		type: bool
		vname: LBL_PORTAL_PERM_BUGS
	portal_perm_contact_cases
		config: portal.perm_contact_cases
		type: bool
		vname: LBL_PORTAL_PERM_CASES
	portal_perm_contact_projects
		config: portal.perm_contact_projects
		type: bool
		vname: LBL_PORTAL_PERM_PROJECTS
	portal_perm_contact_events
		config: portal.perm_contact_events
		type: bool
		vname: LBL_PORTAL_PERM_EVENTS
	portal_perm_contact_kb
		config: portal.perm_contact_kb
		type: bool
		vname: LBL_PORTAL_PERM_KB
	portal_perm_contact_faq
		config: portal.perm_contact_faq
		type: bool
		vname: LBL_PORTAL_PERM_FAQ
	portal_perm_contact_forum
		config: portal.perm_contact_forum
		type: bool
		vname: LBL_PORTAL_PERM_FORUM
	portal_perm_contact_contactus
		config: portal.perm_contact_contactus
		type: bool
		vname: LBL_PORTAL_PERM_CONTACTUS
	portal_perm_contact_mycontact
		config: portal.perm_contact_mycontact
		type: bool
		vname: LBL_PORTAL_PERM_MYCONTACT
	portal_perm_contact_password
		config: portal.perm_contact_password
		type: bool
		vname: LBL_PORTAL_PERM_PASSWORD
	portal_perm_contact_request
		config: portal.perm_contact_request
		type: bool
		vname: LBL_PORTAL_PERM_REQUEST
	portal_perm_contact_subscribe
		config: portal.perm_contact_subscribe
		type: bool
		vname: LBL_PORTAL_PERM_SUBSCRIBE
	portal_perm_unreg_kb
		config: portal.perm_unreg_kb
		type: bool
		vname: LBL_PORTAL_PERM_KB
	portal_perm_unreg_faq
		config: portal.perm_unreg_faq
		type: bool
		vname: LBL_PORTAL_PERM_FAQ
	portal_perm_unreg_forum
		config: portal.perm_unreg_forum
		type: bool
		vname: LBL_PORTAL_PERM_FORUM
	portal_perm_unreg_contactus
		config: portal.perm_unreg_contactus
		type: bool
		vname: LBL_PORTAL_PERM_CONTACTUS
	portal_perm_unreg_mycontact
		config: portal.perm_unreg_mycontact
		type: bool
		vname: LBL_PORTAL_PERM_MYCONTACT
	portal_perm_unreg_request
		config: portal.perm_unreg_request
		type: bool
		vname: LBL_PORTAL_PERM_REQUEST
	portal_perm_unreg_register
		config: portal.perm_unreg_register
		type: bool
		vname: LBL_PORTAL_PERM_REGISTER

	
	proxy_enabled
		config: proxy.enabled
		vname: LBL_PROXY_ON
		vdesc: LBL_PROXY_ON_DESC
		type: bool
	proxy_host_name
		config: proxy.host_name
		vname: LBL_PROXY_HOST
		type: varchar
		width: 25
	proxy_port
		config: proxy.port
		vname: LBL_PROXY_PORT
	proxy_auth_req
		config: proxy.auth_req
		vname: LBL_PROXY_AUTH
		type: bool
	proxy_user_name
		config: proxy.user_name
		vname: LBL_PROXY_USERNAME
		type: varchar
	proxy_password
		config: proxy.password
		vname: LBL_PROXY_PASSWORD
		type: password
	locale_defaults_date_format
		config: locale.defaults.date_format
		vname: LBL_LOCALE_DEFAULT_DATE_FORMAT
		type: enum
		options_function: get_locale_date_options
		required: true
	locale_defaults_time_format
		config: locale.defaults.time_format
		vname: LBL_LOCALE_DEFAULT_TIME_FORMAT
		type: enum
		options_function: get_locale_time_options
		required: true
	locale_defaults_language
		config: locale.defaults.language
		vname: LBL_LOCALE_DEFAULT_LANGUAGE
		type: enum
		options_function: get_locale_language_options
		required: true
	locale_defaults_number_format
		config: locale.defaults.number_format
		vname: LBL_LOCALE_DEFAULT_NUMBER_FORMAT
		type: enum
		options_function: get_locale_number_options
		required: true
	locale_defaults_name_format
		config: locale.defaults.name_format
		vname: LBL_LOCALE_DEFAULT_NAME_FORMAT
		type: varchar
		widget: NameFormatInput
		required: true
	locale_defaults_address_format
		config: locale.defaults.address_format
		vname: LBL_LOCALE_DEFAULT_ADDRESS_FORMAT
		widget: AddressFormatInput
		required: true
	locale_defaults_use_real_names
		config: locale.defaults.use_real_names
		vname: LBL_USE_REAL_NAMES
		type: bool
	site_timezone
		config: site.timezone
		vname: LBL_SERVER_TIMEZONE
		type: enum
		options_function: get_locale_timezone_options
		required: true
	site_database_primary_collation
		config: site.database.primary_collation
		vname: LBL_LOCALE_DB_COLLATION
		type: enum
		options_function: get_database_collation_options
		required: true
	locale_defaults_holidays
		config: locale.defaults.holidays
		vname: LBL_HOLIDAYS
		type: enum
		options_function: get_locale_holidays_options
	layout_defaults_theme
		config: layout.defaults.theme
		vname: DEFAULT_THEME
		type: enum
		options_function: get_theme_options
		required: true
    telephony_call_logging
        config: telephony.call_logging
        vname: LBL_CALL_LOGGING
        type: bool
	telephony_integration
		config: telephony.integration
		vname: LBL_TELEPHONY_INTEGRATION
		vdesc: LBL_TELEPHONY_INTEGRATION_DESC
        type: enum
        options: telephony_integration_dom
        options_add_blank: false
	telephony_format_phones
		config: telephony.format_phones
		vname: LBL_FORMAT_PHONES
		type: bool
	telephony_country
		config: telephony.country
		vname: LBL_PHONE_RULES
		type: enum
		options_function: get_locale_phone_country_options
	telephony_local_code
		config: telephony.local_code
		vname: LBL_LOCAL_AREA_CODE
		type: varchar
		len: 7
	telephony_ext
		config: telephony.ext
		vname: LBL_EXT_STRING
		type: varchar
		len: 5
	telephony_local_digits
		config: telephony.local_digits
		vname: LBL_LOCAL_PHONE_DIGITS
		type: varchar
		len: 5
	telephony_country_code
		config: telephony.country_code
		vname: LBL_COUNTRY_CODE
		type: varchar
		size: 7

	aclconfig_additive
		config: aclconfig.additive
		vname: LBL_TEAMS_ADDITIVE
		vdesc: LBL_TEAMS_ADDITIVE_DESC
		type: bool
	aclconfig_user_role_precedence
		config: aclconfig.user_role_precedence
		vname: LBL_TEAMS_USER_ROLE_PRECEDENCE
		vdesc: LBL_TEAMS_USER_ROLE_PRECEDENCE_DESC
		type: bool
	aclconfig_inherit_creator
		config: aclconfig.inherit_creator
		vname: LBL_TEAMS_INHERIT_CREATOR
		vdesc: LBL_TEAMS_INHERIT_CREATOR_DESC
		type: bool
	aclconfig_inherit_assigned
		config: aclconfig.inherit_assigned
		vname: LBL_TEAMS_INHERIT_ASSIGNED
		vdesc: LBL_TEAMS_INHERIT_ASSIGNED_DESC
		type: bool
	#aclconfig_inherit_parent
	#aclconfig_user_popup
	#aclconfig_popup_select

	notify_enabled
		config: notify.enabled
		vname: LBL_NOTIFY_ON
		vname_module: EmailMan
		vdesc: LBL_NOTIFICATION_ON_DESC
		type: bool
	notify_from_name
		config: notify.from_name
		vname: LBL_NOTIFY_FROMNAME
		vname_module: EmailMan
		type: varchar
		len: 25
	notify_from_address
		config: notify.from_address
		vname: LBL_NOTIFY_FROMADDRESS
		vname_module: EmailMan
		type: email
		len: 35
	notify_send_by_default
		config: notify.send_by_default
		vname: LBL_NOTIFY_SEND_BY_DEFAULT
		vname_module: EmailMan
		type: bool
	notify_send_from_assigning_user
		config: notify.send_from_assigning_user
		vname: LBL_NOTIFY_SEND_FROM_ASSIGNING_USER
		vname_module: EmailMan
		type: bool
	notify_send_invites_by_default
		config: notify.send_invites_by_default
		vname: LBL_NOTIFY_SEND_INVITES_BY_DEFAULT
		type: bool
	notify_cases_user_create
		config: notify.cases_user_create
		vname: LBL_NOTIFY_CASES_USER_CREATE
		vname_module: EmailMan
		type: bool
	notify_cases_user_update
		config: notify.cases_user_update
		vname: LBL_NOTIFY_CASES_USER_UPDATE
		vname_module: EmailMan
		type: bool
	notify_cases_contact_create
		config: notify.cases_contact_create
		vname: LBL_NOTIFY_CASES_CONTACT_CREATE
		vname_module: EmailMan
		type: bool
	notify_cases_contact_update
		config: notify.cases_contact_update
		vname: LBL_NOTIFY_CASES_CONTACT_UPDATE
		vname_module: EmailMan
		type: bool
	email_send_type
		config: email.send_type
		vname: LBL_MAIL_SENDTYPE
		vname_module: EmailMan
		type: enum
		options: notifymail_sendtype
		required: true
	email_smtp_server
		config: email.smtp_server
		vname: LBL_MAIL_SMTPSERVER
		vname_module: EmailMan
		type: varchar
		len: 40
	email_smtp_port
		config: email.smtp_port
		vname: LBL_MAIL_SMTPPORT
		vname_module: EmailMan
		type: port
		len: 5
	email_smtp_auth_req
		config: email.smtp_auth_req
		vname: LBL_MAIL_SMTPAUTH_REQ
		vname_module: EmailMan
		type: bool
	email_smtp_user
		config: email.smtp_user
		vname: LBL_MAIL_SMTPUSER
		vname_module: EmailMan
		type: varchar
		len: 50
	email_smtp_password
		config: email.smtp_password
		vname: LBL_MAIL_SMTPPASS
		vname_module: EmailMan
		type: password
		len: 50
	email_default_client
		config: email.default_client
		vname: LBL_EMAIL_DEFAULT_EDITOR
		vname_module: EmailMan
		type: enum
		options: dom_email_link_type
	email_default_editor
		config: email.default_editor
		vname: LBL_EMAIL_DEFAULT_CLIENT
		vname_module: EmailMan
		type: enum
		options: dom_email_editor_option
	email_default_charset
		config: email.default_charset
		vname: LBL_EMAIL_DEFAULT_CHARSET
		vname_module: EmailMan
		type: enum
		options_function: get_export_charset_options
		options_add_blank: false
	massemailer_campaign_emails_per_run
		config: massemailer.campaign_emails_per_run
		vname: LBL_EMAILS_PER_RUN
		vname_module: EmailMan
		type: enum
		options_function: get_email_outbound_max_messages_options
		options_add_blank: false
	#massemailer_tracking_entities_location_type
	#	vname: LBL_LOCATION_TRACK
	email_relate_inbound
		config: email.relate_inbound
		vname: LBL_RELATE_INBOUND
		vname_module: EmailMan
		type: bool
	email_relate_skipdomains
		config: email.relate_skipdomains
		vname: LBL_RELATE_SKIPDOMAINS
		vname_module: EmailMan
		type: varchar
		len: 50
	email_import_max_session_messages
		config: email.import.max_session_messages
		vname: LBL_MAX_SESSION_MESSAGES
		vname_module: EmailMan
		type: enum
		options_function: get_email_inbound_max_messages_options
		options_add_blank: false
	email_keep_raw
		config: email.keep_raw
		vname: LBL_KEEP_RAW_EMAIL
		vdesc: LBL_KEEP_RAW
		vname_module: EmailMan
		type: bool
	backup_enabled
		config: backup.enabled
		vname: LBL_BACKUP_ENABLED
		vdesc: LBL_BACKUP_ENABLED_DESC
		type: bool
	backup_dump_enabled
		config: backup.dump_enabled
		vname: LBL_BACKUP_DUMP_ENABLED
		vdesc: LBL_BACKUP_DUMP_ENABLED_DESC
		type: bool
	backup_dump_available
		vname: LBL_BACKUP_DUMP_AVAILABLE
		vdesc: LBL_BACKUP_DUMP_AVAILABLE_DESC
		type: bool
		editable: false
    backup_target_dir
        config: site.paths.backup_dir
        vname: LBL_BACKUP_DIRECTORY
        type: varchar
        len: 40
	backup_mysqldump_path
		config: backup.mysqldump_path
		vname: LBL_BACKUP_MYSQLDUMP_PATH
		type: varchar
		len: 40
	backup_zip_files_filter
		config: backup.zip_files_filter
		vname: LBL_BACKUP_ZIP_FILES_FILTER
		type: enum
		options: backup_filter_options_dom
		options_add_blank: false
		width: 60
	backup_rsync_enabled
		config: backup.rsync_enabled
		vname: LBL_BACKUP_RSYNC_ENABLED
		vdesc: LBL_BACKUP_RSYNC_ENABLED_DESC
		type: bool
	backup_rsync_available
		config: backup.rsync_available
		vname: LBL_BACKUP_RSYNC_AVAILABLE
		vdesc: LBL_BACKUP_RSYNC_AVAILABLE_DESC
		type: bool		
		editable: false
	backup_rsync_path
		config: backup.rsync_path
		vname: LBL_BACKUP_RSYNC_PATH
		type: varchar
		len: 40
	backup_rsync_target_dir
		config: backup.rsync_target_dir
		vname: LBL_BACKUP_RSYNC_TARGET_DIR
		vdesc: LBL_BACKUP_RSYNC_TARGET_DIR_DESC
		type: varchar
		len: 40
