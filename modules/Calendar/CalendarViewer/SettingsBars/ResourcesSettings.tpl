<div class="filter_field" id="div_main_settings">
<button type="button" class="input-select input-outer flatter" id="button_target_filter">
<div class="input-arrow select-label">
<div class="input-icon left active {$target_icon}"></div><span id="span_user_filter" class="input-label">{$target_name}</span>
</div>
</button>
</div>

<script type="text/javascript">
var calendar = {$calendar_ctrl}, res_id = "{$target_res_id}", res_name = "{$target_name}";
{literal}
calendar.addControl(new CalResSelect('button_target_filter', {res_id: res_id, res_name: res_name}));
{/literal}
</script>
