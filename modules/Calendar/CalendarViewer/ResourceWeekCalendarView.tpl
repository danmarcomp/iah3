{*
/******************************************************************************
* The contents of this file are subject to the CareBrains Software End User
* License Agreement ('License') which can be viewed at
* http://www.sugarforum.jp/download/cbieula.shtml
* By installing or using this file, You have unconditionally agreed to the
* terms and conditions of the License, and You may not use this file except in
* compliance with the License.  Under the terms of the license, You shall not,
* among other things: 1) sublicense, resell, rent, lease, redistribute, assign
* or otherwise transfer Your rights to the Software, and 2) use the Software
* for timesharing or service bureau purposes such as hosting the Software for
* commercial gain and/or for the benefit of a third party.  Use of the Software
* may be subject to applicable fees and any use of the Software without first
* paying applicable fees is strictly prohibited.
* Your Warranty, Limitations of liability and Indemnity are expressly stated
* in the License.  Please refer to the License for the specific language
* governing these rights and limitations under the License.
*****************************************************************************/
*}

<form name="form_calendar" id="form_calendar" method="POST">
	<input type="hidden" name="module" value="Calendar">
	<input type="hidden" name="action" value="asyncCalendarBody">
	<input type="hidden" name="to_pdf" value="1">
	<input type="hidden" id="view_type" name="view_type" value="resource_week">
	<input type="hidden" id="target_date" name="target_date" value="{$target_date}">
	<input type="hidden" id="selected_targets" name="selected_targets" value="{$selected_targets}">
	<input type="hidden" id="target_id" name="target_id" value="{$target_id}">
	<input type="hidden" id="target_type" name="target_type" value="{$target_type}">
	<input type="hidden" id="view_mode" name="view_mode" value="{$MODE}">
	<input type="hidden" id="project_id" name="project_id" value="{$PROJECT_ID}">				
	<input type="hidden" id="target_user_id" name="target_user_id" value="">
	<input type="hidden" id="target_team_id" name="target_team_id" value="">
	<input type="hidden" id="target_res_type" name="target_res_type" value="{$target_res_type}">
	<input type="hidden" id="display_meetings" name="display_meetings" {$display_meetings}>
	<input type="hidden" id="display_calls" name="display_calls" {$display_calls}>
	<input type="hidden" id="display_tasks" name="display_tasks" {$display_tasks}>
	<input type="hidden" id="display_project_tasks" name="display_project_tasks" {$display_project_tasks}>
	<input type="hidden" id="display_events" name="display_events" {$display_events}>
	<input type="hidden" id="display_leave" name="display_leave" {$display_leave}>
	<input type="hidden" id="target_department" name="target_department" value="{$target_department}">
	<input type="hidden" id="forDashlet" name="forDashlet" value="{$forDashlet}">
</form>
<form name="settings_form" id="settings_form" method="POST" action="index.php" onsubmit="return false;">
<table cols="1" border="0" cellpadding="0" cellspacing="0" width="100%">
	<tr>
		<td>
			{$calendar_tab}
			<table style="height:40px;" cols="3" class="tabForm calendarHead" border="0" cellpadding="0" cellspacing="0" width="100%">	
				<tr>
					<td align="left" nowrap="nowrap" width="25%" style="padding-left:0.5em">
					{assign var="calendar_ctrl" value="WeekCalendarCtrl"}
					{include file="modules/Calendar/CalendarViewer/MakerFilter.tpl"}
						{*<form name="form_select_resource"></form>*}
					</td>
				
					<td valign="middle" align="center" nowrap="nowrap" width="50%" style="padding:0 1em">
						<span class="monthHeaderH3" id="target_date_text">
							<button type="button" onclick="ResourceWeekCalendarCtrl.moveCalendar('{$prev_date}');" title="{$APP.LBL_PREVIOUS_WEEK}" class="input-button input-outer nav-button"><div class="input-icon icon-prev"></div></button>
							&nbsp;{$formated_target_date}&nbsp;
							<button type="button" onclick="ResourceWeekCalendarCtrl.moveCalendar('{$next_date}');" title="{$APP.LBL_NEXT_WEEK}" class="input-button input-outer nav-button"><div class="input-icon icon-next"></div></button>
						</span>
					</td>
					<td valign="middle" align="right" nowrap="nowrap" width="25%">
						<a style="font-weight:bold;" href="javascript:ResourceWeekCalendarCtrl.moveCalendar('{$to_day}');" class="NextPrevLink">&nbsp;{$APP.LBL_CALENDAR_TODAY}</a>
						<a style="font-weight:bold;border-left:1px solid #CCCCCC;margin-right:0.5em;" href="#" class="NextPrevLink" id="select_day" >&nbsp;{$APP.LNK_SELECT_DATE}&nbsp;</a>
					</td>
				</tr>
				<tr>
					<td colspan="3" valign="middle" nowrap="nowrap" width="100%" style="padding:0 0.5em">
						{include file="modules/Calendar/CalendarViewer/GridSettingsBar.tpl"}
					</td>
				</tr>				
			</table>
		</td>
	</tr>
	
	<tr>
		<td valign="top">
			<table cols="8" class="calendar_outer_table" cellpadding="0" cellspacing="0" width="100%"><tbody>
				<tr>
					<td width="10%" valign="top" class="week_header_left corner">&nbsp;</td>
{foreach from=$weekdays key=week_index item=weekday}
					<td width="13%" align="center" valign="top" class="week_header{$weekday.dateCssClass}">
						<a href="javascript:ResourceDayCalendarCtrl.moveCalendar('{$weekday.date}');">
							<span class="{$weekdayfonts[$weekday.weekDayIndex]}">{$weekday.date_short} ({$weekday.weekday})</span>
						</a>
							{$weekday.rokuyo}
							{if $weekday.holiday != ""}
								<br /><span class="holiday">{$weekday.holiday}</span>
							{/if}
					</td>
{/foreach}
				</tr>

{* 各ユーザの予定 *}

<tr>
<td colspan="100" class="team_cal_separator">{$MOD.LBL_SECTION_USERS}</td>
</tr>

{foreach from=$activities_every_user_array key=user_id item=activities_every_user}
				<tr>
					<td valign="top" class="week_user" nowrap="nowrap">
						<a href="javascript:WeekCalendarCtrl.asyncCalendarBody('{$target_date}', '{$user_id}', 'user');">{$activities_every_user.user_full_name}</a>
                        &nbsp;<div class="input-icon active-icon icon-delete" onclick="{$calendar_ctrl}.deleteGridEntry('{$user_id}', 'user');"></div>
					</td>
	{foreach from=$activities_every_user.activities_of_day_array key=week_index item=activities_of_day}
					<td valign="top" class="week_activity{$weekdays.$week_index.dateCssClass}" onmouseover="ResourceWeekCalendarCtrl.ov(this);" onmouseout="ResourceWeekCalendarCtrl.ot(this);">
			{foreach from=$activities_of_day.activities item=activity key=activity_id}
				{if $activity.isViewAble == true}
						<div 
						{if $activity.canEdit && $MODE != "projects"}
                            {if $MODE != 'timesheets'}
						        ondblclick="MeetingsEditView.show(this,  '{$activity.module}', '{$activity_id}');"
                            {else}
                                ondblclick="HoursEditView.show(this,  '{$activity_id}', '', '', '', '', '');"
                            {/if}
						{/if}
						onselectstart="return false;" onmousedown="return false;" class="{$activity.cssClass}{if $activity.canEdit} can_edit{/if}">
							<table cols="2" width="95%" class="week_activity_item{if $activity.is_duplicate} dup{/if}" border="0" cellspacing="0" cellpadding="2">
								<tr>
									<td width="16" class="week_activity_item">
										<a href="index.php?module={$activity.module}&amp;action=DetailView&amp;record={$activity_id}" class="week_activity_item">
										{$activity.activityImgHTML}
										</a>
									</td>
									<td class="week_activity_item">
										{$activity.startTime}-{$activity.endTime}
									</td>
								</tr>
								<tr>
									<td colspan="2" class="week_activity_item">
										<div class="input-icon icon-info" id="act_{$activity_id}" onmouseover="return SUGAR.util.getAdditionalDetails('{$activity.module}', '{$activity_id}', 'act_{$activity_id}');" onmouseout="return SUGAR.util.clearAdditionalDetailsCall();"></div>
										{$activity.subject}
										{$activity.recurrenceImgHTML}									
									</td>
								</tr>
							</table>
						</div>
				{else}
						<div valign="top" class="{$activity.cssClass}">							
							<table cols="2" width="95%" class="week_activity_item{if $activity.is_duplicate} dup{/if}" border="0" cellspacing="0" cellpadding="2">
								<tr>
									<td width="16" class="week_activity_item">
										{$activity.activityImgHTML}
									</td>
									<td class="week_activity_item">
										{$activity.startTime}-{$activity.endTime}
									</td>
								</tr>
								<tr>
									<td colspan="2" class="week_activity_item">
										{$activity.subject}
										{$activity.recurrenceImgHTML}									
									</td>
								</tr>								
							</table>
						</div>
				{/if}
			{/foreach}
						{if $canEdit && $MODE != "projects"}
						<div style="padding-top:3px; text-align:right; ">
							<img src="themes/{$THEME}/images/CreateMeetings.gif" border="0" style="vertical-align:bottom;"
                            {if $MODE != "timesheets"}
    							onclick="MeetingsEditView.showNew(this, '{$user_id}', '', '{$weekdays.$week_index.date}', '{$local_current_hour}', '0', '0', '30', 0, '{$user_ids}', '{$resource_ids}');"
                            {else}
                                onclick="HoursEditView.showNew(this, '{$user_id}', '', '{$weekdays.$week_index.date}', '{$local_current_hour}', '0');"
                            {/if}
						/>
						</div>
						{/if}
					</td>
	{/foreach}
				</tr>
{/foreach}

            <tr>
            	<td class="team_cal_add" align="right">
                    <b>{$MOD.LBL_ADD_USER}:&nbsp;</b>
                </td>
                <td colspan="100" class="team_cal_add">&nbsp;{$add_user}</td>
            </tr>

{if $MODE eq "activities"}
<tr>
<td colspan="100" class="team_cal_separator">{$MOD.LBL_SECTION_RESOURCES}</td>
</tr>

{foreach from=$activities_every_resource_array key=resource_id item=activities_every_resource}
				<tr>
					<td valign="top" class="week_user" nowrap="nowrap">
						<a href="javascript:WeekCalendarCtrl.asyncCalendarBody('{$target_date}', '{$resource_id}', 'resource');">{$activities_every_resource.user_full_name}</a>
                        &nbsp;<div class="input-icon active-icon icon-delete" onclick="{$calendar_ctrl}.deleteGridEntry('{$resource_id}', 'resource');"></div>
					</td>
	{foreach from=$activities_every_resource.activities_of_day_array key=week_index item=activities_of_day}
					<td valign="top" class="week_activity{$weekdays.$week_index.dateCssClass}" onmouseover="ResourceWeekCalendarCtrl.ov(this);" onmouseout="ResourceWeekCalendarCtrl.ot(this);">
			{foreach from=$activities_of_day.activities item=activity key=activity_id}
				{if $activity.isViewAble == true}
						<div 
						{if $activity.canEdit}
						ondblclick="MeetingsEditView.show(this,  '{$activity.module}', '{$activity_id}');"
						{/if}
						onselectstart="return false;" onmousedown="return false;" class="{$activity.cssClass}{if $activity.canEdit} can_edit{/if}">
							<table cols="2" width="95%" class="week_activity_item{if $activity.is_duplicate} dup{/if}" border="0" cellspacing="0" cellpadding="2">
								<tr>
									<td width="16" class="week_activity_item">
										<a href="index.php?module={$activity.module}&amp;action=DetailView&amp;record={$activity_id}" class="week_activity_item">
										{$activity.activityImgHTML}
										</a>
									</td>
									<td class="week_activity_item">
										{$activity.startTime}-{$activity.endTime}
									</td>
								</tr>
								<tr>
									<td colspan="2" class="week_activity_item">
										<div class="input-icon icon-info" id="act_{$activity_id}" onmouseover="return SUGAR.util.getAdditionalDetails('{$activity.module}', '{$activity_id}', 'act_{$activity_id}');" onmouseout="return SUGAR.util.clearAdditionalDetailsCall();"></div>
										{$activity.subject}
										{$activity.recurrenceImgHTML}									
									</td>
								</tr>
							</table>
						</div>
				{else}
						<div valign="top" class="{$activity.cssClass}">							
							<table cols="2" width="95%" class="week_activity_item{if $activity.is_duplicate} dup{/if}" border="0" cellspacing="0" cellpadding="2">
								<tr>
									<td width="16" class="week_activity_item">
										{$activity.activityImgHTML}
									</td>
									<td class="week_activity_item">
										{$activity.startTime}-{$activity.endTime}
									</td>
								</tr>
								<tr>
									<td colspan="2" class="week_activity_item">
										{$activity.subject}
										{$activity.recurrenceImgHTML}									
									</td>
								</tr>
							</table>
						</div>
				{/if}
			{/foreach}
						{if $canEdit}
						<div style="padding-top:3px; text-align:right; ">
							<img src="themes/{$THEME}/images/CreateMeetings.gif" border="0" style="vertical-align:bottom;" onclick="MeetingsEditView.showNew(this, '', '{$resource_id}', '{$weekdays.$week_index.date}', '{$local_current_hour}', '0', '0', '30', 0, '{$user_ids}', '{$resource_ids}');" />
						</div>
						{/if}
					</td>
	{/foreach}
				</tr>
{/foreach}

            <tr>
                <td class="team_cal_add" align="right">
	                <b>{$MOD.LBL_ADD_RESOURCE}:&nbsp;</b>
	            </td>
                <td colspan="100" class="team_cal_add">&nbsp;{$add_resource}</td>
            </tr>
{/if}
			</tbody></table>
		</td>
	</tr>
</table>
</form>
<input id="print_url" type="hidden" value="{$PRINT_LINK}" >
{iah_script}
{$init_js}
setPrintLink({if $forDashlet}'dashlet_print_{$dashletId}'{/if});
window.defaultEditModule = '{$defaultEditModule}';
{literal}
function checkUserId() {
	setTimeout('var newId = $("user_id_selected").value; if (newId != "") WeekCalendarCtrl.addGridEntry($("settings_form"), "user");', 200);
}
function checkResourceId() {
	setTimeout('var newId = $("res_id_selected").value; if (newId != "") WeekCalendarCtrl.addGridEntry($("settings_form"), "resource");', 200);
}
{/literal}
{/iah_script}

