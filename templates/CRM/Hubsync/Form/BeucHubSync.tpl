{* HEADER *}

<div class="help">
  <p>This page allows you to <strong>manually synchronize</strong> the data between the HUB and CiviCRM.</p>
  <p>Make sure you filled in the <a href="{$settingsPage}">connection settings</a> before perfoming any of the actions below.</p>
  <p>You can also <a href="{$statusPage}">checkout out the status</a> of the latest synchronization.</p>
</div>

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
