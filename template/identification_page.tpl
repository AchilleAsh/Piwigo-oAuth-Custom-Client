{html_style}
#oauth_wrap .oauth { margin:0 2px; }
{/html_style}
  
<fieldset style="text-align:center;" id="oauth_wrap">
  <legend>{'Connexion'|translate}</legend>
  
{foreach from=$OAUTH.providers item=provider key=p}{strip}
  {if $provider.enabled }
  {if $provider.name="Webteam"}
    <a href="#" class="oauth oauth_webteam" data-idp="{$p}" title="{$provider.name}">Webteam</a>
  {else}
    <a href="#" class="oauth oauth_{$OAUTH.conf.identification_icon} {$p|strtolower}" data-idp="{$p}" title="{$provider.name}"></a>
  {/if}
  {/if}
{/strip}{/foreach}
</fieldset>