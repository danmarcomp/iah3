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
	<input type="hidden" id="view_type" name="view_type" value="year">
	<input type="hidden" id="target_date" name="target_date" value="{$target_date}">
	<input type="hidden" id="view_mode" name="view_mode" value="{$MODE}">
	<input type="hidden" id="project_id" name="project_id" value="{$PROJECT_ID}">				
	<input type="hidden" id="target_type" name="target_type" value="{$target_type}">
	<input type="hidden" id="target_id" name="target_id" value="{$target_id}">
	<input type="hidden" id="selected_targets" name="selected_targets" value="{$selected_targets}">
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

<table border="0" cellpadding="0" cellspacing="0" width="100%">
	<tr>
		<td>
			{$calendar_tab}
			<table style="height:40px;" class="tabForm calendarHead" border="0" cellpadding="0" cellspacing="0" width="100%">	
				<tr>
					<td valign="middle" nowrap="nowrap" width="25%" style="padding-left:0.5em">
						<div id="div_filter_form" style="width:300px;height:100px;position:absolute;display:none;z-index:9999;">
							<form name="filter_form" id="filter_form" method="POST" action="index.php" onsubmit="return false;">
								<table border="0" cellpadding="1" cellspacing="0" class="filter_form">
									<tr>
										<td>
											<label><input type="radio" id="select_target_type_user" name="select_target_type" value="user" {$CHECKED_TARGET_TYPE_USER} onclick="YearCalendarCtrl.toggleSelectTargetType(this.form);">{$MOD.LBL_TARGET_USER}</label>
											<label><input type="radio" id="select_target_type_res" name="select_target_type" value="resource" {$CHECKED_TARGET_TYPE_RESOURCE} onclick="YearCalendarCtrl.toggleSelectTargetType(this.form);">{$MOD.LBL_TARGET_RESOURCE}</label>
											
											<div id="div_select_target_user" style="{$STYLE_DISPLAY_SELECT_USER}">
												<input type="text" name="user_name_selected" value="{$user_name_selected}" readonly>
												<input type="button" class="button" value="{$APP.LBL_SELECT_BUTTON_LABEL}"
													onclick='open_popup("Users", 600, 400, "", true, false, {$encoded_users_popup_request_data});'>
												<input type="hidden" name="user_id_selected" {$user_id_selected}>
											</div>
											<div id="div_select_target_resource" style="{$STYLE_DISPLAY_SELECT_RESOURCE}">
												<input type="text" name="res_name_selected" value="{$res_name_selected}" readonly>
												<input type="button" class="button" value="{$APP.LBL_SELECT_BUTTON_LABEL}"
													onclick='open_popup("Resources", 600, 400, "", true, false, {$encoded_res_popup_request_data});'>
												<input type="hidden" name="res_id_selected" {$res_id_selected}>
											</div>
<div>
{include file="modules/Calendar/CalendarViewer/DisplayTypeSelect.tpl"}
</div>											
										</td>
									</tr>
									<tr>
										<td align="center">
											<input type="button" class="button" value="{$MOD.LBL_APPLY_BUTTON_LABLE}" onclick="YearCalendarCtrl.applyFilter(this.form);">&nbsp;
											<input type="button" class="button" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" onclick="YearCalendarCtrl.hideFilterForm();">
										</td>
									</tr>
								</table>
							</form>
						</div>
					</td>
					<td valign="middle" align="center" nowrap="nowrap" width="50%" style="padding:0 1em">
						<span class="monthHeaderH3" id="target_date_text">
						<button type="button" onclick="YearCalendarCtrl.moveCalendar('{$prev_date}');" title="{$APP.LBL_PREVIOUS_YEAR}" class="input-button input-outer nav-button"><div class="input-icon icon-prev"></div></button>
						&nbsp;{$target_year}&nbsp;
						<button type="button" onclick="YearCalendarCtrl.moveCalendar('{$next_date}');" title="{$APP.LBL_NEXT_YEAR}" class="input-button input-outer nav-button"><div class="input-icon icon-next"></div></button>
						</span>
					</td>
					<td valign="middle" align="right" nowrap="nowrap" width="25%">
						{*<a style="font-weight:bold;" href="javascript:YearCalendarCtrl.moveCalendar('{$to_day}');" class="NextPrevLink">&nbsp;{$APP.LBL_CALENDAR_TODAY}</a>
						<a style="font-weight:bold;border-left:1px solid #CCCCCC;margin-right:0.5em;" href="#" class="NextPrevLink" id="select_day" >&nbsp;{$APP.LNK_SELECT_DATE}&nbsp;</a>*}
					</td>
				</tr>
{if $calEvents}
				<tr>
					<td colspan="3">
{foreach from=$calEvents key=calEvent_id item=calEvent}
					
{/foreach}
{foreach from=$myTasks key=task_id item=task}						
					
{/foreach}
					</td>
				</tr>
{/if}
			</table>
		</td>
	</tr>
	
	<tr>
		<td valign="top" >
			<div class="calendarbox">
			<table border="0" cellspacing="0" cellpadding="0" width="100%"><tbody>
				<tr>
{foreach from=$month1to6 item=contents}
					<td valign="top" width="17%" class="year_cal_month">
						<table border="0" cellspacing="0" cellpadding="0" width="100%" class="year_cal_month"><tbody>
							<tr>
								<td align="center" class="year_cal_month_head" colspan="2">{$contents.month_formatted}{$MOD.LBL_DECO_MONTH}</td>
							</tr>
	{foreach from=$contents.dayContents key=day item=dayContent}
							<tr class="{if $dayContent.date == $to_day}today{/if}">
								<td class="year_cal_day" width="20" align="center">
									<a class="year_cal_day" href="javascript:DayCalendarCtrl.asyncCalendarBody('{$dayContent.date}');">
										<span class="{$weekdayfonts[$dayContent.weekdayIndex]}">{$day}</span></a>
								</td>
								<td class="year_cal_day_content" width="80">
									{if $dayContent.holiday}<span class="holiday">{$dayContent.holiday}</span>{/if}&nbsp;
								</td>
							</tr>
	{/foreach}
						</tbody></table>
					</td>
{/foreach}
				</tr>
				<tr>
					<td class="year_cal_break">&nbsp;</td>
				</tr>
				<tr>
{foreach from=$month7to12 item=contents}
					<td valign="top" width="17%" class="year_cal_month">
						<table border="0" cellspacing="0" cellpadding="0" width="100%" class="year_cal_month"><tbody>
							<tr>
								<td align="center" class="year_cal_month_head" colspan="2">{$contents.month_formatted}{$MOD.LBL_DECO_MONTH}</td>
							</tr>
	{foreach from=$contents.dayContents key=day item=dayContent}
							<tr class="{if $dayContent.date == $to_day}today{/if}">
								<td class="year_cal_day" width="20" align="center">
									<a class="year_cal_day" href="javascript:DayCalendarCtrl.asyncCalendarBody('{$dayContent.date}');">
										<span class="{$weekdayfonts[$dayContent.weekdayIndex]}">{$day}</span></a>
								</td>
								<td class="year_cal_day_content" width="80">
									{if $dayContent.holiday}<span class="holiday">{$dayContent.holiday}</span>{/if}&nbsp;
								</td>
							</tr>
	{/foreach}
						</tbody></table>
					</td>
{/foreach}
				</tr>
			</tbody></table>
			</div>
		</td>
	</tr>
	
	
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
{iah_script}
{$init_js}
setPrintLink({if $forDashlet}'dashlet_print_{$dashletId}'{/if});
window.defaultEditModule = '{$defaultEditModule}';
{/iah_script}

