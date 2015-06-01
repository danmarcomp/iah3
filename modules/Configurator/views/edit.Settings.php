<?php return; /* no output */ ?>

detail
    type: edit
    title: LBL_CONFIGURE_SETTINGS_TITLE
    icon: Administration
layout
	form_buttons
	sections
		--
			id: default
			vname: DEFAULT_SYSTEM_SETTINGS
			columns: 2
			label_widths: 25%
			widths: 25%
			elements
				- layout_list_default_display_rows
				- layout_max_dashlets_homepage
				- layout_list_subpanel_display_rows
				- site_performance_calculate_response_time
				- layout_mobile_columns
		--
			id: ldap
			vname: LBL_LDAP_TITLE
			columns: 1
			show_descriptions: true
			elements
				- ldap_enabled
				--
					id: ldap_body
					section: true
					toggle_display
						name: ldap_enabled
					elements
						- ldap_host_name
						- ldap_port
						- ldap_base_dn
						- ldap_bind_attr
						- ldap_login_attr
						- ldap_admin_user
						- ldap_admin_password
						- ldap_auto_create_users
						- ldap_enc_key
		--
			id: proxy
			vname: LBL_PROXY_TITLE
			show_descriptions: true
			columns: 1
			elements
				- proxy_enabled
				--
					id: proxy_body
					section: true
					toggle_display
						name: proxy_enabled
					elements
						- proxy_host_name
						- proxy_port
						- proxy_auth_req
						- proxy_user_name
						- proxy_password
		--
			id: portal
			vname: LBL_PORTAL_TITLE
			columns: 1
			label_widths: 30%
			widths: 70%
			elements
				- portal_enabled
				- portal_conversion_email
				- portal_url
		--
			id: portal_unreg
			vname: LBL_PORTAL_PERMISSIONS_UNREG
			columns: 1
			label_widths: 30%
			widths: 70%
			elements
				- portal_perm_unreg_request
				- portal_perm_unreg_contactus
				- portal_perm_unreg_register
				- portal_perm_unreg_faq
				- portal_perm_unreg_forum
		--
			id: portal_leads
			vname: LBL_PORTAL_PERMISSIONS_LEADS
			columns: 1
			label_widths: 30%
			widths: 70%
			elements
				- portal_perm_lead_quotes
				- portal_perm_lead_bugs
				- portal_perm_lead_cases
				- portal_perm_lead_projects
				- portal_perm_lead_events
				- portal_perm_lead_kb
				- portal_perm_lead_faq
				- portal_perm_lead_forum
				- portal_perm_lead_contactus
				- portal_perm_lead_mycontact
				- portal_perm_lead_password
				- portal_perm_lead_request
				- portal_perm_lead_subscribe
		--
			id: portal_contacts
			vname: LBL_PORTAL_PERMISSIONS_CONTACTS
			columns: 1
			label_widths: 30%
			widths: 70%
			elements
				- portal_perm_contact_quotes
				- portal_perm_contact_bugs
				- portal_perm_contact_cases
				- portal_perm_contact_projects
				- portal_perm_contact_events
				- portal_perm_contact_kb
				- portal_perm_contact_faq
				- portal_perm_contact_forum
				- portal_perm_contact_contactus
				- portal_perm_contact_mycontact
				- portal_perm_contact_password
				- portal_perm_contact_request
				- portal_perm_contact_subscribe

		--
			id: export
			vname: EXPORT
			elements
				- export_enabled
				- export_charset
				- export_admin_only
				- export_list_format
		--
			id: invoices
			vname: INVOICES
			columns: 1
			label_widths: 30%
			widths: 70%
			elements
				- company_create_invoice_products
				- company_invoice_line_events
				- company_invoice_line_serials
				- company_invoice_default_terms
		--
			id: bills
			vname: BILLS
			columns: 1
			label_widths: 30%
			widths: 70%
			elements
				- company_bill_default_terms
		--
			id: kb
			vname: KB
			columns: 1
			label_widths: 30%
			widths: 70%
			elements
				- company_kb_allow_new_tags
		--
			id: advanced
			vname: ADVANCED
			label_widths: 30%
			widths: 20%
			elements
				- layout_jsmin_enabled
				- site_security_verify_client_ip
				- layout_svg_charts_enabled
				- site_upload_maxsize
				- layout_scayt_enabled
		--
			id: logging
			vname: LOGGING
			elements
				- site_log_file
				- site_log_level
				- site_log_file_size
				- site_log_backup_count
				- site_performance_dump_slow_queries
				- site_performance_slow_query_time_msec
				- site_performance_log_memory_usage
				- site_log_stack_trace_errors
                - site_log_clear_activities_interval
                -
		--
			id: inspector
			vname: LBL_INSPECTOR_TITLE
			columns: 1
			show_descriptions: true
			elements
				- inspector_enabled
				- inspector_new_password
				- inspector_limit_ip
