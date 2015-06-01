<div class="filter_field" id="div_main_settings">

<div style="float:left;">
<button type="button" class="input-select input-outer flatter" id="button_target_filter">
<div class="input-arrow select-label">
<div class="input-icon left active {$target_icon}"></div><span id="span_user_filter" class="input-label">{if $user_name_selected eq 'Me'}{$MOD.LBL_ME}{else}{$user_name_selected}{/if}</span>
</div>
</button>
</div>

	<input type="hidden" name="display_calls" {$display_calls}>
	<input type="hidden" name="display_meetings" {$display_meetings}>
	<input type="hidden" name="display_tasks" {$display_tasks}>
	<input type="hidden" name="display_project_tasks" {$display_project_tasks}>
	<input type="hidden" name="display_events" {$display_events}>
	<input type="hidden" name="display_leave" {$display_leave}>
	<table class="nav-button-group flatter" cellpadding="0" cellspacing="0" border="0" style="float: right"><tr><td class="first">
	<button type="button" class="input-checkbox input-outer flatter {$display_calls_checked}" id="display_calls-input" onclick="{$calendar_ctrl}.toggleFlag(this.form, 'display_calls');">
		<div class="input-icon left"></div><span class="input-label">{$MOD.LBL_SHOW_CALLS}</span>
	</button></td>
	<td class="mid"><button type="button" class="input-checkbox input-outer flatter {$display_meetings_checked}" id="display_meetings-input" onclick="{$calendar_ctrl}.toggleFlag(this.form, 'display_meetings');">
		<div class="input-icon left"></div><span class="input-label">{$MOD.LBL_SHOW_MEETINGS}</span>
	</button></td>
	<td class="mid"><button type="button" class="input-checkbox input-outer flatter {$display_tasks_checked}" id="display_tasks-input" onclick="{$calendar_ctrl}.toggleFlag(this.form, 'display_tasks');">
		<div class="input-icon left"></div><span class="input-label">{$MOD.LBL_SHOW_TASKS}</span>
	</button></td>
	<td class="last"><button type="button" class="input-select input-outer flatter" id="button_more_filter">
		<div class="input-arrow select-label"><span id="span_more_filter" class="input-label">{$MOD.LBL_MORE} {$MORE_COUNT}</span></div>
	</button></td>
	</tr></table>
</div>

{iah_script}
var calendar = {$calendar_ctrl}, uid = "{$user_id_selected}", uname = "{$user_name_selected}";
{literal}
calendar.addControl(new CalUserSelect('button_target_filter', {user_id: uid, user_name: uname}));
var m = new SUGAR.ui.MenuSource('button_more_filter', {icon_key: 'icon', options: {/literal}{$MORE_OPTIONS}{literal}});
m.onchange = function(key) { var form = document.forms[CalendarCtrl.form_name]; form[key].value = form[key].value ? 0 : 1; {/literal}{$calendar_ctrl}{literal}.applySettings(form); }
calendar.addControl(m);
{/literal}
{/iah_script}
