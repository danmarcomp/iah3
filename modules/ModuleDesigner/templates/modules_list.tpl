<table width="100%" cellpadding="0" cellspacing="1" border="0" class="tabDetailView2">
{foreach from=$modules item=mod key=idx}
{if !($idx & 1)}
 	<tr>
{/if}
		<td width="25%" class="tabDetailViewDL2" nowrap>
		{$mod.icon}
		{$mod.label}
		</td>
		<td width="25%" class="tabDetailViewDL2" nowrap >
		<a href="index.php?module=ModuleDesigner&action=EditView&mod_name={$mod.name}">{$LANG.LBL_EDIT_MODULE}</a> &nbsp; 
		<a href="index.php?module=ModuleDesigner&action=EditFields&mod_name={$mod.name}">{$LANG.LBL_EDIT_FIELDS2}</a> &nbsp; 
		<a href="index.php?module=ModuleDesigner&action=Relations&mod_name={$mod.name}">{$LANG.LBL_EDIT_RELATIONS2}</a> &nbsp; 
		<a href="index.php?module=ModuleDesigner&action=Subpanels&mod_name={$mod.name}">{$LANG.LBL_EDIT_SUBPANELS2}</a> &nbsp; 
		</td>
{if ($idx & 1)}
 	</tr>
{/if}
{/foreach}
{if ($modules|@count) & 1}
		<td width="25%" class="tabDetailViewDL2" nowrap>&nbsp;</td>
		<td width="25%" class="tabDetailViewDL2" nowrap>&nbsp;</td>
 	</tr>
{/if}
</table>


