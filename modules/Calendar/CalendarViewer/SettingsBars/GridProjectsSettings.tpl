<div class="filter_field" id="div_main_settings">
<a class="NextPrevLink" href='#' style="font-weight:bold" onclick="{$calendar_ctrl}.showProjectForm(this); return false;">
<span id="span_project_filter">
	{$project_icon}&nbsp;{$PROJECT_NAME}
</span>
{iah_icon_image name="searchMore" style="vertical-align:bottom"}
</a>
</div>

<div id="div_select_project" style="width:400px;height:100px;position:absolute;display:none;z-index:9999;">
<table border="0" cellpadding="3" cellspacing="0" class="calendar_filter_form" height="90px;">
	<tr>
		<td align="left"><div id="div_project_label">{$MOD.LBL_PROJECT}:</div></td>
		<td align="left">
			<div id="div_project">
			<input type="text" class="sqsEnabled" name="project_name_selected" id="project_name_selected" autocomplete="off" value="">
			<input type="button" class="button" value="{$APP.LBL_SELECT_BUTTON_LABEL}" onclick='{$calendar_ctrl}.hideProjectForm();open_popup("Project", 600, 400, "", true, false, {$encoded_project_popup_request_data});'>
			<input type="hidden" name="project_id_selected" id="project_id_selected" value="">
			</div>
		</td>
	</tr>
	<tr>
		<td align="right" colspan="2">
			<input type="button" class="button" value="{$MOD.LBL_ALL_PROJECTS_BUTTON_LABEL}" onclick="{$calendar_ctrl}.applyProject(this.form);">
			<input type="button" class="button" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" onclick="{$calendar_ctrl}.hideProjectForm();">
		</td>
	</tr>	
</table>
</div>

<script type="text/javascript">
var calendar = {$calendar_ctrl};
{literal}
function checkProjectId() {
	setTimeout('var newId = $("project_id_selected").value; if (newId != "") calendar.applyProject($("settings_form"))', 200);	
}
function setProject(popup_data) {	
	set_return(popup_data);
	calendar.applyProject($('settings_form'));	
}
{/literal}
</script>
