<div class="filter_field" id="div_main_settings">
<button type="button" class="input-select input-outer flatter hard-right" id="button_target_filter">
<div class="input-arrow select-label">
<div class="input-icon left active {$target_icon}"></div><span
	id="span_user_filter" class="input-label">{if $user_name_selected eq 'Me'}{$MOD.LBL_ME}{else}{$user_name_selected}{/if}</span>
</div>
</button
><button type="button" class="input-select input-outer flatter hard-left" id="button_project_filter">
<div class="input-arrow select-label">
<div class="input-icon left active {$project_icon}"></div><span
	id="span_project_filter" class="input-label">{$PROJECT_NAME}</span>
</div>
</button>
</div>

<script type="text/javascript">
var calendar = {$calendar_ctrl}, uid = "{if $target_user_type == 'all'}all{else}{$user_id_selected}{/if}", uname = "{$user_name_selected}",
	pid = "{$project_id_selected}", pname = "{$PROJECT_NAME}";
{literal}
calendar.addControl(new CalUserSelect('button_target_filter', {user_id: uid, user_name: uname, all_users: true}));
calendar.addControl(new CalProjectSelect('button_project_filter', {proj_id: pid, proj_name: pname}));
{/literal}
</script>
