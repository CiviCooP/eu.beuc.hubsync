{* HEADER *}

<div class="help">
  <p>Fill in the settings in order to connect with HUB. See your HUB provider for more information.</p>
  <p>Please note that these settings are confidential.</p>
</div>

<p>
  <a href="{$mainPage}">&lt; Return to the main page</a>
  <br>
</p>

{foreach from=$elementNames item=elementName}
  <div class="crm-section">
    <div class="label">{$form.$elementName.label}</div>
    <div class="content">{$form.$elementName.html}</div>
    <div class="clear"></div>
  </div>
{/foreach}

{* FIELD EXAMPLE: OPTION 2 (MANUAL LAYOUT)

  <div>
    <span>{$form.favorite_color.label}</span>
    <span>{$form.favorite_color.html}</span>
  </div>

{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
