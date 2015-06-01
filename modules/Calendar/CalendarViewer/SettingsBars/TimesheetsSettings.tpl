<div class="filter_field" id="div_main_settings">

<button type="button" class="input-select input-outer flatter" id="button_target_filter">
<div class="input-arrow select-label">
<div class="input-icon left active {$target_icon}"></div
	><span id="span_user_filter" class="input-label">{if $user_name_selected eq 'Me'}{$MOD.LBL_ME}{else}{$user_name_selected}{/if}</span>
</div>
</button>

{if $TIMESHEETS_NUM > 0}
	<button type="button" class="input-select input-outer flatter" id="button_timesheet_filter">
	<div class="input-arrow select-label">
	<div class="input-icon left active {$timesheet_icon}"></div
	><span id="span_timesheet_filter" class="input-label">{$TIMESHEET_SELECTED}</span>
	</div>
	</button>
{elseif $user_name_selected eq 'Me'}
	<a class="NextPrevLink" href='index.php?module=Timesheets&action=EditView&date={$target_date}' style="font-weight:bold; margin-left: 1em">
	<div class="input-icon theme-icon create-Timesheet"></div>&nbsp;{$MOD.LNK_CREATE_TIMESHEET}
	</a>
{/if}

{if $timesheet_id != null && $TIMESHEET_STATUS == 'draft'}
    <span style="padding-left: 15px;">
        <input type="button" class="button" value="{$MOD.LBL_SUBMIT_BUTTON_LABEL}" onclick="{$calendar_ctrl}.submitTimesheet('{$timesheet_id}', '{$MOD.MSG_SUBMIT_CONFIRM}');">&nbsp;
    </span>
{elseif $timesheet_id != null && $TIMESHEET_STATUS == 'approve_reject'}
    <span style="padding-left: 15px;">
        <input type="button" class="button" value="{$MOD.LBL_APPROVE_BUTTON_LABEL}" onclick="{$calendar_ctrl}.approveHours('{$timesheet_id}', 'approve', '{$MOD.MSG_APPROVE_CONFIRM}');">&nbsp;
        <input type="button" class="button" value="{$MOD.LBL_REJECT_BUTTON_LABEL}" onclick="{$calendar_ctrl}.approveHours('{$timesheet_id}', 'reject', '{$MOD.MSG_REJECT_CONFIRM}');">&nbsp;
    </span>
{/if}

</div>

<div id="div_select_timesheet" style="width:450px;height:100px;position:absolute;display:none;z-index:9999;">
<table border="0" cellpadding="3" cellspacing="0" class="calendar_filter_form">
    {foreach from=$TIMESHEETS item=timesheet}
        <tr>
            <td>
                <div class="filter_field">
                    <label><input type="radio" class="radio" name="select_timesheets" onclick="{$calendar_ctrl}.applyTimesheet(this.form);" value="{$timesheet.id}" {if $timesheet_id == $timesheet.id}checked="checked"{/if}>&nbsp;{$timesheet.name}</label>
                </div>
            </td>
        </tr>
	{/foreach}
</table>
</div>

<script type="text/javascript">
var calendar = {$calendar_ctrl}, uid = "{$user_id_selected}", uname = "{$user_name_selected}";
{literal}
calendar.addControl(new CalUserSelect('button_target_filter', {user_id: uid, user_name: uname}));
SUGAR.popups.attachPopup('button_timesheet_filter', 'div_select_timesheet', {require_click: true});
{/literal}
</script>
