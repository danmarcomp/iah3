<?php

$section_pages = array(
	array(
		'section' => 'LBL_TABGROUP_TODAY_ACTIVITIES',
		'title' => 'LBL_HOME_DASHBOARD',
		'icon' => 'Home',
		'position' => 'start',
		'tab_order' => 1,
		'flat_tab' => true,
		'columns' => array(
			array(
				'width' => '60%',
				'dashlets' => array(
					'Calendar20Dashlet',
					array(
						'className' => 'FeedsDashlet',
						'options' => array(
							'feed_url' => 'http://www.nytimes.com/services/xml/rss/nyt/Business.xml',
							'title' => 'NY Times | Business',
							'feed_title' => 'NY Times | Business',
							'feed_icon' => 'http://www.nytimes.com/favicon.ico',
							'display_rows' => 8,
						),
					),
				),
			),
			array(
				'width' => '40%',
				'dashlets' => array(
					array(
						'className' => 'WeatherDashlet',
						'options' => array(
							'degrees_units' => 'c',
							'show_times' => 1,
						),
					),
					'StockDashlet',
					array(
						'className' => 'JotPadDashlet',
						'options' => array(
							'height' => 250,
						),
					),
				),
			),
		),
	),
	array(
		'section' => 'LBL_TABGROUP_TODAY_ACTIVITIES',
		'title' => 'LBL_ACTION_ITEMS',
		'position' => 'start',
		'tab_order' => 2,
		'flat_tab' => true,
		'columns' => array(
			array(
				'width' => '50%',
				'dashlets' => array(
					array(
						'className' => 'UnreadEmailsDashlet',
						'options' => array(
							'displayRows' => 10,
						),
					),
					'MyTasksDashlet',
					'MyThreadsDashlet',
				),
			),
			array(
				'width' => '50%',
				'dashlets' => array(
					'MyMeetingsDashlet',
					'MyCallsDashlet',
					array(
						'className' => 'MyContactsDashlet',
						'options' => array(
							'displayRows' => 3,
						),
					),
					array(
						'className' => 'MyAccountsDashlet',
						'options' => array(
							'displayRows' => 3,
						),
					),
				),
			),
		),
	),
	array(
		'section' => 'LBL_TABGROUP_SALES_MARKETING',
		'title' => 'LBL_SALES_HOME',
		'icon' => 'Home',
		'position' => 'start',
		'tab_order' => 1,
		'columns' => array(
			array(
				'width' => '60%',
				'dashlets' => array(
					'MyLeadsDashlet',
					'MyOpportunitiesDashlet',
				),
			),
			array(
				'width' => '40%',
				'dashlets' => array(
					'MyPipelineBySalesStageDashlet',
				),
			),
		),
	),
	array(
		'section' => 'LBL_TABGROUP_SALES_MARKETING',
		'title' => 'LBL_SALES_CHARTS',
		'position' => 'end',
		'tab_order' => 1,
		'columns' => array(
			array(
				'dashlets' => array(
					'Chart_fiscal_year_booked',
					'Chart_pipeline_by_month',
					'Chart_pipeline_by_sales_stage',
					'Chart_lead_source_by_outcome',
					'Chart_outcome_by_month',
					'Chart_pipeline_by_lead_source',
				),
			),
		),
	),
	array(
		'section' => 'LBL_TABGROUP_ORDER_MANAGEMENT',
		'title' => 'LBL_FINANCE_HOME',
		'icon' => 'Home',
		'position' => 'start',
		'tab_order' => 1,
		'columns' => array(
			array(
				'width' => '50%',
				'dashlets' => array(
					array(
						'className' => 'MyAccountsDashlet',
						'options' => array(
							'displayRows' => 3,
						),
					),
					'QuotesDashlet',
					'SalesOrdersDashlet',
					'InvoicesDashlet',
					'ShippedItemsDashlet',
				),
			),
			array(
				'width' => '50%',
				'dashlets' => array(
					array(
						'className' => 'MyContactsDashlet',
						'options' => array(
							'displayRows' => 3,
						),
					),
					'OpenPurchaseOrdersDashlet',
					'ReceivedItemsDashlet',
					'UnpaidBillsDashlet',
				),
			),
		),
	),
	array(
		'section' => 'LBL_TABGROUP_ORDER_MANAGEMENT',
		'title' => 'LBL_FINANCE_CHARTS',
		'position' => 'end',
		'tab_order' => 1,
		'columns' => array(
			array(
				'dashlets' => array(
					'Chart_fiscal_year_booked',
					'Invoiced_Sales_Report',
					'Chart_project_revenue_by_month',
				),
			),
		),
	),
	array(
		'section' => 'LBL_TABGROUP_PROJECT_MANAGEMENT',
		'title' => 'LBL_PROJECT_HOME',
		'icon' => 'Home',
		'position' => 'start',
		'tab_order' => 1,
		'columns' => array(
			array(
				'width' => '60%',
				'dashlets' => array(
					'MyActiveProjectsDashlet',
					'MyBookedHoursDashlet',
				),
			),
			array(
				'width' => '40%',
				'dashlets' => array(
					'MyProjectTaskDashlet',
				),
			),
		),
	),
	array(
		'section' => 'LBL_TABGROUP_PROJECT_MANAGEMENT',
		'title' => 'LBL_PROJECT_CHARTS',
		'position' => 'end',
		'tab_order' => 1,
		'columns' => array(
			array(
				'dashlets' => array(
					'Chart_project_revenue_by_month',
				),
			),
		),
	),
	array(
		'section' => 'LBL_TABGROUP_SUPPORT',
		'title' => 'LBL_SUPPORT_HOME',
		'icon' => 'Home',
		'position' => 'start',
		'tab_order' => 1,
		'columns' => array(
			array(
				'width' => '60%',
				'dashlets' => array(
					'MyCasesDashlet',
					'MyBugsDashlet',
				),
			),
			array(
				'width' => '40%',
				'dashlets' => array(
					'MyProjectTaskDashlet',
				),
			),
		),
	),
	array(
		'section' => 'LBL_TABGROUP_SUPPORT',
		'title' => 'LBL_SUPPORT_CHARTS',
		'position' => 'end',
		'tab_order' => 1,
		'columns' => array(
			array(
				'dashlets' => array(
					'Cases_by_Status_by_User',
					'Cases_by_Month_by_Priority',
					'Bugs_by_Status_by_User',
					'Bugs_by_Month_by_Priority',
				),
			),
		),
	),
);

