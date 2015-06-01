<?php return; /* no output */ ?>

detail
    type: edit
    title: LBL_LOCALE_TITLE
    icon: Currencies
layout
	form_buttons
	sections
		--
			id: defaults
			vname: LBL_LOCALE_DEFAULT_SYSTEM_SETTINGS
			columns: 2
			elements
				- locale_defaults_date_format
				- locale_defaults_time_format
				- locale_defaults_language
				- locale_defaults_number_format
				- locale_defaults_name_format
				- locale_defaults_address_format
				- locale_defaults_use_real_names
				- site_timezone
				- locale_defaults_holidays
				- layout_defaults_theme
		--
			id: telephony
			vname: LBL_TELEPHONY_TITLE
			elements
                - telephony_call_logging
				- telephony_integration
				- telephony_format_phones
				- telephony_country
				--
					id: phone_format_type
					section: true
					toggle_display
						name: telephony_format_phones
					elements
						- telephony_local_code
						- telephony_ext
				--
					id: phone_format_options
					section: true
					toggle_display
						name: telephony_country
						value: other
					elements
						- telephony_local_digits
						- telephony_country_code
		--
			id: database
			vname: LBL_LOCALE_DB_COLLATION_TITLE
			elements
				- site_database_primary_collation
