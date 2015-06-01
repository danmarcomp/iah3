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
<div style="{$OUTER_DIV_STYLE}">
<table cols="1" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td valign="middle" nowrap="nowrap" class="team_cal_info">
			<span class="monthHeaderH3" id="target_date_text">
				{$cal_dt.formattedDate}
			</span>
			<span class="{$weekdayfonts[$cal_dt.localWeekDayIndex]}">({$cal_dt.localWeekDay})</span>
			{if $cal_dt.holiday != ""}
				<span class="week_day_holiday">{$cal_dt.holiday}</span>
			{/if}
		</td>
		<td align="right">
		</td>
	</tr>
	
	<tr>
		<td valign="top">
			<table class="team_cal" cellpadding="0" cellspacing="0"><tbody>
				<tr>
					<td width="100" class="team_cal_header" nowrap rowspan="2">&nbsp;</td>
{foreach from=$hours key=hour item=hour_info}
					<td width="25" colspan="4" align="center" class="team_cal_header">{$hour_info.display}</td>
{/foreach}
				</tr>
				<tr>
{foreach from=$hours key=hour item=hour_info}
					<td width="5" onmouseover="ResourceDayCalendarCtrl.ov(this);" onmouseout="ResourceDayCalendarCtrl.ot(this);" class="team_cal_header_min"
						>&nbsp;</td>
					<td width="5" onmouseover="ResourceDayCalendarCtrl.ov(this);" onmouseout="ResourceDayCalendarCtrl.ot(this);" class="team_cal_header_min noleft"
						>&nbsp;</td>
					<td width="5" onmouseover="ResourceDayCalendarCtrl.ov(this);" onmouseout="ResourceDayCalendarCtrl.ot(this);" class="team_cal_header_min"
						>&nbsp;</td>
					<td width="5" onmouseover="ResourceDayCalendarCtrl.ov(this);" onmouseout="ResourceDayCalendarCtrl.ot(this);" class="team_cal_header_min noleft"
						>&nbsp;</td>
{/foreach}
				</tr>
	
{foreach from=$activities_every_resource_array key=resource_id item=activities_every_resource}
				<tr>
					<td nowrap valign="top" class="team_cal_user" rowspan="{$activities_every_resource.max_level+1}">
						<input type="checkbox" name="selected_resource_id[]" value="{$resource_id}">
						<a href="javascript:MeetingsEditView.addResource('{$resource_id}')">
							<span id="span_res_name_{$resource_id}">{$activities_every_resource.full_name}</span>
						</a>
					</td>
	{assign var="pre_end_offset" value=$start_offset_min}
	
	{foreach from=$activities_every_resource.activities key=activity_id item=activity}
		{* 空白を追加 *}
		{if $pre_end_offset < $activity.startOffsetMinutes}
			{math equation="(x-y+1)/15" x=$activity.startOffsetMinutes y=$pre_end_offset format="%d" assign=diff}
					<td valign="top" colspan="{$diff}" class="team_cal_empty">
						&nbsp;
					</td>
		{/if}
		
		{* 予定を追加 *}
		{math equation="(x+y)" x=$activity.startOffsetMinutes y=$activity.durationMin format="%d" assign=endOffsetMinutes}

		{if $endOffsetMinutes > $end_offset_min}
			{math equation="(x-y+1)/15" x=$end_offset_min y=$activity.startOffsetMinutes format="%d" assign=diff}
			{assign var="pre_end_offset" value=$end_offset_min}
		{else}
			{math equation="(x-y+1)/15" x=$endOffsetMinutes y=$activity.startOffsetMinutes format="%d" assign=diff}
			{assign var="pre_end_offset" value=$endOffsetMinutes}
		{/if}
					<td  valign="top" colspan="{$diff}" class="light_cal_activity">
						<div id="div_act_{$activity_id}" valign="top">&nbsp;
						</div>
					</td>
	{/foreach}
	
	{* 一番最後の予定より後ろの部分に空白を埋める *}
	{if $pre_end_offset < $end_offset_min}
		{math equation="(x-y+1)/15" x=$end_offset_min y=$pre_end_offset format="%d" assign=diff}
					<td valign="top" colspan="{$diff}" class="team_cal_empty">
						&nbsp;
					</td>
	{/if}
				</tr>
				
	{* 時間が重複している予定 *}
	{foreach from=$activities_every_resource.activities_of_level key=level item=activities}
				<tr>
		{assign var="pre_end_offset" value=$start_offset_min}
		{foreach from=$activities key=activity_id item=activity}
			{* 空白を埋める *}
			{if $pre_end_offset < $activity.startOffsetMinutes}
				{math equation="(x-y+1)/15" x=$activity.startOffsetMinutes y=$pre_end_offset format="%d" assign=diff}
					<td valign="top" colspan="{$diff}" class="team_cal_empty">
						&nbsp;
					</td>
			{/if}

			{* 予定を追加 *}
			{math equation="(x+y)" x=$activity.startOffsetMinutes y=$activity.durationMin format="%d" assign=endOffsetMinutes}
	
			{if $endOffsetMinutes > $end_offset_min}
				{math equation="(x-y+1)/15" x=$end_offset_min y=$activity.startOffsetMinutes format="%d" assign=diff}
				{assign var="pre_end_offset" value=$end_offset_min}
			{else}
				{math equation="(x-y+1)/15" x=$endOffsetMinutes y=$activity.startOffsetMinutes format="%d" assign=diff}
				{assign var="pre_end_offset" value=$endOffsetMinutes}
			{/if}
					<td  valign="top" colspan="{$diff}" class="light_cal_activity">
						<div valign="top">&nbsp;</div>
					</td>
		{/foreach}
			
		{* 一番最後の予定より後ろの部分に空白を埋める *}
		{if $pre_end_offset < $end_offset_min}
			{math equation="(x-y+1)/15" x=$end_offset_min y=$pre_end_offset format="%d" assign=diff}
					<td valign="top" colspan="{$diff}" class="team_cal_empty">
						&nbsp;
					</td>
		{/if}
				</tr>
	{/foreach}
{/foreach}
			</tbody></table>
		</td>
	</tr>
</table>
</div>
