<p>
    <a href="{$mainPage}">&lt; Return to the main page</a>
    <br>
</p>
<p>
    Synchronization started at: {$lastRun}<br>
    Number of items in the queue: {$queueItems}
</p>
<h3>Priorities (count = {$countPriorities})</h3>
<div class="crm-block crm-content-block">
    <table class="crm-info-panel">
        <tr class="columnheader">
            <th>ID</th>
            <th>Priority</th>
            <th>Action</th>
        </tr>
        {foreach from=$priorities item=row}
            <tr class="{cycle values="odd-row,even-row"}">
                <td>{$row.id}</td>
                <td>{$row.name}</td>
                <td>{$row.sync_status}</td>
            </tr>
        {/foreach}
    </table>
</div>

<h3>Organizations (count = {$countOrgs})</h3>
<div class="crm-block crm-content-block">
    <table class="crm-info-panel">
        <tr class="columnheader">
            <th>ID</th>
            <th>Name</th>
            <th>Country</th>
            <th>Action</th>
        </tr>
        {foreach from=$orgs item=row}
            <tr class="{cycle values="odd-row,even-row"}">
                <td>{$row.id}</td>
                <td>{$row.name}</td>
                <td>{$row.country}</td>
                <td>{$row.sync_status}</td>
            </tr>
        {/foreach}
    </table>
</div>

<h3>Users (count = {$countUsers})</h3>
<div class="crm-block crm-content-block">
    <table class="crm-info-panel">
        <tr class="columnheader">
            <th>ID</th>
            <th>Last Name</th>
            <th>First Name</th>
            <th>Action</th>
        </tr>
        {foreach from=$users item=row}
            <tr class="{cycle values="odd-row,even-row"}">
                <td>{$row.id}</td>
                <td>{$row.last_name}</td>
                <td>{$row.first_name}</td>
                <td>{$row.sync_status}</td>
            </tr>
        {/foreach}
    </table>
</div>



