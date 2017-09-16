<div class="title">Collections</div>

<div class="info">

  {if $_collectionsType eq "list"}
  
    {if $_collectionsList|@count gt 0}
      <div class="subtitle">{($_collectionsPage*$_collectionsNumber) + 1} - {($_collectionsPage*$_collectionsNumber) + $_collectionsList|@count} from {$_collectionsTotal|intval} item(s)</div>
      
      <table>
        <tr class="title">
          <td>&nbsp;</td>
          <td>Key</td>
          <td>Initialised</td>
          <td>Dependencies</td>
          <td>Created</td>
          <td>Checked</td>
          <td>Expires</td>
          <td>Actions</td>
        </tr>   
        {foreach $_collectionsList as $item}
          <tr>
            <td>{$item@key + ($_collectionsPage*$_collectionsNumber) + 1}</td>
            <td>{$item.key|escape:html}</td>
            <td>{if $item.initialised}yes{else}no{/if}</td>
            <td>{if $item.collectionIds}yes{else}no{/if}</td>
            <td>{$item.created|escape:html}</td>
            <td>{$item.checked|escape:html}</td>
            <td>{$item.expires|escape:html}</td>
            <td><form action="{$_configuration->url ("collections","list")|escape:javascript}" method="post"><input type="hidden" name="key" value="{$item.key|escape:javascript}"/><button name="action" value="delete">delete</button>&nbsp;<button name="action" value="uncheck">uncheck</button>&nbsp;<button name="action" value="check">check</button>&nbsp;<button name="action" value="view">view</button></form></td>
          </tr>
        {/foreach}  
      </table>
      <br />
      {assign var="previousPage" value="`$_collectionsPage-1`"}    
      {assign var="nextPage" value="`$_collectionsPage+1`"} 
      {if $_collectionsPage gt 0}         
        {if $previousPage eq 0}
          {assign var="previousUrl" value=$_configuration->url ("collections","list")}
        {else}
          {assign var="listPreviousPage" value="list$previousPage"}
          {assign var="previousUrl" value=$_configuration->url ("collections",{$listPreviousPage})}
        {/if}
        <button onclick="location.href='{$previousUrl|escape:javascript}';">previous {$_collectionsNumber|intval}</button>      
      {/if} 
      {if $nextPage*$_collectionsNumber lt $_collectionsTotal}         
        {assign var="listNextPage" value="list$nextPage"}
        {assign var="nextUrl" value=$_configuration->url ("collections",{$listNextPage})}
        <button onclick="location.href='{$nextUrl|escape:javascript}';">next {min($_collectionsNumber,$_collectionsTotal-($nextPage*$_collectionsNumber))}</button> 
      {/if}  
    {elseif $_collectionsTotal gt 0}
      <div class="subtitle">No items, but {$_collectionsTotal|intval} item(s) available</div>
    {else}
      <div class="subtitle">No items available</div>
    {/if}
  {elseif $_collectionsType eq "view"}
    <div class="subtitle">Details collection '{$_collectionsData.key|escape:html}'</div>
    <div>Back to the <a href="{$_configuration->url ("collections","list")|escape:javascript}">list of collections</a></div>
    <br />
    <div>
      <table>
        {foreach $_collectionsData as $item}
          <tr>
            <td>{$item@key|escape:html}</td>
            <td>{$item|escape:html}</td>
        {/foreach}
      </table>
    </div>  
  {else}  
    {if $_collectionsTotal gt 0}        
      <div>
        <button onclick="location.href='{$_configuration->url ("collections","list")|escape:javascript}';">view list with {$_collectionsTotal|intval} item(s)</button>
      </div>  
      <br />
    {else} 
      <div class="subtitle">0 items</div> 
    {/if}
    <div>
      <form method="post" action="{$_configuration->url ("collections","list")|escape:javascript}">
        <button name="action" value="reset">reset database</button>
      </form>
    </div>
  {/if}
  <br />
</div>