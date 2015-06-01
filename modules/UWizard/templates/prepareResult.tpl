<h2><div class="input-icon theme-icon module-Administration"></div> {if $uninstall}{$LANG.LBL_PREPARING_UNINSTALL}{else}{$LANG.LBL_PREPARING_INSTALL}{/if}: {$package_name} {$package_version}</h2>

{if $finished}
<div style="margin: 8px 0">
	<form method="post" action="index.php">
	<input type="hidden" name="module" value="UWizard">
	<input type="hidden" name="action" value="index">
	<input type="hidden" name="source" value="{$source}">
	<input type="hidden" name="perform" value="{if $uninstall}uninstall{else}install{/if}">
	<button type="submit" class="form-button"><div class="input-icon icon-accept left"></div><span class="input-label">{$LANG.LBL_PROCEED}</span></button>
	<button type="button" class="form-button" onclick="SUGAR.util.loadUrl('?module=UWizard&amp;action=index');"><div class="input-icon icon-cancel left"></div><span class="input-label">{$LANG.LBL_CANCEL}</span></button>
	</form>
</div>
{/if}

{foreach from=$results item=result key=index}
	<div style="border: solid 2px {if $result.status=='success'}#999{else}{$result.color}{/if}; padding: 8px; margin: 8px 0">
		{if $result.status == 'success'}
		<h4>{$result.led} {$LANG.LBL_STEP} {$index+1}: {$result.label}</h4>
		<p>{if $result.message}{$result.message}{else}{$LANG.LBL_STEP_COMPLETED}{/if}</p>
		{else}
		<h4>{$result.led} {$LANG.LBL_STEP} {$index+1}: {$result.label}</h4>
		{if ! $result.no_default_message}<p>{if $result.status == 'warning'}{$LANG.LBL_STEP_WARNINGS}{else}{$LANG.LBL_STEP_FAILED}{/if}</p>{/if}
		<p>{$result.message}</p><p>{if $result.final && $finished}{$LANG.LBL_READY_INSTALL}{/if}</p>
		{if $result.errors}
		<p>
		{foreach from=$result.errors item=error}
		<div>{$error}</div>
		{/foreach}
		</p>
		{/if}
		{/if}
		{if $result.messages}
			<table class="tabDetailView" width="700" cellspacing=0>
			<tbody>
				{foreach from=$result.messages item=message}
					<tr>
						<td width="50%" class="tabDetailViewDL" style="text-align: left">
							{$message.name}
							{if $message.ext && $message.level!='info'}({$message.ext}){/if}
						</td>
						<td width="50%" class="tabDetailViewDF">
							<span style="color: {$colors[$message.level]}">
							{$message.message}
							</span>
						</td>
					</tr>
				{/foreach}
			</tbody>
			</table>
		{/if}
		
		{if $result.final}
		{if $result.status == 'success'}
		<p><b>{if $uninstall}{$LANG.LBL_PREPARED_UNINSTALL}{else}{$LANG.LBL_PREPARED_INSTALL}{/if}</b></p>
		{/if}
		
		{if $result.status == 'error'}
		<div style="margin-top: 0.5em">
		<form method="post" action="index.php">
		<input type="hidden" name="module" value="UWizard">
		<input type="hidden" name="action" value="index">
		<input type="hidden" name="source" value="{$source}">
		<input type="hidden" name="perform" value="{$action}">
		<input type="hidden" name="confirm" value="{$confirm}">
		<input type="hidden" name="conditions" value="{$conditions}">
		<button type="submit" class="form-button" onclick="{literal}var self=this;setTimeout(function() { self.disabled=true; },50);{/literal}"><div class="input-icon icon-recur left"></div><span class="input-label">{$LANG.LBL_RETRY}</span></button>
		<button type="button" class="form-button" onclick="SUGAR.util.loadUrl('?module=UWizard&amp;action=index');"><div class="input-icon icon-cancel left"></div><span class="input-label">{$LANG.LBL_CANCEL}</span></button>
		</form>
		</div>
		{/if}
		
		{if $result.status == 'warning'}
		<div style="margin-top: 0.5em">
		<form method="post" action="index.php">
		<input type="hidden" name="module" value="UWizard">
		<input type="hidden" name="action" value="index">
		<input type="hidden" name="source" value="{$source}">
		<input type="hidden" name="perform" value="{$action}">
		<input type="hidden" name="confirm" value="{$confirm}">
		{if $result.retry}<button type="submit" class="form-button" onclick="{literal}var self=this;setTimeout(function() { self.disabled=true; },50);{/literal}"><div class="input-icon icon-recur left"></div><span class="input-label">{$LANG.LBL_RECHECK}</span></button>{/if}
		<button type="button" class="form-button" onclick="this.form.confirm.value += ',{$result.step}';{literal}var self=this;setTimeout(function() { self.disabled=true; },50);this.form.submit();{/literal}"><div class="input-icon icon-accept left"></div><span class="input-label">{$LANG.LBL_PROCEED}</span></button>
		<button type="button" class="form-button" onclick="SUGAR.util.loadUrl('?module=UWizard&amp;action=index');"><div class="input-icon icon-cancel left"></div><span class="input-label">{$LANG.LBL_CANCEL}</span></button>
		</form>
		</div>
		{/if}
		{/if}
	</div>
{/foreach}

{if $finished}
<div style="margin: 8px 0">
	<form method="post" action="index.php">
	<input type="hidden" name="module" value="UWizard">
	<input type="hidden" name="action" value="index">
	<input type="hidden" name="source" value="{$source}">
	<input type="hidden" name="perform" value="{if $uninstall}uninstall{else}install{/if}">
	<button type="submit" class="form-button"><div class="input-icon icon-accept left"></div><span class="input-label">{$LANG.LBL_PROCEED}</span></button>
	<button type="button" class="form-button" onclick="SUGAR.util.loadUrl('?module=UWizard&amp;action=index');"><div class="input-icon icon-cancel left"></div><span class="input-label">{$LANG.LBL_CANCEL}</span></button>
	</form>
</div>
{/if}
