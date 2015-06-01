<div style='width: 500px'>
<form id='configure_{$id}' id="feeds_edit_form" action="index.php" method="post" onSubmit='return SUGAR.sugarHome.postForm("configure_{$id}", SUGAR.sugarHome.uncoverPage);'>
<input type='hidden' name='id' value='{$id}'>
<input type='hidden' name='module' value='Home'>
<input type='hidden' name='action' value='ConfigureDashlet'>
<input type='hidden' name='to_pdf' value='true'>
<input type='hidden' name='configure' value='true'>
<table width="100%" cellpadding="0" cellspacing="0" border="0" class="tabForm" align="center">
<tr>
    <td valign='top' nowrap class='dataLabel'>{$STR.LBL_FIND_FEED}</td>
    <td valign='top' class='dataField'>
    	<input type="search" id="search_{$id}" name="search_value" size="25" autocomplete="off" onkeypress="if(event.keyCode == 13) {ldelim}FeedsDashlet.searchFeeds('{$id}', this.value); return false;{rdelim}">
    	<div id="feed_results_{$id}" style="display: none"></div>
    </td>
</tr>
<tr>
    <td valign='top' nowrap class='dataLabel'>{$STR.LBL_CONFIGURE_FEED_URL}</td>
    <td valign='top' class='dataField'>
    	<textarea id="feed_url_{$id}" name="feed_url" class="input-textarea" cols='40' rows='3'>{$feed_url|escape}</textarea>
    </td>
</tr>
<tr>
    <td valign='top' nowrap class='dataLabel'>{$STR.LBL_CONFIGURE_TITLE}</td>
    <td valign='top' class='dataField'>
    	<input type="text" id="feed_title_{$id}" class="input-text" name="title" size='25' value='{$title}'>
    </td>
</tr>
<tr>
    <td valign='top' nowrap class='dataLabel'>{$STR.LBL_CONFIGURE_DISPLAY_ROWS}</td>
    <td valign='top' class='dataField'>
    	<select name="display_rows">{$display_rows_options}</select>
    </td>
</tr>
<tr>
    <td valign='top' nowrap class='dataLabel'>{$refresh_label}</td>
    <td valign='top' class='dataField'>
    {html_options name=auto_refresh_time options=$refresh_options selected=$refresh_time}
    </td>
</tr>
<tr>
    <td align="right" colspan="2">
		<button type='button' class='input-button input-outer' onclick="SUGAR.sugarHome.hideConfigure();"><div class="input-icon icon-cancel left"></div><span class="input-label">{$cancelLbl}</span></button>
		<button type='submit' class='input-button input-outer'><div class="input-icon icon-accept left"></div><span class="input-label">{$saveLbl}</span></button>
   	</td>
</tr>
</table>
</form>
</div>