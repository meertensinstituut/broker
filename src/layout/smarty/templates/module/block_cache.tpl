<div class="title">Cache</div>

<div class="info">

  {if $_cacheType eq "list"}
  
    {if $_cacheList|@count gt 0}
      <div class="subtitle">{math equation="x*y+1" x=$_cachePage y=$_cacheNumber} - {math equation="x*y+z" x=$_cachePage y=$_cacheNumber z=$_cacheList|@count} from {$_cacheTotal|intval} item(s)</div>
      
      <table>
        <tr class="title">
          <td>&nbsp;</td>
          <td>Configuration</td>
          <td>Created</td>
          <td>Used</td>
          <td>Last Used</td>
          <td>Expires</td>
          <td>Actions</td>
        </tr>   
        {foreach $_cacheList as $item}
          <tr>
            <td>{$item@key + ($_cachePage*$_cacheNumber) + 1}</td>
            <td>{$item.configuration|escape:html}</td>
            <td>{$item.created|escape:html}</td>
            <td>{$item.used|escape:html}</td>
            <td>{$item.numberOfChecks|escape:html}</td>
            <td>{$item.expires|escape:html}</td>
            <td><form action="{$_configuration->url ("cache","list")|escape:javascript}" method="post"><input type="hidden" name="key" value="{$item.hash|escape:javascript}"/><button name="action" value="delete">delete</button>&nbsp;<button name="action" value="view">view</button></form></td>
          </tr>
        {/foreach}  
      </table>
      <br />
      {assign var="previousPage" value="`$_cachePage-1`"}    
      {assign var="nextPage" value="`$_cachePage+1`"} 
      {if $_cachePage gt 0}         
        {if $previousPage eq 0}
          {assign var="previousUrl" value=$_configuration->url ("cache","list")}
        {else}
          {assign var="listPreviousPage" value="list$previousPage"}
          {assign var="previousUrl" value=$_configuration->url ("cache",{$listPreviousPage})}
        {/if}
        <button onclick="location.href='{$previousUrl|escape:javascript}';">previous {$_cacheNumber|intval}</button>      
      {/if} 
      {if $nextPage*$_cacheNumber lt $_cacheTotal}         
        {assign var="listNextPage" value="list$nextPage"}
        {assign var="nextUrl" value=$_configuration->url ("cache",{$listNextPage})}
        <button onclick="location.href='{$nextUrl|escape:javascript}';">next {min($_cacheNumber,$_cacheTotal-($nextPage*$_cacheNumber))}</button> 
      {/if}  
    {elseif $_cacheTotal gt 0}
      <div class="subtitle">No items, but {$_cacheTotal|intval} item(s) available</div>
    {else}
      <div class="subtitle">No items available</div>
    {/if}
  {elseif $_cacheType eq "view"}
    <div class="subtitle">Details cache '{$_cacheData.hash|escape:html}'</div>
    <div>Back to the <a href="{$_configuration->url ("cache","list")|escape:javascript}">list of items</a></div>
    <br />
    <div>
      <table>
        {foreach $_cacheData as $item}
          <tr>
            <td>{$item@key|escape:html}</td>
            <td>{$item|escape:html}</td>
        {/foreach}
      </table>
    </div>  
  {else}  
    {if $_cacheTotal gt 0}        
      <div>
        <button onclick="location.href='{$_configuration->url ("cache","list")|escape:javascript}';">view list with {$_cacheTotal|intval} item(s)</button>
      </div>  
      <br />
    {else} 
      <div class="subtitle">0 items</div> 
    {/if}
    <div>
      <form method="post" action="{$_configuration->url ("cache","list")|escape:javascript}">
        <button name="action" value="reset">reset database</button>
      </form>
    </div>
  {/if}
  <br />
</div>

