<div class="title">Status</div>

<div class="info">

  {if $_statusType eq "list"}
  
    {if $_statusList|@count gt 0}
      <div class="subtitle">{($_statusPage*$_statusNumber) + 1} - {($_statusPage*$_statusNumber) + $_statusList|@count} from {$_statusTotal|intval} item(s)</div>
      
      <table>
        <tr class="title">
          <td>&nbsp;</td>
          <td>Key</td>
          <td>Cache</td>
          <td>Created</td>
          <td>Started</td>
          <td>Updated</td>
          <td>Finished</td>
          <td>Expires</td>
          <td>Actions</td>
        </tr>   
        {foreach $_statusList as $item}
          <tr>
            <td>{$item@key + ($_statusPage*$_statusNumber) + 1}</td>
            <td>{$item.key|escape:html}</td>
            <td>{if $item.cache}yes{else}no{/if}</td>
            <td>{$item.created|escape:html}</td>
            <td>{$item.started|escape:html}</td>
            <td>{$item.updated|escape:html}</td>
            <td>{$item.finished|escape:html}</td>
            <td>{$item.expires|escape:html}</td>
            <td><form action="{$_configuration->url ("status","list")|escape:javascript}" method="post"><input type="hidden" name="key" value="{$item.key|escape:javascript}"/><button name="action" value="delete">delete</button>&nbsp;<button name="action" value="view">view</button></form></td>
          </tr>
        {/foreach}  
      </table>
      <br />
      {assign var="previousPage" value="`$_statusPage-1`"}    
      {assign var="nextPage" value="`$_statusPage+1`"} 
      {if $_statusPage gt 0}         
        {if $previousPage eq 0}
          {assign var="previousUrl" value=$_configuration->url ("status","list")}
        {else}
          {assign var="listPreviousPage" value="list$previousPage"}
          {assign var="previousUrl" value=$_configuration->url ("status",{$listPreviousPage})}
        {/if}
        <button onclick="location.href='{$previousUrl|escape:javascript}';">previous {$_statusNumber|intval}</button>      
      {/if} 
      {if $nextPage*$_statusNumber lt $_statusTotal}         
        {assign var="listNextPage" value="list$nextPage"}
        {assign var="nextUrl" value=$_configuration->url ("status",{$listNextPage})}
        <button onclick="location.href='{$nextUrl|escape:javascript}';">next {min($_statusNumber,$_statusTotal-($nextPage*$_statusNumber))}</button> 
      {/if}  
    {elseif $_statusTotal gt 0}
      <div class="subtitle">No items, but {$_statusTotal|intval} item(s) available</div>
    {else}
      <div class="subtitle">No items available</div>
    {/if}
  {elseif $_statusType eq "view"}
    <div class="subtitle">Details status '{$_statusData.key|escape:html}'</div>
    <div>Back to the <a href="{$_configuration->url ("status","list")|escape:javascript}">list of items</a></div>
    <br />
    <div>
      <table>
        {foreach $_statusData as $item}
          <tr>
            <td>{$item@key|escape:html}</td>
            <td>{$item|escape:html}</td>
        {/foreach}
      </table>
    </div>  
  {else}  
    {if $_statusTotal gt 0}        
      <div>
        <button onclick="location.href='{$_configuration->url ("status","list")|escape:javascript}';">view list with {$_statusTotal|intval} item(s)</button>
      </div>  
      <br />
    {else} 
      <div class="subtitle">0 items</div> 
    {/if}
    <div>
      <form method="post" action="{$_configuration->url ("status","list")|escape:javascript}">
        <button name="action" value="reset">reset database</button>
      </form>
    </div>
  {/if}
  <br />
</div>

