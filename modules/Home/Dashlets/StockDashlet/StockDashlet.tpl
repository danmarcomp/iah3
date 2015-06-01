<div id="dashlet-body-{$id}" style="padding-bottom: 0.5em">
<table id="stock_rows_{$id}" cellpadding='0' cellspacing='0' width='100%' border='0' class='listView form-bottom'>
    <tr height='20'>
		<td scope='col' class='listViewThS1' nowrap>
			{$LANG.LBL_SYMBOL}
		</td>
		<td scope='col' class='listViewThS1' nowrap>
			{$LANG.LBL_LAST_TRADE}
		</td>
		<td scope='col' class='listViewThS1' nowrap>
			{$LANG.LBL_CHANGE}
		</td>
    </tr>

	{foreach name=rowIteration from=$data key=row_id item=rowData}
		{if $smarty.foreach.rowIteration.iteration is odd}
			{assign var='_rowColor' value=$rowColor[0]}
		{else}
			{assign var='_rowColor' value=$rowColor[1]}
		{/if}
		<tr height='20' class='{$_rowColor}S1' onmouseover="sListView.row_action(this, 'over', '{$row_id}');" onmouseout="sListView.row_action(this, 'out', '{$row_id}');" {if $rowData.n}onmousedown="return StockQuotes.markRow(this, 'click', '{$row_id}', '{$id}');" id="stockquotes_row_{$row_id}" style="cursor: pointer"{/if}>
			<td scope='row' valign=top>
				{$rowData.s}
			</td>
			<td scope='row' valign=top>
				{$rowData.l1}
			</td>
			<td scope='row' valign=top>
				{$rowData.c6}
			</td>
	    </tr>
		{if ! $smarty.foreach.rowIteration.last}<tr><td colspan='20' class='listViewHRS1'></td></tr>{/if}
	{/foreach}
</table>
<div id="stock_details_{$id}" style="display:none" class="fade hidden">
<table cellpadding='0' cellspacing='0' width='100%' border='0' class="h3Row">
<tr><td width="90%">
	<h3 id="stock_name_{$id}"></h3>
</td><td nowrap>
	<a href="http://finance.yahoo.com/q?s=" id="stock_ext_link_{$id}" class="chartToolsLink" target="_blank">{$LANG.LBL_VISIT_SITE} {iah_icon_image name="view_inline" attrs="border='0'"}</a>
</td></tr>
</table>
<div class="form-bottom opaque">
<table cellpadding='0' cellspacing='0' width='100%' border='0' class='listView'>
	<tr class="oddListRowS1">
		<td width="20%">{$LANG.LBL_LAST_TRADE}</td>
		<td width="30%" style="font-weight:bold" id="stock_last_trade_{$id}"></td>
		<td width="20%">{$LANG.LBL_DAYS_RANGE}</td>
		<td width="30%" style="font-weight:bold" id="stock_days_range_{$id}"></td>
	</tr>
	<tr><td colspan='20' class='listViewHRS1'></td></tr>
	<tr class="oddListRowS1">
		<td>{$LANG.LBL_TRADE_TIME}</td>
		<td style="font-weight:bold" id="stock_last_trade_time_{$id}"></td>
		<td>{$LANG.LBL_52WK_RANGE}</td>
		<td style="font-weight:bold" id="stock_last_52w_range_{$id}"></td>
	</tr>
	<tr><td colspan='20' class='listViewHRS1'></td></tr>
	<tr class="oddListRowS1">
		<td>{$LANG.LBL_CHANGE}</td>
		<td style="font-weight:bold" id="stock_change_{$id}"></td>
		<td>{$LANG.LBL_VOLUME}</td>
		<td style="font-weight:bold" id="stock_volume_{$id}"></td>
	</tr>
	<tr><td colspan='20' class='listViewHRS1'></td></tr>
	<tr class="oddListRowS1">
		<td>{$LANG.LBL_PREV_CLOSE}</td>
		<td style="font-weight:bold" id="stock_prev_close_{$id}"></td>
		<td>{$LANG.LBL_AVG_VOLUME}</td>
		<td style="font-weight:bold" id="stock_avg_volume_{$id}"></td>
	</tr>
	<tr><td colspan='20' class='listViewHRS1'></td></tr>
	<tr class="oddListRowS1">
		<td>{$LANG.LBL_OPEN}</td>
		<td style="font-weight:bold" id="stock_open_{$id}"></td>
		<td>{$LANG.LBL_MARKET_CAP}</td>
		<td style="font-weight:bold" id="stock_market_cap_{$id}"></td>
	</tr>
	<tr><td colspan='20' class='listViewHRS1'></td></tr>
	<tr class="oddListRowS1">
		<td>{$LANG.LBL_BID}</td>
		<td style="font-weight:bold" id="stock_bid_{$id}"></td>
		<td>{$LANG.LBL_P_E}</td>
		<td style="font-weight:bold" id="stock_pe_{$id}"></td>
	</tr>
	<tr><td colspan='20' class='listViewHRS1'></td></tr>
	<tr class="oddListRowS1">
		<td>{$LANG.LBL_ASK}</td>
		<td style="font-weight:bold" id="stock_ask_{$id}"></td>
		<td>{$LANG.LBL_EPS}</td>
		<td style="font-weight:bold" id="stock_eps_{$id}"></td>
	</tr>
	<tr><td colspan='20' class='listViewHRS1'></td></tr>
	<tr class="oddListRowS1">
		<td>{$LANG.LBL_1Y_TARGET_EST}</td>
		<td style="font-weight:bold" id="stock_1y_target_{$id}"></td>
		<td>{$LANG.LBL_DIV_YIELD}</td>
		<td style="font-weight:bold" id="stock_div_yield_{$id}"></td>
	</tr>
</table>
<div id="StockQuotesLinks_{$id}" style="text-align:center; display:none">{$LANG.LBL_CLICK_FOR_LARGER}</div>
<div id="stock_chart_{$id}" style="text-align:center; display:none">
</div>
</div>
</div>
</div>
