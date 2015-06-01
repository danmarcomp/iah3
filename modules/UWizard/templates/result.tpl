{if $result.status == 'success'}
{assign var=frame_color value='#00EE00'}
{elseif $result.status == 'warning'}
{assign var=frame_color value='#DD7500'}
{else}
{assign var=frame_color value='#FF0000}
{/if}
<div style="border: solid 2px {$frame_color}; padding: 8px; margin: 8px">
{if $result.status == 'success'}
<h4>{$LANG.LBL_STEP_COMPLETED}</h4>
{$result.message}
{else}
<h4>{if $result.status == 'warning'}{$LANG.LBL_STEP_WARNINGS}{else}{$LANG.LBL_STEP_FAILED}{/if}</h4>
{$result.message}

<p>
{foreach from=$result.errors item=error}
<div>{$error}</div>
{/foreach}
</p>
{/if}

