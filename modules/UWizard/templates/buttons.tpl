{if $result.status == 'fatal'}
<form method="post" action="index.php">
<input type="hidden" name="module" value="UWizard">
<input type="hidden" name="action" value="index">
<input type="hidden" name="uw_step" value="{$this_step}">
<input type="hidden" name="source" value="{$source}">
<input type="hidden" name="perform" value="{$action}">
<input type="hidden" name="retry" value="{$result.retry}">
<button type="submit" class="form-button" onclick="{literal}var self=this;setTimeout(function() { self.disabled=true; },50);{/literal}"><div class="input-icon icon-recur left"></div><span class="input-label">{$LANG.LBL_RETRY}</span></button>
<button type="button" class="form-button" onclick="SUGAR.util.loadUrl('?module=UWizard&amp;action=index');"><div class="input-icon icon-cancel left"></div><span class="input-label">{$LANG.LBL_CANCEL}</span></button>
</form>
{/if}

{if $result.status == 'success' || $result.status == 'warning'}
{if $result.final}
<button type="button" class="form-button" onclick="SUGAR.util.loadUrl('?module=UWizard&amp;action=index');"><div class="input-icon icon-return left"></div><span class="input-label">{$LANG.LBL_RETURN}</span></button>
{else}
<form method="post" action="index.php">
<input type="hidden" name="module" value="UWizard">
<input type="hidden" name="action" value="index">
<input type="hidden" name="uw_step" value="{$next_step}">
<input type="hidden" name="source" value="{$source}">
<input type="hidden" name="perform" value="{$action}">
<input type="hidden" name="retry" value="0">
{if $result.status == 'warning' && $result.retry}
<button type="button" class="form-button" onclick="this.form.uw_step.value='{$this_step}';{literal}this.form.retry.value='1';var self=this;setTimeout(function() { self.disabled=true; },50);this.form.submit();{/literal}"><div class="input-icon icon-recur left"></div><span class="input-label">{$LANG.LBL_RETRY}</span></button>
{/if}
<button type="submit" class="form-button" onclick="{literal}var self=this;setTimeout(function() { self.disabled=true; },50);{/literal}"><div class="input-icon icon-accept left"></div><span class="input-label">{$LANG.LBL_NEXT_STEP}</span></button>
</form>
{/if}
{/if}

