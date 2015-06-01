<div id="dashlet-body-{$id}" style="padding-bottom: 0.5em;">
<table id="feed_rows_{$id}" cellpadding='0' cellspacing='0' width='100%' border='0' class='listView form-bottom'>
    {foreach from=$feed_data.items key=rowId item=rowData name=rowIteration}
		{if $smarty.foreach.rowIteration.iteration is odd}
			{assign var='_rowColor' value=$rowColor[0]}
		{else}
			{assign var='_rowColor' value=$rowColor[1]}
		{/if}
	<tr height='20' class='{$_rowColor}S1' onmouseover="sListView.row_action(this, 'over', '{$id}');" onmouseout="sListView.row_action(this, 'out', '{$id}');" id="feeds_row_{$rowId}" style="cursor: pointer">
		<td scope='row' onmousedown="return FeedsDashlet.displayFeedItem('{$id}', '{$rowId}');">
			{$rowData.title}
		</td>
		<td scope='row' valign="middle" style='vertical-align: middle; white-space: nowrap' width="50">
			{if $rowData.link}<a href="{$rowData.link|escape}" target="_blank" class="listViewTdToolsS1">{iah_icon_image name='view_inline'}&nbsp;{$STR.LBL_VIEW_LINK}</a>{/if}
		</td>
    </tr>
    {if ! $smarty.foreach.rowIteration.last}<tr><td colspan='20' class='listViewHRS1'></td></tr>{/if}
    {/foreach}
</table>
</div>
