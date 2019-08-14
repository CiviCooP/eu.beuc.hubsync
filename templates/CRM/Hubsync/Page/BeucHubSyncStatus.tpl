<p>
    <a href="{$mainPage}">&lt; Return to the main page</a>
    <br>
</p>

<h3>Priorities (count = {$countPriorities})</h3>

{* Example: Display a variable directly *}
<p>The current time is {$currentTime}</p>

<h3>Users (count = {$countUsers})</h3>

<h3>Organizations (count = {$countOrgs})</h3>

{* Example: Display a translated string -- which happens to include a variable *}
<p>{ts 1=$currentTime}(In your native language) The current time is %1.{/ts}</p>
