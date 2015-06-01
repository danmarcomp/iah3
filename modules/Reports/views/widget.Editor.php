detail
	type: configform
layout
	form_buttons
		save
			icon: icon-accept
			vname: LBL_SAVE_BUTTON_LABEL
			onclick: sListView.saveLayout(this.form);
			type: submit
		cancel
			icon: icon-cancel
			vname: LBL_CANCEL_BUTTON_LABEL
			onclick: sListView.cancelEditLayout(this.form.list_id.value);
	tabs
		--
			name: general
			label: LBL_LAYOUT_GENERAL
			icon: icon-sources
		--
			name: filters
			label: LBL_LAYOUT_FILTERS
			icon: icon-filter
		--
			name: columns
			label: LBL_COLUMN_LAYOUT
			icon: icon-layout
		--
			name: sorting
			label: LBL_LAYOUT_SORTING
			icon: icon-sortlist
		--
			name: chart
			label: LBL_CHART_LAYOUT
			icon: theme-icon module-Dashboard
	sections
		--
			id: layout-general
			tab: general
			label_position: top
			columns: 2
			label_widths: [0%]
			widths: [50%]
			elements
				--
					name: name
					colspan: 2
                --
                    name: assigned_user
                    acl: admin
                --
                    name: shared_with
                    widget: SharedWithInput
                --
                	id: teams_panel
                	type: section
					toggle_display
						name: shared_with
						value: teams
                	elements
						--
							name: teams
							vname: LBL_SECURITYGROUPS
							widget: DynamicListInput
							class: listLayoutConfig
							renderer_class: SUGAR.ui.RelatedListRender
							onupdate: onUpdateRelated
							show_handles: false
							max_depth: 0
							colspan: 2
							cols
								--
									name: module
									label: ""
									width: 5%
									format: icon
								--
									name: _display
									label: LBL_NAME
									width: 95%
									render_method: renderName
						--
							name: add_team
							vname: LBL_ADD_TEAM
							widget: RelatedSelectInput
							list_name: teams
							module: SecurityGroups
							width: 14
							colspan: 2
				--
					name: description
					colspan: 2
				--
					name: run_method
				--
					name: export_ids
				--
					id: schedule_info
					section: true
					toggle_display
						name: run_method
						value: scheduled
						onchange: SUGAR.ui.PopupManager.reposition();			
					elements
						- next_run
						- run_interval
				--
					name: sources
					vname: LBL_SOURCES
					widget: DynamicListInput
					class: listLayoutConfig
					renderer_class: SUGAR.ui.SourceListRender
					onupdate: onUpdateSources
					require_group_attrib: true
					show_handles: false
					depth_column: label
					max_depth: 0
					colspan: 2
					cols
						--
							name: icon
							label: ""
							width: 5%
							format: icon
						--
							name: label
							label: LBL_NAME
							width: 60%
							render_method: renderName
						--
							name: display
							label: LBL_DISPLAY
							width: 30%
							render_method: renderDisplay
						--
							name: required
							label: LBL_REQUIRED
							render_method: renderRequired
							width: 5%
							format: check
				--
					name: add_source
					vname: LBL_ADD_SOURCES
					widget: FieldSelectInput
					list_name: sources
					mode: sources
					width: 14
					colspan: 2
		--
			id: layout-filters
			tab: filters
			label_position: top
			columns: 1
			elements
				--
					name: filters
					vname: LBL_LAYOUT_FILTERS
					widget: DynamicListInput
					class: listLayoutConfig
					renderer_class: SUGAR.ui.FilterListRender
					require_group_attrib: true
					max_depth: 3
					cols
						--
							name: label
							label: LBL_FIELD
							width: 40%
							render_method: renderName
						# array('name: type', 'label: Type', 'width: 30%', 'render_method: renderType')
						--
							name: filter
							label: LBL_DEFAULT_VALUE
							width: 60%
							render_method: renderFilter
						--
							name: hidden
							label: LBL_HIDE
							width: 5%
							format: check
				--
					name: add_filter
					vname: LBL_ADD_FILTERS
					widget: FieldSelectInput
					list_name: filters
					mode: filters	
		--
			id: layout-columns
			tab: columns
			label_position: top
			columns: 2
			widths: [60%, 40%]
			elements
				--
					name: columns
					colspan: 2
					vname: LBL_COLUMN_LAYOUT
					widget: DynamicListInput
					class: listLayoutConfig
					renderer_class: SUGAR.ui.ListLayoutRender
					show_action: true
					onupdate: onUpdateFields
					cols
						--
							name: label
							label: LBL_NAME
							width: 40%
							render_method: renderName
						--
							name: type
							label: LBL_TYPE
							width: 40%
							render_method: renderType
						--
							name: width
							label: LBL_WIDTH
							width: 15%
							render_method: renderWidth
						--
							name: format
							label: LBL_FORMAT
							width: 15%
							render_method: renderFormat
						--
							name: sort
							label: LBL_SORT
							width: 10%
							render_method: renderSort
						--
							name: hidden
							label: LBL_HIDE
							width: 5%
							format: check	
				--
					name: add_column
					vname: LBL_ADD_COLUMNS
					widget: FieldSelectInput
					list_name: columns
				--
					name: add_total
					vname: LBL_ADD_TOTALS
					widget: FieldSelectInput
					list_name: columns
					mode: totals
		--
			id: layout-sorting
			tab: sorting
			label_position: top
			columns: 2
			widths: [60%, 40%]
			elements
				--
					name: sort_order
					vname: LBL_SORT_ORDER
					widget: SortOrderInput
					list_name: columns
					colspan: 2
				--
					name: add_sorted
					vname: LBL_ADD_SORTED
					widget: FieldSelectInput
					list_name: sort_order
					mode: order
		--
			id: layout-chart
			tab: chart
			label_position: top
			columns: 2
			elements
				--
					id: chart_type_panel
					section: true
					elements
						--
							name: chart_type
							colspan: 2
							onchange: onUpdateType
				--
					id: chart_options_panel
					section: true
					toggle_display
						name: chart_type
						onchange: SUGAR.ui.PopupManager.reposition();
					elements
						--
							name: chart_title
							colspan: 2
						--
							name: chart_series
							colspan: 2
						--
							name: chart_rollover
							colspan: 2
						--
							name: chart_description
							colspan: 2
						- chart_stacked
						- chart_3d
						- chart_exploded
						- chart_percent
						
