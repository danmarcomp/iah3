<div class="filter_field" id="div_main_settings" style="text-align: right">
	<input type="hidden" name="display_calls" {$display_calls}>
	<input type="hidden" name="display_meetings" {$display_meetings}>
	<input type="hidden" name="display_tasks" {$display_tasks}>
	<button type="button" class="input-checkbox input-outer flatter {$display_calls_checked} hard-right" id="display_calls-input" onclick="{$calendar_ctrl}.toggleFlag(this.form, 'display_calls');">
	<div class="input-icon left"></div><span class="input-label">{$MOD.LBL_SHOW_CALLS}</span>
	</button><button type="button" class="input-checkbox input-outer flatter {$display_meetings_checked} hard-left hard-right" id="display_meetings-input" onclick="{$calendar_ctrl}.toggleFlag(this.form, 'display_meetings');">
	<div class="input-icon left"></div><span class="input-label">{$MOD.LBL_SHOW_MEETINGS}</span>
	</button><button type="button" class="input-checkbox input-outer flatter {$display_tasks_checked} hard-left hard-right" id="display_tasks-input" onclick="{$calendar_ctrl}.toggleFlag(this.form, 'display_tasks');">
	<div class="input-icon left"></div><span class="input-label">{$MOD.LBL_SHOW_TASKS}</span>
	</button><button type="button" class="input-select input-outer flatter hard-left" id="link_more_filter">
		<div class="input-arrow"><span id="span_more_filter" class="input-label">{$MOD.LBL_MORE} {$MORE_COUNT}</span></div>
	</button>
</div>

<div id="div_more_list" style="height:100px;position:absolute;display:none;z-index:9999;">
<table border="0" cellpadding="3" cellspacing="0" class="calendar_filter_form">
	<tr>
		<td align="left">
			<div class="filter_field">
			<label><input type="checkbox" class="checkbox" name="display_project_tasks" id="display_project_tasks-input" onchange="{$calendar_ctrl}.applySettings(this.form);" {$display_project_tasks_sel} value="1">
			{$MOD.LBL_SHOW_PTASKS}</label>
			</div>
		</td>
	</tr>
	<tr>
		<td align="left">
			<div class="filter_field">
			<label><input type="checkbox" class="checkbox" name="display_events" id="display_events-input" onchange="{$calendar_ctrl}.applySettings(this.form);" {$display_events_sel} value="1">
			{$MOD.LBL_SHOW_EVENTS}</label>
			</div>
		</td>
	</tr>
	<tr>
		<td align="left">
			<div class="filter_field">
			<label><input type="checkbox" class="checkbox" name="display_leave" id="display_leave-input" onchange="{$calendar_ctrl}.applySettings(this.form);" {$display_leave_sel} value="1">
			{$MOD.LBL_SHOW_LEAVE}</label>
			</div>
		</td>
	</tr>
</table>
</div>

{iah_script}
{literal}
SUGAR.popups.attachPopup('link_more_filter', 'div_more_list', {require_click: true});
{/literal}
{/iah_script}
