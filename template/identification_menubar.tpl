{if $id == "mbIdentification" and isset($U_LOGIN)}
  {html_style}
  dl#mbIdentification dd:first-of-type { padding-bottom:0 !important; }
  #mbIdentification .oauth { margin:0 1px; }
  {/html_style}
  
  <dd>
    <form id="quickconnect">

    </form>
    <fieldset style="text-align:center;">
      <legend>{'Connexion'|translate}</legend>
    {foreach from=$OAUTH.providers item=provider key=p}{strip}

      {if $provider.enabled}
        {if $provider.name="Webteam"}
        <a href="#" class="oauth oauth_webteam" data-idp="{$p}" title="{$provider.name}">Webteam</a>
        {else}
        <a href="#" class="oauth oauth_{$OAUTH.conf.menubar_icon} {$p|strtolower}" data-idp="{$p}" title="{$provider.name}"></a>
        {/if}
      {/if}
    {/strip}{/foreach}
    </fieldset>

  </dd>
{/if}