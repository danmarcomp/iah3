<p>&nbsp;</p>
<div class="listViewTitle">
<h3>{$LANG.LBL_INSTALLED_PACKAGES}</h3>
</div>
<table cellspacing="0" cellpadding="0" border="0" width="100%" class="listView form-mid">
<thead class="listViewColumns">
	<tr class="listHead">
		<th width="3%" class="listViewThS1">
			<div class="listColLabel">&nbsp;</div>
		</th>
		<th width="39%" class="listViewThS1">
			<div class="listColLabel">{$LANG.LBL_NAME}</div>
		</th>
		<th width="16%" class="listViewThS1">
			<div class="listColLabel">{$LANG.LBL_TYPE}</div>
		</th>
		<th width="15%" class="listViewThS1">
			<div class="listColLabel">{$LANG.LBL_VERSION}</div>
		</th>
		<th width="20%" class="listViewThS1">
			<div class="listColLabel">{$LANG.LBL_DATE_INSTALLED}</div>
		</th>
		<th width="25%" class="listViewThS1">
			<div class="listColLabel">{$LANG.LBL_ACTION}</div>
		</th>
	</tr>
</thead>
<tbody>
	{foreach from=$packages item=package}
	<tr class="oddListRowS1">
		<td class="listViewTd">
			{$package.icon}
		</td>
		<td class="listViewTd">
			{$package.name}
		</td>
		<td class="listViewTd">
			{$LISTS.module_types_dom[$package.type]}
		</td>
		<td class="listViewTd">
			{$package.version}
		</td>
		<td class="listViewTd">
			{$package.date_entered}
		</td>
		<td class="listViewTd" rowspan="3">
			{if $package.uninstallable}
			<form method="post" action="index.php">
			<input type="hidden" name="module" value="UWizard">
			<input type="hidden" name="action" value="index">
			<input type="hidden" name="source" value="{$package.filename}">
			<input type="hidden" name="perform" value="prepare_uninstall">
			<button class="input-button input-outer" type="submit" style="vertical-align: top">
				<div class="input-icon icon-cancel left"></div><span class="input-label">{$LANG.LBL_UNINSTALL}</span>
			</button>
			</form>
			{/if}
		</td>
	</tr>
	<tr class="oddListRowS1">
		<td class="listViewTd" colspan="7">
			<div class="listAddLine">{$package.description}</div>
		</td>
	</tr>
	<tr><td colspan="7" class="listViewHRS1"></td></tr>
	{/foreach}
</tbody>
</table>

