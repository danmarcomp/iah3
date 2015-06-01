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
	<input type="hidden" id="view_type" name="view_type" value="day">
	<input type="hidden" id="target_date" name="target_date" value="{$target_date}">
	<input name="target_res_type" type="hidden" value="">

	<input type="hidden" id="view_mode" name="view_mode" value="{$MODE}">	
	<input type="hidden" id="target_type" name="target_type" value="{$target_type}">
	<input type="hidden" id="target_id" name="target_id" value="{$target_id}">
	<input type="hidden" id="selected_targets" name="selected_targets" value="{$selected_targets}">
	<input type="hidden" id="project_id" name="project_id" value="{$PROJECT_ID}">	
	<input type="hidden" id="timesheet_id" name="timesheet_id" value="{$timesheet_id}">
	<input type="hidden" id="target_team_id" name="target_team_id" value="{$target_team_id}">
	
	<input type="hidden" id="display_meetings" name="display_meetings" {$display_meetings}>
	<input type="hidden" id="display_calls" name="display_calls" {$display_calls}>
	<input type="hidden" id="display_tasks" name="display_tasks" {$display_tasks}>
	<input type="hidden" id="display_project_tasks" name="display_project_tasks" {$display_project_tasks}>
	<input type="hidden" id="display_events" name="display_events" {$display_events}>
	<input type="hidden" id="display_leave" name="display_leave" {$display_leave}>
	<input type="hidden" id="display_booked_hours" name="display_booked_hours" {$display_booked_hours}>	
	<input type="hidden" id="target_department" name="target_department" value="{$target_department}">
	<input type="hidden" id="forDashlet" name="forDashlet" value="{$forDashlet}">
</form>

{if !$forDashlet && $tasks}
<table cols="3" border="0" cellpadding="0" cellspacing="0" width="100%"><tr><td>
{/if}

<table cols="1" border="0" cellpadding="0" cellspacing="0" width="100%">
	<tr>
		<td>
			{$calendar_tab}
			<table style="height:40px;" cols="3" class="tabForm calendarHead" border="0" cellpadding="0" cellspacing="0" width="100%">	
				<tr>
					<td valign="middle" nowrap="nowrap" width="25%" style="padding-left:0.5em">
					{assign var="calendar_ctrl" value="DayCalendarCtrl"}
					{include file="modules/Calendar/CalendarViewer/FilterForm.tpl"}
					</td>
					<td valign="middle" align="center" nowrap="nowrap" width="50%" style="padding:0 1em">
						<button type="button" onclick="DayCalendarCtrl.moveCalendar('{$prev_date}');" title="{$APP.LBL_PREVIOUS_DAY}" class="input-button input-outer nav-button"><div class="input-icon icon-prev"></div></button>
						<span class="monthHeaderH3" id="target_date_text">
							&nbsp;{$cal_dt.formattedDate}
						</span>
						<span class="{$weekdayfonts.$target_weekday_index}">({$target_weekday})</span>&nbsp;
						<button type="button" onclick="DayCalendarCtrl.moveCalendar('{$next_date}');" title="{$APP.LBL_NEXT_DAY}" class="input-button input-outer nav-button"><div class="input-icon icon-next"></div></button>
						{$target_rokuyo}
						{if $target_holiday != ""}
							<br /><span class="holiday">{$target_holiday}</span>
						{/if}
					</td>
					<td valign="middle" align="right" nowrap="nowrap" width="25%">
						<a style="font-weight:bold;" name="print_link" class="NextPrevLink" href="#">&nbsp;{$APP.LNK_PRINT}</a>
						<a style="font-weight:bold;border-left:1px solid #CCCCCC;" href="javascript:DayCalendarCtrl.moveCalendar('{$to_day}');" class="NextPrevLink">&nbsp;{$APP.LBL_CALENDAR_TODAY}</a>
						<a style="font-weight:bold;border-left:1px solid #CCCCCC;margin-right:0.5em;" href="#" class="NextPrevLink" id="select_day" >&nbsp;{$APP.LNK_SELECT_DATE}&nbsp;</a>
					</td>
				</tr>
				<tr>
					<td colspan="3" valign="middle" nowrap="nowrap" width="100%" style="padding:0 0.5em">
						{include file="modules/Calendar/CalendarViewer/SettingsBar.tpl"}
					</td>				
				</tr>
			</table>
		</td>
	</tr>
	
	<tr>
		<td valign="top" style="position:relative;">
			<div id="div_calendar" class="calendarbox"><div style="max-width: 800px">
				<table 
				{if $canEdit}
				onMouseDown="DayCalendarCtrl.mouseDown(this, event); return false;" onMouseUp="DayCalendarCtrl.mouseUp(this, event);" onMouseMove="DayCalendarCtrl.mouseMove(this, event); return false;"
				{else}
				onMouseDown="return false;"
				{/if}
				class="calendar_outer_table" id="calendar_outer_table" cellpadding="0" cellspacing="0" width="100%"><tbody>
					<tr class="calendar_all_day_row">
						<td valign="top" class="calendar_all_day" style="width:{$hour_width}px; max-width:{$hour_width}px" >{$MOD.LBL_ALL_DAY_COL_HEADER}</td>
						<td class="calendar_all_day_content" id="calendar_all_day_content" style="min-width:{$day_min_width}px">&nbsp;</td>
					</tr>
{foreach from=$hours key=hour item=hour_info}
					<tr class="calendar_hour_row">
						<td valign="top" class="calendar_hour" style="width:{$hour_width}px; max-width:{$hour_width}px" >{$hour_info.display}</td>
						<td class="calendar_hour_content" id="calendar_hour_content-{$hour}">&nbsp;</td>
					</tr>
					<tr class="calendar_hour_row half">
						<td class="calendar_hour half">&nbsp;</td>
						<td class="calendar_hour_content half" id="calendar_hour_content_half-{$hour}">&nbsp;</td>
					</tr>
{/foreach}
				</tbody></table>

{if $summary}
	<div class="calendar_activity_box summary" style="top:1px;left:{$hour_width+4}px;width:400px;max-width:400px" onmouseover="DayCalendarCtrl.ov(this);" onmouseout="DayCalendarCtrl.ot(this);">
	<div class="summary_content">{$summary}</div>
	<div class="summary_details" id="summary_details_0">
	{foreach from=$activities item=activity}
		{assign var=activity_id value=$activity.id}
			{if $activity.is_daylong}
				{if $activity.isViewAble == true}
						<div 
						{if $activity.canEdit}
						    {if $MODE != 'timesheets'}
						        ondblclick="MeetingsEditView.show(this,  '{$activity.module}', '{$activity_id}', '{$target_user_id}');"
						    {else}
						        ondblclick="HoursEditView.show(this,  '{$activity_id}', '{$target_user_id}', '', '', '', '{$timesheet_id}');"
						    {/if}
						{/if}
						onselectstart="return false;" onmousedown="return false;" class="{$activity.cssClass}{if $activity.canEdit} can_edit{/if}">
							<table cols="2" class="week_activity_item{if $activity.is_duplicate==1} dup{/if}" border="0" cellspacing="0" cellpadding="2">
								<tr>
									<td width="22" class="week_activity_item" style="white-space: nowrap">
										<a href="index.php?module={$activity.module}&amp;action=DetailView&amp;record={$activity_id}" class="week_activity_item">
										{$activity.imgHTML}
										</a>
										<div class="input-icon icon-info" id="act_{$activity_id}_0" onmouseover="SUGAR.util.getAdditionalDetails('{$activity.module}', '{$activity_id}', 'act_{$activity_id}_0');" onmouseout="return SUGAR.util.clearAdditionalDetailsCall();"></div>
									</td>
									<td class="week_activity_item title">
										{$activity.displayTime}
									</td>
								</tr>
								<tr>
									<td colspan="2" class="week_activity_item content">
										{$activity.subject}
										{$activity.recurrenceImgHTML}									
									</td>
								</tr>
							</table>
						</div>
				{else}
						<div onselectstart="return false;" onmousedown="return false;" class="{$activity.cssClass}">
							<table cols="2" class="week_activity_item{if $activity.is_duplicate==1} dup{/if}" border="0" cellspacing="0" cellpadding="2">
								<tr>
									<td width="16" class="week_activity_item" nowrap>
										{$activity.imgHTML}
									</td>
									<td class="week_activity_item title">
										{$activity.displayTime}
									</td>
								</tr>
								<tr>
									<td colspan="2" class="week_activity_item content">
										{$activity.subject}
										{$activity.recurrenceImgHTML}									
									</td>
								</tr>
							</table>
						</div>
				{/if}
		{/if}
	{/foreach}
	</div>
	</div></div>

{/if}



{foreach from=$activities key=activity_id item=activity}
	{assign var=activity_id value=$activity.id}
	{if !$activity.is_daylong || !$summary}
	{if $activity.isViewAble}
                <div id="div_act_{$activity_id}" onmouseover="DayCalendarCtrl.ov(this);" onmouseout="DayCalendarCtrl.ot(this);"
				{if $activity.canEdit}
                    {if $MODE != 'timesheets'}
                        ondblclick="MeetingsEditView.show(this,  '{$activity.module}', '{$activity_id}', '{$target_user_id}');"
                    {else}
                        ondblclick="HoursEditView.show(this,  '{$activity_id}', '{$target_user_id}', '', '', '', '{$timesheet_id}');"
                    {/if}
				{/if}
				onselectstart="return false;" onmousedown="return false;" class="calendar_activity_box{if $activity.isDuplicate} dup{/if}{if $activity.canEdit} can_edit{/if}" style="top:{$activity.top+$top_offset}px;left:{$activity.left}px;width:{$activity.width}px;max-width:{$activity.width}px;height:{$activity.height}px;min-height:{$activity.height}px;z-index:{$activity.duplicateLevel}">
	{else}
				<div id="div_act_{$activity_id}" onmouseover="DayCalendarCtrl.ov(this);" onmouseout="DayCalendarCtrl.ot(this);" class="calendar_activity_box" style="top:{$activity.top+$top_offset}px;left:{$activity.left}px;width:{$activity.width}px;height:{$activity.height}px;min-height:{$activity.height}px;z-index:{$activity.duplicateLevel}">
	{/if}		
					<table cols="2" border="0" cellpadding="0" cellspacing="0" width="100%" class="calendar_activity_table">
						<tr class="activity_top_row">
							<td nowrap class="activity_title">
							{if $activity.isViewAble}
								<a href="index.php?module={$activity.module}&amp;action=DetailView&amp;record={$activity_id}" class="week_activity_item">{$activity.imgHTML}</a>
							{else}
								{$activity.imgHTML}
							{/if}
                            {if $activity.status_color != ''}
                                <div class="input-icon icon-led{$activity.status_color}" style="vertical-align: middle"></div>
                            {/if}
							{$activity.displayTime} {$activity.recurrenceImgHTML}
							{if $activity.height <= 25}
							<span class="short_title">
								&nbsp;{$activity.subject}
							</span>
							{/if}
                            </td>
						</tr>
						<tr>
							<td colspan="2" class="activity_content" style="{$activity.calendar_color}">
								<div class="input-icon icon-info" id="act_{$activity_id}" onmouseover="return SUGAR.util.getAdditionalDetails('{$activity.module}', '{$activity_id}', 'act_{$activity_id}');" onmouseout="return SUGAR.util.clearAdditionalDetailsCall();"></div>
								{$activity.subject} 
							</td>
						</tr>
					</table>
				</div>
	{/if}
{/foreach}
			</div>
		</td>
	</tr>
	<tr>
		<td>
			<div class="calendar_footer">
				<div class="calendar_footer_help">
					{$MOD.LBL_MODIFIED_HELP}
				</div>
			</div>
		</td>
	</tr>
</table>

{if !$forDashlet && $tasks}
</td>
<td width="1%">&nbsp;</td>
<td valign="top" width="35%">
{$tasks}
</td>
</tr>
</table>
{/if}

<input id="print_url" type="hidden" value="{$PRINT_LINK}" >
{iah_script}
window.defaultEditModule = '{$defaultEditModule}';
{$init_js}
setPrintLink({if $forDashlet}'dashlet_print_{$dashletId}'{/if});
{/iah_script}

