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

<form name="form_calendar">
	<input type="hidden" name="module" value="Calendar">
	<input type="hidden" name="action" value="asyncCalendarBody">
	<input type="hidden" name="to_pdf" value="1">
	<input type="hidden" id="view_type" name="view_type" value="team_week">
	<input type="hidden" id="selected_targets" name="selected_targets" value="{$selected_targets}">
	<input type="hidden" id="view_mode" name="view_mode" value="{$MODE}">
	<input type="hidden" id="project_id" name="project_id" value="{$PROJECT_ID}">				
	<input type="hidden" id="target_id" name="target_id" value="{$target_id}">
	<input type="hidden" id="target_type" name="target_type" value="{$target_type}">
	<input type="hidden" id="target_date" name="target_date" value="{$target_date}">
	<input type="hidden" id="target_user_id" name="target_user_id" value="{$target_user_id}">
	<input type="hidden" id="target_team_id" name="target_team_id" value="{$target_team_id}">
	<input type="hidden" id="display_meetings" name="display_meetings" {$display_meetings}>
	<input type="hidden" id="display_calls" name="display_calls" {$display_calls}>
	<input type="hidden" id="display_tasks" name="display_tasks" {$display_tasks}>
	<input type="hidden" id="display_project_tasks" name="display_project_tasks" {$display_project_tasks}>
	<input type="hidden" id="display_events" name="display_events" {$display_events}>
	<input type="hidden" id="display_leave" name="display_leave" {$display_leave}>
	<input type="hidden" id="display_booked_hours" name="display_booked_hours" {$display_booked_hours}>		
	<input type="hidden" id="forDashlet" name="forDashlet" value="{$forDashlet}">
</form>

<table cols="1" border="0" cellpadding="0" cellspacing="0" width="100%">
	<tr>
		<td>
			{$calendar_tab}
			<table style="border-top: 0px none;height:40px;" cols="3" class="tabForm calendarHead" border="0" cellpadding="0" cellspacing="0" width="100%">	
				<tr>
					<td align="left" nowrap="nowrap" width="25%">
										
						<form id="form_select_team" name="form_select_team">
							<img src="themes/{$THEME}/images/Teams.gif" border="0" style="vertical-align:bottom;" />
							<span class="monthHeaderH3">{$APP.LBL_TEAM}</span><select name="sel_target_team" onchange="TeamWeekCalendarCtrl.teamOnChange(this.value)">{$team_options}</select>
						</form>
					</td>
				
					<td valign="middle" align="center" nowrap="nowrap" width="50%">
						<span class="monthHeaderH3" id="target_date_text">{$formated_target_date}</span>
					</td>
					<td valign="middle" align="right" nowrap="nowrap" width="25%">
						<a style="font-weight:bold;border-left:1px solid #CCCCCC;" href="javascript:TeamWeekCalendarCtrl.moveCalendar('{$prev_date}');" class="NextPrevLink">
							&nbsp;<img src="themes/{$THEME}/images/calendar_previous.gif" alt="{$APP.LBL_PREVIOUS_DAY}" title="{$APP.LBL_PREVIOUS_DAY}" align="absmiddle" border="0" height="10" width="6">
						</a>
						&nbsp;
						<a href="javascript:TeamWeekCalendarCtrl.moveCalendar('{$next_date}');" alt="{$MOD.LBL_NEXT_DAY}" class="NextPrevLink">
							<img src="themes/{$THEME}/images/calendar_next.gif" alt="{$APP.LBL_NEXT_DAY}" title="{$APP.LBL_NEXT_DAY}" align="absmiddle" border="0" height="10" width="6">
						</a>
						<a style="font-weight:bold;border-left:1px solid #CCCCCC;" href="javascript:TeamWeekCalendarCtrl.moveCalendar('{$to_day}');" class="NextPrevLink">&nbsp;{$APP.LBL_CALENDAR_TODAY}</a>
												
						<a style="font-weight:bold;border-left:1px solid #CCCCCC;border-right:1px solid #CCCCCC;" href="#" class="NextPrevLink" id="select_day" >&nbsp;{$APP.LNK_SELECT_DATE}&nbsp;</a>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	
	<tr>
		<td valign="top">
			<table cols="8" class="table_team_week" cellpadding="0" cellspacing="0" width="100%"><tbody>
				<tr>
					<td width="10%" valign="top" class="week_header">&nbsp;</td>
{foreach from=$weekdays key=week_index item=weekday}
					<td width="13%" align="center" valign="top" class="{$weekday.headerCssClass}">
						<a href="javascript:TeamDayCalendarCtrl.moveCalendar('{$weekday.date}');">
							<span class="{$weekday.dateCssClass}">{$weekday.date_short}({$weekday.weekday})</span>
							{$weekday.rokuyo}
							{if $weekday.holiday != ""}
								<br /><span class="week_day_holiday">{$weekday.holiday}</span>
							{/if}
						</a>
					</td>
{/foreach}
				</tr>

{* 各ユーザの予定 *}
{foreach from=$activities_every_user_array key=user_id item=activities_every_user}
				<tr>
					<td valign="top" class="week_user">
						<a href="javascript:WeekCalendarCtrl.asyncCalendarBody('{$target_date}', '{$user_id}', 'user', '{$target_team_id}');">{$activities_every_user.user_full_name}</a>
					</td>
	{foreach from=$activities_every_user.activities_of_day_array key=week_index item=activities_of_day}
					<td valign="top" style="z-index:1;" class="{$weekdays.$week_index.activityCssClass}" onmouseover="TeamWeekCalendarCtrl.ov(this);" onmouseout="TeamWeekCalendarCtrl.ot(this);">
			{foreach from=$activities_of_day.activities key=activity_id item=activity}
				{if $activity.isViewAble == true}
						<div ondblclick="MeetingsEditView.show(this,  '{$activity.module}', '{$activity_id}', '{$user_id}');" valign="top" class="{$activity.cssClass}" style="cursor:pointer;">							
							<table cols="2" width="95%" class="{if $activity.is_duplicate==1}week_activity_item_dup{else}week_activity_item{/if}" border="0" cellspacing="0" cellpadding="2">
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
										<div class="input-icon info-icon" id="act_{$activity_id}" onmouseover="return SUGAR.util.getAdditionalDetails('{$activity.module}', '{$activity_id}', 'act_{$activity_id}');" onmouseout="return SUGAR.util.clearAdditionalDetailsCall();"></div>
										{$activity.subject}
										{$activity.recurrenceImgHTML}									
									</td>
								</tr>
							</table>
						</div>
				{else}
						<div valign="top" class="{$activity.cssClass}">							
							<table cols="2" width="95%" class="{if $activity.is_duplicate==1}week_activity_item_dup{else}week_activity_item{/if}" border="0" cellspacing="0" cellpadding="2">
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
						<div style="padding-top:3px;">
							<img src="themes/{$THEME}/images/CreateMeetings.gif" border="0" style="vertical-align:bottom;" onclick="MeetingsEditView.show(this, 'Meetings', '', '{$user_id}', '{$weekdays.$week_index.date}', '{$local_current_hour}', '0');" />
						</div>
					</td>
	{/foreach}
				</tr>
{/foreach}
			</tbody></table>
		</td>		
	<tr>
		<td>
			<div class="calendar_footer">
				<div class="calendar_footer_help">
					&nbsp;
				</div>
			</div>
		</td>
	</tr>
</table>

<input id="print_url" type="hidden" value="{$PRINT_LINK}" >
<script type="text/javascript">
{$init_js}
setPrintLink({if $forDashlet}'dashlet_print_{$dashletId}'{/if});
</script>

